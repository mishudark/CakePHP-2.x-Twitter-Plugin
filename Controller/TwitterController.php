<?php
class TwitterController extends AppController {

	public $name = 'Twitter';
	public $uses = '';
	public $components = array('Twitter.Twitter');
	
/**
 * connect method
 */
	public function connect() {
		$this->Twitter->setupApp('USyRjvOuSvFgakcSy2aUA', 'RzZ6eGSAkyX9glDyFHFNJX1FE26iVV0uunMzdMZkII'); 
		$this->Twitter->connectApp('http://'.$_SERVER['HTTP_HOST'].'/twitter/twitter/authorization');
	}
	
/**
 * authorization method
 */
	public function authorization() { 
		if (!empty($this->request->query['oauth_token']) && !empty($this->request->query['oauth_verifier'])) {
			$this->Twitter->authorizeTwitterUser($this->request->query['oauth_token'], $this->request->query['oauth_verifier']);
			# connect the user to the application
			$this->_connectUser($this->Twitter->getTwitterUser(true), $this->request->query['oauth_verifier'], $this->request->query['oauth_token']);
		}
	}
	
/**
 * dashboard method
 * 
 */
	public function dashboard() {
		$credentialCheck = $this->Twitter->accountVerifyCredentials();
		debug(CakeSession::read());
		debug($credentialCheck);
	}
	
/**
 * Save the user data to the application.
 * 
 * @return null
 * @todo 	Make this model name variable so that anyone using this plugin can easily change the table it saves data to.
 */
	protected function _connectUser($profileData, $token, $verifier) {		
		App::uses('UserConnect', 'Users.Model');
		$UserConnect = new UserConnect;
		$data['UserConnect']['type'] = 'twitter';
		$data['UserConnect']['value'] = serialize(array_merge(array('token' => $token), array('verifier' => $verifier), $profileData));
		
		if ($UserConnect->add($data)) {
			$this->Twitter->updateStatus('I just connected my Zuha website to twitter. @GetZuha');
			$this->Session->setFlash('Test status message sent.');
			$this->redirect(array('action' => 'dashboard'));
		} else {
			throw new Exception(__('no user to tie this twitter account to (probably need to auto create a user on our end'));
		}
	}
}	