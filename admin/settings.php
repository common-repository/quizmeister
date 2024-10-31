<?php
/**
 * QuizMeister. Developed by Chris Dennett (dessimat0r@gmail.com)
 * Donate by PayPal to dessimat0r@gmail.com.
 * Bitcoin: 1JrHT9F96GjHYHHNFmBN2oRt79DDk5kzHq
 */

class QuizMeister_Settings {
	private $sections = array();
	private $fields = array();
	private $this_gc_count = null;

	public function __construct() {
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_menu', array($this, 'admin_menu'));
	}

	function admin_enqueue_scripts() {
		wp_enqueue_style('admin_style', plugins_url('/css/admin.css', __FILE__));
		wp_enqueue_script('jquery');
	}

	function plugin_page() {
		$plugin_data = get_plugin_data(dirname(dirname(__FILE__)).'/qm.php');
		$plugin_ver = $plugin_data['Version'];
		?>
		<div class="wrap">
			<h2 id="qm-admin-title">QuizMeister Settings</h2>
			<div id="qm-admin-support">
				<p><strong>QuizMeister <?php echo $plugin_ver;?></strong>, the number 1 solution to allow users to create their own quizzes on your Wordpress install without providing unsecure and unrestricted access and providing social network share support (Twitter, Facebook, etc.) with external media support (Imgur, Pintrest, YouTube, Vimeo, etc.) through oEmbed. For external/social login support (Google, Facebook, etc) we suggest installing the <a href="https://wordpress.org/plugins/wp-oauth/">WP-OAuth</a> plugin.</p><p>For support,
				e-mail <a href="mailto:dessimat0r@gmail.com">dessimat0r@gmail.com</a>.</p>
			</div>
			<div id="qm-donate">
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top" style="padding-bottom: 50px; float: left;">
				<input type="hidden" name="cmd" value="_s-xclick" />
				<input type="hidden" name="hosted_button_id" value="KXNX6FPVJ7KGG" />
				<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate" />
				<img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1" />
				</form>
				Any donations are graciously received via the PayPal button to the left and go to the author, who has poured a lot of work into the development of this plugin and needs to stay alive ;) Thanks in advance :)<br />Bitcoin: 1JrHT9F96GjHYHHNFmBN2oRt79DDk5kzHq.
			</div>
			<?php echo settings_errors();?>
			<div class="clear"></div>
			<?php echo $this->output_page();?>
		</div>
		<?php
	}

	function admin_init() {
		$fix_gc_cron = get_option('quizmeister_fix_cron_job', 'no') === 'yes';
		if ($fix_gc_cron) {
			update_option('quizmeister_fix_cron_job', 'no'); // reset option
			$this->this_gc_count = quizmeister_cron_gallery_cleanup(true);
		}
		$gallery_cleanup_next_run = wp_next_scheduled('quizmeister_evt_cron_gallery_cleanup', array(true));
		$this->sections = apply_filters('quizmeister_settings_sections', array(
			'quizmeister_quiz' => array(
				'id' => 'quizmeister_quiz',
				'title' => __('Quiz Display', 'quizmeister'),
				'fields' => array(
					'quizmeister_twitter_data_rel' => array(
						'name' => 'quizmeister_twitter_data_rel',
						'label' => __('Twitter data-related' , 'quizmeister'),
						'desc' => __('If specified, related Twitter accounts associated with share tweets (see Twitter API docs)', 'quizmeister'),
						'type' => 'text',
						'default' => ''
					),
					'quizmeister_facebook_app_id' => array(
						'name' => 'quizmeister_facebook_app_id',
						'label' => __('Facebook App ID', 'quizmeister'),
						'desc' => __('If specified, allows for sharing of quiz to Facebook. Get this from your Facebook app settings after setting up the API stuff', 'quizmeister'),
						'type' => 'text',
						'default' => ''
					)
				)
			),
			'quizmeister_posting' => array(
				'id' => 'quizmeister_posting',
				'title' => __('Quiz Posting', 'quizmeister'),
				'fields' => array(
					'quizmeister_allow_cats' => array(
						'name' => 'quizmeister_allow_cats',
						'label' => __('Allow category choice?', 'quizmeister'),
						'desc' => __('If selected, users will be able to choose a category during quiz creation (otherwise the default category is used)', 'quizmeister'),
						'type' => 'checkbox',
						'default' => 'yes'
					),
					'quizmeister_exclude_cats' => array(
						'name' => 'quizmeister_exclude_cats',
						'label' => __('Exclude category IDs', 'quizmeister'),
						'desc' => __('Exclude certain categories from the dropdown (comma-delimited)', 'quizmeister'),
						'type' => 'text'
					),
					'quizmeister_default_cat' => array(
						'name' => 'quizmeister_default_cat',
						'label' => __('Default post category', 'quizmeister'),
						'desc' => __('If users are not allowed to choose a category, this category will be used instead. Also selects this category by default in the category selector', 'quizmeister'),
						'type' => 'select',
						'default' => 1,
						'options' => quizmeister_get_cats()
					),
					'quizmeister_cat_type' => array(
						'name' => 'quizmeister_cat_type',
						'label' => __('Category selector style', 'quizmeister'),
						'type' => 'radio',
						'options' => array(
							'standard' => __('Standard', 'quizmeister'),
							'dynamic' => __('Dynamic', 'quizmeister')
						),
						'default' => 'dynamic'
					),
					'quizmeister_enable_featured_image' => array(
						'name' => 'quizmeister_enable_featured_image',
						'label' => __('Enable Featured Image upload?', 'quizmeister'),
						'desc' => __('If selected, allows the user to upload a featured image during quiz creation', 'quizmeister'),
						'type' => 'checkbox',
						'default' => 'yes'
					),
					'quizmeister_editor_type' => array(
						'name' => 'quizmeister_editor_type',
						'label' => __('Content editor type', 'quizmeister'),
						'type' => 'select',
						'options' => array(
							'plain' => __('Plain', 'quizmeister'),
							'rich' => __('Rich', 'quizmeister'),
							'full' => __('Full', 'quizmeister')
						),
						'default' => 'full'
					),
					/*
					array(
						'name' => 'allow_tags',
						'label' => __('Allow post tags?', 'quizmeister'),
						'desc' => __('If selected, allows users to add tags during quiz creation', 'quizmeister'),
						'type' => 'checkbox',
						'default' => 'yes'
					),
					*/
					'quizmeister_use_theme_quiz_template' => array(
						'name' => 'quizmeister_use_theme_quiz_template',
						'label' => __('Use theme quiz template?', 'quizmeister'),
						'desc' => __('If selected, disables the built-in quiz template that is most compatible with TwentyFifteen so that you can define your own in a theme (hint: copy out the \'templates\' directory in the plugin directory into \'themes\' and correct the URL look-ups)', 'quizmeister'),
						'type' => 'checkbox',
						'default' => 'no'
					)
				)
			),
			'quizmeister_misc' => array(
				'id' => 'quizmeister_misc',
				'title' => __('Miscellaneous', 'quizmeister'),
				'desc' =>
					'<p><strong>Shortcodes to use on pages:</strong> <code>[quizmeister_new_quiz]</code>: new quiz page shortcode.</p>'.
					'<p>Note that if you don\'t see a page in the listboxes after creating it and putting the shortcode on there, check on the Text tab of the input field of the page content editor for extraneous HTML tags that may be embedded inside the shortcode. Shortcode arguments are permitted but should be ignored during this lookup.</p>'				,
				'fields' => array(
					'quizmeister_post_notification' => array(
						'name' => 'quizmeister_post_notification',
						'label' => __('New quiz notification?', 'quizmeister'),
						'desc' => __('If selected, a mail will be sent to the admin when a new quiz is created', 'quizmeister'),
						'type' => 'checkbox',
						'default' => 'yes'
					),
					'quizmeister_new_quiz_page_id' => array(
						'name' => 'quizmeister_new_quiz_page_id',
						'label' => __('\'New Quiz\' page', 'quizmeister'),
						'desc' => __('Select the default page where <code>[quizmeister_new_quiz]</code> is located', 'quizmeister'),
						'type' => 'select',
						'options' => quizmeister_get_pages('quizmeister_new_quiz')
					),
					'quizmeister_gallery_cleanup_mins' => array(
						'name' => 'quizmeister_gallery_cleanup_mins',
						'label' => __('Gallery clean-up frequency', 'quizmeister'),
						'desc' => __('How often (in minutes) to clean up orphaned gallery images from quiz posts in media uploads (set to 0 to disable). ', 'quizmeister').get_option('quizmeister_purged_gallery_orphans_count',0). ' orphan(s) cleaned so far. '.($gallery_cleanup_next_run !== false ? ('Running again in ' . intval(max(0, $gallery_cleanup_next_run - time())/60,10) . ' mins'):'Not currently scheduled.'),
						'type' => 'text',
						'default' => strval(30*60), // 30 minutes
						'sanitize_callback' => 'intval'
					),
					'quizmeister_fix_cron_job' => array(
						'name' => 'quizmeister_fix_cron_job',
						'label' => __('Fix gallery cleanup job in the case that it isn\'t running?', 'quizmeister'),
						'desc' => __('If selected, gallery will be cleaned up and the cron job reset upon submission of the form. This option gets reset after cleanup.', 'quizmeister'),
						'type' => 'checkbox',
						'default' => 'no'
					)
				)
			)
		));
		// register sections
		foreach ($this->sections as $section) {
			add_settings_section(
				$section['id'], $section['title'],
				array($this, 'section_callback'), $section['id']
			);
			foreach ($section['fields'] as $field) {
				$type = isset($field['type']) ? $field['type'] : 'text';
				$args = array(
					'id' => $field['name'],
					'name' => $field['label'],
					'section' => $section['id'],
					'desc' => isset($field['desc']) ? $field['desc'] : '',
					'size' => isset($field['size']) ? $field['size'] : null,
					'options' => isset($field['options']) ? $field['options'] : '',
					'std' => isset($field['default']) ? $field['default'] : ''
				);
				add_settings_field($field['name'], $field['label'], array($this, 'setfield_' . $type), $section['id'], $section['id'], $args);
				$sanitize_callback = null;
				if (isset($field['sanitize_callback'])) $sanitize_callback = $field['sanitize_callback'];
				// TODO: chain this call?
				else if ($type === 'text' || $type === 'textfield') $sanitize_callback = 'sanitize_text_field';
				if (isset($sanitize_callback)) register_setting($section['id'], $field['name'], $sanitize_callback);
				else register_setting($section['id'], $field['name']);
			}
		}
	}

	function admin_menu() {
		add_menu_page(
			__('QuizMeister', 'quizmeister'),
			__('QuizMeister', 'quizmeister'),
			'activate_plugins', 'quizmeister', array($this, 'plugin_page'), 'dashicons-editor-help'
		);
	}

	/**
	 * Displays text field for settings field
	 * @param array $args settings field args
	 */
	function setfield_text($args) {
		$value = esc_attr(get_option($args['id'], $args['std']));
		$size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';

		$html = sprintf('<input type="text" class="%1$s-text" id="%2$s" name="%2$s" value="%3$s"/>', $size, $args['id'], $value);
		$html .= sprintf('<span class="description">%s</span>', $args['desc']);

		echo $html;
	}

	/**
	 * Displays checkbox for settings field
	 * @param array $args settings field args
	 */
	function setfield_checkbox($args) {
		$value = get_option($args['id'], $args['std']);

		$html = sprintf('<input type="hidden" name="%1$s" value="no" />', $args['id']);
		$html .= sprintf('<input type="checkbox" class="checkbox" id="%1$s" name="%1$s" value="yes"%2$s />', $args['id'], checked($value, 'yes', false));
		$html .= sprintf('<span class="description">%s</span>', $args['desc']);

		echo $html;
	}

	/**
	 * Displays multi-checkbox for settings field
	 * @param array $args settings field args
	 */
	function setfield_multicheck($args) {
		$value = get_option($args['id'], $args['std']);

		$html = '';
		foreach ($args['options'] as $key => $label) {
			$checked = isset($value[$key]) ? $value[$key] : '0';
			$html .= sprintf('<input type="checkbox" class="checkbox" id="%1$s[%2$s]" name="%1$s[%2$s]" value="%2$s"%3$s />', $args['id'], $key, checked($checked, $key, false));
			$html .= sprintf('<label for="%1$s[%3$s]">%2$s</label><br>', $args['section'], $args['id'], $label, $key);
		}
		$html .= sprintf('<span class="description">%s</span>', $args['desc']);

		echo $html;
	}

	/**
	 * Displays a multi-checkbox for settings field
	 * @param array $args settings field args
	 */
	function setfield_radio($args) {
		$value = get_option($args['id'], $args['std']);

		$html = '';
		foreach ($args['options'] as $key => $label) {
			$html .= sprintf('<input type="radio" class="radio" id="%1$s[%2$s]" name="%1$s" value="%2$s"%3$s />', $args['id'], $key, checked($value, $key, false));
			$html .= sprintf('<label for="%1$s[%3$s]">%2$s</label><br>', $args['id'], $label, $key);
		}
		$html .= sprintf('<span class="description">%s</span>', $args['desc']);

		echo $html;
	}

	/**
	 * Displays select dropdown for settings field
	 * @param array $args settings field args
	 */
	function setfield_select($args) {
		$value = esc_attr(get_option($args['id'], $args['std']));
		$size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';

		$html = sprintf('<select class="%1$s" name="%2$s" id="%2$s">', $size, $args['id']);
		$found_sel = null;
		foreach ($args['options'] as $key => $label) {
			if ($value == $key) {
				$found_sel = $key;
				break;
			}
		}
		if (!$found_sel) $html .= '<option disabled selected value> -- select page -- </option>';
		foreach ($args['options'] as $key => $label) {
			$html .= sprintf('<option value="%1$s"%2$s>%3$s</option>', $key, isset($found_sel) ? selected($value, $key, false) : '', $label);
		}
		$html .= sprintf('</select>');
		$html .= sprintf('<span class="description">%s</span>', $args['desc']);

		echo $html;
	}

	/**
	 * Displays textarea for settings field
	 * @param array $args settings field args
	 */
	function setfield_textarea($args) {
		$value = esc_textarea(get_option($args['id'], $args['std']));
		$size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';

		$html = sprintf('<textarea rows="5" cols="55" class="%1$s-text" id="%2$s" name="%2$s">%3$s</textarea>', $size, $args['id'], $value);
		$html .= sprintf('<br><span class="description">%s</span>', $args['desc']);

		echo $html;
	}

	// outputs the page
	function output_page() {
		$fix_gc_cron = get_option('quizmeister_fix_cron_job', 'no') === 'yes';
		if (isset($this->this_gc_count)) {
			echo $this->this_gc_count . ' orphan(s) cleaned (looking for quiz metadata only from quiz gallery uploads).';
			echo '<div class="clear"></div>';
		}
		foreach ($this->sections as $section) {
			$this->output_section_form($section);
		}
		unset($section);
	}

	/**
	 * Outputs a section form
	 * @param string $section the section id
	 */
	function output_section_form($section) {
		?><div id="qm-setsec-<?php echo $section['id']; ?>" class="qm-setsec">
			<form method="POST" action="options.php"><?php
				settings_fields($section['id']);
				do_settings_sections($section['id']);
				submit_button();
			?></form>
		</div><?php
	}

	function section_callback($arg) {
		if (isset($this->sections[$arg['id']]['desc'])) {
			?><?php echo $this->sections[$arg['id']]['desc'];?><?php
		}
	}
}

$quizmeister_settings = new QuizMeister_Settings();
