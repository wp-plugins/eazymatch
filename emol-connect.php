<?php

/**
* returns the current eazymatch connect Instance
* 
* @return EazyConnect
*/
function eazymatch_connect(){
	$connectionManager =  EazyConnectManager::getInstance();
	return $connectionManager->getConnection();
}

Class EazyConnectManager {
	private static $instanceObj;
	private $instance;
	private $key;
	private $secret;
	private $connection = null;

	public function __construct(){
		$this->instance = get_option( 'emol_instance' );
		$this->key 		= get_option( 'emol_key' );
		$this->secret 	= get_option( 'emol_secret' );
	}

	/**
	* @return EazyConnectManager
	*/
	public static function getInstance() {
		if (!isset(self::$instanceObj)) {
			self::$instanceObj = new EazyConnectManager();
		}
		return self::$instanceObj;
	}

	public function getConnection(){
		if ( $this->connection == null )
			$this->reconnect();

		return $this->connection;
	}

	public function reconnect(){
		// check if apiKey is present and instanceName is not empty
		if ( strlen( $this->key ) < 6 && strlen( $this->instance ) < 4 && strlen( $this->secret ) < 4  ){
			if ( is_admin() ){
				eazymatch_trow_error('Eazymatch connection settings incorrect.');
			}
			return null;
		}
		
		if( emol_session::exists( 'api_hash' ) ){
			$apiKey = emol_session::get( 'api_hash' );
		} else {
            //get new token
            $apiConnect = new EazyConnect( $this->key, $this->instance );
            $tempToken = $apiConnect->get('session')->getToken( $this->key );
            $tempToken = hash('sha256', $tempToken . $this->secret);
            
            //save it in session
            emol_session::set(array( 'api_hash' => $tempToken));
			
            //get it from session
            $apiKey = emol_session::get( 'api_hash' );
            
		}

		if ( $this->connection == null )
			$this->connection = new EazyConnect( $apiKey, $this->instance );
		else
			$this->connection->setKey( $apiKey );
	}

	public function resetConnection(){
        
		emol_session::remove( 'api_hash' );
		$this->reconnect();
	}

	public function setToken( $token, $forceReconnect = true ){
		emol_session::set( 'api_hash', hash('sha256', $token . $this->secret) );

		if ( $forceReconnect )
			$this->reconnect();
	}

	// functie om singleton af te sluiten, let op, alle gekopieerde instancies moeten ook afgesloten worden
	public function destroy(){
		self::$instance = null;
	}
}


/**
* 
* Provides a proxy to the EazyCore by autocreating soapclients
* 
* @author Rob van der Burgt
*
*/
Class EazyConnect {
	private $apiKey = '';
	public  $instanceName = '';
	private $serviceNames = array();

	/**
	* contructe the connection to the eazycore
	*
	* @param string $serviceUrl url of the eazycore
	*/
	public function __construct($key,$instance){
		$this->apiKey 		= $key;
		$this->instanceName = $instance;
	}

	/**
	* Magic function to autocreate class objects for soap services
	*/
	public function &__get($serviceName){
		// generate a new EazyConnectProxy to provide access to the Core controller
		$this->{$serviceName} = new EazyConnectProxy($this->instanceName, $this->apiKey, $serviceName);

		if ( !in_array($serviceName, $this->serviceNames ) )
			$this->serviceNames[] = $serviceName;

		// return the object
		return $this->{$serviceName};
	}

	public function get($serviceName){
		if ( isset( $this->{$serviceName} ) )
			return $this->{$serviceName};
		else 
			return $this->__get($serviceName);
	}

	public function setKey( $key ){
		$this->apiKey = $key;

		foreach( $this->serviceNames as $serviceName ){
			$service = $this->get( $serviceName );
			$service->setKey( $key );
		}
	}
}

