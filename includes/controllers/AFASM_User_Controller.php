<?php
/**
 * The User Controller Class.
 *
 * @package  claud/afa-submission-manager
 * @since 1.0.0
 */

namespace AFASM\Includes\Controllers;

use AFASM\Includes\Models\AFASM_User_Model;
use AFASM\Includes\Models\AFASM_User_Tokens_Model;
use AFASM\Includes\Plugins\JWT\AFASM_JWT_Plugin;
use AFASM\Includes\Models\AFASM_User_QR_Code_Model;
use AFASM\Includes\Plugins\AFASM_Config;
use AFASM\Includes\Database\AFASM_Supported_Plugins;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class UserController
 *
 * Manipulate User info
 *
 * @since 1.0.0
 */
class AFASM_User_Controller {

	/**
	 * User Model
	 *
	 * @var AFASM_User_Model
	 */
	private $user_model;

	/**
	 * JWT Plugin
	 *
	 * @var JWTPlugin
	 */
	private $jwt_plugin;

	/**
	 * User Tokens Model
	 *
	 * @var AFASM_User_Tokens_Model
	 */
	private $user_tokens_model;

	/**
	 * UserController constructor.
	 */
	public function __construct() {
		$this->user_model        = new AFASM_User_Model();
		$this->jwt_plugin        = new AFASM_JWT_Plugin();
		$this->user_tokens_model = new AFASM_User_Tokens_Model();
	}

	/**
	 * Login user.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_User|WP_Error $user WP User with tokens info
	 */
	public function login( $request ) {
		$username = sanitize_user( $request['username'] );
		$password = $request['password'];

		$user = $this->user_model->login( $username, $password );

		if ( is_wp_error( $user ) ) {
			return rest_ensure_response( $user );
		}

		$this->user_tokens_model->delete_user_token_by_id( $user->data->ID );

		$access_token         = $this->jwt_plugin->generate_token( $user->data->ID );
		$access_refresh_token = $this->jwt_plugin->generate_refresh_token( $user->data->ID );

		$this->user_tokens_model->create( $user->data->ID, $access_token, $access_refresh_token );

		$data = array(
			'access_token'      => $access_token,
			'refresh_token'     => $access_refresh_token,
			'id'                => $user->data->ID,
			'user_email'        => $user->data->user_email,
			'user_nicename'     => $user->data->user_nicename,
			'user_display_name' => $user->data->display_name,
		);
		return rest_ensure_response( $data );
	}

	/**
	 * Login with QRCode .
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_User|WP_Error $user WP User with tokens info
	 */
	public function login_qr_code( $request ) {

		$secret = sanitize_text_field( $request['secret'] );

		$result = explode( '|', $secret, 2 );

		if ( count( $result ) !== 2 ) {
			return new WP_Error(
				'qr_code_auth_invalid_token',
				'QRCode token bad format',
				array(
					'status' => 403,
				)
			);
		}

		$user_id = $result[0];
		$user    = get_user_by( 'ID', $user_id );

		if ( false === $user ) {
			return new WP_Error(
				'invalid_user',
				'User not found',
				array(
					'status' => 403,
				)
			);
		}

		if ( ! $this->user_model->user_can_manage_afa( $user_id ) ) {
			return new WP_Error(
				'invalid_role',
				'Sorry, you are not allowed to login',
				array(
					'status' => 401,
				)
			);
		}

		$secret = $result[1];

		$verify_qr_code = ( new AFASM_User_QR_Code_Model() )->verify_qr_code( $user_id, $secret );

		if ( is_wp_error( $verify_qr_code ) ) {
			return rest_ensure_response( $verify_qr_code );
		}

		$this->user_tokens_model->delete_user_token_by_id( $user->data->ID );

		$access_token         = $this->jwt_plugin->generate_token( $user->data->ID );
		$access_refresh_token = $this->jwt_plugin->generate_refresh_token( $user->data->ID );

		$this->user_tokens_model->create( $user->data->ID, $access_token, $access_refresh_token );

		( new AFASM_User_QR_Code_Model() )->delete_qr_code_by_user_id( $user->data->ID );

		$data = array(
			'access_token'      => $access_token,
			'refresh_token'     => $access_refresh_token,
			'id'                => $user->data->ID,
			'user_email'        => $user->data->user_email,
			'user_nicename'     => $user->data->user_nicename,
			'user_display_name' => $user->data->display_name,
		);

		return rest_ensure_response( $data );
	}


