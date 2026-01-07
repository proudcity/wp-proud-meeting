<?php

/**
Plugin Name: Proud Meeting
Plugin URI: http://proudcity.com/
Description: Declares an Meeting custom post type.
Version: 2026.01.07.1419
Author: ProudCity
Author URI: http://proudcity.com/
License: Affero GPL v3
 */

namespace Proud\Meeting;

if (! defined('ABSPATH')) exit;

// Load Extendible
// -----------------------
if (! class_exists('ProudPlugin')) {
	require_once(plugin_dir_path(__FILE__) . '../wp-proud-core/proud-plugin.class.php');
}


require_once plugin_dir_path(__FILE__) . '/inc/meeting-settings.php';

class ProudMeeting extends \ProudPlugin
{
	public function __construct()
	{
		parent::__construct(array(
			'textdomain'     => 'wp-proud-meeting',
			'plugin_path'    => __FILE__,
		));

		//$this->post_type = 'meeting';
		//$this->taxonomy = 'meeting-taxonomy';

		$this->hook('init', 'create_meeting');
		$this->hook('rest_api_init', 'meeting_rest_support');
		$this->hook('init', 'create_taxonomy');

		add_action('init', array($this, 'add_meeting_feed'), 10);

		add_filter('wp_insert_post_data', array($this, 'meeting_presave'), '99', 2);

		add_filter('manage_meeting_posts_columns', array($this, 'set_meeting_columns'));
		add_action('manage_meeting_posts_custom_columns', array($this, 'set_meeting_author'), 10, 2);

		add_action('wp_ajax_proud_track_metabox_change', array($this, 'track_meeting_modified'));
	}

	public function track_meeting_modified()
	{
		check_ajax_referer('proud_track_metabox_change', 'nonce');
		update_option('sfn_test', 'updating meeting ' . time());
		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		if (! $post_id) {
			wp_send_json_error('Missing post_id');
		}

		if (! current_user_can('edit_post', $post_id)) {
			wp_send_json_error('Permission denied');
		}

		$meta_key   = sanitize_key($_POST['meta_key'] ?? '');


		update_post_meta(absint($post_id), esc_attr($meta_key), time());

		wp_send_json_success();
	}

	public function add_meeting_feed()
	{
		add_feed('proudcity-meetings', array($this, 'generate_meeting_feed'));
	}

	public function generate_meeting_feed()
	{
		include_once 'feed-meeting.php';
	}

	/**
	 * Adds custom column to meetings so we can display who is hosting the meeting
	 *
	 * @since 2022.12.08
	 * @author Curtis
	 *
	 * @param       array           $columns            required            Array of columns
	 * @return      array           $columns                                Our modified array
	 */
	public function set_meeting_columns($columns)
	{
		$columns['author'] = 'Meeting Host';
		return $columns;
	}

	/**
	 * Sets the content in our custom column
	 *
	 * @since 2022.12.08
	 * @author Curtis
	 *
	 * @param       string          $column         required            The column we need data for
	 * @param       int             $post_id        required            the post we're currently looping through
	 * @uses        get_post_field()                                    Retrieves field from wp_posts given value and post_id
	 * @uses        get_the_author_meta()                               Returns author meta given value and author_id
	 * @uses        absint()                                            no negative numbers
	 */
	public function set_meeting_author($column, $post_id)
	{

		if ('author' === $column) {
			$author_id = get_post_field('post_author', absint($post_id));
			echo esc_attr(get_the_author_meta('display_name', absint($author_id)));
		}
	}

