<?php

namespace Controllers\WPF;

use Models\WPF\EntryMetaModel;

class EntryMetaController
{   
    private $entryMetaModel;
    public function __construct()
    {
        $this->entryMetaModel= new EntryMetaModel();
         
    }

    /**
     * WPF forms entry.
     *
     * @param WP_REST_Request $request The request.
     * 
     * @return array $forms WPF forms.
     */
    public function entryMetaByEntryID($request)
    {   
        $entry_id = $request['entry_id'];

        $items = $this->entryMetaModel->entryMetaByEntryID($entry_id);
       
        return rest_ensure_response($items);
    }

    /**
     * WPF forms entry.
     *
     * @param WP_REST_Request $request The request.
     * 
     * @return array $forms WPF forms.
     */
    public function searchEntryMetaAnswer($request)
    {   
        $answer = urldecode($request['answer']);

        $items = $this->entryMetaModel->searchEntryMetaAnswer($answer, 0, $this->number_of_records_per_page);
      
        return rest_ensure_response($items);
    }
    
}
