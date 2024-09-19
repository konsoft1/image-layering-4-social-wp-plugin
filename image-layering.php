<?php
/*
Plugin Name: Image Layering
Description: Allows users to upload images and synthesizing them from the frontend.
Version: 2.0
Author: yuari@konsoft
*/

function image_layering_form_shortcode()
{
    ob_start();
?>
    <form id="user-post-form" action="" method="post" enctype="multipart/form-data">
        <div id="image-edit-container">
            <div id="drag-and-drop-container">
                <div id="image-preview-container0" class="image-preview-container"></div>
                <div id="drag-and-drop-wrapper1" class="drag-and-drop-wrapper disabled">
                    <div id="drag-and-drop-area1" class="drag-and-drop-area">
                        <!-- <span class="explain"><span><b>Background</b>(.jpg)<br>(1000x1000)</span><br>Drag &amp; Drop<br>or<br>Click</span> -->
                        <div id="image-preview-container1" class="image-preview-container">
                            <img src="/wp-content/plugins/image-layering/images/step_inst/1_logo.png">
                        </div>
                    </div>
                    <input type="file" id="image_file1" class="image_file" name="image_file" style="display: none;" accept="image/jpeg, image/png, text/csv" multiple>
                </div>
                <div id="drag-and-drop-wrapper2" class="drag-and-drop-wrapper current-step-focus">
                    <div id="drag-and-drop-area2" class="drag-and-drop-area">
                        <span class="explain"><span><b>Logo</b>(.png)<br>(200x200)</span><br>Drag &amp; Drop<br>or<br>Click</span>
                        <div id="image-preview-container2" class="image-preview-container"></div>
                    </div>
                    <input type="file" id="image_file2" class="image_file" name="logo_file" style="display: none;" accept="image/png">
                </div>
                <input id="brand-promise-input" type="text" class="" placeholder="Input Brand Promise">
                <input id="category-name-ribbon" type="text" value="Package Name" disabled>
                <button id="pack-new-btn" class="pack-ctrl" disabled>+ New</button>
                <button id="pack-del-btn" class="pack-ctrl">&times; Delete</button>
                <button id="next-step-btn">Next Step ></button>
            </div>
            <div id="navigator-container">
                <div id="navigator-title">Select LOGO PNG file.</div>
                <!-- <div class="spinner"></div>
                <div class="spinner-text">Processing...</div> -->
            </div>
        </div>

        <?php wp_nonce_field('user_post_action', 'user_post_nonce'); ?>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('image_layering_form', 'image_layering_form_shortcode');

// Enqueue scripts and styles
function enqueue_uploader_scripts()
{
    wp_enqueue_script('jquery');
    wp_enqueue_style('image-uploader-stylesheet', plugin_dir_url(__FILE__) . 'css/uploader.css', false, '1.0', 'all');
    wp_enqueue_script('image-uploader-script', plugin_dir_url(__FILE__) . 'js/uploader.js', array('jquery'), null, true);
    wp_localize_script('image-uploader-script', 'custom_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('custom-ajax-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_uploader_scripts');

// handle logo upload
function handle_logo_upload_ajax()
{
    // Verify nonce for security
    if (!check_ajax_referer('custom-ajax-nonce', 'nonce'))
        die;

    // Include necessary files for media handling
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    // Handle file uploads
    if (!empty($_FILES["image_files"]['name'][0])) {
        //$file = $_FILES["image_files"][0];
        $file = [
            'name' => $_FILES["image_files"]['name'][0],
            'type' => $_FILES["image_files"]['type'][0],
            'tmp_name' => $_FILES["image_files"]['tmp_name'][0],
            'error' => $_FILES["image_files"]['error'][0],
            'size' => $_FILES["image_files"]['size'][0],
        ];

        // Check for upload errors
        if ($file['error'] !== 0) {
            $upload_errors[] = "Error uploading file." . $file['error'];
            echo "Error uploading file." . $file['error'] . '<br>';
            die;
        }

        $upload = wp_handle_upload($file, array('test_form' => false));
        if (isset($upload['error'])) {
            $upload_errors[] = "Error uploading file: " . $upload['error'];
        } else {
            $pngLogo = resize_image_to_fit($upload['file'], 200, 200, 0);
            //modulate($jpgImage, 1.15, 2.37, 142);
            $dominantColors = extractDominateColors($pngLogo);
            imagedestroy($pngLogo);

            $bg_hsls = [
                [183, 48, 83],
                [169, 15, 68],
                [201, 10, 53],
                [195, 14, 50],
                [200, 20, 46],
                [133, 7, 72],
                [167, 10, 54],
                [179, 16, 47],
                [135, 13, 52],
                [138, 16, 73]
            ];

            $temppath = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'bg_temp';
            wp_mkdir_p($temppath);
            $ret = [];
            foreach ($dominantColors as $idx => $dominantColor) {
                list($h, $s, $l) = rgbToHsl($dominantColor['r'], $dominantColor['g'], $dominantColor['b']);
                $bg_imgs = [];
                for ($i = 0; $i < 10; $i++) {
                    $orgpath = plugin_dir_path(__FILE__) . 'images' . DIRECTORY_SEPARATOR . 'bg_templates' . DIRECTORY_SEPARATOR . 'bg (' . ($i + 1) . ').jpg';
                    $image = new Imagick($orgpath);
                    $image->modulateImage( /* ($l * 10000 / $bg_hsls[$i][2] - 100) / 10 +  */100, ($s * 10000 / $bg_hsls[$i][1] - 100) / (9 * $s * $s + 1) + 100, 100 + ($h - $bg_hsls[$i][0]) * 5 / 9);

                    /* $image->transformImageColorspace(Imagick::COLORSPACE_HSL);
                    //$hueChannel->evaluateImage(Imagick::EVALUATE_MULTIPLY, 0.3);
                    //$hueChannel->evaluateImage(Imagick::EVALUATE_ADD, $h / 360 - 0.15);
                    //$saturationChannel->evaluateImage(Imagick::EVALUATE_MULTIPLY, ($s * 100 / $bg_hsls[$i][1] - 1) / 2 + 1);
                    //$valueChannel->evaluateImage(Imagick::EVALUATE_MULTIPLY, ($l * 100 / $bg_hsls[$i][2] - 1) / 2 + 1);


                    $hue = $image->clone();
                    $saturation = $image->clone();
                    $lightness = $image->clone();
                    $hue->separateImageChannel(Imagick::CHANNEL_RED);
                    $saturation->separateImageChannel(Imagick::CHANNEL_GREEN);
                    $lightness->separateImageChannel(Imagick::CHANNEL_BLUE);
                    $hue->fxImage("(u*0.3)+0.9");
                    $saturation->fxImage("u*2.0");
                    $combined = new Imagick();
                    $combined->addImage($hue);
                    $combined->addImage($saturation);
                    $combined->addImage($lightness);
                    $combined = $combined->combineImages(Imagick::CHANNEL_ALL);
                    $combined->clear();
                    $combined->destroy();
                    //$image->separateImageChannel(Imagick::CHANNEL_RED);

                    // Apply the `fxImage` method to modify the red channel
                    // The equivalent formula to "u+0.1" is applied here
                    //$combined = $image->fxImage('u+0.5');

                    // Recombine the channels to get the full-color image
                    $hue->setImageColorspace(Imagick::COLORSPACE_SRGB);
                    $saturation->setImageColorspace(Imagick::COLORSPACE_SRGB);
                    $lightness->setImageColorspace(Imagick::COLORSPACE_SRGB);
                    $combined = new Imagick();
                    $combined->newImage($image->getImageWidth(), $image->getImageHeight(), new ImagickPixel('gray'));
                    $combined->setImageColorspace(Imagick::COLORSPACE_SRGB);
                    //$combined->compositeImage($lightness, Imagick::COMPOSITE_LIGHTEN, 0, 0);
                    $combined->compositeImage($hue, Imagick::COMPOSITE_HUE, 0, 0);
                    //$combined->compositeImage($saturation, Imagick::COMPOSITE_SATURATE, 0, 0);*/


                    $newfilename = 'bg-theme' . ($idx + 1) . '-' . ($i + 1) . '-rgb(' . round($dominantColor['r']) . ',' . round($dominantColor['g']) . ',' . round($dominantColor['b']) . ').jpg';
                    $newpath = $temppath . DIRECTORY_SEPARATOR . $newfilename;
                    /* $image->setImageFormat('jpg');  */
                    $image->writeImage($newpath);

                    /* $hue->clear();
                    $hue->destroy();
                    $saturation->clear();
                    $saturation->destroy();
                    $lightness->clear();
                    $lightness->destroy(); */
                    $image->clear();
                    $image->destroy();
                    /* $combined->clear();
                    $combined->destroy(); */
                    array_push($bg_imgs, $newfilename);
                }

                $l = $l < 0.5 ? $l : 0.5;
                list($newR, $newG, $newB) = hslToRgb($h, $s, $l);
                $dominantColor['r'] = $newR;
                $dominantColor['g'] = $newG;
                $dominantColor['b'] = $newB;

                array_push($ret, ['color' => $dominantColor, 'imgs' => $bg_imgs]);
            }

            echo json_encode([
                'status' => 'success',
                'logo' => wp_make_link_relative($upload['url']),
                'data' => $ret
            ]);

            die;
        }
    } else {
        echo 'Image does not exist!';
        die;
    }
}
add_action('wp_ajax_handle_logo_upload_ajax', 'handle_logo_upload_ajax');
add_action('wp_ajax_nopriv_handle_logo_upload_ajax', 'handle_logo_upload_ajax');

// handle post
function handle_image_upload_ajax()
{
    // Verify nonce for security
    /* if (!isset($_POST['user_post_nonce']) || !wp_verify_nonce($_POST['user_post_nonce'], 'user_post_action')) {
        return;
    } */
    if (!check_ajax_referer('custom-ajax-nonce', 'nonce'))
        die;

    // Include necessary files for media handling
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    if (!function_exists('media_handle_sideload')) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
    }

    if (!function_exists('wp_generate_attachment_metadata')) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    $logo_filepath = isset($_POST['logo']) ? ABSPATH . $_POST['logo'] : '';
    $theme_color = isset($_POST['theme']) ? explode(',', substr($_POST['theme'], 4, -1)) : [0, 0, 0];
    $bg_filepath = isset($_POST['bg']) ? ABSPATH . wp_make_link_relative(substr($_POST['bg'], 6, -3)) : '';
    $brand = isset($_POST['brand']) ? $_POST['brand'] : '';
    $packs = isset($_POST['packs']) ? $_POST['packs'] : [];

    foreach ($packs as $idx => $packname) {
        //$rand_texts = file(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'phrase-text.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        //$rand_text = $rand_texts[rand(0, count($rand_texts) - 1)];

        $uploads = [];
        $upload_errors = [];

        // Handle file uploads
        for ($i = 0; $i < count($_FILES["pack_files" . $idx]['name']); $i++) {
            if (!empty($_FILES["pack_files" . $idx]['name'][$i])) {
                //$file = $_FILES["pack_files" . $idx][$i];
                $file = [
                    'name' => $_FILES["pack_files" . $idx]['name'][$i],
                    'type' => $_FILES["pack_files" . $idx]['type'][$i],
                    'tmp_name' => $_FILES["pack_files" . $idx]['tmp_name'][$i],
                    'error' => $_FILES["pack_files" . $idx]['error'][$i],
                    'size' => $_FILES["pack_files" . $idx]['size'][$i],
                ];

                // Check for upload errors
                if ($file['error'] !== 0) {
                    $upload_errors[] = "Error uploading file $i." . $file['error'];
                    continue;
                }

                $upload = wp_handle_upload($file, array('test_form' => false));
                if (isset($upload['error'])) {
                    $upload_errors[] = "Error uploading file $i: " . $upload['error'];
                } else {
                    $uploads[] = $upload;
                }
            } else {
                echo 'Image does not exist!';
                die;
            }
        }

        if (!empty($upload_errors)) {
            foreach ($upload_errors as $error) {
                echo $error . '<br>';
            }
            die;
        }

        // Analize logo back brightness
        $backL = extractMeanBrightness($bg_filepath, 85, 40, 160, 160);
        $logoL = extractMeanBrightness($logo_filepath);
        if ($logoL > $backL && $logoL - $backL < 0.15) {
            if ($logoL > 0.5) $lAdj1 = 'burnx1';
            else $lAdj1 = 'dodgex2';
        } else if ($logoL < $backL && $backL - $logoL < 0.15) {
            if ($backL > 0.5) $lAdj1 = 'burnx2';
            else $lAdj1 = 'dodgex1';
        } else {
            $lAdj1 = '';
        }

        $backL = extractMeanBrightness($bg_filepath, 135, 20, 250, 250);
        if ($logoL > $backL && $logoL - $backL < 0.15) {
            if ($logoL > 0.5) $lAdj2 = 'burnx1';
            else $lAdj2 = 'dodgex2';
        } else if ($logoL < $backL && $backL - $logoL < 0.15) {
            if ($backL > 0.5) $lAdj2 = 'burnx2';
            else $lAdj2 = 'dodgex1';
        } else {
            $lAdj2 = '';
        }

        foreach ($uploads as $upload) {
            if ($upload['type'] == 'image/jpeg' || $upload['type'] == 'image/png') {
                // Insert post into the database
                $post_id = wp_insert_post(array(
                    'post_title'   => basename($logo_filepath, '.png') . ' + ' . basename($upload['file']),
                    'post_content' => basename($logo_filepath, '.png') . ' + ' . $brand . ' + ' . $packname . ' + ' . basename($upload['file']),
                    'post_status'  => 'publish',
                    'post_author'  => get_current_user_id(),
                    'post_type'    => 'post', // Change to your custom post type if needed
                    'post_category' => array(get_cat_ID('Image Layering'))
                ));

                if ($post_id) {

                    // Compose image
                    $newfilepath = composeImage($bg_filepath, $logo_filepath, $lAdj1, $brand, $theme_color, $packname, $upload['file']);

                    wp_set_post_terms($post_id, $packname, 'post_tag', true);
                    add_post_meta($post_id, 'brand_promise',  $brand, true);

                    // Attach uploaded images to the post
                    /* $attachment_id = media_handle_sideload(array(
                        'name' => basename($upload['file']),
                        'tmp_name' => $upload['file']
                    ), $post_id);

                    if (is_wp_error($attachment_id)) {
                        echo "Error attaching image: " . $attachment_id->get_error_message();
                    } */
                    wp_delete_file($upload['file']);

                    $attachment_id = media_handle_sideload(array(
                        'name' => basename($newfilepath),
                        'tmp_name' => $newfilepath
                    ), $post_id);

                    if (is_wp_error($attachment_id)) {
                        echo "Error attaching image: " . $attachment_id->get_error_message();
                    } else {
                        // Optionally set the first image as the post thumbnail
                        if (!has_post_thumbnail($post_id)) {
                            set_post_thumbnail($post_id, $attachment_id);
                        }
                    }

                    //echo "success";
                } else {
                    //echo "Error creating post.";
                }
            } else if ($upload['type'] == 'text/csv') {
                $texts = file($upload['file'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                foreach ($texts as $i => $text) {

                    // Insert post into the database
                    $post_id = wp_insert_post(array(
                        'post_title'   => basename($logo_filepath, '.png') . ' + ' . basename($upload['file']) . '(' . $i . ')',
                        'post_content' => $text,
                        'post_status'  => 'publish',
                        'post_author'  => get_current_user_id(),
                        'post_type'    => 'post', // Change to your custom post type if needed
                        'post_category' => array(get_cat_ID('Image Layering'))
                    ));

                    if ($post_id) {

                        // Compose image
                        $newfilepath = composeImage($bg_filepath, $logo_filepath, $lAdj2, $brand, $theme_color, $packname, '', $text);

                        wp_set_post_terms($post_id, $packname, 'post_tag', true);
                        add_post_meta($post_id, 'brand_promise',  $brand, true);

                        // Attach uploaded images to the post
                        /* $attachment_id = media_handle_sideload(array(
                            'name' => basename($upload['file']),
                            'tmp_name' => $upload['file']
                        ), $post_id);

                        if (is_wp_error($attachment_id)) {
                            echo "Error attaching image: " . $attachment_id->get_error_message();
                        } */

                        $attachment_id = media_handle_sideload(array(
                            'name' => basename($newfilepath),
                            'tmp_name' => $newfilepath
                        ), $post_id);

                        if (is_wp_error($attachment_id)) {
                            echo "Error attaching image: " . $attachment_id->get_error_message();
                        } else {
                            // Optionally set the first image as the post thumbnail
                            if (!has_post_thumbnail($post_id)) {
                                set_post_thumbnail($post_id, $attachment_id);
                            }
                        }

                        //echo "success";
                    } else {
                        //echo "Error creating post.";
                    }
                }

                wp_delete_file($upload['file']);
            }
        }
    }
    echo 'success';
    die;
}
/* function handle_image_upload_ajax()
{
    if (!check_ajax_referer('custom-ajax-nonce', 'nonce'))
        die;

    // Include necessary files for media handling
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    if (!function_exists('media_handle_sideload')) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
    }

    if (!function_exists('wp_generate_attachment_metadata')) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    // Sanitize and validate form inputs
    //$rand_text = sanitize_textarea_field($_POST['rand_text']);
    $rand_texts = file(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'phrase-text.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    //$rand_text = "Enjoy your life";
    $rand_text = $rand_texts[rand(0, count($rand_texts) - 1)];

    $uploads = [];
    $upload_errors = [];

    // Handle file uploads
    for ($i = 0; $i < 2; $i++) {
        if (!empty($_FILES["image_files"]['name'][$i])) {
            //$file = $_FILES["image_files"][$i];
            $file = [
                'name' => $_FILES["image_files"]['name'][$i],
                'type' => $_FILES["image_files"]['type'][$i],
                'tmp_name' => $_FILES["image_files"]['tmp_name'][$i],
                'error' => $_FILES["image_files"]['error'][$i],
                'size' => $_FILES["image_files"]['size'][$i],
            ];

            // Check for upload errors
            if ($file['error'] !== 0) {
                $upload_errors[] = "Error uploading file $i." . $file['error'];
                continue;
            }

            $upload = wp_handle_upload($file, array('test_form' => false));
            if (isset($upload['error'])) {
                $upload_errors[] = "Error uploading file $i: " . $upload['error'];
            } else {
                $uploads[] = $upload;
            }
        } else {
            echo 'Image does not exist!';
            die;
        }
    }

    if (!empty($upload_errors)) {
        foreach ($upload_errors as $error) {
            echo $error . '<br>';
        }
        die;
    }

    // Insert post into the database
    $post_id = wp_insert_post(array(
        'post_title'   => basename($uploads[0]['file']) . ' + ' . basename($uploads[1]['file']),
        'post_content' => $rand_text,
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id(),
        'post_type'    => 'post', // Change to your custom post type if needed
        'post_category' => array(get_cat_ID('Image Layering'))
    ));

    if ($post_id) {
        // Create new image
        //$jpgImage = imagecreatefromjpeg($uploads[0]['file']);
        //$pngLogo = imagecreatefrompng($uploads[1]['file']);
        $jpgImage = resize_image_to_fit($uploads[0]['file'], 1000, 1000, 1);
        $pngLogo = resize_image_to_fit($uploads[1]['file'], 200, 200, 0);
        // TEST start
        modulate($jpgImage, 1.15, 2.37, 142);
        $dominantColors = extractDominateColors($pngLogo);
        echo json_encode($dominantColors);
        // TEST end
        $jpgWidth = imagesx($jpgImage);
        $jpgHeight = imagesy($jpgImage);
        $logoWidth = imagesx($pngLogo);
        $logoHeight = imagesy($pngLogo);
        $logoX = 0;
        $logoY = 0;
        $fontSize = $jpgWidth / 30; // Font size in points
        $fontFile = plugin_dir_path(__FILE__) . 'font/OpenSans-Regular.ttf'; // Path to a TrueType font file
        $marginX = $jpgWidth * 3 / 40;
        $marginBottom = $fontSize * 2;
        $lineGap = $fontSize * 2 / 3;

        // Merge the PNG logo onto the JPG image
        imagecopy($jpgImage, $pngLogo, $logoX, $logoY, 0, 0, $logoWidth, $logoHeight);

        // Calculate word wrap
        $lines = [];
        $words = explode(' ', $rand_text);
        $line = '';
        foreach ($words as $word) {
            $testLine = $line . ' ' . $word;
            $box = imagettfbbox($fontSize, 0, $fontFile, $testLine);
            if ($box[2] > $jpgWidth - $marginX * 2) {
                $lines[] = trim($line);
                $line = $word;
            } else {
                $line = $testLine;
            }
        }
        $lines[] = trim($line);

        // Add the text to the image
        $textColor = imagecolorallocate($jpgImage, 255, 255, 255); // Calculate text bounding box dimensions
        for ($i = 0; $i < count($lines); $i++) {
            $idx = count($lines) - $i - 1;
            $line = $lines[$i];
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $line);
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];
            $textX = ($jpgWidth - $textWidth) / 2;
            $textY = $jpgHeight - $marginBottom - $textHeight * $idx - $lineGap * $idx; //($jpgHeight - $textHeight) / 2 + $textHeight;
            imagettftext($jpgImage, $fontSize, 0, (int) $textX, (int) $textY, $textColor, $fontFile, $line);
        }
        // Save the final image as a new file
        $newfilename = basename($uploads[0]['file']) . '.new.jpg';
        $newfilepath = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $newfilename;
        imagejpeg($jpgImage, $newfilepath, 100); // 100 is the quality percentage

        // Free up memory
        imagedestroy($jpgImage);
        imagedestroy($pngLogo);

        // Attach uploaded images to the post
        foreach ($uploads as $upload) {
            $attachment_id = media_handle_sideload(array(
                'name' => basename($upload['file']),
                'tmp_name' => $upload['file']
            ), $post_id);

            if (is_wp_error($attachment_id)) {
                echo "Error attaching image: " . $attachment_id->get_error_message();
            }
        }

        $attachment_id = media_handle_sideload(array(
            'name' => $newfilename,
            'tmp_name' => $newfilepath
        ), $post_id);

        if (is_wp_error($attachment_id)) {
            echo "Error attaching image: " . $attachment_id->get_error_message();
        } else {
            // Optionally set the first image as the post thumbnail
            if (!has_post_thumbnail($post_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }

        //echo "success";
    } else {
        echo "Error creating post.";
    }
    die;
} */
add_action('wp_ajax_handle_image_upload_ajax', 'handle_image_upload_ajax');
add_action('wp_ajax_nopriv_handle_image_upload_ajax', 'handle_image_upload_ajax');

function extractMeanBrightness($imgpath, $x = 0, $y = 0, $w = 0, $h = 0)
{
    $image_info = getimagesize($imgpath);
    $src_type = $image_info[2];

    if (!$x && !$y && !$w && !$h) {
        $w = $image_info[0];
        $h = $image_info[1];
    }

    switch ($src_type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($imgpath);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($imgpath);
            break;
        default:
            return 0;
    }

    $totalBrightness = 0;
    $pixelCount = 0;

    for ($i = $x; $i < $x + $w; $i++) {
        for ($j = $y; $j < $y + $h; $j++) {
            $rgb = imagecolorat($image, $i, $j);

            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            if ($src_type == IMAGETYPE_PNG) {
                $alpha = ($rgb & 0x7F000000) >> 24;

                $opacity = 127 - $alpha;

                if ($opacity == 0) {
                    continue;
                }
            }

            $brightness = (0.299 * $r + 0.587 * $g + 0.114 * $b);

            $totalBrightness += $brightness;
            $pixelCount++;
        }
    }

    if ($pixelCount > 0) {
        $meanBrightness = $totalBrightness / $pixelCount;
    } else {
        $meanBrightness = 0;
    }

    imagedestroy($image);

    return $meanBrightness / 255;
}

function composeImage($bg_filepath, $logo_filepath, $lAdj, $brand, $theme_color, $packname, $content_filepath, $text = '')
{
    $isTxt = $content_filepath == '';

    $bgWidth = 1080;
    $bgHeight = 1350;
    $logoWidth = $isTxt ? 250 : 160;
    $logoHeight = $logoWidth;
    $contentWidth = $isTxt ? 810 : 910;
    $contentHeight = $isTxt ? 895 : 910;
    $bgImg = resize_image_to_fit($bg_filepath, $bgWidth, $bgHeight, 1);
    $logoImg = resize_image_to_fit($logo_filepath, $logoWidth, $logoHeight, 0);
    $logoX = ($bgWidth - $contentWidth) / 2;
    $logoY = $isTxt ? 20 : 40;
    $contentX = ($bgWidth - $contentWidth) / 2;
    $contentY = $isTxt ? 285 : 235;
    $fontFile = plugin_dir_path(__FILE__) . 'font/OpenSans-Regular.ttf'; // Path to a TrueType font file
    $boldFontFile = plugin_dir_path(__FILE__) . 'font/OpenSans-Bold.ttf'; // Path to a TrueType font file

    if ($lAdj != '') {
        $lAdjImg = imagecreatefrompng(plugin_dir_path(__FILE__) . 'images' . DIRECTORY_SEPARATOR . $lAdj . '.png');
        imagecopy($bgImg, $lAdjImg, $logoX + $logoWidth / 2 - 983 / 2, $logoY + $logoHeight / 2 - 986 / 2, 0, 0, 983, 986);
    }

    // Merge the PNG logo onto the JPG image
    imagecopy($bgImg, $logoImg, $logoX, $logoY, 0, 0, $logoWidth, $logoHeight);

    // Draw white content background
    $contentBgColor = imagecolorallocate($bgImg, 255, 255, 255);
    imagefilledrectangle($bgImg, $contentX, $contentY, $contentX + $contentWidth, $contentY + $contentHeight, $contentBgColor);

    // Draw main content
    if (!$isTxt) {
        // Draw image content
        $contentMargin = 15;
        $contentImgX = $contentX + $contentMargin;
        $contentImgY = $contentY + $contentMargin;
        $contentImgW = $contentWidth - $contentMargin * 2;
        $contentImgH = $contentHeight - $contentMargin * 2;
        $contentImg = resize_image_to_fit($content_filepath, $contentImgW, $contentImgH, 1);
        imagecopy($bgImg, $contentImg, $contentImgX, $contentImgY, 0, 0, $contentImgW, $contentImgH);
    } else {
        // Draw text content
        $fontSize = 70; // Font size in points
        $margin = 63;

        // Calculate word wrap
        $lines = [];
        $words = explode(' ', $text);
        $line = '';
        foreach ($words as $word) {
            $testLine = $line . ' ' . $word;
            $box = imagettfbbox($fontSize, 0, $boldFontFile, $testLine);
            if ($box[2] > $contentWidth - $margin * 2) {
                $lines[] = trim($line);
                $line = $word;
            } else {
                $line = $testLine;
            }
        }
        $lines[] = trim($line);
        $lineGap = ($contentHeight - $margin * 2.2) / count($lines);

        // Add the text to the image
        $textColor = imagecolorallocate($bgImg, $theme_color[0], $theme_color[1], $theme_color[2]);
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $bbox = imagettfbbox($fontSize, 0, $boldFontFile, $line);
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];
            $textX = ($bgWidth - $textWidth) / 2;
            $textY = $contentY + $margin + $lineGap * ($i + 1) - ($lineGap - $fontSize) / 2;
            imagettftext($bgImg, $fontSize, 0, (int) $textX, (int) $textY, $textColor, $boldFontFile, $line);
        }
    }

    // Draw Brand Promise Text
    $textColor = imagecolorallocate($bgImg, 255, 255, 255); // Calculate text bounding box dimensions
    $bottomH = $bgHeight - $contentY - $contentHeight;
    $fontSize = 65;
    $marginBottom = ($bottomH - $fontSize) * 7 / 12;
    $bbox = imagettfbbox($fontSize, 0, $fontFile, $brand);
    $textWidth = $bbox[2] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[7];
    $textX = ($bgWidth - $textWidth) / 2;
    $textY = $bgHeight - $marginBottom;
    imagettftext($bgImg, $fontSize, 0, (int) $textX, (int) $textY, $textColor, $fontFile, $brand);

    // Draw Pack Title Bar
    $barColor = imagecolorallocate($bgImg, $theme_color[0], $theme_color[1], $theme_color[2]);
    $barR = $isTxt ? 46 : 35;
    $barX1 = $isTxt ? 716 : 810;
    $barY1 = $isTxt ? 118 : 90;
    $barX2 = $bgWidth;
    $barY2 = $barY1 + $barR * 2;
    $fontSize = $barR * 2 / 3;
    imagefilledrectangle($bgImg, $barX1, $barY1, $barX2, $barY2, $isTxt ? $textColor : $barColor);
    imagefilledarc($bgImg, $barX1, $barY1 + $barR, $barR * 2, $barR * 2, 90, 270, $isTxt ? $textColor : $barColor, IMG_ARC_PIE);
    imagettftext($bgImg, $fontSize, 0, (int) $barX1 + $barR / 4, (int) $barY2 - $barR * 2 / 3, $isTxt ? $barColor : $textColor, $fontFile, $packname);

    // Save the final image as a new file
    $newfilename = basename($bg_filepath) . '.new.jpg';
    $newfilepath = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $newfilename;
    imagejpeg($bgImg, $newfilepath, 100); // 100 is the quality percentage

    // Free up memory
    imagedestroy($bgImg);
    imagedestroy($logoImg);

    return $newfilepath;
}

/**
 * $mode: 0-contain, 1-cover
 */
function resize_image_to_fit($file_path, $max_width, $max_height, $mode = 0)
{
    $image_info = getimagesize($file_path);
    $src_width = $image_info[0];
    $src_height = $image_info[1];
    $src_type = $image_info[2];

    switch ($src_type) {
        case IMAGETYPE_JPEG:
            $src_image = imagecreatefromjpeg($file_path);
            break;
        case IMAGETYPE_PNG:
            $src_image = imagecreatefrompng($file_path);
            break;
        default:
            return false;
    }

    $src_ratio = $src_width / $src_height;
    $max_ratio = $max_width / $max_height;

    if ($mode == 0) { // contain
        $dst_x = 0;
        if ($src_ratio > $max_ratio) {
            $new_width = $max_width;
            $new_height = $max_width / $src_ratio;
            $dst_y = ($max_height - $new_height) / 2;
        } else {
            $new_width = $max_height * $src_ratio;
            $new_height = $max_height;
            $dst_y = 0;
        }
        $src_x = 0;
        $src_y = 0;
    } else { // cover
        if ($src_ratio > $max_ratio) {
            $new_width = $max_width;
            $new_height = $max_height;
            $src_x = intval(($max_height * $src_ratio - $max_width) / 2); // Crop from both sides
            $src_y = 0;
        } else {
            $new_width = $max_width;
            $new_height = $max_height;
            $src_x = 0;
            $src_y = intval(($max_width / $src_ratio - $max_height) / 2);
        }
        $dst_x = 0;
        $dst_y = 0;
    }

    $dst_image = imagecreatetruecolor($max_width, $max_height);

    if ($src_type == IMAGETYPE_PNG) {
        imagealphablending($dst_image, false);
        $transparentColor = imagecolorallocatealpha($dst_image, 0, 0, 0, 127);
        imagefill($dst_image, 0, 0, $transparentColor);
        imagesavealpha($dst_image, true);
    }

    imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $new_width, $new_height, $src_width - 2 * $src_x, $src_height - 2 * $src_y);

    return $dst_image;
}

function modulate(&$image, $brightnessFactor, $saturationFactor, $hueRotation)
{
    $width = imagesx($image);
    $height = imagesy($image);

    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $idx = imagecolorat($image, $x, $y);
            $rgb = imagecolorsforindex($image, $idx);

            // Convert RGB to HSL
            list($h, $s, $l) = rgbToHsl($rgb['red'], $rgb['green'], $rgb['blue']);

            // Rotate the hue
            $h += $hueRotation;
            if ($h > 360) {
                $h -= 360;
            } elseif ($h < 0) {
                $h += 360;
            }

            // Adjust saturation and brightness
            $s = max(0, min(1, $s * $saturationFactor));
            $l = max(0, min(1, $l * $brightnessFactor));

            // Convert back to RGB
            list($newR, $newG, $newB) = hslToRgb($h, $s, $l);

            // Apply the new color
            $newColor = imagecolorallocate($image, $rgb['red'], $rgb['green'], $rgb['blue']);
            imagesetpixel($image, $x, $y, $newColor);
        }
    }
}