	public function create_meeting()
	{
		$labels = array(
			'name'               => _x('Meetings', 'post name', 'wp-meeting'),
			'singular_name'      => _x('Meeting', 'post type singular name', 'wp-meeting'),
			'menu_name'          => _x('Meetings', 'admin menu', 'wp-meeting'),
			'name_admin_bar'     => _x('Meeting', 'add new on admin bar', 'wp-meeting'),
			'add_new'            => _x('Add New', 'meeting', 'wp-meeting'),
			'add_new_item'       => __('Add New Meeting', 'wp-meeting'),
			'new_item'           => __('New Meeting', 'wp-meeting'),
			'edit_item'          => __('Edit Meeting', 'wp-meeting'),
			'view_item'          => __('View Meeting', 'wp-meeting'),
			'all_items'          => __('All Meetings', 'wp-meeting'),
			'search_items'       => __('Search meeting', 'wp-meeting'),
			'parent_item_colon'  => __('Parent meeting:', 'wp-meeting'),
			'not_found'          => __('No meetings found.', 'wp-meeting'),
			'not_found_in_trash' => __('No meetings found in Trash.', 'wp-meeting')
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __('Description.', 'wp-meeting'),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_icon'         => 'dashicons-groups',
			'query_var'          => true,
			'rewrite'            => array('slug' => 'meetings', 'feeds' => true, 'with_front' => false),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'show_in_rest'       => true,
			'rest_base'          => 'meetings',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'supports'           => array('title', 'thumbnail',)
		);

		register_post_type('meeting', $args);
	}

	public function create_taxonomy()
	{
		register_taxonomy(
			'meeting-taxonomy',
			'meeting',
			array(
				'labels' => array(
					'name' => 'Meeting Categories',
					'add_new_item' => 'Add New Meeting Category',
					'new_item_name' => "New Meeting"
				),
				'show_ui' => true,
				'show_tagcloud' => false,
				'hierarchical' => true
			)
		);
	}

	public function meeting_rest_support()
	{
		register_rest_field(
			'meeting',
			'meta',
			array(
				'get_callback'    => array($this, 'meeting_rest_metadata'),
				'update_callback' => null,
				'schema'          => null,
			)
		);
	}

	/**
	 * Alter the REST endpoint.
	 * Add metadata to the post response
	 */
	public function meeting_rest_metadata($object, $field_name, $request) {}

	/**
	 * Alter the post_content pre-save to add all of our metafields for searching
	 */
	public function meeting_presave($data, $postarr)
	{

		if ($data['post_type'] !== 'meeting') {
			return $data;
		}

		// These are the fieldsets we care about
		foreach (['datetime', 'agenda', 'agenda_packet', 'minutes'] as $field) {

			if (isset($postarr["form-meeting_$field"])) {

				$fields = reset($postarr["form-meeting_$field"]);
				$title = ucfirst(str_replace('_', ' ', $field));

				switch ($field) {
					case 'datetime':
						$data['post_content'] .= "Date and time: " . $fields['datetime'] . '<br/>';
						$obj_location = get_post($fields['location']);
						if (!empty($obj_location)) {
							$data['post_content'] .= "Location: " . $obj_location->post_title . '<br/>';
						}
						$obj_agency = get_post($fields['agency']);
						if (!empty($obj_agency)) {
							$data['post_content'] .= "Department: " . $obj_agency->post_title . '<br/>';
						}
						break;

					default:
						$text = $fields[$field];
						if (!empty($text)) {
							$data['post_content'] .= '<h2>' . $title . '</h2>' . $text;
						}

						$attachment = $fields[$field . '_attachment'];
						if (!empty($text)) {
							// @todo Alex save attachment to elastic
						}
						break;
				} //switch

			} // isset

		} // foreach

		return $data;
	}
} // class
new ProudMeeting();

