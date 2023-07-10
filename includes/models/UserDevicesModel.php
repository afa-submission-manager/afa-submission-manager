<?php
/**
 * The User Devices Model Class.
 *
 * @package  WP_All_Forms_API
 * @since 1.0.0
 */

namespace Includes\Models;

use Includes\Plugins\Constant;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class UserDevicesModel
 *
 * Hendler with user device data
 *
 * @since 1.0.0
 */
class UserDevicesModel {

	/**
	 * Table name
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * UserQRCodeModel constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . Constant::TABLE_USER_DEVICE;
	}

	/**
	 * Create new device register
	 *
	 * @param int    $user_id The user ID.
	 * @param string $device_id The device virtual ID.
	 * @param string $device_language The device language.
	 * @param string $expo_token The user token for push notification.
	 *
	 * @return int|false
	 */
	public function create( $user_id, $device_id, $device_language, $expo_token = '' ) {
		global $wpdb;

		$item = array(
			'user_id'         => $user_id,
			'device_id'       => $device_id,
			'device_language' => $device_language,
			'expo_token'      => $expo_token,
			'created_at'      => gmdate( 'Y-m-d H:i:s' ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->insert(
			$this->table_name,
			$item
		);

		return $results;
	}

	/**
	 * Delete register
	 *
	 * @param string $expo_token The user token for push notification.
	 *
	 * @return int|false
	 */
	public function delete_register_by_expo_token( $expo_token ) {
		global $wpdb;

		$item = array(
			'expo_token' => $expo_token,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->delete(
			$this->table_name,
			$item
		);

		return $results;
	}

	/**
	 * Delete register
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return int|false
	 */
	public function delete_register_by_user_id( $user_id ) {
		global $wpdb;

		$item = array(
			'user_id' => $user_id,
		);

		$item_format = array(
			'%d',
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->delete(
			$this->table_name,
			$item,
			$item_format
		);

		return $results;
	}


}