<?php
namespace TrxAddons\AiHelper\TrxAiAssistants;

use TrxAddons\AiHelper\TrxAiAssistants;

if ( ! class_exists( 'Helper' ) ) {

	/**
	 * Main class for AI Helper TrxAiAssistants support
	 */
	class Helper {

		/**
		 * Constructor
		 */
		function __construct() {
			add_action( 'admin_footer', array( $this, 'embed_assistants' ) );
			add_action( 'trx_addons_action_load_scripts_admin', array( $this, 'load_scripts_for_add_support' ) );
			add_filter( 'trx_addons_filter_localize_script_admin', array( $this, 'localize_scripts_for_add_support' ) );
			add_action( 'wp_ajax_trx_addons_ai_assistant_add_support', array( $this, 'add_support' ) );
		}

		/**
		 * Embed assistant to the admin footer
		 * 
		 * @hooked 'admin_footer'
		 */
		function embed_assistants() {
			if ( trx_addons_is_theme_activated() && (int)trx_addons_get_option( 'ai_helper_trx_ai_assistants' ) > 0 && function_exists( 'trx_addons_sc_chat' ) ) {
				// Prepare shortcode's arguments for [trx_sc_chat]
				$args = apply_filters( 'trx_addons_filter_ai_helper_trx_ai_assistant_args', array(
					'id' => "trx-suppilot",
					'model' => "trx-ai-assistants/ai-assistant",
					'type' => "popup",
					'nolimits' => "1",			// Disable limits for the chat messages
					'save_history' => "1",		// Restore chat messages after the page reload
					'open_on_load' => "0",		// Open or not a chat popup on a page load
					'allow_voice_input' => "1",	// Enable voice input
					'offset_x' => "1em",
					'offset_y' => "1em",
					'chat_shadow' => "0px 0px 10px 0px rgba(0,0,0,0.5)",
					'popup_button_shadow' => "0px 0px 10px 0px rgba(0,0,0,0.5)",
					'popup_button_size' => "4em",
					'title_text' => __( "ThemeREX AI Assistant", 'trx_addons' ),
					'title_icon' => "trx_addons_icon-chat-empty",
					'new_chat_icon' => "trx_addons_icon-spin3",
					'new_chat_text' => "#",
					'assistant_image' => "{images}/chatbot.png",	// The addon's folder 'images' is used
					'user_image' => "{images}/user.png",
					'button_icon' => "trx_addons_icon-paper-plane",
					'button_text' => "#",
					'tags_position' => "before",
					'tags' => array(
						array(
							'title' => __( "Who are you?", 'trx_addons' ),
							'prompt' => __( "Who are you?", 'trx_addons' ),
						),
						array(
							'title' => __( "Change site title", 'trx_addons' ),
							'prompt' => __( "Change a site title to ...", 'trx_addons' ),
						),
						array(
							'title' => __( "Change site tagline", 'trx_addons' ),
							'prompt' => __( "Change a site tagline (description) to ...", 'trx_addons' ),
						),
						array(
							'title' => __( "Change logo", 'trx_addons' ),
							'prompt' => __( "Change a main logo of this site to the image from URL ...", 'trx_addons' ),
						),
						array(
							'title' => __( "Change accent color", 'trx_addons' ),
							'prompt' => __( "Change an accent color (color of links and buttons) in a color scheme 'default' to #rrggbb", 'trx_addons' ),
						),
						array(
							'title' => __( "Hide mouse helper", 'trx_addons' ),
							'prompt' => __( "Hide a mouse helper (a circle running after the mouse cursor).", 'trx_addons' ),
						),
				 	),
				) );
				?>
				<!-- EmbedChat TrxAiAssistant -->
				<?php
				// Replace {images} with the path to the folder 'addons/ai-helper/images'
				foreach ( $args as $k => $v ) {
					if ( is_string( $v ) ) {
						$args[ $k ] = str_replace(
							array( '{images}' ),
							array( trx_addons_get_folder_url( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/images' ) ),
							$v
						);
					}
				}
				// Force to enqueue styles for the shortcodes
				add_filter( 'trx_addons_filter_force_enqueue_styles', '__return_true' );
				// Do shortcode and embed the output
				trx_addons_show_layout( trx_addons_sc_chat( $args ) );
				// Remove the filter
				remove_filter( 'trx_addons_filter_force_enqueue_styles', '__return_true' );
				?>
				<!-- /EmbedChat TrxAiAssistant -->
				<?php
			}
		}

		/**
		 * Load scripts for the 'add_support' action
		 * 
		 * @hooked 'trx_addons_action_load_scripts_admin'
		 */
		function load_scripts_for_add_support() {
			// if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'trx_addons_options' ) {
			if ( trx_addons_is_theme_activated() && (int)trx_addons_get_option( 'ai_helper_trx_ai_assistants' ) > 0 && function_exists( 'trx_addons_sc_chat' ) ) {
				trx_addons_enqueue_msgbox();
				wp_enqueue_script( 'trx_addons_ai_assistant', trx_addons_get_file_url( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/TrxAiAssistants/js/ai-assistant-options.js' ), array( 'jquery' ), null, true );
				do_action( 'trx_addons_action_load_scripts_ai_assistant' );
			}
		}

		/**
		 * Localize scripts for the 'add_support' action
		 * 
		 * @hooked 'trx_addons_filter_localize_script_admin'
		 */
		function localize_scripts_for_add_support( $vars ) {
			$vars['msg_ai_assistant_add_support'] = __( 'Specify the purchase code of "Technical Support"', 'trx_addons' );
			$vars['msg_ai_assistant_add_support_header'] = __( 'Support key registration', 'trx_addons' );
			return $vars;
		}

		/**
		 * Add support for the AI assistant
		 * 
		 * @hooked 'wp_ajax_trx_addons_ai_assistant_add_support'
		 */
		function add_support() {

			trx_addons_verify_nonce();

			$response = array( 'error' => '', 'data' => '' );
			
			$support_key = trx_addons_get_value_gp( 'key' );

			if ( ! empty( $support_key ) ) {
				
				$rez = TrxAiAssistants::instance()->add_support_key( $support_key );

				if ( empty( $rez['error'] ) ) {
				$months = ! empty( $rez['months'] ) ? $rez['months'] : 1;
				$response['data'] = sprintf(
											__( 'The support period is extended for the key "%1$s" for %2$s', 'trx_addons'),
											$support_key,
											$months . ' ' . _n( 'month', 'months', $months, 'trx_addons' )
					);
				} else {
					$response['error'] = ! empty( $rez['error']['message'] )
											? $rez['error']['message']
											: ( is_string( $rez['error'] ) ? $rez['error'] : __( 'Unexpected server response. AI Assistant support period is not extended.', 'trx_addons' ) );
				}

			}

			// Return response to the AJAX handler
			trx_addons_ajax_response( $response );
		}
	}
}
