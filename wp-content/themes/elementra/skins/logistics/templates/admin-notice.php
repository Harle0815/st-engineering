<?php
/**
 * The template to display Admin notices
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0.1
 */

$elementra_theme_slug = get_template();
$elementra_theme_obj  = wp_get_theme( $elementra_theme_slug );
?>
<div class="elementra_admin_notice elementra_welcome_notice notice notice-info is-dismissible" data-notice="admin">
	<?php
	// Theme image
	$elementra_theme_img = elementra_get_file_url( 'screenshot.jpg' );
	if ( '' != $elementra_theme_img ) {
		?>
		<div class="elementra_notice_image"><img src="<?php echo esc_url( $elementra_theme_img ); ?>" alt="<?php esc_attr_e( 'Theme screenshot', 'elementra' ); ?>"></div>
		<?php
	}

	// Title
	?>
	<h3 class="elementra_notice_title">
		<?php
		echo esc_html(
			sprintf(
				// Translators: Add theme name and version to the 'Welcome' message
				__( 'Welcome to %1$s v.%2$s', 'elementra' ),
				$elementra_theme_obj->get( 'Name' ) . ( ELEMENTRA_THEME_FREE ? ' ' . __( 'Free', 'elementra' ) : '' ),
				$elementra_theme_obj->get( 'Version' )
			)
		);
		?>
	</h3>
	<?php

	// Description
	?>
	<div class="elementra_notice_text">
		<p class="elementra_notice_text_description">
			<?php
			echo str_replace( '. ', '.<br>', wp_kses_data( $elementra_theme_obj->description ) );
			?>
		</p>
		<p class="elementra_notice_text_info">
			<?php
			echo wp_kses_data( __( 'Attention! Plugin "ThemeREX Addons" is required! Please, install and activate it!', 'elementra' ) );
			?>
		</p>
	</div>
	<?php

	// Buttons
	?>
	<div class="elementra_notice_buttons">
		<?php
		// Link to the page 'About Theme'
		?>
		<a href="<?php echo esc_url( admin_url() . 'themes.php?page=elementra_about' ); ?>" class="button button-primary"><i class="dashicons dashicons-nametag"></i> 
			<?php
			echo esc_html__( 'Install plugin "ThemeREX Addons"', 'elementra' );
			?>
		</a>
	</div>
</div>
