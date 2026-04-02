import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl, Spinner, CheckboxControl } from '@wordpress/components';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import debounce from 'lodash/debounce';

registerBlockType('wp-event-genius/attendee-list', {
    title: __('Event Genius Attendee List', 'event-genius'),
    description: __('Show a list of registered attendees for your events with customizable display options.', 'event-genius'),
    icon: 'groups',
    category: 'widgets',
    attributes: {
        eventId: {
            type: 'string',
            default: 'auto'
        },
        template: {
            type: 'string',
            default: 'auto'
        },
        perLoad: {
            type: 'string',
            default: '20'
        },
        selectedEventMeta: {
            type: 'object',
            default: {}
        },
        selectedFields: {
            type: 'array',
            default: []
        },
        simpleFormat: {
            type: 'string',
            default: ''
        }
    },
    
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const [events, setEvents] = useState([]);
        const [isLoading, setIsLoading] = useState(true);
        const [preview, setPreview] = useState('');
        const [previewLoading, setPreviewLoading] = useState(false);
        const [searchQuery, setSearchQuery] = useState('');
        const [availableFields, setAvailableFields] = useState([]);
        const [fieldsLoading, setFieldsLoading] = useState(false);
        const blockProps = useBlockProps();
        
        // Get current post information to detect if we're on an event page
        const currentPost = useSelect((select) => {
            try {
                const editor = select('core/editor');
                if (!editor) {
                    return null;
                }
                const postId = editor.getCurrentPostId();
                const postType = editor.getCurrentPostAttribute('type');
                return {
                    id: postId,
                    type: postType
                };
            } catch (e) {
                // Editor store might not be available in all contexts (e.g., widget editor)
                return null;
            }
        }, []);
        
        const isEventPage = currentPost && currentPost.type === 'evge_event';
        const currentEventId = isEventPage ? currentPost.id : null;

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
                        
                        // If we're on an event page, add "This Event" as the first option
                        // Check currentPost here to get fresh values
                        const post = currentPost;
                        const isEvent = post && post.type === 'evge_event';
                        const eventId = isEvent ? post.id : null;
                        
                        // Add "Auto" as the first option
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
            [currentPost]
        );

        // Fetch events on component mount and search changes
        useEffect(() => {
            debouncedSearch(searchQuery);
            return () => debouncedSearch.cancel();
        }, [searchQuery, debouncedSearch]);
        
        // Auto-select current event if on event page and eventId is 'auto'
        useEffect(() => {
            if (isEventPage && currentEventId && (attributes.eventId === 'auto' || !attributes.eventId)) {
                setAttributes({ 
                    eventId: currentEventId.toString() 
                });
            }
        }, [isEventPage, currentEventId, attributes.eventId, setAttributes]);

        // Fetch available fields when event or template changes
        useEffect(() => {
            const eventId = (attributes.eventId && attributes.eventId !== 'auto') 
                ? attributes.eventId 
                : (isEventPage ? currentEventId : null);
            const template = attributes.template === 'auto' ? 'auto' : attributes.template;
            const isFullTemplate = template === 'full';
            
            if (!eventId || eventId === 'auto') {
                setAvailableFields([]);
                // Clear selected fields if no event
                if (attributes.selectedFields && attributes.selectedFields.length > 0) {
                    setAttributes({ selectedFields: [] });
                }
                return;
            }
            
            setFieldsLoading(true);
            // Include all fields if template is "full"
            const includeAll = isFullTemplate ? '&include_all=true' : '';
            apiFetch({
                path: `/wp-event-genius/v1/attendee-list/event-fields?event_id=${eventId}${includeAll}`,
                method: 'GET'
            })
                .then((fields) => {
                    setAvailableFields(fields || []);
                    const fieldSlugs = (fields || []).map(f => f.slug).filter(Boolean);
                    
                    // For simple template, set default format if not set
                    if (template === 'simple' && !attributes.simpleFormat && fieldSlugs.length > 0) {
                        // Default format: {first} {last} if both exist, otherwise first available field
                        let defaultFormat = '';
                        if (fieldSlugs.includes('first') && fieldSlugs.includes('last')) {
                            defaultFormat = '{first} {last}';
                        } else if (fieldSlugs.length > 0) {
                            defaultFormat = `{${fieldSlugs[0]}}`;
                        }
                        if (defaultFormat) {
                            setAttributes({ simpleFormat: defaultFormat });
                        }
                    }
                    
                    // Only handle field selection for full template
                    if (isFullTemplate) {
                        // Filter selected fields to only include those that exist in the new event
                        const currentSelected = attributes.selectedFields || [];
                        const validSelected = currentSelected.filter(slug => fieldSlugs.includes(slug));
                        
                        // If no valid fields selected and fields are available, select all by default
                        if (validSelected.length === 0 && fieldSlugs.length > 0) {
                            setAttributes({ selectedFields: fieldSlugs });
                        } else if (validSelected.length !== currentSelected.length) {
                            // Some selected fields are no longer valid, update to only valid ones
                            setAttributes({ selectedFields: validSelected });
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching fields:', error);
                    setAvailableFields([]);
                })
                .finally(() => {
                    setFieldsLoading(false);
                });
        }, [attributes.eventId, attributes.template, isEventPage, currentEventId]);

        // Update preview when attributes change
        useEffect(() => {
            setPreviewLoading(true);
            // Use current event ID if on event page and eventId is 'auto'
            const previewEventId = (attributes.eventId && attributes.eventId !== 'auto')
                ? attributes.eventId
                : (isEventPage ? currentEventId : 'auto');
            
            apiFetch({
                path: '/wp-event-genius/v1/attendee-list/preview',
                method: 'POST',
                data: {
                    ...attributes,
                    eventId: previewEventId
                }
            })
                .then((response) => {
                    setPreview(response.html);
                })
                .catch(error => {
                    console.error('Error loading preview:', error);
                    setPreview(__('Error loading attendee list preview.', 'event-genius'));
                })
                .finally(() => {
                    setPreviewLoading(false);
                });
        }, [attributes, isEventPage, currentEventId]);

        const templateOptions = [
            { label: __('Auto', 'event-genius'), value: 'auto' },
            { label: __('Simple', 'event-genius'), value: 'simple' },
            { label: __('Full', 'event-genius'), value: 'full' }
        ];


        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Attendee List Settings', 'event-genius')}>
                        <div style={{ position: 'relative' }}>
                            <TextControl
                                label={__('Search Events', 'event-genius')}
                                value={searchQuery}
                                onChange={setSearchQuery}
                                placeholder={__('Search by title or date', 'event-genius')}
                            />
                            {isLoading && (
                                <div style={{ position: 'absolute', right: '8px', top: '50%', transform: 'translateY(-50%)' }}>
                                    <Spinner />
                                </div>
                            )}
                        </div>
                        <SelectControl
                            label={__('Select Event', 'event-genius')}
                            value={attributes.eventId || 'auto'}
                            options={events}
                            onChange={(value) => {
                                const selectedEvent = events.find(e => e.value === value);
                                setAttributes({ 
                                    eventId: value,
                                    selectedEventMeta: selectedEvent?.meta || {}
                                });
                            }}
                            disabled={isLoading}
                            help={__('Auto means either the next upcoming event that allows registration or if added to a single event page, that event in context.', 'event-genius')}
                        />
                        <SelectControl
                            label={__('Template', 'event-genius')}
                            value={attributes.template}
                            options={templateOptions}
                            onChange={(value) => setAttributes({ template: value })}
                        />
                        {attributes.eventId && attributes.template === 'simple' && (
                            <div>
                                <TextControl
                                    label={__('Attendee Format', 'event-genius')}
                                    value={attributes.simpleFormat || ''}
                                    onChange={(value) => setAttributes({ simpleFormat: value })}
                                    placeholder={(() => {
                                        const fieldPlaceholders = availableFields
                                            .map(f => `{${f.slug}}`)
                                            .join(' ');
                                        return fieldPlaceholders || '{first} {last}';
                                    })()}
                                />
                                {availableFields.length > 0 && (
                                    <div style={{ marginTop: '8px', padding: '8px', backgroundColor: '#f0f0f1', borderRadius: '4px', fontSize: '12px' }}>
                                        <div style={{ fontWeight: 'bold', marginBottom: '4px' }}>
                                            {__('Placeholders:', 'event-genius')}
                                        </div>
                                        <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                                            {availableFields.map((field) => (
                                                <div>
                                                    <code key={field.id || field.slug} style={{ 
                                                        padding: '2px 6px', 
                                                        backgroundColor: '#fff', 
                                                        borderRadius: '2px',
                                                        border: '1px solid #ddd'
                                                    }}>
                                                        {`{${field.slug}}`}
                                                    </code>
                                                    <span>{field.label}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                        {attributes.eventId && attributes.template === 'full' && (
                            <div>
                                <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>
                                    {__('Display Fields', 'event-genius')}
                                </label>
                                {fieldsLoading ? (
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                        <Spinner />
                                        <span>{__('Loading fields...', 'event-genius')}</span>
                                    </div>
                                ) : availableFields.length > 0 ? (
                                    <div style={{ maxHeight: '200px', overflowY: 'auto', border: '1px solid #ddd', padding: '8px', borderRadius: '4px' }}>
                                        {availableFields.map((field) => {
                                            const isChecked = attributes.selectedFields && attributes.selectedFields.includes(field.slug);
                                            return (
                                                <CheckboxControl
                                                    key={field.id || field.slug}
                                                    label={field.label || field.slug}
                                                    checked={isChecked}
                                                    onChange={(checked) => {
                                                        const currentFields = attributes.selectedFields || [];
                                                        let newFields;
                                                        if (checked) {
                                                            newFields = [...currentFields, field.slug];
                                                        } else {
                                                            newFields = currentFields.filter(slug => slug !== field.slug);
                                                        }
                                                        setAttributes({ selectedFields: newFields });
                                                    }}
                                                />
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <p style={{ color: '#666', fontStyle: 'italic' }}>
                                        {__('No fields available for this event.', 'event-genius')}
                                    </p>
                                )}
                            </div>
                        )}
                        <TextControl
                            label={__('Attendees Per Load', 'event-genius')}
                            type="number"
                            value={attributes.perLoad}
                            onChange={(value) => setAttributes({ perLoad: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="wp-block-wp-event-genius-attendee-list">
                    {previewLoading ? (
                        <div className="evge-loading-spinner">
                            <Spinner />
                            <p>{__('Loading attendee list...', 'event-genius')}</p>
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