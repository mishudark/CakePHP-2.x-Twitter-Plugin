<?php
/**
 * TwitterComponent.php - The main component file
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

App::uses('Twitter', 'Twitter.Utility');

class TwitterBehavior extends ModelBehavior {

/**
* Model instance
* @var Twitter
*/
	protected $_Model;

/**
* Twitter utility instance
* @var Twitter
*/
	protected $_Twitter;

/**
* Model before setup - Initialized before the controllers beforeFilter()
*/
	public function setup(Model $Model, $settings = array()) {
		$this->_Model = $Model;
		$this->_Twitter = new Twitter($settings);
	}

/*--- Begin Proxy methods for Utility/Twitter ---*/

	public function authorizeTwitterUser($Model, $oauthToken, $oauthVerifier) {
		return $this->_Twitter->authorizeTwitterUser($oauthToken, $oauthVerifier);
	}

	public function loginTwitterUser($Model, $oauthToken, $oauthTokenSecret, $userId = null, $screenName = null) {
		return $this->_Twitter->loginTwitterUser($oauthToken, $oauthTokenSecret, $userId, $screenName);
	}

	public function getTwitterUser($Model, $show_full_profile = false) {
		return $this->_Twitter->getTwitterUser($show_full_profile);
	}

	public function appStatus($Model) {
		return $this->_Twitter->appStatus();
	}

	public function userStatus($Model) {
		return $this->_Twitter->userStatus();
	}

	public function status($Model) {
		return $this->_Twitter->status();
	}

	public function apiRequest($Model, $method, $twitterMethodUrl, $body) {
		return $this->_Twitter->apiRequest($method, $twitterMethodUrl, $body);
	}

	public function accountVerifyCredentials($Model) {
		return $this->_Twitter->accountVerifyCredentials();
	}

	public function accountRateLimitStatus($Model) {
		return $this->_Twitter->accountRateLimitStatus();
	}

	public function getDirectMessagesSent($Model) {
		return $this->_Twitter->getDirectMessagesSent();
	}

	public function newDirectMessage($Model, $screenName, $text) {
		return $this->_Twitter->newDirectMessage($screenName, $text);
	}

	public function getDirectMessages($Model) {
		return $this->_Twitter->getDirectMessages();
	}

	public function destroyDirectMessage($Model, $id) {
		return $this->_Twitter->destroyDirectMessage($id);
	}

	public function newFavorite($Model, $id) {
		return $this->_Twitter->newFavorite($id);
	}

	public function getFriendsIds($Model, $screenName) {
		return $this->_Twitter->getFriendsIds($screenName);
	}

	public function getFollowersIds($Model, $screenName) {
		return $this->_Twitter->getFollowersIds($screenName);
	}

	public function createFriendship($Model, $screenName) {
		return $this->_Twitter->createFriendship($screenName);
	}

	public function createFriendshipById($Model, $id) {
		return $this->_Twitter->createFriendshipById($id);
	}

	public function destroyFriendship($Model, $screenName) {
		return $this->_Twitter->destroyFriendship($screenName);
	}

	public function destroyFriendshipById($Model, $id) {
		return $this->_Twitter->destroyFriendshipById($id);
	}

	public function friendshipExists($Model, $user_a, $user_b) {
		return $this->_Twitter->friendshipExists($user_a, $user_b);
	}

	public function showStatus($Model, $id) {
		return $this->_Twitter->showStatus($id);
	}

	public function updateStatus($Model, $status) {
		return $this->_Twitter->updateStatus($status);
	}

	public function destroyStatus($Model, $id) {
		return $this->_Twitter->destroyStatus($id);
	}

	public function retweetStatus($Model, $status) {
		return $this->_Twitter->retweetStatus($status);
	}

	public function publicTimeline($Model) {
		return $this->_Twitter->publicTimeline();
	}

	public function friendsTimeline($Model) {
		return $this->_Twitter->friendsTimeline();
	}

	public function homeTimeline($Model) {
		return $this->_Twitter->homeTimeline();
	}

	public function userTimeline($Model, $param = null) {
		return $this->_Twitter->userTimeline($param);
	}

	public function mentionsTimeline($Model) {
		return $this->_Twitter->mentionsTimeline();
	}

	public function showUser($Model, $param) {
		return $this->_Twitter->showUser($param);
	}

	public function setupApp($Model, $consumerKey, $consumerSecret) {
		return $this->_Twitter->setupApp($consumerKey, $consumerSecret);
	}

	public function setToken($Model, $oauth_token, $oauth_token_secret) {
		return $this->_Twitter->setToken($oauth_token, $oauth_token_secret);
	}

	public function signIn($Model, $callbackUrl) {
		return $this->_Twitter->signIn($callbackUrl);
	}

	public function logoutTwitterUser($Model) {
		return $this->_Twitter->logoutTwitterUser();
	}

	public function relatedResults($Model, $tweetId) {
		return $this->_Twitter->relatedResults($tweetId);
	}

/*--- End Proxy methods for Utility/Twitter ---*/

}