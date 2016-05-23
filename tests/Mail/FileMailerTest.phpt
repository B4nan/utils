<?php

namespace B4nan\Tests\Mail;

use B4nan\Mail\FileMailer;
use B4nan\Tests\TestCase;
use Nette\Mail\Message;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * File Mailer test
 *
 * @testCase
 * @author Martin Adámek <adamek@bargency.com>
 */
class FileMailerTest extends TestCase
{

	/** @var FileMailer */
	private $mailer;

	public function setUp()
	{
		$this->mailer = new FileMailer(TEMP_DIR . '/mails');
	}

	public function testSender()
	{
		$m = new Message;
		$m->setFrom('from@mail.com');
		$m->setBody('body');
		$file = $this->mailer->send($m);
		Assert::true(file_exists($file) && is_file($file));
		Assert::contains('from@mail.com', file_get_contents($file));
	}

}

// run test
run(new FileMailerTest);
