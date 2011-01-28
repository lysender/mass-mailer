<?php

class MailQueue
{
	protected $_recipients;
	protected $_sender = 'info@ourcompany.ph';
	protected $_conn;

	public function __construct()
	{
		// Connect whehter we like it or not
		$this->_conn = mysql_connect(DB_HOST, DB_USER, DB_PASS);
		
		if ( ! $this->_conn)
		{
			die(mysql_error());
		}
		
		// Select database
		if ( ! mysql_select_db(DB_NAME, $this->_conn))
		{
			die('Unable to select database');
		}
	}

	public function run()
	{
		$message = $this->_get_message();
		
		$recipients_rows = $this->_get_recipients();
		$ids = array();
		$emails = array();
		
		if (empty($recipients_rows))
		{
			return FALSE;
		}
		
		foreach ($recipients_rows as $row)
		{
			$ids[] = $row['id'];
			$emails[] = $row['email'];
		}
		
		$this->_recipients = $emails;
		
		if ($this->_send($message[0], $message[1]))
		{
			$this->_mark_recipients($ids);
		}
	}

	protected function _get_message()
	{		
		$subject = file_get_contents(DOCROOT.'subject.txt');
		$message = file_get_contents(DOCROOT.'message.txt');
		
		return array($subject, $message);
	}

	protected function _get_recipients()
	{
		$sql = 'SELECT * FROM `email_queue` WHERE `sent` = 0 LIMIT 10';

		$result = mysql_query($sql, $this->_conn);
		
		if ( ! $result)
		{
			die(mysql_error());
		}
		
		$rows = array();
		
		while ($row = mysql_fetch_assoc($result))
		{
			$rows[] = $row;
		}
		
		return $rows;
	}

	protected function _mark_recipients(array $ids)
	{
		$count = count($ids);
		
		if ($count == 0)
		{
			return FALSE;
		}
		elseif ($count == 1)
		{
			$in_id = reset($ids);
		}
		else 
		{
			$in_id = implode(', ', $ids);	
		}
		
		$sql = 'UPDATE `email_queue` SET `sent` = 1 WHERE `id` IN ('.$in_id.')';
		
		$result = mysql_query($sql, $this->_conn);
		
		if ( ! $result)
		{
			die(mysql_error());
		}
		
		self::log('SENT: '.$in_id."\n");
	}

	protected function _send($subject, $message)
	{
		$mail = new MailSwift;
		
		return $mail->send(array(
			'subject' => $subject,
			'recipient' => $this->_recipients,
			'body' => $message,
			'body_type' => 'text/plain'
		));
	}
	
	public static function log($message)
	{
		$log_file = DOCROOT.'log.txt';
		
		return file_put_contents($log_file, $message, FILE_APPEND);
	}
}
