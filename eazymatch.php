<?php
/*
Plugin Name: EazyMatch 
Plugin URI: http://wordpress.eazymatch.net
Description: This Plugin integrates EazyMatch in your Wordpress website.
Version: 1.2.3
Author: EazyMatch
Author URI: http://www.eazymatch.net
License: GPL2
*/
	
	//eazymatch session management
	include('emol-session.php');
	
	//set version
	global $emol_side;
	global $emol_db_version;
	$emol_db_version = "1.2.3";
	
	global $emol_isDebug;
	$emol_isDebug = false;
	
	//eazymatch directory on server
	define('EMOL_DIR',dirname(__FILE__));
	
	//include language file
    if( file_exists(EMOL_DIR.'/lang/'.get_bloginfo('language').'.php') ) {
	    include(EMOL_DIR.'/lang/'.get_bloginfo('language').'.php');
    } else {
        eazymatch_trow_error('Language file '. get_bloginfo('language').'.php' . ' missing.');
    }
	
	//eazymatch array manager
	include('emol-array.php');
	
	//eazymatch specific functions
	include('emol-functions.php');
	
	//the install/d-install script
	include('emol-install.php');
	
	//include the admin menu
	include('emol-admin.php');
	
	//include function and class for eazymatch 3.0 SOAP connection
	include('emol-connect.php');

	//rewrite urls for jobs etc
	include('emol-rewrite.php');
	
	//instantiate shorttags
	include('emol-shorttags.php');
	
	/**
	* includes all objects stored in the folder objects
	*/
	if ($handle = opendir(EMOL_DIR.'/objects')) {
	    /* This is the correct way to loop over the directory. */
	    while (false !== ($file = readdir($handle))) {
	    	if($file != '..' && $file != '.')
	       	 include(EMOL_DIR.'/objects/'.$file);
		}
	    closedir($handle);
	}
	
	/**
	* includes all emol pages stored in the folder pages
	*/
	if ($handle = opendir(EMOL_DIR.'/pages')) {
	    /* This is the correct way to loop over the directory. */
	    while (false !== ($file = readdir($handle))) {
	    	if($file != '..' && $file != '.')
	       	 include(EMOL_DIR.'/pages/'.$file);
	       
		}
	    closedir($handle);
	}
	
	/**
	* includes all widgets stored in the folder widgets
	*/
	if ($handle = opendir(EMOL_DIR.'/widgets')) {
	    /* This is the correct way to loop over the directory. */
	    while (false !== ($file = readdir($handle))) {
	    	if($file != '..' && $file != '.')
	       	 include(EMOL_DIR.'/widgets/'.$file);
		}
	    closedir($handle);
	}
	
	
	//when activated do install function to create nescesserry settings
	register_activation_hook(__FILE__,'eazymatch_install');
	register_deactivation_hook(__FILE__,'eazymatch_uninstall');