function extractDominateColors($image)
{
    $width = imagesx($image);
    $height = imagesy($image);

    $smallImage = imagecreatetruecolor(100, 100);
    imagealphablending($smallImage, false);
    imagesavealpha($smallImage, true);
    imagecopyresampled($smallImage, $image, 0, 0, 0, 0, 100, 100, $width, $height);

    // Extract colors and count frequencies, omitting transparent pixels
    $colors = [];
    for ($y = 0; $y < 100; $y++) {
        for ($x = 0; $x < 100; $x++) {
            $idx = imagecolorat($smallImage, $x, $y);
            $rgba = imagecolorsforindex($smallImage, $idx);

            // Skip fully transparent pixels
            if ($rgba['alpha'] === 127) {
                continue;
            }
            // Skip Gray colors
            /* $min = min($rgba['red'], $rgba['green'], $rgba['blue']);
            $max = max($rgba['red'], $rgba['green'], $rgba['blue']);
            if ($max - $min < 40) {
                continue;
            } */

            $colors[] = ['r' => $rgba['red'], 'g' => $rgba['green'], 'b' => $rgba['blue']];
        }
    }
    imagedestroy($smallImage);

    list($clusters, $centroids) = kMeansClustering($colors, 2);

    // Determine monochrome

    // Determine the size of each cluster
    $clusterSizes = [count($clusters[0]), count($clusters[1])];
    $sizeDifferenceRatio = min($clusterSizes) / max($clusterSizes);

    // Calculate the distance between the two centroids
    $colorDifference = calculateColorDistance($centroids[0], $centroids[1]);

    if ($sizeDifferenceRatio < 0.3 || $colorDifference < 0.2) {
        list($monochromeCluster, $monochromeCentroid) = kMeansClustering($colors, 1);
        return $monochromeCentroid;
    } else {
        return $centroids;
    }
}

