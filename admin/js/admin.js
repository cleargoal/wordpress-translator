jQuery(document).ready(function($) {
    $('.wpste-translate-btn').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var postId = $btn.data('post-id');
        var targetLang = $('#wpste_target_lang').val();
        var $status = $('.wpste-translation-status');
        
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
                    $status.html('<p style="color: green;">' + response.data.message + '</p>');
                    $status.append('<p><a href="' + response.data.edit_link + '" target="_blank">Edit translated post</a></p>');
                } else {
                    $status.html('<p style="color: red;">Error: ' + response.data.message + '</p>');
                }
                $btn.prop('disabled', false).text('Translate');
            },
            error: function() {
                $status.html('<p style="color: red;">Ajax error occurred</p>');
                $btn.prop('disabled', false).text('Translate');
            }
        });
    });
});
