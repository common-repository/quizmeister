<?php
/**
 * QuizMeister. Developed by Chris Dennett (dessimat0r@gmail.com)
 * Donate by PayPal to dessimat0r@gmail.com.
 * Bitcoin: 1JrHT9F96GjHYHHNFmBN2oRt79DDk5kzHq
 */

require_once 'qm-min-functions.php';

add_action('init', 'quizmeister_buffer_start');

// Start output buffering, needed for redirecting to quiz after creation
function quizmeister_buffer_start() {
   ob_start();
}

// format and echo error messages
function quizmeister_echo_errors($error_msgs) {
	?><ul id="qm-errors"><?php
	foreach ($error_msgs as $value) {
		?><li><?php echo $value;?></li><?php
	}
	unset($value);
	?></ul><?php
}

// function to redirect after login
function quizmeister_auth_redirect_login() {
	$user = wp_get_current_user();
	if ($user->ID !== 0) return;
	nocache_headers();
	wp_redirect( get_option('siteurl') . '/wp-login.php?redirect_to=' . urlencode($_SERVER['REQUEST_URI']));
	exit();
}

// Notify the admin about a new post
function quizmeister_notify_post_mail($post_id) {
	$post = get_post($post_id);
	$author_id = $post->post_author;
	$sitename = get_bloginfo('name');
	$permalink = get_permalink( $post_id );
	$to = get_bloginfo('admin_email');

	$headers = sprintf( 'From: %s <%s>', $sitename, $to );
	$subject = sprintf( __( '[%s] New Quiz Submission: %s' ), $sitename, get_the_title( $post_id ) );

	$msg  = sprintf( __( 'A new quiz has been submitted on \'%s\' titled \'%s\'' ), $sitename, get_the_title( $post_id ) ) . '\r\n\r\n';
	$msg .= sprintf( __( 'Author: %s / %s (%s)' ), get_the_author_meta('login', $author_id), get_the_author_meta('nicename', $author_id), get_the_author_meta('user_email', $author_id) ) . '\r\n';
	$msg .= sprintf( __( 'Permalink: %s' ), $permalink ) . "\r\n";
	$msg .= sprintf( __( 'Edit Link: %s' ), admin_url( 'post.php?action=edit&post=' . $post_id ) ) . '\r\n';

	//plugin api
	$to      = apply_filters( 'quizmeister_notify_to', $to );
	$subject = apply_filters( 'quizmeister_notify_subject', $subject );
	$msg     = apply_filters( 'quizmeister_notify_message', $msg );

	wp_mail( $to, $subject, $msg, $headers );
}

/**
 * Generic function to upload a file
 *
 * @since 0.8
 * @param string $field_name file input field name
 * @return bool|int attachment id on success, bool false instead
 */
function quizmeister_upload_file( $upload_data ) {
	$uploaded_file = wp_handle_upload( $upload_data, array('test_form' => false) );

	// If the wp_handle_upload call returned a local path for the image
	if ( isset( $uploaded_file['file'] ) ) {
		$file_loc = $uploaded_file['file'];
		$file_name = basename( $upload_data['name'] );
		$file_type = wp_check_filetype( $file_name );

		$attachment = array(
			'post_mime_type' => $file_type['type'],
			'post_title'     => preg_replace('/\.[^.]+$/', '', basename($file_name)),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		$attach_id   = wp_insert_attachment( $attachment, $file_loc );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_loc );
		$attach_data['quiz'] = 'quiz';
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
	}

	return false;
}

// Checks the submitted files if they have any errors and returns error list
function quizmeister_check_feat_img_upload() {
	$file_name = basename( $_FILES['quizmeister_featured_img']['name'][0] );

	// check if file is uploaded
	if (!$file_name) return null;

	$errors     = array();
	$mime       = get_allowed_mime_types();
	$size_limit = intval(get_option( 'quizmeister_attachment_max_size' ),10) * 1024;
	$tmp_name  = basename( $_FILES['quizmeister_featured_img']['tmp_name'][0] );

	$attach_type = wp_check_filetype( $file_name );
	$attach_size = $_FILES['quizmeister_featured_img']['size'][$i];

	// check file size
	if ( $attach_size > $size_limit ) {
		$errors[] = __( "Attachment is too big", 'quizmeister' );
	}

	// check file type
	if ( !in_array( $attach_type['type'], $mime ) ) {
		$errors[] = __( "Invalid attachment filetype", 'quizmeister' );
	}
	return $errors;
}