function kMeansClustering($colors, $k, $maxIter = 10)
{
    // Initialize centroids
    $centroids = initializeCentroids($colors, $k);

    // Main k-means loop
    for ($i = 0; $i < $maxIter; $i++) {
        $clusters = array_fill(0, $k, []);

        // Assign each color to the nearest centroid
        foreach ($colors as $color) {
            $minDistance = PHP_INT_MAX;
            $closestCentroidIndex = 0;
            foreach ($centroids as $index => $centroid) {
                $distance = calculateColorDistance($color, $centroid);
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $closestCentroidIndex = $index;
                }
            }
            $clusters[$closestCentroidIndex][] = $color;
        }

        // Recalculate centroids
        $newCentroids = [];
        foreach ($clusters as $index => $cluster) {
            $rTotal = $gTotal = $bTotal = $count = 0;
            foreach ($cluster as $color) {
                $rTotal += $color['r'];
                $gTotal += $color['g'];
                $bTotal += $color['b'];
                $count++;
            }
            if ($count > 0) {
                $newCentroids[$index] = [
                    'r' => $rTotal / $count,
                    'g' => $gTotal / $count,
                    'b' => $bTotal / $count
                ];
            } else {
                // Handle empty clusters by reinitializing centroids
                $newCentroids[$index] = $colors[array_rand($colors)];
            }
        }

        // Check for convergence
        if ($centroids === $newCentroids) {
            break;
        }

        $centroids = $newCentroids;
    }

    return [$clusters, $centroids];
}

