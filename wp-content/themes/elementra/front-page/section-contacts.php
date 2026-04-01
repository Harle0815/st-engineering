<div class="front_page_section front_page_section_contacts<?php
	$elementra_scheme = elementra_get_theme_option( 'front_page_contacts_scheme' );
	if ( ! empty( $elementra_scheme ) && ! elementra_is_inherit( $elementra_scheme ) ) {
		echo ' scheme_' . esc_attr( $elementra_scheme );
	}
	echo ' front_page_section_paddings_' . esc_attr( elementra_get_theme_option( 'front_page_contacts_paddings' ) );
	if ( elementra_get_theme_option( 'front_page_contacts_stack' ) ) {
		echo ' sc_stack_section_on';
	}
?>"
		<?php
		$elementra_css      = '';
		$elementra_bg_image = elementra_get_theme_option( 'front_page_contacts_bg_image' );
		if ( ! empty( $elementra_bg_image ) ) {
			$elementra_css .= 'background-image: url(' . esc_url( elementra_get_attachment_url( $elementra_bg_image ) ) . ');';
		}
		if ( ! empty( $elementra_css ) ) {
			echo ' style="' . esc_attr( $elementra_css ) . '"';
		}
		?>
>
<?php
	// Add anchor
	$elementra_anchor_icon = elementra_get_theme_option( 'front_page_contacts_anchor_icon' );
	$elementra_anchor_text = elementra_get_theme_option( 'front_page_contacts_anchor_text' );
if ( ( ! empty( $elementra_anchor_icon ) || ! empty( $elementra_anchor_text ) ) && shortcode_exists( 'trx_sc_anchor' ) ) {
	echo do_shortcode(
		'[trx_sc_anchor id="front_page_section_contacts"'
									. ( ! empty( $elementra_anchor_icon ) ? ' icon="' . esc_attr( $elementra_anchor_icon ) . '"' : '' )
									. ( ! empty( $elementra_anchor_text ) ? ' title="' . esc_attr( $elementra_anchor_text ) . '"' : '' )
									. ']'
	);
}
?>
	<div class="front_page_section_inner front_page_section_contacts_inner
	<?php
	if ( elementra_get_theme_option( 'front_page_contacts_fullheight' ) ) {
		echo ' elementra-full-height sc_layouts_flex sc_layouts_columns_middle';
	}
	?>
			"
			<?php
			$elementra_css      = '';
			$elementra_bg_mask  = elementra_get_theme_option( 'front_page_contacts_bg_mask' );
			$elementra_bg_color_type = elementra_get_theme_option( 'front_page_contacts_bg_color_type' );
			if ( 'custom' == $elementra_bg_color_type ) {
				$elementra_bg_color = elementra_get_theme_option( 'front_page_contacts_bg_color' );
			} elseif ( 'scheme_bg_color' == $elementra_bg_color_type ) {
				$elementra_bg_color = elementra_get_scheme_color( 'bg_color', $elementra_scheme );
			} else {
				$elementra_bg_color = '';
			}
			if ( ! empty( $elementra_bg_color ) && $elementra_bg_mask > 0 ) {
				$elementra_css .= 'background-color: ' . esc_attr(
					1 == $elementra_bg_mask ? $elementra_bg_color : elementra_hex2rgba( $elementra_bg_color, $elementra_bg_mask )
				) . ';';
			}
			if ( ! empty( $elementra_css ) ) {
				echo ' style="' . esc_attr( $elementra_css ) . '"';
			}
			?>
	>
		<div class="front_page_section_content_wrap front_page_section_contacts_content_wrap content_wrap">
			<?php

			// Title and description
			$elementra_caption     = elementra_get_theme_option( 'front_page_contacts_caption' );
			$elementra_description = elementra_get_theme_option( 'front_page_contacts_description' );
			if ( ! empty( $elementra_caption ) || ! empty( $elementra_description ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
				// Caption
				if ( ! empty( $elementra_caption ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
					?>
					<h2 class="front_page_section_caption front_page_section_contacts_caption front_page_block_<?php echo ! empty( $elementra_caption ) ? 'filled' : 'empty'; ?>">
					<?php
						echo wp_kses( $elementra_caption, 'elementra_kses_content' );
					?>
					</h2>
					<?php
				}

				// Description
				if ( ! empty( $elementra_description ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
					?>
					<div class="front_page_section_description front_page_section_contacts_description front_page_block_<?php echo ! empty( $elementra_description ) ? 'filled' : 'empty'; ?>">
					<?php
						echo wp_kses( wpautop( $elementra_description ), 'elementra_kses_content' );
					?>
					</div>
					<?php
				}
			}

			// Content (text)
			$elementra_content = elementra_get_theme_option( 'front_page_contacts_content' );
			$elementra_layout  = elementra_get_theme_option( 'front_page_contacts_layout' );
			if ( 'columns' == $elementra_layout && ( ! empty( $elementra_content ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) ) {
				?>
				<div class="front_page_section_columns front_page_section_contacts_columns columns_wrap">
					<div class="column-1_3">
				<?php
			}

			if ( ( ! empty( $elementra_content ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) ) {
				?>
				<div class="front_page_section_content front_page_section_contacts_content front_page_block_<?php echo ! empty( $elementra_content ) ? 'filled' : 'empty'; ?>">
					<?php
					echo wp_kses( $elementra_content, 'elementra_kses_content' );
					?>
				</div>
				<?php
			}

			if ( 'columns' == $elementra_layout && ( ! empty( $elementra_content ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) ) {
				?>
				</div><div class="column-2_3">
				<?php
			}

			// Shortcode output
			$elementra_sc = elementra_get_theme_option( 'front_page_contacts_shortcode' );
			if ( ! empty( $elementra_sc ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
				?>
				<div class="front_page_section_output front_page_section_contacts_output front_page_block_<?php echo ! empty( $elementra_sc ) ? 'filled' : 'empty'; ?>">
					<?php
					elementra_show_layout( do_shortcode( $elementra_sc ) );
					?>
				</div>
				<?php
			}

			if ( 'columns' == $elementra_layout && ( ! empty( $elementra_content ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) ) {
				?>
				</div></div>
				<?php
			}
			?>

		</div>
	</div>
</div>