// MeetingAddress meta box
if (class_exists('ProudMetaBox')) {
	class MeetingDetails extends \ProudMetaBox
	{
		public $options = [  // Meta options, key => default
			'datetime' => '',
			'location' => '',
			'agency' => '',
		];

		public function __construct()
		{
			parent::__construct(
				'meeting_datetime', // key
				'Details', // title
				'meeting', // screen
				'normal',  // position
				'high' // priority
			);
		}

		/**
		 * Called on form creation
		 * @param $displaying : false if just building form, true if about to display
		 * Use displaying:true to do any difficult loading that should only occur when
		 * the form actually will display
		 */
		public function set_fields($displaying)
		{
			// Already set, no loading necessary
			if ($displaying) {
				return;
			}

			// Get locations
			$locations = get_posts([
				'post_type' => 'proud_location',
				'orderby' => 'post_title',
				'posts_per_page' => 1000
			]);
			$location_options = ['' => '- Select one -'];
			if (!empty($locations) && empty($locations['errors'])) {
				foreach ($locations as $location) {
					$location_options[$location->ID] = $location->post_title;
				}
			}

			// Get Agencies
			$agencies = get_posts([
				'post_type' => 'agency',
				'orderby' => 'post_title',
				'posts_per_page' => 1000
			]);
			$agency_options = ['' => '- Select one -'];
			if (!empty($agencies) && empty($agencies['errors'])) {
				foreach ($agencies as $agency) {
					$agency_options[$agency->ID] = $agency->post_title;
				}
			}

			$this->fields = [
				'datetime' => [
					'#type' => 'text',
					'#title' => __pcHelp('Date and Time'),
				],
				'location' => [
					'#type' => 'select',
					'#options' => $location_options,
					'#title' => __pcHelp('Location'),
					'#description' => __pcHelp('<a href="/wp-admin/edit.php?post_type=proud_location" target="_blank">Manage Locations</a>'),
				],
				'agency' => [
					'#type' => 'select',
					'#options' => $agency_options,
					'#title' => _x('Agency', 'post type singular name', 'wp-agency'),
					'#description' => __pcHelp('<a href="/wp-admin/edit.php?post_type=agency" target="_blank">Manage ' . _x('Agencies', 'post name', 'wp-agency') . '</a>'),
				],


				//@todo: location
			];
		}

		/**
		 * Saves form values
		 */
		public function save_meta($post_id, $post, $update)
		{
			// Grab form values from Request
			$values = $this->validate_values($post);

			// let's make sure we have a value before we try to do anything with it
			if (isset($values['datetime']) && ! empty($values['datetime'])) {
				$values['datetime'] = !empty(strtotime($values['datetime'])) ? date('Y-m-d H:i', strtotime($values['datetime'])) : '';
			}

			if (!empty($values)) {
				$this->save_all($values, $post_id);
			}
		}
	}
	if (is_admin()) {
		new MeetingDetails();
	}
}


// MeetingAddress meta box
if (class_exists('ProudMetaBox')) {
	class MeetingAgenda extends \ProudMetaBox
	{
		public $options = [  // Meta options, key => default
			'agenda' => '',
			'agenda_attachment' => '',
			'agenda_attachment_meta' => '',
			'agenda_attachment_preview' => '1',
		];

		public function __construct()
		{
			parent::__construct(
				'meeting_agenda', // key
				'Agenda', // title
				'meeting', // screen
				'normal',  // position
				'high' // priority
			);
		}

		/**
		 * Called on form creation
		 * @param $displaying : false if just building form, true if about to display
		 * Use displaying:true to do any difficult loading that should only occur when
		 * the form actually will display
		 */
		public function set_fields($displaying)
		{
			// Already set, no loading necessary
			if ($displaying) {
				return;
			}

			$this->fields = [
				//      'agenda_wrapper' => [
				//        '#type' => 'html',
				//        '#html' => '<div id="agenda-wrapper"></div>'
				//      ],
				'agenda' => [
					'#type' => 'editor',
					'#title' => __pcHelp('Agenda Text'),
				],
				'agenda_attachment' => [
					'#type' => 'select_file',
					'#title' => __pcHelp('Attachment'),
				],
				'agenda_attachment_meta' => [
					'#type' => 'hidden',
				],
				'agenda_attachment_preview' => [
					'#type' => 'checkbox',
					'#title' => 'Show preview',
					'#replace_title' => 'Show the embedded document preview',
					'#default_value' => '1',
					'#return_value' => '1',
				],

			];
		}

		/**
		 * Saves form values
		 */
		public function save_meta($post_id, $post, $update)
		{
			// Grab form values from Request
			$values = $this->validate_values($post);

			if (!empty($values)) {
				// Build file meta info for elastic
				// @TODO add processing for non-stateless
				if (!empty($values['agenda_attachment'])) {
					$stateless_meta = \Proud\Core\getStatelessFileMeta($values['agenda_attachment']);

					try {
						$values['agenda_attachment_meta'] = json_encode($stateless_meta);
					} catch (\Exception $e) {
						error_log($e);
					}
				}

				$this->save_all($values, $post_id);
			}
		}
	}
	if (is_admin()) {
		new MeetingAgenda();
	}
}


