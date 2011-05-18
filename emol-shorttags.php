<?php
if(!isset($emol_db_version)) { exit(); }

/**
* Get published joblist
* 
*/
function eazymatch_get_jobs(){
    global $emol_side;
    $emol_side='applicant';
    
    $api  = eazymatch_connect();

    if( $api ){
        $jobs = array();

        $limit = 5;
        if(is_numeric(get_option('emol_job_amount_pp')) && get_option('emol_job_amount_pp') > 0)
            $limit = get_option('emol_job_amount_pp');

        try {
            $filterOptions = unserialize(get_option('emol_filter_options'));
          
            $wsJob  = $api->get('job');
            $jobs 	= $wsJob->getPublished($limit,$filterOptions);
           // var_dump($jobs);
        } catch (SoapFault $e){
            eazymatch_trow_error('Fout in SOAP request EazyMatch -> jobs');
            echo "<pre>";
            print_r($e);
        }

        $text = '<hr>';

        //navigation
        $total = count($jobs);

        //get a option
        $picVisible     = get_option('emol_job_search_logo');
        $descVisible    = get_option('emol_job_search_desc');
        $regioVisible   = get_option('emol_job_search_region');

        $i=0;
        if($total > 0){
            $text  ='';
            foreach($jobs as $job){

                $i++;
                if($i > $limit){
                    break;
                }
                $img = '';
                if($job['Company']['Logo']['content'] > '' && $picVisible == 1){
                    $img = '<div class="emol-job-result-logo"><img src="data:image/png;base64,'.$job['Company']['Logo']['content'].'" /></div>';
                } elseif ($picVisible == 1){
                    $img = '<div class="emol-job-result-logo"><img src="'. get_bloginfo( 'wpurl') .'/wp-content/plugins/eazymatch/icon/blank-icon.png" alt="" /></div>';
                }

                $job_url 	= '/'.get_option( 'emol_job_url' ).'/'.$job['id'].'/'.eazymatch_friendly_seo_string($job['name']).'/';
                $apply_url 	= '/'.get_option( 'emol_apply_url' ).'/'.$job['id'].'/'.eazymatch_friendly_seo_string($job['name']).'/';
                $text .= $img;
                $text .= '<div class="eazymatch_job_title"><a href="'.$job_url.'">'.$job['name'].'</a></div>';
                if($descVisible == 1) $text .= '<div class="eazymatch_job_body">'.$job['description'].'</div>';
                if($regioVisible == 1 && isset($job['Address']['Region'])) $text .= '<div class="eazymatch_job_region">'.$job['Address']['Region']['name'].'</div>';
                $text .= '<div class="eazymatch_job_toolbar"><a href="'.$apply_url.'">'.EMOL_JOBSEARCH_APPLY.'</a> | <a href="'.$job_url.'">'.EMOL_SEARCH_READMORE.'</a></div>';
                $text .= '<div class="eazymatch_result_seperator"></div>';
            }
            $text .= '<div class="emol-pagnation-readmore"><a href="/'.get_option( 'emol_job_search_url' ).'/all/">'.EMOL_JOBSEARCH_MORE.'</a></div>';

        } else {
            $text .= '<div class="emol-no-results">'.get_option( 'emol_job_no_result' ).'</div>';
        }

        return ($text);

    } else {
        unset($_SESSION['emol']);
        eazymatch_trow_error('Geen connectie met EazyMatch -> stel eerst een verbinding in via het CMS');
    }

}

