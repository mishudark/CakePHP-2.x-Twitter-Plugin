<?php
/**
 * Twitter.php - The main utility file
 *
 * This is a plugin for CakePHP to connect your app with the Twitter API using OAuth.
 * With this plugin it's possible to access the main API methods
 * (such as status updates, timelines or user) of the Twitter API in all of your controllers.
 * You even have the opportunity to make custom API-Calls with this plugin.
 *
 * @author Florian Nitschmann (f.nitschmann@media-n.net)
 * @link www.media-n.net
 * @copyright (c) 2011 media-n (www.media-n.net)
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 *
 * CakePHP 2.x
 * @conttrib mishu.drk@gmail.com
 */
App::import('Vendor', 'Twitter.HttpSocketOauth');
App::uses('CakeSession', 'Model/Datasource');

class Twitter extends Object {

/**
 * Twitter consumer key & consumer secret
 *
 * @access private
 * @var string $consumer_key The OAuth consumer key
 * @var string $consumer_secret The OAuth consumer secret
 */
	private $consumer_key, $consumer_secret, $oauthToken, $oauthTokenSecret;

/**
 * The OAuthConfig Class and class var
 *
 * @access private
 * @var private $Oauth The HttpSocketOauth class var
 */
	private $Oauth;

	public function __construct($settings = array()) {
		parent::__construct();
		$this->initialize($settings);
		$this->consumer_key = Configure::read('Twitter.consumerKey');
		$this->consumer_secret = Configure::read('Twitter.consumerSecret');
	}

/**
 * Setup the counsumer key and consumer secret for the app.
 *
 * @access public
 * @param string $consumer_key OAuth consumer key of the Twitter app
 * @param string $consumer_secret OAuth consumer secret of the Twitter app
 */
	public function setupApp($consumer_key, $consumer_secret) {
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;
		//Cookie content
		$content = array(
			'consumer_key' => $this->consumer_key,
			'consumer_secret' => $this->consumer_secret
		);
		//Check if session isset
		if (!is_null(CakeSession::read('Twitter.Consumer'))) {
			CakeSession::delete('Twitter.Consumer');
		}
		//Write keys in local session store
		CakeSession::write('Twitter.Consumer', $content);
	}

/**
 * Setup the oauth token and oauth token secret for the instance.
 *
 * @access public
 * @param string $consumer_key OAuth consumer key of the Twitter app
 * @param string $consumer_secret OAuth consumer secret of the Twitter app
 */
	public function setToken($token = null, $token_secret = null) {
		$this->oauthToken = $token;
		$this->oauthTokenSecret = $token_secret;;
	}

/**
 * Connect app to twitter and let it authorize through the user.
 *
 * @param string $callback_url Url where Twitter should redirect after authorisation
 * @param string $action action from twitter api
 * @access public
 */
	public function connectApp($callback_url, $action='authorize') {
		$request = array(
			'uri' => array(
				'host' => 'api.twitter.com',
				'path' => '/oauth/request_token',
				),
			'method' => 'GET',
				'auth' => array(
				'method' => 'OAuth',
				'oauth_callback' => $callback_url,
				'oauth_consumer_key' => $this->consumer_key,
				'oauth_consumer_secret' => $this->consumer_secret,
				),
			);
		$response = $this->Oauth->request($request);
		// Redirect user to twitter to authorize application
		parse_str($response, $response);
		header("Location: http://api.twitter.com/oauth/$action?oauth_token={$response['oauth_token']}");
		exit();
	}

/**
 * Authenticate User with their twitter account.
 *
 * @param string $callback_url Url where Twitter should redirect after authentication
 * @access public
 */
	public function signIn($callback_url) {
		$this->connectApp($callback_url, 'authenticate');
	}

/**
 * OAuth token and OAuth token secret (The user vars)
 *
 * @access private
 * @var string $oauth_token The user-specific OAuth token
 * @var string $oauth_token_secret The user-specific OAuth token secret
 */
	private $oauth_token, $oauth_token_secret;


/**
 * The user authorisation, wich should be called after the user was redirected by Twitter
 * to your site again.
 *
 * @access public
 * @param string $oauth_token The token send back by Twitter to the callback url,
 * @param string $$oauth_verifier: The verifier send back by Twitter to the callback url
 */
	public function authorizeTwitterUser($oauth_token, $oauth_verifier) {
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
				'oauth_verifier' => $oauth_verifier,
				),
			);
		// Get the response
		$response = $this->Oauth->request($request);
		parse_str($response, $response);
		// Setup a new Twitter user
		if (isset($response['user_id'])) {
			$this->loginTwitterUser($response['oauth_token'], $response['oauth_token_secret'], $response['user_id'], $response['screen_name']);
		} else {
			$this->log('missing user_id in response');
			return false;
		}
	}

