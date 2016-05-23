<?php

namespace B4nan\Tests\Templates;

use B4nan\Localization\Translator;
use B4nan\Templates\Helpers;
use B4nan\Tests\TestCase;
use Nette\Application\UI\ITemplateFactory;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.container.php';

/**
 * Helpers test
 *
 * @testCase
 * @author Martin Adámek <adamek@bargency.com>
 * @todo add fit/thumb/cachedImg helpers tests
 */
class HelpersTest extends TestCase
{

	/** @var \B4nan\Templates\Helpers */
	private $helpers;

	public function setUp()
	{
		$this->helpers = $this->container->getByType(Helpers::class);
	}

	public function testRegister()
	{
		$factory = $this->container->getByType(ITemplateFactory::class);
		/** @var Template $tpl */
		$tpl = $factory->createTemplate();
		$filters = $tpl->getLatte()->getFilters();
		Assert::count(38, $filters);
		Assert::false(isset($filters['bool']));
		$this->helpers->register($tpl);
		$filters = $tpl->getLatte()->getFilters();
		Assert::count(58, $filters);
		Assert::true(isset($filters['bool']));
	}

	public function testGetThumbName()
	{
		$path = __DIR__ . '/../Utils/img.png';
		$t = time();
		Assert::same("img_50x20-$t.png", $this->helpers->getThumbName($path, 50, 20, $t));
		Assert::same("img_150x120-$t.png", $this->helpers->getThumbName($path, 150, 120, $t));
		Assert::same("img_500x200-$t.png", $this->helpers->getThumbName($path, 500, 200, $t));
	}

	public function testImageOffset()
	{
		$path = __DIR__ . '/../Utils/img.png';
		copy($path, TEMP_DIR . '/img/img.png');
		Assert::same('', $this->helpers->imageOffset('/not_exists.png', 50, 20));
		Assert::same('padding: 0px 15px;', $this->helpers->imageOffset('/img.png', 50, 20));
		Assert::same('padding: 0px 65px;', $this->helpers->imageOffset('/img.png', 150, 20));
		Assert::same('padding: 50px 0px;', $this->helpers->imageOffset('/img.png', 20, 120));
	}

	public function testImageDimensions()
	{
		$path = __DIR__ . '/../Utils/img.png';
		copy($path, TEMP_DIR . '/img/img.png');
		$file = new \stdClass;
		$file->path = 'not_exists.png';
		Assert::same('0x0', $this->helpers->imageDimensions($file));
		$file->path = 'img.png';
		Assert::same('200x200', $this->helpers->imageDimensions($file));
	}

	public function testWrapWithLink()
	{
		Assert::type(Html::class, $this->helpers->wrapWithLink('url'));
	}

	public function testBool()
	{
		Assert::same('Yes', $this->helpers->bool(1));
		Assert::same('Yes', $this->helpers->bool(5));
		Assert::same('No', $this->helpers->bool(0));
		Assert::same('No', $this->helpers->bool(FALSE));
	}

	public function testWrapParagraph()
	{
		Assert::same('<p>asd aosd</p>', $this->helpers->wrapParagraph('asd aosd'));
		Assert::same('<p>asd aosd</p>', $this->helpers->wrapParagraph('<p>asd aosd</p>'));
	}

