<?php
/**
* EmolReactPage
*/
class EmolReactPage
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
	var $page_title = 'EmolReactPage';

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
	var $applicantApi;
	var $competenceApi;

	/**
	* When initialized this will be the handled job
	* 
	* @var mixed
	*/
	var $applicant;
	var $competences;
	var $applicantId = 0;

	/**
	* Class constructor
	*/
	function EmolReactPage($slug,$function=''){

		$this->page_slug = $slug.'/'.$function;
		$this->emol_function = $function;

		//first connect to the api
		$this->emolApi  = eazymatch_connect();

		if( ! $this->emolApi ){
			eazymatch_trow_error();
		}

		//split up the variables given
		$urlVars = explode('/',$this->page_slug);
		$applicantId = $urlVars[1];

		//get apis
		//$this->competenceApi    = $this->emolApi->get('competence');
		$this->applicantApi    	= $this->emolApi->get('applicant');

		if(is_numeric($applicantId) && $applicantId > 0){

			//get competences
			//$this->competences 		= $this->competenceApi->tree();

			//get the job
			$this->applicant		= $this->applicantApi->get($applicantId);
			$this->applicantId 	 	= $this->applicant['id'];


			//set the page variables	
			$this->page_title = EMOL_REACT.' "'.$this->applicant['title'].'"';
		} else {
			$this->cvId = 'open';
			$this->page_title = EMOL_CV_REACT_FREE;
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
		if(isset($_POST['EMOL_react'])){
			$this->doReact();
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
	function doReact(){
		$ws = $this->emolApi->get('company');

		if( ! emol_session::isValidId('company_id') ){

			//create a array the way EazyMatch likes it
			$subscription = new emol_CompanyMutation();

			//set the Company
			$subscription->setCompany(
			    null,
			    emol_post( 'name' ) ,
			    emol_post( 'profile' ),
			    emol_post( 'companysize_id' ) ,
			    emol_post( 'branche_id' ) ,
			    emol_post( 'coc' ) 
			);

			//set addresses
			foreach($_POST['address'] as $addrPieceArr){
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

			//set the person
			$subscription->setPerson(
			    null,
			    emol_post( 'firstname' ),
			    emol_post( 'middlename'),
			    emol_post( 'lastname'),
			    emol_post( 'gender')
			);

			//set the Contact
			$subscription->setContact(
			    null,
			    emol_post( 'department' )
			);


			/**email**/
			$subscription->addEmailaddresses(null,null,emol_post( 'email'));

			/**phonenumber**/
			$subscription->addPhonenumber(null,null,emol_post( 'phonenumber' ) );

			/**PHOTO**/
			if(isset($_FILES['logo']['tmp_name']) && (string)$_FILES['logo']['tmp_name'] != ''){

				/**set the CV document**/
				$doc = array();
				$doc['name'] = $_FILES['logo']['name'];
				$doc['content'] = base64_encode(file_get_contents($_FILES['logo']['tmp_name']));
				$doc['type'] = $_FILES['logo']['type'];

				/**set the logo**/
				$subscription->setLogo($doc['name'], $doc['type'], $doc['content']);
			}


			/**JOBDOC**/
			if(isset($_FILES['jobDocument']['tmp_name'])){

				/**set the CV document**/
				$doc = array();
				$doc['name'] = $_FILES['jobDocument']['name'];
				$doc['content'] = base64_encode(file_get_contents($_FILES['jobDocument']['tmp_name']));
				$doc['type'] = $_FILES['jobDocument']['type'];

				/**set the docu**/
				$subscription->setJob(emol_post( 'jobName' ), $doc);
                
			} elseif (emol_post( $_POST['jobName'] ) !== null){
			    $subscription->setJob(  emol_post( 'jobName' ) , array() );
			}
            			
            $url = $_SERVER['HTTP_HOST'];
			
            $subscription->setApplication(
			    null ,
                emol_post( 'applicantId' ) , 
                emol_post( 'motivation' ) ,
                $url
			);

			//create the workable postable array
			$postData = $subscription->createSubscription();

			//save the subscription to EazyMatch
			$ws->subscription($postData);

			ob_clean();		
			wp_redirect( get_bloginfo('wpurl').'/'.get_option('emol_react_url_cv').'/'.$this->applicantId .'/success/' );
			exit;

		} else {
			/*react to an applicant, notification will be sent to emol user*/
            $contactId  = emol_session::get('contact_id');
            
            if((int)$contactId > 0){
			    $success    = $ws->reactToApplicant( emol_post( 'jobId' ) , $contactId , emol_post( 'applicantId' ), emol_post( 'motivation' ) , true);
            } else { 
                echo "ERROR NO CONTACT"; 
                exit();
            }
			
			if($success == true){
				ob_clean();		
				wp_redirect( get_bloginfo('wpurl').'/'.get_option('emol_react_url_cv').'/'.$this->applicantId .'/success/' );
				exit;
			} else {
				ob_clean();		
				wp_redirect( get_bloginfo('wpurl').'/'.get_option('emol_react_url_cv').'/'.$this->applicantId .'/unsuccess/' );
				exit;
			}
		}
		exit();
	}


	/**
	* creates the fake content
	*/
	function getContent(){
    	// prepare client resources
        emol_require::validation();
        emol_require::jqueryUi();
		
		//the react form
		$reactHtml = '
		<div id="emol-form-div">
		<form method="post" id="emol-apply-form" enctype="multipart/form-data">
		<input type="hidden" name="applicantId" value="'.$this->applicantId.'" />
		<input type="hidden" name="EMOL_react" value="1" />
		<table class="emol-form-table">
		<tbody>';

		$urlVars = explode('/',$this->page_slug);
		if(isset($urlVars[2]) && $urlVars[2] == 'success'){
			$reactHtml .= '<tr><td colspan="2">'. nl2br(get_option( 'emol_react_success' )) .'</td></tr>';
		} elseif(isset($urlVars[2]) && $urlVars[2] == 'unsuccess'){
			$reactHtml .= '<tr><td colspan="2">'. EMOL_REACT_FAIL_MSG .'</td></tr>';
		} else {
			if( ! emol_session::isValidId( 'company_id' ) ){
				//NOT logged on
				$reactHtml .= '
				<tr>
				<td class="emol-form-td">
				'.EMOL_REACT_COMPANY.'
				</td>
				<td>
				<input type="text" class="emol-text-input validate[required]" name="name" id="name" />
				</td>
				</tr>
				<tr>
				<td class="emol-form-td">
				'.EMOL_GENDER.'
				</td>
				<td>
				<input type="radio" class="emol-radio-input" name="gender" value="m" checked="checked" /> '.EMOL_MALE.' <br />
				<input type="radio" class="emol-radio-input" name="gender" value="f" /> '.EMOL_FEMALE.'
				</td>
				</tr>
				<tr>
				<td>
				'.EMOL_FIRSTNAME.'
				</td>
				<td>
				<input type="text" class="emol-text-input" name="firstname" id="firstname" />
				</td>
				</tr>
				<tr>
				<td>
				'.EMOL_MIDDLENAME.' &amp; '.EMOL_LASTNAME.'
				</td>
				<td>
				<input type="text" class="emol-text-input emol-small " name="middlename" id="middlename" /> <input type="text" class="emol-text-input" name="lastname" id="lastname" />
				</td>
				</tr>
				<tr>
				<td>
				'.EMOL_ADDRESS.'  + '.EMOL_HOUSENUMBER.'  + '.EMOL_EXTENSION.'
				</td>
				<td>
				<input type="text" class="emol-text-input" name="address[1][street]" /> <input type="text" class="emol-text-input emol-small validate[required,custom[onlyNumber],length[0,5]]" name="address[1][housenumber]"   id="housenumber" /> <input type="text" class="emol-text-input emol-small" name="address[1][extension]" id="extension" />
				</td>
				</tr>
				<tr>
				<td>
				'.EMOL_REACT_DEPARTMENT.'
				</td>
				<td>
				<input type="text" class="emol-text-input" name="department" id="department" />
				</td>
				</tr>
				<tr>
				<td>
				'.EMOL_ZIPCODE.'
				</td>
				<td>
				<input type="text" class="emol-text-input" name="address[1][zipcode]" id="zipcode" />
				</td>
				</tr>
				<tr>
				<td>
				'.EMOL_REACT_CITY.'
				</td>
				<td>
				<input type="text" class="emol-text-input" name="address[1][city]" id="city" />
				</td>
				</tr>
				<tr>
				<td>
				'.EMOL_PHONE.'
				</td>
				<td>
				<input type="text" class="emol-text-input" name="phonenumber" id="phonenumber" />
				</td>
				</tr>
				<tr>
				<td>
				'.EMOL_EMAIL.'
				</td>
				<td>
				<input type="text" class="emol-text-input" name="email" id="email" />
				</td>
				</tr>
				<tr>
				<td>
				'.EMOL_REACT_COC.'
				</td>
				<td>
				<input type="text" class="emol-text-input" name="coc" id="coc" />
				</td>
				</tr>
				<tr>
				<td>
				'.EMOL_REACT_JOB.'
				</td>
				<td>
				<input type="text" class="emol-text-input validate[required]" name="jobName" id="jobName" />
				</td>
				</tr>
				<tr>
				<td>
				'.EMOL_REACT_JOBDOC.'
				</td>
				<td>
				<input type="file" class="emol-text-input emol-file" name="jobDocument" id="jobDocument" />
				</td>
				</tr>
				<tr>
				<td>
				'.EMOL_REACT_LOGO.'
				</td>
				<td>
				<input type="file" class="emol-text-input emol-file" name="logo" id="logo" />
				</td>
				</tr>
				';
			} else {
				//logged on
				$api  = $this->emolApi->get('company');
				$comp = $api->getSummaryPrivate();

				$api  = $this->emolApi->get('job');
				$jobs = $api->getPublishedByCompany( emol_session::get( 'company_id' ) );
               
                $items = '';
				
                if(count($jobs) > 0){
					$items = '<select name="jobId">';
					foreach($jobs as $job){
						$items .= '<option value='.$job['id'].'>'.$job['name'].'</option>';
					}
					$items .= '</select>';
				}

                if($items != ''){
                    $items = '<tr>
                                <td>
                                '.EMOL_REACT_JOB.'
                                </td>
                                <td>
                                '.$items.'
                                </td>
                                </tr>';
                }
                
				$reactHtml .= '
				<tr>
				<td>
				'.$comp['Person']['fullname'].'
				</td>
				<td>
				'.EMOL_REACT_BEHALF.' '.ucfirst($comp['Company']['name']).'
				</td>
				</tr>
				'.$items;
			}

			$reactHtml .= '
			<tr>
			<td>
			'.EMOL_REACT_MESSAGE.'
			</td>
			<td>
			<textarea class="emol-text-input emol-textarea" name="motivation" id="motivation"></textarea>
			</td>
			</tr>
			<tr>
			<td>
			&nbsp;
			</td>
			<td>
			<input type="submit" value="'.EMOL_REACT_SEND.'" />
			</td>
			</tr>';

		}

		//finish up html
		$reactHtml .= '</tbody>
		</table>
		</form>
		</div>';

		//return some html
		return $reactHtml;
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