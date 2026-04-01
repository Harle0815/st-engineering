<?php
$elementra_woocommerce_sc = elementra_get_theme_option( 'front_page_woocommerce_products' );
if ( ! empty( $elementra_woocommerce_sc ) ) {
	?><div class="front_page_section front_page_section_woocommerce<?php
		$elementra_scheme = elementra_get_theme_option( 'front_page_woocommerce_scheme' );
		if ( ! empty( $elementra_scheme ) && ! elementra_is_inherit( $elementra_scheme ) ) {
			echo ' scheme_' . esc_attr( $elementra_scheme );
		}
		echo ' front_page_section_paddings_' . esc_attr( elementra_get_theme_option( 'front_page_woocommerce_paddings' ) );
		if ( elementra_get_theme_option( 'front_page_woocommerce_stack' ) ) {
			echo ' sc_stack_section_on';
		}
	?>"
			<?php
			$elementra_css      = '';
			$elementra_bg_image = elementra_get_theme_option( 'front_page_woocommerce_bg_image' );
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
		$elementra_anchor_icon = elementra_get_theme_option( 'front_page_woocommerce_anchor_icon' );
		$elementra_anchor_text = elementra_get_theme_option( 'front_page_woocommerce_anchor_text' );
		if ( ( ! empty( $elementra_anchor_icon ) || ! empty( $elementra_anchor_text ) ) && shortcode_exists( 'trx_sc_anchor' ) ) {
			echo do_shortcode(
				'[trx_sc_anchor id="front_page_section_woocommerce"'
											. ( ! empty( $elementra_anchor_icon ) ? ' icon="' . esc_attr( $elementra_anchor_icon ) . '"' : '' )
											. ( ! empty( $elementra_anchor_text ) ? ' title="' . esc_attr( $elementra_anchor_text ) . '"' : '' )
											. ']'
			);
		}
	?>
		<div class="front_page_section_inner front_page_section_woocommerce_inner
			<?php
			if ( elementra_get_theme_option( 'front_page_woocommerce_fullheight' ) ) {
				echo ' elementra-full-height sc_layouts_flex sc_layouts_columns_middle';
			}
			?>
				"
				<?php
				$elementra_css      = '';
				$elementra_bg_mask  = elementra_get_theme_option( 'front_page_woocommerce_bg_mask' );
				$elementra_bg_color_type = elementra_get_theme_option( 'front_page_woocommerce_bg_color_type' );
				if ( 'custom' == $elementra_bg_color_type ) {
					$elementra_bg_color = elementra_get_theme_option( 'front_page_woocommerce_bg_color' );
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
			<div class="front_page_section_content_wrap front_page_section_woocommerce_content_wrap content_wrap woocommerce">
				<?php
				// Content wrap with title and description
				$elementra_caption     = elementra_get_theme_option( 'front_page_woocommerce_caption' );
				$elementra_description = elementra_get_theme_option( 'front_page_woocommerce_description' );
				if ( ! empty( $elementra_caption ) || ! empty( $elementra_description ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
					// Caption
					if ( ! empty( $elementra_caption ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
						?>
						<h2 class="front_page_section_caption front_page_section_woocommerce_caption front_page_block_<?php echo ! empty( $elementra_caption ) ? 'filled' : 'empty'; ?>">
						<?php
							echo wp_kses( $elementra_caption, 'elementra_kses_content' );
						?>
						</h2>
						<?php
					}

					// Description (text)
					if ( ! empty( $elementra_description ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
						?>
						<div class="front_page_section_description front_page_section_woocommerce_description front_page_block_<?php echo ! empty( $elementra_description ) ? 'filled' : 'empty'; ?>">
						<?php
							echo wp_kses( wpautop( $elementra_description ), 'elementra_kses_content' );
						?>
						</div>
						<?php
					}
				}

				// Content (widgets)
				?>
				<div class="front_page_section_output front_page_section_woocommerce_output list_products shop_mode_thumbs">
					<?php
					if ( 'products' == $elementra_woocommerce_sc ) {
						$elementra_woocommerce_sc_ids      = elementra_get_theme_option( 'front_page_woocommerce_products_per_page' );
						$elementra_woocommerce_sc_per_page = count( explode( ',', $elementra_woocommerce_sc_ids ) );
					} else {
						$elementra_woocommerce_sc_per_page = max( 1, (int) elementra_get_theme_option( 'front_page_woocommerce_products_per_page' ) );
					}
					$elementra_woocommerce_sc_columns = max( 1, min( $elementra_woocommerce_sc_per_page, (int) elementra_get_theme_option( 'front_page_woocommerce_products_columns' ) ) );
					echo do_shortcode(
						"[{$elementra_woocommerce_sc}"
										. ( 'products' == $elementra_woocommerce_sc
												? ' ids="' . esc_attr( $elementra_woocommerce_sc_ids ) . '"'
												: '' )
										. ( 'product_category' == $elementra_woocommerce_sc
												? ' category="' . esc_attr( elementra_get_theme_option( 'front_page_woocommerce_products_categories' ) ) . '"'
												: '' )
										. ( 'best_selling_products' != $elementra_woocommerce_sc
												? ' orderby="' . esc_attr( elementra_get_theme_option( 'front_page_woocommerce_products_orderby' ) ) . '"'
													. ' order="' . esc_attr( elementra_get_theme_option( 'front_page_woocommerce_products_order' ) ) . '"'
												: '' )
										. ' per_page="' . esc_attr( $elementra_woocommerce_sc_per_page ) . '"'
										. ' columns="' . esc_attr( $elementra_woocommerce_sc_columns ) . '"'
						. ']'
					);
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
}
