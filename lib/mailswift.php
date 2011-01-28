<?php

require_once DOCROOT.'lib/vendor/swift/swift_required.php';

/**
 * MaileSwift - SwiftMailer wrapper
 * 
 * SwiftMailer must exist inside APPATH/vendor/swift/
 * 
 */
class MailSwift
{
	/** 
	 * STMP server to connect
	 * 
	 * @var string
	 */
	protected $_smtp_server = 'mail.ourcompany.ph';
	
	/** 
	 * Port number to connect
	 * 
	 * @var int
	 */
	protected $_port = 465;
	
	/** 
	 * Whether or not encryption is used
	 * 
	 * @var boolean
	 */
	protected $_enc = TRUE;
	
	/** 
	 * Encryption type
	 * 
	 * @var string
	 */
	protected $_enc_type = 'tls';
	
	/** 
	 * Username to use when authenticating
	 * 
	 * @var string
	 */
	protected $_username = 'info@ourcompany.ph';
	
	/** 
	 * Password to use when authenticating
	 * 
	 * @var string
	 */
	protected $_password = 'tilapia';
	
	/** 
	 * Default from email address when no from is specified
	 * 
	 * @var mixed
	 */
	protected $_from = 'info@ourcompany.ph';
	
	/** 
	 * SwiftMailer instance
	 * 
	 * @var Swift_Mailer
	 */
	protected $_mailer;
	
	/**
	 * Sends email using the current smtp account
	 * for auto mailer
	 * 
	 * $data possible keys
	 * 		subject
	 * 		recipient
	 * 		body
	 * 		body_type
	 * 		from
	 * 		cc
	 * 		body_part
	 * 		body_part_type
	 * 
	 * @param array $data
	 * @return integer
	 */
	public function send(array $data)
	{
		$mailer = $this->_mailer();
		
		// Create the message
		$message = Swift_Message::newInstance()
		  ->setSubject($data['subject'])
		  ->setTo($data['recipient'])
		  ->setBody($data['body'], $data['body_type']);
		  
		// If no from is defined, use the `from` from config
		$from = NULL;
		
		if (isset($data['from']))
		{
			$from = $data['from'];
		}
		else
		{
			$from = $this->_from;
		}
		
		// Set from
		$message->setFrom($from);
		  
		// If cc is defined, add cc
		if (isset($data['cc']) && !empty($data['cc']))
		{
			$message->setCc($data['cc']);
		}
		
		// If body part is defined, add it
		if (isset($data['body_part']))
		{
			$message->addPart($data['body_part'], $data['body_part_type']);
		}
		
		$failed_recipients = array();
		
		// Send the message
		$result = $mailer->batchSend($message, $failed_recipients);
		
		if ( ! empty($failed_recipients))
		{
			MailQueue::log('FAILED: '.implode(', ', $failed_recipients)."\n");
		}
		
		return TRUE;
	}
	
	/** 
	 * Returns an instance of Swift_Mailer
	 * 
	 * @return Swift_Mailer
	 */
	protected function _mailer()
	{
		if ($this->_mailer === NULL)
		{
			// Always use UTF-8
			Swift_Preferences::getInstance()->setCharset('utf-8');
			
			// Create transport
			$transport = Swift_SmtpTransport::newInstance()
				->setHost($this->_smtp_server)
				->setEncryption($this->_enc_type)
				->setPort($this->_port)
				->setUsername($this->_username)
				->setPassword($this->_password);
			
			// Create mailer
			$this->_mailer = Swift_Mailer::newInstance($transport);
		}
		
		return $this->_mailer;
	}
}
