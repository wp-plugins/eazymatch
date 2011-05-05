<?php
/**
* Container for job view
*/
class EmolJobPage
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
	var $page_title = 'Jobpage';
	
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
	* When initialized this will be the handled job
	* 
	* @var mixed
	*/
	var $job;
	
	/**
	* When initialized this will be the handled job texts
	* 
	* @var mixed
	*/
	var $jobTexts;
	
    /**
    * When initialized this will be the handled job competences
    * 
    * @var mixed
    */
    var $jobCompetences;
    

	/**
	 * Class constructor
	 */
	function EmolJobPage($slug,$function='')
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
		$jobId = $urlVars[1];
		
		//fetch the job
		$this->emolApi   = $this->emolApi->get('job');

		$trunk = new EazyTrunk();
			
		// create a response array and add all the requests to the trunk
		$this->job              = &$trunk->request( 'job', 'getFullPublished', array($jobId) );
		$this->jobTexts         = &$trunk->request( 'job', 'getCustomTexts', array($jobId) );
        $this->jobCompetences   = &$trunk->request( 'job', 'getPublishedJobCompetence', array($jobId) );
		
		// execute the trunk request
		$trunk->execute();
		
        if( ! isset($this->job['name']) ) { $this->job['name'] = 'unknown'; } 
		//set the page variables	
		$this->page_title = EMOL_JOB_NAME.' '.$this->job['name'];
        
		
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
	
	function getContent(){
		//$jobHtml = '<h2 class="emol-job-heading">'.$this->job['name'].'</h2>';
        if( ! isset( $this->job['id'] )  ){
            return 'Not Found';
        }
        
        //shortcode
        if(! isset($this->job['shortcode']) ) { $this->job['shortcode']='';} 
		
        $jobHtml = '<div id="emol-job-container">';
		

		$jobHtml .= '<div id="emol-job-body">';
		$jobHtml .= '<table>';
		
		$img = '';
		if( isset($this->job['Company']['Logo']) && $this->job['Company']['Logo']['content'] > '' && get_option('emol_job_search_logo') == 1){
			$img = '<div class="emol-job-picture"><img src="data:image/png;base64,'.$this->job['Company']['Logo']['content'].'" /></div>';
		}
		
        
        //code of job
        $jobHtml .= '<tr><td class="emol-job-body-col1">'.EMOL_JOB_CODE.'</td>';
        $jobHtml .= '<td class="emol-job-body-col2">'.$this->job['shortcode'].'</td></tr>';
            
		if($img != ''){
			$jobHtml .= '<tr><td class="emol-job-body-col1">'.EMOL_JOB_PICTURE.'</td>';
            $jobHtml .= '<td class="emol-job-body-col2">'.$img.'</td></tr>';
            
		}
        
        
        if(isset($this->job['Address']['Region']['name']) && $this->job['Address']['Region']['name'] != ''){
            $addRegion='';
            
            if(isset($this->job['Address']['Region']['name'])){
                $addRegion = ''.$this->job['Address']['Region']['name'].'';
            }
		    $jobHtml .= '<tr><td class="emol-job-body-col1">'.EMOL_JOB_PLACE.'</td>';
            //$jobHtml .= '<td>'.$this->job['Address']['city'].' '.$addRegion.'</td></tr>';
		    $jobHtml .= '<td>'.$addRegion.'</td></tr>';
        }
		$jdate = '';
		if( isset( $this->job['created'] ) ){
			$jdate = date('d-m-Y',strtotime($this->job['created']));
		}
		$jobHtml .= '<tr><td class="emol-job-body-col1">'.EMOL_JOB_DATE.'</td>';
		$jobHtml .= '<td>'.$jdate.'</td></tr>';
		
        if( isset($this->job['description']) ){
		    $jobHtml .= '<tr><td class="emol-job-body-col1">'.EMOL_JOB_DESCRIPTION.'</td>';
		    $jobHtml .= '<td>'.$this->job['description'].'</td></tr>';
        }
		$jobHtml .= '</table></div>';
		
		$jobHtml .= '<div class="emol-job-apply"><a href="'.get_bloginfo( 'wpurl').'/'.get_option('emol_apply_url').'/'.$this->job['id'].'/'.eazymatch_friendly_seo_string($this->job['name']).'/" class="emol-apply-button">'.EMOL_JOB_APPLY.'</a></div>';
		/**
		* Text blocks
		* 
		* @var mixed
		*/

		//$cust = $this->emolApi->getCustomTexts($this->job['id']);
		$cust = $this->jobTexts;
        if( is_array($cust) &&  count($cust) > 0 ){
		    foreach($cust as $custom){
			    if(strlen($custom['value']) == 0) continue;
			    $jobHtml .= '<h2 class="emol-job-heading">'.$custom['title'].'</h3>';
			    $jobHtml .= '<p class="emol-job-paragraph">'.nl2br($custom['value']).'</p>';
		    }
        }
		
		/**
		* Competences
		* 
		* @var mixed
		*/
        
        $competences = $this->jobCompetences;
		//var_dump( $competences );
        //if we have any competences show em
        if(is_array($competences) && count($competences) > 0 && $competences[0] != false) { 
		    $i=0;
		    $jobHtml .= '<div class="emol-job-competences">';
		    $jobHtml .= '<h2 class="emol-job-heading">'.EMOL_JOB_COMPETENCES.'</h2>';
		    $cHtml 	= '';
		    //$competences = $this->emolApi->getCompetence($this->job['id'],2);
		    
		    foreach($competences as $comp){
			    $i++;
			    if($comp['level'] == 1 && count($comp['JobCompetence']) > 0){
				    if($i != 1){
					    $cHtml .= '</ul>';
				    }
				    $cHtml .= '<h4>'.$comp['name'].'</h4><ul class="emol-job-block">';

			    } else if($comp['level'] > 1){ //JUST SHOW LEVEL 2 AND HIGHER
				    $cHtml .= '<li class="emol-level-'.$comp['level'].'">'.$comp['name'].'</li>';
			    }
		    }
		    
		    //close ul if it has not been closed up here
		    if(substr($cHtml,-5,5) != '</ul>'){
			    $jobHtml .= $cHtml.'</ul>';
		    } else {
			    $jobHtml .= $cHtml;
		    }
		    
		    //finalize the html
		    $jobHtml .= '</div>'; //emol-job-competences
        }
        
		$jobHtml .= '<div class="emol-job-apply"><a href="'.get_bloginfo( 'wpurl').'/'.get_option('emol_apply_url').'/'.$this->job['id'].'/'.eazymatch_friendly_seo_string($this->job['name']).'/" class="emol-apply-button">'.EMOL_JOB_APPLY.'</a></div>';
        
		$jobHtml .= '</div>'; //job-container
        
                /**share this code **/
        $jobHtml .= '<!-- AddThis Button BEGIN -->
<div class="addthis_toolbox addthis_default_style">
<a class="addthis_button_preferred_1"></a><a class="addthis_button_preferred_2"></a><a class="addthis_button_preferred_3"></a><a class="addthis_button_preferred_4"></a><a class="addthis_button_compact"></a><a class="addthis_counter addthis_bubble_style"></a></div><script type="text/javascript">var addthis_config = {"data_track_clickback":true};</script><script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=ra-4d9de1cd75052c1e"></script>
<!-- AddThis Button END -->';
		
		return $jobHtml;
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