<?php

namespace B4nan\Tests\Libraries\Forms;

use B4nan\Tests\TestCase;
use B4nan\Utils\Common;
use Tester\Assert;
use Nette\Utils\FileSystem;

require __DIR__ . '/../bootstrap.php';

/**
 * Common utils class test
 *
 * @testCase
 * @author Martin Adámek <adamek@bargency.com>
 */
class CommonTest extends TestCase
{

	private $temp;

	public function setUp()
	{
		$this->temp = TEMP_DIR . '/' . md5(microtime());
		FileSystem::delete($this->temp);
	}

	public function testTransliteration()
	{
		$sentence = 'Примечание';
		$expected = 'Primechanie';
		$result = Common::cyrillicToLatin($sentence);
		Assert::equal($expected, $result);
	}

	public function testAction2Path()
	{
		$sentence = 'viewSuperLongAction';
		$expected = 'view-super-long-action';
		$result = Common::action2path($sentence);
		Assert::equal($expected, $result);
		$sentence = 'EShop:Orders';
		$expected = 'e-shop.orders';
		$result = Common::action2path($sentence);
		Assert::equal($expected, $result);
	}

	public function testPath2Action()
	{
		$sentence = 'view-super-long-action';
		$expected = 'viewSuperLongAction';
		$result = Common::path2action($sentence);
		Assert::equal($expected, $result);
	}

	public function testPrepareFilename()
	{
		// filename only
		$sentence = 'nonExisting FILE.html';
		$expected = 'nonexisting_file.html';
		$result = Common::prepareFilename($sentence);
		Assert::equal($expected, $result);

		// filename and path
		$result = Common::prepareFilename($sentence, $this->temp);
		Assert::equal($expected, $result);
		$result = Common::prepareFilename($sentence, $this->temp . '/');
		Assert::equal($expected, $result);
	}

	public function testRemoveNonEmptyDirectory()
	{
		$folder = $this->temp . '/a';
		FileSystem::createDir($folder);
		FileSystem::createDir($folder . '/a');
		file_put_contents($folder . '/a.a', 'asd');
		file_put_contents($folder . '/b.a', 'asd');
		file_put_contents($folder . '/a/a.a', 'asd');
		FileSystem::delete($folder);

		Assert::false(file_exists($folder));
		Assert::false(is_dir($folder));
	}

	public function testGetUniqueFilenameForExistingFile()
	{
		FileSystem::createDir($this->temp);
		$existing = realpath($this->temp) . '/existing_file.html';
		file_put_contents($existing, 'asd');
		$result = Common::getUniqueFilename($existing, '_');
		Assert::equal('existing_file_1.html', $result);
	}

	public function testGetUniqueFilenameForExistingFiles()
	{
		FileSystem::createDir($this->temp);
		$existing = realpath($this->temp) . '/existing_file.html';
		file_put_contents($existing, 'asd');
		$existing1 = realpath($this->temp) . '/existing_file1.html';
		file_put_contents($existing1, 'asd');
		$result = Common::getUniqueFilename($existing);
		Assert::equal('existing_file2.html', $result);
	}

	public function testEncodeAndDecode()
	{
		$salt = 'T12.asd.21;t';
		$expected = 'PWNUWjFBRFp5WXpZM0VEVnhJakxoTkhadUlUTTdRWE13TUdPMElqTmlSVFk=';
		$encoded = Common::encode($salt);
		Assert::same($expected, $encoded);
		$decoded = Common::decode($encoded);
		Assert::same($salt, $decoded);
	}

	public function testVocative()
	{
		Assert::same('Naomi', Common::czechVocative('Naomi'));
		Assert::same('Marie', Common::czechVocative('Marie'));
		Assert::same('Danielo', Common::czechVocative('Daniela'));
		Assert::same('Danieli', Common::czechVocative('Daniel'));
		Assert::same('Elleno', Common::czechVocative('Ellen'));
		Assert::same('Roberte', Common::czechVocative('Robert'));
		Assert::same('Richarde', Common::czechVocative('Richard'));
		Assert::same('Matěji', Common::czechVocative('Matěj'));
		Assert::same('Pepo', Common::czechVocative('Pepa'));
		Assert::same('Petře', Common::czechVocative('Petr'));
		Assert::same('Igore', Common::czechVocative('Igor'));
		Assert::same('Radime', Common::czechVocative('Radim'));
		Assert::same('Milane', Common::czechVocative('Milan'));
		Assert::same('Davide', Common::czechVocative('David'));
		Assert::same('Karle', Common::czechVocative('Karel'));
		Assert::same('Zdeňku', Common::czechVocative('Zdeněk'));
		Assert::same('Eliško', Common::czechVocative('Eliška'));
		Assert::same('Patriku', Common::czechVocative('Patrik'));
		Assert::same('Matouši', Common::czechVocative('Matouš'));
		Assert::same('Anastazie', Common::czechVocative('Anastazia'));
		Assert::same('Bille', Common::czechVocative('Bill'));
		Assert::same('Radku', Common::czechVocative('Radek'));
		Assert::same('Vítku', Common::czechVocative('Vítek'));
		Assert::same('Víte', Common::czechVocative('Vít'));
		Assert::same('Martino', Common::czechVocative('Martina'));
		Assert::same('Dagmar', Common::czechVocative('Dagmar'));
		Assert::same('Ondřeji', Common::czechVocative('Ondřej'));
	}

