<?php
/**
* Applying to a job or open or whatever
*/
class EmolApplyPage
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
    var $page_title = 'Applypage';

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
    var $jobApi;
    var $competenceApi;

    /**
    * When initialized this will be the handled job
    * 
    * @var mixed
    */
    var $job;
    var $competences;
    var $jobId = 0;

    /**
    * Class constructor
    */
    function EmolApplyPage($slug,$function=''){

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

        //get competences
        //$this->competenceApi    = $this->emolApi->get('competence');
        //$this->competences 		= $this->competenceApi->tree();

        if(is_numeric($jobId) && $jobId > 0){

            //initialize wsdls
            $this->jobApi   		= $this->emolApi->get('job');

            //get the job
            $this->job  	 		= $this->jobApi->get($jobId);
            $this->jobId 	 		= $this->job['id'];


            //set the page variables	
            $this->page_title = EMOL_APPLY.' "'.$this->job['name'].'"';
        } else {
            
            $this->jobId = 'open';
            $this->page_title = EMOL_JOB_APPLY_FREE;
        }
        /**
        * We'll wait til WordPress has looked for posts, and then
        * check to see if the requested url matches our target.
        */
        add_filter('the_posts',array(&$this,'detectPost'));
    }


    /**
    * Called by the 'detectPost' action
    */
    function createPost(){

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
        if( emol_post_exists( 'EMOL_apply' ) ){
            $this->doApply();
        } else {
            $post->post_content = $this->getContent();
        }

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
    * when someone has hit the button
    */
    function doApply(){

        //initiate webservice method
        $ws = $this->emolApi->get('applicant');

        if( ! emol_session::isValidId( 'applicant_id' ) ) {
            
            //create a array the way EazyMatch likes it
            $subscription = new emol_ApplicantMutation();

            //set the person
            $subscription->setPerson(
                null,
               emol_post( 'firstname' ),
               emol_post( 'middlename' ),
               emol_post( 'lastname' ),
               emol_post( 'birthdate' ),
               emol_post( 'gender' )
            );

            //set the Applicant
            $subscription->setApplicant(
                null,
                date('Ymd'),
                date('Ymd'),
                null,
               emol_post( 'title' ),
               emol_post( 'linkedInrequest' )
            );

            //set addresses
            foreach( $_POST['address'] as $addrPieceArr ){
            	$addrPiece = new emol_array( $addrPieceArr );
            	
                $subscription->addAddress(
	                null,
	                $addrPiece->province_id,
	                $addrPiece->country_id,
	                $addrPiece->region_id,
	                $addrPiece->street,
	                $addrPiece->housenumber,
	                $addrPiece->extension,
	                $addrPiece->zipcode,
	                $addrPiece->city
                );
            }

            /**email**/
            $subscription->addEmailaddresses(null,null, emol_post( 'email' ) );
            /**phonenumber**/
            $subscription->addPhonenumber(null,null, emol_post( 'phonenumber' ) );

            //CV
            if( isset($_FILES['cv']) && isset($_FILES['cv']['tmp_name']) && $_FILES['cv']['tmp_name'] != '' ){
                //set the CV document
                $doc = array();
                $doc['name'] = $_FILES['cv']['name'];
                $doc['content'] = base64_encode(file_get_contents($_FILES['cv']['tmp_name']));
                $doc['type'] = $_FILES['cv']['type'];

                $subscription->setCV($doc['name'], $doc['type'], $doc['content']);
            }

            //photo
            if( isset($_FILES['picture']) && isset($_FILES['picture']['tmp_name']) && $_FILES['picture']['tmp_name'] != ''){
                //set the CV document
                $doc = array();
                $doc['name'] = $_FILES['picture']['name'];
                $doc['content'] = base64_encode(file_get_contents($_FILES['picture']['tmp_name']));
                $doc['type'] = $_FILES['picture']['type'];


                $subscription->setPicture($doc['name'], $doc['type'], $doc['content']);
            }

            //competences
            if( emol_post_exists('competence') ){
                foreach( emol_post('competence') as $cpt ){
                    $subscription->addCompetence($cpt);
                }
            }

            //job / mediation / match
            if( emol_post('job_id') == '')
               emol_post_set( 'job_id', null );

            $url = $_SERVER['HTTP_HOST'];
            
            $subscription->setApplication(
           		emol_post( 'job_id' ),	emol_post( 'motivation'), $url
            );

            //create the workable postable array
            $postData = $subscription->createSubscription();

            /**save the subscription to EazyMatch, this will send an notification to emol user and an email to the subscriber**/
            $ws->subscription($postData);

            ob_clean();		
            wp_redirect( get_bloginfo('wpurl').'/'.get_option('emol_apply_url').'/'.$this->jobId .'/success/' );
            exit;
            
        } else {
            /**
            * apply to job, the true in the end is for triggering mail event
            * EazyMatch will create a mediation between the job and applicant with the motivation.
            * It also will register a correspondence moment and will send an e-mail to the emol user ( notification ) 
            **/
            
            $success = $ws->applyToJob( emol_post( 'job_id' ), emol_session::get( 'applicant_id' ), emol_post('motivation') , true );
            if($success == true){
                ob_clean();		
                wp_redirect( get_bloginfo('wpurl').'/'.get_option('emol_apply_url').'/'.$this->jobId .'/success/' );
                exit;
            } else {
                ob_clean();		
                wp_redirect( get_bloginfo('wpurl').'/'.get_option('emol_apply_url').'/'.$this->jobId .'/unsuccess/' );
                exit;
            }
        }
    }


    /**
    * creates the fake content
    */
    function getContent(){
    	// remove auto line breaks
		remove_filter('the_content', 'wpautop');
    	
    	// prepare client resources
        emol_require::validation();
        emol_require::jqueryUi();
        
        $linkedInrequest = (get_query_var('emolrequestid'));
        $titleCV = '';
        if(strlen($linkedInrequest) == 128){
            
            $appApi = $this->emolApi->get('applicant');
            $data = $appApi->getLinkedInProfile($linkedInrequest);
            $titleCV = $data['headline'];
            
        } else {
           
            $data['first-name'] = '';
            $data['last-name'] = '';
            $data['location']['name'] = '';
            $data['summary'] = '';
        }
        
        //the apply form
        $loginWidget = '';
        $loginWidget .= "<div id=\"emolLoginDialog\"  title=\"".EMOL_WIDGET_LOGIN."\">
        <div id=\"eazymatch_login_widget\">
        <form method=\"post\" action=\"/".get_option( 'emol_account_url' )."/login/\">";
        $loginWidget .= "<input type=\"hidden\" value=\"EMOL_LOGIN\" name=\"EMOL_LOGIN\">";
        $loginWidget .= "<input type=\"hidden\" value=\"".$_SERVER['REQUEST_URI']."\" name=\"emol_redirect_url\">";
        $loginWidget .= "<input type=\"text\" class=\"emol-text-input\" value=\"".EMOL_LOGIN_USER."\" onfocus=\"if(this.value == '".EMOL_LOGIN_USER."'){this.value='';}\" name=\"username\"><br>";
        $loginWidget .= "<input type=\"password\" class=\"emol-text-input\" value=\"".EMOL_LOGIN_PASS."\" onfocus=\"if(this.value == '".EMOL_LOGIN_PASS."'){this.value='';}\" name=\"password\"><br>";
        $loginWidget .= "<button type=\"submit\">".EMOL_MENU_LOGIN."</button>";
        $loginWidget .= "</form></div></div>";
        
        $applyHtml = $loginWidget.'
        <div id="emol-form-div">
        <form method="post" id="emol-apply-form" enctype="multipart/form-data">
        <input type="hidden" name="job_id" value="'.$this->jobId.'" />
        <input type="hidden" name="EMOL_apply" value="1" />
        <input type="hidden" name="linkedInrequest" value="'.$linkedInrequest.'" />
        <table class="emol-form-table">
        <tbody>';

        
        $urlVars = explode('/',$this->page_slug);
        
        
        //url applying
        $url = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

         
        if(isset($urlVars[2]) && $urlVars[2] == 'success'){
            $applyHtml .= '<tr><td colspan="2">'. nl2br(get_option( 'emol_apply_success' )) .'</td></tr>';
        } elseif(isset($urlVars[2]) && $urlVars[2] == 'unsuccess'){
            $applyHtml .= '<tr><td colspan="2">'. EMOL_APPLY_FAIL_MSG .'</td></tr>';
        } else {
            if( ! emol_session::isValidId( 'applicant_id' ) ){
                
                //make login widget
                $applyHtml .= '
                <tr>
                    <td class="emol-form-td" colspan="2">
	                    <img src="https://linkedin.eazymatch.net/default-login.png" class="emol-login-logo" onclick="jQuery(\'#emolLoginDialog\').dialog();" />
	                    <a href="https://linkedin.eazymatch.net/?refer='.$url.'&amp;instance='.$this->emolApi->instanceName.'">
	                    <img src="https://linkedin.eazymatch.net/connect-to-linkedin.png" class="emol-linkedin-logo" />
	                    </a><strong>'.$titleCV.'</strong>
                    </td>
                </tr>
                
                <tr>
                <td class="emol-form-td">
                	'.EMOL_GENDER.'
                </td>
                <td>
	                <input type="radio" class="emol-radio-input" name="gender" value="m" checked="checked" id="emol-gender-male" />  <label for="emol-gender-male">'.EMOL_MALE.'</label>
	                <input type="radio" class="emol-radio-input" name="gender" value="f" id="emol-gender-female" />  <label for="emol-gender-female">'.EMOL_FEMALE.'</label>
                </td>
                </tr>
                
                <tr>
	                <td>
	                	<label for="emol-firstname">'.EMOL_FIRSTNAME.'</label>
	                </td>
	                <td>
	                	<input type="text" class="emol-text-input" name="firstname" id="emol-firstname" value="'.$data['first-name'].'" />
	                </td>
                </tr>
                
                <tr>
	                <td>
                		<label for="emol-middlename">'.EMOL_MIDDLENAME.' &amp;</label> <label for="emol-lastname">'.EMOL_LASTNAME.'</label>
	                </td>
	                <td>
		                <input type="text" class="emol-text-input emol-small " name="middlename" id="emol-middlename" /> 
		                <input type="text" class="emol-text-input"  value="'.$data['last-name'].'" name="lastname" id="emol-lastname" />
	                </td>
                </tr>
                
                <tr>
	                <td>
	                	<label for="emol-address">'.EMOL_ADDRESS.' + </label>
	                	<label for="emol-housenumber">'.EMOL_HOUSENUMBER.'  + </label>
	                	<label for="emol-extension">'.EMOL_EXTENSION.'</label>
	                </td>
	                <td>
		                <input type="text" class="emol-text-input" id="emol-address" name="address[1][street]" /> 
		                <input type="text" class="emol-text-input emol-small validate[required,custom[onlyNumber],length[0,5]]" name="address[1][housenumber]" id="emol-housenumber" /> 
		                <input type="text" class="emol-text-input emol-small" name="address[1][extension]" id="emol-extension" />
	                </td>
                </tr>
                
                <tr>
                <td>
                <label for="emol-zipcode">'.EMOL_ZIPCODE.'</label>
                </td>
                <td>
                <input type="text" class="emol-text-input" name="address[1][zipcode]" id="emol-zipcode" />
                </td>
                </tr>
                <tr>
                <td>
                <label for="emol-city">'.EMOL_CITY.'</label>
                </td>
                <td>
                <input type="text" class="emol-text-input" value="'.$data['location']['name'].'"  name="address[1][city]" id="emol-city" />
                </td>
                </tr>
                <tr>
                <td>
                <label for="emol-phonenumber">'.EMOL_PHONE.'</label>
                </td>
                <td>
                <input type="text" class="emol-text-input" name="phonenumber" id="emol-phonenumber" />
                </td>
                </tr>
                <tr>
                <td>
                <label for="emol-email">'.EMOL_EMAIL.'</label>
                </td>
                <td>
                <input type="text" class="emol-text-input" name="email" id="emol-email" />
                </td>
                </tr>
                <tr>
                <td>
                <label for="emol-cv">'.EMOL_APPLY_CV.'</label>
                </td>
                <td>
                <input type="file" class="emol-text-input emol-file" name="cv" id="emol-cv" />
                </td>
                </tr>
                <tr>
                <td>
                <label for="emol-picture">'.EMOL_APPLY_PICTURE.'</label>
                </td>
                <td>
                <input type="file" class="emol-text-input emol-file" name="picture" id="emol-picture" />
                </td>
                </tr>
                ';

                //if there are comptences, make them selectable
                /*
                if(count($this->competences) > 0 ){
                $treelist = new emol_Treelist($this->competences, true);
                $tree = $treelist->getTree();
                $applyHtml .= '
                <tr>
                <td>'.EMOL_APPLY_MATCHPROFILE.'</td>
                <td>
                <div id="competences">
                ' . $tree . ' 
                </div>
                </td>
                </tr>';
                }
                */

            } else { //else if logged on:

                $api = $this->emolApi->get('applicant');
                $app = $api->getSummaryPrivate();
                $applyHtml .= '
                <tr>
                <td>
                <input type="hidden" name="applicant_id" value="'.$app['id'].'">Sollicitant
                </td>
                <td>
                '.$app['Person']['fullname'].'
                </td>
                </tr>
                ';
            }

           
            if ( !isset($data) )
            	$data = array();
            	
            if ( !isset($data['summary']) )
            	$data['summary'] = '';
            
            $applyHtml .= '
            <tr>
             <td>
             '.EMOL_APPLY_MOTIVATION.'
             </td>
            <td>
              <textarea class="emol-text-input emol-textarea" name="motivation" id="motivation">'.$data['summary'].'</textarea>
            </td>
            </tr>
            <tr>
            <td>
            &nbsp;
            </td>
            <td>
            <input type="submit" value="'.EMOL_APPLY_SEND.'" />
            
            </td>
            </tr>';

        }

        //finish up html
        $applyHtml .= '</tbody>
        </table>
        </form>
        </div>';
        
        //return some html
        return $applyHtml;
    }

    /**
    * userd by the initialisation
    */
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