	/**
	 * Get user.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return array $user Some User info.
	 */
	public function user( $request ) {
		$user_id = absint( $request['user_id'] );
		return rest_ensure_response( $this->user_model->user( $user_id ) );
	}

	/**
	 * Get user.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return array $user Some User info.
	 */
	public function user_form_type_me( $request ) {
		$key = sanitize_text_field( $request['form_type'] );

		$user_id = absint( $request['user_id'] );

		$number_of_forms = 0;

		$form = ( new AFASM_Config() )->form_model( $key );

		if ( is_object( $form ) ) {
			$number_of_forms = $form->user_form_count( $user_id );
		}

		$user_data = $this->user_model->user( $user_id );

		$user_data['muber_of_forms'] = $number_of_forms;
		return rest_ensure_response( $user_data );
	}

	/**
	 * Get user.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return array $user Some User info.
	 */
	public function user_form_type_home( $request ) {
		$key = sanitize_text_field( $request['form_type'] );

		$user_id = absint( $request['user_id'] );

		$number_of_forms = 0;

		$form = ( new AFASM_Config() )->form_model( $key );

		if ( is_object( $form ) ) {
			$number_of_forms = $form->user_form_count( $user_id );
		}

		$user_data = $this->user_model->user( $user_id );

		$user_data['muber_of_forms'] = $number_of_forms;

		$plugin_name = ( new AFASM_Supported_Plugins() )->get_plugin_name_by_slug( $key );

		$result = array();

		$result['plugin_name'] = $plugin_name;
		$result['user_data']   = $user_data;

		$entry = ( new AFASM_Config() )->entry_model( $key );

		$offset                     = 0;
		$number_of_records_per_page = 3;
		$result['last_entries']     = $entry->entries( $offset, $number_of_records_per_page );

		return rest_ensure_response( $result );
	}

	/**
	 * Decodes a JWT string into a PHP object.
	 * if sucess force user login and keep on
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return array $data The info with user id, acess token and  refresh token.
	 */
	public function token( $request ) {

		$refresh_token = sanitize_text_field( $request['refresh_token'] );
		$validate      = $this->jwt_plugin->validate_refresh_token( $refresh_token );

		if ( is_wp_error( $validate ) ) {
			return rest_ensure_response( $validate );
		}

		$user_id              = $validate->id;
		$check_resfresh_token = $this->user_tokens_model->check_if_refresh_token_exist( $user_id, $refresh_token );

		if ( ! $check_resfresh_token ) {
			return new WP_Error(
				'jwt_auth_invalid_token',
				'Refresh token not found',
				array(
					'status' => 403,
				)
			);
		}

		if ( ! $this->user_model->user_can_manage_afa( $user_id ) ) {
			return new WP_Error(
				'invalid_role',
				'Sorry, you are not allowed to login',
				array(
					'status' => 401,
				)
			);
		}

		$this->user_tokens_model->delete_user_token_by_id( $user_id );

		$access_token         = $this->jwt_plugin->generate_token( $user_id );
		$access_refresh_token = $this->jwt_plugin->generate_refresh_token( $user_id );

		$this->user_tokens_model->create( $user_id, $access_token, $access_refresh_token );

		$data = array(
			'access_token'  => $access_token,
			'refresh_token' => $access_refresh_token,
			'id'            => $user_id,
		);

		return rest_ensure_response( $data );
	}

	/**
	 * Logout user.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return boolean|WP_Error $user WP User with tokens info
	 */
	public function logout( $request ) {
		$device_id = sanitize_text_field( $request['device_id'] );
		$user_id = absint( $request['user_id'] );
		$result    = $this->user_model->logout( $user_id, $device_id );
		return rest_ensure_response( $result );
	}

}
