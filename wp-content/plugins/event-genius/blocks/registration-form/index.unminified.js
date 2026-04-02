import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, SelectControl, Spinner, TextControl } from '@wordpress/components';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import debounce from 'lodash/debounce';

registerBlockType('wp-event-genius/registration-form', {
    title: __('Event Genius Registration Form', 'event-genius'),
    description: __('Display a customizable registration form for your events.', 'event-genius'),
    icon: 'clipboard',
    category: 'widgets',
    attributes: {
        formType: {
            type: 'string',
            default: 'inline'
        },
        eventId: {
            type: 'string',
            default: 'auto'
        },
        showHeader: {
            type: 'boolean',
            default: true
        },

    },
    
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const [events, setEvents] = useState([]);
        const [isLoading, setIsLoading] = useState(true);
        const [preview, setPreview] = useState('');
        const [previewLoading, setPreviewLoading] = useState(false);
        const [searchQuery, setSearchQuery] = useState('');
        const blockProps = useBlockProps();

        // Debounced search function
        const debouncedSearch = useCallback(
            debounce((query) => {
                setIsLoading(true);
                const searchParam = query ? `?search=${encodeURIComponent(query)}` : '';
                
                apiFetch({
                    path: `/evge/v1/upcoming-events${searchParam}`,
                    method: 'GET'
                })
                    .then((events) => {
                        const eventOptions = events.map(event => ({
                            label: `${event.title} - ${event.date}`,
                            value: event.id.toString(),
                            meta: {
                                startDate: event.startDate,
                                endDate: event.endDate,
                                venue: event.venue,
                                permalink: event.permalink
                            }
                        }));
                        
                        eventOptions.unshift({ 
                            label: __('Auto', 'event-genius'), 
                            value: 'auto' 
                        });
                        
                        setEvents(eventOptions);
                    })
                    .catch(error => {
                        console.error('Error fetching events:', error);
                        setEvents([{ 
                            label: __('Error loading events', 'event-genius'), 
                            value: '' 
                        }]);
                    })
                    .finally(() => {
                        setIsLoading(false);
                    });
            }, 300),
            []
        );

        // Fetch events on component mount and search changes
        useEffect(() => {
            debouncedSearch(searchQuery);
            return () => debouncedSearch.cancel();
        }, [searchQuery]);

        // Update preview when attributes change
        useEffect(() => {
            setPreviewLoading(true);
            apiFetch({
                path: '/wp-event-genius/v1/registration-form/preview',
                method: 'POST',
                data: {
                    event_id: attributes.eventId || 'auto',
                    show_header: attributes.showHeader,
                    form_type: attributes.formType
                }
            })
                    .then((response) => {
                        setPreview(response.html);
                    })
                    .catch(error => {
                        console.error('Error loading preview:', error);
                        setPreview(__('Error loading registration form preview.', 'event-genius'));
                    })
                    .finally(() => {
                        setPreviewLoading(false);
                    });
        }, [attributes.eventId, attributes.showHeader, attributes.formType]);

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Form Settings', 'event-genius')}>
                        <SelectControl
                            label={__('Form Display Type', 'event-genius')}
                            value={attributes.formType}
                            options={[
                                { label: __('Inline', 'event-genius'), value: 'inline' },
                                { label: __('Modal Button', 'event-genius'), value: 'modal' }
                            ]}
                            onChange={(value) => setAttributes({ formType: value })}
                        />
                        <div style={{ position: 'relative' }}>
                            <TextControl
                                label={__('Search Events', 'event-genius')}
                                value={searchQuery}
                                onChange={setSearchQuery}
                                placeholder={__('Search by title or date (e.g., "Conference" or "2024-03")', 'event-genius')}
                            />
                            <SelectControl
                                label={__('Select Event', 'event-genius')}
                                value={attributes.eventId || 'auto'}
                                options={events}
                                onChange={(value) => {
                                    const selectedEvent = events.find(e => e.value === value);
                                    setAttributes({ 
                                        eventId: value,
                                        showHeader: true
                                    });
                                }}
                                disabled={isLoading}
                                help={__('Auto means either the next upcoming event that allows registration or if added to a single event page, that event in context.', 'event-genius')}
                            />
                            {isLoading && (
                                <div style={{ position: 'absolute', right: '8px', top: '50%', transform: 'translateY(-50%)' }}>
                                    <Spinner />
                                </div>
                            )}
                        </div>
                        <ToggleControl
                            label={__('Show Event Info', 'event-genius')}
                            checked={attributes.showHeader}
                            onChange={(value) => setAttributes({ showHeader: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="wp-block-wp-event-genius-registration-form">
                    {previewLoading ? (
                        <div className="evge-loading-spinner">
                            <Spinner />
                            <p>{__('Loading registration form...', 'event-genius')}</p>
                        </div>
                    ) : (
                        <div dangerouslySetInnerHTML={{ __html: preview }} />
                    )}
                </div>
            </div>
        );
    },

    save: function() {
        // Return null because we'll render this dynamically
        return null;
    }
}); 