// Function to calculate the Euclidean distance between two colors
function calculateColorDistance($color1, $color2)
{
    /* return sqrt(
        pow($color1['r'] - $color2['r'], 2) +
            pow($color1['g'] - $color2['g'], 2) +
            pow($color1['b'] - $color2['b'], 2)
    ); */
    list($h1, $s1, $l1) = rgbToHsl($color1['r'], $color1['g'], $color1['b']);
    list($h2, $s2, $l2) = rgbToHsl($color2['r'], $color2['g'], $color2['b']);

    $hueDifference = min(abs($h1 - $h2), 360 - abs($h1 - $h2)) / 180; // Normalize to [0, 1]
    $saturationDifference = abs($s1 - $s2);
    $lightnessDifference = abs($l1 - $l2) * 0.1; // 0.1 is lightness weight

    // You can decide how to weigh these components. Lightness difference is optional.
    $distance = sqrt(pow($hueDifference, 2) + pow($saturationDifference, 2) + pow($lightnessDifference, 2));

    return $distance;
}

// Function to convert RGB to HSL
function rgbToHsl($r, $g, $b)
{
    $r /= 255;
    $g /= 255;
    $b /= 255;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $h = 0;
    $s = 0;
    $l = ($max + $min) / 2;

    if ($max == $min) {
        $h = $s = 0; // achromatic
    } else {
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        switch ($max) {
            case $r:
                $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                break;
            case $g:
                $h = ($b - $r) / $d + 2;
                break;
            case $b:
                $h = ($r - $g) / $d + 4;
                break;
        }
        $h /= 6;
    }

    return [$h * 360, $s, $l];
}

