<?php

class SiteOrigin_Panels_Styles_Admin {

	function __construct() {
		add_action( 'wp_ajax_so_panels_style_form', array( $this, 'action_style_form' ) );

		add_filter( 'siteorigin_panels_data', array( $this, 'convert_data' ) );
		add_filter( 'siteorigin_panels_prebuilt_layout', array( $this, 'convert_data' ) );
	}

	public static function single() {
		static $single;
		return empty( $single ) ? $single = new self() : $single;
	}

	/**
	 * Admin action for handling fetching the style fields
	 */
	function action_style_form() {
		$type = $_REQUEST['type'];
		if ( ! in_array( $type, array( 'row', 'cell', 'widget' ) ) ) {
			exit();
		}
		if ( empty( $_GET['_panelsnonce'] ) || ! wp_verify_nonce( $_GET['_panelsnonce'], 'panels_action' ) ) {
			exit();
		}

		$current = isset( $_REQUEST['style'] ) ? $_REQUEST['style'] : array();
		$post_id = empty( $_REQUEST['postId'] ) ? 0 : $_REQUEST['postId'];

		$args = ! empty( $_POST['args'] ) ? json_decode( stripslashes( $_POST['args'] ), true ) : array();

		switch ( $type ) {
			case 'row':
				$this->render_styles_fields( 'row', '<h3>' . __( 'Row Styles', 'siteorigin-panels' ) . '</h3>', '', $current, $post_id, $args );
				break;

			case 'cell':
				$cell_number = isset( $args['index'] ) ? ' ' . ( intval( $args['index'] ) + 1 ) : '';
				$this->render_styles_fields( 'cell', '<h3>' . sprintf( __( 'Cell%s Styles', 'siteorigin-panels' ), $cell_number ) . '</h3>', '', $current, $post_id, $args );
				break;

			case 'widget':
				$this->render_styles_fields( 'widget', '<h3>' . __( 'Widget Styles', 'siteorigin-panels' ) . '</h3>', '', $current, $post_id, $args );
				break;
		}

		wp_die();
	}

	/**
	 * Render all the style fields
	 *
	 * @param $section
	 * @param string $before
	 * @param string $after
	 * @param array $current
	 * @param int $post_id
	 * @param array $args Arguments passed by the builder
	 *
	 * @return bool
	 */
	function render_styles_fields( $section, $before = '', $after = '', $current = array(), $post_id = 0, $args = array() ) {
        $fields = array();
        $fields = apply_filters( 'siteorigin_panels_' . $section . '_style_fields', $fields, $post_id, $args );
        $fields = apply_filters( 'siteorigin_panels_general_style_fields', $fields, $post_id, $args );
        if ( empty( $fields ) ) {
			return false;
		}

		$groups = array(
			'attributes' => array(
				'name'     => __( 'Attributes', 'siteorigin-panels' ),
				'priority' => 5
			),
			'layout'     => array(
				'name'     => __( 'Layout', 'siteorigin-panels' ),
				'priority' => 10
			),
			'design'     => array(
				'name'     => __( 'Design', 'siteorigin-panels' ),
				'priority' => 15
			),
		);

		// Check if we need a default group
		foreach ( $fields as $field_id => $field ) {
			if ( empty( $field['group'] ) || $field['group'] == 'theme' ) {
				if ( empty( $groups['theme'] ) ) {
					$groups['theme'] = array(
						'name'     => __( 'Theme', 'siteorigin-panels' ),
						'priority' => 10
					);
				}
				$fields[ $field_id ]['group'] = 'theme';
			}
		}
		$groups = apply_filters( 'siteorigin_panels_' . $section . '_style_groups', $groups, $post_id, $args );
		$groups = apply_filters( 'siteorigin_panels_general_style_groups', $groups, $post_id, $args );

		// Sort the style fields and groups by priority
		uasort( $fields, array( $this, 'sort_fields' ) );
		uasort( $groups, array( $this, 'sort_fields' ) );

		echo $before;

		$group_counts = array();
		foreach ( $fields as $field_id => $field ) {
			if ( empty( $group_counts[ $field['group'] ] ) ) {
				$group_counts[ $field['group'] ] = 0;
			}
			$group_counts[ $field['group'] ] ++;
		}

		foreach ( $groups as $group_id => $group ) {

			if ( empty( $group_counts[ $group_id ] ) ) {
				continue;
			}

			?>
			<div class="style-section-wrapper">
				<div class="style-section-head">
					<h4><?php echo esc_html( $group['name'] ) ?></h4>
				</div>
				<div class="style-section-fields" style="display: none">
					<?php
					foreach ( $fields as $field_id => $field ) {
						$default = isset( $field['default'] ) ? $field['default'] : false;

						if ( $field['group'] == $group_id ) {
							?>
							<div class="style-field-wrapper">
								<label><?php echo $field['name'] ?></label>
								<div
									class="style-field style-field-<?php echo sanitize_html_class( $field['type'] ) ?>">
									<?php $this->render_style_field( $field, isset( $current[ $field_id ] ) ? $current[ $field_id ] : $default, $field_id ) ?>
								</div>
							</div>
							<?php

						}

					}
					?>
				</div>
			</div>
			<?php
		}

		echo $after;
	}

