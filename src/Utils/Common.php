<?php

namespace B4nan\Utils;

use Nette\Object;
use Nette\Utils\Strings;
use Nette\Utils\Image;
use Nette\Utils\Finder;
use Nette\Utils\FileSystem;
use ZipArchive;

/**
 * common functions
 *
 * @author Martin Adámek <martinadamek59@gmail.com>
 */
final class Common extends Object
{

	/**
	 * encode strings
	 *
	 * @param string $value
	 * @return string
	 */
	public static function encode($value)
	{
		$value = substr(md5($value), 15, 10) . strrev($value) . substr(md5(strrev($value)), 0, 10);
		return base64_encode(strrev(base64_encode($value)));
	}

	/**
	 * encode strings
	 *
	 * @param string $value
	 * @return string
	 */
	public static function decode($value)
	{
		$value = base64_decode(strrev(base64_decode($value)));
		return strrev(substr($value, 10, -10));
	}


	/**
	 * converts czech name in nominative to vocative
	 *
	 * @param string $name
	 * @return string
	 */
	public static function czechVocative($name)
	{
		$last = Strings::substring($name, -1);
		$stripped = Strings::substring($name, 0, -1);
		switch ($last) {
			case 'r':
				if (Strings::endsWith($name, 'or') || Strings::endsWith($name, 'ír')) { // Igor -> Igore
					return $name . 'e';
				}
				if (Strings::endsWith($name, 'ar')) { // Dagmar -> Dagmar
					return $name;
				}
				return $stripped . 'ře'; // Petr -> Petře
			case 'd':
			case 'f':
			case 'm':
			case 't':
			case 'v':
			case 'p':
			case 'b':
			case 'n':
				if (Strings::endsWith($name, 'en')) { // Ellen -> Elleno
					return $name . 'o';
				}
				return $name . 'e'; // Radim -> Radime, Milan -> Milane, Josef -> Josefe
			case 'j': return $name . 'i'; // Ondřej -> Ondřeji
			case 'k':
				if (Strings::endsWith($name, 'něk')) { // Zdeněk -> Zdeňku
					return Strings::substring($name, 0, -3) . 'ňku';
				}
				if (Strings::endsWith($name, 'ek')) { // Vítek -> Vítku
					return Strings::substring($name, 0, -2) . 'ku';
				}
				return $name . 'u'; // Radim -> Radime, Milan -> Milane
			case 'l':
				if (Strings::endsWith($name, 'iel') || Strings::endsWith($name, 'cel')) { // Daniel -> Danieli
					return Strings::substring($name, 0, -1) . 'li';
				}
				if (Strings::endsWith($name, 'el')) { // Karel -> Karle
					return Strings::substring($name, 0, -2) . 'le';
				}
				return $name . 'e'; // Bill -> Bille
			case 'š': return $stripped . 'ši'; // Matouš -> Matouši
			case 'a':
				if (Strings::endsWith($name, 'ia')) { // Anastazia -> Anastazie
					return Strings::substring($name, 0, -2) . 'ie';
				}
				return $stripped . 'o'; // Martina -> Martino
		}
		return $name;
	}

	/**
	 * finds and replaces all not yet replaced urls and emails with links (and mailto:)
	 * finds also urls without http://, and adds http:// to those
	 *
	 * @see http://daringfireball.net/2010/07/improved_regex_for_matching_urls
	 * @param string $text
	 * @return string
	 */
	public static function replaceLinks($text)
	{
		$regexp = '(.{0,6})(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';
		$text = Strings::replace($text, "@$regexp@i", function($match) {
			if ($match[1] === 'href="') {
				return $match[0];
			} else {
				return "{$match[1]}<a href=\"{$match[2]}\">{$match[2]}</a>";
			}
		});
		// first regexp gets also urls without http://, add http:// to those urls
		$text = Strings::replace($text, "@\<a href=\"www\.@i", '<a href="http://www.');

		// find emails
		if (Strings::length($text) > 22) { // minimal length of <a href="mailto:a@b.c">
			$text = Strings::replace($text, '~([^ ]?)([^ <>]{0,7})(\w[-._\w]*\w@\w[-._\w]*\w\.\w{2,4})~i', function($match) use($text) {
				if ($match[2] === 'mailto:' || $match[1] === '>') {
					return $match[0];
				} else {
					return "<a href=\"mailto:{$match[0]}\">{$match[0]}</a>";
				}
			});
		} else {
			$text = Strings::replace($text, '~(\w[-._\w]*\w@\w[-._\w]*\w\.\w{2,4})~i', function($match) {
				return "<a href=\"mailto:{$match[1]}\">{$match[1]}</a>";
			});
		}
		return $text;
	}

