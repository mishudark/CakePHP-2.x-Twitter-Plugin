<?php
class TwitterController extends AppController {

	public $name = 'Twitter';
	public $uses = '';
	public $components = array('Twitter.Twitter');
	
/**
 * connect method
 */
	public function connect() {
		CakeSession::delete('Twitter.User');
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
			try {
				$user = $this->Twitter->getTwitterUser(true);
				$this->_connectUser($user, $this->request->query['oauth_verifier'], $this->request->query['oauth_token']);
				$this->Session->setFlash('Test status message sent.');
				$this->redirect(array('action' => 'dashboard'));
			} catch (Exception $e) {
				$this->Session->setFlash($e->getMessage());
				$this->redirect(array('action' => 'dashboard'));
			}
		} else {
			$this->Session->setFlash('Invalid authorization request.');
			$this->redirect(array('action' => 'dashboard'));
		}
	}
	
/**
 * dashboard method
 * 
 */
	public function dashboard() {
		//CakeSession::delete('Twitter');
		debug($this->Twitter->updateStatus('Logout login test @GetZuha'));
		
		$credentialCheck = $this->Twitter->accountVerifyCredentials();
		debug(CakeSession::read());
		
		
		//CakeSession::write('Twitter.Consumer.consumer_key', 'USyRjvOuSvFgakcSy2aUA');
		//CakeSession::write('Twitter.Consumer.consumer_secret', 'RzZ6eGSAkyX9glDyFHFNJX1FE26iVV0uunMzdMZkII');
		//CakeSession::write('Twitter.User.oauth_token', '18295813-aypALYPJNdSzrpB0ZjADofI7OY4zsrc9M8OMHpB4');
		//CakeSession::write('Twitter.User.oauth_token_secret', 'jk8u1ldc0SnzLDZJrECKzVK08oVEz5cqlqxYn49M');
		//CakeSession::write('Twitter.User.user_id', '18295813');
		//CakeSession::write('Twitter.User.screen_name', 'RazorIT');
		
		
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
			if ($this->Twitter->updateStatus('I just connected my Zuha website to Twitter. @GetZuha')) {
				return true;
			} else {
				throw new Exception(__('test status message failed'));
			}
		} else {
			throw new Exception(__('no user to tie this twitter account to (probably need to auto create a user on our end'));
		}
	}
}	