	/**
	 * Generate the style field
	 *
	 * @param $field
	 * @param $current
	 */
	function render_style_field( $field, $current, $field_id ) {
		$field_name = 'style[' . $field_id . ']';

		echo '<div class="style-input-wrapper">';
		switch ( $field['type'] ) {
			case 'measurement' :

				if ( ! empty( $field['multiple'] ) ) {
					?>
					<div class="measurement-inputs">
						<div class="measurement-wrapper">
							<input type="text" class="measurement-value measurement-top"
							       placeholder="<?php _e( 'Top', 'siteorigin-panels' ) ?>"/>
						</div>
						<div class="measurement-wrapper">
							<input type="text" class="measurement-value measurement-right"
							       placeholder="<?php _e( 'Right', 'siteorigin-panels' ) ?>"/>
						</div>
						<div class="measurement-wrapper">
							<input type="text" class="measurement-value measurement-bottom"
							       placeholder="<?php _e( 'Bottom', 'siteorigin-panels' ) ?>"/>
						</div>
						<div class="measurement-wrapper">
							<input type="text" class="measurement-value measurement-left"
							       placeholder="<?php _e( 'Left', 'siteorigin-panels' ) ?>"/>
						</div>
					</div>
					<?php
				} else {
					?><input type="text" class="measurement-value measurement-value-single"/><?php
				}

				?>
				<select
					class="measurement-unit measurement-unit-<?php echo ! empty( $field['multiple'] ) ? 'multiple' : 'single' ?>">
					<?php foreach ( $this->measurements_list() as $measurement ): ?>
						<option
							value="<?php echo esc_html( $measurement ) ?>"><?php echo esc_html( $measurement ) ?></option>
					<?php endforeach ?>
				</select>
				<input type="hidden" name="<?php echo esc_attr( $field_name ) ?>"
				       value="<?php echo esc_attr( $current ) ?>"/>
				<?php
				break;

			case 'color' :
				?>
				<input type="text" name="<?php echo esc_attr( $field_name ) ?>"
				       value="<?php echo esc_attr( $current ) ?>" class="so-wp-color-field"/>
				<?php
				break;

			case 'image' :
				$image = false;
				if ( ! empty( $current ) ) {
					$image = wp_get_attachment_image_src( $current, 'thumbnail' );
				}

				?>
				<div class="so-image-selector">
					<div class="current-image" <?php if ( ! empty( $image ) ) {
						echo 'style="background-image: url(' . esc_url( $image[0] ) . ');"';
					} ?>>
					</div>

					<div class="select-image">
						<?php _e( 'Select Image', 'siteorigin-panels' ) ?>
					</div>
					<input type="hidden" name="<?php echo esc_attr( $field_name ) ?>"
					       value="<?php echo intval( $current ) ?>"/>
				</div>
				<a href="#" class="remove-image"><?php _e( 'Remove', 'siteorigin-panels' ) ?></a>
				<?php
				break;

			case 'url' :
			case 'text' :
				?><input type="text" name="<?php echo esc_attr( $field_name ) ?>"
				         value="<?php echo esc_attr( $current ) ?>" class="widefat" /><?php
				break;

			case 'checkbox' :
				$current = (bool) $current;
				?>
				<label class="so-checkbox-label">
					<input type="checkbox" name="<?php echo esc_attr( $field_name ) ?>" <?php checked( $current ) ?> />
					<?php echo esc_html( isset( $field['label'] ) ? $field['label'] : __( 'Enabled', 'siteorigin-panels' ) ) ?>
				</label>
				<?php
				break;

			case 'select' :
				?>
				<select name="<?php echo esc_attr( $field_name ) ?>">
					<?php foreach ( $field['options'] as $k => $v ) : ?>
						<option
							value="<?php echo esc_attr( $k ) ?>" <?php selected( $current, $k ) ?>><?php echo esc_html( $v ) ?></option>
					<?php endforeach; ?>
				</select>
				<?php
				break;

			case 'textarea' :
			case 'code' :
				?><textarea type="text" name="<?php echo esc_attr( $field_name ) ?>"
				            class="widefat <?php if ( $field['type'] == 'code' ) {
					            echo 'so-field-code';
				            } ?>" rows="4"><?php echo esc_textarea( $current ) ?></textarea><?php
				break;
		}