// MeetingAddress meta box
if (class_exists('ProudMetaBox')) {
	class MeetingAgendaPacket extends \ProudMetaBox
	{
		public $options = [  // Meta options, key => default
			'agenda_packet' => '',
			'agenda_packet_attachment' => '',
			'agenda_packet_attachment_meta' => '',
			'agenda_packet_attachment_preview' => '1',
		];

		public function __construct()
		{
			parent::__construct(
				'meeting_agenda_packet', // key
				'Agenda Packet', // title
				'meeting', // screen
				'normal',  // position
				'high' // priority
			);
		}

		/**
		 * Called on form creation
		 * @param $displaying : false if just building form, true if about to display
		 * Use displaying:true to do any difficult loading that should only occur when
		 * the form actually will display
		 */
		public function set_fields($displaying)
		{
			// Already set, no loading necessary
			if ($displaying) {
				return;
			}

			$this->fields = [
				'agenda_packet' => [
					'#type' => 'editor',
					'#title' => __pcHelp('Agenda Packet Text'),
				],
				'agenda_packet_attachment' => [
					'#type' => 'select_file',
					'#title' => __pcHelp('Attachment'),
				],
				'agenda_packet_attachment_meta' => [
					'#type' => 'hidden',
				],
				'agenda_packet_attachment_preview' => [
					'#type' => 'checkbox',
					'#title' => 'Show preview',
					'#replace_title' => 'Show the embedded document preview',
					'#default_value' => '1',
					'#return_value' => '1',
				],

			];
		}

		/**
		 * Saves form values
		 */
		public function save_meta($post_id, $post, $update)
		{
			// Grab form values from Request
			$values = $this->validate_values($post);

			if (!empty($values)) {
				// Build file meta info for elastic
				// @TODO add processing for non-stateless
				if (!empty($values['agenda_packet_attachment'])) {
					$stateless_meta = \Proud\Core\getStatelessFileMeta($values['agenda_packet_attachment']);

					try {
						$values['agenda_packet_attachment_meta'] = json_encode($stateless_meta);
					} catch (\Exception $e) {
						error_log($e);
					}
				}

				$this->save_all($values, $post_id);
			}
		}
	}
	if (is_admin()) {
		new MeetingAgendaPacket();
	}
}


// MeetingAddress meta box
if (class_exists('ProudMetaBox')) {
	class MeetingMinutes extends \ProudMetaBox
	{
		public $options = [  // Meta options, key => default
			'minutes' => '',
			'minutes_attachment' => '',
			'minutes_attachment_meta' => '',
			'minutes_attachment_preview' => '1',
		];

		public function __construct()
		{
			parent::__construct(
				'meeting_minutes', // key
				'Minutes', // title
				'meeting', // screen
				'normal',  // position
				'high' // priority
			);
		}

		/**
		 * Called on form creation
		 * @param $displaying : false if just building form, true if about to display
		 * Use displaying:true to do any difficult loading that should only occur when
		 * the form actually will display
		 */
		public function set_fields($displaying)
		{
			// Already set, no loading necessary
			if ($displaying) {
				return;
			}

			$this->fields = [
				'minutes' => [
					'#type' => 'editor',
					'#title' => __pcHelp('Minutes Text'),
				],
				'minutes_attachment' => [
					'#type' => 'select_file',
					'#title' => __pcHelp('Attachment'),
				],
				'minutes_attachment_meta' => [
					'#type' => 'hidden',
				],
				'minutes_attachment_preview' => [
					'#type' => 'checkbox',
					'#title' => 'Show preview',
					'#replace_title' => 'Show the embedded document preview',
					'#default_value' => '1',
					'#return_value' => '1',
				],
			];
		}


		/**
		 * Saves form values
		 */
		public function save_meta($post_id, $post, $update)
		{
			// Grab form values from Request
			$values = $this->validate_values($post);

			if (!empty($values)) {
				// Build file meta info for elastic
				// @TODO add processing for non-stateless
				if (!empty($values['minutes_attachment'])) {
					$stateless_meta = \Proud\Core\getStatelessFileMeta($values['minutes_attachment']);

					try {
						$values['minutes_attachment_meta'] = json_encode($stateless_meta);
					} catch (\Exception $e) {
						error_log($e);
					}
				}

				$this->save_all($values, $post_id);
			}
		}
	}
	if (is_admin()) {
		new MeetingMinutes();
	}
}


