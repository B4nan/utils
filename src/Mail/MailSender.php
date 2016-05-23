<?php

namespace B4nan\Mail;

use Latte\Loaders\StringLoader;
use Nette\Application\LinkGenerator;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\Bridges\ApplicationLatte\UIMacros;

/**
 * @author Martin Adámek <martinadamek59@gmail.com>
 */
class MailSender
{

	/** @var LinkGenerator */
	private $linkGenerator;

	/** @var IMailer */
	private $mailer;

	/** @var ILatteFactory */
	private $latteFactory;

	/**
	 * @param ILatteFactory $lf
	 * @param LinkGenerator $lg
	 * @param IMailer $mailer
	 */
	public function __construct(ILatteFactory $lf, LinkGenerator $lg, IMailer $mailer)
	{
		$this->latteFactory = $lf;
		$this->linkGenerator = $lg;
		$this->mailer = $mailer;
	}

	/**
	 * @param Message $mail
	 * @param string $body
	 * @param array|NULL $params
	 */
	public function send(Message $mail, $body = NULL, array $params = NULL)
	{
		if ($body) {
			$latte = $this->latteFactory->create();
			$latte->setLoader(new StringLoader);
			UIMacros::install($latte->getCompiler());

			$params = (array) $params; // can be NULL
			$params['_control'] = $this->linkGenerator;

			$html = $latte->renderToString($body, $params);
			$mail->setHtmlBody($html);
		}

		$this->mailer->send($mail);
	}

	/**
	 * @return LinkGenerator
	 */
	public function getLinkGenerator()
	{
		return $this->linkGenerator;
	}

}
