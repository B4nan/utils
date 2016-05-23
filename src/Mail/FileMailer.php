<?php

namespace B4nan\Mail;

use Nette\Mail\Message;
use Nette\Mail\IMailer;
use Nette\Utils\FileSystem;

/**
 * fake mailer that saves emails to text files
 *
 * @author Martin Adámek <martinadamek59@gmail.com>
 */
class FileMailer implements IMailer
{

	/** @var string path to store emails */
	private $dir;

	/**
	 * @param string $dir
	 */
	public function __construct($dir)
	{
		$this->dir = $dir;
		FileSystem::createDir($dir);
	}

	/**
	 * saves email to log file
	 * @param Message $mail
	 * @return string file name
	 */
	public function send(Message $mail)
	{
		$name = $this->dir . '/' . 'email_' . microtime(TRUE) . '.eml';
		file_put_contents($name, $mail->generateMessage());
		return $name;
	}

}
