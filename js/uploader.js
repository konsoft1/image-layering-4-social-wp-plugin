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

        if ($(this).parent().hasClass('disabled')) return;

        let files = e.originalEvent.dataTransfer.files;
        onLoadFiles(files);
    });

    $('#drag-and-drop-area2').on('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragging');

        if ($(this).parent().hasClass('disabled')) return;

        var files = e.originalEvent.dataTransfer.files;
        if (files[0].type != 'image/png') {
            showModal('Please choose PNG file!');
            return;
        }

        logoFile = files[0];
        previewImage(logoFile, 2);

        //btn.prop('disabled', !logoFile);

        uploadLogoFile();
    });

    // Handle file selection
    $('.drag-and-drop-area').on('click', function (e) {
        if ($(this).parent().hasClass('disabled')) return;

        $(this).parent().find('.image_file').trigger('click');
        document.querySelector('.success-message, .error-message')?.remove();
    });

    $('.image_file').on('click', function (e) {
        e.stopPropagation();  // Prevent the click event from bubbling up to .drag-and-drop-area
    });

    $('#image_file1').on('change', function () {
        let files = this.files;
        onLoadFiles(files);
        $(this).val('');
    });

    $('#image_file2').on('change', function () {
        let files = this.files;
        if (files[0].type != 'image/png') {
            showModal('Please choose PNG file!');
            return;
        }
        logoFile = files[0];
        previewImage(logoFile, 2);

        //btn.prop('disabled', !(logoFile && logoFile));

        uploadLogoFile();
    });

    function onLoadFiles(files) {

        let id = $('.pack-choice-title.active').prop('id');
        id = id.substring(id.length - 1) * 1;
        let idx = packs.indexOf(id);

        let imageFile = null;
        let csvFile = false;
        for (let i = 0; i < files.length; i++) {
            if (files[i].type == 'image/jpeg' || files[i].type == 'image/png') {
                if (imageFile === null)
                    imageFile = files[i];
            } else if (files[i].type == 'text/csv') {
                csvFile = true;
            } else {
                showModal('Please choose JPEG, PNG or CSV files!');
                return;
            }
        }
        if (imageFile != null)
            previewImage(imageFile, 1);
        else if (csvFile)
            $('#image-preview-container1').text('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.');
        else
            previewImage(imageFile, 1);

        btn.prop('disabled', false);
        $('#navigator-title').removeClass('current-step-focus');

        for (let i = 0; i < files.length; i++) {
            let file = files[i];
            packFiles[idx].push(file);
            if (file.type == 'image/jpeg' || file.type == 'image/png') {
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
            $('#drag-and-drop-wrapper1').removeClass('current-step-focus');
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
        
        $('#image-preview-container1').html('<div class="spinner"></div><div class="spinner-text">Processing...</div>');
        $('#navigator-title').text('Processing logo file...');

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
                
                $('#image-preview-container1').html('<img src="/wp-content/plugins/image-layering/images/step_inst/2_theme.png">');

                $('#navigator-container').css('overflow', 'visible');
                $('#navigator-container').html('');
                $('#navigator-container').append('<div id="navigator-title" class="current-step-focus">Select theme.</div>');
                themes.forEach((theme, idx) => {
                    $('#navigator-container').append('<div id="bg-choice-theme' + idx + '" class="bg-choice-theme ' + (idx == 0 ? 'active' : '') + '" style="background: rgb(' + Math.round(theme.color.r) + ',' + Math.round(theme.color.g) + ',' + Math.round(theme.color.b) + ');" onclick="selectTheme(' + idx + ')">Theme ' + (idx + 1) + '</div>');
                    $('#navigator-container').append('<div id="bg-choice-img-container' + idx + '" class="bg-choice-img-container"></div>');
                    theme.imgs.forEach(img => {
                        $('#bg-choice-img-container' + idx).append('<img class="bg-choice-img" src="/wp-content/uploads/bg_temp/' + img + '" onclick="selectBg(this)">');
                    });
                });

                $('#drag-and-drop-wrapper2').css('border-color', 'transparent');
                $('#category-name-ribbon').css('background', $('#bg-choice-theme0').css('background'));
                $('#category-name-ribbon').css('border-color', 'transparent');

                //$('#drag-and-drop-container').css('font-size', $('#drag-and-drop-container')[0].clientHeight / 50 + 'px');

                $('#drag-and-drop-wrapper2').removeClass('current-step-focus');
            },
            error: function (xhr, status, error) {
            }
        });

        //$('#drag-and-drop-container').css('width', '80%');
        //$('#navigator-container').css('width', '20%');
    }

    $('#brand-promise-input').on('change', function (e) {
        $('#brand-promise-input').removeClass('current-step-focus');
        if ($('#brand-promise-input').val() == '') {
            $('#next-step-btn').attr('disabled', true);
            return;
        }
        if (step != 2) {
            $('#next-step-btn').attr('disabled', false);
            $('#next-step-btn').addClass('current-step-focus');
            $('#image-preview-container1').html('<img src="/wp-content/plugins/image-layering/images/step_inst/5_next_step.png">');
            $('#navigator-title').text('Press Next Step button.');
        }
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
        $('#navigator-container').append('<div id="navigator-title">Rename pack.</div>');
        $('#navigator-container').append('<div id="pack-choice-title0" class="pack-choice-title active disabled" onclick="selectPack(0)">New Pack</div>');
        $('#navigator-container').append('<div id="pack-file-container0" class="pack-file-container active"></div>');

        $('#category-name-ribbon').attr('disabled', false);
        $('#category-name-ribbon').val('New Pack');
        $('#image-preview-container1').html('<img src="/wp-content/plugins/image-layering/images/step_inst/6_new_pack.png">');

        $('#next-step-btn').removeClass('current-step-focus');
        $('#category-name-ribbon').addClass('current-step-focus');
        $('#category-name-ribbon').on('change', function (e) {
            $('#category-name-ribbon').removeClass('current-step-focus');
            $('#navigator-title').text('Import files.');
        });

        packs.push(0);
        packFiles.push([]);

        $('#category-name-ribbon').on('change', function () {
            $('.pack-choice-title.active').text($('#category-name-ribbon').val());

            $('#category-name-ribbon').removeClass('current-step-focus');
            $('#drag-and-drop-wrapper1').addClass('current-step-focus');
            if ($('.pack-file-container.active').html() == '') {
                $('#navigator-title').text('Import files.');
                $('#image-preview-container1').html('<img src="/wp-content/plugins/image-layering/images/step_inst/7_files.png">');
            }

            $('#drag-and-drop-wrapper1').removeClass('disabled');
            $('#pack-new-btn').attr('disabled', false);
            $('.pack-choice-title').removeClass('disabled');
            //$('#image-preview-container1').text('Drag & Drop or Click here to add JPEG images or CSV files consisting some sentences.');
        });

        $('#pack-new-btn').on('click', function (e) {
            e.preventDefault();

            let idx = packs[packs.length - 1] + 1;
            $('#navigator-container').append('<div id="pack-choice-title' + idx + '" class="pack-choice-title active" onclick="selectPack(' + idx + ')">New Pack</div>');
            $('#navigator-container').append('<div id="pack-file-container' + idx + '" class="pack-file-container active"></div>');
            $('#image-preview-container1').html('<img src="/wp-content/plugins/image-layering/images/step_inst/6_new_pack.png">');
            packs.push(idx);
            packFiles.push([]);

            selectPack(idx);

            $('#image-preview-container1').text('');

            $('#category-name-ribbon').addClass('current-step-focus');
            $('#navigator-title').text('Rename pack.');
            $('#drag-and-drop-wrapper1').removeClass('current-step-focus');

            $('#drag-and-drop-wrapper1').addClass('disabled');
            $('#pack-new-btn').attr('disabled', true);
            $('#next-step-btn').prop('disabled', true);
            $('.pack-choice-title').addClass('disabled');
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
            
            $('#drag-and-drop-wrapper1').removeClass('disabled');
            $('#pack-new-btn').attr('disabled', false);
            $('#next-step-btn').prop('disabled', false);
            $('.pack-choice-title').removeClass('disabled');
        });
    }

    function publish(btn) {

        for (let i = 0; i < packs.length; i++) {
            if (packFiles[i].length == 0) {
                showModal("There's an empty pack!");
                return;
            }
        }

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
        
        $('#image-preview-container1').html('<div class="spinner"></div><div class="spinner-text">Publishing...</div>');
        //$('#navigator-container').html('<div class="spinner"></div><div class="spinner-text">Publishing...</div>');

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
                if (response == 'success') {
                    $('#image-preview-container1').html('<div class="spinner-success">✔</div><div class="spinner-text">Success!</div>');
                    //$('#navigator-container').html('<div class="spinner-success">✔</div><div class="spinner-text">Success!</div>');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            },
            error: function (xhr, status, error) {
            }
        });
    }

    function showModal(msg) {
        $(document.body).append('<div id="modal" class="modal"></div>');
        $('#modal').text(msg);
        $('#modal').on('click', function(e) {
            removeModal();
        });
        setTimeout(removeModal, 4000);
    }

    function removeModal() {
        $('#modal').fadeOut(300, function() {
            $(this).remove();
        });
    }
});