/**
* Get published cvlist
* 
*/
function eazymatch_get_cv(){
    global $emol_side;
    $emol_side='company';

    $api  = eazymatch_connect();

    if( $api ){
        $cvs = array();

        $limit = 5;
        if(is_numeric(get_option('emol_cv_amount_pp')) && get_option('emol_cv_amount_pp') > 0)
            $limit = get_option('emol_cv_amount_pp');

        try{
            $wsCV  = $api->get('applicant');
            $cvs 	= $wsCV->getPublished($limit);
        } catch (SoapFault $e){
            eazymatch_trow_error('Fout in SOAP request EazyMatch -> cv');
            echo "<pre>";
            print_r($e);
        }

        $text = '';

        //navigation
        $total = count($cvs);

        //get a option
        $picVisible = get_option('emol_cv_search_picture');
        $descVisible = get_option('emol_cv_search_desc');

        //$wsPers = $api->get('person');
        $i=0;
        if($total > 0){

            foreach($cvs as $cv){
                $i++;
                if($i > $limit){
                    break;
                }
                $img = '';
                if( isset( $cv['Person']['Picture'] ) &&  $cv['Person']['Picture']['content'] != ''  && $picVisible == 1){
                    $img = '<div class="emol-cv-result-picture"><img src="data:image/png;base64,'.$cv['Person']['Picture']['content'].'" /></div>';
                } elseif ($picVisible == 1){
                    $img = '<div class="emol-cv-result-picture"><img src="'. get_bloginfo( 'wpurl') .'/wp-content/plugins/eazymatch/icon/blank-icon.png" alt="" /></div>';
                }

                $cv_url 	= '/'.get_option( 'emol_cv_url' ).'/'.$cv['id'].'/'.eazymatch_friendly_seo_string($cv['title']).'/';
                $react_url 	= '/'.get_option( 'emol_react_url_cv' ).'/'.$cv['id'].'/'.eazymatch_friendly_seo_string($cv['title']).'/';
                
                /**image*/
                $text .= $img;
                
                /**title*/
                $text .= '<div class="eazymatch_cv_title"><a href="'.$cv_url.'">('.$cv['id'].') '.$cv['title'].'</a>';
                
                /**prefered address*/
                if( isset($cv['Person']['Preferedaddress']['city']) ) 
                    $text .= '<div class="eazymatch_cv_city"><a href="'.$cv_url.'">'.strtoupper($cv['Person']['Preferedaddress']['city']).'</a></div></div>';
                
                /**is the body of CV visible*/
                if($descVisible == 1) $text .= '<div class="eazymatch_cv_body">'.$cv['description'].'</div>';
                
                /**toolbar*/
                $text .= '<div class="eazymatch_cv_toolbar"><a href="'.$cv_url.'">'.EMOL_SEARCH_READMORE.'</a> | <a href="'.$react_url.'">'.EMOL_CVSEARCH_APPLY.'</a> </div>';
                
                /**seperator of results*/
                $text .= '<div class="eazymatch_result_seperator"></div>';
            }
            $text .= '<div class="emol-pagnation-readmore"><a href="/'.get_option( 'emol_cv_search_url' ).'/all/">'.EMOL_CVSEARCH_MORE.'</a></div>';
        } else {
            $text .= '<div class="emol-no-results">'.get_option( 'emol_cv_no_result' ).'</div>';
        }


        return $text;

    } else {
        unset($_SESSION['emol']);
        eazymatch_trow_error('Geen connectie met EazyMatch -> stel eerst een verbinding in via het CMS');
    }

}


/**
* Include the shortscriptfunctions for eazymatch
* 
* enables:
* [eazymatch view="jobs"]
* [eazymatch view="cv"]
* 
* in contentpages
*/
function eazymatch_short_tags($atts) {
    extract(shortcode_atts(array(
    'view' 		=> ''
    ), $atts));

    $return = '';

    switch($view) {

        case 'jobs' :
            $return = eazymatch_get_jobs();
            break;
        case 'cv' :
            $return = eazymatch_get_cv();
            break;	
    }
    
    if ( !empty( $return ) ){
	    // make sure the basic style/scripts are included
	    emol_require::all();
    }
    
    return $return;
}
//add shortcodes
add_shortcode( 'eazymatch', 'eazymatch_short_tags');
