<?php
/**
 * The default template to displaying related posts
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0.54
 */

$elementra_link        = get_permalink();
$elementra_post_format = get_post_format();
$elementra_post_format = empty( $elementra_post_format ) ? 'standard' : str_replace( 'post-format-', '', $elementra_post_format );
?><div id="post-<?php the_ID(); ?>" <?php post_class( 'related_item post_format_' . esc_attr( $elementra_post_format ) ); ?> data-post-id="<?php the_ID(); ?>">
	<?php
	elementra_show_post_featured(
		array(
			'thumb_size' => apply_filters( 'elementra_filter_related_thumb_size', elementra_get_thumb_size(
				(int) elementra_get_theme_option( 'related_posts' ) == 1 || (int) elementra_get_theme_option( 'related_columns' ) == 1 ? 'full' : 'big' )
			),
		)
	);
	?>
	<div class="post_header entry-header">
		<h6 class="post_title entry-title"><a href="<?php echo esc_url( $elementra_link ); ?>"><?php
			if ( '' == get_the_title() ) {
				esc_html_e( 'No title', 'elementra' );
			} else {
				the_title();
			}
		?></a></h6>
		<?php
		if ( in_array( get_post_type(), array( 'post', 'attachment' ) ) ) {
			?>
			<span class="post_date"><a href="<?php echo esc_url( $elementra_link ); ?>"><?php echo wp_kses_data( elementra_get_date() ); ?></a></span>
			<?php
		}
		?>
	</div>
</div>
