/* global jQuery, TRX_ADDONS_STORAGE */

jQuery( document ).on( 'action.init_hidden_elements', function(e, container) {

	"use strict";
	
	if ( container === undefined ) {
		container = jQuery( 'body' );
	}

	// Voice input
	container.find( '.trx_addons_ai_helper_stt_button:not(.inited)' ).addClass( 'inited' ).each( function() {
		var $stt_button = jQuery( this );
		var id = $stt_button.attr( 'id' ) || '';
		if ( ! id ) {
			id = 'trx_addons_ai_helper_stt_button_' + trx_addons_get_unique_id();
			$stt_button.attr( 'id', id );
		}

		// Init media recorder
		trx_addons_media_recorder( {
			startButtonId: id,
			stopButtonId: '',
			activeClass: 'trx_addons_ai_helper_stt_button_active',
			disabledClass: 'trx_addons_ai_helper_stt_button_disabled',
			onStart: function( startButton, stopButton ) {
				trx_addons_media_recorder_wave_init( $stt_button );
				$stt_button.parent().addClass( 'trx_addons_ai_helper_stt_active' );
			},
			onStop: function( audioBlob, audioUrl ) {
				$stt_button.parent().removeClass( 'trx_addons_ai_helper_stt_active' );
				$stt_button.addClass( 'trx_addons_loading_icon' );
				// Convert audioBlob to text via AJAX
				var formData = new FormData();
				formData.append( 'nonce', TRX_ADDONS_STORAGE['ajax_nonce'] );
				formData.append( 'action', 'trx_addons_ai_helper_speech_to_text' );
				formData.append( 'is_admin_request', TRX_ADDONS_STORAGE['admin_mode'] ? 1 : 0 );
				formData.append( 'model', $stt_button.data( 'voice-input-model' ) || '' );
				formData.append( 'voice_input', audioBlob, 'voice_input.wav' );
				jQuery.ajax( {
					url: TRX_ADDONS_STORAGE['ajax_url'],
					type: "POST",
					data: formData,
					processData: false,		// Don't process fields to the string
					contentType: false,		// Prevent content type header
					success: function( response ) {
						show_answer( response );
					},
					error: function( jqXHR, textStatus, errorThrown ) {
						trx_addons_msgbox_warning( TRX_ADDONS_STORAGE['msg_ajax_error'], TRX_ADDONS_STORAGE['msg_speech_to_text'] );
						$stt_button.removeClass( 'trx_addons_loading_icon' );
					}
				} );
			}
		} );

		// Fetch answer
		function fetch_answer( data ) {
			$stt_button.addClass( 'trx_addons_loading_icon' );
			jQuery.post( TRX_ADDONS_STORAGE['ajax_url'], {
				nonce: TRX_ADDONS_STORAGE['ajax_nonce'],
				action: 'trx_addons_ai_helper_speech_to_text_fetch',
				is_admin_request: TRX_ADDONS_STORAGE['admin_mode'] ? 1 : 0,
				fetch_id: data.fetch_id,
				fetch_url: data.fetch_url
			}, function( response ) {
				show_answer( response )
			} );
		}

		// Show answer
		function show_answer( response ) {
			$stt_button.removeClass( 'trx_addons_loading_icon' );
			var rez = trx_addons_parse_ajax_response( response, TRX_ADDONS_STORAGE['msg_ai_helper_error'] );
			if ( rez.finish_reason == 'queued' ) {
				var time = rez.fetch_time ? rez.fetch_time : 2000;
				setTimeout( function() {
					fetch_answer( rez );
				}, time );
			} else if ( ! rez.error ) {
				var $field = $stt_button.data( 'linked-field' )
								? jQuery( '#' + $stt_button.data( 'linked-field' ) )
								: $stt_button.siblings( 'input[type="text"],textarea' ).eq(0);
				var value = $field.val();
				$field.val( ( value ? value + ' ' : '' ) + rez.data.text ).trigger( 'change' ).get(0).focus();
				$stt_button.removeClass( 'trx_addons_loading_icon' );
			} else {
				trx_addons_msgbox_warning( rez.error, TRX_ADDONS_STORAGE['msg_speech_to_text'] );
			}
		}

		function trx_addons_media_recorder_wave_init( $button ) {
			var $wave = $stt_button.siblings( '.trx_addons_ai_helper_stt_wave' );
			if ( $wave.length == 0 ) {
				$stt_button.after( '<div class="trx_addons_ai_helper_stt_wave"></div>' );
				$wave = $stt_button.siblings( '.trx_addons_ai_helper_stt_wave' );
			}
			// Set wave position left after the button
			var button_pos = $button.position();
			$wave.css( {
				left: button_pos.left + $button.outerWidth() + 4,
				height: $button.outerHeight()
			} );
			// Set wave position right before the button "Send" if exists and positioned absolutely. Otherwise - right to the container right edge
			var $send = $button.siblings( 'a[class*="_prompt_button"]' );
			if ( $send.length > 0 && $send.css( 'position' ) == 'absolute' ) {
				var send_pos = $send.position();
				$wave.css( {
					right: $button.parent().innerWidth() - send_pos.left + 10
				} );
			} else {
				$wave.css( {
					right: 10
				} );
			}
		}
	} );

} );