function quizmeister_get_cats() {
	$cats = get_categories( array('hide_empty' => false) );

	$list = array();

	if ( $cats ) {
		foreach ($cats as $cat) {
			$list[$cat->cat_ID] = $cat->name;
		}
		unset($cat);
	}

	return $list;
}

// Get lists of users from database
function quizmeister_list_users() {
	global $wpdb;
	$users = $wpdb->get_results( "SELECT ID, user_login from $wpdb->users" );
	if (!$users) return null;
	$list = array();
	foreach ($users as $user) {
		$list[$user->ID] = $user->user_login;
	}
	unset($user);
	return $list;
}

// Gets an array of the pages that contain the specified shortcode
function quizmeister_get_pages($shortcode = null) {
	global $wpdb;
	$pages = get_pages();
	if (!$pages) return null;
	$array = array();
	foreach ($pages as $page) {
		if (isset($shortcode) && !quizmeister_has_shortcode($shortcode, $page->ID)) continue;
		$array[$page->ID] = $page->post_title;
	}
	unset($page);
	return $array;
}

/**
 * Displays attachment information upon upload as featured image
 *
 * @since 0.8
 * @param int $attach_id attachment id
 * @return string
 */
function quizmeister_feat_img_html( $attach_id ) {
	$image = wp_get_attachment_image_src( $attach_id, 'thumbnail' );
	if ($image == '') return null;
	$post = get_post( $attach_id );

	$html =  sprintf( '<div class="qm-item" id="attachment-%d">', $attach_id );
	$html .= sprintf( '<img src="%s" alt="%s" />', $image[0], esc_attr( $post->post_title ) );
	$html .= sprintf( '<input type="button" class="qm-del-ft-image qm-small-button" data-id="%d" value="%s" />', $attach_id, __( 'Remove Image', 'quizmeister' ) );
	$html .= sprintf( '<input type="hidden" id="quizmeister_featured_img" name="quizmeister_featured_img" value="%d" />', $attach_id );
	$html .= '</div>';

	return $html;
}

// display msg if permalinks aren't setup correctly
function quizmeister_permalink_nag() {
	if ( current_user_can( 'manage_options' ) ) {
		$msg = sprintf( __( 'You need to set your <a href="%1$s">permalink custom structure</a> to at least contain <b>/&#37;postname&#37;/</b> before QuizMeister will work correctly.', 'quizmeister' ), 'options-permalink.php' );
	}
	echo '<div class="error fade"><p>'.$msg.'</p></div>';
}

//if not found %postname%, shows a error msg at admin panel
if ( !stristr( get_option( 'permalink_structure' ), '%postname%' ) ) {
	add_action( 'admin_notices', 'quizmeister_permalink_nag', 3 );
}

function quizmeister_get_image_sizes() {
	$image_sizes_orig = get_intermediate_image_sizes();
	$image_sizes_orig[] = 'full';
	$image_sizes = array();

	foreach ($image_sizes_orig as $size) {
		$image_sizes[$size] = $size;
	}

	return $image_sizes;
}

function quizmeister_has_shortcode( $shortcode = '', $post_id = null ) {
	if (!isset($shortcode)) return false;
	$post_obj = isset($post_id) ? get_post($post_id) : get_post(get_the_ID());
	if (!$post_obj) return false;

	// check post content for the short code (with options and also without)
	return (stripos($post_obj->post_content, '[' . $shortcode . ' ') !== false) || (stripos($post_obj->post_content, '[' . $shortcode . ']') !== false);
}

// TODO: optimise this function
function quizmeister_get_cat_trail($catid) {
	$cats = array();
	$first = true;
	$cat_datas = array();
	$i = 0;
	while ($first || isset($cat_data)) {
		// populate selected category data
		$curr = $first ? $catid : ((!empty($cat_datas) && isset($cat_datas[0]->parent) && $cat_datas[0]->parent > 0) ? $cat_datas[0]->parent : null);
		$cat_data = isset($curr) ? get_category($curr) : null;
		if ($first && !isset($cat_data)) return null;
		$first = false;
		if (!isset($cat_data)) continue;
		array_unshift($cat_datas, $cat_data); // insert cat data at front of array
	 }
	 return $cat_datas;
}

