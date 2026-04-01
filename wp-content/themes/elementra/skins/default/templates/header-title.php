<?php
/**
 * The template to display the page title and breadcrumbs
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0
 */

// Page (category, tag, archive, author) title

if ( elementra_need_page_title() ) {
	elementra_sc_layouts_showed( 'title', true );
	?>
	<div class="top_panel_title sc_layouts_row">
		<div class="content_wrap">
			<div class="sc_layouts_column sc_layouts_column_align_center">
				<div class="sc_layouts_item">
					<div class="sc_layouts_title sc_align_center">
						<?php
						// Blog/Page title
						?>
						<div class="sc_layouts_title_title">
							<?php
							$elementra_blog_title           = elementra_get_blog_title();
							$elementra_blog_title_text      = '';
							$elementra_blog_title_class     = '';
							$elementra_blog_title_link      = '';
							$elementra_blog_title_link_text = '';
							if ( is_array( $elementra_blog_title ) ) {
								$elementra_blog_title_text      = $elementra_blog_title['text'];
								$elementra_blog_title_class     = ! empty( $elementra_blog_title['class'] ) ? ' ' . $elementra_blog_title['class'] : '';
								$elementra_blog_title_link      = ! empty( $elementra_blog_title['link'] ) ? $elementra_blog_title['link'] : '';
								$elementra_blog_title_link_text = ! empty( $elementra_blog_title['link_text'] ) ? $elementra_blog_title['link_text'] : '';
							} else {
								$elementra_blog_title_text = $elementra_blog_title;
							}
							?>
							<h1 class="sc_layouts_title_caption<?php echo esc_attr( $elementra_blog_title_class ); ?>"<?php
								if ( elementra_is_on( elementra_get_theme_option( 'seo_snippets' ) ) ) {
									?> itemprop="headline"<?php
								}
							?>>
								<?php
								$elementra_top_icon = elementra_get_term_image_small();
								if ( ! empty( $elementra_top_icon ) ) {
									$elementra_attr = elementra_getimagesize( $elementra_top_icon );
									?>
									<img src="<?php echo esc_url( $elementra_top_icon ); ?>" alt="<?php esc_attr_e( 'Site icon', 'elementra' ); ?>"
										<?php
										if ( ! empty( $elementra_attr[3] ) ) {
											elementra_show_layout( $elementra_attr[3] );
										}
										?>
									>
									<?php
								}
								echo wp_kses_data( $elementra_blog_title_text );
								?>
							</h1>
							<?php
							if ( ! empty( $elementra_blog_title_link ) && ! empty( $elementra_blog_title_link_text ) ) {
								?>
								<a href="<?php echo esc_url( $elementra_blog_title_link ); ?>" class="theme_button sc_layouts_title_link"><?php echo esc_html( $elementra_blog_title_link_text ); ?></a>
								<?php
							}

							// Category/Tag description
							if ( ! is_paged() && ( is_category() || is_tag() || is_tax() ) ) {
								the_archive_description( '<div class="sc_layouts_title_description">', '</div>' );
							}

							?>
						</div>
						<?php

						// Breadcrumbs
						ob_start();
						do_action( 'elementra_action_breadcrumbs' );
						$elementra_breadcrumbs = ob_get_contents();
						ob_end_clean();
						elementra_show_layout( $elementra_breadcrumbs, '<div class="sc_layouts_title_breadcrumbs">', '</div>' );
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