	/**
	 * Převod azbuky na latinku podle GOST 16876-71
	 *
	 * @param string text v azbuce
	 * @return string text v latince
	 * @copyright Jakub Vrána, http://php.vrana.cz/
	 */
	public static function cyrillicToLatin($s)
	{
		return strtr($s, [
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е́' => 'e', 'е' => 'e', 'ё' => 'jo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'jj', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о́' => 'o', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'eh', 'ю' => 'ju', 'я' => 'ja',
			'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е́' => 'E', 'Е' => 'E', 'Ё' => 'JO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I', 'Й' => 'JJ', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О́' => 'O', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'KH', 'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SHH', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'EH', 'Ю' => 'JU', 'Я' => 'JA',
		]);
	}

	/**
	 * covert pascalCase to dashed-text
	 *
	 * @param string $s
	 * @param string $delimiter
	 * @return string
	 */
	public static function action2path($s, $delimiter = '-')
	{
		$s = preg_replace('#(.)(?=[A-Z])#', '$1' . $delimiter, $s);
		$s = str_replace([':', '.' . $delimiter], '.', $s);
		$s = strtolower($s);
		$s = rawurlencode($s);
		return $s;
	}

	/**
	 * covert dashed-text to pascalCase
	 * @param string $s
	 * @return string
	 */
	public static function path2action($s)
	{
		$s = strtolower($s);
		$s = preg_replace('#-(?=[a-z])#', ' ', $s);
		$s = substr(ucwords('x' . $s), 1);
		$s = str_replace(' ', '', $s);
		return $s;
	}

	/**
	 * gets unique filename
	 *
	 * @param string $file path to file
	 * @param string $delimiter delimiter for filename and number suffix
	 * @return string
	 */
	public static function getUniqueFilename($file, $delimiter = '')
	{
		$a = explode('/', $file);
		$file = array_pop($a);
		$dir = implode('/', $a);
		if (file_exists($dir . '/' . $file)) {
			$file = explode('.', $file);
			$ext = array_pop($file);
			$file = implode('.', $file);
			$i = 1;
			$newName = $file . $delimiter . $i;
			while (file_exists($dir . '/' . $newName . '.' . $ext)) {
				$newName = $file . $delimiter . ++$i;
			}
			$file = $newName . '.' . $ext;
		}

		return $file;
	}

	/**
	 * prepare filename to safe form ([a-z_])
	 *
	 * @param string $filename
	 * @param string $path
	 * @param string $delimiter
	 * @return string filename
	 */
	public static function prepareFilename($filename, $path = NULL, $delimiter = '')
	{
		// separate extension
		$a = explode('.', $filename);
		$ext = strtolower(array_pop($a));
		$filename = implode('_', $a);
		$filename = Strings::webalize($filename);
		$filename = str_replace('-', '_', $filename);
		$filename = "$filename.$ext";

		if ($path === NULL) {
			return $filename;
		}

		if (!Strings::endsWith($path, '/')) {
			$path .= '/';
		}

		return self::getUniqueFilename($path . $filename, $delimiter);
	}

	/**
	 * removes directory with all its content recursively
	 *
	 * @param string $path
	 * @return bool success
	 * @deprecated use FileSystem::delete
	 */
	public static function removeDirectory($path)
	{
		FileSystem::delete($path);
		return TRUE;
	}

