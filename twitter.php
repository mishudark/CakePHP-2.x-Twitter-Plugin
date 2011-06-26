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
		 * @access private
		 * @var string $consumer_key The OAuth consumer key
		 * @var string $consumer_secret The OAuth consumer secret
		 */ 
		private $consumer_key, $consumer_secret;
		
		//============OAuth setup and connect
		
		/*
		 * The OAuthConfig Class and class var
		 * 
		 * @access private
		 * @var private $Oauth The OAuthSocket class var
		 */
		private $Oauth;
		
		/*
		 * Set the counsumer key and consumer secret for the app. Method should be called at startup and
		 * before connect(). All data will be saved in a cookie. 
		 * 
		 * @access public
		 * @param string $consumer_key OAuth consumer key of the Twitter app
		 * @param string $consumer_secret OAuth consumer secret of Twitter app
		 * @param bool $cookie (TRUE or FALSE) Save the keys as cookie (true) or not (false) 
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
		  * Change the local consumer key and consumer secret after the setup() and manuell
		  * 
		  * @access public
		  * @param string $consumer_key The consumer key for your Twitter app
		  * @param string $consumer_secret The consumer secret for your Twitter app
		  * @param bool $cookie TRUE: The keys are stored in a local CakeCookie 
		  * FALSE: The keys are just stored in a local session and not in a CakeCookie
		  */
		 public function setOauthConsumerKeys($consumer_key, $consumer_secret, $cookie) {
		 	//Update / set the local class vars
		 	$this->consumer_key = $consumer_key;
			$this->consumer_secret = $consumer_secret;
			//Content for session and cookie
			$content = array(
				'consumer_key' => $this->consumer_key,
				'consumer_secret' => $this->consumer_secret				
			);
			//Check if cookie is allowed
			if($cookie == true) {
				if(!is_null($this->Cookie->read('Twitter.OAuth.Consumer'))) $this->Cookie->delete('Twitter.OAuth.Consumer');
				$this->Cookie->write('Twitter.OAuth.Consumer', $content, true, '+365 day'); 
			}
			//Setup local session
			$this->Session->write('Twitter.OAuth.Consumer', $content);
		 }
		 
		 /*
		  * Connect app to twitter and let it authorize.
		  * 
		  * @param string $callback Url where Twitter should redirect after authorisation 
		  * (Should be a function in the controller weher $this->Twitter->callback() is called)
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
		 * OAuth token and OAuth token secret
		 * 
		 * @access private 
		 * @var string $oauth_token The user-specific OAuth token
		 * @var string $oauth_token_secret The user-specific OAuth token secret
		 */
		private $oauth_token, $oauth_token_secret;
		
		/*
		 * The twitter callback method. Should be called after connect() in a different controller function.
		 * 
		 * @access public
		 * @param string $oauth_token The token send back by Twitter to the callback url,
		 * @param string $$oauth_vertifier: The vertifier send back by Twitter to the callback url
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
			//print_r($response);
			$this->setOauthUserKeys($response['oauth_token'], $response['oauth_token_secret']);
		}
		
		/*
		 * Set the oauth secret and oauth vertifier and save the in the current session
		 * 
		 * @access public
		 * @param string $oauth_secret The oauth secret 
		 * @param string $oauth_vertifier The oauth vertifier
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
		
		/*
		 * Controller before setup - Initialized before the controllers beforeFilter()
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
						$this->oauth_token = $consumer_session['oauth_token'];
						$this->oauth_token_secret = $consumer_session['oauth_token_secret'];
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
		 * @return array()
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
		
		/*
		 * Make a custom request on the Twitter API
		 * 
		 * @access public
		 * @return JSON or XML
		 * @param string $method The request method (post, delete, get, put)
		 * @param string $twitterMethodUrl The url of the API method (without 'api.twitter.com'), 
		 * e.g. /1/trends.json
		 * @param array() $body The body of the api request. It has to be an valid array()
		 */
		public function apiRequest($method, $twitterMethodUrl, $body) {
			$request = array();
			
			//Method 
			$method = strtoupper($method);
			if($method == 'GET' || $method == 'POST' || $method == 'DELETE' || $method == 'PUT') $request['method'] = $method;
			//URI
			if(substr($twitterMethodUrl, 0, 1) == '/') $twitterMethodUrl = substr($twitterMethodUrl, 1, strlen($twitterMethodUrl));
			$request['uri'] = array(
				'host' => 'api.twitter.com',
				'path' => $twitterMethodUrl
			);
			//Auth
			$request['auth'] = $this->authArray();
			//Body
			if(is_array($body)) {
				$body = array_change_key_case($body);
				//Check if status isset
				if(array_key_exists('status', $body)) {
					if(strlen($body['status']) > 140) $body['status'] = substr($body['status'], 0, 137).'...';
				}
				else if(array_key_exists('text', $body)) {
					if(strlen($body['text']) > 140) $body['text'] = substr($body['text'], 0, 137).'...';
				}
				//Set the request body
				$request['body'] = $body;
			}
			//Return
			return $this->Oauth->request($request);
		}
		
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
			//Request & return
			return json_decode($this->apiRequest('get', '/1/account/verify_credentials.json', ''), true);
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
			//Request & return
			return json_decode($this->apiRequest('get', '/1/account/rate_limit_status.json', ''), true);
		}
		
		#Direct Messages Methods
		//NOTE: To use this methods your app needs 'Read, Write, and Direct messages'-Access
		
		#direct_messages
		/*
		 * Returns a list of the 20 most recent direct messages sent to the authenticating user. 
		 * The XML and JSON versions include detailed information about the sending and recipient users. 
		 * 
		 * @access public
		 * @return array()
		 * @param int $count The count how many messages should be shown (max. 200)
		 * @param int $page Specifies the page of direct messages to retrieve
		 */
		public function getDirectMessages() {
			//Return 
			return json_decode($this->apiRequest('get', '/1/direct_messages.json', ''), true);
		}
		
		#direct_messages/sent
		/*
		 * Returns a list of the 20 most recent direct messages sent by the authenticating user. 
		 * The XML and JSON versions include detailed information about the sending and recipient users.
		 * 
		 * @access public
		 * @return array() 
		 */
		public function directMessagesSent() {
			//Return 
			return json_decode($this->apiRequest('get','/1/direct_messages/sent.json', ''), true);
		}
		
		#direct_messages/new
		/*
		 * Sends a new direct message to the specified user from the authenticating user. 
		 * Requires both the user and text parameters. Request must be a POST. 
		 * Returns the sent message in the requested format when successful.
		 * 
		 * @access public
		 * @return array()
		 * @param string $screen_name The username of the recipient 
		 * (Must be a follower of the authenticating user)
		 * @param text $text The message text. Shouldn't be longer than 140 chars
		 */
		public function newDirectMessage($screen_name, $text) {
			//Request body
			$body = array();
			if($screen_name != '' && $text != '') {
				$body['screen_name'] = strtolower($screen_name);
				$body['text'] = $text;
			}
			//Return and request
			return json_decode($this->apiRequest('post', '/1/direct_messages/new.json', $body), true);
		}
		
		#direct_messages/destroy
		/*
		 * Destroys the direct message specified in the required ID parameter. 
		 * The authenticating user must be the recipient of the specified direct message.
		 * 
		 * @access public
		 * @return array()
		 * @param int $id An unique identifier number of the message.
		 */
		public function destroyDirectMessage($id) {
			//Return 
			return json_decode($this->apiRequest('delete', '/1/direct_messages/destroy/'.$id.'.json', ''), true);
		}
		
		#Status Methods
		
		#Timeline Methods
		
		#statuses/public_timeline.json
		/*
		 * Returns the 20 most recent statuses from non-protected users 
		 * who have set a custom user icon. The public timeline is cached for 60 seconds 
		 * so requesting it more often than that is a waste of resources.
		 * 
		 * @access public
		 * @return array()  
		 */
		public function publicTimeline() {
			//Request & Return
			return json_decode($this->apiRequest('get', '/1/statuses/public_timeline.json', ''), true);	
		}
		//-----
		
		
	 }
?>