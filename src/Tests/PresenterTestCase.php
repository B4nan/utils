<?php

namespace B4nan\Tests;

use B4nan\Entities\Role;
use B4nan\Entities\User;
use Nette\Application\IResponse;
use Nette\Application\Request;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\UI\ITemplate;
use Nette\Utils\DateTime;
use Nette\Application\IPresenterFactory;
use Tester\Assert;
use Tester\DomQuery;

/**
 * presenter test case
 *
 * @author Martin Adámek <martinadamek59@gmail.com>
 */
class PresenterTestCase extends TestCase
{

	/** @var \Nette\Application\IPresenter */
	protected $presenter;

	/** @var \Nette\Security\User */
	protected $user;

	/** @var string */
	protected static $presenterName = NULL;

	public function setUp()
	{
		parent::setUp();

		$presenterFactory = $this->container->getByType(IPresenterFactory::class);
		$this->presenter = $presenterFactory->createPresenter(static::$presenterName);
		$this->presenter->autoCanonicalize = FALSE;
		$this->user = $this->container->getByType(\Nette\Security\User::class);
	}

	public function login($roleName)
	{
		$identity = new User;
		$identity->id = 0;
		$identity->email = 'admin@bargency.com';
		$identity->name = 'Name';
		$identity->surname = 'Surname';
		$identity->active = TRUE;
		$identity->registered = new DateTime;
		$identity->lastLogin = new DateTime;
		$identity->lang = 'cs';

		switch ($roleName) {
			case 'admin':
				$identity->id = 1;
				$role = new Role;
				$role->id = 1;
				$role->name = 'administrator';
				$identity->addRole($role); break;
			default:
				$role = new Role;
				$role->id = 1;
				$role->name = $roleName;
				$identity->addRole($role);
		}

		$this->user->login($identity);
	}

	public function request($method, $params, $post = [], $files = [], $flags = [])
	{
		$request = new Request(static::$presenterName, $method, $params, $post, $files, $flags);
		$response = $this->presenter->run($request);
		return $response;
	}

	public function dom($response)
	{
		$html = $response->getSource();
		libxml_use_internal_errors(true);
		$dom = DomQuery::fromHtml($html);
		libxml_clear_errors();
		return $dom;
	}

	public function assertResponse(IResponse $response, $expected = 'Text')
	{
		Assert::type("Nette\\Application\\Responses\\{$expected}Response", $response);

		if ($expected === 'Text') {
			Assert::type(ITemplate::class, $response->getSource());
			Assert::type('string', (string) $response->getSource()); // try to render
		}
	}

	public function assertRedirect(IResponse $response)
	{
		Assert::type(RedirectResponse::class, $response);
	}

}
