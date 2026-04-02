import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

registerBlockType('wp-event-genius/calendar', {
    title: __('Event Genius Calendar', 'event-genius'),
    description: __('Display a beautiful calendar of events with filtering and search capabilities.', 'event-genius'),
    icon: 'calendar',
    category: 'widgets',
    attributes: {
        calendarId: {
            type: 'string',
            default: ''
        }
    },
    
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const [preview, setPreview] = useState('');
        const [isLoading, setIsLoading] = useState(false);
        const [calendars, setCalendars] = useState([]);
        const blockProps = useBlockProps();

        // Detect if we're in a template editor (Site Editor)
        const isTemplateEditor = useSelect((select) => {
            try {
                const editor = select('core/editor');
                if (!editor) {
                    return false;
                }
                const postType = editor.getCurrentPostAttribute('type');
                return postType === 'wp_template' || postType === 'wp_template_part';
            } catch (e) {
                // Editor store might not be available in all contexts
                return false;
            }
        }, []);

        // Fetch available calendars
        useEffect(() => {
            apiFetch({
                path: '/wp/v2/evge_calendar?per_page=100&orderby=id&order=desc'
            })
                .then((response) => {
                    const calendarOptions = response.map(calendar => ({
                        label: calendar.name,
                        value: calendar.id.toString()
                    }));
                    setCalendars([
                        { label: __('Default Calendar', 'event-genius'), value: '' },
                        ...calendarOptions
                    ]);
                })
                .catch(error => {
                    console.error('Error loading calendars:', error);
                });
        }, []);

        // Fetch preview when calendar is selected (only for normal block editor, not template editor)
        useEffect(() => {
            if (isTemplateEditor) {
                // Skip preview loading in template editor
                return;
            }

                setIsLoading(true);
                apiFetch({
                    path: '/wp-event-genius/v1/calendar/preview',
                    method: 'POST',
                    data: {
                    calendar_id: attributes.calendarId || ''
                    }
                })
                    .then(response => {
                        setPreview(response.html);
                    })
                    .catch(error => {
                        console.error('Error loading preview:', error);
                        setPreview(__('Error loading calendar preview.', 'event-genius'));
                    })
                    .finally(() => {
                        setIsLoading(false);
                    });
        }, [attributes.calendarId, isTemplateEditor]);

        // Get selected calendar name for display
        const selectedCalendar = calendars.find(cal => cal.value === attributes.calendarId);
        const calendarName = selectedCalendar ? selectedCalendar.label : __('Default Calendar', 'event-genius');

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Event Calendar Settings', 'event-genius')}>
                        <SelectControl
                            label={__('Select Calendar', 'event-genius')}
                            value={attributes.calendarId}
                            options={calendars}
                            onChange={(value) => setAttributes({ calendarId: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="wp-block-wp-event-genius-calendar">
                    {isTemplateEditor ? (
                        // Show placeholder in template editor (Site Editor)
                        <div style={{
                            padding: '20px',
                            border: '1px dashed #ccc',
                            borderRadius: '4px',
                            textAlign: 'center',
                            backgroundColor: '#f9f9f9'
                        }}>
                            <div style={{ fontSize: '24px', marginBottom: '10px' }}>📅</div>
                            <div style={{ fontWeight: 'bold', marginBottom: '5px' }}>
                                {__('Calendar Content', 'event-genius')}
                            </div>
                            <div style={{ color: '#666', fontSize: '14px' }}>
                                {__('Displays the default calendar on the front end.', 'event-genius')}
                            </div>
                        </div>
                    ) : (
                        // Show preview in normal block editor
                        isLoading ? (
                        <div className="evge-loading-spinner">
                            <Spinner />
                            <p>{__('Loading events...', 'event-genius')}</p>
                        </div>
                    ) : (
                        <div dangerouslySetInnerHTML={{ __html: preview }} />
                        )
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