<div class="front_page_section front_page_section_googlemap<?php
	$elementra_scheme = elementra_get_theme_option( 'front_page_googlemap_scheme' );
	if ( ! empty( $elementra_scheme ) && ! elementra_is_inherit( $elementra_scheme ) ) {
		echo ' scheme_' . esc_attr( $elementra_scheme );
	}
	echo ' front_page_section_paddings_' . esc_attr( elementra_get_theme_option( 'front_page_googlemap_paddings' ) );
	if ( elementra_get_theme_option( 'front_page_googlemap_stack' ) ) {
		echo ' sc_stack_section_on';
	}
?>"
		<?php
		$elementra_css      = '';
		$elementra_bg_image = elementra_get_theme_option( 'front_page_googlemap_bg_image' );
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
	$elementra_anchor_icon = elementra_get_theme_option( 'front_page_googlemap_anchor_icon' );
	$elementra_anchor_text = elementra_get_theme_option( 'front_page_googlemap_anchor_text' );
if ( ( ! empty( $elementra_anchor_icon ) || ! empty( $elementra_anchor_text ) ) && shortcode_exists( 'trx_sc_anchor' ) ) {
	echo do_shortcode(
		'[trx_sc_anchor id="front_page_section_googlemap"'
									. ( ! empty( $elementra_anchor_icon ) ? ' icon="' . esc_attr( $elementra_anchor_icon ) . '"' : '' )
									. ( ! empty( $elementra_anchor_text ) ? ' title="' . esc_attr( $elementra_anchor_text ) . '"' : '' )
									. ']'
	);
}
?>
	<div class="front_page_section_inner front_page_section_googlemap_inner
		<?php
		$elementra_layout = elementra_get_theme_option( 'front_page_googlemap_layout' );
		echo ' front_page_section_layout_' . esc_attr( $elementra_layout );
		if ( elementra_get_theme_option( 'front_page_googlemap_fullheight' ) ) {
			echo ' elementra-full-height sc_layouts_flex sc_layouts_columns_middle';
		}
		?>
		"
			<?php
			$elementra_css      = '';
			$elementra_bg_mask  = elementra_get_theme_option( 'front_page_googlemap_bg_mask' );
			$elementra_bg_color_type = elementra_get_theme_option( 'front_page_googlemap_bg_color_type' );
			if ( 'custom' == $elementra_bg_color_type ) {
				$elementra_bg_color = elementra_get_theme_option( 'front_page_googlemap_bg_color' );
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
		<div class="front_page_section_content_wrap front_page_section_googlemap_content_wrap
		<?php
		if ( 'fullwidth' != $elementra_layout ) {
			echo ' content_wrap';
		}
		?>
		">
			<?php
			// Content wrap with title and description
			$elementra_caption     = elementra_get_theme_option( 'front_page_googlemap_caption' );
			$elementra_description = elementra_get_theme_option( 'front_page_googlemap_description' );
			if ( ! empty( $elementra_caption ) || ! empty( $elementra_description ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
				if ( 'fullwidth' == $elementra_layout ) {
					?>
					<div class="content_wrap">
					<?php
				}
					// Caption
				if ( ! empty( $elementra_caption ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
					?>
					<h2 class="front_page_section_caption front_page_section_googlemap_caption front_page_block_<?php echo ! empty( $elementra_caption ) ? 'filled' : 'empty'; ?>">
					<?php
					echo wp_kses( $elementra_caption, 'elementra_kses_content' );
					?>
					</h2>
					<?php
				}

					// Description (text)
				if ( ! empty( $elementra_description ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
					?>
					<div class="front_page_section_description front_page_section_googlemap_description front_page_block_<?php echo ! empty( $elementra_description ) ? 'filled' : 'empty'; ?>">
					<?php
					echo wp_kses( wpautop( $elementra_description ), 'elementra_kses_content' );
					?>
					</div>
					<?php
				}
				if ( 'fullwidth' == $elementra_layout ) {
					?>
					</div>
					<?php
				}
			}

			// Content (text)
			$elementra_content = elementra_get_theme_option( 'front_page_googlemap_content' );
			if ( ! empty( $elementra_content ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
				if ( 'columns' == $elementra_layout ) {
					?>
					<div class="front_page_section_columns front_page_section_googlemap_columns columns_wrap">
						<div class="column-1_3">
					<?php
				} elseif ( 'fullwidth' == $elementra_layout ) {
					?>
					<div class="content_wrap">
					<?php
				}

				?>
				<div class="front_page_section_content front_page_section_googlemap_content front_page_block_<?php echo ! empty( $elementra_content ) ? 'filled' : 'empty'; ?>">
				<?php
					echo wp_kses( $elementra_content, 'elementra_kses_content' );
				?>
				</div>
				<?php

				if ( 'columns' == $elementra_layout ) {
					?>
					</div><div class="column-2_3">
					<?php
				} elseif ( 'fullwidth' == $elementra_layout ) {
					?>
					</div>
					<?php
				}
			}

			// Widgets output
			?>
			<div class="front_page_section_output front_page_section_googlemap_output">
				<?php
				if ( is_active_sidebar( 'front_page_googlemap_widgets' ) ) {
					dynamic_sidebar( 'front_page_googlemap_widgets' );
				} elseif ( current_user_can( 'edit_theme_options' ) ) {
					if ( ! elementra_exists_trx_addons() ) {
						elementra_customizer_need_trx_addons_message();
					} else {
						elementra_customizer_need_widgets_message( 'front_page_googlemap_caption', 'ThemeREX Addons - Google map' );
					}
				}
				?>
			</div>
			<?php

			if ( 'columns' == $elementra_layout && ( ! empty( $elementra_content ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) ) {
				?>
				</div></div>
				<?php
			}
			?>
		</div>
	</div>
</div>
