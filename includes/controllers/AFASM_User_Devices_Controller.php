<?php
/**
 * The User Devices Controller Class.
 *
 * @package  claud/afa-submission-manager
 * @since 1.0.0
 */

namespace AFASM\Includes\Controllers;

use AFASM\Includes\Models\AFASM_User_Devices_Model;
use AFASM\Includes\Models\AFASM_Notification_Subscription_Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class UserDevicesController
 *
 * Manipulate User device info
 *
 * @since 1.0.0
 */
class AFASM_User_Devices_Controller {
	/**
	 * User Tokens Model
	 *
	 * @var AFASM_User_Devices_Model
	 */
	private $user_devices_model;

	/**
	 * UserTokensController constructor.
	 */
	public function __construct() {
		$this->user_devices_model = new AFASM_User_Devices_Model();
	}


	/**
	 * Create user device register.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return int|false
	 */
	public function create( $request ) {

		$expo_token      = sanitize_text_field( $request['expo_token'] );
		$device_id       = sanitize_text_field( $request['device_id'] );
		$device_language = sanitize_text_field( $request['device_language'] );

		$user_id = absint( $request['user_id'] );

		$result = $this->user_devices_model->create_device_register_if_not_exist( $user_id, $device_id, $device_language, $expo_token );

		if ( ! empty( $result ) ) {
			$notification_subscription_model = new AFASM_Notification_Subscription_Model();
			$notification_subscription_model->subscribe_user_all_notificions_by_expo_token( $expo_token );
		}
		return rest_ensure_response( $result );

	}

	/**
	 * Update language for user device register.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return int|false
	 */
	public function language( $request ) {

		$device_id       = sanitize_text_field( $request['device_id'] );
		$device_language = sanitize_text_field( $request['device_language'] );

		$user_id = absint( $request['user_id'] );
		$result = $this->user_devices_model->language( $user_id, $device_id, $device_language );
		return rest_ensure_response( $result );

	}


}
