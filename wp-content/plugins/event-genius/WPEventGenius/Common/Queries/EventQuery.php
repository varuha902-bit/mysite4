<?php
namespace WPEventGenius\Common\Queries;


use WPEventGenius\Common\Database;
use WPEventGenius\Common\Event\EventPost;
use WPEventGenius\Common\Registration\Field\FieldHandler;
use WPEventGenius\Common\Registration\Form\Form;
use WPEventGenius\Common\Services\RegistrationObjectFactory;
use WPEventGenius\Common\Utils\Formatter;
use WPEventGenius\Common\Utils\Utils;
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class EventQuery {

	protected $args;

	protected $events;

	protected $wp_query;

	protected $database;
	public function __construct( $args = array() ) {
		if ( empty( $args ) ) {
			$args = array();
		}

		if ( ! empty( $args['page'] ) ) {
			unset( $args['page'] );
		}
		if ( ! empty( $args['view'] ) ) {
			unset( $args['view'] );
		}


		$args['post_type'] = EVGE_EVENT_POST_TYPE;

		// Default to 'publish' status to exclude scheduled/future posts unless explicitly overridden
		// But allow all statuses when querying by specific ID (for admin management of draft/pending events)
		if ( ! isset( $args['post_status'] ) ) {
			// If querying by specific ID, allow all post statuses for admin management
			if ( ! empty( $args['ID'] ) ) {
				$args['post_status'] = array( 'publish', 'draft', 'pending', 'future' );
			} else {
				$args['post_status'] = 'publish';
			}
		}

		if ( ! empty( $args['ID'] ) ) {

			if ( ! is_array( $args['ID'] ) ) {
				$args['ID'] = array( $args['ID'] );
			}
			$args['post__in'] = $args['ID'];
			unset( $args['ID'] );
		} else {
			$args['orderby'] = 'meta_value';
			$args['order'] = 'ASC';
			$args['meta_key'] = 'evge_start_date_utc';
			$args['meta_type'] = 'DATETIME';
			$args['posts_per_page'] = ! empty( $args['posts_per_page'] ) ? $args['posts_per_page'] : EVGE_ADMIN_EVENTS_PER_PAGE;
			$qtype = 'upcoming';
			if ( ! empty( $args['qtype'] ) ) {
				$qtype = $args['qtype'];
				unset( $args['qtype'] );
			}

			if ( ! empty( $args['paged'] ) ) {
				$args['offset'] = ( $args['paged'] - 1 ) * $args['posts_per_page'];
			}

			switch ( $qtype ) {
				case 'past':
					$args['order'] = 'DESC';
					$args['meta_query'] = array(
						'relation' => 'AND',
						array(
							'key'     => 'evge_end_date',
							'value'   => wp_date( 'Y-m-d H:i:s' ),
							'compare' => '<',
							'type' => 'DATETIME'
						),
						array(
							'key' => 'evge_end_date',
							'compare' => 'EXISTS',
						),
					);
					break;
				case 'all':
					$args['order'] = 'ASC';
					break;
				case 'custom':
					$args['order'] = 'ASC';
					$start_date = isset( $args['start'] ) ? wp_date( 'Y-m-d H:i:s', strtotime( $args['start'] ) ) : wp_date( 'Y-m-d H:i:s' );
					$args['meta_query'] = array(
						array(
							'key'     => 'evge_start_date',
							'value'   =>  $start_date,
							'compare' => '>',
							'type' => 'DATETIME'
						)
					);
					break;
				case 'cur':
					// events that are currently happening
					$args['meta_query'] = array(
						'relation' => 'AND',
						array(
							'key'     => 'evge_start_date',
							'value'   =>  wp_date( 'Y-m-d H:i:s' ),
							'compare' => '<=',
							'type' => 'DATETIME'
						),
						array(
							'key'     => 'evge_end_date',
							'value'   =>  wp_date( 'Y-m-d H:i:s' ),
							'compare' => '>',
							'type' => 'DATETIME'
						),
					);
					break;
				default:
					$args['order'] = 'ASC';
					$start_date = isset( $args['start_date'] ) ? wp_date( 'Y-m-d H:i:s', strtotime( $args['start_date'] ) ) : wp_date( 'Y-m-d H:i:s' );
					$args['meta_query'] = array(
						'relation' => 'AND',
						array(
							'key'     => 'evge_end_date',
							'value'   =>  $start_date,
							'compare' => '>',
							'type' => 'DATETIME'
						),
						array(
							'key' => 'evge_end_date',
							'compare' => 'EXISTS',
						),
					);
					break;
			}

			$args['tax_query'] = array();
			if ( isset( $args['tag'] ) ) {
				if ( ! empty( $args['tag'] ) ) {

					$args['tax_query'][] = array(
						'taxonomy' => EVGE_EVENT_TAG_TYPE,
						'field'    => 'term_id',
						'terms'    => $args['tag'],
					);
				}
				unset( $args['tag'] );

			}

			if ( isset( $args['cat'] ) ) {
				if ( ! empty( $args['cat'] ) ) {
					$args['tax_query'][] = array(
						'taxonomy' => EVGE_EVENT_CATEGORY_TYPE,
						'field' => 'term_id',
						'terms' => $args['cat'],
					);
				}

				unset( $args['cat'] );

			}

		}

		// add support for "with" filter the post meta with the key "allow_registration" should be set to "enabled"
		if ( isset( $args['with'] ) && $args['with'] === 'with' ) {
			$args['meta_query'][] = array(
				'key' => 'evge_allow_registration',
				'value' => 'enabled',
			);
		}

		$unsets = array( 'start', 'with', 'stype' );
		foreach( $unsets as $unset ) {
			if ( isset( $args[ $unset ] ) ) {
				unset( $args[ $unset ] );
			}

		}

		$this->args = $args;

		$this->database = new Database();
	}

	public function get_events() {
		return $this->events;
	}

	public function add_wp_query() {
		$query = new \WP_Query( $this->args );

		$this->wp_query = $query;

		$this->events = $query->get_posts();

		wp_reset_postdata();
	}

	public function add_all( $args = array() ){
		$statuses = ! empty( $args['statuses'] ) && $args['statuses'] !== 'all' ? $args['statuses'] : array();
		if ( ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}
		$max = ! empty( $args['max'] ) ? $args['max'] : 10000;
		$attach_payments = true;
		$events = $this->get_events();
		$events_with_all = array();
		foreach ( $events as $event ) {
			$event_meta = get_post_meta( $event->ID );
			$event->event_meta = $event_meta;

			$event->registrations = $this->get_event_registrations( $event->ID, $statuses, $max, $attach_payments );
			$event->registration_status_counts = $this->get_registration_status_counts( $event->ID );

			$events_with_all[] = $event;
		}

		$this->events = $events_with_all;
	}

	public function get_event_registrations( $event_id, $statuses = array(), $max = 1000, $attach_payments = false ) {
		$db = $this->database;
		$where = array(
			array(
				'column' => 'event_id',
				'value' => $event_id,
				'compare' => '=',
				'type' => 'int'
			)
		);

		// Apply group filter if specified
		if ( ! empty( $this->args['group'] ) ) {
			$group_id = absint( $this->args['group'] );
			return $this->get_group_registrations( $event_id, $group_id, $statuses, $max, $attach_payments );
		}

		// Handle attendance status filter (Standard tier only)
		$attendance_status = ! empty( $this->args['attendance_status'] ) ? sanitize_key( $this->args['attendance_status'] ) : '';
		if ( ! empty( $attendance_status ) && $attendance_status !== 'all' && function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) {
			// Ensure attendance join is included
			if ( empty( $this->args['attendance'] ) ) {
				$this->args['attendance'] = true;
			}
		}

		// Apply filters to modify WHERE clauses for series registration collection
		$where = apply_filters( 'evge_event_registrations_where', $where, $event_id );

		if ( ! empty( $this->args['reg_search'] ) ) {
			return $db->search_registration_meta( $this->args['reg_search'], $event_id, $statuses );
		}

		// Determine which joins to include
		$joins = array();
		if ( $attach_payments || ! empty( $this->args['payments'] ) ) {
			$joins[] = 'payments';
		}
		if ( ! empty( $this->args['attendance'] ) ) {
			$joins[] = 'attendance';
		}
		// If no specific join requested, default to payments for backward compatibility
		if ( empty( $joins ) ) {
			$joins[] = 'payments';
		}

		// Add attendance status filter to WHERE clause if specified
		if ( ! empty( $attendance_status ) && $attendance_status !== 'all' && in_array( 'attendance', $joins, true ) ) {
			$where[] = array(
				'column' => 'attendance_status',
				'value' => $attendance_status,
				'compare' => '=',
				'type' => 'string'
			);
		}

		if ( ! empty( $statuses ) ) {
			foreach ( $statuses as $status ) {
				$where[] = array(
					'column' => 'status',
					'value' => $status,
					'compare' => '=',
					'type' => 'string'
				);
			}

			$registrations = $db->registration_joined_query( $where, $joins,  'registration_date DESC', $max, '', true );
			return $registrations;
		}

		$registrations = $db->registration_joined_query( $where, $joins, 'registration_date DESC', $max, '', true );
		return $registrations;
	}

	/**
	 * Get registrations for a specific group (main registration + guests)
	 *
	 * @param int $event_id Event ID
	 * @param int $group_id Group ID (main registration ID)
	 * @param array $statuses Array of status filters
	 * @param int $max Maximum number of results
	 * @param bool $attach_payments Whether to attach payment data
	 * @return array Array of registration records
	 */
	private function get_group_registrations( $event_id, $group_id, $statuses = array(), $max = 1000, $attach_payments = false ) {
		$db = $this->database;
		$registrations = array();

		// Handle attendance status filter (Standard tier only)
		$attendance_status = ! empty( $this->args['attendance_status'] ) ? sanitize_key( $this->args['attendance_status'] ) : '';
		if ( ! empty( $attendance_status ) && $attendance_status !== 'all' && function_exists( 'evge_is_standard_tier' ) && evge_is_standard_tier() ) {
			// Ensure attendance join is included
			if ( empty( $this->args['attendance'] ) ) {
				$this->args['attendance'] = true;
			}
		}

		// Determine which joins to include
		$joins = array();
		if ( $attach_payments || ! empty( $this->args['payments'] ) ) {
			$joins[] = 'payments';
		}
		if ( ! empty( $this->args['attendance'] ) ) {
			$joins[] = 'attendance';
		}
		// If no specific join requested, default to payments for backward compatibility
		if ( empty( $joins ) ) {
			$joins[] = 'payments';
		}

		// Get the main registration
		$main_where = array(
			array(
				'column' => 'event_id',
				'value' => $event_id,
				'compare' => '=',
				'type' => 'int'
			),
			array(
				'column' => 'id',
				'value' => $group_id,
				'compare' => '=',
				'type' => 'int'
			)
		);

		// Add status filters to main registration query
		if ( ! empty( $statuses ) ) {
			foreach ( $statuses as $status ) {
				$main_where[] = array(
					'column' => 'status',
					'value' => $status,
					'compare' => '=',
					'type' => 'string'
				);
			}
		}

		$main_registrations = $db->registration_joined_query( $main_where, $joins, 'registration_date DESC', 1, '', true );

		// Get guest registrations (parent = group_id)
		$guest_where = array(
			array(
				'column' => 'event_id',
				'value' => $event_id,
				'compare' => '=',
				'type' => 'int'
			),
			array(
				'column' => 'parent',
				'value' => $group_id,
				'compare' => '=',
				'type' => 'int'
			)
		);

		// Add status filters to guest registration query
		if ( ! empty( $statuses ) ) {
			foreach ( $statuses as $status ) {
				$guest_where[] = array(
					'column' => 'status',
					'value' => $status,
					'compare' => '=',
					'type' => 'string'
				);
			}
		}

		// Add attendance status filter to WHERE clauses if specified
		$attendance_status = ! empty( $this->args['attendance_status'] ) ? sanitize_key( $this->args['attendance_status'] ) : '';
		if ( ! empty( $attendance_status ) && $attendance_status !== 'all' && in_array( 'attendance', $joins, true ) ) {
			$main_where[] = array(
				'column' => 'attendance_status',
				'value' => $attendance_status,
				'compare' => '=',
				'type' => 'string'
			);
			$guest_where[] = array(
				'column' => 'attendance_status',
				'value' => $attendance_status,
				'compare' => '=',
				'type' => 'string'
			);
		}

		$guest_registrations = $db->registration_joined_query( $guest_where, $joins, 'registration_date DESC', $max - 1, '', true );
		$registrations = array_merge( $main_registrations, $guest_registrations );

		return $registrations;
	}

	public function hydrate() {
		$db = $this->database;
		$events = $this->get_events();

		$events_hydrated = array();
		foreach ($events as $event) {
			$factory = new RegistrationObjectFactory();
			$event_post = $factory->create_event_post($event->ID);
			$registration_counter = $factory->create_registration_counter($event->ID, $this->database);
			$event_post->set_registration_counter($registration_counter);
			$event->details = array();
			
			// Get recurrence info
			$is_recurrence = get_post_meta($event->ID, 'evge_is_recurrence', true);
			
			// Determine edit link - if it's a recurrence, link to template event
			$event->details['edit_link'] = get_edit_post_link(
				$is_recurrence ? $is_recurrence : $event->ID
			);
			if ($is_recurrence) {
				$event->details['edit_link'] = add_query_arg('recurrence_id', $event->ID, (string)$event->details['edit_link']);
			}

			// Rest of the existing hydration code...
			$event->details['cost_amount'] = $event_post->get_the_cost_amount();
			$event->details['cost_display'] = $event_post->get_the_cost_display();
			$event->details['allow_registration'] = $event_post->get_allow_registration();
			$event->details['capacity_display'] = $event_post->get_the_capacity_display( true );
			$event->details['registration_quantity'] = $registration_counter->get_active_count();
			$event->details['full_date'] = $event_post->get_the_full_date();
			$event->details['date_summary'] = $event_post->get_the_date_summary('brief');
			$event->details['start_date'] = $event_post->get_the_start_date();
			$event->details['end_date'] = $event_post->get_the_end_date();

			$event->details['organizer_id'] = $event_post->get_the_organizer_id();
			$event->details['venue_id'] = $event_post->get_the_venue_id();
			$event_form = $factory->create_form( $event_post->get_the_form_id() );
			$event_form->set_fields();
			$event->details['form'] = $event_form;
			$hydrated_registrations = array();
			if ( ! empty( $event->registrations ) ) {
				foreach ( $event->registrations as $registration ) {
					if ( ! empty( $registration['meta_cache'] ) ) {
						$registration['meta_cache'] = json_decode( $registration['meta_cache'], true );
						foreach ( $registration['meta_cache'] as $key => $value ) {
							$registration[ $key ] = $value;
						}
					}
					$registration = Utils::maybe_add_group_data( $registration, $event_post );

					if ( evge_is_free_tier() ) {
						// Calculate cost from event during hydration (for free tier optimization)
						$quantity = isset( $registration['quantity'] ) ? intval( $registration['quantity'] ) : 1;
						$payment_gross = isset( $registration['payment_gross'] ) ? $registration['payment_gross'] : 0;
						
						// If payment_gross is set and greater than 0, use it; otherwise calculate from event cost
						if ( ! empty( $payment_gross ) && (float) $payment_gross > 0 ) {
							$registration['payment_gross'] = (float) $payment_gross;
						} else {
							$event_cost = $event_post->get_the_cost_amount();
							if ( ! empty( $event_cost ) && is_numeric( $event_cost ) ) {
								$registration['payment_gross'] = (float) $event_cost * $quantity;
							} else {
								$registration['payment_gross'] = 0;
							}
						}
					}
					
					$registration['quantity_cost'] = Formatter::get_quantity_cost_display( $registration );
					$hydrated_registrations[] = $registration;
				}
			}

			$event->registrations = $hydrated_registrations;

			$events_hydrated[] = $event;
		}

		$this->events = $events_hydrated;
	}

	public function get_total_count() {
		return $this->wp_query->found_posts;
	}

	public function get_registration_status_counts($event_id) {
		// Use the factory to get the appropriate RegistrationCounter (free or Pro)
		$factory = new RegistrationObjectFactory();
		$registration_counter = $factory->create_registration_counter($event_id, $this->database);
		return $registration_counter->get_all_counts();
	}



	//$query->found_posts

}