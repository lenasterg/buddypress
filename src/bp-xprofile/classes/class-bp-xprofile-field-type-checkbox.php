<?php
/**
 * BuddyPress XProfile Classes.
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Checkbox xprofile field type.
 *
 * @since 2.0.0
 */
class BP_XProfile_Field_Type_Checkbox extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the checkbox field type.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Multi Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Checkboxes', 'xprofile field type', 'buddypress' );

		$this->supports_multiple_defaults = true;
		$this->accepts_null_value         = true;
		$this->supports_options           = true;

		$this->set_format( '/^.+$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Checkbox class.
		 *
		 * @since 2.0.0
		 *
		 * @param BP_XProfile_Field_Type_Checkbox $field_type Current instance of the field type class.
		 */
		do_action( 'bp_xprofile_field_type_checkbox', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_properties Optional key/value array of
	 *                              {@link http://dev.w3.org/html5/markup/input.checkbox.html permitted attributes}
	 *                              that you want to add.
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// User_id is a special optional parameter that we pass to
		// {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		} else {
			$user_id = bp_displayed_user_id();
		} ?>

			<legend>
				<?php bp_the_profile_field_name(); ?>
				<?php bp_the_profile_field_required_label(); ?>
			</legend>

			<?php if ( bp_get_the_profile_field_description() ) : ?>
				<p class="description" tabindex="0"><?php bp_the_profile_field_description(); ?></p>
			<?php endif; ?>

			<?php

			/** This action is documented in bp-xprofile/bp-xprofile-classes */
			do_action( bp_get_the_profile_field_errors_action() ); ?>

			<?php bp_the_profile_field_options( array(
				'user_id' => $user_id,
			) ); ?>

		<?php
	}

	/**
	 * Output the edit field options HTML for this field type.
	 *
	 * BuddyPress considers a field's "options" to be, for example, the items in a selectbox.
	 * These are stored separately in the database, and their templating is handled separately.
	 *
	 * This templating is separate from {@link BP_XProfile_Field_Type::edit_field_html()} because
	 * it's also used in the wp-admin screens when creating new fields, and for backwards compatibility.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 */
	public function edit_field_options_html( array $args = array() ) {
		$options       = $this->field_obj->get_children();
		$option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] ) );

		/*
		 * Determine whether to pre-select the default option.
		 *
		 * If there's no saved value, take the following into account:
		 * If the user has never saved a value for this field,
		 * $option_values will be an empty string, and we should pre-select the default option.
		 * If the user has specifically chosen none of the options,
		 * $option_values will be an empty array, and we should respect that value.
		 */
		$select_default_option = false;
		if ( empty( $option_values ) && ! is_array( $option_values ) ) {
			$select_default_option = true;
		}

		$option_values = ( $option_values ) ? (array) $option_values : array();

		$html = '';

		// Check for updated posted values, but errors preventing them from
		// being saved first time.
		if ( isset( $_POST[ 'field_' . $this->field_obj->id ] ) && $option_values != maybe_serialize( $_POST[ 'field_' . $this->field_obj->id ] ) ) {
			if ( ! empty( $_POST[ 'field_' . $this->field_obj->id ] ) ) {
				$option_values = array_map( 'sanitize_text_field', $_POST[ 'field_' . $this->field_obj->id ] );
			}
		}

		for ( $k = 0, $count = count( $options ); $k < $count; ++$k ) {
			$selected = '';

			// First, check to see whether the user's saved values match the option.
			for ( $j = 0, $count_values = count( $option_values ); $j < $count_values; ++$j ) {

				// Run the allowed option name through the before_save filter,
				// so we'll be sure to get a match.
				$allowed_options = xprofile_sanitize_data_value_before_save( $options[ $k ]->name, false, false );

				if ( $option_values[ $j ] === $allowed_options || in_array( $allowed_options, $option_values ) ) {
					$selected = ' checked="checked"';
					break;
				}
			}

			// If the user has not yet supplied a value for this field, check to
			// see whether there is a default value available.
			if ( empty( $selected ) && $select_default_option && ! empty( $options[ $k ]->is_default_option ) ) {
				$selected = ' checked="checked"';
			}

			$new_html = sprintf( '<label for="%3$s" class="option-label"><input %1$s type="checkbox" name="%2$s" id="%3$s" value="%4$s">%5$s</label>',
				$selected,
				esc_attr( bp_get_the_profile_field_input_name() . '[]' ),
				esc_attr( "field_{$options[$k]->id}_{$k}" ),
				esc_attr( stripslashes( $options[ $k ]->name ) ),
				esc_html( stripslashes( $options[ $k ]->name ) )
			);

			/**
			 * Filters the HTML output for an individual field options checkbox.
			 *
			 * @since 1.1.0
			 *
			 * @param string $new_html Label and checkbox input field.
			 * @param object $value    Current option being rendered for.
			 * @param int    $id       ID of the field object being rendered.
			 * @param string $selected Current selected value.
			 * @param string $k        Current index in the foreach loop.
			 */
			$html .= apply_filters( 'bp_get_the_profile_field_options_checkbox', $new_html, $options[ $k ], $this->field_obj->id, $selected, $k );
		}

		printf(
			'<div id="%1$s" class="input-options checkbox-options">%2$s</div>',
			esc_attr( 'field_' . $this->field_obj->id ),
			// phpcs:ignore WordPress.Security.EscapeOutput
			$html
		);
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		bp_the_profile_field_options();
	}

	/**
	 * Output HTML for this field type's children options on the wp-admin Profile Fields "Add Field" and "Edit Field" screens.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string            $control_type  Optional. HTML input type used to render the current
	 *                                         field's child options.
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		parent::admin_new_field_html( $current_field, 'checkbox' );
	}
}
