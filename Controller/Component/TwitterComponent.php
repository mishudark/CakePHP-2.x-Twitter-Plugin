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
App::import('Vendor', 'Twitter.HttpSocketOauth');
App::uses('CakeSession', 'Model/Datasource');
App::uses('Twitter', 'Twitter.Utility');

class TwitterComponent extends Component {

/**
 * Controller instance
 *
 * @var Controller
 */
	protected $_controller = null;

/**
 * Twitter Utility instance
 *
 * @var Twitter
 */
	protected $_Twitter = null;

	public function __call($method, $params) {
		return call_user_func_array(array($this->_Twitter, $method), $params);
	}

/**
 * Controller before setup - Initialized before the controllers beforeFilter()
 */
	public function initialize(Controller $controller, $settings = array()) {
		$this->_controller = $controller;
		$this->_Twitter = new Twitter($settings);
	}

}