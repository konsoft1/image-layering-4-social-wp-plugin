jQuery(document).ready(function ($) {
    var imageFile;
    var logoFile;
    let btn = $('#upload-btn');
    btn.prop('disabled', true);

    // Handle drag and drop
    $('.drag-and-drop-area').on('dragover', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragging');
    });

    $('.drag-and-drop-area').on('dragleave', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragging');
    });

    $('#drag-and-drop-area1').on('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragging');

        var files = e.originalEvent.dataTransfer.files;

        imageFile = files[0];
        previewImage(imageFile, 1);

        btn.prop('disabled', !(logoFile && logoFile));
    });

    $('#drag-and-drop-area2').on('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragging');

        var files = e.originalEvent.dataTransfer.files;

        logoFile = files[0];
        previewImage(logoFile, 2);

        btn.prop('disabled', !(logoFile && logoFile));
    });

    // Handle file selection
    $('.drag-and-drop-area').on('click', function (e) {
        $(this).parent().find('.image_file').trigger('click');
        document.querySelector('.success-message, .error-message')?.remove();
    });

    $('.image_file').on('click', function (e) {
        e.stopPropagation();  // Prevent the click event from bubbling up to .drag-and-drop-area
    });

    $('#image_file1').on('change', function () {
        var files = this.files;
        imageFile = files[0];
        previewImage(imageFile, 1);

        btn.prop('disabled', !(logoFile && logoFile));
    });

    $('#image_file2').on('change', function () {
        var files = this.files;
        logoFile = files[0];
        previewImage(logoFile, 2);

        btn.prop('disabled', !(logoFile && logoFile));
    });

    // Preview selected images
    function previewImage(file, idx) {
        var reader = new FileReader();
        if (file) {
            reader.onload = function (e) {
                var imgHtml = `<img src="${e.target.result}" alt="${file.name}">`;
                $('#image-preview-container' + idx).html(imgHtml);
            }
            reader.readAsDataURL(file);
            $('#drag-and-drop-area' + idx + ' .explain').hide();
        } else {
            $('#image-preview-container' + idx).html('');
            $('#drag-and-drop-area' + idx + ' .explain').show();
        }
    }

    $('#upload-btn').on('click', async function (e) {
        e.preventDefault();
        var $button = $(this);
        $button.prop('disabled', true);

        var formData = new FormData();
        formData.append('image_files[]', imageFile);
        formData.append('image_files[]', logoFile);
        formData.append('action', 'handle_image_upload_ajax');
        formData.append('nonce', custom_ajax_object.nonce);

        /* const response = await fetch(custom_ajax_object.ajax_url, {
            method: 'POST',
            body: formData
        }); */
        $.ajax({
            url: custom_ajax_object.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response == 'success')
                    location.reload();
            },
            error: function (xhr, status, error) {
            }
        });
    });
});