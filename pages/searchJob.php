<?php
/**
* Container for job view
*/
class EmolJobSearchPage
{
    /**
    * The slug for the fake post.  This is the URL for your plugin, like:
    * http://site.com/about-me or http://site.com/?page_id=about-me
    * @var string
    */
    var $page_slug = '';

    /**
    * The title for your fake post.
    * @var string
    */
    var $page_title = 'JobSearch';

    /**
    * Allow pings?
    * @var string
    */
    var $ping_status = 'open';

    /**
    * Function to be executed in eazymatch
    * 
    * @var mixed
    */
    var $emol_function = '';

    /**
    * EazyMatch 3.0 Api
    * 
    * @var mixed
    */
    var $emolApi;


    /**
    * allowed tags to be filtered on
    * 
    * @var mixed
    */
    var $searchCriteria = array(
    'competence',
    'free'
    );

    /**
    * Class constructor
    */
    function EmolJobSearchPage($slug,$function='')
    {

        $this->page_slug = $slug.'/'.$function;
        $this->emol_function = $function;

        //first connect to the api
        $this->emolApi  = eazymatch_connect();
        if( ! $this->emolApi ){
            eazymatch_trow_error();
        }

        //split up the variables given
        $urlVars = explode('/',$this->page_slug);

        //set the page variables	
        $this->page_title = get_option('emol_job_header');

        /**
        * We'll wait til WordPress has looked for posts, and then
        * check to see if the requested url matches our target.
        */
        add_filter('the_posts',array(&$this,'detectPost'));
    }


    /**
    * Called by the 'detectPost' action
    */
    function createPost()
    {

        /**
        * What we are going to do here, is create a fake post.  A post
        * that doesn't actually exist. We're gonna fill it up with
        * whatever values you want.  The content of the post will be
        * the output from your plugin.
        */		 

        /**
        * Create a fake post.
        */
        $post = new stdClass;
        $post->post_type = '';
        $post->post_parent = '';

        /**
        * The author ID for the post.  Usually 1 is the sys admin.  Your
        * plugin can find out the real author ID without any trouble.
        */
        $post->post_author = 1;

        /**
        * The safe name for the post.  This is the post slug.
        */
        $post->post_name = $this->page_slug;

        /**
        * Not sure if this is even important.  But gonna fill it up anyway.
        */
        $post->guid = get_bloginfo('wpurl') . '/' . $this->page_slug;


        /**
        * The title of the page.
        */
        $post->post_title = $this->page_title;

        /**
        * This is the content of the post.  This is where the output of
        * your plugin should go.  Just store the output from all your
        * plugin function calls, and put the output into this var.
        */
        $post->post_content = $this->getContent();

        /**
        * Fake post ID to prevent WP from trying to show comments for
        * a post that doesn't really exist.
        */
        $post->ID = -1;

        /**
        * Static means a page, not a post.
        */
        $post->post_status = 'static';

        /**
        * Turning off comments for the post.
        */
        $post->comment_status = 'closed';

        /**
        * Let people ping the post?  Probably doesn't matter since
        * comments are turned off, so not sure if WP would even
        * show the pings.
        */
        $post->ping_status = $this->ping_status;

        $post->comment_count = 0;

        /**
        * You can pretty much fill these up with anything you want.  The
        * current date is fine.  It's a fake post right?  Maybe the date
        * the plugin was activated?
        */
        $post->post_date = current_time('mysql');
        $post->post_date_gmt = current_time('mysql', 1);

        return($post);		
    }