		echo '</div>';

		if ( ! empty( $field['description'] ) ) {
			?><p class="so-description"><?php echo wp_kses_post( $field['description'] ) ?></p><?php
		}
	}

	/**
	 * Sanitize the style fields in panels_data
	 *
	 * @param $panels_data
	 *
	 * @return mixed
	 */
	function sanitize_all( $panels_data ) {
		if ( ! empty( $panels_data['widgets'] ) ) {
			// Sanitize the widgets
			for ( $i = 0; $i < count( $panels_data['widgets'] ); $i ++ ) {
				if ( empty( $panels_data['widgets'][ $i ]['panels_info']['style'] ) ) {
					continue;
				}
				$panels_data['widgets'][ $i ]['panels_info']['style'] = $this->sanitize_style_fields( 'widget', $panels_data['widgets'][ $i ]['panels_info']['style'] );
			}
		}

		if ( ! empty( $panels_data['grids'] ) ) {
			// The rows
			for ( $i = 0; $i < count( $panels_data['grids'] ); $i ++ ) {
				if ( empty( $panels_data['grids'][ $i ]['style'] ) ) {
					continue;
				}
				$panels_data['grids'][ $i ]['style'] = $this->sanitize_style_fields( 'row', $panels_data['grids'][ $i ]['style'] );
			}
		}

		if ( ! empty( $panels_data['grid_cells'] ) ) {
			// And finally, the cells
			for ( $i = 0; $i < count( $panels_data['grid_cells'] ); $i ++ ) {
				if ( empty( $panels_data['grid_cells'][ $i ]['style'] ) ) {
					continue;
				}
				$panels_data['grid_cells'][ $i ]['style'] = $this->sanitize_style_fields( 'cell', $panels_data['grid_cells'][ $i ]['style'] );
			}
		}

		return $panels_data;
	}

	/**
	 * Sanitize style fields.
	 *
	 * @param $section
	 * @param $styles
	 *
	 * @return Sanitized styles
	 */
	function sanitize_style_fields( $section, $styles ) {
		// Use the filter to get the fields for this section.
		if ( empty( $fields_cache[ $section ] ) ) {
			// This filter doesn't pass in the arguments $post_id and $args
			// Plugins looking to extend fields, should always add their fields if these are empty
            $fields_cache[ $section ] = array();
			$fields_cache[ $section ] = apply_filters( 'siteorigin_panels_' . $section . '_style_fields', $fields_cache[ $section ], false, false );
			$fields_cache[ $section ] = apply_filters( 'siteorigin_panels_general_style_fields', $fields_cache[ $section ], false, false );
		}
		$fields = $fields_cache[ $section ];

		$return = array();
		foreach ( $fields as $k => $field ) {
			// Skip this if no field type is set
			if ( empty( $field['type'] ) ) {
				continue;
			}

			// Handle the special case of a checkbox
			if ( $field['type'] == 'checkbox' ) {
				$return[ $k ] = ! empty( $styles[ $k ] ) ? true : '';
				continue;
			}

			// Ignore this if we don't even have a value for the style
			if ( ! isset( $styles[ $k ] ) || $styles[ $k ] == '' ) {
				continue;
			}

			switch ( $field['type'] ) {
				case 'color' :
					$color = $styles[ $k ];
					if ( preg_match( '|^#([A-Fa-f0-9]{3,8})$|', $color ) ) {
						$return[ $k ] = $color;
					} else {
						$return[ $k ] = '';
					}
					break;
				case 'image' :
					$return[ $k ] = ! empty( $styles[ $k ] ) ? intval( $styles[ $k ] ) : false;
					break;
				case 'url' :
					$return[ $k ] = esc_url_raw( $styles[ $k ] );
					break;
				case 'measurement' :
					$measurements = array_map( 'preg_quote', $this->measurements_list() );
					if ( ! empty( $field['multiple'] ) ) {
						if ( preg_match_all( '/(?:(-?[0-9\.,]+).*?(' . implode( '|', $measurements ) . ')+)/', $styles[ $k ], $match ) ) {
							$return[ $k ] = $styles[ $k ];
						} else {
							$return[ $k ] = '';
						}
					} else {
						if ( preg_match( '/([-?0-9\.,]+).*?(' . implode( '|', $measurements ) . ')/', $styles[ $k ], $match ) ) {
							$return[ $k ] = $match[1] . $match[2];
						} else {
							$return[ $k ] = '';
						}
					}
					break;
				case 'select' :
					if ( ! empty( $styles[ $k ] ) && in_array( $styles[ $k ], array_keys( $field['options'] ) ) ) {
						$return[ $k ] = $styles[ $k ];
					}
					break;
				default:
					// Just pass the value through.
					$return[ $k ] = $styles[ $k ];
					break;

			}
		}

		return $return;
	}

	/**
	 * Convert the single string attribute of the grid style into an array.
	 *
	 * @param $panels_data
	 *
	 * @return mixed
	 */
	function convert_data( $panels_data ) {
		if ( empty( $panels_data ) || empty( $panels_data['grids'] ) || ! is_array( $panels_data['grids'] ) ) {
			return $panels_data;
		}

		foreach( $panels_data['grids'] as & $grid ) {
			if ( ! is_array( $grid ) || empty( $grid ) || empty( $grid['style'] ) ) {
				continue;
			}

			if ( is_string( $grid['style'] ) ) {
				$grid['style'] = array(
					$grid['style']
				);
			}
		}

		return $panels_data;
	}

	/**
	 * Get list of supported mesurements
	 *
	 * @return array
	 */
	function measurements_list() {
		$measurements = array(
			'px',
			'%',
			'in',
			'cm',
			'mm',
			'em',
			'ex',
			'pt',
			'pc',
			'rem'
		);

		// Allow themes and plugins to trim or enhance the list.
		return apply_filters( 'siteorigin_panels_style_get_measurements_list', $measurements );
	}

	/**
	 * User sort function to sort by the priority key value.
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	static function sort_fields( $a, $b ) {
		return ( ( isset( $a['priority'] ) ? $a['priority'] : 10 ) > ( isset( $b['priority'] ) ? $b['priority'] : 10 ) ) ? 1 : - 1;
	}
}

// Initialise all the default styling
SiteOrigin_Panels_Styles::single();
