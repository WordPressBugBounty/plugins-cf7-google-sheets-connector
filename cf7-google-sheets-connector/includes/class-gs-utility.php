<?php

/*
 * Utilities class for Google Sheet Connector
 * @since       1.0
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Utilities class - singleton class
 *
 * @since 1.0
 */
class Gs_Connector_Free_Utility {

	private function __construct() {
		// Do Nothing
	}
	/**
	 * Get the singleton instance of the Gs_Connector_Free_Utility class
	 *
	 * @return singleton instance of Gs_Connector_Free_Utility
	 */
	public static function instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Gs_Connector_Free_Utility();
		}
		return $instance;
	}
	/**
	 * Prints message (string or array) in the debug.log file
	 *
	 * @param mixed $message
	 */
	public function logger( $message ) {
		if ( WP_DEBUG === true ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				gscf7_error_logs::log_from_debug( $message->getMessage() );
			} else {
				gscf7_error_logs::log_from_debug( $message );
			}
		}
	}
	/**
	 * Display error or success message in the admin section
	 *
	 * @param array $data containing type and message
	 * @return string with html containing the error message
	 *
	 * @since 1.0 initial version
	 */
	public function admin_notice( $data = array() ) {
		$message      = isset( $data['message'] ) ? $data['message'] : '';
		$message_type = isset( $data['type'] ) ? $data['type'] : '';

		switch ( $message_type ) {
			case 'error':
				$admin_notice = '<div id="message" class="error notice is-dismissible">';
				break;

			case 'update':
				$admin_notice = '<div id="message" class="updated notice is-dismissible">';
				break;

			case 'update-nag':
				$admin_notice = '<div id="message" class="update-nag">';
				break;

			case 'review':
				$admin_notice = '<div id="message" class="updated notice gs-adds is-dismissible">';
				break;

			case 'auth-expired-notice':
				$admin_notice = '<div id="message" class="error notice gs-auth-expired-adds is-dismissible">';
				break;

			case 'upgrade':
				$admin_notice = '<div id="message" class="error notice gs-upgrade is-dismissible">';
				break;

			default:
				$message      = __( 'There’s something wrong with your code…', 'cf7-google-sheets-connector' );
				$admin_notice = '<div id="message" class="error">';
				break;
		}
		$admin_notice .= '<p>' . wp_kses_post( $message ) . '</p>';
		$admin_notice .= '</div>';
		return $admin_notice;
	}
	/**
	 * Utility function to get the current user's role
	 *
	 * @since 1.0
	 */
	public function get_current_user_role() {
		global $wp_roles;
		foreach ( $wp_roles->role_names as $role => $name ) :
			if ( current_user_can( $role ) ) {
				return $role;
			}
		endforeach;
	}
	/**
	 * Fetch and save Auto Integration API credentials.
	 *
	 * A site connected under the managed ("Existing / Auto Setup") method holds
	 * its own cached copy of the relay's OAuth client ID/secret in
	 * `cf7gsc_free_api_creds`. Google will only redeem a refresh token with the
	 * OAuth client that issued it, so overwriting that option on a site whose
	 * stored token still works breaks that site's sync at the next refresh --
	 * silently, with no user action involved.
	 *
	 * A freshly fetched set is therefore only ever written to the *live* option
	 * when doing so cannot invalidate a working connection (see
	 * `has_live_google_token()` below). Otherwise it is staged in
	 * `cf7gsc_free_api_creds_pending` and applied later, at a point where the
	 * refresh token is being discarded or replaced anyway (see
	 * `promote_pending_api_credentials()`).
	 *
	 * @since 5.0.20
	 * @since 5.2.4 Added validation, staging, locking and back-off.
	 *
	 * @param string $context Short label identifying the caller, used only in
	 *                        logs/meta -- never in anything user-facing.
	 * @return bool True when the fetched credentials were valid and were
	 *              applied (live or staged); false on any failure. A blocked
	 *              (locked or back-off) call also returns true/false without
	 *              performing a request -- see below.
	 */
	public function save_api_credentials( $context = 'default' ) {
		if ( $this->has_credential_lock() ) {
			// A concurrent request is already fetching; nothing to do here.
			return true;
		}

		if ( $this->has_credential_backoff() ) {
			return false;
		}

		$this->acquire_credential_lock();

		// Create a nonce
		$nonce = wp_create_nonce( 'cf7gsc_free_api_creds' );
		// Prepare parameters for the API call
		$params = array(
			'action' => 'get_data',
			'nonce'  => $nonce,
			'plugin' => 'CF7GSC',
			'method' => 'get',
		);
		// Add nonce and any other security parameters to the API request
		$api_url = add_query_arg( $params, GS_CONNECTOR_API_URL );
		// Make the API call using wp_remote_get
		$response = wp_remote_get(
			$api_url,
			array(
				'timeout'     => 10,
				'redirection' => 5,
				'sslverify'   => true,
				'headers'     => array(
					'Accept' => 'application/json',
				),
			)
		);
		// Check for errors
		if ( is_wp_error( $response ) ) {
			$this->record_credential_failure( $context, $response->get_error_code() );
			$this->release_credential_lock();
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			$this->record_credential_failure( $context, 'http_' . $code );
			$this->release_credential_lock();
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			$this->record_credential_failure( $context, 'empty_body' );
			$this->release_credential_lock();
			return false;
		}

		$decoded_response = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded_response ) ) {
			$this->record_credential_failure( $context, 'invalid_json' );
			$this->release_credential_lock();
			return false;
		}

		$raw       = isset( $decoded_response['api_creds'] ) ? $decoded_response['api_creds'] : null;
		$validated = $this->validate_api_credentials( $raw );

		if ( false === $validated ) {
			$this->record_credential_failure( $context, 'invalid_credentials' );
			$this->release_credential_lock();
			return false;
		}

		$new_fingerprint     = $this->creds_fingerprint( $validated );
		$current              = $this->get_api_credentials();
		$current_fingerprint  = $current ? $this->creds_fingerprint( $current ) : '';

		if ( $current_fingerprint === $new_fingerprint ) {
			/*
			 * Nothing changed upstream relative to the live set -- avoid an
			 * unnecessary write. Also drop any staged set: if the relay is
			 * back to reporting what is already live (e.g. a rotation was
			 * rolled back), a previously staged set is now stale and must not
			 * be applied later by promote_pending_api_credentials().
			 */
			$this->clear_pending_api_credentials();
			$this->clear_credential_backoff();
			$this->release_credential_lock();
			return true;
		}

		$has_live_token = $this->has_live_google_token();
		$token_fp        = get_option( 'gscf7_token_client_fp', '' );

		if ( ! $current || ! $has_live_token || ( $token_fp && $token_fp === $new_fingerprint ) ) {
			// Nothing valid stored yet, no token to invalidate, or the live
			// token already belongs to this exact credential set -- safe to
			// apply immediately.
			$this->write_live_api_credentials( $validated );
		} else {
			// A live token exists and either belongs to a different (or
			// unknown) client. Staging is always recoverable; breaking a
			// working refresh token is not.
			$this->write_pending_api_credentials( $validated, $context );
		}

		$this->clear_credential_backoff();
		$this->release_credential_lock();
		return true;
	}

	/**
	 * Validate a raw credential payload.
	 *
	 * @since 5.2.4
	 *
	 * @param mixed $raw Decoded `api_creds` payload from the relay, or a
	 *                    previously stored option value.
	 * @return array|false Array with exactly `client_id_web` and
	 *                      `client_secret_web` when valid, false otherwise.
	 */
	public function validate_api_credentials( $raw ) {
		if ( ! is_array( $raw ) ) {
			return false;
		}

		if ( empty( $raw['client_id_web'] ) || empty( $raw['client_secret_web'] ) ) {
			return false;
		}

		if ( ! is_string( $raw['client_id_web'] ) || ! is_string( $raw['client_secret_web'] ) ) {
			return false;
		}

		$client_id     = trim( $raw['client_id_web'] );
		$client_secret = trim( $raw['client_secret_web'] );

		if ( '' === $client_id || '' === $client_secret ) {
			return false;
		}

		if ( ! preg_match( '/\.apps\.googleusercontent\.com$/', $client_id ) ) {
			return false;
		}

		return array(
			'client_id_web'     => $client_id,
			'client_secret_web' => $client_secret,
		);
	}

	/**
	 * Truncated fingerprint of a credential set's client ID only.
	 *
	 * Never derived from the client secret -- safe to log or show in support.
	 *
	 * @since 5.2.4
	 *
	 * @param array|false $creds Credential array (or output of
	 *                           validate_api_credentials()).
	 * @return string Empty string when there is no client ID to fingerprint.
	 */
	public function creds_fingerprint( $creds ) {
		if ( empty( $creds['client_id_web'] ) ) {
			return '';
		}

		return substr( hash( 'sha256', $creds['client_id_web'] ), 0, 16 );
	}

	/**
	 * Get the currently live, validated managed API credentials.
	 *
	 * @since 5.2.4
	 *
	 * @return array|false
	 */
	public function get_api_credentials() {
		$raw = is_multisite()
			? get_site_option( 'cf7gsc_free_api_creds' )
			: get_option( 'cf7gsc_free_api_creds' );

		return $this->validate_api_credentials( $raw );
	}

	/**
	 * Whether a staged (not-yet-applied) credential set is waiting.
	 *
	 * @since 5.2.4
	 *
	 * @return bool
	 */
	public function has_pending_api_credentials() {
		$raw = is_multisite()
			? get_site_option( 'cf7gsc_free_api_creds_pending' )
			: get_option( 'cf7gsc_free_api_creds_pending' );

		return false !== $this->validate_api_credentials( $raw );
	}

	/**
	 * Whether the stored managed OAuth token is a live token.
	 *
	 * Only true when the value parses as JSON carrying an access or refresh
	 * token. An OAuth error payload or a bare, unexchanged auth code is not a
	 * live token, so every "is this site connected?" check must call this
	 * instead of testing the option for emptiness.
	 *
	 * @since 5.2.4
	 *
	 * @return bool
	 */
	public function has_live_google_token() {
		$token_json = get_option( 'gs_token' );

		if ( empty( $token_json ) ) {
			return false;
		}

		$token = json_decode( $token_json, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $token ) ) {
			return false;
		}

		return ! empty( $token['access_token'] ) || ! empty( $token['refresh_token'] );
	}

	/**
	 * Record which credential set a just-issued/refreshed token belongs to.
	 *
	 * Called after a successful token exchange or refresh so a later staging
	 * decision can tell whether the live token's issuing client is known and
	 * matches a freshly fetched credential set.
	 *
	 * @since 5.2.4
	 *
	 * @param mixed $creds Credentials used for the exchange/refresh that just
	 *                      succeeded.
	 * @return void
	 */
	public function record_token_client_fingerprint( $creds ) {
		$validated = $this->validate_api_credentials( $creds );

		if ( false === $validated ) {
			return;
		}

		update_option( 'gscf7_token_client_fp', $this->creds_fingerprint( $validated ), false );
	}

	/**
	 * Apply a staged credential set as the live set.
	 *
	 * Purely local -- no HTTP request. Safe to call whenever the refresh
	 * token is about to be replaced anyway (a fresh sign-in, or right after
	 * deauthorising).
	 *
	 * @since 5.2.4
	 *
	 * @return bool True when a valid staged set was applied.
	 */
	public function promote_pending_api_credentials() {
		$raw = is_multisite()
			? get_site_option( 'cf7gsc_free_api_creds_pending' )
			: get_option( 'cf7gsc_free_api_creds_pending' );

		$validated = $this->validate_api_credentials( $raw );

		if ( false === $validated ) {
			return false;
		}

		$this->write_live_api_credentials( $validated );
		return true;
	}

	/**
	 * Freshness-window wrapper around save_api_credentials().
	 *
	 * @since 5.2.4
	 *
	 * @param string $context Caller label, used only in logs/meta.
	 * @param int    $max_age Seconds since the last check before this call is
	 *                        allowed to perform a request.
	 * @param bool   $force   Bypass the freshness window and back-off.
	 * @return bool
	 */
	public function maybe_refresh_api_credentials( $context = 'default', $max_age = 300, $force = false ) {
		if ( get_option( 'gs_cf7_auth_method', 'cf7_existing' ) !== 'cf7_existing' ) {
			return false;
		}

		if ( ! $force ) {
			if ( $this->has_credential_backoff() ) {
				return false;
			}

			$meta         = $this->get_credential_meta();
			$last_checked = isset( $meta['last_checked'] ) ? (int) $meta['last_checked'] : 0;

			if ( $last_checked && ( time() - $last_checked ) < (int) $max_age ) {
				return true;
			}
		}

		$result = $this->save_api_credentials( $context );

		$meta                 = $this->get_credential_meta();
		$meta['last_checked'] = time();
		$this->write_credential_meta( $meta );

		return $result;
	}

	/**
	 * Read the no-secrets credential bookkeeping (fingerprints, timestamps,
	 * last error).
	 *
	 * @since 5.2.4
	 *
	 * @return array
	 */
	public function get_credential_meta() {
		$meta = is_multisite()
			? get_site_option( 'cf7gsc_free_api_creds_meta' )
			: get_option( 'cf7gsc_free_api_creds_meta' );

		return is_array( $meta ) ? $meta : array();
	}

	/**
	 * @since 5.2.4
	 * @param array $meta
	 * @return void
	 */
	private function write_credential_meta( $meta ) {
		if ( is_multisite() ) {
			update_site_option( 'cf7gsc_free_api_creds_meta', $meta );
		} else {
			update_option( 'cf7gsc_free_api_creds_meta', $meta, false );
		}
	}

	/**
	 * @since 5.2.4
	 * @param array $validated Output of validate_api_credentials().
	 * @return void
	 */
	private function write_live_api_credentials( $validated ) {
		if ( is_multisite() ) {
			update_site_option( 'cf7gsc_free_api_creds', $validated );
		} else {
			// Not autoloaded: holds the OAuth client secret and is only needed
			// during authentication and sheet writes.
			update_option( 'cf7gsc_free_api_creds', $validated, false );
		}

		// The set just applied live is no longer "pending" anything.
		$this->clear_pending_api_credentials();

		$meta                = $this->get_credential_meta();
		$meta['fingerprint'] = $this->creds_fingerprint( $validated );
		$meta['updated_at']  = time();
		unset( $meta['pending_fingerprint'], $meta['pending_since'], $meta['pending_context'] );
		$this->write_credential_meta( $meta );
	}

	/**
	 * @since 5.2.4
	 * @param array  $validated Output of validate_api_credentials().
	 * @param string $context   Caller label, used only in meta.
	 * @return void
	 */
	private function write_pending_api_credentials( $validated, $context ) {
		if ( is_multisite() ) {
			update_site_option( 'cf7gsc_free_api_creds_pending', $validated );
		} else {
			update_option( 'cf7gsc_free_api_creds_pending', $validated, false );
		}

		$meta                         = $this->get_credential_meta();
		$meta['pending_fingerprint']  = $this->creds_fingerprint( $validated );
		$meta['pending_since']        = time();
		$meta['pending_context']      = (string) $context;
		$this->write_credential_meta( $meta );
	}

	/**
	 * @since 5.2.4
	 * @return void
	 */
	private function clear_pending_api_credentials() {
		if ( is_multisite() ) {
			delete_site_option( 'cf7gsc_free_api_creds_pending' );
		} else {
			delete_option( 'cf7gsc_free_api_creds_pending' );
		}
	}

	/**
	 * @since 5.2.4
	 * @return bool
	 */
	private function has_credential_lock() {
		return (bool) get_transient( 'cf7gsc_free_creds_lock' );
	}

	/**
	 * @since 5.2.4
	 * @return void
	 */
	private function acquire_credential_lock() {
		set_transient( 'cf7gsc_free_creds_lock', 1, 30 );
	}

	/**
	 * @since 5.2.4
	 * @return void
	 */
	private function release_credential_lock() {
		delete_transient( 'cf7gsc_free_creds_lock' );
	}

	/**
	 * @since 5.2.4
	 * @return bool
	 */
	private function has_credential_backoff() {
		return (bool) get_transient( 'cf7gsc_free_creds_backoff' );
	}

	/**
	 * @since 5.2.4
	 * @return void
	 */
	private function clear_credential_backoff() {
		delete_transient( 'cf7gsc_free_creds_backoff' );
	}

	/**
	 * @since 5.2.4
	 * @param string $context    Caller label.
	 * @param string $error_code Short machine-readable failure reason -- never
	 *                            a credential value.
	 * @return void
	 */
	private function record_credential_failure( $context, $error_code ) {
		set_transient( 'cf7gsc_free_creds_backoff', 1, 15 * MINUTE_IN_SECONDS );

		$meta               = $this->get_credential_meta();
		$meta['last_error'] = array(
			'context' => (string) $context,
			'code'    => (string) $error_code,
			'time'    => time(),
		);
		$this->write_credential_meta( $meta );

		self::gs_debug_log( 'Managed API credential fetch failed [' . $context . ']: ' . $error_code );
	}
	/**
	 * Utility function to get the current user's role
	 *
	 * @since 1.0
	 */
	public static function gs_debug_log( $error ) {
		// ===============================
		// DATABASE LOG (ADD ONLY THIS)
		// ===============================
		if ( class_exists( 'gscf7_error_logs' ) ) {
			gscf7_error_logs::log_from_debug( $error );
		}
	}
}