function hslToRgb($h, $s, $l)
{
    $h /= 360;

    if ($s == 0) {
        $r = $g = $b = $l * 255; // achromatic
    } else {
        $hue2rgb = function ($p, $q, $t) {
            if ($t < 0) $t += 1;
            if ($t > 1) $t -= 1;
            if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
            if ($t < 1 / 2) return $q;
            if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
            return $p;
        };

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        $r = 255 * $hue2rgb($p, $q, $h + 1 / 3);
        $g = 255 * $hue2rgb($p, $q, $h);
        $b = 255 * $hue2rgb($p, $q, $h - 1 / 3);
    }

    return [round($r), round($g), round($b)];
}

// Function to initialize centroids using random selection or k-means++
function initializeCentroids($colors, $k)
{
    $centroids = [];

    // Random initialization: pick k random colors
    for ($i = 0; $i < $k; $i++) {
        $centroids[] = $colors[array_rand($colors)];
    }

    // Optionally, implement k-means++ for better initialization

    return $centroids;
}

// Gallery
function display_image_posts_with_pagination($atts)
{
    ob_start();
    // Shortcode attributes
    $atts = shortcode_atts(array(
        'posts_per_page' => 12
    ), $atts, 'image_posts');

    // Query for posts
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $args = array(
        'post_type'      => 'post', // Change to your custom post type if needed
        'category_name'  => 'image-layering',
        'posts_per_page' => $atts['posts_per_page'],
        'paged'          => $paged,
        'post_status'    => 'publish'
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        echo '<div class="user-posts alignfull">';
        while ($query->have_posts()) {
            $query->the_post();
    ?>
            <div class="post-item">
                <div class="post-thumbnail">
                    <?php if (has_post_thumbnail()) {
                        the_post_thumbnail();
                    } ?>
                </div>
                <h4 class="post-title"><!-- <a href="<?php the_permalink(); ?>"> --><?php the_title(); ?></h4>
                <div class="post-content">
                    <!-- <div class="post-images">
                        <?php
                        $attachments = array_keys(get_attached_media('image', get_the_ID()));
                        echo '<img src="' . esc_url(wp_get_attachment_image_src($attachments[0], 'thumbnail')[0]) . '" />';
                        //echo wp_get_attachment_image($attachments[0], 'full', false, array('class' => 'flexible-image'));
                        //echo '<span>+</span>';
                        echo '<img src="' . esc_url(wp_get_attachment_image_src($attachments[1], 'thumbnail')[0]) . '" />';
                        //echo wp_get_attachment_image($attachments[1], 'full', false, array('class' => 'flexible-image'));
                        //echo '<span>=</span>';
                        //echo wp_get_attachment_image($attachments[2], 'full', false, array('class' => 'flexible-image'));
                        ?>
                    </div> -->
                    <div class="post-excerpt">
                        <?php the_tags('<p class="post-packname">', '', '</p>'); ?>
                        <?php the_excerpt(); ?>
                    </div>
                </div>
                <div class="post-controls">
                    <a href="<?= get_the_post_thumbnail_url() ?>" download>Download</a>
                    <?php
                    if (current_user_can('delete_posts')) :
                    ?>
                        <a class="post-delete" data-post-id="<?php the_ID(); ?>" data-nonce="<?php echo wp_create_nonce('delete_post_' . get_the_ID()); ?>">Delete</a>
                    <?php
                    endif
                    ?>
                </div>
            </div>
<?php
        }
        echo '</div>';

        // Pagination
        echo '<div class="pagination-container">';
        $big = 999999999; // need an unlikely integer
        echo paginate_links(array(
            'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format'    => '?paged=%#%',
            'current'   => max(1, get_query_var('paged')),
            'total'     => $query->max_num_pages
        ));
        echo '</div>';

        wp_reset_postdata();
    } else {
        echo '<div>No posts found.</div>';
    }
    return ob_get_clean();
}
add_shortcode('image_posts', 'display_image_posts_with_pagination');