	public function testReplaceLinks()
	{
		Assert::same('<a href="http://www.google.com">www.google.com</a>',
			Common::replaceLinks('www.google.com'));
		Assert::same('asd dsa asd asd asd asd asd asd <a href="http://www.google.com">www.google.com</a> asd',
			Common::replaceLinks('asd dsa asd asd asd asd asd asd www.google.com asd'));
		Assert::same('<i>asd</i> <a href="http://www.google.com">www.google.com</a> <b>asd</b>',
			Common::replaceLinks('<i>asd</i> www.google.com <b>asd</b>'));
		Assert::same('<a href="http://google.com">google.com</a>',
			Common::replaceLinks('<a href="http://google.com">google.com</a>'));
		Assert::same('<a href="mailto:test@gmail.com">test@gmail.com</a>',
			Common::replaceLinks('test@gmail.com'));
		Assert::same('asd <b>dsa</b> asd asd asd asd <b>dsa</b> asd asd asd <a href="mailto:test@gmail.com">test@gmail.com</a> asd',
			Common::replaceLinks('asd <b>dsa</b> asd asd asd asd <b>dsa</b> asd asd asd test@gmail.com asd'));
		Assert::same('asd <a href="mailto:test@gmail.com">test@gmail.com</a> asd',
			Common::replaceLinks('asd test@gmail.com asd'));
		Assert::same('<a href="mailto:test@gmail.com">test@gmail.com</a> asd asd asd asd asd asd asd adsa sdasdasd',
			Common::replaceLinks('test@gmail.com asd asd asd asd asd asd asd adsa sdasdasd'));
		Assert::same('<a href="mailto:test@gmail.com">test@gmail.com</a> asd asd asd asd asd asd asd adsa sdasdasd',
			Common::replaceLinks('test@gmail.com asd asd asd asd asd asd asd adsa sdasdasd'));
		Assert::same('<a href="mailto:test@gmail.com">test@gmail.com</a>',
			Common::replaceLinks('<a href="mailto:test@gmail.com">test@gmail.com</a>'));
		Assert::same('asd <b>dsa</b> asd asd asd asd asd asd <a href="mailto:test@gmail.com">test@gmail.com</a> das',
			Common::replaceLinks('asd <b>dsa</b> asd asd asd asd asd asd <a href="mailto:test@gmail.com">test@gmail.com</a> das'));
	}

	public function testCropImage()
	{
		$success = Common::cropImage('a.png', 'b.png', 100, 100, array(50, 50, 150, 150));
		Assert::false($success);

		$from = __DIR__ . '/img.png';
		$to = TEMP_DIR . '/out.png';
		$success = Common::cropImage($from, $to, 100, 100, array(50, 50, 150, 150));
		Assert::true($success);
		// different versions of gd produces different hex code for same images, disabled for CI tests
//		Assert::same(file_get_contents($to), file_get_contents(__DIR__ . '/img.expected.png'));
		Assert::equal(array(
			0 => 100,
			1 => 100,
			2 => 3,
			3 => 'width="100" height="100"',
			'bits' => 8,
			'mime' => "image/png",
		), getimagesize($to));

		$from = __DIR__ . '/img.jpg';
		$to = TEMP_DIR . '/out.jpg';
		$success = Common::cropImage($from, $to, 100, 100, array(50, 50, 150, 150));
		Assert::true($success);
		// different versions of gd produces different hex code for same images, disabled for CI tests
//		Assert::same(file_get_contents($to), file_get_contents(__DIR__ . '/img.expected.jpg'));
		Assert::equal(array(
			0 => 100,
			1 => 100,
			2 => 2,
			3 => 'width="100" height="100"',
			'bits' => 8,
			'channels' => 3,
			'mime' => "image/jpeg",
		), getimagesize($to));
	}

	public function testZipFiles()
	{
		$files = array(
			array(
				'name' => 'CommonTest.phpt',
				'unique' => __FILE__,
			),
		);
		FileSystem::createDir($this->temp);
		$out = $this->temp . '/out.zip';
		Common::zipFiles($files, $out);
		Assert::true(file_exists($out));
	}

	public function testZipDir()
	{
		FileSystem::createDir($this->temp);

		$out = $this->temp . '/out.zip';
		Common::zipDir(__DIR__, $out);
		Assert::true(file_exists($out));
		Common::zipDir(realpath(__DIR__ . '/..'));
		Assert::true(file_exists(__DIR__ . "/../../tests.zip"));

		FileSystem::delete(__DIR__ . "/../../tests.zip");
	}

	public function testUnsetKeys()
	{
		$arr = [
			'foo' => 'a',
			'foo2' => 'b',
			'bar' => 'c',
			'bar1' => 'd',
			'bar2' => 'e',
		];
		Assert::count(5, $arr);
		Common::unsetKeys($arr, ['foo', 'bar']);
		Assert::count(3, $arr);
		Assert::false(isset($arr['foo']));
		Assert::false(isset($arr['bar']));
	}

	public function testDirSize()
	{
		FileSystem::createDir(TEMP_DIR . '/a/b');
		FileSystem::write(TEMP_DIR . '/a/ccc', str_repeat('a', 500));
		FileSystem::write(TEMP_DIR . '/a/b/ccc', str_repeat('a', 250));
		Assert::equal(750, Common::directorySize(TEMP_DIR . '/a'));
		Assert::equal(250, Common::directorySize(TEMP_DIR . '/a/b'));
	}

	public function tearDown()
	{
		FileSystem::delete($this->temp);
	}

}

// run test
run(new CommonTest);
