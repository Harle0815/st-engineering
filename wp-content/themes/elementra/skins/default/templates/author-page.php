<?php
/**
 * The template to display the user's avatar, bio and socials on the Author page
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.71.0
 */
?>

<div class="author_page author vcard"<?php
	if ( elementra_is_on( elementra_get_theme_option( 'seo_snippets' ) ) ) {
		?> itemprop="author" itemscope="itemscope" itemtype="<?php echo esc_attr( elementra_get_protocol( true ) ); ?>//schema.org/Person"<?php
	}
?>>

	<div class="author_avatar"<?php
		if ( elementra_is_on( elementra_get_theme_option( 'seo_snippets' ) ) ) {
			?> itemprop="image"<?php
		}
	?>>
		<?php
		$elementra_mult = elementra_get_retina_multiplier();
		echo get_avatar( get_the_author_meta( 'user_email' ), 120 * $elementra_mult );
		?>
	</div>

	<h4 class="author_title"<?php
		if ( elementra_is_on( elementra_get_theme_option( 'seo_snippets' ) ) ) {
			?> itemprop="name"<?php
		}
	?>><span class="fn"><?php the_author(); ?></span></h4>

	<?php
	$elementra_author_description = get_the_author_meta( 'description' );
	if ( ! empty( $elementra_author_description ) ) {
		?>
		<div class="author_bio"<?php
			if ( elementra_is_on( elementra_get_theme_option( 'seo_snippets' ) ) ) {
				?> itemprop="description"<?php
			}
		?>><?php echo wp_kses( wpautop( $elementra_author_description ), 'elementra_kses_content' ); ?></div>
		<?php
	}
	?>

	<div class="author_details">
		<span class="author_posts_total">
			<?php
			$elementra_posts_total = count_user_posts( get_the_author_meta('ID'), 'post' );	// get_the_author_posts() return posts number by post_type from first post in the result
			if ( $elementra_posts_total > 0 ) {
				// Translators: Add the author's posts number to the message
				echo wp_kses( sprintf( _n( '%s article published', '%s articles published', $elementra_posts_total, 'elementra' ),
										'<span class="author_posts_total_value">' . number_format_i18n( $elementra_posts_total ) . '</span>'
								 		),
							'elementra_kses_content'
							);
			} else {
				esc_html_e( 'No posts published.', 'elementra' );
			}
			?>
		</span><?php
			ob_start();
			do_action( 'elementra_action_user_meta', 'author-page' );
			$elementra_socials = ob_get_contents();
			ob_end_clean();
			elementra_show_layout( $elementra_socials,
				'<span class="author_socials"><span class="author_socials_caption">' . esc_html__( 'Follow:', 'elementra' ) . '</span>',
				'</span>'
			);
		?>
	</div>

</div>
