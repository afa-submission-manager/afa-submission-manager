<?php
/**
 * The Form Model Class.
 *
 * @package  claud/afa-submission-manager
 * @since 1.0.0
 */

namespace AFASM\Includes\Models\EFB;

use AFASM\Includes\Plugins\Helpers\AFASM_Form_Model_Helper;
use AFASM\Includes\Models\AFASM_Abstract_Form_Model;
use AFASM\Includes\Models\AFASM_User_Model;
use AFASM\Includes\Models\EFB\AFASM_Entry_Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AbstractFormModel
 *
 * Create model functions
 *
 * @since 1.0.0
 */
class AFASM_Form_Model extends AFASM_Abstract_Form_Model {

	/**
	 * Const to declare table name.
	 */
	const TABLE_NAME = '';

	/**
	 * Const to declare shortcode.
	 */
	const SHORTCODE = '';

	/**
	 * The AFASM_Form_Model_Helper
	 *
	 * @var AFASM_Form_Model_Helper
	 */
	public $form_model_helper;

	/**
	 * Form model constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->form_model_helper = new AFASM_Form_Model_Helper( '' );
	}

	/**
	 * Get Forms
	 *
	 * @param int $offset The offset.
	 * @param int $number_of_records_per_page The posts per page.
	 *
	 * @return array
	 */
	public function forms( $offset, $number_of_records_per_page ) {
		global $wpdb;

		$query = "
			SELECT p.*, pm.meta_value AS form_data
			FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
			WHERE p.post_type = 'page'
			AND pm.meta_key = '_elementor_data'
			AND (pm.meta_value LIKE '%\"elType\":\"widget\"%' AND pm.meta_value LIKE '%\"form_name\"%')
			ORDER BY p.ID DESC
			LIMIT %d, %d
		";

		$sql = $wpdb->prepare( $query, array( $offset, $number_of_records_per_page ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// phpcs:ignore
		$results = $wpdb->get_results( $sql, OBJECT );

		$forms = $this->prepare_data( $results );

		return $forms;
	}

	/**
	 * Get Form by id
	 *
	 * @param int $id The form ID.
	 *
	 * @return array
	 */
	public function form_by_id( $id ) {
		global $wpdb;

		$query = "
			SELECT p.*, pm.meta_value AS form_data
			FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
			WHERE p.post_type = 'page'
			AND pm.meta_key = '_elementor_data'
			AND (pm.meta_value LIKE '%\"elType\":\"widget\"%' AND pm.meta_value LIKE '%\"form_name\"%')
			AND p.ID = %d
		";

		$sql = $wpdb->prepare( $query, array( $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// phpcs:ignore
		$results = $wpdb->get_results($sql, OBJECT);

		$forms = $this->prepare_data( $results );
		
		if ( count( $forms ) > 0 ) {
			return $forms[0];
		}
		
		return $forms;
	}

	/**
	 * Get number of Forms
	 *
	 * @param string $form_name The form name.
	 *
	 * @return int
	 */
	public function mumber_of_items_by_search( $form_name ) {
		global $wpdb;

		$form_name = '%' . $wpdb->esc_like( $form_name ) . '%';

		$query = "
			SELECT COUNT(p.ID)
			FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
			WHERE p.post_type = 'page'
			AND pm.meta_key = '_elementor_data'
			AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s)
    	";

		$sql = $wpdb->prepare( $query, array( '%"form_name":"' . $form_name . '"%', '%"form_name":"' . $form_name . '"%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// phpcs:ignore
		$count = $wpdb->get_var($sql);

		return $count;
	}

	/**
	 * Format Forms
	 *
	 * @param array $results The forms.
	 *
	 * @return array
	 */
	public function prepare_data( $results ) {
		$forms      = array();
		$user_model = new AFASM_User_Model();

		$modified_results = array();

		foreach ( $results as $result ) {
			$form_data         = $result->form_data;
			$pattern           = '/"form_name":"([^"]+)"/';
			$result->form_name = $result->post_title;
			if ( preg_match( $pattern, $form_data, $matches ) ) {
				$form_name         = $matches[1];
				$result->form_name = $form_name;
			}

			$modified_results[] = $result;
		}

		foreach ( $modified_results as $value ) {

			$form = array();

			$form['id']    = $value->ID;
			$form['title'] = $value->form_name;

			$form['date_created'] = $value->post_date;
			$form['registers']    = ( new AFASM_Entry_Model() )->mumber_of_items_by_form_id( $value->ID );
			$form['user_created'] = $user_model->user_info_by_id( $value->post_author );

			$form['perma_links'] = array(
				array(
					'page_name' => $value->post_title,
					'page_link' => get_permalink( $value->ID ),
				),
			);

			$forms[] = $form;
		}

		return $forms;
	}

	/**
	 * Search Forms
	 *
	 * @param string $form_name The post name of the form.
	 * @param int    $offset The offset.
	 * @param int    $number_of_records_per_page The posts per page.
	 *
	 * @return array
	 */
	public function search_forms( $form_name, $offset, $number_of_records_per_page ) {

		global $wpdb;

		$form_name = '%' . $wpdb->esc_like( $form_name ) . '%';

		$query = "
			SELECT p.*, pm.meta_value AS form_data
			FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
			WHERE p.post_type = 'page'
			AND pm.meta_key = '_elementor_data'
			AND pm.meta_value LIKE %s
			ORDER BY p.ID DESC
			LIMIT %d, %d
    	";

		$sql = $wpdb->prepare( $query, array( '%"form_name":"' . $form_name . '"%', $offset, $number_of_records_per_page ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

    	// phpcs:ignore
    	$results = $wpdb->get_results($sql, OBJECT);

		$forms = $this->prepare_data( $results );

		return $forms;

	}

	/**
	 * Count number of forms created by logged user
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id The user id.
	 *
	 * @return int
	 */
	public function user_form_count( $user_id ) {
		global $wpdb;

		$query = "
			SELECT COUNT(p.ID)
			FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
			WHERE p.post_type = 'page'
			AND p.post_author = %d
			AND pm.meta_key = '_elementor_data'
			AND (pm.meta_value LIKE '%\"elType\":\"widget\"%' AND pm.meta_value LIKE '%\"form_name\"%')
    	";
		$count = $wpdb->get_var($wpdb->prepare($query, array($user_id))); // phpcs:ignore

		return $count;
	}

	/**
	 * Get number of Forms
	 *
	 * @return int
	 */
	public function mumber_of_items() {
		global $wpdb;

		$query = "
			SELECT COUNT(p.ID)
			FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
			WHERE p.post_type = 'page'
			AND pm.meta_key = '_elementor_data'
			AND (pm.meta_value LIKE '%\"elType\":\"widget\"%' AND pm.meta_value LIKE '%\"form_name\"%')
		";

		$count = $wpdb->get_var( $query ); // phpcs:ignore

		return $count;
	}
}
