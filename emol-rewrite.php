<?php
/**
* Adds variables that are given by eazymatch to the Wordpress $_GET system
* 
* @param mixed $public_query_vars
*/
function eazymatch_add_var($public_query_vars) {
	$public_query_vars[] = 'emolpage';
	$public_query_vars[] = 'emolaction';
    $public_query_vars[] = 'emolparameters'; //for seo texts or /apply or whatever.
	$public_query_vars[] = 'emolrequestid'; //for seo texts or /apply or whatever.
	return $public_query_vars;
}
add_filter('query_vars', 'eazymatch_add_var');


/**
* Creates rewrite rules so the site will react to a url set in the CMS
* 
*/
function eazymatch_do_rewrite() {
	
	/**job urls**/
	add_rewrite_rule(get_option( 'emol_job_url' ).'/([^/]+)/([^/]+)/?$', 'index.php?emolpage='.get_option( 'emol_job_url' ).'&emolaction=$matches[1]&emolparameters=$matches[2]','top');
	add_rewrite_rule(get_option( 'emol_apply_url' ).'/([^/]+)/([^/]+)/?$', 'index.php?emolpage='.get_option( 'emol_apply_url' ).'&emolaction=$matches[1]&emolparameters=$matches[2]','top');
	add_rewrite_rule(get_option( 'emol_account_url' ).'/([^/]+)/?$', 'index.php?emolpage='.get_option( 'emol_account_url' ).'&emolaction=$matches[1]','top');
    add_rewrite_rule(get_option( 'emol_job_search_url' ).'/([^/]+)/?$', 'index.php?emolpage='.get_option( 'emol_job_search_url' ).'&emolaction=$matches[1]','top');
	add_rewrite_rule(get_option( 'emol_apply_url_free' ).'/?$', 'index.php?emolpage='.get_option( 'emol_apply_url_free' ).'&emolaction=$matches[1]','top');
	
	/**rss feed**/
	add_rewrite_rule('em-jobfeed/?$', 'index.php?emolpage=rss&emolaction=rss', 'top');
	
	/**cv urls**/
	add_rewrite_rule(get_option( 'emol_cv_url' ).'/([^/]+)/([^/]+)/?$', 'index.php?emolpage='.get_option( 'emol_cv_url' ).'&emolaction=$matches[1]&emolparameters=$matches[2]','top');
	add_rewrite_rule(get_option( 'emol_react_url_cv' ).'/([^/]+)/([^/]+)/?$', 'index.php?emolpage='.get_option( 'emol_react_url_cv' ).'&emolaction=$matches[1]&emolparameters=$matches[2]','top');
	add_rewrite_rule(get_option( 'emol_company_account_url' ).'/([^/]+)/?$', 'index.php?emolpage='.get_option( 'emol_company_account_url' ).'&emolaction=$matches[1]','top');
	add_rewrite_rule(get_option( 'emol_cv_search_url' ).'/([^/]+)/?$', 'index.php?emolpage='.get_option( 'emol_cv_search_url' ).'&emolaction=$matches[1]','top');
	
	
}
add_action('init', 'eazymatch_do_rewrite');

/**
* function that is hooked to the Wordpress queryparsing system
* when EMOL parameters are passed, this will create a fake eazymatch page 
* with the right content (see emol-page.php);
*/
function emol_parse_query() {
	global $wp_query;

	$EmolPage 		= get_query_var('emolpage'); //the emol page
	$EmolFunction 	= get_query_var('emolaction'); //id or handle data
	$EmolParams 	= get_query_var('emolparameters'); //mostly sef string
	 
   
   
	if (isset($EmolPage) && $EmolPage != '') {
    
		$wp_query->is_single 	= false;
		$wp_query->is_page 		= false;
		$wp_query->is_archive 	= false;
		$wp_query->is_search 	= false;
		$wp_query->is_home 		= false;

		//we need to combine all data to have our fake page do its work
		$emolSlug = $EmolFunction;
		if(isset($EmolParams) && $EmolParams != ''){
			$emolSlug .= '/'.$EmolParams;
		}
		
		//what side are we on?
		global $emol_side;
		
	
		/**
		* Create an instance the emol Fake Page
		*/

		switch($EmolPage){
			case 'rss':
				$dummyPage = 'EmolRssPage';
			break;
			case get_option( 'emol_job_url' ):
				$emol_side='applicant';
				$dummyPage = 'EmolJobPage';
			break;
            case get_option( 'emol_apply_url' ):
                $emol_side='applicant';
                $dummyPage = 'EmolApplyPage';
            break;
			case get_option( 'emol_apply_url_free' ):
				ob_clean();
                header('location: /'.get_option( 'emol_apply_url' ).'/0/open');
                exit();
			break;
			case get_option( 'emol_account_url' ):
				$emol_side='applicant';
				$dummyPage = 'EmolApplicantAccountPage';
			break;
			case get_option( 'emol_job_search_url' ):
				$emol_side='applicant';
				$dummyPage = 'EmolJobSearchPage';
			break;
			case get_option( 'emol_cv_url' ):
				$emol_side='company';
				$dummyPage = 'EmolCvPage';
			break;
			case get_option( 'emol_react_url_cv' ):
				$emol_side='company';
				$dummyPage = 'EmolReactPage';
			break;
			case get_option( 'emol_company_account_url' ):
				$emol_side='company';
				$dummyPage = 'EmolCompanyAccountPage';
			break;
			case get_option( 'emol_cv_search_url' ):
				$emol_side='company';
				$dummyPage = 'EmolCvSearchPage';
			break;
		}

      
		
		if(isset($dummyPage)){
			$emolDummyPage = new $dummyPage($EmolPage,$emolSlug);
		}
	}
}
add_filter('parse_query','emol_parse_query');
