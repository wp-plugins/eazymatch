<?php
	class emol_array {
		private $manageArray;
		
		function __construct ( &$inputArray = array() ){
			$this->setArray( &$inputArray );
		}
		
		function setArray( &$inputArray = array() ){
			$this->manageArray = &$inputArray;
		}
		
		function exists( $keyName ){
			return isset( $this->manageArray[ $keyName ] );
		}
		
		function get( $keyName ){
			if ( $this->exists( $keyName ) )
				return $this->manageArray[ $keyName ];
			return '';
		}
		
		function set( $keyName, $value ){
			$this->manageArray[ $keyName ] = $value;
		}
		
		function export(){
			return $this->manageArray;
		}
		
		function __get( $keyName ){
			return $this->get( $keyName );
		}
		
		function __set( $keyName, $value ){
			return $this->set( $keyName, $value );
		}
	}