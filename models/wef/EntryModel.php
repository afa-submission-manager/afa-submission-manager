<?php

namespace Models\WEF;

use Models\UserModel;
use Models\WEF\FormModel;
class EntryModel
{   
    public const DATABASE_NAME = "weforms_entries";

    public function __construct()
    {}

    /**
	 * Get Forms entries
     * 
     * @return array
	 */
    public function entries($offset, $number_of_records_per_page)
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.SELF::DATABASE_NAME." ORDER BY id DESC LIMIT ".$offset.",".$number_of_records_per_page,OBJECT);
        
        $entries = [];

        foreach($results as $key => $value){
            
            $entry = [];

            $entry['id'] = $value->id;
            $entry['form_id'] = $value->form_id;
            $entry['date_created'] = $value->created_at;
            $entry['created_by'] = $value->user_id;
            $entry['author_info'] = [];

            if(!empty($value->user_id)){
                $user_model = new UserModel();
                $entry['author_info'] = $user_model->userInfoByID($value->user_id);
            }

            $form_model = new FormModel();
            $entry['form_info'] = $form_model->formByID($value->form_id);

            $entries[] =  $entry;
        }

        return $entries;
    }


    /**
	 * Get Forms 
     * 
     * @return int
	 */
    public function mumberItems()
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT count(*)  as number_of_rows FROM ".$wpdb->prefix.SELF::DATABASE_NAME."");
        $number_of_rows = intval( $results[0]->number_of_rows );
        return $number_of_rows ;  
    }

    /**
	 * Get Forms 
     * 
     * @return int
	 */
    public function mumberItemsByFormID($form_id)
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT count(*)  as number_of_rows FROM ".$wpdb->prefix.SELF::DATABASE_NAME." WHERE form_id = $form_id ");
        $number_of_rows = intval( $results[0]->number_of_rows );
        return $number_of_rows ;  
    }
}