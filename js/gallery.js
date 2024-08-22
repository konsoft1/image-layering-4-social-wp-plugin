jQuery(document).ready(function($) {
    $('.post-delete').on('click', function() {
        if (!confirm('Are you sure you want to delete this post?')) {
            return;
        }

        var button = $(this);
        var postId = button.data('post-id');
        var nonce = button.data('nonce');

        $.ajax({
            type: 'post',
            url: custom_ajax_object.ajax_url,
            data: {
                action: 'delete_post',
                post_id: postId,
                _wpnonce: nonce
            },
            success: function(response) {
                if (response == 'success') {
                    location.reload();
                } else {
                    alert('Post could not be deleted.');
                }
            }
        });
    });
});