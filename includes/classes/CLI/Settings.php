<?php
/**
 * CLI Class to handle trips.
 *
 * @package WPTravelEngine\CLI
 * @since 6.0.0
 */

namespace WPTravelEngine\CLI;

use WP_CLI;
use WP_CLI_Command;
use WPTravelEngine\Core\Models\Settings\PluginSettings;
use WPTravelEngine\Utilities\ArrayUtility;

/**
 * Class Trip
 *
 * @since 6.0.0
 */
class Settings extends WP_CLI_Command {

	/**
	 * List all settings.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wp-travel-engine settings list
	 *
	 * @subcommand config
	 */
	public function list() {
		$settings = new PluginSettings();

		$_settings = ArrayUtility::flatten( $settings->get(), '' );

		$_settings = array_map(
			function ( $key, $value ) {
				return array(
					'key'   => $key,
					'value' => strpos( $value, "\n" ) !== false ? 'SKIPPED' : $value,
				);
			},
			array_keys( $_settings ),
			$_settings
		);

		WP_CLI\Utils\format_items( 'table', $_settings, array( 'key', 'value' ) );
	}

	/**
	 * Get a setting.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wp-travel-engine settings get <setting>
	 *
	 * @subcommand get
	 */
	public function get( $args ) {
		$setting  = $args[ 0 ];
		$settings = new PluginSettings();
		$value    = $settings->get( $setting );

		if( is_scalar( $value ) ) {
			$value = [ $setting => $value ];
		}

		$value = ArrayUtility::flatten( $value, '' );

		$_settings = array_map(
			function ( $key, $value ) {
				return array(
					'key'   => $key,
					'value' => strpos( $value, "\n" ) !== false ? 'SKIPPED' : $value,
					'type'  => gettype( $value ),
				);
			},
			array_keys( $value ),
			$value
		);

		if ( $value ) {
			WP_CLI\Utils\format_items( 'table', $_settings, array( 'key', 'value', 'type' ) );
		} else {
			WP_CLI::error( 'Setting not found.' );
		}
	}

	/**
	 * Update a setting.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wp-travel-engine settings update <setting> <value>
	 *
	 * @subcommand update
	 */
	public function update( $args ) {
		$setting              = $args[ 0 ];
		$value                = $args[ 1 ];
		$settings             = get_option( 'wp_travel_engine_settings', array() );
		$settings[ $setting ] = $value;
		update_option( 'wp_travel_engine_settings', $settings );
		WP_CLI::success( 'Setting updated.' );
	}

	/**
	 * Reset all settings.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wp-travel-engine settings reset
	 *
	 * @subcommand reset
	 */
	public function reset() {
		delete_option( 'wp_travel_engine_settings' );
		WP_CLI::success( 'Settings reset.' );
	}
}
