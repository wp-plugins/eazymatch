<?
	add_action('admin_menu', 'eazymatch_admin_menu');

	/**
	* Add the eazyMatch Admin menu
	* 
	*/
	function eazymatch_admin_menu() {
		add_menu_page( 'EazyMatch', EMOL_ADMIN_GLOBAL, 'manage_options', 'emol-admin', 'eazymatch_plugin_options', 'http://www.eazymatch.nl/wp_admin_icon.png' );
		
		add_submenu_page( 'emol-admin', EMOL_ADMIN_JOB, EMOL_ADMIN_JOB, 'manage_options', 'emol-job', 'eazymatch_plugin_job');
		add_submenu_page( 'emol-admin', EMOL_ADMIN_CV, EMOL_ADMIN_CV, 'manage_options', 'emol-cv', 'eazymatch_plugin_cv');
		//add_submenu_page( 'emol-admin', EMOL_ADMIN_ACCOUNT, EMOL_ADMIN_ACCOUNT, 'manage_options', 'emol-cv', 'eazymatch_plugin_account');
	}


	/**
	* Handle all vars that are configurable
	* 
	*/
	include(EMOL_DIR.'/admin/job.php');
	include(EMOL_DIR.'/admin/cv.php');
	include(EMOL_DIR.'/admin/global.php');
?>