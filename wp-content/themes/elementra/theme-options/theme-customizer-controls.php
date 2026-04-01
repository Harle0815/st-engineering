<?php
/**
 * Theme customizer: Custom controls
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0.31
 */


/**
 * Class Elementra_Customize_Theme_Control.
 *
 * A base class to create all theme-specific controls for Customizer.
 *
 * Extends class WP_Customize_Control.
 */
class Elementra_Customize_Theme_Control extends WP_Customize_Control {
	
	protected function start_render_field() {
		?>
		<div class="customize-control-wrap<?php
			if ( ! empty( $this->input_attrs['data-pro-only'] ) ) {
				echo ' elementra_options_pro_only';
			}
		?>">
		<?php
	}

	protected function end_render_field() {
		if ( ! empty( $this->input_attrs['data-pro-only'] ) ) {
			elementra_show_layout( elementra_add_inherit_cover( $this->id, array( 'type' => $this->type, 'pro_only' => true ) ) );
		}
		?>
		</div>
		<?php
	}

	protected function render_field_title() {
		if ( ! empty( $this->label ) ) {
			?>
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php
		}
	}

	protected function render_field_description() {
		if ( ! empty( $this->description ) ) {
			?>
			<span class="customize-control-description description"><?php elementra_show_layout( $this->description ); ?></span>
			<?php
		}
	}

	protected function render_content() {
		$this->start_render_field();
		if ( ! empty( $this->input_attrs['data-pro-only'] ) && 'checkbox' == $this->type ) {
			$this->render_field_title();
			$this->render_field_description();
		}
		parent::render_content();
		$this->end_render_field();
	}

}


/**
 * Class Elementra_Customize_Info_Control.
 * 
 * A control to display an information block in the Customizer.
 */
class Elementra_Customize_Info_Control extends Elementra_Customize_Theme_Control {
	public $type = 'info';

	protected function render_content() {
		$this->start_render_field();
		$this->render_field_title();
		$this->render_field_description();
		$this->end_render_field();
	}
}


/**
 * Class Elementra_Customize_Hidden_Control.
 * 
 * A control to render a hidden input field in the Customizer.
 * This is used for storing values that do not require user interaction.
 */
class Elementra_Customize_Hidden_Control extends Elementra_Customize_Theme_Control {
	public $type = 'hidden';

	protected function render_content() {
		?>
		<input type="hidden" name="_customize-hidden-<?php echo esc_attr( $this->id ); ?>" value=""
			<?php
			$this->link();
			if ( ! empty( $this->input_attrs['var_name'] ) ) {
				echo ' data-var_name="' . esc_attr( $this->input_attrs['var_name'] ) . '"';
			}
			?>
		>
		<?php
		// We need to fire action 'admin_print_footer_scripts' if this is a last option
		if ( 'last_option' == $this->id && elementra_storage_get( 'need_footer_scripts', false ) ) {
			elementra_storage_set( 'need_footer_scripts', false );
			do_action( 'admin_print_footer_scripts' );
		}
	}
}


/**
 * Class Elementra_Customize_Button_Control.
 * 
 * A control to render a button in the Customizer.
 * It can be used for actions like opening links or triggering JavaScript actions.
 */
class Elementra_Customize_Button_Control extends Elementra_Customize_Theme_Control {
	public $type = 'button';

	protected function render_content() {
		$this->start_render_field();
		$this->render_field_title();
		$this->render_field_description();
		if ( ! empty( $this->input_attrs['link'] ) ) {
			?>
			<a href="<?php echo esc_url( $this->input_attrs['link'] ); ?>"<?php
				echo elementra_external_links_target( true );
				if ( ! empty( $this->input_attrs['class'] ) ) {
					echo ' class="' . esc_attr( $this->input_attrs['class'] ) . '"';
				}
				?>
			>
				<?php
				echo esc_html( $this->input_attrs['caption'] );
				?>
			</a>
			<?php
		} elseif ( ! empty( $this->input_attrs['action'] ) ) {
			?>
			<input type="button" 
				<?php
				if ( ! empty( $this->input_attrs['class'] ) ) {
					echo ' class="' . esc_attr( $this->input_attrs['class'] ) . '"';
				}
				?>
				name="_customize-button-<?php echo esc_attr( $this->id ); ?>" 
				value="<?php echo esc_attr( $this->input_attrs['caption'] ); ?>"
				data-action="<?php echo esc_attr( $this->input_attrs['action'] ); ?>"
			>
			<?php
		}
		$this->end_render_field();
	}
}


