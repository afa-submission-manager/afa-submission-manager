<?php
/**
 * The Form Model Class.
 *
 * @package  WP_All_Forms_API
 * @since 1.0.0
 */

namespace Includes\Models\CF7;

use Includes\Models\CF7\EntryModel;
use Includes\Plugins\Helpers\FormModelHelper;
use Includes\Models\AbstractFormModel;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
/**
 * Class AbstractFormModel
 *
 * Create model functions
 *
 * @since 1.0.0
 */
class FormModel extends AbstractFormModel {

	/**
	 * Const to declare shortcode.
	 */
	const SHORTCODE = 'contact-form-7';

	/**
	 * The FormModelHelper
	 *
	 * @var FormModelHelper
	 */
	public $form_model_helper;

	/**
	 * Form model constructor
	 */
	public function __construct() {
		$this->form_model_helper = new FormModelHelper( 'wpcf7_contact_form' );
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
		$posts = $this->form_model_helper->forms( $offset, $number_of_records_per_page );

		$forms = $this->prepare_data( $posts );

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
		$results = $this->form_model_helper->form_by_id( $id );

		$forms = $this->prepare_data_array( $results );

		if ( count( $forms ) > 0 ) {
			return $forms[0];
		}

		return $forms;
	}

	/**
	 * Get Forms
	 *
	 * @param string $post_name The post name.
	 * @param int    $offset The offset.
	 * @param int    $number_of_records_per_page The posts per page.
	 *
	 * @return array
	 */
	public function search_forms( $post_name, $offset, $number_of_records_per_page ) {
		$posts = $this->form_model_helper->search_forms( $post_name, $offset, $number_of_records_per_page );

		$forms = $this->prepare_data( $posts );

		return $forms;
	}

	/**
	 * Format Forms
	 *
	 * @param object $posts The forms.
	 *
	 * @return array
	 */
	public function prepare_data( $posts ) {
		$forms = array();

		while ( $posts->have_posts() ) {

			$posts->the_post();

			$form['id']           = $posts->post->ID;
			$form['title']        = $posts->post->post_title;
			$form['date_created'] = $posts->post->post_date;
			$form['registers']    = ( new EntryModel() )->mumberItemsByChannel( $posts->post->post_name );

			$form['user_created'] = $posts->post->post_author;
			$form['perma_links']  = parent::pages_links( $posts->post->ID, self::SHORTCODE );
			$forms[]              = $form;
		}

		return $forms;
	}

	/**
	 * Format Forms for array from sql
	 *
	 * @param array $results The forms.
	 *
	 * @return array
	 */
	private function prepare_data_array( $results ) {
		$forms = array();

		foreach ( $results as $key => $value ) {

			$form = array();

			$form['id']           = $value->ID;
			$form['title']        = $value->post_title;
			$form['date_created'] = $value->post_date;

			$form['registers'] = ( new EntryModel() )->mumberItemsByChannel( $value->post_name );

			$form['user_created'] = $value->post_author;
			$form['perma_links']  = parent::pages_links( $value->ID, self::SHORTCODE );

			$forms[] = $form;
		}

		return $forms;

	}
}