/**
 * Login the user to Twitter with his own and specific OAuth token and secret, if he isn't
 * already.
 *
 * @access public
 * @param string $oauth_secret The oauth secret
 * @param string $oauth_verifier The oauth verifier
 */
	public function loginTwitterUser($oauthToken, $oauthTokenSecret, $userId = null, $screenName = null) {
		if (is_null(CakeSession::read('Twitter.User'))) {
			//Update class vars
			$this->oauthToken = $oauthToken;
			$this->oauthTokenSecret = $oauthTokenSecret;
			//Create new session content
			$newSession = array(
				'oauth_token' => $oauthToken,
				'oauth_token_secret' => $oauthTokenSecret,
				'user_id' => $userId,
				'screen_name' => $screenName,
				);
			//Save session
			CakeSession::write('Twitter.User', $newSession);
		}
	}


/**
 * Return the current oauth token and oauth token secret of the user and make
 * them usable in the controller. Be carefull in usage! (Secret and user-specific informations)
 *
 * @access public
 * @return array()
 * @param boolean $show_full_profile
 */
	public function getTwitterUser($show_full_profile = false) {
		$userKeys = array();
		if ($this->userStatus() == false) {
			$session = CakeSession::read('Twitter.User');
			if (!is_null($session)) {
				$userKeys['oauth_token'] = $session['oauth_token'];
				$userKeys['oauth_token_secret'] = $session['oauth_token_secret'];
			}
		} else {
			$userKeys['oauth_token'] = $this->oauthToken;
			$userKeys['oauth_token_secret'] = $this->oauthTokenSecret;
			}
		if ($show_full_profile == true) {
			$userKeys['profile'] = $this->accountVerifyCredentials();
		}
		return $userKeys;
	}


/**
 * Logout the current Twitter User (destroy Session `Twitter.User`)
 *
 * @access public
 */
	public function logoutTwitterUser() {
		//Set local keys null
		$this->oauthToken = null;
		$this->oauthTokenSecret = null;
		//Destroy session
		if (!is_null(CakeSession::read('Twitter.User'))) CakeSession::delete('Twitter.User');
	}

/**
 * Initialize class
 */
	public function initialize($settings = array()) {
		//Open a new OAuthSocket
		$this->Oauth = new HttpSocketOauth();
		if ($this->status() == false) {
			//Check app status
			if ($this->appStatus() == false) {
				$consumer_session = CakeSession::read('Twitter.Consumer');
				if (!is_null($consumer_session)) {
					$this->oauthToken = !empty($consumer_session['oauth_token']) ? $consumer_session['oauth_token'] : null;
					$this->oauthTokenSecret = !empty($consumer_session['oauth_token_secret']) ? $consumer_session['oauth_token_secret'] : null;
				}
			}
			//Check $oauth_token and $oauth_token_secret
			if ($this->userStatus() == false) {
				//Look for the session
				$oauth_session = CakeSession::read('Twitter.User');
				if (!is_null($oauth_session)) {
					$this->oauthToken = $oauth_session['oauth_token'];
					$this->oauthTokenSecret = $oauth_session['oauth_token_secret'];
				}
			}
		}
	}


/**
 * Status of the app (checks if consumer key and consumer secret are available)
 *
 * @access public
 * @return boolean
 */
	public function appStatus() {
		if ($this->consumer_key != '' && $this->consumer_secret != '') {
			return true;
		}else {
			return false;
		}
	}