function quizmeister_get_quiz_cats($parent_cat=0, $level=0) {
	$exclude = get_option( 'quizmeister_exclude_cats', '' );
	$cats = get_categories(array(
		'taxonomy'=>'category',
		'parent'=>$parent_cat,
		'hide_empty'=>0,
		'hierarchical'=>1,
		'exclude'=>$exclude
	));
	$cats_to_post_count = array();
	foreach ($cats as $cat) {
		$query = new WP_Query(array(
			'post_type'=>'quizzes',
			'cat'=>$cat->term_id
		));
		$cats_to_post_count[] = array(
			'cat_id'=>$cat->term_id,
			'name'=>$cat->name,
			'posts'=>$query->found_posts
		);
	}
	$html = $level <= 0 ? '<select id="category[]" class="cat required-field" name="category[]">' : '';
	foreach ($cats_to_post_count as $cat_assoc) {
		$cats = get_categories(array(
			'parent'=>$cat_assoc['cat_id'],
			'hide_empty'=>0,
			'hierarchical'=>1,
			'exclude'=>$exclude
		));
		$html .= '<option class="cat-lvl-'.$level.($cats ? ' has-child-cats' : '').'" value="'.$cat_assoc['cat_id'].'">'.quizmeister_get_spaces($level).$cat_assoc['name'].' ('.strval($cat_assoc['posts']).')</option>';
		if ($cats) $html .= quizmeister_get_quiz_cats($cat_assoc['cat_id'], $level+1);
	}
	if ($level <= 0) $html .= '</select>';
	return $html;
}

function quizmeister_get_quiz_child_cats($parent_cat=0, $selected=null) {
	$exclude = get_option( 'quizmeister_exclude_cats', '');
	$cat_type = get_option( 'quizmeister_cat_type', 'dynamic' );
	$cats = get_categories(array(
		'taxonomy'=>'category',
		'parent'=>$parent_cat,
		'hide_empty'=>0,
		'hierarchical'=>1,
		'exclude'=>$exclude
	));
	if (!$cats) return '';
	for ($i = 0; $i < count($cats); $i++) {
		if ($cats[$i]->term_id == 1) array_unshift($cats, array_splice($cats, $i, 1)[0]);
	}
	$cats_to_post_count = array();
	foreach ($cats as $cat) {
		$query = new WP_Query(array(
			'post_type'=>'quizzes',
			'cat'=>$cat->term_id
		));
		$cats_to_post_count[] = array(
			'cat_id'=>$cat->term_id,
			'name'=>$cat->name,
			'posts'=>$query->found_posts
		);
	}
	$html = '<select id="category[]" class="cat cat-ajax" name="category[]">';
	if (isset($parent_cat) && $parent_cat > 0) $html .= '<option value="-1"'.((!isset($selected) || $selected <= 0) ? ' selected="selected"' : '').'>Use parent</option>';
	// check for child categories for eaxh category
	foreach ($cats_to_post_count as $cat_assoc) {
		$cats = get_categories(array(
			'parent'=>$cat_assoc['cat_id'],
			'hide_empty'=>0,
			'hierarchical'=>1,
			'exclude'=>$exclude
		));
		$html .= '<option '.($cats?' class="has-child-cats"':'').' value="'.$cat_assoc['cat_id'].'"'.((isset($selected) && $selected == $cat_assoc['cat_id']) ? ' selected="selected"' : '').'>'.$cat_assoc['name'].($cats ? '...' : '').' ('.strval($cat_assoc['posts']).')</option>';
	}
	$html .= '</select>';
	return $html;
}

function quizmeister_echo_cat_sels($selected = null) {
	$cat_type = get_option( 'quizmeister_cat_type', 'dynamic' );
	$sel_valid = isset($selected) && is_numeric($selected) && ($selected = intval($selected)) > 0;
	if ($sel_valid) $cat_datas = quizmeister_get_cat_trail($selected);
	?><span class="cat-wrap-all main-input"><span class="category-wrap"><?php
	if (isset($cat_datas)) {
		$first = true;
		for ($i = 0; $i < count($cat_datas); $i++) {
			if ($i > 0) echo ' <img class="qm-cat-arrow-img" src="' . plugins_url('/images/arrow-right.png', __FILE__) . '">';
			?><span id="cat-wrap-lvl-<?php echo strval($i);?>" class="cat-ajax-wrap<?php echo $i < count($cat_datas)-1 ? ' has-child-cats' : '';?>" data-level="<?php echo strval($i);?>" style="display: inline-block;"><?php
			echo quizmeister_get_quiz_child_cats($cat_datas[$i]->parent, $cat_datas[$i]->cat_ID);
			if ($i >= count($cat_datas)-1) {
				$html = quizmeister_get_quiz_child_cats($cat_datas[$i]->cat_ID);
				if (($html = trim($html)) !== '') {
					echo ' <img class="qm-cat-arrow-img" src="' . plugins_url('/images/arrow-right.png', __FILE__) . '">';
					echo $html;
				}
			} ?></span><?php
		}
	} else {
		if ($cat_type == 'dynamic') {
			?><span id="cat-wrap-lvl-0" class="cat-ajax-wrap" style="display: inline-block;" data-level="0"><?php
			echo quizmeister_get_quiz_child_cats();
			?></span><?php
		} else {
			?><span id="cat-wrap" class="cat-wrap" style="display: inline-block;"><?php
			echo quizmeister_get_quiz_cats();
			?></span><?php
		}?>
	<?php
	}
	?></span><span class="cat-loading" style="display: inline-block;"></span></span><?php
}

