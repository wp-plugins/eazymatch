<?php 
/**
* Creates a seo friendly string
* 
* @param mixed $input
* @return mixed
*/
function eazymatch_friendly_seo_string($input){
	$input = str_replace("-"," ",$input);
	$return = trim(ereg_replace(' +',' ',preg_replace('/[^a-zA-Z0-9\s]/','',strtolower($input))));
	$return = str_replace(' ','-',$return);
	if($return == '') $return = '-';
	return $return;
}

//error handeling
function eazymatch_trow_error($msg='EAZYMATCH ERROR'){
	//session_start();
	@session_destroy();
	echo "<div class=\"emol-error\">".$msg."</div>";
}

//debugging
function emol_debug($data){
	echo "<pre>";
	print_r($data);
	echo "</pre>";
}

/**
* include scripts / javascript / css for eazymatch
*/
wp_enqueue_script("jquery");
function eazymatch_header_scripts() {
    if( ! get_option( 'emol_jquery_ui_skin' ) ){
        add_option( 'emol_jquery_ui_skin' , 'base');
    }
	echo "\n<script type=\"text/javascript\" src=\"". get_bloginfo( 'wpurl') ."/wp-content/plugins/eazymatch/scripts/validation.js\"></script>";
	echo "\n<script type=\"text/javascript\" src=\"". get_bloginfo( 'wpurl') ."/wp-content/plugins/eazymatch/scripts/emol.js\"></script>";
	echo "\n<link href=\"". get_bloginfo( 'wpurl') ."/em-jobfeed/\" rel=\"alternate\" type=\"application/rss+xml\" title=\"Jobs - ".get_bloginfo( 'title')." - RSS 2.0\" />";
	echo "\n<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"". get_bloginfo( 'wpurl') ."/wp-content/plugins/eazymatch/css/style.css\" />";
    echo PHP_EOL.'<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/'.get_option( 'emol_jquery_ui_skin' ).'/jquery-ui.css" type="text/css" media="all" />';    
    echo PHP_EOL.'<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.8/jquery-ui.min.js" type="text/javascript"></script>';	
}
add_action('wp_head', 'eazymatch_header_scripts');

// eazymatch post object handling
$emol_post_obj = new emol_array( $_POST );
	
function emol_post( $keyName ){
	global $emol_post_obj;
	return $emol_post_obj->get( $keyName );
}

function emol_post_exists( $keyName ){
	global $emol_post_obj;
	return $emol_post_obj->exists( $keyName );
}

function emol_post_set( $keyName, $value ){
	global $emol_post_obj;
	return $emol_post_obj->set( $keyName, $value );
}


/**
* array_merge_recursive does indeed merge arrays, but it converts values with duplicate
* keys to arrays rather than overwriting the value in the first array with the duplicate
* value in the second array, as array_merge does. I.e., with array_merge_recursive,
* this happens (documented behavior):
*
* array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
*     => array('key' => array('org value', 'new value'));
*
* array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
* Matching keys' values in the second array overwrite those in the first array, as is the
* case with array_merge, i.e.:
*
* array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
*     => array('key' => array('new value'));
*
* Parameters are passed by reference, though only for performance reasons. They're not
* altered by this function.
*
* @param array $array1
* @param array $array2
* @return array
* @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
* @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
*/
function array_merge_recursive_distinct ( array &$array1, array &$array2 )
{
	$merged = $array1;

	foreach ( $array2 as $key => &$value )
	{
		if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
		{
			$merged [$key] = array_merge_recursive_distinct ( $merged [$key], $value );
		}
		else
		{
			$merged [$key] = $value;
		}
	}

	return $merged;
}

/*** DEBUGGING ***/
function eazymatch_start_debug(){
	ob_clean();
	echo '<B>'.date("H:i:s").'</B><br>';
	echo "<div class=\"emol-error\">";
	echo "<pre>";
}

function eazymatch_end_debug(){
	echo "</pre></div>";
	echo '<B>'.date("H:i:s").'</B>';
	exit();
}