	public function dataTruncate()
	{
		return [
			[
				'nahrávám aktuální', 20,
				'nahrávám aktuální',
			],
			[
				'nahrávám aktuální psd je tam upravený publikační box', 20,
				'nahrávám aktuální p…',
			],
			[
				'nahrávám&nbsp;aktuální&nbsp;psd&nbsp;je&nbsp;tam&nbsp;upravený publikační box', 40,
				'nahrávám&nbsp;aktuální&nbsp;psd&nbsp;je&nbsp;tam&nbsp;upravený p…',
			],
			[
				'nahrávám aktuální psd&nbsp;je tam upravený publikační box', 40,
				'nahrávám aktuální psd&nbsp;je tam upravený p…',
			],
			[
				'<b>nahrávám</b> <i>aktuální <strong>psd&nbsp;je</strong></i> tam <em>upravený publikační</em> box', 40,
				'<b>nahrávám</b> <i>aktuální <strong>psd&nbsp;je</strong></i> tam <em>upravený p</em>…',
			],
			[
				'<b>nahrávám</b> <i>aktuální <strong>psd&nbsp;je</strong></i> tam upravený publikační <b>box</b>', 40,
				'<b>nahrávám</b> <i>aktuální <strong>psd&nbsp;je</strong></i> tam upravený p…',
			],
			[
				'<b>nahrávám</b> <i>aktuální <strong>psd&nbsp;je</strong></i> <i>tam <em><b>upravený publikační</b> box</em> asd </i> asdasd</i>', 40,
				'<b>nahrávám</b> <i>aktuální <strong>psd&nbsp;je</strong></i> <i>tam <em><b>upravený p</b></em></i>…',
			],
			[
				'<b>nahrávám</b> <i>aktuální <strong>psd&nbsp;je</strong></i> tam <em>upravený</em> publikační box', 40,
				'<b>nahrávám</b> <i>aktuální <strong>psd&nbsp;je</strong></i> tam <em>upravený</em> p…',
			],
			[
				'<a href="http://google.cz/?a=123&b=321">nahrávám</a> <i>aktuální <strong>psd&nbsp;je</strong></i> tam <em>upravený</em> publikační box', 40,
				'<a href="http://google.cz/?a=123&b=321">nahrávám</a> <i>aktuální <strong>psd&nbsp;je</strong></i> tam <em>upravený</em> p…',
			],
		];
	}

	/**
	 * @dataProvider dataTruncate
	 */
	public function testTruncate($string, $limit, $expected)
	{
		$result = $this->helpers->truncate($string, $limit);
		Assert::equal($expected, $result);
	}

	public function testDayOfWeek()
	{
		Assert::equal('Monday', $this->helpers->dayOfWeek(1));
		Assert::equal('Monday', $this->helpers->dayOfWeek(8));
		Assert::equal('Tuesday', $this->helpers->dayOfWeek(2));
		Assert::equal('Wednesday', $this->helpers->dayOfWeek(3));
		Assert::equal('Thursday', $this->helpers->dayOfWeek(4));
		Assert::equal('Friday', $this->helpers->dayOfWeek(5));
		Assert::equal('Saturday', $this->helpers->dayOfWeek(6));
		Assert::equal('Sunday', $this->helpers->dayOfWeek(7));
	}

	public function testPhone()
	{
		Assert::equal('+420 123 321 123', $this->helpers->phone('00420123321123'));
		Assert::equal('+420 123 321 123', $this->helpers->phone('+420123321123'));
		Assert::equal('+420 123 321 123', $this->helpers->phone('+420123 321 123'));
		Assert::equal('+420 123 321 123', $this->helpers->phone('+420 123321123'));
		Assert::equal('+420 123 321 123', $this->helpers->phone('+420 123 321 123'));
		Assert::equal('+420 123 32 1 12 3', $this->helpers->phone('+420 123 32 1 12 3'));
	}

	public function testZipCode()
	{
		Assert::equal('420 12', $this->helpers->zipCode('42012'));
		Assert::equal('2312', $this->helpers->zipCode('2312'));
	}

	public function testNumber()
	{
		Assert::equal('12 302 123 123', $this->helpers->number(12302123123.23132));
		Assert::equal('12 302 123 123.23', $this->helpers->number(12302123123.23132, 2));
		Assert::equal('12 302 123 123.23132', $this->helpers->number(12302123123.23132, 5));
	}

