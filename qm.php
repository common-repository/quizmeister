<?php
/*
Plugin Name: QuizMeister
Plugin URI: http://demio.us/quizmeister/
Description: QuizMeister is a quiz-creation plugin for Wordpress that allows users to create and share their own quizzes.
Author: Chris Dennett
Version: 1.0.1
Author URI: http://demio.us/
*/

/**
 * QuizMeister. Developed by Chris Dennett (dessimat0r@gmail.com)
 * Donate by PayPal to dessimat0r@gmail.com.
 * Bitcoin: 1JrHT9F96GjHYHHNFmBN2oRt79DDk5kzHq
 */
require_once 'qm-functions.php';

if (is_admin()) require_once 'admin/settings.php';
require_once 'qm-new-quiz.php';
require_once 'qm-ajax.php';
require_once 'qm-quiz.php';

class QuizMeister_Main {
	function __construct() {
		register_activation_hook( __FILE__, array($this, 'activate') );
		register_deactivation_hook( __FILE__, array($this, 'deactivate') );

		add_action( 'init', array($this, 'load_textdomain') );
		add_action( 'init', array($this, 'reg_post_type') );

		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );
		// only allow template override in the case that this is 'yes'
		if (get_option('quizmeister_use_theme_quiz_template', 'no') === 'no') {
			add_filter('single_template', array($this, 'change_post_type_template'));
		}
		add_filter('wp_link_query_args', array($this, 'mod_wp_link_query_args'));
	}

	// filter function to remove quizzes from all editor ui link functionality
	// (insert/edit link)
	function mod_wp_link_query_args($query) {
		$key = array_search('quiz', $query['post_type']);
		if ($key !== false) unset($query['post_type'][$key]);
		return $query;
	}

	function change_post_type_template($single_template) {
		global $post;
		if ($post->post_type === 'quiz') {
			$single_template = plugin_dir_path( __FILE__ ) . 'templates/single-quiz.php';
		}
		return $single_template;
	}

	function activate() {
		//TODO: plugin setup?
		global $wpdb;
		flush_rewrite_rules( false );
	}

	function deactivate() {
		//TODO: plugin teardown?
		global $wpdb;
		flush_rewrite_rules( false );
	}

	function enqueue_scripts() {
		$path = plugins_url('', __FILE__ );

		// multi-site upload limit filter
		if (is_multisite()) require_once ABSPATH . '/wp-admin/includes/ms.php';
		require_once ABSPATH . '/wp-admin/includes/template.php';

		wp_enqueue_style( 'quizmeister', $path . '/css/qm.css' );

		$params = array('plugin_base' => $path);
		wp_enqueue_script( 'quizmeister', $path . '/js/qm.js', array('jquery') );

		$feat_img_enabled = (get_option('quizmeister_enable_featured_image', 'yes') === 'yes') ? true : false;

		wp_localize_script('quizmeister', 'quizmeister', array(
			'plugin_base' => $path,
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'confirmMsg' => __( 'Are you sure?', 'quizmeister' ),
			'nonce' => wp_create_nonce( 'quizmeister_nonce' ),
			'featEnabled' => $feat_img_enabled,
			'plupload' => array(
				'runtimes' => 'html5,silverlight,flash,html4',
				'browse_button' => 'qm-ft-upload-pickfiles',
				'container' => 'qm-ft-upload-container',
				'file_data_name' => 'quizmeister_featured_img',
				'url' => admin_url( 'admin-ajax.php' ) . '?action=quizmeister_featured_img&nonce=' . wp_create_nonce( 'quizmeister_featured_img' ),
				'flash_swf_url' => includes_url( 'js/plupload/plupload.flash.swf' ),
				'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
				'filters' => array(array('title' => __( 'Image files' ), 'extensions' => 'jpg,jpeg,gif,png')),
				'multipart' => true,
				'urlstream_upload' => true,
				'max_file_size' => wp_max_upload_size() . 'b'
			)
		));
	}

	function load_textdomain() {
		$locale = apply_filters( 'quizmeister_locale', get_locale() );
		$mofile = dirname( __FILE__ ) . "/languages/qm-{$locale}.mo";

		if (file_exists($mofile)) {
			load_textdomain('quizmeister', $mofile);
		}
	}

	function reg_post_type() {
		$labels = array(
			'name'              => __( 'Quizzes' ),
			'singular_name'     => __( 'Quiz' ),
			'search_items'      => __( 'Search Quizzes' ),
			'all_items'         => __( 'All Quizzes' ),
			'parent_item'       => __( 'Parent Quiz' ),
			'parent_item_colon' => __( 'Parent Quiz:' ),
			'edit_item'         => __( 'Edit Quiz' ),
			'update_item'       => __( 'Update Quiz' ),
			'add_new_item'      => __( 'Add New Quiz' ),
			'new_item_name'     => __( 'New Quiz Name' ),
			'menu_name'         => __( 'Quizzes' )
		);

		// create a new post type
		register_post_type(
			'quiz',
			array(
				'labels' => $labels,
				'public' => true,
				'has_archive' => false,
				'rewrite' => array('slug' => 'quizzes'),
				'query_var' => 'quizzes',
				'supports' => array('title', 'editor', 'author', 'thumbnail')
			)
		);
	}
}

$quizmeister_main = new QuizMeister_Main();
