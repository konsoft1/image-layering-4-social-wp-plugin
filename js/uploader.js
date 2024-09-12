var step = 0;

jQuery(document).ready(function ($) {
    var packFiles = [];
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

        let files = e.originalEvent.dataTransfer.files;
        onLoadFiles(files);
    });

    $('#drag-and-drop-area2').on('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragging');

        var files = e.originalEvent.dataTransfer.files;

        logoFile = files[0];
        previewImage(logoFile, 2);

        btn.prop('disabled', !logoFile);

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
        let files = this.files;
        onLoadFiles(files);
    });

    $('#image_file2').on('change', function () {
        let files = this.files;
        logoFile = files[0];
        previewImage(logoFile, 2);

        btn.prop('disabled', !(logoFile && logoFile));

        uploadLogoFile();
    });

    function onLoadFiles(files) {

        let id = $('.pack-choice-title.active').prop('id');
        id = id.substring(id.length - 1) * 1;
        let idx = packs.indexOf(id);

        let imageFile = null;
        for (let i = 0; i < files.length; i++) {
            if (files[i].name.substring(files[i].name.length - 4) == '.jpg') {
                imageFile = files[i];
                break;
            }
        }
        if (imageFile != null)
            previewImage(imageFile, 1);

        btn.prop('disabled', false);

        for (let i = 0; i < files.length; i++) {
            let file = files[i];
            packFiles[idx].push(file);
            if (file.name.substring(file.name.length - 4) == '.jpg') {
                let reader = new FileReader();
                if (file) {
                    reader.onload = function (e) {
                        $('#pack-file-container' + id).append(`<img class="pack-file-img" src="${e.target.result}" onclick="selectImg(this)">`);
                    }
                    reader.readAsDataURL(file);
                }
            }
            else if (file.name.substring(file.name.length - 4) == '.csv')
                $('#pack-file-container' + id).append('<img class="pack-file-csv" src="/wp-content/plugins/image-layering/images/csv.svg" onclick="selectCsv(this)">');
        }
    }

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
                response = JSON.parse(response);
                themes = response.data;

                var imgHtml = `<img src="${response.logo}">`;
                $('#image-preview-container2').html(imgHtml);

                $('#navigator-container').html('');
                themes.forEach((theme, idx) => {
                    $('#navigator-container').append('<div id="bg-choice-theme' + idx + '" class="bg-choice-theme ' + (idx == 0 ? 'active' : '') + '" style="background: rgb(' + Math.round(theme.color.r) + ',' + Math.round(theme.color.g) + ',' + Math.round(theme.color.b) + ');" onclick="selectTheme(' + idx + ')">Theme ' + (idx + 1) + '</div>');
                    $('#navigator-container').append('<div id="bg-choice-img-container' + idx + '" class="bg-choice-img-container ' + (idx == 0 ? 'active' : '') + '"></div>');
                    theme.imgs.forEach(img => {
                        $('#bg-choice-img-container' + idx).append('<img class="bg-choice-img" src="/wp-content/uploads/bg_temp/' + img + '" onclick="selectBg(this)">');
                    });
                });

                $('#drag-and-drop-wrapper2').css('border-color', 'transparent');
                $('#category-name-ribbon').css('background', $('#bg-choice-theme0').css('background'));
                $('#category-name-ribbon').css('border-color', 'transparent');

                $('#drag-and-drop-container').css('font-size', $('#drag-and-drop-container')[0].clientHeight / 50 + 'px');

                updateNextBtnStatus();
            },
            error: function (xhr, status, error) {
            }
        });

        $('#drag-and-drop-container').css('width', '80%');
        $('#navigator-container').css('width', '20%');
    }

    $('#brand-promise-input').on('change', function (e) {
        updateNextBtnStatus();
    });

    $('#next-step-btn').on('click', async function (e) {
        e.preventDefault();
        if (step == 0)
            return;
        else if (step == 1) {
            toSecondStep();
            return;
        }
        else if (step == 2) {
            publish(this);
            return;
        }
    });

    var packs = [];

    function toSecondStep() {
        step = 2;

        $('#drag-and-drop-container').removeClass('step1');
        $('#drag-and-drop-container').addClass('step2');

        $('#next-step-btn').prop('disabled', true);
        $('#next-step-btn').text('Publish >');

        $('#navigator-container').html('');
        $('#navigator-container').append('<div id="pack-choice-title0" class="pack-choice-title active" onclick="selectPack(0)">New Pack</div>');
        $('#navigator-container').append('<div id="pack-file-container0" class="pack-file-container active"></div>');

        $('#category-name-ribbon').val('New Pack');
        $('#image-preview-container1').text('Drag & Drop or Click here to add JPEG images or CSV files consisting some sentences.');
        packs.push(0);
        packFiles.push([]);

        $('#category-name-ribbon').on('change', function () {
            $('.pack-choice-title.active').text($('#category-name-ribbon').val());
        });

        $('#pack-new-btn').on('click', function (e) {
            e.preventDefault();

            let idx = packs[packs.length - 1] + 1;
            $('#navigator-container').append('<div id="pack-choice-title' + idx + '" class="pack-choice-title active" onclick="selectPack(' + idx + ')">New Pack</div>');
            $('#navigator-container').append('<div id="pack-file-container' + idx + '" class="pack-file-container active"></div>');
            packs.push(idx);
            packFiles.push([]);

            selectPack(idx);
        });

        $('#pack-del-btn').on('click', function (e) {
            e.preventDefault();

            if (packs.length == 1) return;

            let id = $('.pack-choice-title.active').prop('id');
            let idx = packs.indexOf(id.substring(id.length - 1) * 1);

            $('.pack-choice-title.active').remove();
            $('.pack-file-container.active').remove();
            packs.splice(idx, 1);
            packFiles.splice(idx, 1);

            selectPack(packs[0]);
        });
    }

    function publish(btn) {
        var $button = $(btn);
        $button.prop('disabled', true);

        var formData = new FormData();
        formData.append('logo', $('#image-preview-container2 img').attr('src'));
        formData.append('theme', $('#category-name-ribbon').css('background-color'));
        formData.append('bg', $('#image-preview-container0').css('background-image'));
        formData.append('brand', $('#brand-promise-input').val());
        for (let i = 0; i < packs.length; i++) {
            if (packFiles[i].length == 0) continue;
            formData.append('packs[]', $('#pack-choice-title' + packs[i]).text());
            for (let j = 0; j < packFiles[i].length; j++)
                formData.append('pack_files' + i + '[]', packFiles[i][j]);
        }

        formData.append('action', 'handle_image_upload_ajax');
        formData.append('nonce', custom_ajax_object.nonce);

        $.ajax({
            url: custom_ajax_object.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                /* let chromes = [];
                chromes = JSON.parse(response);
                chromes.forEach(chrome => {
                    $('#drag-and-drop-container').append('<div style="background: rgb(' + Math.round(chrome.r) + ',' + Math.round(chrome.g) + ',' + Math.round(chrome.b) + ');width:100%;height:30px;"></div>');
                }); */
                if (response == 'success')
                    location.reload();
            },
            error: function (xhr, status, error) {
            }
        });
    }
});

function selectBg(img) {
    step = 1;

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

function selectPack(idx) {
    jQuery('.pack-choice-title').removeClass('active');
    jQuery('#pack-choice-title' + idx).addClass('active');

    jQuery('.pack-file-container').removeClass('active');
    jQuery('#pack-file-container' + idx).addClass('active');

    jQuery('#category-name-ribbon').val(jQuery('.pack-choice-title.active').text());
}

function selectImg(img) {
    var imgHtml = `<img src="${img.src}">`;
    jQuery('#image-preview-container1').html(imgHtml);
}

function selectCsv(file) {
    jQuery('#image-preview-container1').text('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.');
}