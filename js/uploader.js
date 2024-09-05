jQuery(document).ready(function ($) {
    var imageFile;
    var logoFile;
    let btn = $('#next-step-btn');
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

        uploadLogoFile();
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

        uploadLogoFile();
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

    function uploadLogoFile() {

        var formData = new FormData();
        formData.append('image_files[]', logoFile);
        formData.append('action', 'handle_logo_upload_ajax');
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
                let themes = [];
                themes = JSON.parse(response);
                themes.forEach((theme, idx) => {
                    $('#bg-sel-container').append('<div id="bg-choice-theme' + idx + '" class="bg-choice-theme ' + (idx == 0 ? 'active' : '') + '" style="background: rgb(' + Math.round(theme.color.r) + ',' + Math.round(theme.color.g) + ',' + Math.round(theme.color.b) + ');" onclick="selectTheme(' + idx + ')">Theme ' + (idx + 1) + '</div>');
                });
                themes.forEach((theme, idx) => {
                    $('#bg-sel-container').append('<div id="bg-choice-img-container' + idx + '" class="bg-choice-img-container ' + (idx == 0 ? 'active' : '') + '"></div>');
                    theme.imgs.forEach(img => {
                        $('#bg-choice-img-container' + idx).append('<img class="bg-choice-img" src="/wp-content/uploads/bg_temp/' + img + '" onclick="selectBg(this)">');
                    });
                });
                $('#drag-and-drop-wrapper2').css('border-color', 'transparent');

                $('#category-name-ribbon').css('background', $('#bg-choice-theme0').css('background'));
                $('#category-name-ribbon').css('border-color', 'transparent');
            },
            error: function (xhr, status, error) {
            }
        });

        $('#drag-and-drop-container').css('font-size', $('#drag-and-drop-container')[0].clientHeight * 0.8 / 50 + 'px');

        $('#drag-and-drop-container').css('width', '80%');
        $('#bg-sel-container').css('width', '20%');
    }

    $('#brand-promise-input').on('change', function (e) {
        updateNextBtnStatus();
    });

    $('#next-step-btn').on('click', async function (e) {
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
                let chromes = [];
                chromes = JSON.parse(response);
                chromes.forEach(chrome => {
                    $('#drag-and-drop-container').append('<div style="background: rgb(' + Math.round(chrome.r) + ',' + Math.round(chrome.g) + ',' + Math.round(chrome.b) + ');width:100%;height:30px;"></div>');
                });
                if (response == 'success')
                    location.reload();
            },
            error: function (xhr, status, error) {
            }
        });
    });
});

function selectBg(img) {
    jQuery('#image-preview-container0').css('background-image', "url('" + img.src + "')");
    jQuery('#drag-and-drop-container').css('borderColor', 'transparent');
    jQuery('#drag-and-drop-container').addClass('step1');
    jQuery('#image-preview-container1').text('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.');
    
    updateNextBtnStatus();
}

function selectTheme(idx) {
    jQuery('.bg-choice-theme').removeClass("active");
    jQuery('#bg-choice-theme' + idx).addClass("active");

    jQuery('.bg-choice-img-container').removeClass("active");
    jQuery('#bg-choice-img-container' + idx).addClass("active");

    jQuery('#category-name-ribbon').css('background', jQuery('#bg-choice-theme' + idx).css('background'));
    jQuery('#image-preview-container1').css('color', jQuery('#bg-choice-theme' + idx).css('background-color'));
}

function updateNextBtnStatus() {
    jQuery('#next-step-btn').prop('disabled', !jQuery('#drag-and-drop-container').hasClass('step1') || jQuery('#brand-promise-input').val() == '');
}