/**
 * Class Elementra_Customize_Switch_Control.
 * 
 * A control to render a switch (toggle) input in the Customizer.
 * This control allows users to toggle a setting on or off.
 */
class Elementra_Customize_Switch_Control extends Elementra_Customize_Theme_Control {
	public $type = 'switch';

	protected function render_content() {
		$this->start_render_field();
		$this->render_field_title();
		$this->render_field_description();
		?>
		<label class="customize-control-field-wrap elementra_options_item_switch">
			<input type="hidden"
				<?php
				$this->link();
				if ( ! empty( $this->input_attrs['var_name'] ) ) {
					echo ' data-var_name="' . esc_attr( $this->input_attrs['var_name'] ) . '"';
				}
				?>
				value="<?php
					if ( ! empty( $this->input_attrs['value'] ) ) {
						echo esc_attr( $this->input_attrs['value'] );
					}
					?>"
			/>
			<input type="checkbox" value="1" <?php
				if ( ! empty( $this->input_attrs['value'] ) ) {
					?> checked="checked"<?php
				}
				?>
			/>
			<span class="elementra_options_item_holder" tabindex="0">
				<span class="elementra_options_item_holder_back"></span>
				<span class="elementra_options_item_holder_handle"></span>
			</span>
			<?php if ( ! empty( $this->label ) ) { ?>
				<span class="elementra_options_item_caption">
					<?php echo esc_html( $this->label ); ?>
				</span>
			<?php } ?>
		</label>
		<?php
		$this->end_render_field();
	}
}


/**
 * Class Elementra_Customize_Icon_Control.
 * 
 * A control to render an icon selector in the Customizer.
 * This control allows users to select an icon from a predefined set from the current theme skin.
 */
class Elementra_Customize_Icon_Control extends Elementra_Customize_Theme_Control {
	public $type = 'icon';

	protected function render_content() {
		$this->start_render_field();
		$this->render_field_title();
		$this->render_field_description();
		?>
		<span class="customize-control-field-wrap"><input type="text" 
			<?php
			$this->link();
			if ( ! empty( $this->input_attrs['var_name'] ) ) {
				echo ' data-var_name="' . esc_attr( $this->input_attrs['var_name'] ) . '"';
			}
			?>
		/>
			<?php
			elementra_show_layout(
				elementra_show_custom_field(
					'_customize-icon-selector-' . esc_attr( $this->id ),
					array(
						'type'   => 'icons',
						'button' => true,
						'icons'  => true,
					),
					$this->input_attrs['value']
				)
			);
			?>
		</span>
		<?php
		$this->end_render_field();
	}
}


/**
 * Class Elementra_Customize_Checklist_Control.
 * 
 * A control to render a checklist input in the Customizer.
 * This control allows users to select multiple options from a list.
 */
class Elementra_Customize_Checklist_Control extends Elementra_Customize_Theme_Control {
	public $type = 'checklist';

	protected function render_content() {
		$this->start_render_field();
		$this->render_field_title();
		$this->render_field_description();
		?>
		<span class="customize-control-field-wrap"><input type="hidden" 
			<?php
			$this->link();
			if ( ! empty( $this->input_attrs['var_name'] ) ) {
				echo ' data-var_name="' . esc_attr( $this->input_attrs['var_name'] ) . '"';
			}
			?>
		/>
			<?php
			elementra_show_layout(
				elementra_show_custom_field(
					'_customize-checklist-' . esc_attr( $this->id ),
					array_merge(
						$this->input_attrs, array(
							'options' => $this->choices,
						)
					),
					$this->input_attrs['value']
				)
			);
			?>
		</span>
		<?php
		$this->end_render_field();
	}
}


/**
 * Class Elementra_Customize_Choice_Control.
 * 
 * A control to render a choice input in the Customizer.
 * This control allows users to select a single option from a list of choices.
 */
class Elementra_Customize_Choice_Control extends Elementra_Customize_Theme_Control {
	public $type = 'choice';