// Enqueue scripts and styles
function enqueue_gallery_scripts()
{
    wp_enqueue_style('image-gallery-stylesheet', plugin_dir_url(__FILE__) . 'css/gallery.css', false, '1.0', 'all');
    wp_enqueue_script('image-gallery-script', plugin_dir_url(__FILE__) . 'js/gallery.js', array('jquery'), null, true);
    wp_add_inline_script('image-gallery-script', 'custom_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_gallery_scripts');

function handle_delete_post()
{
    // Verify the nonce for security
    $nonce = $_POST['_wpnonce'];
    $post_id = intval($_POST['post_id']);

    if (!wp_verify_nonce($nonce, 'delete_post_' . $post_id)) {
        wp_die('Nonce verification failed');
    }

    // Check if the user has permission to delete the post
    if (current_user_can('delete_post', $post_id)) {
        wp_delete_post($post_id, true); // true for force delete
        echo 'success';
    } else {
        echo 'failed';
    }

    wp_die(); // All ajax handlers die when finished
}
add_action('wp_ajax_delete_post', 'handle_delete_post');

function custom_delete_post_attachments($post_id)
{
    // Check if the post type supports thumbnails
    if (has_post_thumbnail($post_id)) {
        // Get the thumbnail ID
        $thumbnail_id = get_post_thumbnail_id($post_id);
        // Delete the thumbnail
        wp_delete_attachment($thumbnail_id, true);
    }

    // Get all attachments for the post
    $attachments = get_children(array(
        'post_parent' => $post_id,
        'post_type'   => 'attachment'
    ));

    // Loop through each attachment and delete it
    foreach ($attachments as $attachment_id => $attachment) {
        wp_delete_attachment($attachment_id, true);
    }
}

// Hook into the delete post action
add_action('before_delete_post', 'custom_delete_post_attachments');
