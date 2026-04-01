jQuery(document).ready(function($) {
    $('.wpste-translate-btn').on('click', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var postId = $btn.data('post-id');
        var targetLang = $('#wpste_target_lang').val();
        var $status = $('.wpste-translation-status');

        // Validate language selection
        if (!targetLang) {
            $status.show().html('<p style="color: red;"><strong>Error:</strong> Please select a target language.</p>');
            return;
        }

        $btn.prop('disabled', true).text('Translating...');
        $status.show().html('<p>Translation in progress...</p>');
        
        $.ajax({
            url: wpste.ajax_url,
            type: 'POST',
            data: {
                action: 'wpste_translate_post',
                nonce: wpste.nonce,
                post_id: postId,
                target_lang: targetLang
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<p style="color: green;"><strong>✓ ' + response.data.message + '</strong></p>');
                    $status.append('<p>Characters translated: ' + response.data.characters + '</p>');
                    $status.append('<p><a href="' + response.data.view_link + '" target="_blank" class="button">View translated post</a></p>');
                    $status.append('<p style="font-size: 12px; color: #666;">Tip: Use the language switcher on the frontend to see translations.</p>');
                } else {
                    $status.html('<p style="color: red;"><strong>Error:</strong> ' + response.data.message + '</p>');
                }
                $btn.prop('disabled', false).text('Translate');
            },
            error: function() {
                $status.html('<p style="color: red;"><strong>Error:</strong> Ajax request failed</p>');
                $btn.prop('disabled', false).text('Translate');
            }
        });
    });
});
