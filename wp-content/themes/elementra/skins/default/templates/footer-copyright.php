<?php
/**
 * The template to display the copyright info in the footer
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0.10
 */

// Copyright area
?> 
<div class="footer_copyright_wrap">
	<div class="footer_copyright_inner">
		<div class="content_wrap">
			<div class="copyright_text">
				<?php
					$elementra_copyright = elementra_get_theme_option( 'copyright' );
					if ( ! empty( $elementra_copyright ) ) {
						// Replace {{Y}} or {Y} with the current year
						$elementra_copyright = str_replace( array( '{{Y}}', '{Y}' ), date( 'Y' ), $elementra_copyright );
						// Replace {{...}} and ((...)) on the <i>...</i> and <b>...</b>
						$elementra_copyright = elementra_prepare_macros( $elementra_copyright );
						// Display copyright
						echo wp_kses( nl2br( $elementra_copyright ), 'elementra_kses_content' );
					}
				?>
			</div>
		</div>
	</div>
</div>