function quizmeister_cron_gallery_cleanup($cleanup = false) {
	$orphans_cleaned = 0;
	if ($cleanup) $orphans_cleaned = quizmeister_gallery_cleanup();
	$cleanup_mins = intval(get_option('quizmeister_gallery_cleanup_mins', 240*60), 10);
	$next_run = wp_next_scheduled('quizmeister_evt_cron_gallery_cleanup', array(true));
	if ($next_run !== false) {
		wp_clear_scheduled_hook('quizmeister_evt_cron_gallery_cleanup', array(true));
		remove_action('quizmeister_evt_cron_gallery_cleanup', 'quizmeister_cron_gallery_cleanup');
	}
	if ($cleanup_mins > 0) {
		$next_run = time()+($cleanup_mins*60);
		// schedule next event
		add_action('quizmeister_evt_cron_gallery_cleanup', 'quizmeister_cron_gallery_cleanup', 10, 2);
		wp_schedule_single_event($next_run, 'quizmeister_evt_cron_gallery_cleanup', array(true));
	}
	return $orphans_cleaned;
}

function quizmeister_get_qm_media_orphans(){
	// Setup array for storing objects
	$quiz_images = array();

	// Arguments for custom WP_Query loop
	$my_cpts_args = array(
		'post_type' => 'quiz',
		'posts_per_page' => -1,
		'post_status' => 'any'
	);

	// Make the new instance of the WP_Query class
	$my_cpts = new WP_Query( $my_cpts_args );
	foreach ( $my_cpts->posts as $post) {
		// arguments for get_posts
		$attachment_args = array(
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'post_status' => 'any',
			'post_parent' => $post->ID,
			'posts_per_page' => -1
		);
		// get the posts
		$this_posts_attachments = get_posts( $attachment_args );
		foreach ( $this_posts_attachments as $image) {
			$quiz_images[$image->ID] = $image;
		}
	}
	$args = array(
		'post_type' => 'attachment',
		'post_mime_type' =>'image',
		'post_status' => 'any',
		'posts_per_page' => -1
	);
	$query_images = new WP_Query( $args );
	$images = array();
	foreach ( $query_images->posts as $image) {
		$images[$image->ID] = $image;
	}

	// ignore quiz images
	$unfilt_orphans = array_diff_key($images, $quiz_images);
	$quiz_orphans = array();

	// make absolutely sure we don't delete any attachement we aren't supposed to
	// so.. we only limit to attachments with 'quiz = quiz' metadata
	foreach ($unfilt_orphans as $orphan) {
		$data = wp_get_attachment_metadata($orphan->ID, true);
		if ($data === false || !isset($data['quiz'])) continue;
		if ($data['quiz'] !== 'quiz') continue;
		// only delete orphaned images older than 30 mins since creation
		if ((time() - mysql2date('G', $orphan->post_date_gmt)) < 30*60) continue;
		$quiz_orphans[$orphan->ID] = $orphan;
	}
	return $quiz_orphans;
}


function quizmeister_gallery_cleanup() {
    $this_purge_count = 0;
	$purged_count = intval(get_option('quizmeister_purged_gallery_orphans_count', 0),10);
	foreach (quizmeister_get_qm_media_orphans() as $orphan) {
		wp_delete_post($orphan->ID, true);
		$this_purge_count++;
	}
	update_option('quizmeister_purged_gallery_orphans_count', $purged_count+$this_purge_count);
	return $this_purge_count;
}