// MeetingVideo meta box
if (class_exists('ProudMetaBox')) {
	class MeetingVideo extends \ProudMetaBox
	{
		public $options = [  // Meta options, key => default
			'video_style' => '',
			'video' => '',
			'youtube_bookmarks' => '',
			'external_video' => '',
		];

		public function __construct()
		{
			parent::__construct(
				'meeting_video', // key
				'Video', // title
				'meeting', // screen
				'normal',  // position
				'high' // priority
			);
		}

		/**
		 * Called on form creation
		 * @param $displaying : false if just building form, true if about to display
		 * Use displaying:true to do any difficult loading that should only occur when
		 * the form actually will display
		 */
		public function set_fields($displaying)
		{
			// Already set, no loading necessary
			if ($displaying) {
				return;
			}
			$path = plugins_url('assets/', __FILE__);

			$this->fields = [
				'video_style' => [
					'#title' => __('Video Type', 'wp-proud-core'),
					'#type'    => 'radios',
					'#default_value'     => '',
					'#options' => [
						'' => __('Embedded Youtube Player', 'wp-proud-core'),
						'external' => __('Link out to external webpage', 'wp-proud-core'),
					],
				],
				'video' => [
					'#type' => 'text',
					'#title' => __pcHelp('YouTube Video'),
					'#description' =>  __pcHelp('Enter the URL or ID of the YouTube video'),
					'#states' => [
						'visible' => [
							'video_style' => [
								'operator' => '!=',
								'value' => ['external'],
								'glue' => '&&'
							],
						],
					],
				],
				'external_video' => [
					'#type' => 'text',
					'#title' => __pcHelp('External Video'),
					'#description' =>  __pcHelp('Enter the URL to open in a new tab'),
					'#states' => [
						'visible' => [
							'video_style' => [
								'operator' => '==',
								'value' => ['external'],
								'glue' => '&&'
							],
						],
					],
				],
				'youtube_bookmarks' => [
					'#title' => __pcHelp('bookmarks'),
					'#type' => 'text',
				],
				'youtube_bookmarks_html' => [
					'#type' => 'html',
					'#html' => file_get_contents(__DIR__ . '/assets/html/youtube-bookmarks.php'),
				],
			];
		}


		/**
		 * Prints form

		 */
		public function settings_content($post)
		{
			parent::settings_content($post);
			// Enqueue JS
			$path = plugins_url('assets/', __FILE__);
			wp_enqueue_script('moment-js', $path . 'vendor/bootstrap-datetimepicker/moment.min.js');
			wp_enqueue_style('glyphicons-css', '//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-glyphicons.css');
			wp_enqueue_script('bootstrap-datetimepicker-js', $path . 'vendor/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js');
			wp_enqueue_style('bootstrap-datetimepicker-css', $path . 'vendor/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css');
			wp_enqueue_script('youtube-api', '//www.youtube.com/iframe_api');
			wp_enqueue_script('handlebars', $path . 'vendor/handlebars.min.js');
			wp_enqueue_style('proud-meeting-css', $path . 'css/proud-meeting.css');
			wp_enqueue_script('proud-meeting-youtube-bookmarks-js', $path . 'js/youtube-bookmarks.js');

			wp_enqueue_script('proud-meeting-js', $path . 'js/proud-meeting.js');
			wp_localize_script('proud-meeting-js', 'ProudMeeting', [
				'proudMeetingNonce' => wp_create_nonce('proud_track_metabox_change')
			]);

			//    // Get field ids
			//    $options = $this->get_field_ids();
			//    // Set global lat / lng
			//    $options['lat'] = get_option('lat', true);
			//    $options['lng'] = get_option('lng', true);
			//    wp_localize_script( 'google-places-field', 'meeting', $options );
			//    wp_enqueue_script( 'google-places-field' );

		}


		/**
		 * Saves form values
		 */
		public function save_meta($post_id, $post, $update)
		{
			// Grab form values from Request
			$values = $this->validate_values($post);

			if (!empty($values)) {
				$this->save_all($values, $post_id);
			}
		}
	}
	if (is_admin()) {
		new MeetingVideo();
	}
}

