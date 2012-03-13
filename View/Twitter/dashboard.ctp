<?php
if (!empty($status)) {
	// used after account authorization
	echo $this->Html->link($status['text'], 'http://twitter.com/'.$status['user']['screen_name'], array('target' => '_blank')); 
} ?>