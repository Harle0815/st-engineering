<?php
/**
 * The template to display Admin notices
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0.64
 */

$elementra_skins_url  = get_admin_url( null, 'admin.php?page=trx_addons_theme_panel#trx_addons_theme_panel_section_skins' );
$elementra_skins_args = get_query_var( 'elementra_skins_notice_args' );
?>
<div class="elementra_admin_notice elementra_skins_notice notice notice-info is-dismissible" data-notice="skins">
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
		<?php esc_html_e( 'New skins are available', 'elementra' ); ?>
	</h3>
	<?php

	// Description
	$elementra_total      = $elementra_skins_args['update'];	// Store value to the separate variable to avoid warnings from ThemeCheck plugin!
	$elementra_skins_msg  = $elementra_total > 0
							// Translators: Add new skins number
							? '<strong>' . sprintf( _n( '%d new version', '%d new versions', $elementra_total, 'elementra' ), $elementra_total ) . '</strong>'
							: '';
	$elementra_total      = $elementra_skins_args['free'];
	$elementra_skins_msg .= $elementra_total > 0
							? ( ! empty( $elementra_skins_msg ) ? ' ' . esc_html__( 'and', 'elementra' ) . ' ' : '' )
								// Translators: Add new skins number
								. '<strong>' . sprintf( _n( '%d free skin', '%d free skins', $elementra_total, 'elementra' ), $elementra_total ) . '</strong>'
							: '';
	$elementra_total      = $elementra_skins_args['pay'];
	$elementra_skins_msg .= $elementra_skins_args['pay'] > 0
							? ( ! empty( $elementra_skins_msg ) ? ' ' . esc_html__( 'and', 'elementra' ) . ' ' : '' )
								// Translators: Add new skins number
								. '<strong>' . sprintf( _n( '%d paid skin', '%d paid skins', $elementra_total, 'elementra' ), $elementra_total ) . '</strong>'
							: '';
	?>
	<div class="elementra_notice_text">
		<p>
			<?php
			// Translators: Add new skins info
			echo wp_kses_data( sprintf( __( "We are pleased to announce that %s are available for your theme", 'elementra' ), $elementra_skins_msg ) );
			?>
		</p>
	</div>
	<?php

	// Buttons
	?>
	<div class="elementra_notice_buttons">
		<?php
		// Link to the theme dashboard page
		?>
		<a href="<?php echo esc_url( $elementra_skins_url ); ?>" class="button button-primary"><i class="dashicons dashicons-update"></i> 
			<?php
			esc_html_e( 'Go to Skins manager', 'elementra' );
			?>
		</a>
		<?php
		// Dismiss notice for 7 days
		?>
		<a href="#" role="button" class="button button-secondary elementra_notice_button_dismiss" data-notice="skins"><i class="dashicons dashicons-no-alt"></i> 
			<?php
			esc_html_e( 'Dismiss', 'elementra' );
			?>
		</a>
		<?php
		// Hide notice forever
		?>
		<a href="#" role="button" class="button button-secondary elementra_notice_button_hide" data-notice="skins"><i class="dashicons dashicons-no-alt"></i> 
			<?php
			esc_html_e( 'Never show again', 'elementra' );
			?>
		</a>
	</div>
</div>