	protected function render_content() {
		$this->start_render_field();
		$this->render_field_title();
		$this->render_field_description();
		?>
		<span class="customize-control-field-wrap"><input type="hidden" 
			<?php
			$this->link();
			if ( ! empty( $this->input_attrs['var_name'] ) ) {
				echo ' data-var_name="' . esc_attr( $this->input_attrs['var_name'] ) . '"';
			}
			?>
		/>
			<?php
			elementra_show_layout(
				elementra_show_custom_field(
					'_customize-choice-' . esc_attr( $this->id ),
					array_merge(
						$this->input_attrs, array(
							'options' => $this->choices,
						)
					),
					$this->input_attrs['value']
				)
			);
			?>
		</span>
		<?php
		$this->end_render_field();
	}
}


/**
 * Class Elementra_Customize_Scheme_Editor_Control.
 * 
 * A control to render a scheme editor in the Customizer.
 * This control allows users to edit each color scheme from the skin.
 */
class Elementra_Customize_Scheme_Editor_Control extends Elementra_Customize_Theme_Control {
	public $type = 'scheme_editor';

	protected function render_content() {
		$this->start_render_field();
		$this->render_field_title();
		$this->render_field_description();
		?>
		<span class="customize-control-field-wrap"><input type="hidden" 
			<?php
			$this->link();
			if ( ! empty( $this->input_attrs['var_name'] ) ) {
				echo ' data-var_name="' . esc_attr( $this->input_attrs['var_name'] ) . '"';
			}
			?>
		/>
			<?php
			elementra_show_layout(
				elementra_show_custom_field(
					'_customize-scheme-editor-' . esc_attr( $this->id ),
					$this->input_attrs,
					elementra_unserialize( $this->input_attrs['value'], true )
				)
			);
			?>
		</span>
		<?php
		$this->end_render_field();
	}
}


/**
 * Class Elementra_Customize_Text_Editor_Control.
 * 
 * A control to render a text editor in the Customizer.
 * This control allows users to input rich text content.
 */
class Elementra_Customize_Text_Editor_Control extends Elementra_Customize_Theme_Control {
	public $type = 'text_editor';

	protected function render_content() {
		$this->start_render_field();
		$this->render_field_title();
		$this->render_field_description();
		?>
		<span class="customize-control-field-wrap"><input type="hidden" 
			<?php
			$this->link();
			if ( ! empty( $this->input_attrs['var_name'] ) ) {
				echo ' data-var_name="' . esc_attr( $this->input_attrs['var_name'] ) . '"';
			}
			?>
			value="<?php echo esc_textarea( $this->value() ); ?>"
		/>
			<?php
			elementra_show_layout(
				elementra_show_custom_field(
					'_customize-text-editor-' . esc_attr( $this->id ),
					$this->input_attrs,
					$this->input_attrs['value']
				)
			);
			?>
		</span>
		<?php
		$this->end_render_field();
		// We need to fire action 'admin_print_footer_scripts' when the last option is render
		elementra_storage_set( 'need_footer_scripts', true );
	}
}


/**
 * Class Elementra_Customize_Range_Control.
 * 
 * A control to render a range slider in the Customizer.
 * This control allows users to select a minimum and maximum value using a slider.
 */
class Elementra_Customize_Range_Control extends Elementra_Customize_Theme_Control {
	public $type = 'range';

	protected function render_content() {
		$this->start_render_field();
		$this->render_field_title();
		$this->render_field_description();
		$show_value = ! isset( $this->input_attrs['show_value'] ) || $this->input_attrs['show_value'];
		?>
		<span class="customize-control-field-wrap"><input type="<?php echo ! $show_value ? 'hidden' : 'text'; ?>" 
			<?php
			$this->link();
			if ( $show_value ) {
				echo ' class="elementra_range_slider_value"';
			}
			if ( ! empty( $this->input_attrs['var_name'] ) ) {
				echo ' data-var_name="' . esc_attr( $this->input_attrs['var_name'] ) . '"';
			}
			?>
		/>
			<?php
			elementra_show_layout(
				elementra_show_custom_field(
					'_customize-range-' . esc_attr( $this->id ),
					$this->input_attrs,
					$this->input_attrs['value']
				)
			);
			?>
		</span>
		<?php
		$this->end_render_field();
	}
}
