<?php
/**
 * The template to display the background video in the header
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0.14
 */
$elementra_header_video = elementra_get_header_video();
$elementra_embed_video  = '';
if ( ! empty( $elementra_header_video ) && ! elementra_is_from_uploads( $elementra_header_video ) ) {
	if ( elementra_is_youtube_url( $elementra_header_video ) && preg_match( '/[=\/]([^=\/]*)$/', $elementra_header_video, $matches ) && ! empty( $matches[1] ) ) {
		?><div id="background_video" data-youtube-code="<?php echo esc_attr( $matches[1] ); ?>"></div>
		<?php
	} else {
		?>
		<div id="background_video"><?php elementra_show_layout( elementra_get_embed_video( $elementra_header_video ) ); ?></div>
		<?php
	}
}