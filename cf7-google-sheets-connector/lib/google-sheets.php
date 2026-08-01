<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class CF7GSC_googlesheet {


	private $spreadsheet;
	private $worksheet;

	/**
	GET GOOGLE CREDS

	@since 1.0.0
	 */
	private static function creds() {
		return is_multisite()
		? get_site_option( 'cf7gsc_free_api_creds' )
		: get_option( 'cf7gsc_free_api_creds' );
	}

	/**
	 * Authenticate Google Client using authorization code.
	 *
	 * Fetches API credentials, initializes the Google Client,
	 * exchanges the authorization code for an access token,
	 * and stores the token data.
	 *
	 * @since 1.0
	 *
	 * @param string $code Google OAuth authorization code.
	 * @return bool True when a valid access token was stored, false otherwise.
	 */
	public static function preauth( $access_code ) {
		try {
			$creds = self::creds();
			if ( ! $creds ) {
				return;
			}

			$response = wp_remote_post(
				'https://oauth2.googleapis.com/token',
				array(
					'body' => array(
						'code'          => $access_code,
						'client_id'     => $creds['client_id_web'],
						'client_secret' => $creds['client_secret_web'],
						'redirect_uri'  => 'https://oauth.gsheetconnector.com/auth-api.php',
						'grant_type'    => 'authorization_code',
					),
				)
			);
			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $body ) ) {
				$body = array();
			}

			if ( empty( $body['access_token'] ) ) {
				self::updateToken( $body );
				return false;
			}

			self::updateToken( $body );
			return true;
		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log( '[Auth Exception]. ' . $e->getMessage() );
			throw new LogicException( 'Auth error: ' . esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Store and update Google OAuth token for manual authentication.
	 *
	 * This function saves the token data returned by Google OAuth
	 * and validates the permission scopes before storing them
	 * in WordPress options.
	 *
	 * @since 3.0
	 *
	 * @param array $tokenData Google OAuth token data.
	 *
	 * @return void
	 */
	public static function updateToken( $tokenData ) {
		// Invalid token response
		if ( empty( $tokenData['access_token'] ) ) {

			update_option( 'cf7gf_email_account', '', false );

			update_option(
				'gs_token',
				wp_json_encode( $tokenData ),
				false
			);

			if ( class_exists( 'gscf7_error_logs' ) ) {

				gscf7_error_logs::log_to_db(
					'Google_Access_Token_Invalid_Existing',
					403,
					'Google access token is invalid or expired (Existing Method)',
					array(
						'error_type'            => 'invalid_token',
						'authentication_method' => 'Existing',
						'message'               => 'Authentication failed. The stored Google access token is invalid, expired, or refresh token is no longer valid. Please re-authenticate your Google account.',
					)
				);
			}

			return;
		}

		if ( isset( $tokenData['expires_in'] ) ) {
			$tokenData['expire'] = time() + intval( $tokenData['expires_in'] );
		}

		try {
			if ( isset( $tokenData['scope'] ) ) {
				$permission = explode( ' ', $tokenData['scope'] );
				if ( ( in_array( 'https://www.googleapis.com/auth/drive.metadata.readonly', $permission ) || in_array( 'https://www.googleapis.com/auth/drive.file', $permission ) ) && ( in_array( 'https://www.googleapis.com/auth/spreadsheets', $permission ) ) ) {
					update_option( 'gs_verify', 'valid' );
				} else {
					update_option( 'gs_verify', 'invalid-auth' );
					// Log permission error to error logs
					if ( class_exists( 'gscf7_error_logs' ) ) {
						gscf7_error_logs::log_to_db(
							'Google_Auth_Permission_Error',
							403,
							'Google Drive and Google Sheets permissions not granted',
							array(
								'error_type'            => 'Missing Permissions',
								'message'               => 'User did not grant Google Drive and/or Google Sheets permissions during OAuth authentication',
								'granted_scopes'        => $tokenData['scope'] ?? '',
								'required_drive_scope'  => 'https://www.googleapis.com/auth/drive.file OR https://www.googleapis.com/auth/drive.metadata.readonly',
								'required_sheets_scope' => 'https://www.googleapis.com/auth/spreadsheets',
							)
						);
					}
				}
			}
			$tokenJson = wp_json_encode( $tokenData );
			update_option( 'gs_token', $tokenJson, false );
		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log( $e->getMessage() );
			return;
		}
	}

	/**
	 * AUTHENTICATE GOOGLE CLIENT FOR API ACCESS
	 *
	 * This method handles authentication for Google API access by retrieving
	 * a valid access token using the configured authentication method.
	 *
	 * Supported authentication modes:
	 * 1. Manual OAuth authentication (client ID/secret based token)
	 * 2. Service Account authentication (JSON credentials based token)
	 * 3. Default OAuth authentication (plugin-managed token system)
	 *
	 * The method:
	 * - Retrieves a valid access token from the active authentication flow
	 * - Ensures token is refreshed automatically if required (handled internally)
	 * - Returns the valid token for API usage
	 *
	 * If authentication fails, the error is logged and false is returned.
	 *
	 * @since 1.0.0
	 * @return string|false Valid access token or false on failure
	 */
	public function auth() {
		try {
			$token = $this->get_active_token();

			if ( ! $token ) {
				return false;
			}

			return $token;
		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log(
				__METHOD__ . " Error in Auth: \n " . $e->getMessage()
			);
			return false;
		}
	}

	/**
	 * Get the active Google API access token based on selected auth mode
	 *
	 * Modes:
	 * cf7_manual = Manual OAuth Token
	 * cf7_service = Service Account
	 * Default = Existing/Auto token method
	 *
	 * @since 1.0.0
	 */
	public function get_active_token() {
		try {
			$auth_method = get_option( 'gs_cf7_auth_method', 'cf7_existing' );

			// Service Account Auth
			if ( $auth_method === 'cf7_service' ) {
				return $this->get_service_account_token();
			}

			return $this->token();
		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log(
				__METHOD__ . " Error in Getting token: \n " . $e->getMessage()
			);
			return false;
		}
	}

	/**
	 * GET ACCESS TOKEN
	 *
	 * @since 1.0.0
	 */
	private function token() {
		try {
			$tokenJson = get_option( 'gs_token' );
			$tokenData = json_decode( $tokenJson, true );

			if ( empty( $tokenData ) ) {
				return false;
			}

			if ( ! isset( $tokenData['expire'] ) ) {
					return false;
			}

			if ( time() > intval( $tokenData['expire'] ) ) {

							$newToken = $this->refresh( $tokenData );

				if ( ! empty( $newToken['access_token'] ) ) {

					self::updateToken( $newToken );

					return $newToken['access_token'];
				}

				return false;
			}

			return $tokenData['access_token'];

		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log(
				'Error in getting auto token ' . $e->getMessage()
			);

		}
	}

	/**
	 * REFRESH ACCESS TOKEN USING GOOGLE OAUTH
	 *
	 * This function refreshes the expired OAuth access token using the stored refresh token.
	 * It calls Google's OAuth token endpoint and updates the WordPress option with the new token data.
	 *
	 * Steps:
	 * 1. Validate refresh token exists
	 * 2. Get client credentials
	 * 3. Send request to Google OAuth API
	 * 4. Validate response
	 * 5. Store new access token + expiry time in database
	 * 6. Return new access token
	 *
	 * @since 1.0.0
	 */
	private function refresh( $token ) {
		try {
			if ( empty( $token['refresh_token'] ) ) {
				return false;
			}
			$creds = self::creds();

			$response = wp_remote_post(
				'https://oauth2.googleapis.com/token',
				array(
					'body' => array(
						'client_id'     => $creds['client_id_web'],
						'client_secret' => $creds['client_secret_web'],
						'refresh_token' => $token['refresh_token'] ?? '',
						'grant_type'    => 'refresh_token',
					),
				)
			);
			if ( is_wp_error( $response ) ) {
						return false;
			}
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $body['access_token'] ) ) {
					$body['refresh_token'] = $token['refresh_token'];
			}

			return $body;
		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log( 'Refresh Auto Token fail! - ' . $e->getMessage() );
		}
	}

	/**
	 * GENERATE ACCESS TOKEN USING SERVICE ACCOUNT
	 *
	 * This method retrieves and validates Google Service Account credentials
	 * stored in the WordPress database, then generates an access token for API usage.
	 *
	 * Workflow:
	 * 1. Fetch service account JSON from database option
	 * 2. Decode and validate JSON structure
	 * 3. Ensure required fields (like client_email) exist
	 * 4. Generate JWT-based access token using credentials
	 *
	 * @since 1.0.0
	 */
	public function get_service_account_token() {
		try {
			$json = get_option( 'gs_cf7_service_account_json' );

			if ( empty( $json ) ) {
				return false;
			}

			$creds = json_decode( $json, true );

			if ( json_last_error() !== JSON_ERROR_NONE || empty( $creds['client_email'] ) ) {
				return false;
			}

			return $this->generate_gscf7_service_token( $creds );
		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log(
				__METHOD__ . ' Error while getting service token: ' . $e->getMessage()
			);
			return null;
		}
	}

	/**
	 * GENERATE JWT AND EXCHANGE IT FOR GOOGLE OAUTH ACCESS TOKEN
	 *
	 * This method creates a JWT (JSON Web Token) using Service Account credentials
	 * and exchanges it with Google OAuth server to obtain an access token.
	 *
	 * Workflow:
	 * 1. Set current timestamp
	 * 2. Create JWT header (algorithm + type)
	 * 3. Create JWT payload with:
	 *    - issuer (client email)
	 *    - required API scopes
	 *    - audience (Google OAuth token URL)
	 *    - expiration time
	 *    - issued-at time
	 * 4. Base64 URL encode header and payload
	 * 5. Sign JWT using private key (RS256)
	 * 6. Send JWT to Google OAuth token endpoint
	 * 7. Receive and return access token
	 *
	 * @since 1.0.0
	 */
	private function generate_gscf7_service_token( $creds ) {
		try {
			$now = time();

			$header = array(
				'alg' => 'RS256',
				'typ' => 'JWT',
			);

			$payload = array(
				'iss'   => $creds['client_email'],
				'scope' => implode(
					' ',
					array(
						'https://www.googleapis.com/auth/spreadsheets',
						'https://www.googleapis.com/auth/drive.metadata.readonly',
					)
				),
				'aud'   => 'https://oauth2.googleapis.com/token',
				'exp'   => $now + 3600,
				'iat'   => $now,
			);

			$base64 = function ( $data ) {
					return rtrim( strtr( base64_encode( json_encode( $data ) ), '+/', '-_' ), '=' );
			};

			$jwt_header  = $base64( $header );
			$jwt_payload = $base64( $payload );

			$signature_input = $jwt_header . '.' . $jwt_payload;

			openssl_sign( $signature_input, $signature, $creds['private_key'], 'sha256' );

			$jwt = $signature_input . '.' . strtr( base64_encode( $signature ), '+/', '-_' );

			$response = wp_remote_post(
				'https://oauth2.googleapis.com/token',
				array(
					'body' => array(
						'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
						'assertion'  => $jwt,
					),
				)
			);

			if ( is_wp_error( $response ) ) {
						return false;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			return $body['access_token'] ?? false;
		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log(
				__METHOD__ . ' Error while generating WC Token: ' . $e->getMessage()
			);
			return null;
		}
	}

	/**
	 * Default timeout, in seconds, for every Google API request.
	 *
	 * wp_remote_* defaults to 5 seconds per request. Because the submission path
	 * issues several requests in sequence, an explicit shared value keeps the
	 * worst-case latency predictable.
	 *
	 * @since 5.2.1
	 */
	const GSC_API_TIMEOUT = 10;

	/**
	 * Build the shared request arguments for a Google API call.
	 *
	 * @since 5.2.1
	 *
	 * @param string $token  Bearer token.
	 * @param array  $extra  Additional wp_remote_* arguments.
	 * @return array Request arguments.
	 */
	private static function gsc_request_args( $token, $extra = array() ) {
		$args = array(
			'timeout' => self::GSC_API_TIMEOUT,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
			),
		);

		if ( isset( $extra['headers'] ) ) {
			$args['headers'] = array_merge( $args['headers'], $extra['headers'] );
			unset( $extra['headers'] );
		}

		return array_merge( $args, $extra );
	}

	/**
	 * Convert a zero-based column index into its A1 notation letters.
	 *
	 * @since 5.2.1
	 *
	 * @param int $index Zero-based column index.
	 * @return string Column letters, e.g. 0 => A, 26 => AA.
	 */
	private static function gsc_column_letter( $index ) {
		$index  = max( 0, (int) $index );
		$letter = '';

		do {
			$letter = chr( 65 + ( $index % 26 ) ) . $letter;
			$index  = intdiv( $index, 26 ) - 1;
		} while ( $index >= 0 );

		return $letter;
	}

	/**
	 * Retrieve spreadsheet metadata, using a short-lived cache.
	 *
	 * Sheet titles and IDs change very rarely, but this request previously ran on
	 * every single form submission. Caching it removes one blocking round-trip
	 * from the critical path without affecting the data that is written.
	 *
	 * @since 5.2.1
	 *
	 * @param string $spreadsheet_id Spreadsheet ID.
	 * @param string $token          Bearer token.
	 * @return array|WP_Error Decoded metadata, or WP_Error on failure.
	 */
	private function gsc_get_spreadsheet_meta( $spreadsheet_id, $token ) {
		$cache_key = 'cf7gsc_meta_' . md5( $spreadsheet_id );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode( $spreadsheet_id ),
			self::gsc_request_args( $token )
		);

		$body = self::gsc_parse_response( $response, 'spreadsheet metadata' );

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		if ( empty( $body['sheets'] ) || ! is_array( $body['sheets'] ) ) {
			return new WP_Error(
				'cf7gsc_no_sheets',
				__( 'The spreadsheet returned no worksheets.', 'cf7-google-sheets-connector' )
			);
		}

		set_transient( $cache_key, $body, 15 * MINUTE_IN_SECONDS );

		return $body;
	}

	/**
	 * Validate a Google API response and return its decoded body.
	 *
	 * Previously the submission path dereferenced response bodies without any
	 * checks, so a transport error, an expired token or a rate-limit response
	 * produced PHP warnings and silently discarded the submission.
	 *
	 * @since 5.2.1
	 *
	 * @param array|WP_Error $response Response from a wp_remote_* call.
	 * @param string         $context  Short description used in the log entry.
	 * @return array|WP_Error Decoded body, or WP_Error on failure.
	 */
	private static function gsc_parse_response( $response, $context ) {
		if ( is_wp_error( $response ) ) {
			Gs_Connector_Free_Utility::gs_debug_log(
				array(
					'context' => 'google_sheets:' . $context,
					'error'   => $response->get_error_message(),
				)
			);

			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) ) {
			$body = array();
		}

		if ( $code < 200 || $code > 299 ) {
			$message = isset( $body['error']['message'] )
			? $body['error']['message']
			: __( 'Unexpected response from the Google Sheets API.', 'cf7-google-sheets-connector' );

			Gs_Connector_Free_Utility::gs_debug_log(
				array(
					'context' => 'google_sheets:' . $context,
					'status'  => $code,
					'error'   => $message,
				)
			);

			return new WP_Error( 'cf7gsc_api_error', $message, array( 'status' => $code ) );
		}

		return $body;
	}

	/**
	 * Clear cached spreadsheet metadata.
	 *
	 * @since 5.2.1
	 *
	 * @param string $spreadsheet_id Spreadsheet ID, or empty to skip.
	 * @return void
	 */
	public static function gsc_flush_meta_cache( $spreadsheet_id = '' ) {
		if ( ! empty( $spreadsheet_id ) ) {
			delete_transient( 'cf7gsc_meta_' . md5( $spreadsheet_id ) );
		}

		delete_transient( 'cf7gsc_connected_email' );
	}

	// preg_match is a key of error handle in this case
	public function setSpreadsheetId( $id ) {
		$this->spreadsheet = $id;
	}

	public function getSpreadsheetId() {

		return $this->spreadsheet;
	}

	public function setWorkTabId( $id ) {
		$this->worksheet = $id;
	}

	public function getWorkTabId() {
		return $this->worksheet;
	}

	public function add_row( $data ) {
		try {

			$spreadsheetId = $this->getSpreadsheetId();
			$worksheet_id  = $this->getWorkTabId();
			$token         = $this->get_active_token();

			if ( empty( $token ) || empty( $data ) ) {
				return;
			}

			/*
			|--------------------------------------------------------------------------
			| 1. RESOLVE THE TARGET WORKSHEET (cached)
			|--------------------------------------------------------------------------
			*/

			$meta = $this->gsc_get_spreadsheet_meta( $spreadsheetId, $token );

			if ( is_wp_error( $meta ) ) {
				return $meta;
			}

			$sheet_id    = null;
			$sheet_title = null;

			foreach ( $meta['sheets'] as $sheet ) {

				$properties = isset( $sheet['properties'] ) ? $sheet['properties'] : array();

				if ( ! isset( $properties['sheetId'], $properties['title'] ) ) {
					continue;
				}

				if ( $properties['sheetId'] != $worksheet_id && $properties['title'] != $worksheet_id ) {
					continue;
				}

				$sheet_id    = $properties['sheetId'];
				$sheet_title = $properties['title'];
				break;
			}

			if ( null === $sheet_title ) {

				// The tab may have been renamed or deleted; drop the cache so the
				// next submission re-reads the spreadsheet structure.
				self::gsc_flush_meta_cache( $spreadsheetId );

				return new WP_Error(
					'cf7gsc_tab_not_found',
					__( 'The configured worksheet tab could not be found in the spreadsheet.', 'cf7-google-sheets-connector' )
				);
			}

			/*
			|--------------------------------------------------------------------------
			| 2. READ HEADER ROW
			|--------------------------------------------------------------------------
			|
			| Read live rather than cached: a column added in Google Sheets must be
			| picked up immediately, otherwise values would be written to the wrong
			| columns until the cache expired.
			*/

			$header_range = $sheet_title . '!1:1';

			$response = wp_remote_get(
				"https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/" . rawurlencode( $header_range ),
				self::gsc_request_args( $token )
			);

			$body = self::gsc_parse_response( $response, 'read header row' );

			if ( is_wp_error( $body ) ) {
				return $body;
			}

			$headers = isset( $body['values'][0] ) ? $body['values'][0] : array();

			if ( empty( $headers ) ) {
				return new WP_Error(
					'cf7gsc_no_headers',
					__( 'The worksheet has no header row, so submitted fields cannot be mapped to columns.', 'cf7-google-sheets-connector' )
				);
			}

			/*
			|--------------------------------------------------------------------------
			| 3. PREPARE INSERT DATA
			|--------------------------------------------------------------------------
			*/

			$insert_data = array();

			foreach ( $headers as $colName ) {
				$insert_data[] = isset( $data[ $colName ] ) ? $data[ $colName ] : '';
			}

			// Force Entry ID to be treated as a string.
			$entry_id_col = array_search( 'Entry ID', $headers, true );

			if ( false !== $entry_id_col && isset( $insert_data[ $entry_id_col ] ) ) {
				$insert_data[ $entry_id_col ] = (string) $insert_data[ $entry_id_col ];
			}

			/*
			|--------------------------------------------------------------------------
			| 4. LOCATE AN EXISTING ROW WITH THE SAME ENTRY ID
			|--------------------------------------------------------------------------
			|
			| Only the Entry ID column is read. The previous implementation fetched
			| the whole A:Z range of the sheet on every submission purely to work out
			| the next free row; appending (step 5) makes that unnecessary.
			*/

			$existing_row = false;

			if ( false !== $entry_id_col && ! empty( $data['Entry ID'] ) ) {

				$column_letter = self::gsc_column_letter( $entry_id_col );
				$column_range  = $sheet_title . '!' . $column_letter . ':' . $column_letter;

				$response = wp_remote_get(
					"https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/" . rawurlencode( $column_range ),
					self::gsc_request_args( $token )
				);

				$column_body = self::gsc_parse_response( $response, 'read entry id column' );

				if ( ! is_wp_error( $column_body ) ) {

					$column_values = isset( $column_body['values'] ) ? $column_body['values'] : array();

					foreach ( $column_values as $index => $row ) {

						if ( 0 === $index ) {
							continue;
						}

						$sheet_entry_id = isset( $row[0] ) ? $row[0] : '';

						if ( (string) $sheet_entry_id === (string) $data['Entry ID'] ) {
							$existing_row = $index + 1;
							break;
						}
					}
				}
			}

			/*
			|--------------------------------------------------------------------------
			| 5. UPDATE OR APPEND
			|--------------------------------------------------------------------------
			|
			| New rows use values:append, which resolves the target row server-side.
			| This removes the read-then-write race that allowed two concurrent
			| submissions to compute the same row number and overwrite each other.
			*/

			if ( $existing_row ) {

				$update_range = $sheet_title . '!A' . $existing_row;

				$response = wp_remote_request(
					"https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/" .
					rawurlencode( $update_range ) .
					'?valueInputOption=RAW',
					self::gsc_request_args(
						$token,
						array(
							'method'  => 'PUT',
							'headers' => array( 'Content-Type' => 'application/json' ),
							'body'    => wp_json_encode( array( 'values' => array( $insert_data ) ) ),
						)
					)
				);

				$result = self::gsc_parse_response( $response, 'update row' );

				if ( is_wp_error( $result ) ) {
					return $result;
				}

				$row_number = $existing_row;

			} else {

				$response = wp_remote_post(
					"https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/" .
					rawurlencode( $sheet_title ) .
					':append?valueInputOption=RAW&insertDataOption=INSERT_ROWS',
					self::gsc_request_args(
						$token,
						array(
							'headers' => array( 'Content-Type' => 'application/json' ),
							'body'    => wp_json_encode( array( 'values' => array( $insert_data ) ) ),
						)
					)
				);

				$result = self::gsc_parse_response( $response, 'append row' );

				if ( is_wp_error( $result ) ) {
					return $result;
				}

				$row_number = self::gsc_row_from_range(
					isset( $result['updates']['updatedRange'] ) ? $result['updates']['updatedRange'] : ''
				);
			}

			/*
			|--------------------------------------------------------------------------
			| 6. CLEAR FORMATTING ON THE WRITTEN ROW
			|--------------------------------------------------------------------------
			*/

			if ( $row_number > 0 ) {

				$clearRequests = array();

				$clearRequests[] = array(
					'repeatCell' => array(
						'range'  => array(
							'sheetId'       => $sheet_id,
							'startRowIndex' => $row_number - 1,
							'endRowIndex'   => $row_number,
						),
						'cell'   => array(
							'userEnteredFormat' => array(
								'backgroundColor' => array(
									'red'   => 1,
									'green' => 1,
									'blue'  => 1,
								),
								'textFormat'      => array(
									'foregroundColor' => array(
										'red'   => 0,
										'green' => 0,
										'blue'  => 0,
									),
									'bold'            => false,
									'italic'          => false,
									'underline'       => false,
									'strikethrough'   => false,
								),
							),
						),
						'fields' => 'userEnteredFormat.textFormat',
					),
				);

				if ( false !== $entry_id_col ) {

					$clearRequests[] = array(
						'repeatCell' => array(
							'range'  => array(
								'sheetId'          => $sheet_id,
								'startRowIndex'    => $row_number - 1,
								'endRowIndex'      => $row_number,
								'startColumnIndex' => $entry_id_col,
								'endColumnIndex'   => $entry_id_col + 1,
							),
							'cell'   => array(
								'userEnteredFormat' => array(
									'numberFormat' => array(
										'type'    => 'TEXT',
										'pattern' => '@',
									),
								),
							),
							'fields' => 'userEnteredFormat.numberFormat',
						),
					);
				}

				wp_remote_post(
					"https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}:batchUpdate",
					self::gsc_request_args(
						$token,
						array(
							'headers' => array( 'Content-Type' => 'application/json' ),
							'body'    => wp_json_encode( array( 'requests' => $clearRequests ) ),
						)
					)
				);
			}

			return $result;

		} catch ( Exception $e ) {

			Gs_Connector_Free_Utility::gs_debug_log(
				array(
					'message' => $e->getMessage(),
					'file'    => $e->getFile(),
					'line'    => $e->getLine(),
				)
			);
		}
	}

	/**
	 * Extract the 1-based row number from an A1 notation range.
	 *
	 * @since 5.2.1
	 *
	 * @param string $range Range such as "Sheet1!A5:D5".
	 * @return int Row number, or 0 when it cannot be determined.
	 */
	private static function gsc_row_from_range( $range ) {
		if ( empty( $range ) ) {
			return 0;
		}

		$matches = array();

		if ( preg_match( '/![A-Z]+(\d+)/', $range, $matches ) ) {
			return (int) $matches[1];
		}

		return 0;
	}

	public function add_multiple_row( $data ) {
		try {
			$spreadsheetId = $this->getSpreadsheetId();
			$worksheet_id  = $this->getWorkTabId();
			$token         = $this->get_active_token();

			if ( empty( $token ) || empty( $data ) ) {
				return false;
			}

			// 1. GET SPREADSHEET DETAILS (SHEETS LIST)
			$response = wp_remote_get(
				"https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}",
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $token,
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $body['sheets'] ) ) {
				return false;
			}

			foreach ( $body['sheets'] as $sheet ) {
				$properties = $sheet['properties'];
				$sheet_id   = $properties['sheetId'];

				// Match worksheet ID
				if ( $sheet_id == $worksheet_id ) {
					$worksheet_title = $properties['title'];

					// 2. GET HEADER ROW (1:1)
					$header_range    = $worksheet_title . '!1:1';
					$header_response = wp_remote_get(
						"https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/" . urlencode( $header_range ),
						array(
							'headers' => array(
								'Authorization' => 'Bearer ' . $token,
							),
						)
					);

					if ( is_wp_error( $header_response ) ) {
						return false;
					}

					$header_body = json_decode( wp_remote_retrieve_body( $header_response ), true );
					$headers     = $header_body['values'][0] ?? array();

					$final_data = array();

					if ( ! empty( $headers ) ) {
						// 3. MATCH HEADER WITH MULTIPLE DATA ROWS
						foreach ( $data as $key => $value ) {
							$insert_data = array();
							foreach ( $headers as $name ) {
								if ( isset( $value[ $name ] ) && $value[ $name ] !== '' ) {
									$insert_data[] = $value[ $name ];
								} else {
									$insert_data[] = '';
								}
							}
							$final_data[] = $insert_data;
						}
					}

					// 4. GET CURRENT VALUES TO FIND THE ROW COUNT (Original Rashid's logic)
					$full_range      = $worksheet_title . '!A1:Z';
					$values_response = wp_remote_get(
						"https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/" . urlencode( $full_range ),
						array(
							'headers' => array(
								'Authorization' => 'Bearer ' . $token,
							),
						)
					);

					if ( is_wp_error( $values_response ) ) {
						return false;
					}

					$values_body = json_decode( wp_remote_retrieve_body( $values_response ), true );
					$get_values  = $values_body['values'] ?? null;

					if ( $get_values ) {
						$row = count( $get_values ) + 1;
					} else {
						$row = 1;
					}

					// Setup the exact range as per original logic
					$range = $worksheet_title . '!A' . $row . ':Z';

					$sheet_values = $final_data;

					// 5. APPEND MULTIPLE ROWS
					if ( ! empty( $sheet_values ) ) {
						$append_url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/" . urlencode( $range ) . ':append?valueInputOption=USER_ENTERED';

						$append_response = wp_remote_post(
							$append_url,
							array(
								'headers' => array(
									'Authorization' => 'Bearer ' . $token,
									'Content-Type'  => 'application/json',
								),
								'body'    => wp_json_encode(
									array(
										'values' => $sheet_values,
									)
								),
							)
						);

						if ( is_wp_error( $append_response ) ) {
							return false;
						}
					}

					return true;
				}
			}

			return false;
		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log( $e->getMessage() );
			return null;
		}
	}

	// get all the spreadsheets
	public function get_spreadsheets() {
		$all_sheets = array();
		try {
			$token = $this->get_active_token();

			if ( empty( $token ) ) {
				return null;
			}

			// 1. PREPARE GOOGLE DRIVE API URL WITH Q PARAMETER
			// mimeType='application/vnd.google-apps.spreadsheet'
			$query = urlencode( "mimeType='application/vnd.google-apps.spreadsheet'" );
			$url   = 'https://www.googleapis.com/drive/v3/files?q=' . $query;

			// 2. HTTP GET REQUEST USING WordPress API
			$response = wp_remote_get(
				$url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $token,
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return null;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			// 3. PARSE FILES AND MAP TO ORIGINAL STRUCTURE
			if ( ! empty( $body['files'] ) ) {
				foreach ( $body['files'] as $spreadsheet ) {
					// Check if kind matches (Google Drive API returns 'drive#file' for files)
					if ( isset( $spreadsheet['kind'] ) && $spreadsheet['kind'] == 'drive#file' ) {
						$all_sheets[] = array(
							'id'    => $spreadsheet['id'] ?? '',
							'title' => $spreadsheet['name'] ?? '', // Original code uses 'name' API field for 'title'
						);
					}
				}
			}
		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log( $e->getMessage() );
			return null;
		}
		return $all_sheets;
	}


	// get worksheets title
	public function get_worktabs( $spreadsheet_id ) {
		$work_tabs_list = array();
		try {
			$token = $this->get_active_token();

			if ( empty( $token ) || empty( $spreadsheet_id ) ) {
				return null;
			}

			// 1. HTTP GET REQUEST TO FETCH SPREADSHEET DETAIL
			$response = wp_remote_get(
				"https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}",
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $token,
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return null;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			// 2. PARSE SHEETS AND EXTRACT ID & TITLE
			if ( ! empty( $body['sheets'] ) ) {
				foreach ( $body['sheets'] as $sheet ) {
					$properties = $sheet['properties'] ?? array();

					$work_tabs_list[] = array(
						'id'    => $properties['sheetId'] ?? '',
						'title' => $properties['title'] ?? '',
					);
				}
			}
		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log( $e->getMessage() );
			return null;
		}

		return $work_tabs_list;
	}

	/**
	 * GET GOOGLE USER EMAIL FROM ACCESS TOKEN
	 *
	 * This method retrieves the authenticated Google user's email address
	 * using the provided OAuth access token.
	 *
	 * Workflow:
	 * 1. Validate access token exists
	 * 2. Send request to Google UserInfo API
	 * 3. Decode API response
	 * 4. Extract and return user email if available
	 *
	 * If the request fails or email is not found, an empty string is returned.
	 *
	 * @since 1.0.0
	 * @param string $token Google OAuth access token
	 * @return string User email or empty string on failure
	 */
	private function get_google_user_email( $token ) {
		try {
			if ( ! $token ) {
				return '';
			}

			$response = wp_remote_get(
				'https://www.googleapis.com/oauth2/v2/userinfo',
				self::gsc_request_args( $token )
			);

			if ( is_wp_error( $response ) ) {
				return '';
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $body['email'] ) ) {
				return $body['email'];
			}

			return '';
		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log(
				__METHOD__ . " Error in fetching user info: \n " . $e->getMessage()
			);
			return false;
		}
	}

	/**
	 * Get and store the authenticated Google account email.
	 *
	 * Authenticates the Google client, fetches the connected
	 * Google account email address, and stores it in the
	 * WordPress options table.
	 *
	 * @since 3.1
	 *
	 * @return string|false Google account email on success, false on failure.
	 */
	public function gsheet_print_google_account_email() {
		try {

			/*
			 * This method previously issued an uncached HTTPS request to Google on
			 * every plugin admin page and on every Contact Form 7 editor page load.
			 * The connected account changes only when the user re-authenticates, so
			 * the result is cached and explicitly invalidated by the auth flows.
			 */
			$cached = get_transient( 'cf7gsc_connected_email' );

			if ( false !== $cached ) {
				return '' === $cached ? false : $cached;
			}

			$token = $this->token();

			if ( ! $token ) {

				update_option( 'cf7gf_email_account', '', false );
				set_transient( 'cf7gsc_connected_email', '', 5 * MINUTE_IN_SECONDS );

				return false;
			}

			$email = $this->get_google_user_email( $token );

			if ( empty( $email ) ) {

				// Cache the negative result briefly so a broken connection does not
				// retry the request on every single admin page load.
				set_transient( 'cf7gsc_connected_email', '', 5 * MINUTE_IN_SECONDS );

				$auth_method = get_option( 'gs_cf7_auth_method', 'cf7_existing' );
				if ( $auth_method === 'cf7_existing' ) {

					update_option(
						'cf7gf_email_account',
						'',
						false
					);

					if ( class_exists( 'gscf7_error_logs' ) ) {

						gscf7_error_logs::log_to_db(
							'Google_User_Email_Empty',
							403,
							'Google user email could not be retrieved (Existing Method)',
							array(
								'error_type'            => 'connected_email_empty',
								'authentication_method' => 'Existing',
								'message'               => 'Failed to retrieve the connected Google account email address.',
							)
						);
					}
				}

				return false;
			}

			update_option(
				'cf7gf_email_account',
				$email,
				false
			);

			set_transient( 'cf7gsc_connected_email', $email, HOUR_IN_SECONDS );

			return $email;

		} catch ( Exception $e ) {

			update_option(
				'cf7gf_email_account',
				'',
				false
			);

			Gs_Connector_Free_Utility::gs_debug_log(
				__METHOD__ .
				' Error fetching email: ' .
				$e->getMessage()
			);

			return false;
		}
	}

	/**
	 * Check whether the provided Google Sheet ID is accessible.
	 *
	 * This function verifies if the authenticated Google account
	 * has permission to access the specified Google Spreadsheet.
	 *
	 * @since 3.0
	 *
	 * @param string $sheet_id Google Spreadsheet ID.
	 *
	 * @return array Response containing status and message.
	 */
	public function check_sheet_access( $sheet_id ) {
		try {
			$token = $this->get_active_token();
			if ( ! $token ) {
				return array(
					'status'  => false,
					'message' => 'Token not found',
				);
			}

			$response = wp_remote_get(
				"https://sheets.googleapis.com/v4/spreadsheets/{$sheet_id}",
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $token,
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return array(
					'status'  => false,
					'message' => $response->get_error_message(),
				);
			}

			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $code === 200 && ! empty( $body['spreadsheetId'] ) ) {
				return array(
					'status'  => true,
					'message' => 'Sheet is accessible',
				);
			}

			return array(
				'status'  => false,
				'message' => $body['error']['message'] ?? 'Access denied',
			);

		} catch ( Exception $e ) {
			Gs_Connector_Free_Utility::gs_debug_log(
				'Sheet Access Check Failed: ' . $e->getMessage()
			);
			return false;
		}
	}
}
