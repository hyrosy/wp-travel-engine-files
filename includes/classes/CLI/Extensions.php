<?php
/**
 * WP Travel Engine CLI Extensions Class
 *
 * @package WPTravelEngine
 */

namespace WPTravelEngine\CLI;

use WP_CLI;

class Extensions {

	/**
	 * Install an extension.
	 *
	 * ## OPTIONS
	 *
	 * <extension_name>: Extension name to be installed.
	 *
	 *  [--status=<value>]
	 *  : Description for flag1.
	 * ## EXAMPLES
	 *
	 *     wp wp-travel-engine extensions install <extension>
	 *
	 *
	 * @subcommand install
	 */
	public function install( $args ) {
		// Clone a repo into plugin directory
		$extension_name = $args[ 0 ];
		$extension_repo = "git@github.com:Codewing-Solutions/{$extension_name}.git";

		$plugin_dir = WP_CONTENT_DIR . '/plugins/';

		$extension_dir = $plugin_dir . $extension_name;

		if ( file_exists( $extension_dir ) ) {
			WP_CLI::error( 'Extension already exists.' );
		}

		$command = "git clone {$extension_repo} {$extension_dir}";

		shell_exec( $command );

		WP_CLI::success( 'Extension installed successfully.' );

	}

	/**
	 * List all extensions.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wp-travel-engine extensions list
	 *
	 * @subcommand list
	 */
	public function list( $args, $assoc_args ) {
		$plugin_dir = WP_CONTENT_DIR . '/plugins/';

		$plugins = get_plugins();

		$_extensions = array();
		// columns: name, status, update, version, update_version

		$status_flag = $assoc_args[ 'status' ] ?? '';

		foreach ( $plugins as $key => $extension ) {
			if ( ! preg_match( '/(wp-travel-engine|wptravelengine)/', $key ) ) {
				continue;
			}

			if ( ! empty( $status_flag ) ) {
				if ( $status_flag !== 'active' && $status_flag !== 'inactive' ) {
					WP_CLI::error( 'Invalid status flag. Use either active or inactive.' );
				}

				if ( $status_flag === 'active' && ! is_plugin_active( $key ) ) {
					continue;
				}

				if ( $status_flag === 'inactive' && is_plugin_active( $key ) ) {
					continue;
				}
			}

			$extension_data = get_plugin_data( $plugin_dir . $key );

			$_extensions[] = array(
				'name'    => $extension_data[ 'Name' ],
				'version' => $extension_data[ 'Version' ],
				'status'  => is_plugin_active( $key ) ? 'active' : 'inactive',
			);

		}

		WP_CLI\Utils\format_items( 'table', $_extensions, array( 'name', 'version', 'status' ) );
	}

}
