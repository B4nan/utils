<?php

namespace B4nan\Tests;

use Nette\DI\Container;
use Tester\Environment;

/**
 * basic test case, with support for running tests in serial mode (for database tests)
 *
 * @author Martin Adámek <martinadamek59@gmail.com>
 */
class TestCase extends \Tester\TestCase
{

	/** @var bool serialize tests? (for database tests) */
	protected static $serial = FALSE;

	/** @var \Nette\DI\Container */
	protected $container;

	/**
	 * @param Container $container
	 */
	public function __construct(Container $container = NULL)
	{
		$this->container = $container;
	}

	public function setUp()
	{
		if (static::$serial) { // serialize report test because of database connection
			Environment::lock('db', dirname(TEMP_DIR));
		}
	}

}