    function getContent()
    {
        $searchHtml = '';
        //get job controller
        $ws   = $this->emolApi->get('job');

        $searchAction = explode(',',$this->emol_function);

        //create an array with searchcriteria that is understandable
        $search = array();
        $keyValue = '';
        foreach($searchAction as $searchValue){
            if( ! in_array($searchValue,$this->searchCriteria) && $keyValue > ''){
                //value
                $search[$keyValue][] = $searchValue;
            } elseif(in_array($searchValue,$this->searchCriteria)) {
                //criteria
                $search[$searchValue] = array();
                $keyValue = $searchValue;
            }
        }

        //status
        $filterOptions = unserialize(get_option('emol_filter_options'));
        if(count($filterOptions) > 0){
            $search['status'] = $filterOptions;
        }
        
        //search criteria
        if(isset($search['free'])){
            emol_session::set( 'freeSearch', $search['free'][0] );
        } else {
            emol_session::remove( 'freeSearch' );
        }

        if(count($search) == ''){
            $search = array();
        }


        //navigation
        $items_totaal = $ws->siteSearchCount($search);

        if( $items_totaal == 0 ){
            $searchHtml = '<div class="emol-no-results">'.get_option( 'emol_job_no_result' ).'</div>';
        } else {

            //max amount per page
            if(get_option('emol_job_amount_pp') > ''){
                $items_per_pagina = get_option('emol_job_amount_pp');
            } else {
                $items_per_pagina = 15;
            }
            $aantal_paginas = ceil($items_totaal / $items_per_pagina);


            //current page
            $huidige_pagina = 0;
            if( isset($_GET['page'] ) && is_numeric($_GET['page']) && $_GET['page'] > 0 && $_GET['page'] < $aantal_paginas ) {
                $huidige_pagina = $_GET['page'];	
            } 

            //determine offset
            $offset = $huidige_pagina * $items_per_pagina;

            //get the actual result with pagination
            $jobs = $ws->siteSearch($search,$offset,$items_per_pagina);

            $nav = '<table class="emol-pagination-table"><tr>';
            $nav .= '<td class="emol-pagnation"><a href="/'.$this->page_slug.'"> <<< </a></td>';
            if($aantal_paginas > 1)
            $nav .= '<td class="emol-pagnation"><a href="/'.$this->page_slug.'/?page='.($huidige_pagina-1).'"> << </a></td>';


            for($i = 0; $i < $aantal_paginas; $i++) {

                // if($i == 0 || $i == $aantal_paginas){
                //     continue;
                // }
                if($huidige_pagina == $i) {
                    // huidige pagina is niet klikbaar
                    $nav .= '<td class="emol-pagnation emol-pagnation-selected">'.($i+1).'</td>';
                } else {
                    // een andere pagina
                    if( 
                    ($i + 1 == $huidige_pagina) || 
                    ($i + 2 == $huidige_pagina) || 
                    ($i - 1 == $huidige_pagina) || 
                    ($i - 2 == $huidige_pagina)
                    ){
                        $nav .= '<td class="emol-pagnation"><a href="/'.$this->page_slug.'/?page='.$i.'">'.($i+1).'</a></td>';
                    }
                }

            }
            if($huidige_pagina + 1 != $aantal_paginas)
                $nav .= '<td class="emol-pagnation"><a href="/'.$this->page_slug.'/?page='.($huidige_pagina + 1).'">>></a></td>';

            $nav .= '<td class="emol-pagnation last"><a href="/'.$this->page_slug.'/?page='.($aantal_paginas-1).'"> >>> </a></span></td>';

            $nav .='</tr></table>';

            //get a option
            $picVisible     = get_option('emol_job_search_logo');
            $descVisible    = get_option('emol_job_search_desc');
            $regioVisible   = get_option('emol_job_search_region');
             
            //title of result
            $searchHtml .= '<div id="emol-search-result-header">'.EMOL_JOBSEARCH_TOTAL.' '.$items_totaal.', '.EMOL_PAGE.'<b>'.($huidige_pagina+1).'</b>/'.$aantal_paginas.'</div>';
            $searchHtml .= '<div class="emol-page-navigation">'.$nav.'</div>';

            foreach($jobs as $job){

                $img = '';
                if(isset($job['Company']['Logo']) && $job['Company']['Logo']['content'] > '' && $picVisible == 1){
                    $img = '<div class="emol-job-result-logo"><img src="data:image/png;base64,'.$job['Company']['Logo']['content'].'" /></div>';
                } elseif($picVisible == 1){
                    $img = '<div class="emol-job-result-logo"><img src="'. get_bloginfo( 'wpurl') .'/wp-content/plugins/eazymatch/icon/blank-icon.png" alt="" /></div>';
                }

                $job_url = '/'.get_option( 'emol_job_url' ).'/'.$job['id'].'/'.eazymatch_friendly_seo_string($job['name']).'/';
                $apply_url 	= '/'.get_option( 'emol_apply_url' ).'/'.$job['id'].'/'.eazymatch_friendly_seo_string($job['name']).'/';

                $searchHtml .= $img;
                $searchHtml .= '<div class="eazymatch_job_title"><a href="'.$job_url.'">'.htmlspecialchars_decode($job['name']).'</a></div>';
                if($descVisible == 1) $searchHtml .= '<div class="emol-overview-text">'.($job['description']).'</div>';
                if($regioVisible == 1 && isset($job['Address']['Region'])) $searchHtml .= '<div class="eazymatch_job_region">'.$job['Address']['Region']['name'].'</div>';
                $searchHtml .= '<div class="eazymatch_job_toolbar"><a href="'.$job_url.'">'.EMOL_SEARCH_READMORE.'</a> | <a href="'.$apply_url.'">'.EMOL_JOBSEARCH_APPLY.'</a> </div>';
                $searchHtml .= '<div class="eazymatch_result_seperator"></div>';

            }
            $searchHtml .= '<div class="emol-page-navigation">'.$nav.'</div>';
        }
        return ($searchHtml);
    }

    function detectPost($posts){
        global $wp;
        global $wp_query;
        /**
        * Check if the requested page matches our target 
        */
        if (strtolower($wp->request) == strtolower($this->page_slug) || $wp->query_vars['page_id'] == $this->page_slug){
            //Add the fake post
            $posts=NULL;
            $posts[]=$this->createPost();

            /**
            * Trick wp_query into thinking this is a page (necessary for wp_title() at least)
            * Not sure if it's cheating or not to modify global variables in a filter 
            * but it appears to work and the codex doesn't directly say not to.
            */
            $wp_query->is_page = true;
            //Not sure if this one is necessary but might as well set it like a true page
            $wp_query->is_singular = true;
            $wp_query->is_home = false;
            $wp_query->is_archive = false;
            $wp_query->is_category = false;
            //Longer permalink structures may not match the fake post slug and cause a 404 error so we catch the error here
            unset($wp_query->query["error"]);
            $wp_query->query_vars["error"]="";
            $wp_query->is_404=false;

        }
        return $posts;
    }
}