jQuery(document).ready(function($) {
	// Workaround for venue & organizer post types when editing or adding
	// so events parent menu stays open and active
	if ( $( 'body' ).hasClass( 'taxonomy-evge_event_cat' ) ) {
		$( '#toplevel_page_evge-events, #toplevel_page_evge-events a.wp-has-submenu' )
			.addClass( 'wp-menu-open wp-has-current-submenu wp-has-submenu' )
			.removeClass( 'wp-not-current-submenu' )
			.find( "li a[href='admin.php?page=evge-all-events']" )
			.parent()
			.addClass( 'current' );

		evgeFixMenu();

	}
	if ( $( 'body' ).hasClass( 'taxonomy-evge_event_tag' ) ) {
		$( '#toplevel_page_evge-events, #toplevel_page_evge-events a.wp-has-submenu' )
			.addClass( 'wp-menu-open wp-has-current-submenu wp-has-submenu' )
			.removeClass( 'wp-not-current-submenu' )
			.find( "li a[href='admin.php?page=evge-all-events']" )
			.parent()
			.addClass( 'current' );

		evgeFixMenu();


	}

	function evgeFixMenu() {
		$( "li a[href='edit.php']" )
			.removeClass( 'wp-has-current-submenu open-if-no-js' )
			.parent()
			.removeClass( 'wp-has-current-submenu open-if-no-js' )
			.addClass('wp-not-current-submenu')
			.find('.current').removeClass('current');
	}

	$('.taxonomy-evge_event_cat .evge-header-nav-move,.taxonomy-evge_event_tag .evge-header-nav-move').prependTo('.wrap');

	if ( $( 'body' ).hasClass( 'taxonomy-evge_event_cat' ) || $( 'body' ).hasClass( 'taxonomy-evge_event_tag' ) ) {
		$('.evge-admin-header-identity h1 .evge-current-page').text($('.wp-heading-inline').text());
		$('.wp-heading-inline').hide();
	}
	if ( $( '.evge-management-page' ).length ) {
		$('.evge-filter-move').appendTo($('#posts-filter'));
	}

	$('.taxonomy-evge_event_cat, .taxonomy-evge_event_tag, .evge-management-page').css('visibility', 'visible');

	// Remove links from the count column
	$('.taxonomy-evge_event_cat td.column-posts a, .taxonomy-evge_event_tag td.column-posts a').each(function() {
		var count = $(this).text();
		$(this).replaceWith(count);
	});
});