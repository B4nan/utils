<?php

namespace B4nan\Tests\Utils;

use B4nan\Tests\TestCase;
use B4nan\Application\Parameters;
use Nette\Utils\ArrayHash;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * parameters test
 *
 * @testCase
 * @author Martin Adámek <adamek@bargency.com>
 */
class ParametersTest extends TestCase
{

	public function testParameters()
	{
		$source = [
			'foo' => 'bar',
			'foo2' => 'bar2',
			'foo3' => 'bar3',
		];
		$p = new Parameters($source);

		Assert::type(ArrayHash::class, $p->getParameters());
		Assert::equal(3, count($p->getParameters()));
		Assert::same('bar2', $p->foo2);
		Assert::same('bar3', $p->foo3);
		Assert::true(isset($p->foo3));
		Assert::false(isset($p->foo4));

		$p->foo4 = 'bar4';
		Assert::true(isset($p->foo4));
		Assert::same('bar4', $p->foo4);
	}

}

// run test
run(new ParametersTest);
