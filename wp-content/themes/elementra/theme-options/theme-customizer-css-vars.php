<?php
if ( ! function_exists( 'elementra_add_css_vars' ) ) {
	add_filter( 'elementra_filter_get_css', 'elementra_add_css_vars', 1, 2 );
	/**
	 * Add a theme-specific fonts, vars and colors to the custom CSS
	 * 
	 * @hooked elementra_filter_get_css, 1
	 * 
	 * @param array $css The CSS array to which the variables will be added.
	 * @param array $args The arguments containing the fonts, vars, and colors to be added.
	 * 
	 * @return array The modified CSS array with the added variables.
	 */
	function elementra_add_css_vars( $css, $args ) {

		// Add fonts settings to css variables
		if ( isset( $css['fonts'] ) && isset( $args['fonts'] ) ) {
			$fonts = $args['fonts'];
			if ( is_array( $fonts ) && count( $fonts ) > 0 ) {
				$breakpoints = elementra_get_theme_breakpoints();
				$tmp = '';
				foreach ( $breakpoints as $bp => $bpv ) {
					$suffix = $bp == 'desktop' ? '' : '_' . $bp;
					if ( ! empty( $suffix ) ) {
						$tmp .= "@media (max-width: {$bpv['max']}px) {\n";
					}
					$tmp .= ":root {\n";
					foreach( $fonts as $tag => $font ) {
						if ( is_array( $font ) ) {
							foreach ( $font as $css_prop => $css_value ) {
								if ( in_array( $css_prop, array( 'title', 'description' ) ) ) {
									continue;
								}
								// Skip responsive values
								if ( strpos( $css_prop, '_' ) !== false ) {
									continue;
								}
								if ( empty( $suffix ) || ! empty( $font["{$css_prop}{$suffix}"] ) ) {
									$param = $css_name = $css_prop;
									if ( strpos( $css_prop, ':' ) !== false ) {
										$css_name = str_replace( ':', '-', $css_prop );
										$parts = explode( ':', $css_prop );
										$css_prop = $parts[0];
									}
									if ( ( ! empty( $font["{$param}{$suffix}"] ) && ! elementra_is_inherit( $font["{$param}{$suffix}"] ) ) || strpos( $css_prop, 'color' ) === false ) {
										$tmp .= "--theme-font-{$tag}_{$css_name}: " . ( ! empty( $font["{$param}{$suffix}"] ) && ! elementra_is_inherit( $font["{$param}{$suffix}"] )
																		? ( in_array( $css_prop, array( 'font-size', 'letter-spacing', 'margin-top', 'margin-bottom', 'border-width', 'border-radius' ) )
																			? elementra_prepare_css_value( $font["{$param}{$suffix}"] )
																			: $font["{$param}{$suffix}"]
																			)
																		: 'inherit'
																	) . ";\n";
									}
								}
							}
						}
					}
					$tmp .= "\n}\n";
					if ( ! empty( $suffix ) ) {
						$tmp .= "\n}\n";
					}
				}
				$css['fonts'] = $tmp . $css['fonts'];
			}
		}

		// Add options as css variables if the option parameter 'css' is true or a string with the css variable name.
		// If an option has a parameter 'responsive' set to true, it will generate a css variable for each breakpoint.
		if ( isset( $css['vars'] ) && isset( $args['vars'] ) ) {
			global $ELEMENTRA_STORAGE;
			if ( is_array( $ELEMENTRA_STORAGE['options'] ) && count( $ELEMENTRA_STORAGE['options'] ) > 0 ) {
				$breakpoints = elementra_get_theme_breakpoints();
				foreach ( $breakpoints as $bp => $bpv ) {
					$breakpoints[ $bp ]['filled'] = false;
					$breakpoints[ $bp ]['tmp'] = $bp == 'desktop'
													? ":root {\n"
													: "@media (max-width: {$bpv['max']}px) {\n" . "\t:root {\n" ;
				}
				foreach ( $ELEMENTRA_STORAGE['options'] as $option => $value ) {
					if ( ! empty( $value['css'] ) ) {
						$css_var = $value['css'] === true ? $option : $value['css'];
						$css_var = '--theme-var-' . str_replace( '_', '-', $css_var );
						foreach ( $breakpoints as $bp => $bpv ) {
							if ( empty( $value['responsive'] ) && $bp != 'desktop' ) {
								continue;
							}
							$suffix = $bp == 'desktop' ? '' : '_' . $bp;
							$css_value = isset( $value["val{$suffix}"] ) ? $value["val{$suffix}"] : ( $bp == 'desktop' ? 0 : '' );
							if ( $css_value !== '' ) {
								$css_value = elementra_prepare_css_value( $css_value );
								$breakpoints[ $bp ]['tmp'] .= ( $bp ==  'desktop' ? '' : "\t" ) . "\t{$css_var}: {$css_value};\n";
								$breakpoints[ $bp ]['filled'] = true;
							}
						}
					}
				}
				foreach ( $breakpoints as $bp => $bpv ) {
					$breakpoints[ $bp ]['tmp'] .= ( $bp == 'desktop' ? '' : "\t}\n" ) . "}\n";
					if ( $bpv['filled'] ) {
						$css['vars'] .= "\n" . $breakpoints[ $bp ]['tmp'];
					}
				}
			}
		}

		// Add theme-specific values to css variables
		if ( isset( $css['vars'] ) && isset( $args['vars'] ) ) {
			$vars = $args['vars'];
			if ( is_array( $vars ) && count( $vars ) > 0 ) {
				$tmp = ":root {\n";
				// Set a default value for the sidebar proportional (if absent)
				if ( ! isset( $vars['sidebar_proportional'] ) ) {
					$vars['sidebar_proportional'] = 1;
				}
				// Set a new name for the original value of the sidebar gap
				if ( isset( $vars['sidebar_gap'] ) ) {
					$vars['sidebar_gap_width'] = elementra_prepare_css_value( $vars['sidebar_gap'] );
				}
				// Remove calculated values from css variables
				$exclude = apply_filters( 'elementra_filter_exclude_theme_vars', array( 'sidebar_gap' ) );	//Old case: array( 'sidebar_width', 'sidebar_gap' )
				// Add rest values to css variables
				foreach ( $vars as $var => $value ) {
					if ( ! in_array( $var, $exclude ) ) {
						$tmp .= "--theme-var-{$var}: " . ( empty( $value ) ? 0 : $value ) . ";\n";
					}
				}
				$css['vars'] = $tmp . "\n}\n" . $css['vars'];
			}
		}

		// Add theme-specific colors to css variables
		if ( isset( $css['colors'] ) && isset( $args['colors'] ) ) {
			$colors = $args['colors'];
			if ( is_array( $colors ) && count( $colors ) > 0 ) {
				$tmp = ".scheme_{$args['scheme']},body.scheme_{$args['scheme']},.scheme_{$args['scheme']}:where(.editor-styles-wrapper) {\n";
				foreach ( $colors as $color => $value ) {
					$tmp .= "--theme-color-{$color}: {$value};\n";
				}
				$css['colors'] = $tmp . "\n}\n" . $css['colors'];
			}
		}

		return $css;
	}
}

