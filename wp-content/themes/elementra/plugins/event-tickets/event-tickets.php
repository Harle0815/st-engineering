<?php
/* Event Tickets support functions
------------------------------------------------------------------------------- */

// Theme init priorities:
// 9 - register other filters (for installer, etc.)
if ( ! function_exists( 'elementra_event_tickets_theme_setup9' ) ) {
	add_action( 'after_setup_theme', 'elementra_event_tickets_theme_setup9', 9 );
	function elementra_event_tickets_theme_setup9() {
		if ( elementra_exists_event_tickets() ) {
			add_filter( 'elementra_filter_detect_blog_mode', 'elementra_event_tickets_detect_blog_mode' );
		}
		if ( is_admin() ) {
			add_filter( 'elementra_filter_tgmpa_required_plugins', 'elementra_event_tickets_tgmpa_required_plugins' );
		}
	}
}

// Filter to add in the required plugins list
if ( ! function_exists( 'elementra_event_tickets_tgmpa_required_plugins' ) ) {
	//Handler of the add_filter('elementra_filter_tgmpa_required_plugins',	'elementra_event_tickets_tgmpa_required_plugins');
	function elementra_event_tickets_tgmpa_required_plugins( $list = array() ) {
		if ( elementra_storage_isset( 'required_plugins', 'event-tickets' ) && elementra_storage_get_array( 'required_plugins', 'event-tickets', 'install' ) !== false ) {
			$list[] = array(
				'name'     => elementra_storage_get_array( 'required_plugins', 'event-tickets', 'title' ),
				'slug'     => 'event-tickets',
				'required' => false,
			);
		}
		return $list;
	}
}

// Check if this plugin installed and activated
if ( ! function_exists( 'elementra_exists_event_tickets' ) ) {
	function elementra_exists_event_tickets() {
		return class_exists( 'Tribe__Tickets__Main' );
	}
}

if ( ! function_exists( 'elementra_is_event_tickets_page' ) ) {
	/**
	 * Check if current page is any Event Tickets page
	 * 
	 * @return boolean  	  True if page is Event Tickets page
	 */
	function elementra_is_event_tickets_page( $check_tribe_events = false ) {
		$rez = false;
		if ( elementra_exists_event_tickets() ) {
			$current_page  = get_queried_object_id();
			$checkout_page = 0;
			$success_page  = 0;
			if ( function_exists( 'tribe_get_option' ) ) {
				$checkout_page = (int) tribe_get_option( 'tickets-commerce-checkout-page' );
				if ( ! empty( $checkout_page ) ) {
					$checkout_page = apply_filters( 'tec_tickets_commerce_checkout_page_id', $checkout_page );
				}
				$success_page  = (int) tribe_get_option( 'tickets-commerce-success-page' );
				if ( ! empty( $success_page ) ) {
					$success_page = apply_filters( 'tec_tickets_commerce_success_page_id', $success_page );
				}
			}
			$rez = ( $check_tribe_events && function_exists( 'elementra_is_tribe_events_page' ) && elementra_is_tribe_events_page() )
					|| ( ! empty( $current_page) && ( $current_page == $checkout_page || $current_page == $success_page ) );
		}
		return $rez;
	}
}

// Detect current blog mode
if ( ! function_exists( 'elementra_event_tickets_detect_blog_mode' ) ) {
	//Handler of the add_filter( 'elementra_filter_detect_blog_mode', 'elementra_event_tickets_detect_blog_mode' );
	function elementra_event_tickets_detect_blog_mode( $mode = '' ) {
		if ( elementra_is_event_tickets_page() ) {
			$mode = 'events';	//'event_tickets';
		}
		return $mode;
	}
}
