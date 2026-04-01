<?php
/**
 * Widget: Categories list (Elementor support)
 *
 * @package ThemeREX Addons
 * @since v1.0
 */

// Don't load directly
if ( ! defined( 'TRX_ADDONS_VERSION' ) ) {
	exit;
}



// Elementor Widget
//------------------------------------------------------
if (!function_exists('trx_addons_sc_widget_categories_list_add_in_elementor')) {
	add_action( trx_addons_elementor_get_action_for_widgets_registration(), 'trx_addons_sc_widget_categories_list_add_in_elementor' );
	function trx_addons_sc_widget_categories_list_add_in_elementor() {
		
		if (!class_exists('TRX_Addons_Elementor_Widget')) return;	

		class TRX_Addons_Elementor_Widget_Categories_List extends TRX_Addons_Elementor_Widget {

			/**
			 * Widget base constructor.
			 *
			 * Initializing the widget base class.
			 *
			 * @since 1.6.41
			 * @access public
			 *
			 * @param array      $data Widget data. Default is an empty array.
			 * @param array|null $args Optional. Widget default arguments. Default is null.
			 */
			public function __construct( $data = [], $args = null ) {
				parent::__construct( $data, $args );
				$this->add_plain_params([
					'number' => 'size'
				]);
			}

			/**
			 * Retrieve widget name.
			 *
			 * @since 1.6.41
			 * @access public
			 *
			 * @return string Widget name.
			 */
			public function get_name() {
				return 'trx_widget_categories_list';
			}

			/**
			 * Retrieve widget title.
			 *
			 * @since 1.6.41
			 * @access public
			 *
			 * @return string Widget title.
			 */
			public function get_title() {
				return __( 'Categories List', 'trx_addons' );
			}

			/**
			 * Get widget keywords.
			 *
			 * Retrieve the list of keywords the widget belongs to.
			 *
			 * @since 2.27.2
			 * @access public
			 *
			 * @return array Widget keywords.
			 */
			public function get_keywords() {
				return [ 'categories', 'list', 'taxonomies', 'terms' ];
			}

			/**
			 * Retrieve widget icon.
			 *
			 * @since 1.6.41
			 * @access public
			 *
			 * @return string Widget icon.
			 */
			public function get_icon() {
				return 'eicon-posts-grid trx_addons_elementor_widget_icon';
			}

			/**
			 * Retrieve the list of categories the widget belongs to.
			 *
			 * Used to determine where to display the widget in the editor.
			 *
			 * @since 1.6.41
			 * @access public
			 *
			 * @return array Widget categories.
			 */
			public function get_categories() {
				return ['trx_addons-elements'];
			}

			/**
			 * Register widget controls.
			 *
			 * Adds different input fields to allow the user to change and customize the widget settings.
			 *
			 * @since 1.6.41
			 * @access protected
			 */
			protected function register_controls() {
				$this->register_controls_content_general();
				
				if ( $this->styles_allowed ) {
					$this->register_controls_style_items();
					$this->register_controls_style_image();
					$this->register_controls_style_title();
				}
			}

			/**
			 * Register widget controls: tab 'Content' section 'Categories List'.
			 */
			protected function register_controls_content_general() {

				// Detect edit mode
				$is_edit_mode = trx_addons_elm_is_edit_mode();
				// If open params in Elementor Editor
				$params = $this->get_sc_params();
				// Prepare lists
				$post_type = !empty($params['post_type']) ? $params['post_type'] : 'post';
				$taxonomy = !empty($params['taxonomy']) ? $params['taxonomy'] : 'category';
				$tax_obj = get_taxonomy($taxonomy);

				$this->start_controls_section(
					'section_sc_categories_list',
					[
						'label' => __( 'Categories List', 'trx_addons' ),
					]
				);

				$this->add_control(
					'title',
					[
						'label' => __( 'Title', 'trx_addons' ),
						'label_block' => false,
						'type' => \Elementor\Controls_Manager::TEXT,
						'placeholder' => __( "Widget title", 'trx_addons' ),
						'default' => ''
					]
				);

				$this->add_control(
					'style',
					[
						'label' => __( 'Style', 'trx_addons' ),
						'label_block' => false,
						'type' => \Elementor\Controls_Manager::SELECT,
						'options' => apply_filters('trx_addons_sc_type', trx_addons_components_get_allowed_layouts('widgets', 'categories_list'), 'trx_widget_categories_list'),
						'default' => 1,
					]
				);

				$this->add_control(
					'post_type',
					[
						'label' => __( 'Post type', 'trx_addons' ),
						'type' => \Elementor\Controls_Manager::SELECT,
						'options' => ! $is_edit_mode ? array() : trx_addons_get_list_posts_types(),
						'default' => 'post'
					]
				);

				$this->add_control(
					'taxonomy',
					[
						'label' => __( 'Taxonomy', 'trx_addons' ),
						'type' => \Elementor\Controls_Manager::SELECT,
						'options' => ! $is_edit_mode ? array() : trx_addons_get_list_taxonomies(false, $post_type),
						'default' => 'category'
					]
				);

				$this->add_control(
					'cat_list',
					[
						'label' => __( 'Categories', 'trx_addons' ),
						'type' => \Elementor\Controls_Manager::SELECT2,
						'options' => ! $is_edit_mode
										? array()
											// Make keys as string (add a space after the number) to preserve the order in the list
											// (otherwise the keys will be converted to numbers in the JS and the order will be broken)
										: trx_addons_array_make_string_keys(
												trx_addons_array_merge(
													array( 0 => trx_addons_get_not_selected_text( ! empty( $tax_obj->label ) ? $tax_obj->label : __( '- Not Selected -', 'trx_addons' ) ) ),
													array_map( 'strip_tags', $taxonomy == 'category' 
																				? trx_addons_get_list_categories() 
																				: trx_addons_get_list_terms(false, $taxonomy)
													)
												)
											),
						'multiple' => true,
						'default' => []
					]
				);

				$this->add_control(
					'cat_order',
					[
						'label' => __( 'Categories order', 'trx_addons' ),
						'label_block' => true,
						'type' => \Elementor\Controls_Manager::TEXT,
						'placeholder' => __( "Comma separated term slugs or IDs", 'trx_addons' ),
						'default' => ''
					]
				);

				$this->add_control(
					'number',
					[
						'label' => __( 'Number', 'trx_addons' ),
						'description' => wp_kses_data( __("Specify the number of categories to show", 'trx_addons') ),
						'type' => \Elementor\Controls_Manager::SLIDER,
						'default' => [
							'size' => 0
						],
						'range' => [
							'px' => [
								'min' => 0,
								'max' => 12
							]
						]
					]
				);

				$this->add_responsive_control(
					'columns',
					[
						'label' => __( 'Columns', 'trx_addons' ),
						'description' => wp_kses_data( __("Specify the number of columns. If left empty or assigned the value '0' - auto detect by the number of items.", 'trx_addons') ),
						'type' => \Elementor\Controls_Manager::SLIDER,
						'default' => [
							'size' => 0
						],
						'range' => [
							'px' => [
								'min' => 0,
								'max' => 12
							]
						]
					]
				);

				$this->add_control(
					'show_thumbs',
					[
						'label' => __( 'Show thumbs', 'trx_addons' ),
						'label_block' => false,
						'type' => \Elementor\Controls_Manager::SWITCHER,
						'label_off' => __( 'Off', 'trx_addons' ),
						'label_on' => __( 'On', 'trx_addons' ),
						'default' => '1',
						'return_value' => '1'
					]
				);

				$this->add_control(
					'show_posts',
					[
						'label' => __( 'Show posts number', 'trx_addons' ),
						'label_block' => false,
						'type' => \Elementor\Controls_Manager::SWITCHER,
						'label_off' => __( 'Off', 'trx_addons' ),
						'label_on' => __( 'On', 'trx_addons' ),
						'default' => '1',
						'return_value' => '1'
					]
				);

				$this->add_control(
					'show_children',
					[
						'label' => __( 'Show children', 'trx_addons' ),
						'label_block' => false,
						'description' => wp_kses_data( __("Show only children of the current category", 'trx_addons') ),
						'type' => \Elementor\Controls_Manager::SWITCHER,
						'label_off' => __( 'Off', 'trx_addons' ),
						'label_on' => __( 'On', 'trx_addons' ),
						'default' => '0',
						'return_value' => '1'
					]
				);

				$this->end_controls_section();

				$this->add_slider_param();
			}

			/**
			 * Register widget controls: tab 'Style' section 'Items'
			 */
			protected function register_controls_style_items() {

				$this->start_controls_section(
					'section_sc_categories_list_items_style',
					[
						'label' => __( 'Item Container', 'trx_addons' ),
						'tab' => \Elementor\Controls_Manager::TAB_STYLE
					]
				);

				$this->start_controls_tabs( 'tabs_sc_categories_list_item_style' );

				$this->start_controls_tab(
					'tabs_sc_categories_list_item_style_normal',
					[
						'label' => __( 'Normal', 'trx_addons' ),
					]
				);

				$this->add_control(
					"item_overlay",
					[
						'label' => __( 'Overlay', 'trx_addons' ),
						'label_block' => false,
						'type' => \Elementor\Controls_Manager::COLOR,
						'default' => '',
						'selectors' => [
							'{{WRAPPER}} .categories_list_style_2 .categories_list_image:after' => 'background: {{VALUE}};',
						],
						'condition' => [
							'style' => '2'
						],
					]
				);

				$this->add_group_control(
					\Elementor\Group_Control_Background::get_type(),
					[
						'name' => 'item_background',
						'selector' => '{{WRAPPER}} .categories_list_item,
									   {{WRAPPER}} .sc_item_button > .sc_button',
					]
				);

				$this->add_group_control(
					\Elementor\Group_Control_Border::get_type(),
					array(
						'name'        => 'item_border',
						'label'       => __( 'Border', 'trx_addons' ),
						'placeholder' => '1px',
						'default'     => '1px',
						'selector'    => '{{WRAPPER}} .categories_list_item,
										  {{WRAPPER}} .sc_item_button > .sc_button',
					)
				);
		
				$this->add_responsive_control(
					'item_border_radius',
					array(
						'label'      => __( 'Border Radius', 'trx_addons' ),
						'type'       => \Elementor\Controls_Manager::DIMENSIONS,
						'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
						'selectors'  => array(
										'{{WRAPPER}} .categories_list_item,
										 {{WRAPPER}} .sc_item_button > .sc_button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						),
					)
				);

				$this->add_responsive_control(
					'item_padding',
					[
						'label'                 => esc_html__( 'Padding', 'trx_addons' ),
						'type'                  => \Elementor\Controls_Manager::DIMENSIONS,
						'size_units'            => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
						'selectors'             => [
							'{{WRAPPER}} .categories_list_item,
							 {{WRAPPER}} .sc_item_button > .sc_button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						],
					]
				);
		
				$this->add_group_control(
					\Elementor\Group_Control_Box_Shadow::get_type(),
					[
						'name'     => 'item_box_shadow',
						'selector' => '{{WRAPPER}} .categories_list_item,
									   {{WRAPPER}} .sc_item_button > .sc_button',
					]
				);

				$this->end_controls_tab();

				$this->start_controls_tab(
					'tabs_sc_categories_list_item_style_hover',
					[
						'label' => __( 'Hover', 'trx_addons' ),
					]
				);

				$this->add_control(
					"item_overlay_hover",
					[
						'label' => __( 'Overlay', 'trx_addons' ),
						'label_block' => false,
						'type' => \Elementor\Controls_Manager::COLOR,
						'default' => '',
						'selectors' => [
							'{{WRAPPER}} .categories_list_style_2 .categories_list_item:hover .categories_list_image:after' => 'background: {{VALUE}};',
						],
						'condition' => [
							'style' => '2'
						],
					]
				);

				$this->add_group_control(
					\Elementor\Group_Control_Background::get_type(),
					[
						'name' => 'item_background_hover',
						'selector' => '{{WRAPPER}} .categories_list_item:hover,
									   {{WRAPPER}} .sc_item_button > .sc_button:hover',
					]
				);

				$this->add_group_control(
					\Elementor\Group_Control_Border::get_type(),
					array(
						'name'        => 'item_border_hover',
						'label'       => __( 'Border', 'trx_addons' ),
						'placeholder' => '1px',
						'default'     => '1px',
						'selector'    => '{{WRAPPER}} .categories_list_item:hover,
										  {{WRAPPER}} .sc_item_button > .sc_button:hover',
					)
				);
		
				$this->add_responsive_control(
					'item_border_radius_hover',
					array(
						'label'      => __( 'Border Radius', 'trx_addons' ),
						'type'       => \Elementor\Controls_Manager::DIMENSIONS,
						'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
						'selectors'  => array(
										'{{WRAPPER}} .categories_list_item:hover,
										 {{WRAPPER}} .sc_item_button > .sc_button:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						),
					)
				);
		
				$this->add_group_control(
					\Elementor\Group_Control_Box_Shadow::get_type(),
					[
						'name'     => 'item_box_shadow_hover',
						'selector' => '{{WRAPPER}} .categories_list_item:hover,
									   {{WRAPPER}} .sc_item_button > .sc_button:hover',
					]
				);

				$this->end_controls_tab();

				$this->end_controls_tabs();

				$this->end_controls_section();
			}

			/**
			 * Register widget controls: tab 'Style' section 'Image'
			 */
			protected function register_controls_style_image() {

				$this->start_controls_section(
					'section_sc_categories_list_image_style',
					[
						'label' => __( 'Item Image', 'trx_addons' ),
						'tab' => \Elementor\Controls_Manager::TAB_STYLE,
						'condition' => [
							'style!' => '4'
						],
					]
				);

				$this->add_group_control(
					\Elementor\Group_Control_Background::get_type(),
					[
						'name' => 'image_background',
						'selector' => '{{WRAPPER}} .categories_list_image',
					]
				);

				$this->add_group_control(
					\Elementor\Group_Control_Border::get_type(),
					array(
						'name'        => 'image_border',
						'label'       => __( 'Border', 'trx_addons' ),
						'placeholder' => '1px',
						'default'     => '1px',
						'selector'    => '{{WRAPPER}} .categories_list_image',
					)
				);
		
				$this->add_responsive_control(
					'image_border_radius',
					array(
						'label'      => __( 'Border Radius', 'trx_addons' ),
						'type'       => \Elementor\Controls_Manager::DIMENSIONS,
						'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
						'selectors'  => array(
										'{{WRAPPER}} .categories_list_image,
										 {{WRAPPER}} .categories_list_image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						),
					)
				);

				$this->add_responsive_control(
					'image_padding',
					[
						'label'                 => esc_html__( 'Padding', 'trx_addons' ),
						'type'                  => \Elementor\Controls_Manager::DIMENSIONS,
						'size_units'            => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
						'selectors'             => [
							'{{WRAPPER}} .categories_list_image' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						],
						'condition' => [
							'style!' => ['3', '4']
						],
					]
				);
		
				$this->add_group_control(
					\Elementor\Group_Control_Box_Shadow::get_type(),
					[
						'name'     => 'image_box_shadow',
						'selector' => '{{WRAPPER}} .categories_list_image',
					]
				);
		
				$this->end_controls_section();
			}

			/**
			 * Register widget controls: tab 'Style' section 'Title'
			 */
			protected function register_controls_style_title() {

				$this->start_controls_section(
					'section_sc_categories_list_title_style',
					[
						'label' => __( 'Item Title', 'trx_addons' ),
						'tab' => \Elementor\Controls_Manager::TAB_STYLE,
					]
				);

				$this->add_responsive_control(
					'title_vertical_position',
					[
						'label'                 => __( 'Vertical Position', 'trx_addons' ),
						'type'                  => \Elementor\Controls_Manager::CHOOSE,
						'label_block'           => false,
						'options'               => [
							'top' => [
								'title' => __( 'Top', 'trx_addons' ),
								'icon' => 'eicon-v-align-top',
							],
							'middle' => [
								'title' => __( 'Middle', 'trx_addons' ),
								'icon' => 'eicon-v-align-middle',
							],
							'bottom' => [
								'title' => __( 'Bottom', 'trx_addons' ),
								'icon' => 'eicon-v-align-bottom',
							],
						],
						'selectors_dictionary'  => [
							'top' => 'top:0; transform: translateX(-50%);',
							'middle' => 'top:50%; transform: translate(-50%, -50%);',
							'bottom' => 'top: auto; bottom:0; transform: translateX(-50%);',
						],
						'selectors'             => [
							'{{WRAPPER}} .categories_list_title' => '{{VALUE}}',
						],
						'condition' => [
							'style' => '2'
						],
					]
				);

				$this->add_responsive_control(
					'title_width',
					[
						'label'                 => __( 'Width (in %)', 'trx_addons' ),
						'type'                  => \Elementor\Controls_Manager::SLIDER,
						'size_units'            => [ 'px' ],
						'range'                 => [
							'px' => [
								'min' => 0,
								'max' => 100,
							],
						],
						'selectors'             => [
							'{{WRAPPER}} .categories_list_title' => 'width: {{SIZE}}%;',
						],
						'condition' => [
							'style!' => '4'
						]
					]
				);

				$this->add_control(
					'title_counter_inline',
					[
						'label'                 => esc_html__( 'Counter inline', 'trx_addons' ),
						'label_block'           => false,
						'type'                  => \Elementor\Controls_Manager::SWITCHER,
						'selectors'             => [
							'{{WRAPPER}} .categories_list_title .categories_list_count' => 'display: inline-block; margin-left: 0.3em;',
						],
						'condition' => [
							'style!' => '4'
						]
					]
				);

				$this->start_controls_tabs( 'tabs_sc_categories_list_title_style' );

				$this->start_controls_tab(
					'tabs_sc_categories_list_title_style_normal',
					[
						'label' => __( 'Normal', 'trx_addons' ),
					]
				);

				$this->add_control(
					"title_color",
					[
						'label' => __( 'Title Color', 'trx_addons' ),
						'label_block' => false,
						'type' => \Elementor\Controls_Manager::COLOR,
						'default' => '',
						'selectors' => [
							'{{WRAPPER}} .categories_list_title' => 'color: {{VALUE}};',
						]
					]
				);

				$this->add_group_control(
					\Elementor\Group_Control_Background::get_type(),
					[
						'name' => 'title_background',
						'selector' => '{{WRAPPER}} .categories_list_title',
						'condition' => [
							'style!' => '4'
						]
					]
				);

				$this->add_group_control(
					\Elementor\Group_Control_Border::get_type(),
					array(
						'name'        => 'title_border',
						'label'       => __( 'Border', 'trx_addons' ),
						'placeholder' => '1px',
						'default'     => '1px',
						'selector'    => '{{WRAPPER}} .categories_list_title',
						'condition' => [
							'style!' => '4'
						]
					)
				);
		
				$this->add_responsive_control(
					'title_border_radius',
					array(
						'label'      => __( 'Border Radius', 'trx_addons' ),
						'type'       => \Elementor\Controls_Manager::DIMENSIONS,
						'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
						'selectors'  => array(
										'{{WRAPPER}} .categories_list_title' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						),
						'condition' => [
							'style!' => '4'
						]
					)
				);

				$this->add_responsive_control(
					'title_padding',
					[
						'label'                 => esc_html__( 'Padding', 'trx_addons' ),
						'type'                  => \Elementor\Controls_Manager::DIMENSIONS,
						'size_units'            => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
						'selectors'             => [
							'{{WRAPPER}} .categories_list_title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						],
						'condition' => [
							'style!' => '4'
						]
					]
				);

				$this->add_responsive_control(
					'title_margin',
					[
						'label'                 => esc_html__( 'Margin', 'trx_addons' ),
						'type'                  => \Elementor\Controls_Manager::DIMENSIONS,
						'size_units'            => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
						'selectors'             => [
							'{{WRAPPER}} .categories_list_style_1 .categories_list_title' => 'margin: {{TOP}}{{UNIT}} auto {{BOTTOM}}{{UNIT}} auto;',
							'{{WRAPPER}} .categories_list_style_2 .categories_list_title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
							'{{WRAPPER}} .categories_list_style_3 .categories_list_title' => 'margin: {{TOP}}{{UNIT}} auto {{BOTTOM}}{{UNIT}} auto;',
						],
						'condition' => [
							'style!' => '4'
						]
					]
				);
		
				$this->add_group_control(
					\Elementor\Group_Control_Box_Shadow::get_type(),
					[
						'name'     => 'title_box_shadow',
						'selector' => '{{WRAPPER}} .categories_list_title',
					]
				);

				$this->end_controls_tab();

				$this->start_controls_tab(
					'tabs_sc_categories_list_title_style_hover',
					[
						'label' => __( 'Hover', 'trx_addons' ),
					]
				);

				$this->add_control(
					"title_color_hover",
					[
						'label' => __( 'Title Color', 'trx_addons' ),
						'label_block' => false,
						'type' => \Elementor\Controls_Manager::COLOR,
						'default' => '',
						'selectors' => [
							'{{WRAPPER}} .categories_list_item:hover .categories_list_title' => 'color: {{VALUE}};',
						]
					]
				);

				$this->add_group_control(
					\Elementor\Group_Control_Background::get_type(),
					[
						'name' => 'title_background_hover',
						'selector' => '{{WRAPPER}} .categories_list_item:hover .categories_list_title',
						'condition' => [
							'style!' => '4'
						]
					]
				);

				$this->add_group_control(
					\Elementor\Group_Control_Border::get_type(),
					array(
						'name'        => 'title_border_hover',
						'label'       => __( 'Border', 'trx_addons' ),
						'placeholder' => '1px',
						'default'     => '1px',
						'selector'    => '{{WRAPPER}} .categories_list_item:hover .categories_list_title',
						'condition' => [
							'style!' => '4'
						]
					)
				);
		
				$this->add_responsive_control(
					'title_border_radius_hover',
					array(
						'label'      => __( 'Border Radius', 'trx_addons' ),
						'type'       => \Elementor\Controls_Manager::DIMENSIONS,
						'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
						'selectors'  => array(
										'{{WRAPPER}} .categories_list_item:hover .categories_list_title' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						),
						'condition' => [
							'style!' => '4'
						]
					)
				);
		
				$this->add_group_control(
					\Elementor\Group_Control_Box_Shadow::get_type(),
					[
						'name'     => 'title_box_shadow_hover',
						'selector' => '{{WRAPPER}} .categories_list_item:hover .categories_list_title',
						'condition' => [
							'style!' => '4'
						]
					]
				);

				$this->end_controls_tab();

				$this->end_controls_tabs();
		
				$this->end_controls_section();
			}

		}
		
		// Register widget
		trx_addons_elm_register_widget( 'TRX_Addons_Elementor_Widget_Categories_List' );
	}
}


// Disable our widgets (shortcodes) to use in Elementor
// because we create special Elementor's widgets instead
if (!function_exists('trx_addons_widget_categories_list_black_list')) {
	add_action( 'elementor/widgets/black_list', 'trx_addons_widget_categories_list_black_list' );
	function trx_addons_widget_categories_list_black_list($list) {
		$list[] = 'trx_addons_widget_categories_list';
		return $list;
	}
}