function selectBg(img) {
    step = 1;

    jQuery('#image-preview-container0').css('background-image', "url('" + img.src + "')");
    jQuery('#drag-and-drop-container').css('borderColor', 'transparent');
    jQuery('#drag-and-drop-container').addClass('step1');
    jQuery('#drag-and-drop-wrapper2').addClass('disabled');
    //jQuery('#image-preview-container1').text('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.');

    jQuery('#navigator-title').removeClass('current-step-focus');
    jQuery('#brand-promise-input').addClass('current-step-focus');
    
    jQuery('#image-preview-container1').html('<img src="/wp-content/plugins/image-layering/images/step_inst/4_brand_promise.png">');
    jQuery('#navigator-title').text('Type in Brand Promise.');
}

function selectTheme(idx) {
    jQuery('.bg-choice-theme').removeClass("active");
    jQuery('#bg-choice-theme' + idx).addClass("active");

    jQuery('.bg-choice-img-container').removeClass("active");
    jQuery('#bg-choice-img-container' + idx).addClass("active");

    jQuery('#category-name-ribbon').css('background', jQuery('#bg-choice-theme' + idx).css('background'));
    jQuery('#image-preview-container1').css('color', jQuery('#bg-choice-theme' + idx).css('background-color'));

    jQuery('#navigator-title').text('Select background.');
    jQuery('#image-preview-container1').html('<img src="/wp-content/plugins/image-layering/images/step_inst/3_background.png">');
}

function selectPack(idx) {
    if (jQuery('.pack-choice-title').hasClass('disabled')) return;

    jQuery('.pack-choice-title').removeClass('active');
    jQuery('#pack-choice-title' + idx).addClass('active');

    jQuery('.pack-file-container').removeClass('active');
    jQuery('#pack-file-container' + idx).addClass('active');

    jQuery('#category-name-ribbon').val(jQuery('.pack-choice-title.active').text());
    jQuery('#category-name-ribbon').removeClass('current-step-focus');
    if (jQuery('.pack-file-container.active').html() == '') {
        jQuery('#navigator-title').text('Import files.');
        jQuery('#drag-and-drop-wrapper1').addClass('current-step-focus');
        jQuery('#image-preview-container1').html('<img src="/wp-content/plugins/image-layering/images/step_inst/7_files.png">');
    }

    //jQuery('#image-preview-container1').text('Drag & Drop or Click here to add JPEG images or CSV files consisting some sentences.');
}

function selectImg(img) {
    var imgHtml = `<img src="${img.src}">`;
    jQuery('#image-preview-container1').html(imgHtml);
}

function selectCsv(file) {
    jQuery('#image-preview-container1').text('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.');
}