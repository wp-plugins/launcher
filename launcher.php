<?php
/*
Plugin Name: Launcher
Plugin URI: http://mythemeshop.com/plugins/launcher/
Description: Launching a new product, service or website? Launcher is the perfect plugin for you, with a stunning, customizable design, advanced timer & powerful features for running your campaign.
Author: MyThemeShop
Version: 1.0.2
Author URI: http://mythemeshop.com/
*/

if (!defined('ABSPATH')) die();

include 'functions.php';
require_once('includes/contact-form.php');

define( 'WPLAUNCHER_PATH', trailingslashit( WP_PLUGIN_DIR .'/'. plugin_basename( dirname(__FILE__) ) ) );
define( 'WPLAUNCHER_URI', plugin_dir_url( __FILE__ ) );

class WP_Launcher {
	public $plugin_slug = 'launcher';

	private $settings;
	private $editable_fields_count = 0;
	private $sections_count = 0;
	private $active_sections = array();
	public $template_paths = array();
	private $default_template_headers = array();

	public $template_elements = array();

	public $current_template_directory = '';
	public $current_template_directory_uri = '';
	public $current_template_info = '';

	public function __construct() {
		$default_settings = array(
			'enabled' => 0,
			'page_title' => get_bloginfo( 'title' ),
			'noindex' => 0,
			'contact_phone' => '',
			'contact_email' => '',
			'meta_description' => get_bloginfo( 'description' ),
			'favicon' => '',
			//'user_access' => 'administrator',
			'template' => 'default/default.php',
			'countdown' => array(
				'date' => '', // '1577836801',
				'date_formatted' => '', // '01/01/2020 12:00:01',
				'schedule_disable' => 0
			),
			'twitter' => array(
				'username' => '',
				'api_key' => '',
				'api_secret' => '',
				'access_token' => '',
				'access_secret' => '',
				'access_token_secret' => '',
				'cache_time' => '24',
			),
			'subscribe' => array(
				'service' => '',
				'include_name' => 0,
				'include_last_name' => 0,
				'submit_label' => __('Sign Up', $this->plugin_slug),
				'name_label' => __('First Name', $this->plugin_slug),
				'last_name_label' => __('Last Name', $this->plugin_slug),
				'email_label' => __('Email', $this->plugin_slug),
				'success_message' => __('Thanks for signing up!', $this->plugin_slug),
				'feedburner' => array(
					'username' => ''
				),
				'mailchimp' => array(
					'api_key' => '',
					'list' => ''
				),
				'aweber' => array(
					'code' => '',
					'list' => ''
				),
				'getresponse' => array(
					'api_key' => '',
					'campaign' => ''
				),
				'campaignmonitor' => array(
					'api_key' => '',
					'client' => '',
					'list' => ''
				),
				'madmimi' => array(
					'username' => '',
					'api_key' => '',
					'list' => ''
				)
			),
			'contact' => array(
				'label_name' => __('Name', $this->plugin_slug),
				'label_email' => __('Email', $this->plugin_slug),
				'label_message' => __('Message', $this->plugin_slug),
				'label_submit' => __('Send Message', $this->plugin_slug),
				'sendto' => get_option( 'admin_email' ),
			),
			'social' => array(
				'facebook' => '',
				'twitter' => '',
				'instagram' => '',
				'youtube' => '',
				'linkedin' => '',
				'googleplus' => '',
				'rss' => ''
			),
			'header_code' => '',
			'custom_css' => '',
			'footer_code' => '',
		);
		$this->settings = wp_parse_args( get_option( 'wplauncher_options' ), $default_settings );

		$this->default_template_headers = array(
			'Name' => 'Launcher Template',
			'TemplateURI' => 'Template URI',
			'Version' => 'Version',
			'Description' => 'Description',
			'Author' => 'Author',
			'AuthorURI' => 'Author URI',
			'Supports' => 'Supports'
		);

		$this->template_elements = array('countdown', 'subscribe', 'twitter', 'contact', 'social');


		// We look for templates in:
		// 1. plugins_dir/templates
		// 2. theme_dir/wplauncher
		// 3. childtheme_dir/wplauncher
		// 4... Use filter to add more
		$default_paths = array(
			WPLAUNCHER_PATH.'templates', 
			get_template_directory().'/launcher',
			get_stylesheet_directory().'/launcher',
		);
		$this->template_paths = apply_filters( 'wplauncher_template_paths', $default_paths );
		
		add_action('init', array( $this, 'check_countdown_disable' ) );
		if (is_admin()) {
			// Admin side stuff
			add_action('admin_init', array( $this, 'register_settings' ) );
			add_action('admin_menu', array( $this, 'admin_menu' ) );
			add_action('admin_enqueue_scripts', array( $this, 'admin_scripts'));
		} else {
			// Frontend-only stuff
			add_filter( 'template_include', array( $this, 'launch' ) );
			add_action('wp_enqueue_scripts', array( $this, 'frontend_scripts')); // this won't run on launcher/preview/edit!
		}

		// Textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// AJAX Editor Save
		add_action( 'wp_ajax_wplauncher_save_template_data', array($this, 'ajax_save_template_data') );

		// Add links and status to admin bar
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 2000 );

		if ($this->is_editor() && !empty($_GET['wplauncher_reset_template'])) {
			$current_template = $this->settings['template'];
			$template_settings = get_option( 'wplauncher_template_data' );
			if ( is_array( $template_settings ) && !empty( $template_settings[$current_template] ) ) {
				$template_settings[$current_template] = array();
				update_option( 'wplauncher_template_data', $template_settings );
				wp_redirect( remove_query_arg( 'wplauncher_reset_template' ) ); // make sure user doesn't refresh it accidentally
				die();
			}
		}

		// Subscribe stuff
		add_action( 'wp_ajax_wplauncher_ajax_subscribe', array($this, 'ajax_subscribe') );
		add_action( 'wp_ajax_nopriv_wplauncher_ajax_subscribe', array($this, 'ajax_subscribe') );
		