	public function dataTimeAgoInWords()
	{
		$time = new DateTime;
		return [
			[FALSE, FALSE],
			[NULL, FALSE],
			[time(), 'just now'],
			['now', 'just now'],
			[$time, 'just now'],
			[$time->modifyClone('+ 45 seconds'), 'in a minute'],
			[$time->modifyClone('- 45 seconds'), 'a minute ago'],
			[$time->modifyClone('+ 145 seconds'), 'in 2 minutes'],
			[$time->modifyClone('- 145 seconds'), '2 minutes ago'],
			[$time->modifyClone('+ 50 minutes'), 'in an hour'],
			[$time->modifyClone('- 50 minutes'), 'an hour ago'],
			[$time->modifyClone('+ 5 hours'), 'in 5 hours'],
			[$time->modifyClone('- 5 hours'), '5 hours ago'],
			[$time->modifyClone('+ 24 hours'), 'tomorrow'],
			[$time->modifyClone('- 24 hours'), 'yesterday'],
			[$time->modifyClone('+ 4 days'), 'in 4 days'],
			[$time->modifyClone('- 4 days'), '4 days ago'],
			[$time->modifyClone('+ 1 month'), 'in a month'],
			[$time->modifyClone('- 1 month'), 'a month ago'],
			[$time->modifyClone('+ 3 months'), 'in 3 months'],
			[$time->modifyClone('- 3 months'), '3 months ago'],
			[$time->modifyClone('+ 1 year'), 'in a year'],
			[$time->modifyClone('- 1 year'), 'a year ago'],
			[$time->modifyClone('+ 20 months'), 'in 2 years'],
			[$time->modifyClone('- 20 months'), '2 years ago'],
			[$time->modifyClone('+ 8 year'), 'in 8 years'],
			[$time->modifyClone('- 8 year'), '8 years ago'],
		];
	}

	/**
	 * @dataProvider dataTimeAgoInWords
	 */
	public function testTimeAgoInWords($time, $expected)
	{
		$result = $this->helpers->timeAgoInWords($time);
		Assert::equal($expected, $result);
	}

	public function dataTimeAgoShort()
	{
		$time = new DateTime;
		return [
			[FALSE, FALSE],
			[NULL, FALSE],
			[time(), 'now'],
			['now', 'now'],
			[$time, 'now'],
			[$time->modifyClone('+ 45 seconds'), FALSE],
			[$time->modifyClone('- 45 seconds'), '1m'],
			[$time->modifyClone('- 145 seconds'), '2m'],
			[$time->modifyClone('- 50 minutes'), '1h'],
			[$time->modifyClone('- 5 hours'), '5h'],
			[$time->modifyClone('- 24 hours'), '1d'],
			[$time->modifyClone('- 4 days'), '4d'],
			[$time->modifyClone('- 1 month'), '1m'],
			[$time->modifyClone('- 3 months'), '3m'],
			[$time->modifyClone('- 1 year'), '1y'],
			[$time->modifyClone('- 20 months'), '2y'],
			[$time->modifyClone('- 8 year'), '8y'],
		];
	}

	/**
	 * @dataProvider dataTimeAgoShort
	 */
	public function testTimeAgoShort($time, $expected)
	{
		$result = $this->helpers->timeAgoShort($time);
		Assert::equal($expected, $result);
	}

	public function testIfTrue()
	{
		Assert::equal('', $this->helpers->ifTrue('def', FALSE));
		Assert::equal('def', $this->helpers->ifTrue('def', TRUE));
	}

	public function testIsodate()
	{
		$d = '2015-10-31T22:31:40+0100';
		Assert::equal($d, $this->helpers->isodate(new \DateTime($d)));
	}

	public function testTdate()
	{
		$d = '2015-10-31T22:31:40+0100';
		Assert::equal('31.10.2015 22:31', $this->helpers->tdate(new \DateTime($d)));
		Assert::equal('31-10-2015', $this->helpers->tdate(new \DateTime($d), '%d-%m-%Y'));
	}

	public function testFormatAmount()
	{
		Assert::same('1 231 231', $this->helpers->formatAmount(1231231));
		Assert::same('1 231 231,31', $this->helpers->formatAmount(1231231.313312, 2));
	}

	public function testVocative()
	{
		Assert::equal('Daniel', $this->helpers->vocative('Daniel'));
		$t = $this->container->getByType(Translator::class);
		$t->setLang('cs');
		Assert::equal('Danieli', $this->helpers->vocative('Daniel'));
	}

	public function testShortName()
	{
		Assert::same('John D.', $this->helpers->shortName('John Doe'));
	}

}

// run test
run(new HelpersTest($container));