/**
* 
* Provides a proxy to the EazyCore, the proxy automatically catches SoapFaults
* 
* @author Rob van der Burgt
*
*/
Class EazyConnectProxy {
	/**
	* keeps track of the global debug mode in the plugin
	* 
	* @var bool
	*/
	private $debug;

	/**
	* EazyMatch instance name
	* 
	* @var string
	*/
	private $instanceName = '';

	/**
	* apiKey used in the connection
	* 
	* @var string
	*/
	public $apiKey = '';

	/**
	* service (EazyCore controller) of this object
	* 
	* @var string
	*/
	private $serviceName = '';

	/**
	* EazyCore Url
	* 
	* @var string
	*/
	private $serviceUrl = '';

	/**
	* Reference to the SoapClient wich is wraped in this object
	* 
	* @var SoapClient
	*/
	private $service;

	/**
	* contructe the connection to the eazycore
	*
	* @param string $serviceUrl url of the eazycore
	*/
	public function __construct($instanceName, $apiKey, $serviceName){
		// create a reference to the debug switch
		global $emol_isDebug;
		$this->debug 			= $emol_isDebug;

		$this->instanceName 	= $instanceName;
		
		$this->serviceName 		= $serviceName;
		$this->serviceUrl		= 'https://core.eazymatch.net:443';

		$this->apiKey = $apiKey;
	}

	public function setKey( $key ){
		$this->apiKey = $key;

		/**
		* set SoapClient options
		* http://nl2.php.net/manual/en/soapclient.soapclient.php#soapclient.soapclient.parameters
		*/	

		ini_set("soap.wsdl_cache_enabled", ($this->debug == false ? 1 : 0 ) );

		$soapOptions = array (
		    'cache_wsdl'		=> $this->debug ? WSDL_CACHE_NONE : WSDL_CACHE_BOTH,	
		    'user_agent' 		=> $this->apiKey.','.$this->instanceName,
		    'trace'				=> $this->debug ? 1 : 0,
		    'compression'		=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 16,
		    'encoding'			=> 'utf-8'
		);

		// create object in local variable ( so this method will execute only once but the object stays availible)
		$this->service = new SoapClient($this->serviceUrl . '/wsdl.php?' . $this->serviceName, $soapOptions);
	}

	public function __call($name, $argu){
		// check if apikey is created
		if ( !is_object( $this->service ) ){
			$this->setKey( $this->apiKey );
		}
		
		// check if apiKey is present and instanceName is not empty
		if ( strlen( $this->apiKey ) < 6 && strlen( $this->instanceName ) < 4 ){
			if ( is_admin() ){
				eazymatch_trow_error('Eazymatch connection settings incorrect.');
			}
			return null;
		}
		
		try {
			$response = call_user_func_array(array(&$this->service, $name), $argu);
		} catch ( SoapFault $e ) {
			// if the soap request fails, its most likely the session on the core is lost
			// try to reset the connection to the EazyCore
			$connectionManager = EazyConnectManager::getInstance();
			$connectionManager->resetConnection();

			try {
				$response = call_user_func_array(array(&$this->service, $name), $argu);
			} catch ( SoapFault $e ) {
				$response = null;
                var_dump( $e );
			}
           
		}
		return $response;
	}
}

Class EazyTrunk {
	private $eazyConnect;
	private $calls = array();
	private $response = array();

	/**
	* request an EazyConnect object for requesting Trunks
	* 
	* @param EazyConnect $eazyConnect
	* @return EazyTrunk
	*/
	public function __construct(){
		$this->eazyConnect = eazymatch_connect();
	}

	public function &request($class, $method, $arguments = array()){
		$this->calls[] = array(
		    'class' => $class,
		    'method' => $method,
		    'arguments' => $arguments
		);

		$this->response[] = null;
		return $this->response[count($this->response) - 1];
	}

	public function execute(){
		$responses = $this->eazyConnect->tool->trunk( $this->calls );
		if( $responses !== null){
	        $counter = -1; 
	        foreach($responses as $response){
		        $counter++;
		        $this->response[$counter] = $response;
	        }
        } else {
            $this->response = array('error: null response');
        }
	}
}