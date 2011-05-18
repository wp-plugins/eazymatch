<?php
/*
Plugin Name: EazyMatch 
Plugin URI: http://wordpress.eazymatch.net
Description: This Plugin integrates EazyMatch in your Wordpress website.
Version: 1.3
Author: EazyMatch
Author URI: http://www.eazymatch.net
License: GPL2
*/

/*  Copyright 20011  EazyMatch  (email : vincent@inforvision.nl )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
	
	//eazymatch session management
	include('emol-session.php');
	
	//set version
	global $emol_side;
	global $emol_db_version;
	$emol_db_version = "1.2.2";
	
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
	
	// eazymatch array manager
	include('emol-array.php');
	
	// eazymatch script/css manager
	include('emol-require.php');
	emol_require::basic();
	
	// eazymatch specific functions
	include('emol-functions.php');
	
	// the install/d-install script
	include('emol-install.php');
	
	// include the admin menu
	include('emol-admin.php');
	
	// include function and class for eazymatch 3.0 SOAP connection
	include('emol-connect.php');

	// rewrite urls for jobs etc
	include('emol-rewrite.php');
	
	// instantiate shorttags
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