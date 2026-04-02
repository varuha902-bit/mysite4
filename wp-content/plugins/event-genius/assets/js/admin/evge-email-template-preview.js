/**
 * Email Template Preview JavaScript
 * 
 * Handles email template preview functionality for the form builder
 * 
 * @package WPEventGenius
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    'use strict';

    // Email Template Preview Functionality
    $(document).on('change', '.evge-email-template-select', function() {
        var $select = $(this);
        var templateId = $select.val();
        var fieldId = $select.attr('id');
        var type = $select.closest('.evge-setting-flex-column').find('input[type="hidden"]').attr('name');
        
        // Extract type from the hidden input name if available
        if (type) {
            type = type.replace(/.*\[(.*)\].*/, '$1').replace('_email_template', '');
        }
        
        if (templateId) {
            loadEmailTemplatePreview(templateId, fieldId, type);
        } else {
            // Clear preview if no template selected
            $('#' + fieldId + '_preview').empty();
        }
    });

    function loadEmailTemplatePreview(templateId, fieldId, type) {
        var $previewContainer = $('#' + fieldId + '_preview');
        
        // Show loading state
        $previewContainer.html('<div class="evge-loading">Loading preview...</div>');
        
        $.ajax({
            url: evgeAdminCommon.ajaxUrl,
            type: 'POST',
            data: {
                action: 'evge_get_email_template_preview',
                template_id: templateId,
                type: type,
                nonce: evgeAdminCommon.ajaxNonce
            },
            success: function(response) {
                if (response.success) {
                    $previewContainer.html(response.data.html);
                } else {
                    $previewContainer.html('<div class="evge-error">Error loading preview: ' + response.data.message + '</div>');
                }
            },
            error: function() {
                $previewContainer.html('<div class="evge-error">Error loading preview</div>');
            }
        });
    }
});
