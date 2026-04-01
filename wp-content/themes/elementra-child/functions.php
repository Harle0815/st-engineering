<?php
/**
 * Child-Theme functions and definitions
 */

add_filter(  'gettext',  'wps_translate_words_array'  );
add_filter(  'ngettext',  'wps_translate_words_array'  );
function wps_translate_words_array( $translated ) {
     $words = array(
                // 'word to translate' = > 'translation'
				'Close' => 'Schließen',
     );
     $translated = str_ireplace(  array_keys($words),  $words,  $translated );
     return $translated;
}


add_filter( 'auto_core_update_send_email', 'wpb_stop_auto_update_emails', 10, 4 );
 
function wpb_stop_update_emails( $send, $type, $core_update, $result ) {
if ( ! empty( $type ) && $type == 'success' ) {
return false;
}
return true;
}

add_filter( 'auto_plugin_update_send_email', '__return_false' );

// Interactive Leistungen section (shortcode: [ste_leistungen])
require_once get_stylesheet_directory() . '/includes/leistungen/leistungen.php';

?>