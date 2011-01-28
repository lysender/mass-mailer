<?php

require_once dirname(__FILE__).'/lib/config.php';
require_once DOCROOT.'lib/mailqueue.php';
require_once DOCROOT.'lib/mailswift.php';

$queue = new MailQueue;

set_time_limit(60);

for ($x = 0; $x < 5; $x++)
{
	$queue->run();
	
	sleep(5);
}