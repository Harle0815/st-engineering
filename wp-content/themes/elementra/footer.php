<?php
/**
 * The Footer: widgets area, logo, footer menu and socials
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0
 */

							do_action( 'elementra_action_page_content_end_text' );
							
							// Widgets area below the content
							elementra_create_widgets_area( 'widgets_below_content' );
						
							do_action( 'elementra_action_page_content_end' );
							?>
						</div>
						<?php
						
						do_action( 'elementra_action_after_page_content' );

						// Show main sidebar
						get_sidebar();

						do_action( 'elementra_action_content_wrap_end' );
						?>
					</div>
					<?php

					do_action( 'elementra_action_after_content_wrap' );

					// Widgets area below the page and related posts below the page
					$elementra_body_style = elementra_get_theme_option( 'body_style' );
					$elementra_widgets_name = elementra_get_theme_option( 'widgets_below_page', 'hide' );
					$elementra_show_widgets = ! elementra_is_off( $elementra_widgets_name ) && is_active_sidebar( $elementra_widgets_name );
					$elementra_show_related = elementra_is_single() && elementra_get_theme_option( 'related_position', 'below_content' ) == 'below_page';
					if ( $elementra_show_widgets || $elementra_show_related ) {
						if ( 'fullscreen' != $elementra_body_style ) {
							?>
							<div class="content_wrap">
							<?php
						}
						// Show related posts before footer
						if ( $elementra_show_related ) {
							do_action( 'elementra_action_related_posts' );
						}

						// Widgets area below page content
						if ( $elementra_show_widgets ) {
							elementra_create_widgets_area( 'widgets_below_page' );
						}
						if ( 'fullscreen' != $elementra_body_style ) {
							?>
							</div>
							<?php
						}
					}
					do_action( 'elementra_action_page_content_wrap_end' );
					?>
			</div>
			<?php
			do_action( 'elementra_action_after_page_content_wrap' );

			// Don't display the footer elements while actions 'full_post_loading' and 'prev_post_loading'
			if ( ( ! elementra_is_singular( 'post' ) && ! elementra_is_singular( 'attachment' ) ) || ! in_array ( elementra_get_value_gp( 'action' ), array( 'full_post_loading', 'prev_post_loading' ) ) ) {
				
				// Skip link anchor to fast access to the footer from keyboard
				?>
				<span id="footer_skip_link_anchor" class="elementra_skip_link_anchor"></span>
				<?php

				do_action( 'elementra_action_before_footer' );

				// Footer
				$elementra_footer_type = elementra_get_theme_option( 'footer_type' );
				if ( 'custom' == $elementra_footer_type && ! elementra_is_layouts_available() ) {
					$elementra_footer_type = 'default';
				}
				get_template_part( apply_filters( 'elementra_filter_get_template_part', "templates/footer-" . sanitize_file_name( $elementra_footer_type ) ) );

				do_action( 'elementra_action_after_footer' );

			}
			?>

			<?php do_action( 'elementra_action_page_wrap_end' ); ?>

		</div>

		<?php do_action( 'elementra_action_after_page_wrap' ); ?>

	</div>

	<?php do_action( 'elementra_action_after_body' ); ?>

	<?php wp_footer(); ?>

</body>
</html>