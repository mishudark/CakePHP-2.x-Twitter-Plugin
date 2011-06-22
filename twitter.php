<?php
	/*
	 * CakePHP TwitterComponent
	 * 
	 * This is an easy component for CakePHP to handle with the Twitter API. It includes also 
	 * the authorization via OAuth and the CakePHP HttpSocket, allowd trough the 'http_socket_oauth' by 
	 * Neil Crookes <www.neilcrookes.com>. 
	 * With this component you can easely call the main Twitter API methods, such as status updates, users
	 * or timelines, in your controllers when your application is authorized.
	 * Before you start visit https://dev.twitter.com/apps/new and register your own app to get your 
	 * OAuthConsumer and OAuthConsumerSecret. Then you are able to start and connect your CakePHP-App
	 * with twitter.
	 * 
	 * @author Florian Nitschmann (f.nitschmann@media-n.net)
	 * @links www.florian-nitschmann.de / www.media-n.net
	 * @copyright (c) 2011 Florian Nitschman/media-n
	 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
	 */

	 require_once 'oauth_socket.php';
	 
	 class TwitterComponent extends Object {
	 	//The component name
	 	var $name = 'Twitter';
		//Other used CakePHP core components
		var $components = array('Cookie' ,'Session');
		/*
		 * The Twitter consumer key & consumer secret
		 * 
		 * @access public
		 * @var
		 */ 
		var $consumer_key, $consumer_secret;
		
		//----OAuth setup and connect
		
		/*
		 * The OAuthConfig Class and class var
		 * 
		 * @access private
		 * 
		 */
		private $Oauth;
		
		/*
		 * Set the counsumer key and consumer secret for the app. Method should be called at startup and
		 * before twitterConnect(). All data will be saved in a cookie. 
		 * 
		 * @access public
		 * @params $consumer_key: The consumer key for the twitter app, $consumer_secret: The consumer 
		 * secret for the twitter app, $cookie: TRUE OR FALSE
		 */		
		 public function setup($consumer_key, $consumer_secret, $cookie) {
		 	$this->consumer_key = $consumer_key;
			$this->consumer_secret = $consumer_secret;
			//Cookie content
			$cookie_content = array(
				'consumer_key' => $this->consumer_key,
				'consumer_secret' => $this->consumer_secret
			);
			//If a cookie is allowed 
			if($cookie == true) {
				//OAuth Consumer Cookie
				$oauth_cookie = $this->Cookie->read('Twitter.OAuth.Consumer');
				//Check if $oauth_cookie is_null
				if(is_null($oauth_cookie)) $this->Cookie->write('Twitter.OAuth.Consumer', $cookie_content, true, '+365 day');
				else if($this->consumer_key != $consumer_key || $this->consumer_secret != $consumer_secret) {
					$this->Cookie->delete('Twitter.OAuth.Consumer');
					$this->Cookie->write('Twitter.OAuth.Consumer', $cookie_content, true, '+365 day');
				}
			}
			//Write keys in local session store
			$this->Session->write('Twitter.OAuth.Consumer', $cookie_content);
		 }
		 
		 /*
		  * Connect app to twitter and let it authorize.
		  * 
		  * @param $callback: The callback url (This should be a function in the controller where 
		  * callback() is called)
		  * @access public  
		  */
		 public function connect($callback) {
			$request = array(
			    'uri' => array(
			      'host' => 'api.twitter.com',
			      'path' => '/oauth/request_token',
			    ),
			    'method' => 'GET',
			    'auth' => array(
			      'method' => 'OAuth',
			      'oauth_callback' => $callback,
			      'oauth_consumer_key' => $this->consumer_key,
			      'oauth_consumer_secret' => $this->consumer_secret,
			    ),
			  );
			  $response = $this->Oauth->request($request);
			  //Redirect user to twitter to authorize application
			  parse_str($response, $response);
			  header('Location: http://api.twitter.com/oauth/authorize?oauth_token=' . $response['oauth_token']);
		}
					  
		/*
		 * OAuthToken and OAuthVertifier
		 * 
		 * @access public 
		 * @var
		 */
		var $oauth_token, $oauth_token_secret;
		
		/*
		 * The twitter callback method. Should be called after connect() in a different controller function.
		 * 
		 * @access public
		 * @params $oauth_token: The token send back by Twitter to the callback url,
		 * $$oauth_vertifier: The vertifier send back by Twitter to the callback url
		 */
		public function callback($oauth_token, $oauth_vertifier) {
			//Build request
			$request = array(
		    	'uri' => array(
		      		'host' => 'api.twitter.com',
		      		'path' => '/oauth/access_token',
		    	),
		    	'method' => 'POST',
		    	'auth' => array(
		      		'method' => 'OAuth',
		      		'oauth_consumer_key' => $this->consumer_key,
		      		'oauth_consumer_secret' => $this->consumer_secret,
		      		'oauth_token' => $oauth_token,
		      		'oauth_verifier' => $oauth_vertifier,
		    	),
		  	);
			//Get the response 
		  	$response = $this->Oauth->request($request);
		  	parse_str($response, $response);
			$this->setOauthUserKeys($response['oauth_token'], $response['oauth_token_secret']);
		}
		
		/*
		 * Set the oauth secret and oauth vertifier and save the in the current session
		 * 
		 * @access public
		 * @params $oauth_secret: The oauth secret, $oauth_vertifier: The oauth vertifier
		 */
		function setOauthUserKeys($oauth_token, $oauth_token_secret) {
			$current_session = $this->Session->read('Twitter.OAuth.User');
			if(!is_null($current_session)) $this->Session->delete('Twitter.OAuth.User');
			//Update class vars
			$this->oauth_token = $oauth_token;
			$this->oauth_token_secret = $oauth_token_secret;
			//Create a new session
			$new_session = array(
				'oauth_token' => $oauth_token,
				'oauth_token_secret' => $oauth_token_secret
			);
			//Save session
			$this->Session->write('Twitter.OAuth.User', $new_session);
		}
		
		/*
		 * Return the current oauth token and oauth token secret of the user and make
		 * them usable in the controller. Be carefull in usage! (Secret and user-specific informations)
		 * 
		 * @access public
		 * @return array() 
		 */
		function getOauthUserKeys() {
			$user_keys = array();
			if($this->oauth_token == '' || $this->oauth_token_secret == '') {
				$session = $this->Session->read('Twitter.OAuth.User');
				if(!is_null($session)) {
					$user_keys['oauth_token'] = $session['oauth_token'];
					$user_keys['oauth_token_secret'] = $session['oauth_token_secret'];
				}								
			}
			else {
				$user_keys['oauth_token'] = $this->oauth_token;
				$user_keys['oauth_token_secret'] = $this->oauth_token_secret;
			}
			return $user_keys;
		}
		
		//Controller before setup 
		 
		/*
		 * Initialized before the controllers beforeFilter()
		 */
		 
		function initialize(&$controller, $settings = array()) {
			//Open a new OAuthSocket
		 	$this->Oauth = new OAuthSocket();
			//Check $this->consumer_key and $this->consumer_secret
			if($this->consumer_key == '' || $this->consumer_secret == '') {
				$cookie = $this->Cookie->read('Twitter.OAuth.Consumer');
				if(!is_null($cookie)) {
					$this->consumer_key = $cookie['consumer_key'];
					$this->consumer_secret = $cookie['consumer_secret'];
				}
				//Use the local session if cookie isn't set
				else {
					$consumer_session = $this->Session->read('Twiter.OAuth.Consumer');
					if(!is_null($consumer_session)) {
						
					}
				}	
			}
			//Check $oauth_token and $oauth_token_secret
			if($this->oauth_token == '' || $this->oauth_token_secret == '') {
				//Look for the session
				$oauth_session = $this->Session->read('Twitter.OAuth.User');
				if(!is_null($oauth_session)) {
					$this->oauth_token = $oauth_session['oauth_token'];
					$this->oauth_token_secret = $oauth_session['oauth_token_secret'];
				}
			}
			$this->controller =& $controller;
		}
		//----
		
		/*
		 * Return the auth array for the Twitter API methods
		 * 
		 * @access private
		 */
		private function authArray() {
			return array(
				'method' => 'OAuth',
				'oauth_token' => $this->oauth_token,
				'oauth_token_secret' => $this->oauth_token_secret,
				'oauth_consumer_key' => $this->consumer_key,
				'oauth_consumer_secret' => $this->consumer_secret
			);
		}
		
		#====================Twitter API methods
		
		#Account Methods
		
		#account/verify_credentials
		/*
		 * Returns an HTTP 200 OK response code and a representation of the 
		 * requesting user if authentication was successful; 
		 * returns a 401 status code and an error message if not. 
		 * Use this method to test if supplied user credentials are valid.
		 * 
		 * @access public
		 * @return array 
		 */
		public function accountVerifyCredentials() {
			//Request 	
			$request = array(
	        	'method' => 'GET',
	        	'uri' => array(
	          		'host' => 'api.twitter.com',
	          		'path' => '/account/verify_credentials.json',
	        	),
	        	'auth' => $this->authArray(),
	        	/*'body' => array(
	          		'status' => 'Hello world!',
	        	),*/
	      	);
			//Return
			return json_decode($this->Oauth->request($request), true);
		}
		
		#account/rate_limit_status
		/*
		 * Returns the remaining number of API requests available to the requesting user 
		 * before the API limit is reached for the current hour. Calls to rate_limit_status 
		 * do not count against the rate limit. If authentication credentials are provided, 
		 * the rate limit status for the authenticating user is returned. Otherwise, the rate 
		 * limit status for the requester's IP address is returned.
		 * 
		 * @access public
		 * @return array() 
		 */
		public function accountRateLimitStatus() {
			//Request
			$requst = array(
				'method' => 'GET',
				'url' => array(
					'host' => 'api.twitter.com',
					'path' => '/account/rate_limit_status.json'
				),
	        	'auth' => $this->authArray($this->consumer_key, $this->consumer_secret, $this->oauth_token, $this->oauth_token_secret),
			);
		}
		
		//-----
	 }
?>