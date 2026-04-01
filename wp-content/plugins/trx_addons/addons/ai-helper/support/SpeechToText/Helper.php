<?php
namespace TrxAddons\AiHelper\SpeechToText;

use TrxAddons\AiHelper\ModelsLab;
use TrxAddons\AiHelper\Lists;
use TrxAddons\AiHelper\Utils;
use TrxAddons\AiHelper\Logger;

if ( ! class_exists( 'Helper' ) ) {

	/**
	 * Main class for AI Helper SpeechToText support
	 */
	class Helper {

		var $is_admin_request = 0;

		var $sc_list = array();

		/**
		 * Constructor
		 */
		function __construct() {
			$this->is_admin_request = ( is_admin() && ! wp_doing_ajax() ) || ( wp_doing_ajax() && (int)trx_addons_get_value_gp( 'is_admin_request', 0 ) > 0 );

			$this->sc_list = apply_filters( 'trx_addons_filter_ai_helper_stt_button_targets', array(
				'sc_chat', 'sc_agenerator', 'sc_igenerator', 'sc_mgenerator', 'sc_tgenerator', 'sc_vgenerator'
			) );

			// Enqueue scripts and styles for the admin area
			add_action( 'trx_addons_action_load_scripts_ai_assistant', array( $this, 'enqueue_scripts_admin' ) );


			// Enqueue scripts and styles for the frontend
			add_action( 'trx_addons_action_load_scripts_front', array( $this, 'enqueue_scripts' ), 10, 2 );
			add_filter( 'trx_addons_filter_localize_script', array( $this, 'localize_script' ) );
			add_action( 'trx_addons_action_pagebuilder_admin_scripts', array( $this, 'enqueue_editor_scripts' ) );

			// Merge styles and scripts
			add_filter( 'trx_addons_filter_merge_styles', array( $this, 'merge_styles' ) );
			add_filter( 'trx_addons_filter_merge_scripts', array( $this, 'merge_scripts' ) );

			// AJAX callback for the 'Voice Input' button
			add_action( 'wp_ajax_nopriv_trx_addons_ai_helper_speech_to_text', array( $this, 'speech_to_text' ) );
			add_action( 'wp_ajax_trx_addons_ai_helper_speech_to_text', array( $this, 'speech_to_text' ) );
			// Callback function to fetch answer from the assistant
			add_action( 'wp_ajax_nopriv_trx_addons_ai_helper_speech_to_text_fetch', array( $this, 'fetch_answer' ) );
			add_action( 'wp_ajax_trx_addons_ai_helper_speech_to_text_fetch', array( $this, 'fetch_answer' ) );

			// Add Elementor controls for the 'Voice Input' button
			add_action( 'trx_addons_action_ai_helper_stt_button_settings', array( $this, 'stt_button_elementor_settings' ) );
			add_action( 'trx_addons_action_ai_helper_stt_button_style', array( $this, 'stt_button_elementor_style' ) );
			add_filter( 'trx_addons_sc_atts', array( $this, 'stt_button_add_sc_atts' ), 10, 2 );
			add_filter( 'trx_addons_filter_sc_classes', array( $this, 'stt_button_sc_classes' ), 10, 3 );
			add_action( 'trx_addons_action_ai_helper_stt_button_layout', array( $this, 'stt_button_layout' ), 10, 2 );
		}

		/**
		 * Return first audio model available for transcription
		 */
		private function get_transcription_model() {
			return trx_addons_array_get_first_key( Lists::get_list_ai_audio_models( 'transcription', false ) );
		}

		/**
		 * Check if AI Helper is allowed
		 */
		private function is_allowed() {
			$allowed = $this->is_admin_request > 0;
			if ( ! $allowed ) {
				$model = $this->get_transcription_model();
				if ( ! empty( $model ) ) {
					$api = Utils::get_audio_api( $model );
					$allowed = is_object( $api ) && $api->get_api_key() != '';
				}
			}
			return $allowed;
		}

		/**
		 * Check if shortcode is in the list of supported shortcodes
		 */
		private function check_sc( $sc, $prefix = '' ) {
			$found = false;
			if ( ! empty( $sc ) ) {
				foreach ( $this->sc_list as $item ) {
					if ( $sc == $prefix . $item ) {
						$found = true;
						break;
					}
				}
			}
			return $found;
		}

		/**
		 * Enqueue scripts and styles for the admin area
		 * 
		 * @hooked 'trx_addons_action_load_scripts_admin'
		 */
		public function enqueue_scripts_admin() {
			static $loaded = false;
			if ( ! $loaded ) {
				$loaded = true;
				add_filter( 'trx_addons_filter_localize_script_admin', array( $this, 'localize_script' ) );
				$this->enqueue_scripts( true, 'sc_chat' );
			}
		}

		/**
		 * Enqueue scripts and styles for the frontend
		 * 
		 * @hooked 'trx_addons_action_load_scripts_front'
		 */
		public function enqueue_scripts( $force = false, $sc = '' ) {
			if ( $this->check_sc( $sc ) && $this->is_allowed() ) {
				wp_enqueue_style( 'trx_addons-ai-helper-speech-to-text', trx_addons_get_file_url( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/SpeechToText/assets/css/index.css' ), array(), null );
				wp_enqueue_script( 'trx_addons-ai-helper-speech-to-text', trx_addons_get_file_url( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/SpeechToText/assets/js/index.js' ), array( 'jquery' ), null, true );
				trx_addons_enqueue_msgbox();
			}
		}

		/**
		 * Enqueue editor scripts
		 * 
		 * @hooked 'trx_addons_action_pagebuilder_admin_scripts'
		 */
		public function enqueue_editor_scripts() {
			wp_enqueue_script( 'trx_addons-ai-helper-speech-to-text-elementor', trx_addons_get_file_url( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/SpeechToText/assets/js/editor.js' ), array( 'jquery' ), null, true );
		}

		/**
		 * Localize script to show messages
		 * 
		 * @hooked 'trx_addons_filter_localize_script'
		 * 
		 * @param array $vars  Array of variables to be passed to the script
		 * 
		 * @return array  Modified array of variables
		 */
		public function localize_script( $vars ) {
			if ( $this->is_allowed() ) {
				$vars['msg_ai_helper_error']  = esc_html__( "AI Helper unrecognized response", 'trx_addons' );
				$vars['msg_speech_to_text']   = esc_html__( "Speech To Text", 'trx_addons' );
				$vars['msg_stt_button_title'] = esc_attr__( 'Prefer speaking? Click the microphone to enter text by voice.', 'trx_addons' );
			}
			return $vars;
		}


		/**
		 * Merge shortcode's specific styles to the single stylesheet
		 * 
		 * @hooked 'trx_addons_filter_merge_styles'
		 * 
		 * @param array $list  List of styles to be merged
		 * 
		 * @return array  Modified list of styles
		 */
		public function merge_styles( $list ) {
			$list[ TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/SpeechToText/assets/css/index.css' ] = false;
			return $list;
		}

		/**
		 * Merge shortcode's specific scripts into single file
		 * 
		 * @hooked 'trx_addons_filter_merge_scripts'
		 * 
		 * @param array $list  List of scripts to be merged
		 * 
		 * @return array  Modified list of scripts
		 */
		public function merge_scripts($list) {
			$list[ TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/SpeechToText/assets/js/index.js' ] = false;
			return $list;
		}

		/**
		 * Send a query to API to transcript speech to text
		 * 
		 * @hooked 'wp_ajax_trx_addons_ai_helper_speech_to_text'
		 * 
		 * @param WP_REST_Request  $request  Full details about the request.
		 */
		public function speech_to_text() {

			trx_addons_verify_nonce();

			$answer = array(
				'error' => '',
				'data' => array(
					'text' => '',
					'message' => ''
				)
			);

			if ( $this->is_admin_request ) {
				if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
					$referrer = wp_parse_url( esc_url_raw( $_SERVER['HTTP_REFERER'] ) );
					if ( empty( $referrer['host'] )
						|| $referrer['host'] != wp_parse_url( home_url(), PHP_URL_HOST )
						|| empty( $referrer['path'] )
						|| strpos( $referrer['path'], '/wp-admin/' ) === false
						|| ! is_user_logged_in()
					) {
						$answer['error'] = __( 'Error! Unauthorized admin request.', 'trx_addons' );
					}
				} else {
					$answer['error'] = __( 'Error! Unauthorized admin request.', 'trx_addons' );
				}
				if ( empty( $answer['error'] ) ) {
					$api = \TrxAddons\AiHelper\TrxAiAssistants::instance();
					$model = $api->get_model();
					$base64 = true;
				}
			} else {
				$model = trim( trx_addons_get_value_gp( 'model' ) );
				if ( empty( $model ) ) {
					$model = $this->get_transcription_model();
				}
				$base64 = Utils::is_model_support_base64( $model );
				$api = Utils::get_audio_api( $model );
			}

			if ( ! empty( $api ) && $api->get_api_key() != '' ) {
				$args = array(
					'model' => $model,
				);
				// Add field 'voice_input' => 'init_audio'
				if ( ! empty( $_FILES["voice_input"]["tmp_name"] ) ) {
					$validate = wp_check_filetype( $_FILES["voice_input"]["name"] );
					if ( $validate['type'] == false ) {
						$answer['error'] = __( "File type is not allowed.", "trx_addons" );
					} else {
						if ( $base64 ) {
							$args['init_audio'] = base64_encode( trx_addons_fgc( $_FILES["voice_input"]["tmp_name"] ) );
							$args['base64'] = true;
						} else {
							$ext = trx_addons_get_file_ext( $_FILES["voice_input"]["name"] );
							if ( empty( $ext ) ) {
								$ext = 'mp3';
							}
							$args['init_audio'] = trx_addons_uploads_save_data( trx_addons_fgc( $_FILES["voice_input"]["tmp_name"] ), array(
								'expire' => apply_filters( 'trx_addons_filter_ai_helper_voice_input_expire_time', 10 * 60 ),
								'ext' => $ext,
							) );
							$args['base64'] = false;
						}
					}
				}
				// Add field 'language'
				if ( Utils::is_model_support_language( $model ) ) {
					$language = trim( trx_addons_get_value_gp( 'language' ) );
					if ( ! empty( $language ) ) {
						$args['language'] = $language;
					}
				}
				// Add field 'emotion'
				if ( Utils::is_model_support_emotions( $model ) ) {
					$emotion = trim( trx_addons_get_value_gp( 'emotion' ) );
					if ( ! empty( $emotion ) ) {
						$args['emotion'] = $emotion;
					}
				}
				// Add field 'temperature'
				if ( Utils::is_openai_model( $model ) ) {
					$args['temperature'] = (float)trx_addons_get_value_gp( 'temperature', 1.0 );
				}
				// Send request to the API
				if ( empty( $answer['error'] ) ) {
					if ( ! empty( $args['init_audio'] ) ) {
						$response = $api->transcription( apply_filters( 'trx_addons_filter_ai_helper_transcription_audio_args', $args, 'speech_to_text' ) );
						$answer = Utils::parse_response( $response, $model, $answer, 'audio' );
						if ( ! $this->is_admin_request ) {
							if ( ! empty( $answer['error'] ) && Utils::is_openai_model( $model ) ) {
								$answer['error'] .= '<br>' . __( 'Note: Open AI API for audio transcription works only on a real server with Internet access.', 'trx_addons' );
							}
							$parts = explode( '/', $model );
							Logger::instance()->log( $response, $model, $args, str_replace( 'openai', 'open-ai', $parts[0] ) . '/audio' );
						}
						// Parse response
						if ( ! empty( $answer['data']['fetch_id'] ) ) {
							$answer['data']['fetch_time'] = apply_filters( 'trx_addons_filter_speech_to_text_fetch_time', 2000 );
						}
					} else {
						$answer['error'] = __( "Error! Audio file is not uploaded.", "trx_addons" );
					}
				}
			} else {
				$answer['error'] = __( 'Error! Audio API is not available.', 'trx_addons' );
			}

			// Return response to the AJAX handler
			trx_addons_ajax_response( $answer );
		}

		/**
		 * Fetch audio from the ModelsLab API
		 * 
		 * @hooked 'wp_ajax_trx_addons_ai_helper_speech_to_text_fetch'
		 * @hooked 'wp_ajax_nopriv_trx_addons_ai_helper_speech_to_text_fetch'
		 * 
		 * @param WP_REST_Request  $request  Full details about the request.
		 */
		public function fetch_answer() {

			trx_addons_verify_nonce();

			$answer = array(
				'error' => '',
				'data' => array(
					'text' => ''
				)
			);

			$model = trx_addons_get_value_gp( 'fetch_model', $this->get_transcription_model() );
			$id    = trx_addons_get_value_gp( 'fetch_id', '' );
			$url   = trx_addons_get_value_gp( 'fetch_url', '' );

			if ( ! empty( $id ) ) {
				// Check if the id is in the cache and it is the same model
				$saved_model = Utils::get_data_from_cache( $id );
				if ( $saved_model == $model ) {
					$api = ModelsLab::instance();
					$response = $api->fetch_audio( array(
						'fetch_id'  => $id,
						'fetch_url' => $url,
						// 'model'     => $model,
					) );
					$answer = Utils::parse_response( $response, $model, $answer, 'audio' );
					// Remove id from the cache if the text is fetched
					if ( ! empty( $answer['data']['text'] ) ) {
						Utils::delete_data_from_cache( $id );
					} else if ( ! empty( $answer['data']['fetch_id'] ) ) {
						$answer['data']['fetch_time'] = apply_filters( 'trx_addons_filter_speech_to_text_fetch_time', 2000 );
					}
				} else {
					$answer['error'] = __( 'Error! Incorrect the queue ID for fetch transcription from server.', 'trx_addons' );
				}
			} else {
				$answer['error'] = __( 'Error! Need the queue ID for fetch transcription from server.', 'trx_addons' );
			}

			// Return response to the AJAX handler
			trx_addons_ajax_response( $answer );
		}

		/**
		 * Add Elementor controls for the 'Voice Input' button
		 * 
		 * @hooked 'trx_addons_action_ai_helper_stt_button_settings'
		 * 
		 * @param object $element  Elementor element instance
		 */
		function stt_button_elementor_settings( $element ) {
			$audio_models = Lists::get_list_ai_audio_models( 'transcription', false );

			if ( count( $audio_models ) > 0 ) {
				$element->add_control(
					'allow_voice_input',
					[
						'label' => __( 'Allow voice input', 'trx_addons' ),
						'label_block' => false,
						'description' => __( 'Allow visitors to enter prompts by voice (using a microphone)', 'trx_addons' ),
						'type' => \Elementor\Controls_Manager::SWITCHER,
						'return_value' => '1'
					]
				);

				$element->add_control(
					'voice_input_model',
					[
						'label' => __( 'Model for transcription', 'trx_addons' ),
						'label_block' => false,
						'type' => \Elementor\Controls_Manager::SELECT,
						'options' => $audio_models,
						'default' => trx_addons_array_get_first_key( $audio_models ),
						'condition' => [
							'allow_voice_input' => '1'
						]
					]
				);

				$element->add_control(
					'voice_input_description_open_ai',
					array(
						'raw'             => '<strong>' . __( 'Open AI transcription', 'trx_addons' ) . '</strong>'
											. '<br>' . __( "Speech recognition using the Open AI API will only work on a real server with Internet access.", 'trx_addons' ),
						'type'            => \Elementor\Controls_Manager::RAW_HTML,
						'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
						'condition' => [
							'allow_voice_input' => '1'
						]
					)
				);
			}

		}

		/**
		 * Add Elementor controls for the 'Voice Input' button styles
		 * 
		 * @hooked 'trx_addons_action_ai_helper_stt_button_style'
		 * 
		 * @param object $element  Elementor element instance
		 */
		function stt_button_elementor_style( $element ) {

			$element->start_controls_section(
				'section_voice_input_style',
				[
					'label' => __( 'Button "Voice Input"', 'trx_addons' ),
					'tab' => \Elementor\Controls_Manager::TAB_STYLE
				]
			);

			$element->start_controls_tabs( 'tabs_voice_input_style' );

			$element->start_controls_tab(
				'tab_voice_input_normal',
				[
					'label' => __( 'Normal', 'trx_addons' ),
				]
			);

			$params = trx_addons_get_icon_param( 'voice_input_icon' );
			$params = trx_addons_array_get_first_value( $params );
			unset( $params['name'] );
			$element->add_control( 'voice_input_icon', $params );

			$element->add_control(
				"voice_input_icon_color",
				[
					'label' => __( 'Icon color', 'trx_addons' ),
					'label_block' => false,
					'type' => \Elementor\Controls_Manager::COLOR,
					'default' => '',
					// 'global' => array(
					// 	'active' => false,
					// ),
					'selectors' => [
						'{{WRAPPER}} .trx_addons_ai_helper_stt_button' => 'color: {{VALUE}};',
						'{{WRAPPER}} .trx_addons_ai_helper_stt_button svg' => 'fill: {{VALUE}};',
					],
				]
			);

			$element->add_control(
				"voice_input_bg_color",
				[
					'label' => __( 'Background Color', 'trx_addons' ),
					'label_block' => false,
					'type' => \Elementor\Controls_Manager::COLOR,
					'default' => '',
					// 'global' => array(
					// 	'active' => false,
					// ),
					'selectors' => [
						'{{WRAPPER}} .trx_addons_ai_helper_stt_button' => 'background-color: {{VALUE}};',
					],
				]
			);

			$element->add_control(
				"voice_input_bd_color",
				[
					'label' => __( 'Border Color', 'trx_addons' ),
					'label_block' => false,
					'type' => \Elementor\Controls_Manager::COLOR,
					'default' => '',
					// 'global' => array(
					// 	'active' => false,
					// ),
					'selectors' => [
						'{{WRAPPER}} .trx_addons_ai_helper_stt_button' => 'border-color: {{VALUE}};',
					],
				]
			);

			$element->add_control(
				'voice_input_bd_width',
				[
					'label' => __( 'Border width', 'trx_addons' ),
					'type' => \Elementor\Controls_Manager::SLIDER,
					'default' => [
						'size' => '',
						'unit' => 'px'
					],
					'size_units' => [ 'px' ],
					'range' => [
						'px' => [
							'min' => 0,
							'max' => 10,
							'step' => 1
						]
					],
					'selectors' => [
						'{{WRAPPER}} .trx_addons_ai_helper_stt_button' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
					],
				]
			);

			$element->add_control(
				'voice_input_bd_radius',
				[
					'label' => __( 'Border radius', 'trx_addons' ),
					'type' => \Elementor\Controls_Manager::SLIDER,
					'default' => [
						'size' => '',
						'unit' => 'px'
					],
					'size_units' => [ 'px' ],
					'range' => [
						'px' => [
							'min' => 0,
							'max' => 100,
							'step' => 1
						]
					],
					'selectors' => [
						'{{WRAPPER}} .trx_addons_ai_helper_stt_button' => '--trx-addons-ai-helper-stt-button-border-radius: {{SIZE}}{{UNIT}};',
					],
				]
			);

			$element->add_group_control(
				\Elementor\Group_Control_Box_Shadow::get_type(),
				[
					'name' => 'voice_input_shadow',
					'label' => esc_html__( 'Shadow', 'trx_addons' ),
					'selector' => '{{WRAPPER}} .trx_addons_ai_helper_stt_button',
				]
			);

			$element->end_controls_tab();

			$element->start_controls_tab(
				'tab_voice_input_hover',
				[
					'label' => __( 'Hover', 'trx_addons' ),
				]
			);

			$element->add_control(
				"voice_input_icon_hover",
				[
					'label' => __( 'Icon color', 'trx_addons' ),
					'label_block' => false,
					'type' => \Elementor\Controls_Manager::COLOR,
					'default' => '',
					// 'global' => array(
					// 	'active' => false,
					// ),
					'selectors' => [
						'{{WRAPPER}} .trx_addons_ai_helper_stt_button:hover,
						 {{WRAPPER}} .trx_addons_ai_helper_stt_button:focus' => 'color: {{VALUE}};',
						'{{WRAPPER}} .trx_addons_ai_helper_stt_button:hover svg,
						 {{WRAPPER}} .trx_addons_ai_helper_stt_button:focus svg' => 'fill: {{VALUE}};',
					],
				]
			);

			$element->add_control(
				"voice_input_bg_hover",
				[
					'label' => __( 'Background Color', 'trx_addons' ),
					'label_block' => false,
					'type' => \Elementor\Controls_Manager::COLOR,
					'default' => '',
					// 'global' => array(
					// 	'active' => false,
					// ),
					'selectors' => [
						'{{WRAPPER}} .trx_addons_ai_helper_stt_button:hover,
						 {{WRAPPER}} .trx_addons_ai_helper_stt_button:focus' => 'background-color: {{VALUE}};',
					],
				]
			);

			$element->add_control(
				"voice_input_bd_hover",
				[
					'label' => __( 'Border Color', 'trx_addons' ),
					'label_block' => false,
					'type' => \Elementor\Controls_Manager::COLOR,
					'default' => '',
					// 'global' => array(
					// 	'active' => false,
					// ),
					'selectors' => [
						'{{WRAPPER}} .trx_addons_ai_helper_stt_button:hover,
						 {{WRAPPER}} .trx_addons_ai_helper_stt_button:focus' => 'border-color: {{VALUE}};',
					],
				]
			);

			$element->add_group_control(
				\Elementor\Group_Control_Box_Shadow::get_type(),
				[
					'name' => 'voice_input_shadow_hover',
					'label' => esc_html__( 'Shadow', 'trx_addons' ),
					'selector' => '{{WRAPPER}} .trx_addons_ai_helper_stt_button:hover,
									{{WRAPPER}} .trx_addons_ai_helper_stt_button:focus',
				]
			);

			$element->end_controls_tab();

			$element->end_controls_tabs();

			$element->end_controls_section();

		}

		/**
		 * Add default shortcode attributes for the 'Voice Input' button
		 * 
		 * @hooked 'trx_addons_sc_atts'
		 * 
		 * @param array  $atts      Shortcode attributes
		 * @param string $sc        Shortcode name
		 * 
		 * @return array  Modified default shortcode attributes
		 */
		public function stt_button_add_sc_atts( $atts, $sc ) {
			if ( $this->check_sc( $sc, 'trx_' ) && ! isset( $atts['allow_voice_input'] ) ) {
				$atts['allow_voice_input'] = 0;
				$atts['voice_input_model'] = '';
			}
			return $atts;
		}

		/**
		 * Add the class 'trx_addons_ai_helper_stt_button_present' to the chat shortcode if the 'Voice Input' button is enabled
		 * 
		 * @hooked 'trx_addons_filter_sc_classes'
		 * 
		 * @param array  $atts      Shortcode attributes
		 * @param string $sc        Shortcode name
		 * 
		 * @return array  Modified shortcode attributes
		 */
		public function stt_button_sc_classes( $classes, $sc, $atts ) {
			if ( $this->check_sc( $sc ) && ! empty( $atts['allow_voice_input'] ) ) {
				$classes .= ' trx_addons_ai_helper_stt_button_present';
			}
			return $classes;
		}

		/**
		 * Render layout for the 'Voice Input' button
		 * 
		 * @hooked 'trx_addons_action_ai_helper_stt_button_layout'
		 * 
		 * @param array $args  Shortcode settings
		 * @param string $prompt_field_id  ID of the prompt input field
		 */
		public function stt_button_layout( $args, $prompt_field_id ) {
			if ( ! empty( $args['allow_voice_input'] ) ) {
				$voice_input_icon = ! empty( $args['voice_input_icon'] ) && ! trx_addons_is_off( $args['voice_input_icon'] ) ? $args['voice_input_icon'] : 'trx_addons_icon-mic';
				?>
				<a href="#" class="trx_addons_ai_helper_stt_button <?php echo esc_attr( $voice_input_icon ); ?>"
					title="<?php esc_attr_e( 'Prefer speaking? Click the microphone to enter text by voice.', 'trx_addons' ); ?>"
					data-linked-field="<?php echo esc_attr( $prompt_field_id ); ?>"
					data-voice-input-model="<?php echo esc_attr( ! empty( $args['voice_input_model'] ) ? $args['voice_input_model'] : '' ); ?>"
				></a>
				<?php
			}
		}

	}
}
