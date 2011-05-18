<?php
	class emol_require {
		static private $includes = array();
		
		static private function registerInclude( $includeName ){
			if ( !self::hasInclude( $includeName ) )
				self::$includes[] = $includeName;
		}
		
		static public function hasInclude( $includeName ){
			return in_array( $includeName, self::$includes );
		}
		
		static public function jquery(){
			// the jquery library will conflict when wordpress is in admin mode
			if ( is_admin() || self::hasInclude( 'jquery' ) ) {
				return;
			}
			
			// add jquery from the google CDN for speed
			wp_deregister_script('jquery');
			wp_register_script('jquery', ("https://ajax.googleapis.com/ajax/libs/jquery/1.6.0/jquery.min.js"), false);
			wp_enqueue_script('jquery');
			
			self::registerInclude( 'jquery' );
		}
		
		static public function jqueryUi(){
			if ( is_admin() || self::hasInclude( 'jquery-ui' ) ) {
				return;
			}
			
			// jquery is required for validation
			self::jquery();
			
			if( ! get_option( 'emol_jquery_ui_skin' ) ){
			    add_option( 'emol_jquery_ui_skin' , 'base');
			}
			
			
			// add jquery-ui from the google CDN for speed
			wp_deregister_script('jquery-ui');
			wp_register_script('jquery-ui', ( 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.8/jquery-ui.min.js' ), array('jquery') );
			wp_enqueue_script('jquery-ui');
			
			wp_deregister_style('jquery-ui');
   			wp_register_style('jquery-ui', ( 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/'.get_option( 'emol_jquery_ui_skin' ).'/jquery-ui.css' ), false );
			wp_enqueue_style('jquery-ui');
				
			
			self::registerInclude( 'jquery-ui' );
		}
		
		static public function validation(){
			if ( self::hasInclude('validation') ) {
				return;
			}
			
			// jquery is required for validation
			self::basicJavascript();
			
			wp_deregister_script('validation');
			wp_register_script('validation', ( plugins_url( 'eazymatch') . '/scripts/validation.js' ), 'jquery');
			wp_enqueue_script('validation');
			
			self::registerInclude( 'validation' );
		}
		
		static public function basicCss(){
			if ( self::hasInclude('emol-css') ) {
				return;
			}
			
			// if style.css exists the user has defined his own stylesheets
			$fileName = file_exists( dirname ( __FILE__ ) . '/css/style.css' ) ? 'style.css' : 'style.default.css';
			
			wp_deregister_style('emol-css');
   			wp_register_style('emol-css', ( plugins_url( 'eazymatch') . '/css/' . $fileName  ), false );
			wp_enqueue_style('emol-css');
			
			self::registerInclude( 'emol-css' );
		}
		
		static public function basicJavascript(){
			if ( self::hasInclude('emol-js') ) {
				return;
			}
			
			// jquery is required 
			self::jquery();
			
			wp_deregister_script('emol-js');
			wp_register_script('emol-js', ( plugins_url( 'eazymatch') . '/scripts/emol.js' ), 'jquery');
			wp_enqueue_script('emol-js');
			
			self::registerInclude( 'emol-js' );
		}
			
		
		static public function basic(){
			self::basicCss();
			self::basicJavascript();
		}
		
		/**
		* require all emol scripts/styles
		* 
		* @param string $name
		* @return bool
		*/
		static public function all(){
			self::basic();
			self::validation();
			self::jqueryUi();
		}
	}