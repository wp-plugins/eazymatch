<?php
/**
* Container for cv view
*/
class EmolCvPage
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
	var $page_title = 'EmolCvPage';
	
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
	* When initialized this will be the handled cv
	* 
	* @var mixed
	*/
	var $cv;
	
	/**
	* When initialized this will be the handled cv competences
	* 
	* @var mixed
	*/
	var $cvCompetences;
		
	/**
	 * Class constructor
	 */
	function EmolCvPage($slug,$function='')
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
		$cvId = $urlVars[1];
		
		//fetch the cv
		$trunk = new EazyTrunk();
			
		// create a response array and add all the requests to the trunk
		$this->cv = &$trunk->request('applicant', 'getSummary', array( $cvId ));
		$this->cvCompetences = &$trunk->request('applicant', 'getCompetence', array( $cvId ));
		
		// execute the trunk request
		$trunk->execute();
		
		
		//set the page variables	
		$this->page_title = EMOL_CV_NAME.' - '.$this->cv['title'];
		
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
	
    /**
    * gets the content requested
    * 
    * no params expected
    */
	function getContent()
	{
		
		$img = '';
		if( isset($this->cv['Person']['Picture']) && $this->cv['Person']['Picture']['content'] > '' && get_option('emol_cv_search_picture') == 1){
			$img = '<div class="emol-cv-result-picture"><img src="data:image/png;base64,'.$this->cv['Person']['Picture']['content'].'" /></div>';
		}
		
		//emol_debug($this->cv);
		//$jobHtml = '<h2 class="emol-job-heading">'.$this->job['name'].'</h2>';
		$cvHtml = '<div id="emol-cv-container">';
		$cvHtml .= '<span class="emol-cv-slogan">'.$this->cv['Person']['shortcode'].'</span>';
		$cvHtml .= '<div id="emol-cv-body">';
		$cvHtml .= '<table>';
		if($img != ''){
			$cvHtml .= '<tr><td class="emol-cv-body-col1">'.EMOL_CV_PICTURE.'</td>';
			$cvHtml .= '<td class="emol-cv-body-col2">'.$img.'</td></tr>';
		}
        
        if( isset($this->cv['Person']['Preferedaddress']) ){
		    $cvHtml .= '<tr><td>'.EMOL_CV_PLACE.'</td>';
		    $cvHtml .= '<td>'.$this->cv['Person']['Preferedaddress']['city'].'</td></tr>';
		}
        
		$gender = EMOL_FEMALE;
		if($this->cv['Person']['gender'] == 'm'){
			$gender = EMOL_MALE;
		}
		$cvHtml .= '<tr><td>'.EMOL_GENDER.'</td>';
		$cvHtml .= '<td>'.$gender.'</td></tr>';
		
		$bdate = '-';
		if($this->cv['Person']['birthdate'] > ''){
			$bdate = date('d-m-Y',strtotime($this->cv['Person']['birthdate']));
		}
		$cvHtml .= '<tr><td>'.EMOL_CV_BIRTHDATE.'</td>';
		$cvHtml .= '<td>'.$bdate.'</td></tr>';
		
		$bpdate = EMOL_CV_DIRECT;
		if($this->cv['availablefrom']){
			$bpdate = date('d-m-Y',strtotime($this->cv['availablefrom']));
		}
		$cvHtml .= '<tr><td>'.EMOL_CV_AVAILABLEFROM.'</td>';
		$cvHtml .= '<td>'.$bpdate.'</td></tr>';
		
		$cvHtml .= '<tr><td>'.EMOL_CV_DESCRIPTION.'</td>';
		$cvHtml .= '<td>'.$this->cv['description'].'</td></tr>';
		
		//yes C html
		$cHtml = '';

		/**
		* Add Competences
		*/
		//$competences = $this->emolApi->getCompetence($this->cv['id']);
		$competences = $this->cvCompetences;
		if(is_array($competences) && count($competences) > 0){
			$i = 0;
			
			
			foreach($competences as $comp){
				
				$i++;
				if($comp['level'] == 2){
					if($i != 1){
						$cHtml .= '</ul>';
					}
					$cHtml .= '<h4>'.$comp['name'].'</h4><ul class="emol-job-block">';
					continue;
				} else if($comp['level'] > 2){ //JUST SHOW LEVEL 2 AND HIGHER
					$cHtml .= '<li class="emol-level-'.$comp['level'].'">'.$comp['name'].'</li>';
				}
			}
		}
		if(substr($cHtml,-5,5) != '</ul>'){
			$cHtml .= '</ul>';
		} 
		
		$cvHtml .= '<tr><td valign=top>'.EMOL_CV_MATCHPROFILE.'</td>';
		$cvHtml .= '<td>'.$cHtml.'</td></tr>';
		
		
		$cvHtml .= '</table>';
		$cvHtml .= '</div>';
		//finalize the html
		$cvHtml .= '
		<div class="emol-job-apply">
			<a href="'.get_bloginfo( 'wpurl').'/'.get_option('emol_react_url_cv').'/'.$this->cv['id'].'/'.eazymatch_friendly_seo_string($this->cv['title']).'/" class="emol-apply-button">'.EMOL_CVSEARCH_APPLY.'</a>
		</div>';
		$cvHtml .= '</div>'; //job-container
		
		return $cvHtml;
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