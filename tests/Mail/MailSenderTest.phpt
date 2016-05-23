<?php

namespace B4nan\Tests\Mail;

use B4nan\Mail\FileMailer;
use B4nan\Mail\MailSender;
use B4nan\Tests\TestCase;
use Nette\Application\LinkGenerator;
use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\Mail\Message;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.container.php';

/**
 * File Mailer test
 *
 * @testCase
 * @author Martin Adámek <adamek@bargency.com>
 */
class MailSenderTest extends TestCase
{

	/** @var MailSender */
	private $sender;

	public function setUp()
	{
		/** @var ILatteFactory $lf */
		$lf = $this->container->getByType(ILatteFactory::class);
		/** @var LinkGenerator $lg */
		$lg = \Mockery::mock(LinkGenerator::class);
		$mailer = new FileMailer(TEMP_DIR);
		$this->sender = new MailSender($lf, $lg, $mailer);
	}

	public function testSender()
	{
		Assert::noError(function() {
			$m = new Message;
			$m->setFrom('from@mail.com');
			$this->sender->send($m, 'body');
		});
	}

	public function testLinkGeneratorGetter()
	{
		Assert::type(LinkGenerator::class, $this->sender->getLinkGenerator());
	}

}

// run test
run(new MailSenderTest($container));