	/**
	 * creates new directory
	 *
	 * @param string $path
	 * @param int $mode
	 * @param int $mask
	 * @param bool $recursive
	 * @return bool success
	 * @deprecated use FileSystem::createDir
	 */
	public static function createDirectory($path, $mode = 0777, $mask = 0000, $recursive = TRUE)
	{
		FileSystem::createDir($path, $mode);
		return TRUE;
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @param int $width
	 * @param int $height
	 * @param array $crop
	 * @return bool
	 */
	public static function cropImage($from, $to, $width, $height, $crop)
	{
		if (! file_exists($from)) {
			return FALSE;
		}

		$image = Image::fromFile($from);

		// save PNG alpha blending
		$image->alphaBlending(FALSE);
		$image->saveAlpha(TRUE);

		if (is_array($crop) && count($crop) === 4) { // crop according to given values
			$image->crop($crop[0], $crop[1], $crop[2], $crop[3])->resize($width, $height);
		}
		$ext = pathinfo($from, PATHINFO_EXTENSION);
		if (strtolower($ext) !== 'png') {
			$image->sharpen();
		}

		return $image->save($to);
	}

	/**
	 * Zip given files
	 *
	 * @param array $files array with path to each file
	 * @param string $outZipPath Path of output zip file.
	 * @return string output path
	 */
	public static function zipFiles($files, $outZipPath)
	{
		$zip = new ZipArchive;
		$zip->open($outZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

		foreach ($files as $path) {
			$zip->addFile($path['unique'], $path['name']);
		}
		$zip->close();
		return $outZipPath;
	}

	/**
	 * Zip a folder (include itself).
	 *
	 * @param string $sourcePath Path of directory to be zip.
	 * @param string $outZipPath Path of output zip file.
	 */
	public static function zipDir($sourcePath, $outZipPath = NULL)
	{
		$pathInfo = pathInfo($sourcePath);
		$parentPath = $pathInfo['dirname'];
		$dirName = $pathInfo['basename'];

		if (!$outZipPath) {
			$outZipPath = "$parentPath/$dirName.zip";
		}

		$zip = new ZipArchive;
		$zip->open($outZipPath, ZipArchive::CREATE);
		$zip->addEmptyDir($dirName);
		self::zipDirectory($sourcePath, $zip, strlen("$parentPath/"));
		$zip->close();
	}

	/**
	 * Add files and sub-directories in a folder to zip file.
	 *
	 * @param string $folder
	 * @param ZipArchive $zipFile
	 * @param int $exclusiveLength Number of text to be exclusived from the file path.
	 */
	private static function zipDirectory($folder, &$zipFile, $exclusiveLength)
	{
		$handle = opendir($folder);
		while ($f = readdir($handle)) {
			if ($f !== '.' && $f !== '..') {
				$filePath = "$folder/$f";
				// Remove prefix from file path before add to zip.
				$localPath = substr($filePath, $exclusiveLength);
				if (is_file($filePath)) {
					$zipFile->addFile($filePath, $localPath);
				} elseif (is_dir($filePath)) {
					// Add sub-directory.
					$zipFile->addEmptyDir($localPath);
					self::zipDirectory($filePath, $zipFile, $exclusiveLength);
				}
			}
		}
		closedir($handle);
	}

	/**
	 * Get size of dir tree in bytes
	 *
	 * @param string $path
	 * @return int size in bytes
	 */
	public static function directorySize($path)
	{
		$total = 0;
		if (!is_dir($path)) {
			return $total;
		}
		foreach (Finder::find('*')->in($path) as $cpath => $item) {
			if (is_dir($cpath)) {
				$total += self::directorySize($cpath);
			} else {
				$total += $item->getSize();
			}
		}
		return $total;
	}

	/**
	 * Unset specific keys in array/object
	 *
	 * @param mixed $obj
	 * @param array $keys
	 * @return mixed
	 */
	public static function unsetKeys(&$obj, array $keys)
	{
		foreach ($keys as $key) {
			unset($obj[$key]);
		}
		return $obj;
	}

}
