<?php
/*
Plugin Name: Image Layering
Description: Allows users to upload images and synthesizing them from the frontend.
Version: 1.0
Author: yuari@konsoft
*/

use function Avifinfo\read;

function image_layering_form_shortcode()
{
    ob_start();
?>
    <form id="user-post-form" action="" method="post" enctype="multipart/form-data">
        <div id="drag-and-drop-container">
            <div class="drag-and-drop-wrapper">
                <div id="drag-and-drop-area1" class="drag-and-drop-area">
                    <span class="explain"><span><b>Background</b>(.jpg)<br>(1000x1000)</span><br>Drag &amp; Drop<br>or<br>Click</span>
                    <div id="image-preview-container1" class="image-preview-container"></div>
                </div>
                <input type="file" id="image_file1" class="image_file" name="image_file" style="display: none;" accept="image/jpeg">
            </div>
            <div id="drag-and-drop-wrapper2" class="drag-and-drop-wrapper">
                <div id="drag-and-drop-area2" class="drag-and-drop-area">
                    <span class="explain"><span><b>Logo</b>(.png)<br>(200x300)</span><br>Drag &amp; Drop<br>or<br>Click</span>
                    <div id="image-preview-container2" class="image-preview-container"></div>
                </div>
                <input type="file" id="image_file2" class="image_file" name="logo_file" style="display: none;" accept="image/png">
            </div>
        </div>

        <button id="upload-btn">Upload Images</button>

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

        echo "success";
    } else {
        echo "Error creating post.";
    }
    die;
}
add_action('wp_ajax_handle_image_upload_ajax', 'handle_image_upload_ajax');
add_action('wp_ajax_nopriv_handle_image_upload_ajax', 'handle_image_upload_ajax');

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
        if ($src_ratio > $max_ratio) {
            $new_width = $max_width;
            $new_height = $max_width / $src_ratio;
        } else {
            $new_width = $max_height * $src_ratio;
            $new_height = $max_height;
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
    }

    $dst_image = imagecreatetruecolor($new_width, $new_height);

    if ($src_type == IMAGETYPE_PNG) {
        imagealphablending($dst_image, false);
        imagesavealpha($dst_image, true);
    }

    imagecopyresampled($dst_image, $src_image, 0, 0, $src_x, $src_y, $new_width, $new_height, $src_width - 2 * $src_x, $src_height - 2 * $src_y);

    return $dst_image;
}

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
                    <div class="post-images">
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
                    </div>
                    <div class="post-excerpt">
                        <?php the_excerpt(); ?>
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

function handle_delete_post() {
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

function custom_delete_post_attachments($post_id) {
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