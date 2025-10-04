<?php
/**
 * Manages server-sourced admin notifications and their display functionality.
 *
 * @package WPTravelEngine
 * @since 6.5.7
 */

namespace WPTravelEngine;

class AdminNotice {

    /**
     * Notice content.
     *
     * @var array|false
     */
    private $notice_content = false;

    /**
     * Constructor.
     */
    public function __construct() {

		if ( ! $this->get_notice_content() ) {
			return;
		}

        add_action( 'admin_notices', array( $this, 'display_notice' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Display notice content.
     */
    public function display_notice(): void {
		?>
		<div class="notice wptravelengine-admin-notice is-dismissible">
			<div class="wptravelengine-notice-content"><?php echo wp_kses_post( $this->notice_content['content'] ?? '' ); ?></div>
		</div>
		<?php
    }

    /**
     * Enqueue scripts.
     */
    public function enqueue_scripts(): void {

		wp_enqueue_script( 
			'wptravelengine-admin-global', 
			plugins_url( 'dist/admin/admin-global.js', WP_TRAVEL_ENGINE_FILE_PATH ), 
			array(), 
			filemtime( plugin_dir_path( WP_TRAVEL_ENGINE_FILE_PATH ) . 'dist/admin/admin-global.js' ), 
			true 
		);

		wp_localize_script( 'wptravelengine-admin-global', 'WPTravelEngineAdminGlobal', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'action' => 'wptravelengine_notice_dismiss',
			'nonce' => wp_create_nonce( '_wptravelengine_notice_dismiss' ),
			'last_updated' => $this->notice_content['last_updated'] ?? 0,
		) );
    }

	/**
	 * Get notice content.
	 *
	 * @return bool|array
	 */

	public function get_notice_content() {
		$notice = get_transient( 'wptravelengine_last_notice' );

		if ( ! $notice ) {
			$response = wp_remote_get( 'https://stats.wptravelengine.com/wp-json/wptravelengine-server/v1/notice' );
	
			if ( ! is_wp_error( $response ) ) {
				$notice = json_decode( wp_remote_retrieve_body( $response ), true );	
				if ( ! empty( $notice['content'] ?? '' ) && ! empty( $notice['last_updated'] ?? 0 ) ) {
					set_transient( 'wptravelengine_last_notice', $notice, DAY_IN_SECONDS );
				}
			}
		}

		if ( $notice && ! empty( $notice['content'] ?? '' ) && ! empty( $notice['last_updated'] ?? 0 ) ) {
			$dismissed_at = intval( get_option( 'wptravelengine_notice_dismissed_at', 0 ) );
			$this->notice_content = ( $dismissed_at < $notice['last_updated'] ) ? $notice : false;
		}

		return $this->notice_content;
	}
}