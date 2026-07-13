/**
 * Admin Script for Sincronizador Inmobiliario Multiplataforma
 */

jQuery(document).ready(function($) {

    /* ==========================================================================
       1. Tab Switching Logic
       ========================================================================== */
    // Handled mostly by page refresh via GET query parameters, but we can do transitions if needed.
    
    /* ==========================================================================
       2. WordPress Media Library Gallery Uploader
       ========================================================================== */
    var file_frame;
    
    $(document).on('click', '#gpp-select-gallery', function(e) {
        e.preventDefault();
        
        // If the media frame already exists, reopen it.
        if (file_frame) {
            file_frame.open();
            return;
        }
        
        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Seleccionar Imágenes para el Inmueble',
            button: {
                text: 'Añadir a la Galería'
            },
            multiple: true // Allow multiple files to be selected
        });
        
        // When an image is selected, run a callback.
        file_frame.on('select', function() {
            var attachments = file_frame.state().get('selection').toJSON();
            var img_ids = [];
            var html = '';
            
            // Get existing IDs if any
            var current_ids = $('#gpp_galeria').val();
            if (current_ids) {
                img_ids = current_ids.split(',').map(function(id) {
                    return id.trim();
                });
            }
            
            attachments.forEach(function(attachment) {
                // Prevent duplicate entries
                if (img_ids.indexOf(attachment.id.toString()) === -1) {
                    img_ids.push(attachment.id);
                    
                    var img_url = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                    
                    html += '<div class="gpp-gallery-item" data-id="' + attachment.id + '">';
                    html += '<img src="' + img_url + '" />';
                    html += '<button class="gpp-remove-image dashicons dashicons-no-alt" type="button"></button>';
                    html += '</div>';
                }
            });
            
            // Update hidden input field and preview wrapper
            $('#gpp_galeria').val(img_ids.join(','));
            $('#gpp-gallery-wrapper').append(html);
        });
        
        // Open the modal
        file_frame.open();
    });
    
    // Remove image from gallery
    $(document).on('click', '.gpp-remove-image', function(e) {
        e.preventDefault();
        var item = $(this).closest('.gpp-gallery-item');
        var id_to_remove = item.data('id').toString();
        var current_ids = $('#gpp_galeria').val().split(',');
        
        // Filter out the removed ID
        var new_ids = current_ids.filter(function(id) {
            return id.trim() !== id_to_remove;
        });
        
        $('#gpp_galeria').val(new_ids.join(','));
        item.fadeOut(250, function() {
            $(this).remove();
        });
    });

    /* ==========================================================================
       3. Log Payloads Disclosure
       ========================================================================== */
    $('.gpp-toggle-details-btn').on('click', function(e) {
        e.preventDefault();
        var row = $(this).closest('.gpp-log-row-summary');
        var log_id = row.data('id');
        var details_row = $('#log-details-' + log_id);
        
        details_row.fadeToggle(150);
        
        var text = $(this).text();
        if (text.includes('Ver')) {
            $(this).text('[Ocultar Payloads]');
        } else {
            $(this).text('[Ver Payloads]');
        }
    });

    /* ==========================================================================
       4. AJAX: Test Credentials Connection
       ========================================================================== */
    $('.gpp-test-cred-btn').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var portal = btn.data('portal');
        var result_span = $('#' + portal + '-test-result');
        
        btn.prop('disabled', true).addClass('updating-message');
        result_span.removeClass('success error').text('Comprobando conexión...').css('color', '#64748b');
        
        $.ajax({
            url: gppParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gpp_test_credentials',
                portal: portal,
                nonce: gppParams.nonce
            },
            success: function(response) {
                btn.prop('disabled', false).removeClass('updating-message');
                if (response.success) {
                    result_span.addClass('success').text(response.data.message).css('color', '#10b981');
                } else {
                    result_span.addClass('error').text(response.data.message).css('color', '#ef4444');
                }
            },
            error: function() {
                btn.prop('disabled', false).removeClass('updating-message');
                result_span.addClass('error').text('Ocurrió un error en la conexión local.').css('color', '#ef4444');
            }
        });
    });

    /* ==========================================================================
       5. AJAX: Clear Sync Logs
       ========================================================================== */
    $('#gpp-clear-logs-btn').on('click', function(e) {
        e.preventDefault();
        if (!confirm('¿Estás seguro de que quieres vaciar todo el historial de sincronización?')) {
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true);
        
        $.ajax({
            url: gppParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gpp_clear_logs',
                nonce: gppParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                alert('Ocurrió un error del sistema al limpiar los logs.');
                btn.prop('disabled', false);
            }
        });
    });
});