/**
 * Status of the Twitter user (Checks if OAuth token and OAuth secret are available)
 *
 * @access public
 * @return boolean
 */
	public function userStatus() {
		if ($this->appStatus() == true) {
			if ($this->oauthToken != '' && $this->oauthTokenSecret != '') {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

/**
 * Status of the whole Twitter connection
 *
 * @access public
 * @return boolean
 */
	public function status() {
		if ($this->appStatus() == true && $this->userStatus() == true) {
			return true;
		} else {
			return false;
		}
	}

/**
 * Return the auth array for the Twitter API methods
 *
 * @access private
 * @return array()
 */
	private function authArray() {
		return array(
			'method' => 'OAuth',
			'oauth_token' => $this->oauthToken,
			'oauth_token_secret' => $this->oauthTokenSecret,
			'oauth_consumer_key' => $this->consumer_key,
			'oauth_consumer_secret' => $this->consumer_secret
			);
	}


//====================Twitter API methods

/**
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
		if ($method == 'GET' || $method == 'POST' || $method == 'DELETE' || $method == 'PUT') {
			$request['method'] = $method;
		}
		//URI
		if (substr($twitterMethodUrl, 0, 1) == '/') {
			$twitterMethodUrl = substr($twitterMethodUrl, 1, strlen($twitterMethodUrl));
		}
		$request['uri'] = array(
			'host' => 'api.twitter.com',
			'path' => $twitterMethodUrl
			);
		//Auth
		$request['auth'] = $this->authArray();
		//Body
		if (is_array($body)) {
			$body = array_change_key_case($body);
			//Check if status isset
			if (array_key_exists('status', $body)) {
				if (strlen($body['status']) > 140) $body['status'] = substr($body['status'], 0, 137).'...';
			} else if (array_key_exists('text', $body)) {
				if (strlen($body['text']) > 140) $body['text'] = substr($body['text'], 0, 137).'...';
			}
			//Set the request body
			$request['body'] = $body;
		}
		//Return
		return $this->Oauth->request($request);
	}

  #Account Methods

/**
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


/**
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



/**
 * Returns a list of the 20 most recent direct messages sent to the authenticating user.
 * The XML and JSON versions include detailed information about the sending and recipient users.
 *
 * NOTE: To use this methods your app needs 'Read, Write, and Direct messages'-Access
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


/**
 * Returns a list of the 20 most recent direct messages sent by the authenticating user.
 * The XML and JSON versions include detailed information about the sending and recipient users.
 *
 * @access public
 * @return array()
 */
	public function getDirectMessagesSent() {
		//Return
		return json_decode($this->apiRequest('get','/1/direct_messages/sent.json', ''), true);
	}

/**
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
		if ($screen_name != '' && $text != '') {
			$body['screen_name'] = strtolower($screen_name);
			$body['text'] = $text;
		}
		//Return and request
		return json_decode($this->apiRequest('post', '/1/direct_messages/new.json', $body), true);
	}


/**
 * Destroys the direct message specified in the required ID parameter.
 * The authenticating user must be the recipient of the specified direct message.
 *
 * @access public
 * @return array()
 * @param int $id An unique identifier number of the message.
 */
	public function destroyDirectMessage($id) {
		//Return
		return json_decode($this->apiRequest('delete', '/1/direct_messages/destroy/' . $id . '.json', ''), true);
	}

/**
 * Add Favorite
 *
 * @link https://dev.twitter.com/docs/api/1/post/favorites/create/%3Aid
 * @access public
 * @return array()
 */
	public function newFavorite($id) {
		//Return and request
		return json_decode($this->apiRequest('post', '/1/favorites/create/' . $id . '.json', ''), true);
	}

/**
 * Returns all ids of the friends form any user
 *
 * @access public
 * @return array()
 * @param string $screen_name The username
 */
	public function getFriendsIds($screen_name) {
		//Request-body
		$body = array();
		$body['screen_name'] = strtolower($screen_name);
		//Return and request
		return json_decode($this->apiRequest('get', '/1/friends/ids.json', $body), true);
	}

/**
 * Returns all ids of the followers from any user
 *
 * @access public
 * @return array()
 * @param string $screen_name The usernam
 */
	public function getFollowersIds($screen_name) {
		//Request-body
		$body = array();
		$body['screen_name'] = strtolower($screen_name);
		//Return and request
		return json_decode($this->apiRequest('get', '/1/followers/ids.json', $body), true);
	}


/**
 * Allows the authenticating users to follow the user specified in the ID parameter.
 * Returns the befriended user in the requested format when successful.
 * Returns a string describing the failure condition when unsuccessful.
 * If you are already friends with the user an HTTP 403 will be returned.
 *
 * @access public
 * @return array()
 * @param string $screen_name The uername of the user to be followed
 */
	public function createFriendship($screen_name) {
		//Check if $screen_name is string or int (ID)
		if (!is_numeric($screen_name)) {
			//Request-body
			$body = array();
			$body['screen_name'] = strtolower($screen_name);
			//Return and request
			return json_decode($this->apiRequest('post', '/1/friendships/create.json', $body), true);
		}
	}

/**
 * Look at createFriendship()
 *
 * @access public
 * @return array()
 * @param int $id The unique identifier of the user
 */
	public function createFriendshipById($id) {
		if (is_numeric($id)) {
			//Request-body
			$body = array();
			$body['user_id'] = $id;
			//Return and request
			return json_decode($this->apiRequest('post', '/1/friendships/create.json', $body), true);
		}
	}


/**
 * Allows the authenticating users to unfollow the user specified in the ID parameter.
 * Returns the unfollowed user in the requested format when successful.
 * Returns a string describing the failure condition when unsuccessful.
 *
 * @access public
 * @retun array()
 * @param string $screen_name The username of the user to unfollow
 */
	public function destroyFriendship($screen_name) {
		//Request-body
		if (!is_numeric($screen_name)) {
			$body = array();
			$body['screen_name'] = strtolower($screen_name);
			//Return and request
			return json_decode($this->apiRequest('post', '/1/friendships/destroy.json', $body), true);
		}
	}


/**
 * Look at destoryFriendship()
 *
 * @access public
 * @return array()
 * @param int $id The unique identifier of the user to unfollow
 */
	public function destroyFriendshipById($id) {
		if (is_numeric($id)) {
			$body = array();
			$body['user_id'] = $id;
			//Return and request
			return json_decode($this->apiRequest('post', '/1/friendships/destroy.json', $body), true);
		}
	}


/**
 * Tests for the existance of friendship between two users.
 * Will return true if user_a follows user_b, otherwise will return false.
 *
 * @access public
 * @return array()
 * @param string $user_a Screen name of user a
 * @param string $user_b Screen name of user b
 */
	public function friendshipExists($user_a, $user_b) {
		//Request-body
		$body = array();
		$body['user_a'] = $user_a;
		$body['user_b'] = $user_b;
		//Return and request
		return json_decode($this->apiRequest('get', '/1/friendships/exists.json', $body), true);
	}


/**
 *Returns a single status, specified by the id parameter below.
 * The status's author will be returned inline.
 *
 * @access public
 * @return array()
 * @param int $id The id of the tweet
 */
	public function showStatus($id) {
		if (is_numeric($id)) {
		//Return and request
			return json_decode($this->apiRequest('get', '/1/statuses/show/' . $id . '.json', ''), true);
		}
	}

/**
 * Updates the authenticating user's status.
 * Requires the status parameter specified below. Request must be a POST.
 * A status update with text identical to the authenticating user's current
 * status will be ignored to prevent duplicates.
 *
 * @access public
 * @return array()
 * @param string $status The text wich should be posted as new status
 */
	public function updateStatus($status, $options = array()) {
		if ($status != null || $status != '') {
			//Request-body
			$body = Set::merge(array(
				'status' => $status
				), $options);

			//Return and request
			return json_decode($this->apiRequest('post', '/1/statuses/update.json', $body), true);
		}
	}

/**
 * Destroys the status specified by the required ID parameter.
 * The authenticating user must be the author of the specified status.
 *
 * @access public
 * @return array()
 * @param int $id The ID of the status wich should be destroyed
 */
	public function destroyStatus($id) {
		if (is_numeric($id)) {
			//Return and request
			return json_decode($this->apiRequest('post', '/1/statuses/destroy/' . $id . '.json', ''), true);
		}
	}

/**
 * Retweet
 *
 * @link https://dev.twitter.com/docs/api/1/post/statuses/retweet/%3Aid
 * @access public
 * @return array()
 * @param string $status The text wich should be posted as new status
 */
	public function retweetStatus($id) {
		if ($id) {
			return json_decode($this->apiRequest('post', '/1/statuses/retweet/' . $id . '.json', ''), true);
		}
	}

/**
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


/**
 * Returns the 20 most recent statuses posted by the authenticating user and that user's friends.
 * This is the equivalent of /timeline/home on the Web.
 *
 * @access public
 * @return array()
 */
	public function friendsTimeline() {
		//Return and request
		return json_decode($this->apiRequest('get', '/1/statuses/friends_timeline.json', ''), true);
	}

/**
 * Returns the 20 most recent statuses, including retweets, posted by the authenticating
 * user and that user's friends. This is the equivalent of /timeline/home on the Web.
 *
 * @access public
 * @return array()
 */
	public function homeTimeline() {
		$body = 'include_rts=1';
		return json_decode($this->apiRequest('get', '/1/statuses/home_timeline.json', $body), true);
	}

/**
 * Returns the 20 most recent statuses posted from the authenticating user.
 * It's also possible to request another user's timeline via the id parameter.
 * This is the equivalent of the Web / page for your own user,
 * or the profile page for a third party.
 *
 * @access public
 * @return array()
 * @param int || string $param The ID or screen name of the user
 */
	public function userTimeline($param = null) {
		// Request-body
		$body = array();
		// Check if $param is numeric
		if (is_numeric($param)) {
			$body['user_id'] = $param;
		} else if (!is_numeric($param)) {
			$body['screen_name'] = strtolower($param);
		} else if ($param == '' || $param == null) {
			// Return homeTimeline if $param is null
			return $this->homeTimeline();
		}
		// Return and request
		return json_decode($this->apiRequest('get', '/1/statuses/user_timeline.json', $body), true);
	}


/**
 * Returns the 20 most recent mentions (status containing @username)
 * for the authenticating user.
 *
 * @access public
 * @return array()
 */
	public function mentionsTimeline() {
		//Return and request
		return json_decode($this->apiRequest('get', '/1/statuses/mentions.json', ''), true);
	}

/**
 * Returns related results (to see conversation)
 * Note: This is undocumented/unsupported Twitter API
 *
 * @link https://dev.twitter.com/discussions/293
 * @link https://groups.google.com/d/msg/twitter-development-talk/zcNK54ojULg/rqmau1XD4HYJ
 */
	public function relatedResults($id) {
		$url = '/1/related_results/show/' . $id . '.json';
		return json_decode($this->apiRequest('get', $url, ''), true);
	}

/**
 * Returns extended information of a given user, specified by ID or screen name
 * as per the required id parameter. The author's most recent status will be returned inline.
 *
 * @access public
 * @return array()
 * @param int || string $param The ID or screen name of the user
 */
	public function showUser($param) {
		//Request-body
		$body = array();
		//Check if $param is numeric
		$url = '/1/users/show.json';
		if (is_numeric($param)) {
			$body['user_id'] = $param;
			$url .= '?user_id=' . $param;
		} else {
			$body['screen_name'] = strtolower($param);
			$url .= '?screen_name=' . urlencode($param);
		}
		//Return and request
		return json_decode($this->apiRequest('get', $url, $body), true);
	}

/**
 * Lookup users
 *
 * https://dev.twitter.com/docs/api/1/get/users/lookup
 *
 * $options:
 *   `user_id` string|array optional
 *   `screen_name` string|array optional
 */
	public function lookupUsers($options) {
		$body = array();
		$url = '/1/users/lookup.json';
		if (!isset($options['screen_name']) && !isset($options['user_id'])) {
			return false;
		}

		if (isset($options['screen_name'])) {
			if (is_array($options['screen_name'])) {
				$screenNames = join(',', $options['screen_name']);
			} else {
				$screenNames = $options['screen_name'];
			}
		}

		if (isset($options['user_id'])) {
			if (is_array($options['user_id'])) {
				$userIds = join(',', $options['user_id']);
			} else {
				$userIds = $options['user_id'];
			}
		}

		if (isset($userIds)) {
			$body['user_id'] = $userIds;
		} elseif (isset($screenNames)) {
			$body['screen_name'] = $screenNames;
		}

		return json_decode($this->apiRequest('get', $url, $body), true);
	}

}