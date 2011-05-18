<?php 
/**
* login and edit a applicant profile
*/
class EmolApplicantAccountPage
{
	/**
	* The slug for the fake post.  This is the URL for your plugin, like:
	* http://site.com/about-me or http://site.com/?page_id=about-me
	* @var string
	*/
	var $page_slug = '';

	//image container
	var $emol_app_image = '';

	/**
	* The title for your fake post.
	* @var string
	*/
	var $page_title = 'EmolApplicantAccountPage';

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
	* @var string
	*/
	var $msag = '';

	/**
	* EazyMatch 3.0 Api
	* 
	* @var mixed
	*/
	var $emolApi;
	var $wsApp;

	/**
	* Class constructor
	*/
	function EmolApplicantAccountPage($slug , $function='' ){

		$this->page_slug = $slug.'/'.$function;
		$this->emol_function = $function;

		//first connect to the api
		$this->emolApi  = eazymatch_connect();

		if( ! $this->emolApi ){
			eazymatch_trow_error();
		}

		$this->page_title = EMOL_ACCOUNT_TITLE;
		
		$this->wsApp 	= $this->emolApi->get('applicant');
		
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
		* This is the content of the post.  This is where the output of
		* your plugin should go.  Just store the output from all your
		* plugin function calls, and put the output into this var.
		*/
		if($this->emol_function == 'login'){
			$post->post_content = $this->doLogin();
		} elseif($this->emol_function == 'logout') {
			$post->post_content = $this->doLogout();
		} elseif($this->emol_function == 'match') {
			$this->page_title .= ' - '.EMOL_MENU_APP_MATCH;
			$post->post_content = $this->getMatchContent();
		} elseif($this->emol_function == 'cv') {
			$post->post_content = $this->getCVContent();
		} elseif($this->emol_function == 'naw') {
			$this->page_title .= ' - '.EMOL_MENU_APP_CV;
			$post->post_content = $this->getNAWContent();
		} elseif($this->emol_function == 'applications') {
			$this->page_title .= ' - '.EMOL_MENU_APP_APLIC;
			$post->post_content = $this->getApplicationsContent();
		} elseif($this->emol_function == 'downloadcv') {
			$post->post_content = $this->getCVDownload();
		} elseif($this->emol_function == 'downloadtempcv') {
			$post->post_content = $this->getTempCVDownload();
		}

		/**
		* The title of the page.
		*/
		$post->post_title = $this->page_title;
		
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
	* when someone has hit the button to login
	*/
	function doLogin(){
		$wsLogin  = $this->emolApi->get('session');
		$userPass = emol_post( 'password' );
		$userName = emol_post( 'username' );

		$userKey = $wsLogin->getUserToken( $userName, $userPass, null );
       
		if( $userKey != '' ){
			$connectionManager = EazyConnectManager::getInstance();
			$connectionManager->setToken( $userKey );
			
			//reconnect with new credentials
			$this->emolApi  = eazymatch_connect();
			
			//get user info
			$user = $this->emolApi->get('person');
			$user = $user->getCurrent();
			
			emol_session::set(array(
				'applicant_id'	 	=> $user['isApplicant'],
				'company_id' 	 	=> $user['isCompany'],
				'contact_id' 	 	=> $user['isContact'],
				'person_id' 		=> $user['id']
			));
			
			
            if(emol_post( 'emol_redirect_url' )){
                wp_redirect(emol_post( 'emol_redirect_url' ));
            } else {
            
			    if($user['isApplicant'] > 0){
				    wp_redirect( '/'.get_option( 'emol_account_url' ).'/naw/' );
			    } else {
				    wp_redirect( '/'.get_option( 'emol_company_account_url' ).'/naw/' );
			    }
                
            }
			exit;

		} else {
			emol_session::terminate();
			return '<p>Uw logingegevens zijn niet juist...</p>';	
		}

	}

	/**
	* when someone has hit the button to lgggout
	*/
	function doLogout(){
		emol_session::terminate();
        //reset emol
        $em = new EazyConnectManager();
        $em->resetConnection();
		ob_clean();
		header('location: /');
		exit();
	}


	/**
	* creates the fake content
	*/
	function getMatchContent(){
    	// prepare client resources
        emol_require::validation();
        emol_require::jqueryUi();
		
		//get the data of this logged on person
		$app  	= $this->wsApp->getSummaryPrivate();
		
		//set ids
		$personId 	= $app['person_id'];
		$appId 		= $app['id'];
		
		
		//try and get a previous mutation
		$mutation = array();
		try {
			$mutation  	= $this->wsApp->getMutationData('applicant-update-competence',$appId);
		} catch (SoapFault $e){
			eazymatch_trow_error('EazyMatch Error: account - mutations');
		}
		
		/**
		* Save an update of the naw data
		*/
		if(isset($_POST['save']) && $_POST['save'] == 1){
			$this->saveMATCH($personId,$appId);
		} 
		
		//message to user for pending mutations
		$mutationMsg = '';
		if(count($mutation)>0){
			$mutationMsg = '<tr><td colspan="2"><div class="emol-account-message">'.EMOL_ACCOUNT_APP_MSG_MUTATION.'</div></td></tr>';
		}
		
		//get a possible mutation
		if(count($mutation) == 0){
			$prof 		 = $this->wsApp->getProfilePrivate();
		} else {
			$prof = $mutation['content']['Profile'];
		}
		
		$cApi 		 = $this->emolApi->get('competence');
		$competences = $cApi->tree();
		
		$checkd=array();
		if(isset($prof['Competence']) && is_array($prof['Competence']) && count($prof['Competence']) > 0){
			foreach($prof['Competence'] as $c){
				$checkd[] = $c['id'];
			}
		}
		
		$matchHtml = $mutationMsg.'
			<div id="emol-form-div">
			<form method="post" id="emol-account-match-form" enctype="multipart/form-data">
			<input type="hidden" name="save" value="1" />
			<table class="emol-form-table">
			<tbody>';
			
		//if there are comptences, make them selectable
		if(count($competences) > 0 ){
			$treelist = new emol_Treelist($competences, true, $checkd);
			$tree = $treelist->getTree();
			$matchHtml .= '
			<tr>
				<td>'.EMOL_APPLY_MATCHPROFILE.'</td>
				<td>
					<div id="emol-competences">
						' . $tree . ' 
					</div>
				</td>
			</tr>';
		}
		
		$matchHtml .= '<tr><td colspan=2 align=right><input type="submit" value="'.EMOL_ACCOUNT_SAVE.'" /></td></tr>';
		
		$matchHtml .= '</tbody></table></form></div>';
		return $matchHtml;
	} 

	/**
	* creates the fake content
	*/
	function getCVContent(){
		return 'cv';
	}

	/**
	* creates the fake content
	*/
	function getApplicationsContent(){
    	// prepare client resources
        emol_require::validation();
        emol_require::jqueryUi();
        
		$this->wsApp = $this->emolApi->get('mediation');
		$med = $this->wsApp->byApplicantPrivate();
		
		$medContent = '<table class="emol-account-table">
		<tr class="emol-account-table-header">
			<td>'.EMOL_APPL_DATE.'</td>
			<td>'.EMOL_APPL_JOB.'</td>
			<td>'.EMOL_APPL_STATE.'</td>
		</tr>
		';
		foreach($med as $mediation){
			
			$status = '-';
			if(isset($mediation['Mediationphase']['name'])){
				$status = $mediation['Mediationphase']['name'];
			}
			
			$desc = EMOL_ACCOUNT_EMPTY;
			if($mediation['description'] > '' ){
				$desc = $mediation['description'];
			}
			
			$medContent .= '<tr>';
			$medContent .= '<td>'.date('d-m-Y H:i',strtotime($mediation['datemodified'])).'</td>';
			$medContent .= '<td><span class="emol-mediation-title">(#'.$mediation['Job']['id'].') '.$mediation['Job']['name'].'</span></td>';
			$medContent .= '<td>'.$status.'</td>';
			$medContent .= '</tr>';
			$medContent .= '<tr>';
			$medContent .= '<td colspan="3"><div class="emol-mediation-description">'.$desc.'</div></td>';
			$medContent .= '</tr>';
		}
		$medContent .= '</table>';
		return $medContent;
	}

	/**
	* creates the fake content
	*/
	function getNAWContent(){
    	// prepare client resources
        emol_require::validation();
        emol_require::jqueryUi();
        
		//get the data of this logged on person
		$app  	= $this->wsApp->getSummaryPrivate();
		
		//set ids
		$personId 	= $app['person_id'];
		$appId 		= $app['id'];
		
		//try and get a previous mutation
		$mutation = array();
		try {
			$mutation  	= $this->wsApp->getMutationData('applicant-update-naw',$appId);
		} catch (SoapFault $e){
			eazymatch_trow_error('EazyMatch Error: account - mutations');
		}
		
		/**
		* Save an update of the naw data
		*/
		if(isset($_POST['save']) && $_POST['save'] == 1){
			$this->saveNAW($personId,$appId);
		} 
		
		/**
		* Replace person data with mutation data if the user has given us a mutation already
		*/
		$mutationMsg = '';
		if(count($mutation)>0){
			$mutationMsg = '<tr><td colspan="2"><div class="emol-account-message">'.EMOL_ACCOUNT_APP_MSG_MUTATION.'</div></td></tr>';
			$mutation = $mutation['content'];
			//overwrite app with mutation data
			//this will "overlay" the mutation on the applicant data
			
			$app = array_merge_recursive_distinct($app, $mutation['Applicant']);
			if(isset($mutation['Documents']['Picture']['content']) && strlen($mutation['Documents']['Picture']['content']) > 0){
				$pic = $mutation['Documents']['Picture']['content'];
			}
			
			if(isset($mutation['Documents']['CV']['content']) && strlen($mutation['Documents']['CV']['content']) > 0){
				$downloadUrl = 'downloadtempcv';
				$cv = $mutation['Documents']['CV'];
				$cv['originalname'] = $mutation['Documents']['CV']['name'];
			}
		}
		
		/**
		* Create the html form
		* 
		* @var mixed
		*/
		$accountHtml = $this->msag.'
		<div id="emol-form-div">
		<form method="post" id="emol-account-naw-form" enctype="multipart/form-data">
		<input type="hidden" name="save" value="1" />
		<table class="emol-form-table">
		<tbody>';

		if ( !isset( $app['Person']['Preferedemailaddress'] ) ) $app['Person']['Preferedemailaddress'] = array( 'email' => '' );
		if ( !isset( $app['Person']['Preferedaddress'] ) ) $app['Person']['Preferedaddress'] = array( 'street' => '', 'city' => '', 'housenumber' => '', 'zipcode' => '', 'extension' => '' );
		if ( !isset( $app['Person']['Preferedphonenumber'] ) ) $app['Person']['Preferedphonenumber'] = array( 'phonenumber' => '' );
		
		$accountHtml .= $mutationMsg.'
		<tr>
			<td>
				'.EMOL_ACCOUNT_APP_CVTITLE.'
			</td>
			<td>
				<input type="text" class="emol-text-input emol-large" name="title" value="'.@$app['title'].'" id="emol-title" />
			</td>
			</tr>
			<tr>
			<td>
				'.EMOL_ACCOUNT_APP_FIRSTNAME.'
			</td>
			<td>
				<input type="text" class="emol-text-input" name="firstname" value="'.@$app['Person']['firstname'].'" id="emol-firstname" />
			</td>
			</tr>
			<tr>
			<td>
				'.EMOL_ACCOUNT_APP_LASTNAME.'
			</td>
			<td>
				<input type="text" class="emol-text-input emol-small" name="middlename" value="'.@$app['Person']['middlename'].'" id="emol-middlename" />
				<input type="text" class="emol-text-input" name="lastname" value="'.@$app['Person']['lastname'].'" id="emol-lastname" />
			</td>
			</tr>
			<tr>
			<td>
				'.EMOL_ACCOUNT_APP_ADDRESS.'
			</td>
			<td>
				<input type="hidden" name="address[1][id]" value="'.@$app['Person']['Preferedaddress']['id'].'" /> 
				<input type="text" class="emol-text-input" id="emol-address" name="address[1][street]" value="'.@$app['Person']['Preferedaddress']['street'].'" /> 
				<input type="text" value="'.@$app['Person']['Preferedaddress']['housenumber'].'" class="emol-text-input emol-small validate[required,custom[onlyNumber],length[0,5]]" name="address[1][housenumber]"   id="emol-housenumber" />
				<input type="text" value="'.@$app['Person']['Preferedaddress']['extension'].'" class="emol-text-input emol-small validate[required,custom[onlyNumber],length[0,5]]" name="address[1][extension]"   id="emol-extension" />
			</td>
			</tr>
			<tr>
			<td>
				'.EMOL_ACCOUNT_APP_ZIPCODE.'
			</td>
			<td>
				<input type="text" class="emol-text-input" name="address[1][zipcode]" id="emol-zipcode" value="'.@$app['Person']['Preferedaddress']['zipcode'].'" />
			</td>
			</tr>
			<tr>
			<td>
				'.EMOL_ACCOUNT_APP_CITY.'
			</td>
			<td>
				<input type="text" class="emol-text-input" name="address[1][city]" id="emol-city" value="'.@$app['Person']['Preferedaddress']['city'].'" />
			</td>
			</tr>
			<tr>
			<td>
				'.EMOL_ACCOUNT_APP_PHONE.'
			</td>
			<td>
				<input type="text" class="emol-text-input" name="phonenumber" id="emol-phonenumber" value="'.@$app['Person']['Preferedphonenumber']['phonenumber'].'" />
			</td>
			</tr>
			<tr>
			<td>
				'.EMOL_ACCOUNT_APP_EMAIL.'
			</td>
			<td>
				<input type="text" class="emol-text-input" name="email" id="emol-email" value="'.@$app['Person']['Preferedemailaddress']['email'].'" />
			</td>
		</tr>';

		//get the CV document
		if(!isset($cv) && isset($app['id']) && (int)$app['id'] > 0){
			$cv = $this->wsApp->getCurriculumDocument( $app['id'] );
			$downloadUrl = 'downloadcv';
		}
		
		$icon = '<br><img src="/wp-content/plugins/eazymatch/icon/default.png" />';	
		
		if(isset($cv['type'])){
			switch($cv['type']){
				case 'application/msword':
					$icon = '<br><img src="/wp-content/plugins/eazymatch/icon/msword.png" />';	
				break;
				case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
					$icon = '<br><img src="/wp-content/plugins/eazymatch/icon/msword.png" />';	
				break;
				case 'application/pdf':
					$icon = '<br><img src="/wp-content/plugins/eazymatch/icon/pdf.png" />';	
				break;
			}
		}
		$accountHtml .='
		<tr>
			<td>
				'.EMOL_ACCOUNT_APP_CV.'
			</td>
			<td>
				<input type="file" class="emol-text-input emol-file" name="cv" id="cv" />
				' . ( isset($cv['originalname']) ?  $icon.'&nbsp;<a href="/'.get_option( 'emol_account_url' ).'/'.$downloadUrl.'" target="_blank">'.$cv['originalname'].'</a>' : '') . '
			</td>
		</tr>';
        
        
        /*
		//get the picture
        $pic='';
		if( ! isset($pic) && isset($app['id']) && (int)$app['id'] > 0 ){
			$pic = $this->wsApp->getPicture($app['id']);
		}
		
		$img = '';
		if($pic != ''){
			$img = '<img src="data:image/jpg;base64,'.($pic).'" />';
		}
		
      
        $accountHtml .='
		<tr>
			<td>
				'.EMOL_ACCOUNT_APP_PHOTO.'
			</td>
			<td>
				<input type="file" class="emol-text-input emol-file" name="picture" id="picture" />
				<div id="emol-account-picture">'.$img.'</div>
			</td>
		</tr>
		<tr>
			<td>
				&nbsp;
			</td>
			<td>
				<input type="submit" value="'.EMOL_ACCOUNT_SAVE.'" />
			</td>
		</tr>
		';
*/

		//finish up html
		$accountHtml .= '</tbody>
		</table>
		</form>
		</div>';



		return $accountHtml;
	}
	
	
	/**
	* Downloads a CV
	* 
	*/
	function getCVDownload(){
		//get the data of this logged on person
		$cv  	= $this->wsApp->downloadCV();
		
		ob_clean();
		header("Cache-Control: public, must-revalidate");
		header("Pragma: hack"); // oh well, it works...
		header("Content-Type: " . $cv['type']);
		header("Content-Length: " .$cv['size']);
		header('Content-Disposition: attachment; filename="'.$cv['originalname'].'.'.$cv['extension'].'"');
		header("Content-Transfer-Encoding: binary\n");
		
		ob_flush(); // notify the browser a file is comming
		
		echo base64_decode($cv['content']);
		
		exit();
	}
	
	
	/**
	* Downloads a temp CV
	* 
	*/
	function getTempCVDownload(){
		
		//get the data of this logged on person
		$app  	= $this->wsApp->getSummaryPrivate();
		
		
		//set ids
		$appId 		= $app['id'];
		$mutation  	= $this->wsApp->getMutationData('applicant-update-naw',$appId);
	
		//get the data of this logged on person
		$cv  	= $mutation['content']['Documents']['CV'];
		
		ob_clean();
		header("Cache-Control: public, must-revalidate");
		header("Pragma: hack"); // oh well, it works...
		header("Content-Type: " . $cv['type']);
		header("Content-Length: " .sizeof($cv['content']));
		header('Content-Disposition: attachment; filename="'.$cv['name'].'"');
		header("Content-Transfer-Encoding: binary\n");
		
		ob_flush(); // notify the browser a file is comming
		
		echo base64_decode($cv['content']);
		
		exit();
	}
	
	
	/**
	* Save NAW to emol 3
	* 
	*/
	function saveNAW($personId,$appId){
		//save me
		//create a array the way EazyMatch likes it
		$subscription = new emol_ApplicantMutation();
		
		//set the person
		$subscription->setPerson(
			$personId,
			emol_post( 'firstname' ),
			emol_post( 'middlename' ),
			emol_post( 'lastname' ),
			emol_post( 'middlename' ),
			emol_post( 'birthdate' )
		);
		
		//set the Applicant
		$subscription->setApplicant(
			$appId,
			date('Ymd'),
			date('Ymd'),
			null,
			$_POST['title']
		);
		
		//set addresses
		foreach($_POST['address'] as $addrPieceArr){
            $addrPiece = new emol_array( $addrPieceArr );
            
            $subscription->addAddress(
                $addrPiece->id,
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
		$subscription->addEmailaddresses(null,null,$_POST['email']);
		
		/**phonenumber**/
		$subscription->addPhonenumber(null,null,$_POST['phonenumber']);
		
		//CV
		if(isset($_FILES['cv']['tmp_name']) && !empty($_FILES['cv']['tmp_name'])){
			//set the CV document
			
			$doc = array();
			$doc['name'] = $_FILES['cv']['name'];
			$doc['content'] = base64_encode(file_get_contents($_FILES['cv']['tmp_name']));
			$doc['type'] = $_FILES['cv']['type'];
			
			
			$subscription->setCV($doc['name'], $doc['type'], $doc['content']);
		}
		
		//PHOTO
		if(isset($_FILES['picture']['tmp_name']) && !empty($_FILES['picture']['tmp_name'])){
			//set the CV document
			$doc = array();
			$doc['name'] = $_FILES['picture']['name'];
			$doc['content'] = base64_encode(file_get_contents($_FILES['picture']['tmp_name']));
			$doc['type'] = $_FILES['picture']['type'];
			
			
			$subscription->setPicture($doc['name'], $doc['type'], $doc['content']);
		}
		
		//create the workable postable array
		$postData = $subscription->createSubscription();

		//save the subscription to EazyMatch
		$this->wsApp->addMutationData($postData,'applicant-update-naw');
		
        ob_clean();        
        wp_redirect( get_bloginfo('wpurl') . '/' . get_option( 'emol_account_url' ) . '/naw/' );
        exit;
	}

	/**
	* Save MATCHPROFILE to emol 3
	* 
	*/
	function saveMATCH($personId,$appId){
		//save me
		//create a array the way EazyMatch likes it
		$subscription = new emol_ApplicantMutation();
		
		//set the person
		$subscription->setPerson(
			$personId
		);
		
		//set the Applicant
		$subscription->setApplicant(
			$appId
		);
		
		//competences
		if(isset($_POST['competence'])){
			foreach($_POST['competence'] as $cpt){
				$subscription->addCompetence($cpt);
			}
		}
		
		//create the workable postable array
		$postData = $subscription->createSubscription();

		//save the subscription to EazyMatch
		$this->wsApp->addMutationData($postData,'applicant-update-competence');
		
		ob_clean();
		header('location: /'.get_option( 'emol_account_url' ).'/match/');
		exit();
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
