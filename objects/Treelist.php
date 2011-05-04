<?php

class emol_Level2Listboxes {
	
	//array of lists
	var $lists = '';
	
	//base url
	var $baseUrl = '';
	
	//side
	var $side = '';
	
	/* default constructor */
	function emol_Level2Listboxes($treeArray,$side=null) {
		
		if($side == null){
			$this->side = get_option( 'emol_job_search_url' );
		} else {
			$this->side = $side;
		}
        
        //current units
        $currentCompetences = array();
		$currentparts = explode('/',$_SERVER['REQUEST_URI']);
        foreach($currentparts as $subPart) {
            if( strstr($subPart,'competence') ){
                $otherparts = explode(',',$subPart);
                foreach($otherparts as $another){
                    if( is_numeric($another) )
                    $currentCompetences[] =$another; 
                }
            }
        }
        
		$this->baseUrl = $side.'/';
	
        $return = '';
		foreach($treeArray as $rootTree){
			foreach($rootTree['children'] as $item){
                //listbox
                $return .= '<h3>'.$item['name'].'</h3>';
                $return .= '<select id="'.$item['id'].'" class="emol-search-competence">';
                $return .= '<option value="">&nbsp;</option>';
                
				foreach($item['children'] as $it){
					$sel = '';
                    if( in_array( $it['id'] , $currentCompetences ) ){
                        $sel = 'selected="selected"';
                    }
                    $return .= '<option value="'.$it['id'].'" '.$sel.'>'.$it['name'].'</option>';
                    
			    }
                $return .= '</select>';
		    }
	    }
        $return .= '<hr class="emol-hr"><div align="right"><button class="emol-search-button" onclick="emolSearch(\'' . $this->side . '\');">'.EMOL_WIDGET_SEARCH.'</button></div>';
        $this->lists = $return;
    }
}
