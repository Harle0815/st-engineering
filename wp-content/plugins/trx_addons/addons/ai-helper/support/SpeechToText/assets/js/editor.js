/* global jQuery, TRX_ADDONS_STORAGE */
( function() {
	"use strict";

	trx_addons_add_filter( 'trx_addons_filter_sc_classes', function( classes, settings ) {
		if ( settings.allow_voice_input ) {
			classes += ( classes ? ' ' : '' ) + 'trx_addons_ai_helper_stt_button_present';
		}
		return classes;
	} );

	trx_addons_add_filter( 'trx_addons_filter_ai_helper_stt_button_layout', function( layout, settings, prompt_field_id ) {
		if ( settings.allow_voice_input ) {
			var voice_input_icon = settings.voice_input_icon && settings.voice_input_icon != 'none' ? settings.voice_input_icon : 'trx_addons_icon-mic';
			layout += '<a href="#" class="trx_addons_ai_helper_stt_button ' + voice_input_icon + '"'
						+ ' data-linked-field="' + prompt_field_id + '"'
						+ ' data-voice-input-model="' + settings.voice_input_model + '"'
						+ ' title="' + TRX_ADDONS_STORAGE['msg_stt_button_title'] + '"'
						+ '></a>';
		}
		return layout;
	} );

} )();
