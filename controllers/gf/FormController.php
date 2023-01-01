<?php

namespace Controllers\GF;

use Models\GF\FormModel;
use Plugins\Helpers\Pagination;
use WP_Error;

class FormController
{   
    private $formModel;
    private $number_of_records_per_page;
    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->paginationHelper = new Pagination();
        $this->number_of_records_per_page = $this->paginationHelper->getNumberofRecordsPerPage();
    }

    /**
     * GF forms.
     *
     * @return array $forms GF forms.
     */
    public function forms()
    {   
        $count = $this->formModel->mumberItems();

        $offset = 0;

        $forms =  $this->formModel->forms($offset, $this->number_of_records_per_page);

        $forms_results =  $this->paginationHelper->prepareDataForRestWithPagination($count, $forms);
 
        return rest_ensure_response($forms_results);
    }

    /**
     * GF forms.
     *
     * @param WP_REST_Request $request The request.
     * 
     * @return array $forms GF forms.
     */
    public function formsPagination($request)
    {   
        $page = $request['page_number'];

        $forms_results = [];

        $offset  = ($page - 1) * $this->formModel->mumberItems();

        //$forms = \GFAPI::get_forms();
        $forms =  $this->formModel->forms($offset, $this->number_of_records_per_page);

        $info = [];
        $info["count"]  = $this->formModel->mumberItems();
        $info["pages"]  = ceil($info["count"]/$this->number_of_records_per_page);
        
        $forms_results["info"] = $info;
        $forms_results["results"] = $forms;
 
        return rest_ensure_response($forms_results);
    }


    /**
     * GF forms.
     *
     * @param WP_REST_Request $request The request.
     * 
     * @return array $forms GF forms.
     */
    public function searchForms($request)
    {   
        $post_name = urldecode($request['post_name']);

        $forms_results = [];
        
        $offset = 0;

        $forms =  $this->formModel->searchForms($post_name, $offset, $this->number_of_records_per_page);

        $info = [];
        $info["count"]  = $this->formModel->mumberItemsOnSerach($post_name);
        $info["pages"]  = ceil($info["count"]/$this->number_of_records_per_page);
        
        $forms_results["info"] = $info;
        $forms_results["results"] = $forms;
 
        return rest_ensure_response($forms_results);

    }


    
}
