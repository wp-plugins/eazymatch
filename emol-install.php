<?php

global $eazymatchOptions;

function eazymatch_install(){
	
	global $emol_db_version;
	global $eazymatchOptions;
	
	$current_version = get_option("emol_db_version");
	
	if( ! $current_version ){
		add_option("emol_db_version", $emol_db_version);
	} else {
		update_option("emol_db_version", $emol_db_version);
	}
}
?>