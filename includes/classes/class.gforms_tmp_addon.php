<?php
if (class_exists( "GFForms" )) {
	GFForms::include_addon_framework();
	class GForms_TMP_AddOn extends GFAddOn {
		
		/**
		 *
		 * @var String
		 */
		protected $_version = "1.0";
		
		/**
		 *
		 * @var String
		 */
		protected $_min_gravityforms_version = "1.7.9999";
		
		/**
		 *
		 * @var String
		 */
		protected $_slug = "gforms-tmp-addon";
		
		/**
		 *
		 * @var String
		 */
		protected $_path = "gforms_tmp/includes/classes/class.gforms_tmp_addon.php";
		
		/**
		 *
		 * @var String
		 */
		protected $_full_path = __FILE__;
		
		/**
		 *
		 * @var String
		 */
		protected $_title = "GravityForms Lead Add-on";
		
		/**
		 *
		 * @var String
		 */
		protected $_short_title = "GForms Leads";
		
		/**
		 *
		 * @var String
		 */
		protected $plugin_path;
		
		/**
		 *
		 * @var GForms_TMP
		 */
		protected $gforms_tmp;
		
		/**
		 * Constructor
		 */
		public function __construct() {
			$this->_path = self::addon_local_path();
			$this->plugin_path = plugin_dir_path( dirname( dirname( __FILE__ ) ) );
			
			parent::__construct();
			$this->init();
		}
		
		/**
		 * Init
		 *
		 * @return void
		 */
		public function init() {
			parent::init();
			
			$gforms_tmp_admin_forms = get_option( 'gforms_tmp_admin_forms', false );
			
			if ($gforms_tmp_admin_forms && is_array( $gforms_tmp_admin_forms )) {
				foreach ( $gforms_tmp_admin_forms as $form_id ) {
					add_action( 'gform_after_submission_' . $form_id, array (
							$this,
							'maybe_api_submit' 
					), 10, 2 );
				}
			} else {
				add_action( 'gform_after_submission', array (
						$this,
						'maybe_api_submit' 
				), 10, 2 );
			}
		}
		
		/**
		 * Get File Local Path
		 *
		 * @return string
		 */
		public static function addon_local_path() {
			return plugin_basename( __FILE__ );
		}
		
		/**
		 * Setup Form Fields for Mapping
		 *
		 * @param array $form        	
		 * @return array
		 */
		public function form_settings_fields($form) {
			$fields = $this->form_mapping_fields( $form );
			return array (
					array (
							"title" => "GravityForms Lead Form Settings",
							"fields" => array (
									array (
											"label" => "Enable Custom Field Mapping",
											"type" => "checkbox",
											"name" => "enabled",
											"tooltip" => "Enable if using custom fields / field names.",
											"choices" => array (
													array (
															"label" => "Enabled",
															"name" => "enabled" 
													) 
											) 
									) 
							) 
					),
					array (
							"title" => "Field Mapping",
							"fields" => $fields 
					) 
			);
		}
		
		/**
		 * Return Mapping Form Fields
		 *
		 * @param array $form        	
		 * @return array
		 */
		protected function form_mapping_fields($form) {
			$settings = array ();
			
			$data = file_get_contents( $this->plugin_path . '/data/fields.json' );
			
			$api_fields = json_decode( $data, true );
			
			$api_field_labels = array ();
			
			$form_fields = array ();
			
			$question_labels = array ();
			
			$choices = array (
					0 => array (
							"label" => "Choose Field",
							"value" => "" 
					) 
			);
			
			if ($form && is_array( $form ) && isset( $form ['fields'] )) {
				$idx = 1;
				foreach ( $form ['fields'] as $field ) {
					if (in_array( $field->type, [ 
							'name',
							'text',
							'select',
							'email',
							'phone',
							'radio',
							'textarea',
							'checkbox' 
					] )) {
						$form_fields [$idx] = $field->label;
						$choices [$idx] = array (
								"label" => $field->label,
								"value" => $field->label 
						);
						$idx ++;
					}
				}
				
				$default = array (
						"type" => "select",
						"choices" => $choices 
				);
				
				if ($api_fields && is_array( $api_fields ) && isset( $api_fields ['fields'] )) {
					$api_field_labels = array_reduce( $api_fields ['fields'], function ($labels, $field) {
						$labels [] = trim( $field ['label'] );
						return $labels;
					} );
					
					$question_labels = array_diff( $form_fields, $api_field_labels );
					$idx = 0;
					foreach ( $api_fields ['fields'] as $idx => $field ) {
						switch ($field ['name']) {
							default :
								$select = array_merge( $default, array (
										"label" => trim( $field ['label'] ),
										"name" => trim( $field ['name'] ),
										"tooltip" => "Select Mapping for " . trim( $field ['label'] ),
										"default_value" => trim( $field ['label'] ) 
								) );
								$settings [$idx] = $select;
								break;
						}
					}
				}
			}
			
			return $settings;
		}
		
		/**
		 * Return Default Mapping Form Fields
		 *
		 * @param array $form        	
		 * @return array
		 */
		protected function default_form_mapping_fields($form) {
			$settings = array ();
			
			$data = file_get_contents( $this->plugin_path . '/data/fields.json' );
			
			$api_fields = json_decode( $data, true );
			
			$api_field_labels = array ();
			
			$form_fields = array ();
			
			if ($form && is_array( $form ) && isset( $form ['fields'] )) {
				$idx = 1;
				foreach ( $form ['fields'] as $field ) {
					if (in_array( $field->type, [ 
							'name',
							'text',
							'select',
							'email',
							'phone',
							'radio',
							'textarea',
							'checkbox' 
					] )) {
						$form_fields [$idx] = $field->label;
						$idx ++;
					}
				}
				
				if ($api_fields && is_array( $api_fields ) && isset( $api_fields ['fields'] )) {
					$api_field_labels = array_reduce( $api_fields ['fields'], function ($labels, $field) {
						$labels [] = trim( $field ['label'] );
						return $labels;
					} );
					
					// $additional_labels = array_diff($form_fields,
					// $api_field_labels);
					
					foreach ( $api_fields ['fields'] as $idx => $field ) {
						$settings [$field ['name']] = trim( $field ['label'] );
					}
				}
			}
			
			return $settings;
		}
		
		/**
		 * Map Form Entries to Form Fields
		 *
		 * @param array $form        	
		 * @return array
		 */
		protected function map_entry_fields($form) {
			$settings = $this->get_form_settings( $form );
			
			if (! $settings || isset( $settings ['enabled'] ) === false) {
				$settings = $this->default_form_mapping_fields( $form );
			}
			
			unset( $settings ['enabled'] );
			return $settings;
		}
		
		/**
		 * Remove admin fields from entry array
		 *
		 * @param array $entry        	
		 *
		 * @return array
		 */
		protected function clean_entry($entry) {
			$additional_fields = [ 
					'ip',
					'source_url',
					'user_agent' 
			];
			reset( $entry );
			return array_filter( $entry, function ($v) use($additional_fields, &$entry) {
				$k = key( $entry );
				next( $entry );
				return is_numeric( $k ) || in_array( $k, $additional_fields );
			} );
		}
		
		/**
		 * Generate Lead Entity from Submission
		 *
		 * @param object $entry
		 *        	Submission Entry
		 * @param array $form
		 *        	Form Array
		 * @return array
		 */
		protected function build_submission_lead($entry, $form) {
			$lead = array ();
			
			$gforms_tmp_admin_client_name = get_option( 'gforms_tmp_admin_client_name', null );
			
			$gforms_tmp_admin_client_id = get_option( 'gforms_tmp_admin_client_id', null );
			
			$lead ['company'] = $gforms_tmp_admin_client_name;
			
			$lead ['companyid'] = $gforms_tmp_admin_client_id;
			
			$lead ['ipaddress'] = rgar( $entry, 'ip' );
			
			$lead ['referrer'] = rgar( $entry, 'source_url' );
			
			$lead ['timecreated'] = date( 'Y-m-d H:i:s' );
			
			return $lead;
		}
		
		/**
		 * Generate Detail Entity from Submission
		 *
		 * @param object $entry
		 *        	Submission Entry
		 * @param array $form
		 *        	Form Array
		 * @return array
		 */
		protected function build_submission_detail($entry, $form) {
			$detail = false;
			$data = $entry;
			
			$map = $this->map_entry_fields( $form );
			
			$labels = array_reduce( $form ['fields'], function ($fields, $field) {
				if (in_array( $field->type, [ 
						'name',
						'text',
						'select',
						'email',
						'phone',
						'radio',
						'textarea',
						'checkbox' 
				] )) {
					$fields [$field->id] = $field->label;
				}
				return $fields;
			} );
			$values = array (
					"form" => array (),
					"api" => array () 
			);
			
			$entry_ids = [ ];
			
			foreach ( $labels as $id => $label ) {
				if (isset( $data [$id] )) {
					if (is_array( $data [$id] )) {
						$values ["form"] [$label] = implode( "; ", $data [$id] );
					} else {
						$values ["form"] [$label] = $data [$id];
					}
				} elseif (count( preg_grep( '/^' . preg_quote( $id ) . '\./', array_keys( $data ) ) ) > 0) {
					$subkeys = preg_grep( '/^' . preg_quote( $id ) . '\./', array_keys( $data ) );
					$subvalues = [ ];
					foreach ( $subkeys as $subkey ) {
						$subvalues [] = $data [$subkey];
					}
					$values ["form"] [$label] = implode( "; ", array_filter( $subvalues ) );
				} else {
					$values ["form"] [$label] = null;
				}
				$entry_ids [$label] = $id;
			}
			
			foreach ( $map as $field => $label ) {
				if (isset( $values ["form"] [$label] )) {
					$values ["api"] [$field] = $values ["form"] [$label];
					if (isset( $entry_ids [$label] )) {
						unset( $entry_ids [$label] );
					}
				} else {
					$values ["api"] [$field] = null;
				}
			}
			
			foreach ( $entry_ids as $label => $entry_id ) {
				if (isset( $data [$entry_id] )) {
					if (is_array( $data [$entry_id] )) {
						$values ["api"] [$label] = implode( "; ", $data [$entry_id] );
					} else {
						$values ["api"] [$label] = $data [$entry_id];
					}
				} elseif (count( preg_grep( '/^' . preg_quote( $entry_id ) . '\./', array_keys( $data ) ) ) > 0) {
					$subkeys = preg_grep( '/^' . preg_quote( $entry_id ) . '\./', array_keys( $data ) );
					$subvalues = [ ];
					foreach ( $subkeys as $subkey ) {
						$subvalues [] = $data [$subkey];
					}
					$values ["api"] [$label] = implode( "; ", array_filter( $subvalues ) );
				}
			}
			
			if (isset( $values ['api'] )) {
				$detail = $values ['api'];
			}
			
			return $detail;
		}
		
		/**
		 * Acquire Plugin Class (GForms_TMP) instance
		 *
		 * @return GForms_TMP
		 */
		protected function get_gforms_tmp() {
			if (! $this->gforms_tmp) {
				$this->gforms_tmp = apply_filters( 'gforms_tmp_class_instance', null );
			}
			return $this->gforms_tmp;
		}
		
		/**
		 * Conditionally handle API Submission
		 * and create Lead Post Type
		 *
		 * @param object $entry
		 *        	Submission Entry
		 * @param array $form
		 *        	Form Array
		 * @return int Post ID
		 */
		public function maybe_api_submit($entry, $form) {
			$gforms_tmp = $this->get_gforms_tmp();
			$clean = $this->clean_entry( $entry );
			
			$lead = array ();
			$lead ['lead'] = $this->build_submission_lead( $clean, $form );
			$lead ['attributes'] = $this->build_submission_detail( $clean, $form );
			
			if ($gforms_tmp->is_plugin_activated( true )) {
				$post_id = $this->api_submit( $lead );
			} elseif ($gforms_tmp->is_plugin_activated( false )) {
				$post = $this->get_post_data( $lead, null );
				$meta = $this->get_post_meta( $lead, null );
				
				$post_id = $this->wp_insert_custom_post( $post, $meta, false );
			}
			
			return $post_id;
		}
		
		/**
		 * Submit form entry to API
		 *
		 * @param array $lead
		 *        	Form Submission Entry
		 * @return int Post ID
		 */
		protected function api_submit($lead) {
			$response = null;
			
			$is_multisite = is_multisite();
			
			$gforms_tmp = $this->get_gforms_tmp();
			
			$oauth_provider = $gforms_tmp->get_oauth_provider();
			
			$gforms_tmp_admin_restapi_url = $gforms_tmp->get_oauth_host();
			
			$gforms_tmp_admin_restapi_submit_url = esc_url( parse_url( $gforms_tmp_admin_restapi_url, PHP_URL_SCHEME ) . "://" . parse_url( $gforms_tmp_admin_restapi_url, PHP_URL_HOST ) . "/rest-api/submit" );
			
			$token = $oauth_provider->token();
			
			$args = array (
					"method" => "POST",
					"timeout" => 60,
					"redirection" => 60,
					"body" => array_merge( $lead, array (
							"token" => $token 
					) ) 
			);
			
			if ($gforms_tmp_admin_restapi_submit_url && $token) {
				$_response = wp_remote_post( $gforms_tmp_admin_restapi_submit_url, $args );
				
				if (is_wp_error( $_response )) {
					$response = null;
				} else {
					if (isset( $_response ['response'] ) && $_response ['response'] ['code'] == 201) {
						if (isset( $_response ['body'] )) {
							$data = @json_decode( $_response ['body'], true );
							if ($data && isset( $data ['data'] )) {
								$response = $data ['data'];
							}
						}
					}
				}
			}
			
			$post = $this->get_post_data( $lead, $response );
			$meta = $this->get_post_meta( $lead, $response );
			
			return $this->wp_insert_custom_post( $post, $meta, false );
		}
		
		/**
		 * Insert or update a custom post type.
		 *
		 * @param PostTypeInterface $post        	
		 * @param bool $wp_error        	
		 *
		 * @return int|WP_Error
		 */
		public function wp_insert_custom_post($post, $meta, $wp_error = false) {
			$post_id = wp_insert_post( $post, $wp_error );
			
			if (0 === $post_id || $post_id instanceof WP_Error) {
				return $post_id;
			}
			
			foreach ( $meta as $key => $value ) {
				update_post_meta( $post_id, $key, $value );
			}
			
			return $post_id;
		}
		
		/**
		 * Extract data for post creation from API Submission data
		 *
		 * @param array $submission
		 *        	API Submission Data
		 * @return array Post data
		 */
		public function get_post_data($lead, $response = null) {
			$post = false;
			if ($lead && is_array( $lead )) {
				$title = isset( $lead ['attributes'] ['FirstName'], $lead ['attributes'] ['LastName'] ) ? $lead ['attributes'] ['FirstName'] . " " . $lead ['attributes'] ['LastName'] : "Unknown Applicant (" . date( 'F j, Y H:i:s' ) . ")";
				$content = $this->build_post_content( $lead, $response );
				$post_type = class_exists( 'TMP_Application' ) ? \TMP_Application::POST_TYPE : 'tmpleads';
				$author = get_user_by( 'email', get_option( 'admin_email' ) );
				$post = array (
						'post_content' => $content,
						'post_title' => $title,
						'post_status' => 'publish',
						'post_type' => $post_type,
						'author' => $author->ID 
				);
			}
			return $post;
		}
		
		/**
		 * Generate content for Lead Post Type
		 *
		 * @param array $lead
		 *        	API Submission Data
		 * @param array $response
		 *        	API Response Data
		 * @return string Lead Post Content
		 */
		protected function build_post_content($lead, $response = null) {
			$content = '';
			if (is_array( $lead )) {
				foreach ( $lead as $subtitle => $section ) {
					
					switch ($subtitle) {
						case 'attributes' :
							$additional_data = array_diff_key( $section, array_flip( [ 
									'FirstName',
									'LastName',
									'Email',
									'Phone',
									'City',
									'State' 
							] ) );
							$content .= '<h3>Applicant Details</h3>';
							$content .= '<table>';
							$content .= '<tr><th>Name</th>' . '<td>' . $section ['FirstName'] . ' ' . $section ['LastName'] . '</th></tr>';
							$content .= '<tr><th>Email</th>' . '<td>' . $section ['Email'] . '</th></tr>';
							$content .= '<tr><th>Phone</th>' . '<td>' . $section ['Phone'] . '</th></tr>';
							$content .= '<tr><th>Location</th>' . '<td>' . $section ['City'] . ', ' . $section ['State'] . '</th></tr>';
							foreach ( $additional_data as $label => $value ) {
								$content .= '<tr><th>' . $label . '</th>' . '<td>' . $value . '</th></tr>';
							}
							$content .= '</table>';
							$content .= '<hr />';
							$content .= '<p></p>';
							break;
						case 'lead' :
							$content .= '<h3>Application Details</h3>';
							$content .= '<table>';
							$content .= '<tr><th>Company</th>' . '<td>' . $section ['company'] . ' (' . $section ['companyid'] . ')' . '</th></tr>';
							$content .= '<tr><th>I.P. Address</th>' . '<td>' . $section ['ipaddress'] . '</th></tr>';
							$content .= '<tr><th>Referrer</th>' . '<td>' . $section ['referrer'] . '</th></tr>';
							$content .= '<tr><th>Created</th>' . '<td>' . date( 'F j, Y H:i:s', strtotime( $section ['timecreated'] ) ) . '</th></tr>';
							$content .= '</table>';
							$content .= '<hr />';
							$content .= '<p></p>';
							break;
					}
				}
				
				if (isset( $response ['tenstreet'] ['data'] ) && is_array( $response ['tenstreet'] ['data'] )) {
					$tenstreet = $response ['tenstreet'] ['data'];
					$content .= '<h3>Lead Details</h3>';
					$content .= '<table>';
					if (isset( $tenstreet ['submitted'] )) {
						$content .= '<tr><th>Application submitted</th>' . '<td>' . ($tenstreet ['submitted'] ? date( 'F j, Y H:i:s', strtotime( $tenstreet ['timesubmitted'] ) ) : 'Never') . '</th></tr>';
					}
					if (isset( $tenstreet ['lastresponse'] )) {
						$content .= '<tr><th>API Response</th>' . '<td>' . (isset( $tenstreet ['lastresponse'] ) ? nl2br( $tenstreet ['lastresponse'] ) : 'N/A') . '</th></tr>';
					}
					if (isset( $tenstreet ['driverid'] )) {
						$content .= '<tr><th>Driver ID</th>' . '<td>' . (isset( $tenstreet ['driverid'] ) ? $tenstreet ['driverid'] : '') . '</th></tr>';
					}
					$content .= '</table>';
				}
				if (isset( $response ['email'] ['data'] ) && is_array( $response ['email'] ['data'] )) {
					$email = $response ['email'] ['data'];
					$content .= '<table>';
					if (isset( $email ['message'] )) {
						$content .= '<tr><th>Email</th>' . '<td>' . $email ['message'] . '</td></tr>';
					}
					$content .= '</table>';
				}
			}
			
			return $content;
		}
		
		/**
		 * Extract data for post meta creation from API Submission data
		 *
		 * @param array $lead
		 *        	API Submission Data
		 * @return array Post Meta data
		 */
		function get_post_meta($lead, $response = null) {
			$meta = array ();
			if (is_array( $lead )) {
				foreach ( $lead as $subtitle => $section ) {
					if (is_array( $section )) {
						foreach ( $section as $field => $value ) {
							$meta ["{$subtitle}_{$field}"] = $value;
						}
					} else {
						$meta ["{$subtitle}"] = $section;
					}
				}
				
				if ($response && is_array( $response )) {
					foreach ( $response as $subtitle => $section ) {
						if (isset( $section ['data'] ) && is_array( $section ['data'] )) {
							foreach ( $section ['data'] as $field => $value ) {
								$meta ["response_{$subtitle}_{$field}"] = $value;
							}
						} else {
							$meta ["response_{$subtitle}"] = $section;
						}
					}
				}
			}
			
			return $meta;
		}
	}
	
	new GForms_TMP_AddOn();
}