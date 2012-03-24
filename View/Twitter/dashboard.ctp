<div class="twitter dashboard">
	<h2>Twitter Dashboard</h2>
	<?php
	if (!empty($status)) {
		// used after account authorization
		echo $this->Html->link($this->Html->image($credentialCheck['profile_image_url'], array('alt' => $credentialCheck['screen_name'])), 'http://twitter.com/'.$credentialCheck['screen_name'], array('target' => '_blank', 'escape' => false));
		echo $this->Form->create('Twitter');
		echo $this->Form->input('Twitter.status', array('label' => 'Update Twitter Status'));
		echo $this->Form->end('Submit');
	} 

	if (!empty($reload) && !empty($user)) {
		echo __('Credentials found reload page to check authorization.'); 	
	} else if (!empty($reload)) {
		echo $this->Html->link(__('No credentials found you must authorize.'), array('action' => 'connect'));
	} ?>
</div>
<?php
$this->set('context_menu', array('menus' => array(
	array(
		'heading' => 'Twitter',
		'items' => array(
			$this->Html->link(__('Connect'), array('action' => 'connect')),
			),
		),
	))); ?>