		add_action( 'wp_ajax_wplauncher_get_mailchimp_lists', array($this, 'get_mailchimp_lists') );
		add_action( 'wp_ajax_wplauncher_get_aweber_lists', array($this, 'get_aweber_lists') );
		add_action( 'wp_ajax_wplauncher_get_getresponse_lists', array($this, 'get_getresponse_lists') );
		add_action( 'wp_ajax_wplauncher_get_campaignmonitor_lists', array($this, 'get_campaignmonitor_lists') );
		add_action( 'wp_ajax_wplauncher_update_campaignmonitor_lists', array($this, 'update_campaignmonitor_lists') );
		add_action( 'wp_ajax_wplauncher_get_madmimi_lists', array($this, 'get_madmimi_lists') );
	}

	function load_textdomain() {
		load_plugin_textdomain( $this->plugin_slug, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
	}

	public function admin_bar_menu() {
		if (!$this->user_can_access_site() || !current_user_can('manage_options')) return;
		global $wp_admin_bar;
		
		$menu_id = 'wplauncher';
		$wp_admin_bar->add_menu(array('id' => $menu_id, 'title' => '<i class="wplauncher-icon-rocket"></i> '.__('Launcher'), 'href' => admin_url( 'options-general.php?page=launcher' )));
		
		
		if (!$this->is_editor())
			$wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __('Edit'), 'id' => 'wplauncher-edit-template', 'href' => add_query_arg('wplauncher_edit_template', '1', get_bloginfo( 'url' ))));
		
		$wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __('Preview'), 'id' => 'wplauncher-preview-template', 'href' => add_query_arg('wplauncher_preview_template', '1', get_bloginfo( 'url' )), 'meta' => array('target' => '_blank') ));
		
		if ($this->is_editor())
			$wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __('Reset Template'), 'id' => 'wplauncher-reset-template', 'href' => add_query_arg(array('wplauncher_edit_template' => '1', 'wplauncher_reset_template' => '1'), get_bloginfo( 'url' ))));

		$wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __('Settings'), 'id' => 'wplauncher-settings', 'href' => admin_url( 'options-general.php?page=launcher' )));
		if ($this->is_editor())
			$wp_admin_bar->add_menu(array('id' => 'wplauncher-edit-help', 'title' => ''.__('Click on any element to start editing'), 'href' => '#'));
	
		if ($this->settings['enabled']) {
			$wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __('Launcher page is <strong>enabled</strong>'), 'id' => 'wplauncher-enabled-help', 'href' => '#'));
			//$wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __('Disable Launcher Mode'), 'id' => 'wplauncher-enable', 'href' => '#'));
		} else {
			$wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __('Launcher page is <strong>disabled</strong>'), 'id' => 'wplauncher-disabled-help', 'href' => '#'));
			//$wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __('Enable Launcher Mode'), 'id' => 'wplauncher-enable', 'href' => '#'));
		}
		
	}

	public function locate_template( $template_name, $return_full_path = true ) {
		$paths = array_reverse($this->template_paths);
		$located = '';
		$path_partial = '';
		foreach ($paths as $path) {
			if (file_exists($full_path = trailingslashit($path).$template_name)) {
				$located = $full_path;
				$path_partial = $path;
				break;
			}
		}
		return ($return_full_path ? $located : $path_partial);
	}

	public function get_current_template() {
		if (empty($this->current_template_info))
			$this->current_template_info = $this->get_template_info();

		return $this->current_template_info;
	}

	public function get_template_info( $template = false ) {
		if (!$template)
			$template = $this->settings['template'];

		$path = $this->locate_template($template);
		
		if (file_exists($path))
			return get_file_data( $path, $this->default_template_headers );
		else
			return array( $this->default_template_headers );
	}

	public function get_template_directory() {
		if (empty($this->template_directory))
			$this->current_template_directory = $this->locate_template( $this->settings['template'] );

		return dirname($this->current_template_directory);
	}

	public function get_template_directory_uri() {
		// let's hope this will work in most cases
		return get_bloginfo( 'url' ).'/'.str_replace(ABSPATH, '', $this->get_template_directory());
	}

	public function get_templates_list() {
		$paths = $this->template_paths;
		$templates = array();
		
		foreach ($paths as $path) {
			$path = trailingslashit( $path );
			// Look for files containing our header 'Launcher template'
			$files = (array) self::scandir( $path, 'php', 2 );
			foreach ( $files as $file => $full_path ) {//echo ' <br> '.$file.' - '.$full_path;
				if ( ! $full_path || ! preg_match( '|Launcher Template:(.*)$|mi', file_get_contents( $full_path ), $header ) )
					continue;
				if (file_exists($full_path))
					$templates[ $file ] = get_file_data( $full_path, $this->default_template_headers );
				else
					$templates[ $file ] = $this->default_template_headers;

				$templates[ $file ]['path'] = $path;
			}
		}
		return $templates;
	}

	// get list of active (set up & visible) predefined elements
	// countdown, subscribe, twitter, contact, social
	public function get_template_active_elements( $template = null ) {
		if (!$template)
			$template = $this->settings['template'];

		$template_settings = get_option( 'wplauncher_template_data' );
		
		if (empty($template_settings[$template]) || !is_array($template_settings[$template])) 
			$fields = array();
		else
			$fields = $template_settings[$template];
		
		$output = array();
		
		foreach ($this->template_elements as $elem) {
			if (!$this->template_supports($elem, $template))
				continue; // template doesn't support it
			if (!empty($fields[$elem.'_hidden'])) 
				continue; // hidden element = not active
			
			// let's assume it's okay now
			$output[] = $elem;
		}
		return $output;
	}

	/**
	 * Scans a directory for files of a certain extension.
	 *
	 * @since 1.0
	 *
	 * @static
	 * @access private
	 *
	 * @param string            $path          Absolute path to search.
	 * @param array|string|null $extensions    Optional. Array of extensions to find, string of a single extension,
	 *                                         or null for all extensions. Default null.
	 * @param int               $depth         Optional. How many levels deep to search for files. Accepts 0, 1+, or
	 *                                         -1 (infinite depth). Default 0.
	 * @param string            $relative_path Optional. The basename of the absolute path. Used to control the
	 *                                         returned path for the found files, particularly when this function
	 *                                         recurses to lower depths. Default empty.
	 * @return array|false Array of files, keyed by the path to the file relative to the `$path` directory prepended
	 *                     with `$relative_path`, with the values being absolute paths. False otherwise.
	 */
	private static function scandir( $path, $extensions = null, $depth = 0, $relative_path = '' ) {
		if ( ! is_dir( $path ) )
			return false;
		if ( $extensions ) {
			$extensions = (array) $extensions;
			$_extensions = implode( '|', $extensions );
		}
		$relative_path = trailingslashit( $relative_path );
		if ( '/' == $relative_path )
			$relative_path = '';
		$results = scandir( $path );
		$files = array();
		foreach ( $results as $result ) {
			if ( '.' == $result[0] )
				continue;
			if ( is_dir( $path . '/' . $result ) ) {
				if ( ! $depth || 'CVS' == $result )
					continue;
				$found = self::scandir( $path . '/' . $result, $extensions, $depth - 1 , $relative_path . $result );
				$files = array_merge_recursive( $files, $found );
			} elseif ( ! $extensions || preg_match( '~\.(' . $_extensions . ')$~', $result ) ) {
				$files[ $relative_path . $result ] = $path . '/' . $result;
			}
		}
		return $files;
	}

	function admin_menu() {
	    add_options_page(__('Launcher', $this->plugin_slug), __('Launcher', $this->plugin_slug), 'manage_options', 'launcher', array( $this, 'settings_page' ) );
	}
	function admin_scripts() {
		wp_enqueue_style( 'wplauncher-fontello', WPLAUNCHER_URI.'css/fontello.css' );
		wp_enqueue_style( 'wplauncher-admin', WPLAUNCHER_URI.'css/wplauncher-admin.css' );
		if (get_current_screen()->id == 'settings_page_launcher') {
			add_thickbox();
			wp_enqueue_media();

			wp_enqueue_style( 'jquery-ui-lightness', WPLAUNCHER_URI.'css/jquery-ui-lightness.css' );
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_script( 'jquery-timepicker', WPLAUNCHER_URI.'js/jquery.timepicker.min.js', array('jquery-ui-datepicker') );
			wp_enqueue_script( 'wplauncher-admin', WPLAUNCHER_URI.'js/wplauncher-admin.js' );
		}
	}
	function frontend_scripts() {
		if ($this->user_can_access_site()) { // stuff for the admin bar on frontend
			wp_enqueue_style( 'wplauncher-fontello', WPLAUNCHER_URI.'css/fontello.css' );
			wp_enqueue_style( 'wplauncher-admin', WPLAUNCHER_URI.'css/wplauncher-admin.css' );
		}
	}
	function register_settings() {
	    register_setting('wplauncher-settings-group', 'wplauncher_options');
	}
	function settings_page() {
	    if (!current_user_can('manage_options')) {
	        wp_die(__('You do not have sufficient permissions to access this page.', $this->plugin_slug));
	    }
	    ?>
	    <div class="wrap">
	    	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	    	<form method="post" action="options.php">
                <?php settings_fields('wplauncher-settings-group'); ?>

                <h3 class="nav-tab-wrapper wplauncher-nav-tab-wrapper">
			    	<a href="#" class="nav-tab nav-tab-active" id="wplauncher-tab-general" data-rel="#wplauncher-settings-general"><?php _e('General', $this->plugin_slug); ?></a> 
			    	<a href="#" class="nav-tab" id="wplauncher-tab-templates" data-rel="#wplauncher-settings-templates"><?php _e('Templates', $this->plugin_slug); ?></a> 
			    	<a href="#" class="nav-tab<?php echo (!$this->template_supports( 'countdown' ) ? ' wplauncher-not-supported' : ''); ?>" id="wplauncher-tab-countdown" data-rel="#wplauncher-settings-countdown"><?php _e('Countdown Timer', $this->plugin_slug); ?></a> 
			    	<a href="#" class="nav-tab<?php echo (!$this->template_supports( 'subscribe' ) ? ' wplauncher-not-supported' : ''); ?>" id="wplauncher-tab-subscribe" data-rel="#wplauncher-settings-subscribe"><?php _e('Subscribe Form', $this->plugin_slug); ?></a> 
			    	<a href="#" class="nav-tab<?php echo (!$this->template_supports( 'twitter' ) ? ' wplauncher-not-supported' : ''); ?>" id="wplauncher-tab-twitter" data-rel="#wplauncher-settings-twitter"><?php _e('Twitter Feed', $this->plugin_slug); ?></a>
			    	<a href="#" class="nav-tab<?php echo (!$this->template_supports( 'contact' ) ? ' wplauncher-not-supported' : ''); ?>" id="wplauncher-tab-contact" data-rel="#wplauncher-settings-contact"><?php _e('Contact Form', $this->plugin_slug); ?></a>
			    	<a href="#" class="nav-tab<?php echo (!$this->template_supports( 'social' ) ? ' wplauncher-not-supported' : ''); ?>" id="wplauncher-tab-social" data-rel="#wplauncher-settings-social"><?php _e('Social Links', $this->plugin_slug); ?></a>
	    		</h3>
	    		<div class="wplauncher-nav-tab-contents">
	    			<div id="wplauncher-settings-general">
	    				<table class="form-table">
		                    <?php $this->options_field_checkbox('wplauncher_options[enabled]', __('Enable Launcher Page', $this->plugin_slug), __('If enabled, only administrators can access the frontend of the site.', $this->plugin_slug));

		                    $this->options_field_text('wplauncher_options[page_title]', __('Page Title', $this->plugin_slug));
		                    
		                    $this->options_field_text('wplauncher_options[favicon]', __('Favicon', $this->plugin_slug), '', array('class' => 'large-text'));
							?>
							<script type="text/javascript">
							jQuery(document).ready(function($){
								$('<input type="button" name="upload-btn" id="upload-btn" class="button-secondary" value="Upload Image">')
									.insertAfter('#wplauncher_options-favicon');
							    $('#upload-btn').click(function(e) {
							        e.preventDefault();
							        var image = wp.media({ 
							            title: 'Upload Image',
							            // mutiple: true if you want to upload multiple files at once
							            multiple: false
							        }).open()
							        .on('select', function(e){
							            // This will return the selected image from the Media Uploader, the result is an object
							            var uploaded_image = image.state().get('selection').first();
							            // We convert uploaded_image to a JSON object to make accessing it easier
							            // Output to the console uploaded_image
							            //console.log(uploaded_image);
							            var image_url = uploaded_image.toJSON().url;
							            // Let's assign the url value to the input field
							            $('#wplauncher_options-favicon').val(image_url);
							        });
							    });
							});
							</script>
		                    <?php
		                    $this->options_field_text('wplauncher_options[meta_description]', __('Meta Description', $this->plugin_slug), __('Leave this field empty to omit meta description tag.', $this->plugin_slug), array('class' => 'large-text'));
		                    $this->options_field_checkbox('wplauncher_options[noindex]', __('<em>Noindex</em> Meta', $this->plugin_slug), __('If enabled, search engines won\'t index the launcher page, regardless of your robots.txt settings.', $this->plugin_slug));
		                    
		                    // select user roles
		                    /*$roles = array();
		                    foreach (get_editable_roles() as $role_name => $role_info) {
		                    	$roles[$role_name] = $role_name;
		                    }
		                    $this->options_field_select('wplauncher_options[admin_role]', $roles, __('Access Role', $this->plugin_slug), __('Select user roles who will see the real site instead of the launcher page.', $this->plugin_slug), array('multiple' => 'multiple'));*/
		                    
		                    //$this->options_field_select('wplauncher_options[user_access]', array('administrator' => __('Administrator', $this->plugin_slug), 'loggedin' => __('All logged-in users', $this->plugin_slug)), __('Access Role', $this->plugin_slug), __('Choose whether only administrators will see the real site instead of the launcher page, or any logged in user as well.', $this->plugin_slug));

		                    $this->options_field_textarea('wplauncher_options[custom_css]', __('Custom CSS Code', $this->plugin_slug), __('Additional CSS code to adjust styling.', $this->plugin_slug), array('class' => 'large-text'));
		                    $this->options_field_textarea('wplauncher_options[header_code]', __('Header Code', $this->plugin_slug), __('Additional code inserted in the <code>&lt;head&gt;</code> area of the page.', $this->plugin_slug), array('class' => 'large-text'));
		                    $this->options_field_textarea('wplauncher_options[footer_code]', __('Footer Code', $this->plugin_slug), __('Additional code inserted before the closing <code>&lt;/body&gt;</code> tag.', $this->plugin_slug), array('class' => 'large-text'));

		                     ?>
		                </table>
	    			</div>

	    			<div id="wplauncher-settings-templates" style="display: none;">
	    				<p><?php echo sprintf(__('You can access the <a href="%s">template editor</a> from the admin bar at the top.', $this->plugin_slug), add_query_arg('wplauncher_edit_template', '1', get_bloginfo( 'url' ))); ?></p>
	    				<?php $this->options_field_template('wplauncher_options[template]'); ?>
	    			</div>

	    			<div id="wplauncher-settings-countdown" style="display: none;">
	    				<div class="wplauncher-error"<?php echo ($this->template_supports( 'countdown' ) ? ' style="display: none"' : ''); ?>>
	    					<p><?php _e('The currently selected template does not declare support for the Countdown Timer. It is possible that this element won\'t show up on the launcher page.', $this->plugin_slug); ?></p>
	    				</div>
	    				
	    				<table class="form-table">
	    					<?php
		                    $this->options_field_text('wplauncher_options[countdown][date_formatted]', __('Countdown To', $this->plugin_slug));
		                    $this->options_field_checkbox('wplauncher_options[countdown][schedule_disable]', __('Disable Launcher Page', $this->plugin_slug), __('Disables launcher mode at the specified date/time (if the countdown timer is present on your launcher page).', $this->plugin_slug));
		                	?>
		                </table>
		                <input type="hidden" name="wplauncher_options[countdown][date]" id="wplauncher_options-countdown-date" value="<?php echo $this->settings['countdown']['date']; ?>">
	    			</div>

	    			<div id="wplauncher-settings-subscribe" style="display: none;">
	    				<div class="wplauncher-error"<?php echo ($this->template_supports( 'subscribe' ) ? ' style="display: none"' : ''); ?>>
	    					<p><?php _e('The currently selected template does not declare support for the Subscribe Form. It is possible that this element won\'t show up on the launcher page.', $this->plugin_slug); ?></p>
	    				</div>

	    				<table class="form-table">
		                    <?php
		                    	$this->options_field_select('wplauncher_options[subscribe][service]', array(
		                    		'feedburner' => __( 'Feedburner', $this->plugin_slug ),
									'mailchimp' => __( 'MailChimp', $this->plugin_slug ),
									'aweber' => __( 'Aweber', $this->plugin_slug ),
									'getresponse' => __( 'Get Response', $this->plugin_slug ),
									'campaignmonitor' => __( 'Campaign Monitor', $this->plugin_slug ),
									'madmimi' => __( 'Mad Mimi', $this->plugin_slug ),
								), __('Service', $this->plugin_slug));

		                    	// Mailchimp
		                    	$this->options_field_text('wplauncher_options[subscribe][mailchimp][api_key]', __('Mailchimp <a href="http://kb.mailchimp.com/accounts/management/about-api-keys" target="_blank">API key</a>', $this->plugin_slug), '', array(), 'show-if-subscribe if-mailchimp');
		                    	$this->options_field_select('wplauncher_options[subscribe][mailchimp][list]', array(), __('List', $this->plugin_slug), '', array(), 'show-if-subscribe if-mailchimp');
		                    
		                    	// Aweber
		                    	?>
		                    	<tr class="show-if-subscribe if-aweber">
						            <th><?php _e('Connect with AWeber', $this->plugin_slug) ?></th>
						            <td>
						            	<a href="https://auth.aweber.com/1.0/oauth/authorize_app/a3f7ff6f" target="_blank" class="button wplauncher-aweber-connect"><?php isset($this->settings['subscribe']['aweber']['access_key']) && $this->settings['subscribe']['aweber']['access_key'] == '' ? _e( 'Get Authorization Code', $this->plugin_slug ) : _e( 'Reconnect Account', $this->plugin_slug ); ?></a>
										<input type="hidden" id="wplauncher_options-subscribe-aweber-consumer_key" name="wplauncher_options[subscribe][aweber][consumer_key]" value="<?php echo (isset($this->settings['subscribe']['aweber']['consumer_key']) ? $this->settings['subscribe']['aweber']['consumer_key'] :''); ?>" />
										<input type="hidden" id="wplauncher_options-subscribe-aweber-consumer_secret" name="wplauncher_options[subscribe][aweber][consumer_secret]" value="<?php echo (isset($this->settings['subscribe']['aweber']['consumer_secret']) ? $this->settings['subscribe']['aweber']['consumer_secret'] :''); ?>" />
										<input type="hidden" id="wplauncher_options-subscribe-aweber-access_key" name="wplauncher_options[subscribe][aweber][access_key]" value="<?php echo (isset($this->settings['subscribe']['aweber']['access_key']) ? $this->settings['subscribe']['aweber']['access_key'] :''); ?>" />
										<input type="hidden" id="wplauncher_options-subscribe-aweber-access_secret" name="wplauncher_options[subscribe][aweber][access_secret]" value="<?php echo (isset($this->settings['subscribe']['aweber']['access_secret']) ? $this->settings['subscribe']['aweber']['access_secret'] :''); ?>" />
						           	</td>
						        </tr>
								<?php
		                    	$this->options_field_text('wplauncher_options[subscribe][aweber][code]', __('Paste authorization code', $this->plugin_slug), '', array(), 'show-if-subscribe if-aweber');
		                    	$this->options_field_select('wplauncher_options[subscribe][aweber][list]', array(), __('List', $this->plugin_slug), '', array(), 'show-if-subscribe if-aweber');

		                    	// GetResponse
		                    	$this->options_field_text('wplauncher_options[subscribe][getresponse][api_key]', __('GetResponse <a href="http://www.getresponse.com/learning-center/glossary/api-key.html" target="_blank">API key</a>', $this->plugin_slug), '', array(), 'show-if-subscribe if-getresponse');
		                    	$this->options_field_select('wplauncher_options[subscribe][getresponse][campaign]', array(), __('Campaign', $this->plugin_slug), '', array(), 'show-if-subscribe if-getresponse');

		                    	// Campaign Monitor
		                    	$this->options_field_text('wplauncher_options[subscribe][campaignmonitor][api_key]', __('Campaign Monitor <a href="http://help.campaignmonitor.com/topic.aspx?t=206" target="_blank">API key</a>', $this->plugin_slug), '', array(), 'show-if-subscribe if-campaignmonitor');
		                    	$this->options_field_select('wplauncher_options[subscribe][campaignmonitor][client]', array(), __('Client', $this->plugin_slug), '', array(), 'show-if-subscribe if-campaignmonitor');
		                    	$this->options_field_select('wplauncher_options[subscribe][campaignmonitor][list]', array(), __('List', $this->plugin_slug), '', array(), 'show-if-subscribe if-campaignmonitor');

		                    	// Mad Mimi
		                    	$this->options_field_text('wplauncher_options[subscribe][madmimi][username]', __('Mad Mimi username/email', $this->plugin_slug), '', array(), 'show-if-subscribe if-madmimi');
		                    	$this->options_field_text('wplauncher_options[subscribe][madmimi][api_key]', __('Mad Mimi <a href="http://help.madmimi.com/where-can-i-find-my-api-key/" target="_blank">API key</a>', $this->plugin_slug), '', array(), 'show-if-subscribe if-madmimi');
		                    	$this->options_field_select('wplauncher_options[subscribe][madmimi][list]', array(), __('List', $this->plugin_slug), '', array(), 'show-if-subscribe if-madmimi');

		                    	// Feedburner
		                    	$this->options_field_text('wplauncher_options[subscribe][feedburner][username]', __('Feedburner username', $this->plugin_slug), '', array(), 'show-if-subscribe if-feedburner');
		                    	

		                    	$this->options_field_checkbox('wplauncher_options[subscribe][include_name]', __('Include name field', $this->plugin_slug), '', array(), 'show-if-subscribe if-mailchimp if-aweber if-getresponse if-campaignmonitor if-madmimi');
		                    	$this->options_field_checkbox('wplauncher_options[subscribe][include_last_name]', __('Include last name field', $this->plugin_slug), '', array(), 'show-if-subscribe if-mailchimp if-aweber if-getresponse if-campaignmonitor if-madmimi');

		                    	$this->options_field_text('wplauncher_options[subscribe][name_label]', __('Name field label', $this->plugin_slug));
		                    	$this->options_field_text('wplauncher_options[subscribe][last_name_label]', __('Last name field label', $this->plugin_slug));
		                    	$this->options_field_text('wplauncher_options[subscribe][email_label]', __('Email field label', $this->plugin_slug));
		                    	$this->options_field_text('wplauncher_options[subscribe][submit_label]', __('Submit button label', $this->plugin_slug));
		                    	$this->options_field_text('wplauncher_options[subscribe][success_message]', __('Success message', $this->plugin_slug));
		                    ?>
		                </table>
	    			</div>

	    			<div id="wplauncher-settings-twitter" style="display: none;">
	    				<div class="wplauncher-error"<?php echo ($this->template_supports( 'twitter' ) ? ' style="display: none"' : ''); ?>>
	    					<p><?php _e('The currently selected template does not declare support for the Twitter Feed. It is possible that this element won\'t show up on the launcher page.', $this->plugin_slug); ?></p>
	    				</div>

	    				<table class="form-table">
	    					<p><?php _e('You can generate your API Key from here:', $this->plugin_slug); ?> <a href="https://apps.twitter.com/">https://apps.twitter.com/</a></p>
	    					<?php 
		                    $this->options_field_text('wplauncher_options[twitter][username]', __('Twitter Username', $this->plugin_slug));
		                    $this->options_field_text('wplauncher_options[twitter][api_key]', __('API Key', $this->plugin_slug));
		                    $this->options_field_text('wplauncher_options[twitter][api_secret]', __('API Secret', $this->plugin_slug));
		                    $this->options_field_text('wplauncher_options[twitter][access_token]', __('Access Token', $this->plugin_slug));
		                    $this->options_field_text('wplauncher_options[twitter][access_token_secret]', __('Access Token Secret', $this->plugin_slug));
		                    $this->options_field_text('wplauncher_options[twitter][cache_time]', __('Tweets Caching Time', $this->plugin_slug), __('Refresh tweets every X hours', $this->plugin_slug), array('type' => 'number', 'class' => 'small-text'));
		                	?>
		                </table>
	    			</div>

	    			<div id="wplauncher-settings-contact" style="display: none;">
	    				<div class="wplauncher-error"<?php echo ($this->template_supports( 'contact' ) ? ' style="display: none"' : ''); ?>>
	    					<p><?php _e('The currently selected template does not declare support for the Contact Form. It is possible that this element won\'t show up on the launcher page.', $this->plugin_slug); ?></p>
	    				</div>

	    				<table class="form-table">
	    					<?php 
		                    $this->options_field_text('wplauncher_options[contact][sendto]', __('Send emails to', $this->plugin_slug), '', array('class' => 'large-text'));
		                    $this->options_field_text('wplauncher_options[contact][label_name]', __('Name field label', $this->plugin_slug), '', array('class' => 'large-text'));
		                    $this->options_field_text('wplauncher_options[contact][label_email]', __('Email field label', $this->plugin_slug), '', array('class' => 'large-text'));
		                    $this->options_field_text('wplauncher_options[contact][label_message]', __('Message field label', $this->plugin_slug), '', array('class' => 'large-text'));
		                    $this->options_field_text('wplauncher_options[contact][label_submit]', __('Submit button label', $this->plugin_slug), '', array('class' => 'large-text'));
		                	?>
		                </table>
	    			</div>

	    			<div id="wplauncher-settings-social" style="display: none;">
	    				<div class="wplauncher-error"<?php echo ($this->template_supports( 'social' ) ? ' style="display: none"' : ''); ?>>
	    					<p><?php _e('The currently selected template does not declare support for the Social Links. It is possible that this element won\'t show up on the launcher page.', $this->plugin_slug); ?></p>
	    				</div>

	    				<table class="form-table">
		                    <?php
		                    $this->options_field_text('wplauncher_options[social][facebook]', __('Facebook Page URL', $this->plugin_slug), '', array('class' => 'large-text'));
		                    $this->options_field_text('wplauncher_options[social][twitter]', __('Twitter Page URL', $this->plugin_slug), '', array('class' => 'large-text'));
		                    $this->options_field_text('wplauncher_options[social][instagram]', __('Instagram Page URL', $this->plugin_slug), '', array('class' => 'large-text'));
		                    $this->options_field_text('wplauncher_options[social][youtube]', __('Youtube Page URL', $this->plugin_slug), '', array('class' => 'large-text'));
		                    $this->options_field_text('wplauncher_options[social][linkedin]', __('LinkedIn Page URL', $this->plugin_slug), '', array('class' => 'large-text'));
		                    $this->options_field_text('wplauncher_options[social][googleplus]', __('Google+ Page URL', $this->plugin_slug), '', array('class' => 'large-text'));
		                    $this->options_field_text('wplauncher_options[social][rss]', __('RSS URL', $this->plugin_slug), '', array('class' => 'large-text'));
		                    ?>
		                </table>
	    			</div>
	    		</div>
                
                <?php submit_button(); ?>
            </form>

	    </div>
	    <?php
	}

	public function launch( $template ) {
		if ($this->user_can_access_site() && empty($_GET['wplauncher_edit_template']) && empty($_GET['wplauncher_preview_template'])) {
			return $template;
		}
		if (($this->settings['enabled'] 
			|| (!empty($_GET['wplauncher_edit_template']) 
				&& $this->user_can_access_site())
			|| (!empty($_GET['wplauncher_preview_template'])) 
				&& $this->user_can_access_site())
				&& $this->locate_template( $this->settings['template'] ) ) { // yes this looks weird
			$template = $this->locate_template( $this->settings['template'] );
		}
		return $template;
	}

	public function init_editable( $type, $args, $default = array() ) {
		$this->editable_fields_count++;

		// default single string param
		if (is_string($args) && stripos($args, '=') === false) {
			switch ($type) {
				case 'hide':
					$args = array('hidden' => $args);
				break;
				case 'section':
					$args = array('hidden' => $args);
				break;
				default:
					$args = array('default' => $args);
				break;
			}
		}
		
		$parsed = wp_parse_args( $args, array_merge( array('id' => '', 'default' => ''), $default ) );
		if ( empty( $parsed['id'] ) ) {
			$parsed['id'] = 'field_'.$this->editable_fields_count.'_'.substr(md5($parsed['default']), 0, 4).'_'.$type;				
		}
		if ($type == 'image' && empty($parsed['default'])) {
			$parsed['default'] = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEsAAABLCAYAAAA4TnrqAAAACXBIWXMAAAsTAAALEwEAmpwYAAABNmlDQ1BQaG90b3Nob3AgSUNDIHByb2ZpbGUAAHjarY6xSsNQFEDPi6LiUCsEcXB4kygotupgxqQtRRCs1SHJ1qShSmkSXl7VfoSjWwcXd7/AyVFwUPwC/0Bx6uAQIYODCJ7p3MPlcsGo2HWnYZRhEGvVbjrS9Xw5+8QMUwDQCbPUbrUOAOIkjvjB5ysC4HnTrjsN/sZ8mCoNTIDtbpSFICpA/0KnGsQYMIN+qkHcAaY6addAPAClXu4vQCnI/Q0oKdfzQXwAZs/1fDDmADPIfQUwdXSpAWpJOlJnvVMtq5ZlSbubBJE8HmU6GmRyPw4TlSaqo6MukP8HwGK+2G46cq1qWXvr/DOu58vc3o8QgFh6LFpBOFTn3yqMnd/n4sZ4GQ5vYXpStN0ruNmAheuirVahvAX34y/Axk/96FpPYgAAACBjSFJNAAB6JQAAgIMAAPn/AACA6AAAUggAARVYAAA6lwAAF2/XWh+QAAAAh0lEQVR42uzQMQEAMAgDsDH/ouoMLPDwJRJSSfqx8hXIkiVLlixZCmTJkiVLliwFsmTJkiVLlgJZsmTJkiVLgSxZsmTJkqVAlixZsmTJUiBLlixZsmQpkCVLlixZshTIkiVLlixZCmTJkiVLliwFsmTJkiVLlgJZsmTJkiVLgawTAwAA//8DAHhRA9W4iYyFAAAAAElFTkSuQmCC';	
		} 
		return $parsed;
	}

	public function editable_text( $args ) {
		$params = $this->init_editable( 'text', $args, array('edit_color' => 1, 'hideable' => 0, 'type' => 'text'));
		$field_id = $params['id'];
		$value = $this->get_editable_value( $field_id, $params['default'] );

		$classes = 'wplauncher-editable wplauncher-editable-text';
		$style_attr = '';
		$color_attrs = '';
		if (!empty($params['edit_color'])) {
			$classes .= ' wplauncher-editable-color';
			$color_params = array_merge($params, array('id' => $params['id'].'_color'));
			$color_params = $this->init_editable( 'color', $color_params, array('default_color' => '') );
			$color_value = $this->get_editable_value( $color_params['id'], $color_params['default_color'] );
			$color_attrs = ' data-colorid="'.$color_params['id'].'" data-color="'.$color_value.'"';
			if (!empty($color_value))
				$style_attr .= ' color: '.$color_value.';';
		}

		$hideable_attrs = '';
		if (!empty($params['hideable'])) {
			$classes .= ' wplauncher-hideable';
			$hideable_params = array_merge($params, array('id' => $params['id'].'_hide'));
			$hideable_params = $this->init_editable( 'hide', $hideable_params, array('hidden' => '') );
			$hideable_value = $this->get_editable_value( $hideable_params['id'], $hideable_params['hidden'] );
			$hideable_attrs = ' data-hideid="'.$hideable_params['id'].'" data-hidden="'.$hideable_value.'"';
			if (!empty($hideable_value)) {
				$classes .= ' wplauncher-hidden';	
				if ( ! $this->is_editor() ) {
					$style_attr .= ' display: none;';
				}			
			}
		}

		if ($params['type'] == 'textarea') $classes .= ' wplauncher-editable-textarea';
		echo '<span class="'.$classes.'" id="'.$field_id.'" style="'.$style_attr.'"'.$color_attrs.$hideable_attrs.'>';
		echo nl2br(str_replace(array("<br>\r", "<br>\n"), array("\r", "\n"), $value));
		echo '</span>';
	}

	public function editable_image( $args ) {
		$params = $this->init_editable( 'image', $args, array('hideable' => 0));
		$field_id = $params['id'];
		$value = $this->get_editable_value( $field_id, $params['default'] );

		$classes = 'wplauncher-editable wplauncher-editable-image';
		$style_attr = '';
		$extra_attrs = '';
		if ( !empty( $params['meta'] ) ) {

			// allow width:300|height:200 format
			if (is_string($params['meta'])) $params['meta'] = str_replace(array(':', '|'), array('=', '&'), $params['meta']);
			
			$meta = wp_parse_args( $params['meta'], array() );
			
			// class attribute gets appended
			if (!empty($meta['class'])) {
				$classes .= ' '.trim($meta['class']);
				unset($meta['class']);
			}
			
			unset($meta['id']); // don't do that
			
			foreach ($meta as $attr_k => $attr_v) {
				$extra_attrs .= ' '.$attr_k.'="'.trim($attr_v, '"').'"';
			}
		}

		$hideable_attrs = '';
		if (!empty($params['hideable'])) {
			$classes .= ' wplauncher-hideable';
			$hideable_params = array_merge($params, array('id' => $params['id'].'_hide'));
			$hideable_params = $this->init_editable( 'hide', $hideable_params, array('hidden' => '') );
			$hideable_value = $this->get_editable_value( $hideable_params['id'], $hideable_params['hidden'] );
			$hideable_attrs = ' data-hideid="'.$hideable_params['id'].'" data-hidden="'.$hideable_value.'"';
			if (!empty($hideable_value)) {
				$classes .= ' wplauncher-hidden';	
				if ( ! $this->is_editor() ) {
					$style_attr .= ' display: none;';
				}			
			}
		}
		echo '<img class="'.$classes.'" src="'.$value.'" id="'.$field_id.'" style="'.$style_attr.'"'.$hideable_attrs.$extra_attrs.'>';
	}

	public function editable_attr_background( $args ) {
		$params = $this->init_editable( 'background', $args );
		$field_id = $params['id'];
		$value = $this->get_editable_value( $field_id, $params['default'] );

		$classes = 'wplauncher-editable wplauncher-editable-background'.(!empty($params['class']) ? ' '.$params['class'] : '');
		$style_attr = '';
		$bg_attrs = ' data-backgroundid="'.$params['id'].'" data-background="'.$value.'"';
		if (!empty($value)) {
			$style_attr .= ' background-image: url('.$value.');';			
		}

		if ( !empty( $params['meta'] ) ) {

			// allow class:x|attr2:y format
			if (is_string($params['meta'])) $params['meta'] = str_replace(array(':', '|'), array('=', '&'), $params['meta']);
			
			$meta = wp_parse_args( $params['meta'], array() );
			
			// class attribute gets appended
			if (!empty($meta['class'])) {
				$classes .= ' '.trim($meta['class']);
				unset($meta['class']);
			}
			
			unset($meta['id']); // don't do that
			
			foreach ($meta as $attr_k => $attr_v) {
				$extra_attrs .= ' '.$attr_k.'="'.trim($attr_v, '"').'"';
			}
		}

		echo ' class="'.$classes.'" style="'.$style_attr.'"'.$bg_attrs;
	}

	public function editable_attr_color( $args ) {
		$params = $this->init_editable( 'color', $args );
		$field_id = $params['id'];
		$value = $this->get_editable_value( $field_id, $params['default'] );

		$classes = 'wplauncher-editable wplauncher-editable-color'.(!empty($params['class']) ? ' '.$params['class'] : '');
		$style_attr = '';
		$color_attrs = ' data-colorid="'.$params['id'].'" data-color="'.$value.'"';
		if (!empty($value)) {
			$style_attr .= ' color: '.$value.';';			
		}
		echo ' class="'.$classes.'" style="'.$style_attr.'"'.$color_attrs;
	}

	public function editable_attr_hideable( $args ) {
		$params = $this->init_editable( 'hide', $args, array('hidden' => 0));
		$field_id = $params['id'];
		$value = $this->get_editable_value( $field_id, $params['hidden'] );

		$classes = 'wplauncher-editable wplauncher-hideable'.(!empty($params['class']) ? ' '.$params['class'] : '');
		$style_attr = '';
		$hideable_attrs = ' data-hideid="'.$params['id'].'" data-hidden="'.$value.'"';
		if (!empty($value)) {
			$classes .= ' wplauncher-hidden';	
			if ( ! $this->is_editor() ) {
				$style_attr .= ' display: none;';
			}			
		}
		echo ' class="'.$classes.'" style="'.$style_attr.'"'.$hideable_attrs;
	}

	public function get_editable_value( $id, $default = '', $template = '' ) {
		if (!$template)
    		$template = $this->settings['template'];

		$template = $this->settings['template'];
		$template_settings = get_option( 'wplauncher_template_data' );
		$value = '';
		if ( is_array( $template_settings ) && isset( $template_settings[$template] ) && isset( $template_settings[$template][$id] ) ) {
			$value = $template_settings[$template][$id];
		} else {
			$value = $default;
		}

		return apply_filters( 'wplauncher_get_value', $value, $id, $default, $template );
	}

	// Checks if we're currently in frontend editing mode
	public function is_editor() {
		if ($this->user_can_access_site() && current_user_can('manage_options') && !is_admin() && !empty($_GET['wplauncher_edit_template'])) {
			return true;
		} else {
			return false;
		}
	}

	public function is_preview() {
		if ($this->user_can_access_site() && current_user_can('manage_options') && !is_admin() && !empty($_GET['wplauncher_preview_template'])) {
			return true;
		} else {
			return false;
		}
	}

	public function editable_section_start( $params ) {
		$params = $this->init_editable( 'section', $params, array( 'hidden' => 0 ));
		
		$section_id = $params['id'];
		$value = $this->get_editable_value( $section_id, $params['hidden'] );

		$classes = 'wplauncher-editable wplauncher-section '.$section_id;
		$style_attr1 = '';
		$style_attr2 = '';
		$hideable_attrs = ' data-sectionid="'.$section_id.'" data-hidden="'.$value.'"';
		if (!empty($value)) {
			$classes .= ' wplauncher-hidden-section';	
			//if ( $this->is_editor() ) {
				$style_attr2 .= ' display: none;';
			//}			
		}

		//if (!empty($params['edit_color'])) {
			//$classes .= ' wplauncher-editable-section-bg';
			$bg_params = array_merge($params, array('id' => $params['id'].'_bg'));
			$bg_params = $this->init_editable( 'color', $bg_params, array('default_bg' => '') );
			$bg_value = $this->get_editable_value( $bg_params['id'], $bg_params['default_bg'] );
			$bg_attrs = ' data-backgroundid="'.$bg_params['id'].'" data-background="'.$bg_value.'"';
			if (!empty($bg_value))
				$style_attr1 .= ' background-image: url('.$bg_value.');';
		//}

		if (!empty($params['class'])) {
			$classes .= ' '.trim($params['class']);
		}
		$attrs = ' class="'.$classes.'" style="'.$style_attr1.'"'.$hideable_attrs.$bg_attrs;

		$params['hidden'] = $value;
		$this->active_sections[$section_id] = $params;

		ob_start();
		echo '<div'.$attrs.'>';
		if ($this->is_editor()) {
			echo '<div class="wplauncher-section-editor-buttons">';
			echo '<a class="wplauncher-hideable-toggle" href="#"><i class="wplauncher-icon-eye'.($value ? '-off' : '').'"></i></a>';
			echo '<a class="wplauncher-bg-edit" href="#"><i class="wplauncher-icon-picture"></i></a>';

			echo '</div>';
			echo '<div class="wplauncher-section-editor-wrap" style="'.$style_attr2.'">';

		}
	}
	public function editable_section_end( $id = null ) {
		echo '</div>';
		if ($this->is_editor())
			echo '</div>';

		$section = ob_get_clean();
		if (!$id) {
			// get last started section
			$key = key( array_slice( $this->active_sections, -1, 1, TRUE ) );
		}
		// if section is not hidden, display it
		if ($this->is_editor() || (isset($this->active_sections[$key]) && empty($this->active_sections[$key]['hidden']))) {
			echo $section;
		}
		unset($this->active_sections[$key]);
	}

	public function template_footer() {
		// custom CSS
		if (!empty($this->settings['custom_css'])) {
			echo '<style type="text/css">'.$this->settings['custom_css'].'</style>';
		}

		// footer code
		echo $this->settings['footer_code'];
		
		if ( ! $this->is_editor() ) return; // below scripts only needed when in editor mode

		echo '<link rel="stylesheet" href="'.WPLAUNCHER_URI.'css/wplauncher-editor.css">';
		echo '<link rel="stylesheet" href="'.WPLAUNCHER_URI.'css/wplauncher-admin.css">'; // some admin bar css is in there

		// color picker
		echo '<script>var wpColorPickerL10n = {"clear":"'.__('Clear', $this->plugin_slug).'","defaultString":"Default","pick":"Select Color","current":"Current Color"};</script>';
		echo '<link rel="stylesheet" href="' . WPLAUNCHER_URI . 'css/color-picker.min.css' .'">';
		echo '<script src="' . WPLAUNCHER_URI . 'js/jquery.core.min.js' . '"></script>';
		echo '<script src="' . WPLAUNCHER_URI . 'js/jquery.widget.min.js' .'"></script>';
		echo '<script src="' . WPLAUNCHER_URI . 'js/jquery.mouse.min.js' .'"></script>';
		echo '<script src="' . WPLAUNCHER_URI . 'js/jquery.draggable.min.js' .'"></script>';
		echo '<script src="' . WPLAUNCHER_URI . 'js/jquery.slider.min.js' .'"></script>';
		echo '<script src="' . WPLAUNCHER_URI . 'js/iris.min.js' .'"></script>';
		echo '<script src="' . WPLAUNCHER_URI . 'js/color-picker.js' .'"></script>';

		// media uploader '/wp-admin/media-upload.php?type=image&post_id=1&TB_iframe=true&flash=0'
		echo '<script>var wplauncher_media_upload_url = "'.add_query_arg(array('type' => 'image', 'post_id' => 0, 'TB_iframe' => 'true', 'flash' => 0), admin_url( 'media-upload.php' )).'"</script>';
		echo '<script>var thickboxL10n = {"next":"Next >","prev":"< Prev","image":"Image","of":"of","close":"Close","noiframes":"This feature requires inline frames. You have iframes disabled or your browser does not support them.","loadingAnimation":""};</script>';
		echo '<script src="' . WPLAUNCHER_URI . 'js/underscore.min.js' .'"></script>';
		echo '<script src="' . WPLAUNCHER_URI . 'js/thickbox.js' .'"></script>';
		echo '<script src="' . WPLAUNCHER_URI . 'js/shortcode.min.js' .'"></script>';
		echo '<link rel="stylesheet" href="' . WPLAUNCHER_URI . 'css/thickbox.css' .'">';
		echo '<script src="' . WPLAUNCHER_URI . 'js/media-upload.js' .'"></script>';
		

		// "Replicate" the admin bar
		// Can be disabled using filter if needed
		if (apply_filters( 'wplauncher_show_admin_bar', true )) {
			?><link rel="stylesheet" id="open-sans-css"  href="//fonts.googleapis.com/css?family=Open+Sans%3A300italic%2C400italic%2C600italic%2C300%2C400%2C600&#038;subset=latin%2Clatin-ext&#038;ver=4.1.5" type="text/css" media="all" />
			<link rel="stylesheet" href="<?php echo includes_url( 'css/dashicons.min.css' ); ?>">
			<link rel="stylesheet" href="<?php echo includes_url( 'css/admin-bar.min.css' ); ?>">
			<script type="text/javascript" src="<?php echo includes_url( 'js/admin-bar.min.js' ); ?>"></script>
			<?php wp_admin_bar_header(); _admin_bar_bump_cb(); _wp_admin_bar_init(); wp_admin_bar_render();
		}

		echo '<script src="'.WPLAUNCHER_URI.'js/wplauncher-editor.js"></script>';

		do_action( 'wplauncher_after_footer' );
	}
	public function template_head() {
		$output = '';

		$output .= '<title>'.$this->settings['page_title'].'</title>';
		if (!empty($this->settings['favicon']))
			$output .= '<link rel="shortcut icon" href="'.$this->settings['favicon'].'" />';

		if (!empty($this->settings['meta_description']))
			$output .= '<meta name="description" content="'.$this->settings['meta_description'].'" />';

		if (!empty($this->settings['noindex']))
			$output .= '<meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />';

		$output .= '<script src="'.WPLAUNCHER_URI. 'js/jquery.js'.'"></script>';
		$output .= '<link rel="stylesheet" href="'.WPLAUNCHER_URI.'css/fontello.css">';
		$output .= '<script>var ajaxurl = "'.admin_url( 'admin-ajax.php' ).'", ajax_save_url = "'.add_query_arg('action', 'wplauncher_save_template_data', admin_url( 'admin-ajax.php' )).'", wplauncher_template = "'.$this->settings['template'].'";</script>';
		
		if ($this->is_editor())
			$output .= '<script src="'.WPLAUNCHER_URI.'js/jquery.jeditable.min.js"></script>';

		if (!empty($this->settings['header_code']))
			$output .= $this->settings['header_code'];

		echo apply_filters( 'wplauncher_head', $output );

		do_action( 'wplauncher_after_head' );
	}

	public function template_supports( $feature, $template = false ) {
		$headers = $this->get_template_info( $template );
		if (empty($headers['Supports'])) 
			return false;

		$template_features = array_map('trim', explode(',', $headers['Supports']));
		foreach ($template_features as $template_feature) {
			if (stripos($template_feature, $feature) !== false)
				return true;
		}

		return false;
	}

	public function ajax_save_template_data() {
		$template = preg_replace('#([^a-z0-9_/.-]+|\.\.)#i', '', $_POST['template']);
		$field = preg_replace('#[^a-z0-9_-]#i', '', $_POST['id']);
		$value = wp_kses_post($_POST['value']);
		$template_settings = get_option( 'wplauncher_template_data' );
		$template_settings[$template][$field] = $value;
		update_option( 'wplauncher_template_data', $template_settings );
		echo nl2br(str_replace(array("<br>\r", "<br>\n"), array("\r", "\n"), $value));
		exit();
	}

	public function template_tag_social( $params ) {
		// params: show (specific items), exclude/hide (specific items), format
		$is_set_up = false;
		foreach ($this->settings['social'] as $site => $url) {
			if (!empty($url)) {
				$is_set_up = true;
				break;
			}
		}
		if (!$is_set_up) {
			if ($this->is_editor())
				echo '<span class="wplauncher-configure wplauncher-configure-social">'.sprintf(__('To use the Social Links, insert at least one URL on the Social tab of the <a href="%s">Settings page</a>.', $this->plugin_slug), admin_url('options-general.php?page=launcher#social')).'</span>';
			return;
		}
		// default single string param
		if (is_string($params) && stripos($params, '=') === false) {
			$params = array('show_links' => $params);
		}
		$params = wp_parse_args( $params, array(
			'show_links' => false,
			'hide_links' => false,
			'output' => 'html', // can be 'array'
			'format' => '<a href="%url%">%icon% <span class="wplauncher-social-title">%name%</span></a>'
		) );
		if (!empty($params['show_links'])) {
			if (is_string($params['show_links'])) {
				$params['show_links'] = array_map('trim', explode(',', strtolower($params['show_links'])));
			}
			$params['hide_links'] = false;
		} elseif (!empty($params['hide_links'])) {
			if (is_string($params['hide_links'])) {
				$params['hide_links'] = array_map('trim', explode(',', strtolower($params['hide_links'])));
			}
			$params['show_links'] = false;
		}
		
		$classes = 'wplauncher-editable';
		$style_attr = '';
		$color_attrs = '';
		if (!empty($params['edit_color'])) {
			$classes .= ' wplauncher-editable-color';
			$color_params = array_merge($params, array('id' => 'social_color'));
			$color_params = $this->init_editable( 'color', $color_params, array('default_color' => '') );
			$color_value = $this->get_editable_value( $color_params['id'], $color_params['default_color'] );
			$color_attrs = ' data-colorid="'.$color_params['id'].'" data-color="'.$color_value.'"';
			if (!empty($color_value))
				$style_attr .= ' color: '.$color_value.';';
		}

		$hideable_attrs = '';
		if (!empty($params['hideable'])) {
			$classes .= ' wplauncher-hideable';
			$hideable_params = array_merge($params, array('id' => 'social_hidden'));
			$hideable_params = $this->init_editable( 'hide', $hideable_params, array('hidden' => '') );
			$hideable_value = $this->get_editable_value( $hideable_params['id'], $hideable_params['hidden'] );
			$hideable_attrs = ' data-hideid="'.$hideable_params['id'].'" data-hidden="'.$hideable_value.'"';
			if (!empty($hideable_value)) {
				$classes .= ' wplauncher-hidden';	
				if ( ! $this->is_editor() ) {
					$style_attr .= ' display: none;';
				}			
			}
		}
		
		$name_index = array('facebook' => 'Facebook', 'twitter' => 'Twitter', 'youtube' => 'Youtube', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'googleplus' => 'Google+', 'rss' => 'RSS');
		
		$output = '';
		$output_arr = array();
		foreach ($this->settings['social'] as $site => $url) {
			if (!empty($url)) {
				if (is_array($params['hide_links']) && in_array($site, $params['hide_links'])) {
					// don't show
				} elseif (is_array($params['show_links']) && !in_array($site, $params['show_links'])) {
					// don't show
				} else {
					// do
					$output .= str_replace(array('%url%', '%icon%', '%name%'), array($url, '<i class="wplauncher-icon-'.$site.'"></i>', $name_index[$site]), $params['format']);
					$output_arr[$site] = $url;
				}
			}
		}
		if ($params['output'] == 'array') {
			return $output_arr;
		} else {
			echo '<div class="wplauncher-social '.$classes.'" style="'.$style_attr.'"'.$color_attrs.$hideable_attrs.'>';
			echo apply_filters( 'wplauncher_social', $output );
			echo '</div>';
		}
		
	}

	public function template_tag_countdown( $params = '' ) {
		if (empty($this->settings['countdown']['date_formatted'])) {
			// countdown not set up
			if ($this->is_editor())
				echo '<span class="wplauncher-configure wplauncher-configure-countdown">'.sprintf(__('To use the Countdown Timer, set it up on the <a href="%s">Settings page</a>.', $this->plugin_slug), admin_url('options-general.php?page=launcher#countdown')).'</span>';
			return;
		} elseif ($this->settings['countdown']['date'] < time()) {
			// date expired
			if ($this->is_editor())
				echo '<span class="wplauncher-configure wplauncher-configure-countdown">'.sprintf(__('To use the Countdown Timer, set it to a date in the future on the <a href="%s">Settings page</a>.', $this->plugin_slug), admin_url('options-general.php?page=launcher#countdown')).'</span>';
			return;
		}

		$params = wp_parse_args( $params, array(
			'format' => '
				<span class="wplauncher-days">%D</span> 
					 <span class="wplauncher-date-sep">'.__('days', $this->plugin_slug).'</span>
				<span class="wplauncher-hours">%H</span>
					<span class="wplauncher-time-sep">:</span>
				<span class="wplauncher-minutes">%M</span>
					<span class="wplauncher-time-sep">:</span>
				<span class="wplauncher-seconds">%S</span>', 
		'refresh_rate' => 1000,
		'hideable' => 1
		) );
		
		$classes = 'wplauncher-editable';
		$style_attr = '';
		$color_attrs = '';
		if (!empty($params['edit_color'])) {
			$classes .= ' wplauncher-editable-color';
			$color_params = array_merge($params, array('id' => 'countdown_color'));
			$color_params = $this->init_editable( 'color', $color_params, array('default_color' => '') );
			$color_value = $this->get_editable_value( $color_params['id'], $color_params['default_color'] );
			$color_attrs = ' data-colorid="'.$color_params['id'].'" data-color="'.$color_value.'"';
			if (!empty($color_value))
				$style_attr .= ' color: '.$color_value.';';
		}

		$hideable_attrs = '';
		if (!empty($params['hideable'])) {
			$classes .= ' wplauncher-hideable';
			$hideable_params = array_merge($params, array('id' => 'countdown_hidden'));
			$hideable_params = $this->init_editable( 'hide', $hideable_params, array('hidden' => '') );
			$hideable_value = $this->get_editable_value( $hideable_params['id'], $hideable_params['hidden'] );
			$hideable_attrs = ' data-hideid="'.$hideable_params['id'].'" data-hidden="'.$hideable_value.'"';
			if (!empty($hideable_value)) {
				$classes .= ' wplauncher-hidden';	
				if ( ! $this->is_editor() ) {
					$style_attr .= ' display: none;';
				}			
			}
		}

		ob_start();
		?>
		<div class="wplauncher-countdown<?php echo ' '.$classes; ?>" style="<?php echo $style_attr; ?>"<?php echo $color_attrs.$hideable_attrs; ?>><div id="wplauncher-countdown"></div></div>
		<script type="text/javascript">
			var wplauncher_countdown_refresh_rate = <?php echo $params['refresh_rate']; ?>;
		</script>
		<script src="<?php echo WPLAUNCHER_URI.'js/jquery.countdown.min.js'; ?>"></script>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#wplauncher-countdown').countdown('<?php echo $this->settings["countdown"]["date_formatted"]; ?>', function(event) {
					$(this).html(event.strftime('<?php echo trim(str_replace(array("\n","\r"), '', $params["format"])); ?>'));
				});
			});
		</script>
		<?php 
		$output = ob_get_clean();
		echo apply_filters( 'wplauncher_countdown', $output );
	}

	public function template_tag_twitter( $params ) {

		if(empty($this->settings['twitter']['api_key']) || empty($this->settings['twitter']['api_secret']) || empty($this->settings['twitter']['access_token']) || empty($this->settings['twitter']['access_token_secret']) || empty($this->settings['twitter']['username'])) {
			if ($this->is_editor())
				echo '<span class="wplauncher-configure wplauncher-configure-twitter">'.sprintf(__('To display the Twitter feed, set it up on the <a href="%s">Settings page</a>.', $this->plugin_slug), admin_url('options-general.php?page=launcher#twitter')).'</span>';
			return;
		}

		//check if cache needs update
		$mts_twitter_plugin_last_cache_time = get_option('mts_twitter_plugin_last_cache_time');
		$diff = time() - $mts_twitter_plugin_last_cache_time;
		$crt = $this->settings['twitter']['cache_time'] * 3600;						
		//	yes, it needs update			
		if($diff >= $crt || empty($mts_twitter_plugin_last_cache_time)){
			require_once('includes/twitteroauth.php');
			$connection = new Twitteroauth($this->settings['twitter']['api_key'], $this->settings['twitter']['api_secret'], $this->settings['twitter']['access_token'], $this->settings['twitter']['access_token_secret']);
			$tweets = $connection->get("https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=".$this->settings['twitter']['username']."&count=10");
			if (empty($tweets)) {
				if ($this->is_editor())
					echo '<span class="wplauncher-configure wplauncher-configure-twitter">'.sprintf(__('Couldn\'t retreive feed. Make sure you have set the username correctly on the <a href="%s">Settings page</a>.', $this->plugin_slug), admin_url('options-general.php?page=launcher#twitter')).'</span>';
				return;
			}
			if(!empty($tweets->errors)){
				if ($this->is_editor()) {
					if($tweets->errors[0]->message == 'Invalid or expired token'){
						echo '<span class="wplauncher-configure wplauncher-configure-twitter">'.$tweets->errors[0]->message.'!</strong><br />You\'ll need to regenerate it <a href="https://dev.twitter.com/apps" target="_blank">here</a></span>!';
					} else { 
						echo '<span class="wplauncher-configure wplauncher-configure-twitter">'.$tweets->errors[0]->message.'</strong>';
					}
				}
				
				return;
			}
			for($i = 0;$i <= count($tweets); $i++){
				if(!empty($tweets[$i])){
					$tweets_array[$i]['created_at'] = $tweets[$i]->created_at;
					$tweets_array[$i]['text'] = $tweets[$i]->text;			
					$tweets_array[$i]['status_id'] = $tweets[$i]->id_str;			
				}
			}			
			//save tweets to wp option 		
			update_option('mts_twitter_plugin_tweets',serialize($tweets_array));							
			update_option('mts_twitter_plugin_last_cache_time',time());		
			//echo '<!-- twitter cache has been updated! -->';
		}
		$mts_twitter_plugin_tweets = maybe_unserialize(get_option('mts_twitter_plugin_tweets'));
		$output = '';
		$output_arr = array();
		$classes = '';
		$style_attr = '';
		$hideable_attrs = '';
		$color_attrs = '';
		if(!empty($mts_twitter_plugin_tweets)) {
			$params = wp_parse_args( $params, array(
				'format' => '<li><span>%text%</span> <a class="twitter_time" target="_blank" href="%url%">%time%</a></li>',
				'number' => 5,
				'hideable' => 1,
				'before' => '<ul>',
				'after' => '</ul>'
			) );

			$classes = 'wplauncher-editable';
			$style_attr = '';
			$color_attrs = '';
			if (!empty($params['edit_color'])) {
				$classes .= ' wplauncher-editable-color';
				$color_params = array_merge($params, array('id' => 'twitter_color'));
				$color_params = $this->init_editable( 'color', $color_params, array('default_color' => '') );
				$color_value = $this->get_editable_value( $color_params['id'], $color_params['default_color'] );
				$color_attrs = ' data-colorid="'.$color_params['id'].'" data-color="'.$color_value.'"';
				if (!empty($color_value))
					$style_attr .= ' color: '.$color_value.';';
			}

			$hideable_attrs = '';
			if (!empty($params['hideable'])) {
				$classes .= ' wplauncher-hideable';
				$hideable_params = array_merge($params, array('id' => 'twitter_hidden'));
				$hideable_params = $this->init_editable( 'hide', $hideable_params, array('hidden' => '') );
				$hideable_value = $this->get_editable_value( $hideable_params['id'], $hideable_params['hidden'] );
				$hideable_attrs = ' data-hideid="'.$hideable_params['id'].'" data-hidden="'.$hideable_value.'"';
				if (!empty($hideable_value)) {
					$classes .= ' wplauncher-hidden';	
					if ( ! $this->is_editor() ) {
						$style_attr .= ' display: none;';
					}
				}
			}

			$output = $params['before'];
			$fctr = '1';
			foreach($mts_twitter_plugin_tweets as $tweet) {
				$output .= str_replace(array('%text%', '%url%', '%time%'), array(
					//$this->twitter_convert_links($tweet['text']), // Text
					$this->twitter_convert_links($tweet['text']), // Text
					'http://twitter.com/'.$this->settings['twitter']['username'].'/statuses/'.$tweet['status_id'], // URL
					$this->twitter_relative_time($tweet['created_at']), // Time
					//$tweet['created_at'], // Time
				), $params['format']);
				if($fctr == $params['number']){ break; }
				$fctr++;
			}
			$output .= $params['after'];
		}
		
		echo '<div class="wplauncher-twitter '.$classes.'" style="'.$style_attr.'"'.$color_attrs.$hideable_attrs.'>';
		echo apply_filters( 'wplauncher_twitter', $output );
		echo '</div>';
	}

	//convert links to clickable format
	public function twitter_convert_links($tweet){
		//Convert urls to <a> links
		$tweet = preg_replace("/([\w]+\:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/", "<a target=\"_blank\" href=\"$1\">$1</a>", $tweet);

		//Convert hashtags to twitter searches in <a> links
		$tweet = preg_replace("/#([A-Za-z0-9\/\.]*)/", "<a target=\"_new\" href=\"http://twitter.com/search?q=$1\">#$1</a>", $tweet);

		//Convert attags to twitter profiles in &lt;a&gt; links
		$tweet = preg_replace("/@([A-Za-z0-9\/\.]*)/", "<a href=\"http://www.twitter.com/$1\">@$1</a>", $tweet);
		return $tweet; // return the status
	}					
					
	//convert dates to readable format	
	public function twitter_relative_time($a) {			
		$b = strtotime("now");  //get current timestampt
		$c = strtotime($a); //get timestamp when tweet created
		$d = $b - $c; //get difference
		$minute = 60; //calculate different time values
		$hour = $minute * 60;
		$day = $hour * 24;
		$week = $day * 7;				
		if(is_numeric($d) && $d > 0) {				
			if($d < 3) return "right now"; //if less then 3 seconds
			if($d < $minute) return sprintf(__('%s seconds ago', $this->plugin_slug), floor($d)); //if less then minute
			if($d < $minute * 2) return __('about 1 minute ago', $this->plugin_slug); //if less then 2 minutes
			if($d < $hour) return sprintf(__('%s minutes ago', $this->plugin_slug), floor($d / $minute)); //if less then hour
			if($d < $hour * 2) return __('about 1 hour ago', $this->plugin_slug); //if less then 2 hours
			if($d < $day) return sprintf(__('%s hours ago', $this->plugin_slug), floor($d / $hour)); //if less then day
			if($d > $day && $d < $day * 2) return __('yesterday', $this->plugin_slug); //if more then day, but less then 2 days
			if($d < $day * 365) return sprintf(__('%s days ago', $this->plugin_slug), floor($d / $day)); //if less then year
			return __('over a year ago', $this->plugin_slug); //else return more than a year
		}
	}

	public function template_tag_contact( $params ) {
		if(empty($this->settings['contact']['label_name']) || empty($this->settings['contact']['label_email']) || empty($this->settings['contact']['label_message']) || empty($this->settings['contact']['sendto'])) {
			if ($this->is_editor())
				echo '<span class="wplauncher-configure wplauncher-configure-contact">'.sprintf(__('To display the Contact Form, set it up on the <a href="%s">Settings page</a>.', $this->plugin_slug), admin_url('options-general.php?page=launcher#contact')).'</span>';
			return;
		}
		$params = wp_parse_args( $params, array(
			'hideable' => 1,
			'edit_color' => 1
		) );
		$classes = 'wplauncher-editable';
		$style_attr = '';
		$color_attrs = '';
		if (!empty($params['edit_color'])) {
			$classes .= ' wplauncher-editable-color';
			$color_params = array_merge($params, array('id' => 'contact_color'));
			$color_params = $this->init_editable( 'color', $color_params, array('default_color' => '') );
			$color_value = $this->get_editable_value( $color_params['id'], $color_params['default_color'] );
			$color_attrs = ' data-colorid="'.$color_params['id'].'" data-color="'.$color_value.'"';
			if (!empty($color_value))
				$style_attr .= ' color: '.$color_value.';';
		}

		$hideable_attrs = '';
		if (!empty($params['hideable'])) {
			$classes .= ' wplauncher-hideable';
			$hideable_params = array_merge($params, array('id' => 'contact_hidden'));
			$hideable_params = $this->init_editable( 'hide', $hideable_params, array('hidden' => '') );
			$hideable_value = $this->get_editable_value( $hideable_params['id'], $hideable_params['hidden'] );
			$hideable_attrs = ' data-hideid="'.$hideable_params['id'].'" data-hidden="'.$hideable_value.'"';
			if (!empty($hideable_value)) {
				$classes .= ' wplauncher-hidden';	
				if ( ! $this->is_editor() ) {
					$style_attr .= ' display: none;';
				}
			}
		}

		global $wplauncher_contact_form;
		$wplauncher_contact_form->setvars($this->settings['contact']['label_name'], $this->settings['contact']['label_email'], $this->settings['contact']['label_message'], $this->settings['contact']['label_submit'], $this->settings['contact']['sendto']);
		$wplauncher_contact_form->get_errors(); // if there are any
    	echo '<div class="wplauncher-contact '.$classes.'" style="'.$style_attr.'"'.$color_attrs.$hideable_attrs.'>';
		echo apply_filters( 'wplauncher_contact', $wplauncher_contact_form->get_form() );
		echo '</div>';
	}

	public function template_tag_subscribe( $params ) {
		if(empty($this->settings['subscribe']['service'])) {
			if ($this->is_editor())
				echo '<span class="wplauncher-configure wplauncher-configure-subscribe">'.sprintf(__('To display the Subscribe Form, set it up on the <a href="%s">Settings page</a>.', $this->plugin_slug), admin_url('options-general.php?page=launcher#subscribe')).'</span>';
			return;
		}

		$params = wp_parse_args( $params, array(
			'hideable' => 1,
			'edit_color' => 1
		) );
		$classes = 'wplauncher-editable';
		$style_attr = '';
		$color_attrs = '';
		if (!empty($params['edit_color'])) {
			$classes .= ' wplauncher-editable-color';
			$color_params = array_merge($params, array('id' => 'subscribe_color'));
			$color_params = $this->init_editable( 'color', $color_params, array('default_color' => '') );
			$color_value = $this->get_editable_value( $color_params['id'], $color_params['default_color'] );
			$color_attrs = ' data-colorid="'.$color_params['id'].'" data-color="'.$color_value.'"';
			if (!empty($color_value))
				$style_attr .= ' color: '.$color_value.';';
		}

		$hideable_attrs = '';
		if (!empty($params['hideable'])) {
			$classes .= ' wplauncher-hideable';
			$hideable_params = array_merge($params, array('id' => 'subscribe_hidden'));
			$hideable_params = $this->init_editable( 'hide', $hideable_params, array('hidden' => '') );
			$hideable_value = $this->get_editable_value( $hideable_params['id'], $hideable_params['hidden'] );
			$hideable_attrs = ' data-hideid="'.$hideable_params['id'].'" data-hidden="'.$hideable_value.'"';
			if (!empty($hideable_value)) {
				$classes .= ' wplauncher-hidden';	
				if ( ! $this->is_editor() ) {
					$style_attr .= ' display: none;';
				}
			}
		}
    	echo '<div class="wplauncher-subscribe '.$classes.'" style="'.$style_attr.'"'.$color_attrs.$hideable_attrs.'>';

		if ($this->settings['subscribe']['service'] == 'feedburner') {
			?>
			<form id="wplauncher-subscribe" class="wplauncher-feedburner-form" method="post" action="http://feedburner.google.com/fb/a/mailverify" target="wplwindow">
				<fieldset class="wplauncher-subscribe-fieldset">
					<input name="wplauncher-subscribe-email" id="wplauncher-subscribe-email" type="text" placeholder="<?php echo $this->settings['subscribe']['email_label']; ?>"/>
					<input value="<?php echo esc_attr($this->settings['subscribe']['submit_label']); ?>" type="submit" id="wplauncher-subscribe-submit" name="wplauncher-submit" />
				</fieldset>
			</form>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					$(".wplauncher-feedburner-form").submit(function(e){
						e.preventDefault();
						window.open('http://feedburner.google.com/fb/a/mailverify?uri=<?php echo esc_attr( $this->settings['subscribe']['feedburner']['username'] ); ?>', 'wplwindow', 'scrollbars=yes,width=550,height=520');
						return true;
					});
				});
			</script>
			<?php
		} else {
			?>
			<form id="wplauncher-subscribe" method="post" novalidate="true">
				<fieldset class="wplauncher-subscribe-fieldset">
					<?php if ( $this->settings['subscribe']['include_name'] ) { ?>
						<input id="wplauncher-subscribe-first-name" name="wplauncher-subscribe-fname" type="text" placeholder="<?php echo $this->settings['subscribe']['name_label']; ?>"/>
					<?php } ?>

					<?php if ( $this->settings['subscribe']['include_last_name'] ) { ?>
						<input id="wplauncher-subscribe-last-name" name="wplauncher-subscribe-lname" type="text" placeholder="<?php echo $this->settings['subscribe']['last_name_label']; ?>"/>
					<?php } ?>
					<input id="wplauncher-subscribe-email" name="wplauncher-subscribe-email" type="email" placeholder="<?php echo $this->settings['subscribe']['email_label']; ?>"/>
					<input value="<?php echo esc_attr($this->settings['subscribe']['submit_label']); ?>" type="submit" id="wplauncher-subscribe-submit" name="wplauncher-submit" />
					<input type="hidden" id="wplauncher-subscribe-service" value="<?php echo esc_attr($this->settings['subscribe']['service']); ?>" />
					<div class="wplauncher-subscribe-message"></div>
				</fieldset>
			</form>
			<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#wplauncher-subscribe').submit(function(event) {
			    	var type = $('#wplauncher-subscribe-service').val();
				    if (/*$('#mtsnb-newsletter-type').html() == 'WordPress' ||*/
				    	type == 'aweber' ||
				    	type == 'mailchimp' ||
				    	type == 'getresponse' ||
				    	type == 'campaignmonitor' ||
				    	type == 'madmimi') {

					    event.preventDefault();

					    //$('<i style="margin-left: 10px;" class="mtsnb-submit-spinner fa fa-spinner fa-spin"></i>').insertAfter('.mtsnb-submit');
					    $('#wplauncher-subscribe').addClass('submitting');

						var data = {
							'action': 'wplauncher_ajax_subscribe',
							'type': type,
							'email': $('#wplauncher-subscribe-email').val(),
							'first_name': $('#wplauncher-subscribe-first-name').val(),
							'last_name': $('#wplauncher-subscribe-last-name').val(),
						};

						$.post(ajaxurl, data, function(response) {
							response = $.parseJSON(response);
							//$('.mtsnb-submit-spinner').remove();
					    	$('#wplauncher-subscribe').removeClass('submitting');
							//$('.wplauncher-subscribe-message').html('<i class="fa fa-' + response.status + '"></i> ' + response.message);
							$('.wplauncher-subscribe-message').html(response.message);
							$('.wplauncher-subscribe-message').css('margin-top', '10px');
							if (response.status == 'check') {
								$('#wplauncher-subscribe').find('input').hide();
							}
						});

				    }
				});
			});
			</script>
			<style type="text/css">
				#wplauncher-subscribe.submitting {
					opacity: 0.3;
					pointer-events: none;
				}
			</style>
			<?php
		}
		echo '</div>';

	}

	public function options_field_text($name, $label, $description = '', $attrs = false, $row_class = '') {
		if (empty($attrs) || !is_array($attrs)) $attrs = array();
		if (empty($attrs['type'])) $attrs['type'] = 'text';
		if (empty($attrs['name'])) $attrs['name'] = $name;
		if (empty($attrs['id'])) $attrs['id'] = str_replace( array('[', ']'), array('-', ''), $name );
		// lookup value
		// name = wplauncher_options[subscribe][feedburner][userid]
		// => $this->settings['subscribe']['feedburner']['userid'];
		$var_name = str_replace(array('wplauncher_options'), array(''), $attrs['name']);
		//$var_name = 'settings';
		if (empty($attrs['value'])) $attrs['value'] = $this->var_array_lookup($this->settings, $var_name); // magic!

		$attributes = '';
		foreach ($attrs as $attr_name => $attr_value) {
			$attributes .= ' '.$attr_name.'="'.esc_attr($attr_value).'"';
		}
		?>
		<tr class="<?php echo $attrs['id'].' '.$row_class; ?>">
            <th><label for="<?php echo $attrs['id']; ?>"><?php echo $label; ?></label></th>
            <td>
            	<input<?php echo $attributes; ?>>
            	<?php if (!empty($description)) { ?>
                	<p class="description"><?php echo $description; ?></p>
                <?php } ?>
            </td>
        </tr>
		<?php
	}
	public function options_field_textarea($name, $label, $description = '', $attrs = false, $row_class = '') {
		if (empty($attrs) || !is_array($attrs)) $attrs = array();
		if (empty($attrs['type'])) $attrs['type'] = 'text';
		if (empty($attrs['name'])) $attrs['name'] = $name;
		if (empty($attrs['id'])) $attrs['id'] = str_replace( array('[', ']'), array('-', ''), $name );
		// lookup value
		// name = wplauncher_options[subscribe][feedburner][userid]
		// => $this->settings['subscribe']['feedburner']['userid'];
		$var_name = str_replace(array('wplauncher_options'), array(''), $attrs['name']);
		//$var_name = 'settings';
		if (empty($attrs['value'])) $attrs['value'] = $this->var_array_lookup($this->settings, $var_name); // magic!

		$value = $attrs['value'];
		unset($attrs['value']);

		$attributes = '';
		foreach ($attrs as $attr_name => $attr_value) {
			$attributes .= ' '.$attr_name.'="'.esc_attr($attr_value).'"';
		}
		?>
		<tr class="<?php echo $attrs['id'].' '.$row_class; ?>">
            <th><label for="<?php echo $attrs['id']; ?>"><?php echo $label; ?></label></th>
            <td>
            	<textarea<?php echo $attributes; ?>><?php echo esc_textarea($value); ?></textarea>
            	<?php if (!empty($description)) { ?>
                	<p class="description"><?php echo $description; ?></p>
                <?php } ?>
            </td>
        </tr>
		<?php
	}

	public function options_field_checkbox($name, $label, $description = '', $attrs = false, $row_class = '') {
		if (empty($attrs) || !is_array($attrs)) $attrs = array();
		if (empty($attrs['type'])) $attrs['type'] = 'checkbox';
		if (empty($attrs['name'])) $attrs['name'] = $name;
		if (empty($attrs['id'])) $attrs['id'] = str_replace( array('[', ']'), array('-', ''), $name );
		// lookup value
		// name = wplauncher_options[subscribe][feedburner][userid]
		// => $this->settings['subscribe']['feedburner']['userid'];
		$var_name = str_replace(array('wplauncher_options'), array(''), $attrs['name']);
		//$var_name = 'settings';
		if (empty($attrs['checked']) && $this->var_array_lookup($this->settings, $var_name)) $attrs['checked'] = 'checked'; // magic!

		$attributes = '';
		foreach ($attrs as $attr_name => $attr_value) {
			$attributes .= ' '.$attr_name.'="'.esc_attr($attr_value).'"';
		}
		?>
		<tr class="<?php echo $attrs['id'].' '.$row_class; ?>">
            <th><label for="<?php echo $attrs['id']; ?>"><?php echo $label; ?></label></th>
            <td>
            	<?php if (!empty($description)) { ?>
                	<p class="description"><label>
                <?php } ?>
                <input type="hidden" name="<?php echo $attrs['name']; ?>" value="0">
            	<input<?php echo $attributes; ?>> 
            	<?php if (!empty($description)) { ?>
                	<?php echo $description; ?></p>
                <?php } ?>
            </td>
        </tr>
		<?php
	}

	function options_field_select( $name, $options, $label, $description = '', $attrs = false, $row_class = '' ) {
		if (empty($attrs) || !is_array($attrs)) $attrs = array();
		if (empty($attrs['name'])) $attrs['name'] = $name;
		if (empty($attrs['id'])) $attrs['id'] = str_replace( array('[', ']'), array('-', ''), $name );
		$var_name = str_replace(array('wplauncher_options'), array(''), $attrs['name']);
		$selected = $this->var_array_lookup($this->settings, $var_name);

		if (is_string($selected))
			$attrs['data-selected'] = $selected;

		$attributes = '';
		foreach ($attrs as $attr_name => $attr_value) {
			$attributes .= ' '.$attr_name.'="'.esc_attr($attr_value).'"';
		}
		?>
		<tr class="<?php echo $attrs['id'].' '.$row_class; ?>">
            <th><label for="<?php echo $attrs['id']; ?>"><?php echo $label; ?></label></th>
            <td>
            	<select<?php echo $attributes; ?>>
            		<?php 
            		foreach ($options as $option_val => $option_label) {
            			echo '<option value="'.$option_val.'"'.selected($option_val, $selected, false).'>'.$option_label.'</option>';
            		}
            		?>
            	</select>
            	<?php if (!empty($description)) { ?>
                	<p class="description"><label>
                <?php } ?>
            </td>
        </tr>
		<?php
	}

	function options_field_template( $name ) {
		$templates = $this->get_templates_list();
		$current = $this->settings['template'];
		echo '<div class="wplauncher-templates">';
		foreach ($templates as $file => $template) {
			$thumb = '';
			if (file_exists(str_replace('.php', '.png', $template['path'].$file))) {
				$thumb = get_bloginfo( 'url' ).'/'.str_replace(ABSPATH, '', str_replace('.php', '.png', $template['path'].$file));
			} elseif (file_exists(str_replace('.php', '.jpg', $template['path'].$file))) {
				$thumb = get_bloginfo( 'url' ).'/'.str_replace(ABSPATH, '', str_replace('.php', '.jpg', $template['path'].$file));
			}
			echo '<input type="radio" name="'.$name.'" id="'.sanitize_html_class( $file ).'" value="'.$file.'"  class="wplauncher-template-select"'.checked( $file, $current, false ).' /> ';
			echo '<label for="'.sanitize_html_class( $file ).'" class="wplauncher-single-template">';
			echo $template['Name'];
			if ($thumb)
				echo '<img src="'.$thumb.'" width="220" />';
			
			echo '<input type="hidden" class="wplauncher-template-supports" value="'.strtolower($template['Supports']).'" /> ';
			echo '</label>';
		}
		echo '</div>';
	}

	function var_array_lookup($arr, $string) { 
	    preg_match_all('/\[([^\]]*)\]/', $string, $arr_matches, PREG_PATTERN_ORDER); 
	    
	    $return = $arr; 
	    foreach($arr_matches[1] as $dimension) {
        	if (isset($return[$dimension]))
        		$return = $return[$dimension]; 
	    } 
	        
	    return $return; 
    } 


    /**
	 * Ajax newsletter
	 *
	 * @since    1.0.0
	 */
	public function ajax_subscribe() {

		// No First Name
		if (!isset($_POST['first_name'])) {
			$_POST['first_name'] = '';
		}

		// No Last Name
		if (!isset($_POST['last_name'])) {
			$_POST['last_name'] = '';
		}

		$success_message = stripcslashes( $this->settings['subscribe']['success_message'] );

		// MailChimp
		if ($_POST['type'] == 'mailchimp') {

			require_once('includes/mailchimp/MailChimp.php');

			if (!isset($this->settings['subscribe']['mailchimp']['api_key']) || $this->settings['subscribe']['mailchimp']['api_key'] == '') {
				echo json_encode(array(
					'status'	=> 'warning',
					'message'	=> __('MailChimp account is not setup properly.'),
				));

				die();
			}

			if (!isset($this->settings['subscribe']['mailchimp']['list']) || $this->settings['subscribe']['mailchimp']['list'] == '') {
				echo json_encode(array(
					'status'	=> 'warning',
					'message'	=> __('MailChimp: No list specified.'),
				));

				die();
			}

			$MailChimp = new WPS_MailChimp($this->settings['subscribe']['mailchimp']['api_key']);
			$result = $MailChimp->call('lists/subscribe', array(
                'id'                => $this->settings['subscribe']['mailchimp']['list'],
                'email'             => array('email'=>sanitize_email( $_POST['email']) ),
                'merge_vars'        => array( 'FNAME'=> sanitize_text_field( $_POST['first_name'] ), 'LNAME'=>sanitize_text_field( $_POST['last_name'] ) ),
                'double_optin'      => true,
                'update_existing'   => false,
                'replace_interests' => false,
                'send_welcome'      => true,
            ));

            if ($result) {

	            if (isset($result['email'])) {

					echo json_encode(array(
						'status'		=> 'check',
						'message'		=> $success_message,
					));

					die();
	            }

	            else if (isset($result['status']) && $result['status'] == 'error') {
					echo json_encode(array(
						'status'		=> 'warning',
						'message'		=> $result['error'],
					));

					die();
	            }
            } else {

	            echo json_encode(array(
					'status'	=> 'warning',
					'message'	=> __('Unable to subscribe.'),
				));

				die();
            }
		}

		// Add email to aweber
		else if ($_POST['type'] == 'aweber') {

			require_once('includes/aweber/aweber_api.php');

			if (!isset($this->settings['subscribe']['aweber']['consumer_key']) || $this->settings['subscribe']['aweber']['consumer_key'] == '') {
				echo json_encode(array(
					'status'	=> 'warning',
					'message'	=> __('Aweber account is not setup properly'),
				));

				die();
			}

			$aweber = new AWeberAPI($this->settings['subscribe']['aweber']['consumer_key'], $this->settings['subscribe']['aweber']['consumer_secret']);

			try {
				$account = $aweber->getAccount($this->settings['subscribe']['aweber']['access_key'], $this->settings['subscribe']['aweber']['access_secret']);
				$list = $account->loadFromUrl('/accounts/' . $account->id . '/lists/' . $this->settings['subscribe']['aweber']['list']);

				$subscriber = array(
					'email' 	=> sanitize_email( $_POST['email'] ),
					'name'		=> sanitize_text_field( $_POST['first_name'] ) . ' ' . sanitize_text_field( $_POST['last_name'] ),
					'ip' 		=> $_SERVER['REMOTE_ADDR']
				);

				$newSubscriber = $list->subscribers->create($subscriber);

				echo json_encode(array(
					'status'		=> 'check',
					'message'		=> $success_message,
				));

				die();

			} catch (AWeberAPIException $exc) {
				echo json_encode(array(
					'status'	=> 'warning',
					'message'	=> $exc->message,
				));

				die();
			}
		}

		// Add email to Get Response
		else if ($_POST['type'] == 'getresponse') {

			require_once('includes/getresponse/jsonRPCClient.php');

			$api = new jsonRPCClient('http://api2.getresponse.com');

			try {
				$api->add_contact(
					$this->settings['subscribe']['getresponse']['api_key'],
				    array (
				        'campaign'  => $this->settings['subscribe']['getresponse']['campaign'],
				        'name'      => sanitize_text_field( $_POST['first_name'] ) . ' ' . sanitize_text_field( $_POST['last_name'] ),
				        'email'     => sanitize_email( $_POST['email'] ),
				    )
				);

				echo json_encode(array(
					'status'		=> 'check',
					'message'		=> $success_message,
				));

				die();

			} catch (RuntimeException $exc) {

				$msg = $exc->getMessage();
				$msg = substr($msg, 0, strpos($msg, ";"));

				echo json_encode(array(
					'status'	=> 'warning',
					'message'	=> $msg,
				));

				die();
			}
		}

		// Add email to Campaign Monitor
		else if ($_POST['type'] == 'campaignmonitor') {

			require_once('includes/campaignmonitor/csrest_subscribers.php');

			$wrap = new CS_REST_Subscribers($this->settings['subscribe']['campaignmonitor']['list'], $this->settings['subscribe']['campaignmonitor']['api_key']);

			// Check if subscribor is already subscribed

			$result = $wrap->get(sanitize_email( $_POST['email'] ));

			if ($result->was_successful()) {
				echo json_encode(array(
					'status'	=> 'warning',
					'message'	=> 'You are already subscribed to this list.',
				));

				die();
			}

			$result = $wrap->add(array(
				'EmailAddress' 	=> sanitize_email($_POST['email']),
				'Name' 			=> sanitize_text_field( $_POST['first_name'] ). ' ' . sanitize_text_field( $_POST['last_name'] ),
				'Resubscribe' 	=> true
			));

			if ($result->was_successful()) {

				echo json_encode(array(
					'status'		=> 'check',
					'message'		=> $success_message,
				));

				die();

			} else {

				echo json_encode(array(
					'status'	=> 'warning',
					'message'	=> $result->response->Message,
				));

				die();
			}
		}

		// Add email to Mad Mimi
		else if ($_POST['type'] == 'madmimi') {

			require_once('includes/madmimi/MadMimi.class.php');

			$mailer = new MadMimi($this->settings['subscribe']['madmimi']['username'], $this->settings['subscribe']['madmimi']['api_key']);

			// No Email

			if (!isset($_POST['email']) || $_POST['email'] == '') {
				echo json_encode(array(
					'status'	=> 'warning',
					'message'	=> 'No Email address provided.'
				));
				die();
			}

			// Invalid Email Address

			if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				echo json_encode(array(
					'status'	=> 'warning',
					'message'	=> 'Invalid Email provided.'
				));
				die();
			}

			try {

				// Check if user is already in list

				$result = $mailer->Memberships(sanitize_email( $_POST['email']) );
				$lists  = new SimpleXMLElement($result);

				if ($lists->list) {
					foreach ($lists->list as $l) {
						if ($l->attributes()->{'name'}->{0} == $meta_values['madmimi_list']) {

							echo json_encode(array(
								'status'		=> 'check',
								'message'		=> 'You are already subscribed to this list.',
							));

							die();
						}
					}
			    }

				$result = $mailer->AddMembership($this->settings['subscribe']['madmimi']['list'], sanitize_email( $_POST['email'] ), array(
					'first_name'	=> sanitize_text_field( $_POST['first_name'] ),
					'last_name'		=> sanitize_text_field( $_POST['last_name'] ),
				));

				echo json_encode(array(
					'status'		=> 'check',
					'message'		=> $success_message,
				));

				die();

			} catch (RuntimeException $exc) {

				echo json_encode(array(
					'status'	=> 'warning',
					'message'	=> $msg,
				));

				die();
			}
		}

		die();
	}

	/**
	 * Get all MailChimp Lists and display in settings
	 *
	 */
	public function get_mailchimp_lists() {

		$options = '';

		if (isset($_POST['api_key']) && $_POST['api_key'] != '') {
			require_once( 'includes/mailchimp/MailChimp.php');

			$MailChimp = new WPS_MailChimp(sanitize_text_field( $_POST['api_key'] ));
			$lists = $MailChimp->call('lists/list');

			if (isset($lists) && is_array($lists)) {

				foreach ($lists['data'] as $list) {
					$options .= '<option value="' . $list['id'] . '">' .  $list['name'] . '</option>';
				}

				if (isset($_POST['list']) && $_POST['list'] != '') {
					$options = '';
					foreach ($lists['data'] as $list) {

						if ($_POST['list'] == $list['id']) {
							$options .= '<option value="' . $list['id'] . '" selected="selected">' .  $list['name']. '</option>';
						} else {
							$options .= '<option value="' . $list['id'] . '">' .  $list['name'] . '</option>';
						}
					}
				}
			}
		}

		echo $options;

		die(); // this is required to terminate immediately and return a proper response
	}

	/**
	 * Get the Aweber lists for an account
	 *
	 */
	public function get_aweber_lists() {

		$options = '';
		$consumerKey = '';
		$consumerSecret = '';
		$accessKey = '';
		$accessSecret = '';

		if (isset($_POST['code']) && $_POST['code'] != '') {

			require_once( 'includes/aweber/aweber_api.php');

			try {
				$credentials = AWeberAPI::getDataFromAweberID(sanitize_text_field( $_POST['code'] ));
				list($consumerKey, $consumerSecret, $accessKey, $accessSecret) = $credentials;


				$consumerKey = isset($consumerKey) && !empty($consumerKey) ? $consumerKey : '';
				$consumerSecret = isset($consumerSecret) && !empty($consumerSecret) ? $consumerSecret : '';
				$accessKey = isset($accessKey) && !empty($accessKey) ? $accessKey : '';
				$accessSecret = isset($accessSecret) && !empty($accessSecret) ? $accessSecret : '';
			} catch (AWeberAPIException $exc) {
				error_log($exc);
			}

			try {

				$aweber = new AWeberAPI($consumerKey, $consumerSecret);
				$account = $aweber->getAccount($accessKey, $accessSecret);
				$lists = $account->loadFromUrl('/accounts/' . $account->id . '/lists/');

				foreach ($lists as $list) {
					$options .= '<option value="' . $list->id . '">' .  $list->name . '</option>';
				}

			} catch (AWeberAPIException $exc) { error_log($exc); }
		}

		if (isset($_POST['list']) && $_POST['list'] != '') {

			$consumerKey     = sanitize_text_field( $_POST['consumer_key'] );
			$consumerSecret  = sanitize_text_field( $_POST['consumer_secret'] );
			$accessKey       = sanitize_text_field( $_POST['access_key'] );
			$accessSecret    = sanitize_text_field( $_POST['access_secret'] );

			require_once( 'includes/aweber/aweber_api.php');

			try {

				$aweber = new AWeberAPI($consumerKey, $consumerSecret);
				$account = $aweber->getAccount($accessKey, $accessSecret);
				$lists = $account->loadFromUrl('/accounts/' . $account->id . '/lists/');

				$options = '';
				foreach ($lists as $list) {
					if ($_POST['list'] == $list->id) {
						$options .= '<option value="' . $list->id . '" selected="selected">' .  $list->name . '</option>';
					} else {
						$options .= '<option value="' . $list->id . '">' .  $list->name . '</option>';
					}
				}

			} catch (AWeberAPIException $exc) { error_log($exc); }
		}

		echo json_encode(array(
			'html'               => $options,
			'consumer_key'       => $consumerKey,
			'consumer_secret'    => $consumerSecret,
			'access_key'         => $accessKey,
			'access_secret'      => $accessSecret,
		));

		die(); // this is required to terminate immediately and return a proper response
	}

	/**
	 * Get all Get Repsonse Lists and display in settings
	 *
	 */
	public function get_getresponse_lists() {

		$options = '';

		if (isset($_POST['api_key']) && $_POST['api_key'] != '') {

			require_once( 'includes/getresponse/jsonRPCClient.php');
			$api = new jsonRPCClient('http://api2.getresponse.com');

			try {
				$result = $api->get_campaigns(sanitize_text_field( $_POST['api_key'] ));
				foreach ((array) $result as $k => $v) {
					$campaigns[] = array('id' => $k, 'name' => $v['name']);
				}
			}

			catch (Exception $e) {}

			if (isset($campaigns) && is_array($campaigns)) {

				foreach ($campaigns as $campaign) {
					$options .= '<option value="' . $campaign['id'] . '">' .  $campaign['name'] . '</option>';
				}

				if (isset($_POST['campaign']) && $_POST['campaign'] != '') {
					$options = '';
					foreach ($campaigns as $campaign) {

						if ($_POST['campaign'] == $campaign['id']) {
							$options .= '<option value="' . $campaign['id'] . '" selected="selected">' .  $campaign['name'] . '</option>';
						} else {
							$options .= '<option value="' . $campaign['id'] . '">' .  $campaign['name'] . '</option>';
						}
					}
				}
			}
		}

		echo $options;

		die(); // this is required to terminate immediately and return a proper response
	}

	/**
	 * Get all Campaign Monitor Lists and display in settings
	 *
	 */
	public function get_campaignmonitor_lists() {

		$lists = '';
		$clients = '';

		if (isset($_POST['api_key']) && $_POST['api_key'] != '') {

			require_once( 'includes/campaignmonitor/csrest_general.php');
			require_once( 'includes/campaignmonitor/csrest_clients.php');
			$auth = array('api_key' => sanitize_text_field( $_POST['api_key'] ));
			$wrap = new CS_REST_General($auth);
			$result = $wrap->get_clients();


			if ($result->was_successful()) {

				foreach ($result->response as $client) {
					$clients .= '<option value="' . $client->ClientID . '">' .  $client->Name . '</option>';
				}

				if (isset($_POST['client']) && $_POST['client'] != '') {
					$clients = '';
					foreach ($result->response as $client) {
						if ($_POST['client'] == $client->ClientID) {
							$clients .= '<option value="' . $client->ClientID . '" selected="selected">' .  $client->Name . '</option>';
						} else {
							$clients .= '<option value="' . $client->ClientID . '">' .  $client->Name . '</option>';
						}
					}
				}

				if (isset($_POST['client']) && $_POST['client'] != '') {
					$client_id = sanitize_text_field( $_POST['client'] );
				} else {
					$client_id = $result->response[0]->ClientID;
				}

				$wrap = new CS_REST_Clients($client_id, sanitize_text_field( $_POST['api_key']) );
				$result = $wrap->get_lists();

				if ($result->was_successful()) {
					foreach ($result->response as $list) {
						$lists .= '<option value="' . $list->ListID . '">' .  $list->Name . '</option>';
					}

					if (isset($_POST['list']) && $_POST['list'] != '') {
						$lists = '';
						foreach ($result->response as $list) {
							if ($_POST['list'] == $list->ListID) {
								$lists .= '<option value="' . $list->ListID . '" selected="selected">' .  $list->Name . '</option>';
							} else {
								$lists .= '<option value="' . $list->ListID . '">' .  $list->Name . '</option>';
							}
						}
					}
				}
			}
		}

		echo json_encode(array('clients' => $clients, 'lists' => $lists));

		die(); // this is required to terminate immediately and return a proper response
	}

	/**
	 * Update all Campaign Monitor Lists and display in settings
	 *
	 */
	public function update_campaignmonitor_lists() {

		$lists = '';

		if (isset($_POST['api_key']) && $_POST['api_key'] != '' &&
			isset($_POST['client_id']) && $_POST['client_id'] != '') {

			require_once( 'includes/campaignmonitor/csrest_general.php');
			require_once( 'includes/campaignmonitor/csrest_clients.php');


			$wrap = new CS_REST_Clients(sanitize_text_field( $_POST['client_id'] ), sanitize_text_field( $_POST['api_key'] ));
			$result = $wrap->get_lists();


			if ($result->was_successful()) {
				foreach ($result->response as $list) {
					$lists .= '<option value="' . $list->ListID . '">' .  $list->Name . '</option>';
				}
			}
		}

		echo $lists;

		die(); // this is required to terminate immediately and return a proper response
	}

	/**
	 * Get all Mad Mimi Lists and display in settings
	 *
	 */
	public function get_madmimi_lists() {

		$options = '';

		if (isset($_POST['api_key']) && $_POST['api_key'] != '' &&
			isset($_POST['username']) && $_POST['username'] != '') {

			require_once( 'includes/madmimi/MadMimi.class.php');

			$mailer = new MadMimi(sanitize_text_field( $_POST['username'] ), sanitize_text_field( $_POST['api_key'] ) );

			if (isset($mailer)) {
				try {
					$lists = $mailer->Lists();
					$lists  = new SimpleXMLElement($lists);

				    if ($lists->list) {
						foreach ($lists->list as $l) {
							$options .= '<option value="' . $l->attributes()->{'name'}->{0} . '">' .  $l->attributes()->{'name'}->{0} . '</option>';
						}
				    }

				    if (isset($_POST['list']) && $_POST['list'] != '') {
					    $options = '';
						foreach ($lists->list as $l) {

							if ($_POST['list'] == $l->attributes()->{'name'}->{0}) {
								$options .= '<option value="' . $l->attributes()->{'name'}->{0} . '" selected="selected">' .  $l->attributes()->{'name'}->{0} . '</option>';
							} else {
								$options .= '<option value="' . $l->attributes()->{'name'}->{0} . '">' .  $l->attributes()->{'name'}->{0} . '</option>';
							}
						}
					}
				} catch (Exception $exc) {}
			}
		}

		echo $options;

		die(); // this is required to terminate immediately and return a proper response
	}

	function check_countdown_disable() {
		if (empty($this->settings['enabled']))
			return;

		if (empty($this->settings['countdown']['schedule_disable']))
			return;

		if (!in_array('countdown', $this->get_template_active_elements()))
			return;

		if (time() > $this->settings['countdown']['date']) {
			$this->settings['enabled'] = 0;
			update_option( 'wplauncher_options', $this->settings );
		}

	}

	function user_can_access_site() {
		//if ($this->settings['user_access'] == 'administrator') {
			return current_user_can( 'administrator' );
		//} else {
		//	return is_user_logged_in();
		//}
	}
}