<?php
/**
 * The JWTPlugin handler
 *
 * @package  claud/afa-submission-manager
 * @since 1.0.0
 */

namespace AFASM\Includes\Plugins\JWT;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use AFASM\Includes\Plugins\AFASM_Public_Route;
use AFASM\Includes\Plugins\AFASM_Constant;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class AFASM_JWT_Plugin
 *
 * Handler with token
 *
 * @since 1.0.0
 */
class AFASM_JWT_Plugin {

	/**
	 * The route name space
	 *
	 * @var string
	 */
	private $namespace;

	/**
	 * AFASM_JWT_Plugin constructor.
	 */
	public function __construct() {
		$this->namespace = AFASM_Constant::API_NAMESPACE . '/' . AFASM_Constant::API_VERSION;
	}

	/**
	 * Generate new acces token.
	 *
	 * @param int $id The User ID.
	 *
	 * @return string $jwt The JWT.
	 */
	public function generate_token( $id ) {
		$issued_at           = time();
		$exp_token_in_minute = AFASM_Constant::API_ACCESS_EXP_TOKEN_IN_MINUTE;

		if ( empty( $exp_token_in_minute ) || ! is_numeric( $exp_token_in_minute ) ) {
			$exp_token_in_minute = 15;
		}

		$expire = $issued_at + ( MINUTE_IN_SECONDS * $exp_token_in_minute );

		$key = $this->get_access_token_secret_key();

		$payload = array(
			'iss' => get_bloginfo( 'url' ),
			'iat' => $issued_at,
			'id'  => $id,
			'exp' => $expire,
		);

		$jwt = JWT::encode( $payload, $key, 'HS256' );

		return $jwt;
	}

	/**
	 * Generate new refresh token.
	 *
	 * @param int $id The User ID.
	 *
	 * @return string $jwt The JWT.
	 */
	public function generate_refresh_token( $id ) {
		$issued_at           = time();
		$exp_token_in_minute = AFASM_Constant::API_REFRESH_EXP_TOKEN_IN_MINUTE;

		if ( empty( $exp_token_in_minute ) || ! is_numeric( $exp_token_in_minute ) ) {
			$exp_token_in_minute = 43200;
		}

		$expire = $issued_at + ( MINUTE_IN_SECONDS * $exp_token_in_minute );

		$key = $this->get_refresh_token_secret_key();

		$payload = array(
			'iss' => get_bloginfo( 'url' ),
			'iat' => $issued_at,
			'id'  => $id,
			'exp' => $expire,
		);

		$jwt = JWT::encode( $payload, $key, 'HS256' );

		return $jwt;
	}

	/**
	 * Decodes a JWT string into a PHP object.
	 * if sucess force user login and keep on
	 *
	 * @param mixed           $result Can be anything a normal endpoint can return, or null to not hijack the request.
	 * @param WP_REST_Server  $server Server instance.
	 * @param WP_REST_Request $request The request.
	 *
	 * @return array $decoded The token's payload.
	 */
	private function validate_token( $result, $server, $request ) {

		$authorization = $request->get_header( 'authorization' );

		if ( ! empty( $authorization ) ) {

			$key                 = $this->get_access_token_secret_key();
			$split_authorization = explode( ' ', $authorization );

			if ( 2 === count( $split_authorization ) ) {
				try {

					$jwt     = $split_authorization[1];
					$decoded = JWT::decode( $jwt, new Key( $key, 'HS256' ) );
					$request->set_param( 'user_id', $decoded->id );
					return $request;
				} catch ( Exception $e ) {

					return new WP_Error(
						'jwt_auth_invalid_token',
						$e->getMessage(),
						array(
							'status' => 403,
						)
					);
				}
			} else {
				return new WP_Error(
					'jwt_auth_invalid_token',
					'Incorrect JWT format',
					array(
						'status' => 403,
					)
				);
			}
		} else {
			$url = '';
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$url = strtok( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '?' );
			}
			return new WP_Error( 'not-logged-in', 'API Requests to ' . $url . ' are only supported for authenticated requests', array( 'status' => 401 ) );
		}
	}

	/**
	 * Decodes a JWT string into a PHP object.
	 *
	 * @param string $jwt The JWT.
	 *
	 * @return array $decoded The token's payload.
	 */
	public function validate_refresh_token( $jwt ) {
		try {
			$key     = $this->get_refresh_token_secret_key();
			$decoded = JWT::decode( $jwt, new Key( $key, 'HS256' ) );
			return $decoded;
		} catch ( Exception $e ) {

			return new WP_Error(
				'jwt_auth_invalid_token',
				$e->getMessage(),
				array(
					'status' => 403,
				)
			);
		}
	}

	/**
	 * Filter to hook the rest_pre_dispatch, if there is an error in the request
	 * send it, if there is no error just continue with the current request.
	 *
	 * @param mixed           $result Can be anything a normal endpoint can return, or null to not hijack the request.
	 * @param WP_REST_Server  $server Server instance.
	 * @param WP_REST_Request $request The request.
	 *
	 * @return mixed $result
	 */
	public function validate_token_rest_pre_dispatch( $result, $server, $request ) {
		$url = $request->get_route();

		$explode_namespace = explode( '/', $this->namespace );

		if ( 2 === count( $explode_namespace ) ) {

			if ( strpos( $url, $explode_namespace[0] ) !== false ) {

				$public_route = new AFASM_Public_Route( $this->namespace );

				$require_token = ! $public_route->is_public_route( substr( $url, 1 ) );

				if ( $require_token ) {
					$response = $this->validate_token( $result, $server, $request );
					if ( is_wp_error( $response ) ) {
						return $response;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Get a JWT key.
	 *
	 * @return string $key The token key.
	 */
	private function get_access_token_secret_key() {

		if ( defined( 'AFASM_ACCESS_TOKEN_SECRET_KEY' ) && ! empty( AFASM_ACCESS_TOKEN_SECRET_KEY ) ) {
			return AFASM_ACCESS_TOKEN_SECRET_KEY;
		}

		return get_option( 'AFASM_ACCESS_TOKEN_SECRET_KEY' );

	}

	/**
	 * Get a JWT key.
	 *
	 * @return string $key The token key.
	 */
	private function get_refresh_token_secret_key() {

		if ( defined( 'AFASM_REFRESH_TOKEN_SECRET_KEY' ) && ! empty( AFASM_REFRESH_TOKEN_SECRET_KEY ) ) {
			return AFASM_REFRESH_TOKEN_SECRET_KEY;
		}

		return get_option( 'AFASM_REFRESH_TOKEN_SECRET_KEY' );

	}

}
