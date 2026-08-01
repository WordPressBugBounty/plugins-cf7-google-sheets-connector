<?php

/**
 * Extension class for GS cf7 Forms Google Sheet Connector extensions operations
 *
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GS_Extension Class
 *
 * @since 1.0.0
 */

class GSCF7_Extensions_free {


	/**
	 *  Set things up.
	 *
	 *  @since 1.0.0
	 */
	public function __construct() {

		// Install contact forms plugin
		add_action( 'wp_ajax_gscf7_install_plugin', array( $this, 'gscf7_install_plugin' ) );

		// Activate contact forms plugin
		add_action( 'wp_ajax_gscf7_activate_plugin', array( $this, 'gscf7_activate_plugin' ) );

		// Deactivate contact forms plugin
		add_action( 'wp_ajax_gscf7_deactivate_plugin', array( $this, 'gscf7_deactivate_plugin' ) );
	}

	/**
	 * Deactivate contact forms plugin
	 *
	 * @since 1.0.0
	 */
	public function gscf7_deactivate_plugin() {
		// Nonce check
		check_ajax_referer( 'gs-ajax-nonce', 'security' );

		if ( ! current_user_can( 'activate_plugins' ) ) {

			wp_send_json_error( 'You do not have permission to deactivate plugins' );
		}
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		if ( ! isset( $_POST['plugin_slug'] ) ) {

			wp_send_json_error( 'Plugin slug is missing' );
		}
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		$plugin_slug = sanitize_text_field( wp_unslash( $_POST['plugin_slug'] ) );

		if ( empty( $plugin_slug ) ) {

			wp_send_json_error( 'Invalid plugin' );
		}

		// Ensure plugin exists before attempting to deactivate
		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) ) {

			wp_send_json_error( 'Plugin not found' );
		}

		deactivate_plugins( $plugin_slug );

		if ( is_plugin_active( $plugin_slug ) ) {

			wp_send_json_error( 'Failed to deactivate plugin' );
		}
		wp_send_json_success( 'Plugin deactivated successfully' );
	}

	/**
	 * Determine whether a package URL points at a trusted distribution host.
	 *
	 * Every add-on offered on the Extensions screen is hosted on the official
	 * WordPress.org plugin CDN, so the allow-list is deliberately narrow.
	 *
	 * @since 5.2.1
	 *
	 * @param string $url Package URL supplied by the request.
	 * @return bool True when the URL may be installed from.
	 */
	private static function gscf7_is_trusted_package_url( $url ) {
		$allowed_hosts = array(
			'downloads.wordpress.org',
		);

		if ( empty( $url ) ) {
			return false;
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		$host   = wp_parse_url( $url, PHP_URL_HOST );

		if ( 'https' !== $scheme || empty( $host ) ) {
			return false;
		}

		return in_array( strtolower( $host ), $allowed_hosts, true );
	}

	/**
	 * Installs or upgrades a plugin via AJAX using provided slug and download URL.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function gscf7_install_plugin() {
		// Nonce check
		check_ajax_referer( 'gs-ajax-nonce', 'security' );

		// Permission check
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to install plugin', 'cf7-google-sheets-connector' ),
				)
			);
		}

		if ( empty( $_POST['plugin_slug'] ) || empty( $_POST['download_url'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Missing required parameters', 'cf7-google-sheets-connector' ),
				)
			);
		}

		$plugin_slug  = sanitize_text_field( wp_unslash( $_POST['plugin_slug'] ) );
		$download_url = esc_url_raw( wp_unslash( $_POST['download_url'] ) );

		/*
		 * Only allow packages served by the official WordPress.org plugin CDN.
		 *
		 * Without this check any URL supplied in the request would be downloaded
		 * and unpacked into wp-content/plugins, which turns this endpoint into a
		 * remote code execution primitive for any actor able to issue a request
		 * in an administrator's context.
		 */
		if ( ! self::gscf7_is_trusted_package_url( $download_url ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'The requested package is not from a trusted source.', 'cf7-google-sheets-connector' ),
				)
			);
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/update.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );

		$result = $upgrader->install( $download_url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Installation failed: ', 'cf7-google-sheets-connector' ) . $result->get_error_message(),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Plugin installed successfully', 'cf7-google-sheets-connector' ),
			)
		);
	}

	/**
	 * Activates a plugin via AJAX using the provided plugin slug.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function gscf7_activate_plugin() {

		// Verify nonce
		if ( ! check_ajax_referer( 'gs-ajax-nonce', 'security', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid security token', 'cf7-google-sheets-connector' ),
				)
			);
		}

		// Permission check
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to activate plugin', 'cf7-google-sheets-connector' ),
				)
			);
		}

		// 🔎 Check plugin slug
		if ( empty( $_POST['plugin_slug'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Plugin slug is missing', 'cf7-google-sheets-connector' ),
				)
			);
		}

		$plugin_slug = sanitize_text_field( wp_unslash( $_POST['plugin_slug'] ) );

		// Load required file
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Check if already active
		if ( is_plugin_active( $plugin_slug ) ) {
			wp_send_json_success(
				array(
					'message' => __( 'Plugin is already activated', 'cf7-google-sheets-connector' ),
				)
			);
		}

		// Activate plugin
		$result = activate_plugin( $plugin_slug );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Plugin activated successfully', 'cf7-google-sheets-connector' ),
			)
		);
	}
}
$GSCF7_Extensions = new GSCF7_Extensions_free();