// MeetingAudio meta box
if (class_exists('ProudMetaBox')) {
	class MeetingAudio extends \ProudMetaBox
	{
		public $options = [  // Meta options, key => default
			'audio' => '',
		];

		public function __construct()
		{
			parent::__construct(
				'meeting_audio', // key
				'Audio', // title
				'meeting', // screen
				'normal',  // position
				'high' // priority
			);
		}

		/**
		 * Called on form creation
		 * @param $displaying : false if just building form, true if about to display
		 * Use displaying:true to do any difficult loading that should only occur when
		 * the form actually will display
		 */
		public function set_fields($displaying)
		{
			// Already set, no loading necessary
			if ($displaying) {
				return;
			}
			$path = plugins_url('assets/', __FILE__);

			$this->fields = [
				'audio' => [
					'#type' => 'text',
					'#title' => __pcHelp('SoundCloud Embed Code'),
					'#placeholder' => '<iframe ...',
					'#description' =>  __pcHelp('Enter the Embed Code by clicking on Share > Embed within SoundCloud. <a href="https://help.soundcloud.com/hc/en-us/articles/115003565128-Embedding-a-track-or-playlist-on-WordPress" target="_blank">Learn more</a>.'),
				]
			];
		}

		/**
		 * Saves form values
		 */
		public function save_meta($post_id, $post, $update)
		{
			// Grab form values from Request
			$values = $this->validate_values($post);
			if (!empty($values)) {
				$this->save_all($values, $post_id);
			}
		}
	}
	if (is_admin()) {
		new MeetingAudio();
	}
}

// Meeting desc meta box (empty for body)
if (class_exists('ProudMetaBox')) {
	class MeetingCategory extends \ProudTermMetaBox
	{
		public $options = [  // Meta options, key => default
			'icon' => '',
			'color' => '',
		];

		public function __construct()
		{
			parent::__construct(
				'meeting-taxonomy', // key
				'Settings' // title
			);
		}

		private function colors()
		{
			return [
				'' => ' - Select - ',
				'#ED9356' => 'Orange',
				'#456D9C' => 'Blue',
				'#E76C6D' => 'Red',
				'#5A97C4' => 'Dark blue',
				'#4DC3FF' => 'Baby blue',
				'#9BBF6A' => 'Green',
			];
		}

		/**
		 * Called on form creation
		 * @param $displaying : false if just building form, true if about to display
		 * Use displaying:true to do any difficult loading that should only occur when
		 * the form actually will display
		 */
		public function set_fields($displaying)
		{
			// Already set, no loading necessary
			if ($displaying) {
				return;
			}
			global $proudcore;

			$this->fields = [
				'icon' => [
					'#title' => 'Icon',
					'#type' => 'fa-icon',
					'#default_value' => '',
					'#to_js_settings' => false
				],
				'color' => [
					'#title' => 'Color',
					'#type' => 'select',
					'#options' => $this->colors(),
					'#default_value' => '',
					'#to_js_settings' => false
				],
				'markup' => [
					'#type' => 'html',
					'#html' => '<style type="text/css">.term-description-wrap { display: none; }</style>',
				],
			];
		}

		/**
		 * Includes extra files
		 *
		 * @since  2025.10.21
		 * @author Curtis <curtis@proudcity.com>
		 *
		 * @return null
		 */
		public function includes() {}
	}
	if (is_admin()) {
		new MeetingCategory();
	}
}
