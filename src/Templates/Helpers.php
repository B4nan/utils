<?php

namespace B4nan\Templates;

use B4nan\Utils\Common;
use B4nan\Application\Parameters;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Http\Request;
use Nette\Utils\Image;
use Nette\Utils\Strings;
use Nette\Security\User;
use Nette\Utils\Html;
use Nette\Localization\ITranslator;
use Nette\Object;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Utils\FileSystem;
use Tracy\Debugger;
use Imagick;

/**
 * Helpers
 *
 * use @register anotation to autowire helpers
 *
 * @author Martin Adámek <martinadamek59@gmail.com>
 */
class Helpers extends Object
{

	/** @var string */
	const CACHE_NAMESPACE = 'B4nan.Templates.Helpers';

	/** @var \Nette\Localization\ITranslator */
	protected $translator;

	/** @var \Latte\Template */
	protected $template;

	/** @var \Nette\Utils\ArrayHash */
	protected $parameters;

	/** @var User */
	protected $user;

	/** @var string */
	private $cacheDir;

	/** @var string */
	private $cacheDirUrl;

	/** @var string */
	private $domainDir;

	/** @var string */
	private $domainDirUrl;

	/** @var Cache */
	private $cache;

	/** @var IStorage */
	private $cacheStorage;

	/** @var Request */
	private $request;

	/**
	 * @param Request $request
	 * @param Parameters $parameters
	 * @param ITranslator $translator
	 * @param IStorage $cacheStorage
	 */
	public function __construct(Request $request, Parameters $parameters, ITranslator $translator, IStorage $cacheStorage)
	{
		$this->request = $request;
		$this->parameters = $parameters;
		$this->translator = $translator;
		$this->cacheStorage = $cacheStorage;

		$cacheDir = $this->parameters->dirs->imageCache;
		$url = $this->request->getUrl();
		$basePath = $url->basePath;
		$isAbsolute = $cacheDir[0] === '/' || $cacheDir[1] === ':';
		$this->cacheDir = $isAbsolute ? $cacheDir : WWW_DIR . "/$cacheDir";
		$this->cacheDirUrl = "$basePath$cacheDir";
		if (isset($this->parameters->dirs->domainDir)) {
			$domainDir = $this->parameters->dirs->domainDir;
			$this->domainDir = $domainDir;
		}
		$this->domainDirUrl = rtrim($url->baseUrl, '/');
		FileSystem::createDir($this->cacheDir);
		return $this;
	}

	/**
	 * register helpers
	 *
	 * @param \Nette\Bridges\ApplicationLatte\Template
	 * @return \Nette\Bridges\ApplicationLatte\Template
	 */
	public function register(Template $template)
	{
		$this->cache = new Cache($this->cacheStorage, self::CACHE_NAMESPACE);

		$methods = $this->cache->load('methods', function() {
			$methods = [];
			foreach ($this->getReflection()->getMethods() as $method) {
				if ($filterName = $method->getAnnotation('register')) {
					$methods[] = [ $filterName, $method->name ];
				}
			}
			return $methods;
		});

		foreach ($methods as $method) {
			$template->addFilter($method[0] === TRUE ? $method[1] : $method[0], $this->{$method[1]});
		}

		if (isset($template->domainDir)) {
			$this->domainDir = $template->domainDir;
		}
		if (isset($template->domainDirUrl)) {
			$this->domainDirUrl = $template->domainDirUrl;
		}

		$this->template = $template;

		return $template;
	}

	/**
	 * wraps string in html paragraph if not already wrapped
	 *
	 * @param $string
	 * @return string
	 * @register
	 */
	public function wrapParagraph($string)
	{
		if (!Strings::startsWith($string, '<p>') && !Strings::endsWith($string, '</p>')) {
			$string = '<p>' . $string . '</p>';
		}

		return $string;
	}

	/**
	 * truncates a html string
	 *
	 * @param string $string
	 * @param int $limit
	 * @param string $suffix
	 * @param bool $exact
	 * @return string
	 * @register
	 */
	public function truncate($string, $limit, $suffix = '…', $exact = TRUE)
	{
		if (Strings::length($string) <= $limit) {
			return $string;
		}

		$printedLength = 0;
		$position = 0;
		$tags = array();
		$ret = '';

		if ($limit >= Strings::length($suffix)) { // count suffix in limit
			$limit -= Strings::length($suffix);
		}

		$re = '{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;|[\x80-\xFF][\x80-\xBF]*}';

		while ($printedLength < $limit && preg_match($re, $string, $match, PREG_OFFSET_CAPTURE, $position)) {
			list($tag, $tagPosition) = $match[0];

			// Print text leading up to the tag.
			$str = substr($string, $position, $tagPosition - $position);
			if ($printedLength + strlen($str) > $limit) {
				$ret .= substr($str, 0, $limit - $printedLength);
				$printedLength = $limit;
				break;
			}

			$ret .= $str;
			$printedLength += strlen($str);
			if ($printedLength >= $limit) {
				break;
			}

			if ($tag[0] == '&' || ord($tag) >= 0x80) {
				// Pass the entity or UTF-8 multi byte sequence through unchanged.
				$ret .= $tag;
				$printedLength++;
			} else {
				// Handle the tag.
				$tagName = $match[1][0];
				if ($tag[1] == '/') {
					// This is a closing tag.
					$openingTag = array_pop($tags);
					assert($openingTag == $tagName); // check that tags are properly nested.
					$ret .= $tag;
				} else if ($tag[strlen($tag) - 2] == '/') {
					// Self-closing tag.
					$ret .= $tag;
				} else {
					// Opening tag.
					$ret .= $tag;
					$tags[] = $tagName;
				}
			}

			// Continue after the tag.
			$position = $tagPosition + strlen($tag);
		}

		// Print any remaining text.
		if ($printedLength < $limit && $position < strlen($string)) {
			$ret .= substr($string, $position, $limit - $printedLength);
		}

		// Close any open tags.
		while (!empty($tags)) {
			$ret .= sprintf('</%s>', array_pop($tags));
		}

		// add the defined ending to the text
		$ret .= $suffix;

		return $ret;
	}

	/**
	 * Converts day in week to name of the day in english
	 *
	 * @param int $dayNum monday is 1
	 * @return string
	 * @register
	 */
	public function dayOfWeek($dayNum)
	{
		$dayNum = ($dayNum - 1) % 7;
		$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
		return isset($days[$dayNum]) ? $days[$dayNum] : '';
	}

	/**
	 * time ago in words
	 *
	 * @param \DateTime $time
	 * @return mixed
	 * @register
	 */
	public function timeAgoInWords($time)
	{
		if (!$time) {
			return FALSE;
		} elseif (is_numeric($time)) {
			$time = (int) $time;
		} elseif ($time instanceof \DateTime) {
			$time = $time->format('U');
		} else {
			$time = strtotime($time);
		}

		$delta = time() - $time;
		$days = abs(date('z') - date('z', $time));
		$t = $this->translator;

		if ($delta < 0) {
			$delta = (int) round(abs($delta) / 60); // cast as int to avoid 0.0
			if ($delta === 1) return $t->translate('in a minute');
			if ($delta < 45) return $t->translate('in %s minutes', $delta);
			if ($delta < 90) return $t->translate('in an hour');
			if ($delta < 1440) return $t->translate('in %s hours', round($delta / 60));
			if ($days === 1 && $delta < 40320) return $t->translate('tomorrow');
			if ($delta < 40320) return $t->translate('in %s days', round($delta / 1440));
			if ($delta < 86400) return $t->translate('in a month');
			if ($delta < 525600) return $t->translate('in %s months', round($delta / 43200));
			if ($delta < 787620) return $t->translate('in a year');
			return $t->translate('in %s years', round($delta / 525960));
		}

		$delta = (int) round($delta / 60); // cast as int to avoid 0.0
		if ($delta === 0) return $t->translate('just now');
		if ($delta === 1) return $t->translate('a minute ago');
		if ($delta < 45) return $t->translate('%s minutes ago', $delta);
		if ($delta < 90) return $t->translate('an hour ago');
		if ($delta < 1440) return $t->translate('%s hours ago', round($delta / 60));
		if ($days === 1 && $delta < 40320) return $t->translate('yesterday');
		if ($delta < 40320) return $t->translate('%s days ago', round($delta / 1440));
		if ($delta < 86400) return $t->translate('a month ago');
		if ($delta < 525600) return $t->translate('%s months ago', round($delta / 43200));
		if ($delta < 787620) return $t->translate('a year ago');
		return $t->translate('%s years ago', round($delta / 525960));
	}

	/**
	 * time ago short
	 *
	 * @param \DateTime $time
	 * @return mixed
	 * @register
	 */
	public function timeAgoShort($time)
	{
		if (!$time) {
			return FALSE;
		} elseif (is_numeric($time)) {
			$time = (int) $time;
		} elseif ($time instanceof \DateTime) {
			$time = $time->format('U');
		} else {
			$time = strtotime($time);
		}

		$delta = time() - $time;
		$days = floor($delta / (60 * 60 * 24));
		$t = $this->translator;

		if ($delta < 0) {
			return FALSE;
		}

		$delta = (int) round($delta / 60); // cast as int to avoid 0.0
		if ($delta === 0) return $t->translate('now');
		if ($delta === 1) return $t->translate('1m');
		if ($delta < 45) return $t->translate('%sm', $delta);
		if ($delta < 90) return $t->translate('1h');
		if ($delta < 1440) return $t->translate('%sh', round($delta / 60));
		if ($days === 1) return $t->translate('1d');
		if ($days < 31) return $t->translate('%sd', $days);
		if ($days < 62) return $t->translate('1m');
		if ($days < 365) return $t->translate('%sm', round($days / 31));
		if ($delta < 787620) return $t->translate('1y');
		return $t->translate('%sy', ceil($delta / 525960));
	}

	/**
	 * creates miniature and returns its URI
	 *
	 * @param $origUrl
	 * @param int $width
	 * @param int|null $height
	 * @param  string absolute uri
	 * @return string
	 * @register thumb
	 */
	public function createImageThumb($origUrl, $width, $height = NULL, $crop = NULL)
	{
		$origName = substr($origUrl, strrpos($origUrl, '/') + 1);
		$origPath = ($this->domainDir ? $this->domainDir : $this->cacheDir) . $origUrl;

		if (!is_file($origPath)) {
			return NULL;
		}

		$a = explode('.', $origPath);
		$ext = strtolower(array_pop($a));

		$thumbName = $this->getThumbName($origName, $width, $height, filemtime($origPath), $crop);

		$origDirectory = explode('/', $origUrl);
		array_pop($origDirectory);
		$origDirectory = implode('/', $origDirectory);

		$name = md5(($this->domainDirUrl ? $this->domainDirUrl : $this->cacheDirUrl) . '/' . $origDirectory . '/thumbs/' . $thumbName) . ".$ext";
		$thumbPath = ($this->domainDir ? $this->domainDir : $this->cacheDir)  . $origDirectory . '/thumbs/' . $name;
		$thumbUrl = ($this->domainDirUrl ? $this->domainDirUrl : $this->cacheDirUrl) . $origDirectory . '/thumbs/' . $name;

		FileSystem::createDir(($this->domainDir ? $this->domainDir : $this->cacheDir) . $origDirectory . '/thumbs/');

		// thumb already exits
		if (is_file($thumbPath)) {
			return $thumbUrl;
		}

		try {
			if (class_exists('Imagick')) {
				$image = new Imagick($origPath);

				if (is_array($crop) && count($crop) === 4) { // crop according to given values
					$image->cropImage($crop[2], $crop[3], $crop[0], $crop[1]);
				}

				if ($image->getImageWidth() > $width || $image->getImageHeight() > $height) {
					if ($height === NULL) {
						$image->thumbnailImage($width, 0);
					} else {
						$image->thumbnailImage($width, $height, TRUE);
					}
				}

				$image->writeImage($thumbPath);
				$image->destroy();
			} else {
				$image = Image::fromFile($origPath);

				// zachovani pruhlednosti u PNG
				$image->alphaBlending(FALSE);
				$image->saveAlpha(TRUE);

				if (is_array($crop) && count($crop) === 4) { // crop according to given values
					$image->crop($crop[0], $crop[1], $crop[2], $crop[3]);
				}

				$image->resize($width, $height, Image::SHRINK_ONLY);

				if ($ext !== 'png') {
					$image->sharpen();
				}

				$image->save($thumbPath);
			}

			return $thumbUrl;
		} catch (\Exception $e) {
			return ($this->domainDirUrl ? $this->domainDirUrl : $this->cacheDirUrl) . $origUrl;
		}
	}

	/**
	 * creates miniature and returns its URI
	 *
	 * @param \Nette\Utils\ArrayHash $file
	 * @param int $width
	 * @param NULL|int $height
	 * @return string uri
	 * @register
	 */
	public function fileThumb($file, $width, $height = NULL)
	{
		$path = realpath($this->parameters->dirs->storage . '/' . $file->path);

		if (!$path) {
			return FALSE;
		}

		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$thumbName = $this->getThumbName($file->name, $width, $height, filemtime($path));

		$name = md5($thumbName) . ".$ext";
		$thumbPath = ($this->domainDir ? $this->domainDir : $this->cacheDir) . "/images/thumbs/$name";
		$thumbUrl = ($this->domainDirUrl ? $this->domainDirUrl : $this->cacheDirUrl) . "/images/thumbs/$name";

		// thumb already exits
		if (is_file($thumbPath)) {
			return $thumbUrl;
		}

		try {
			if (class_exists('Imagick')) {
				$image = new Imagick($path);

				$origWidth = $image->getImageWidth();
				$origHeight = $image->getImageHeight();

				if ($origWidth != $width || $origHeight != $height) {
					$ratioOrig = $origWidth / $origHeight;
					$ratioThumb = $width / $height;

					if ($ratioOrig > $ratioThumb) { // use orig height, crop width
						$image->thumbnailImage(0, $height);
					} else { // use orig width, crop height
						$image->thumbnailImage($width, 0);
					}

					$x = (int)(($image->getImageWidth() - $width) / 2);
					$y = (int)(($image->getImageHeight() - $height) / 2);

					$image->cropImage($width, $height, $x, $y);
				}

				$image->writeImage($thumbPath);
				$image->destroy();
			} else {
				$image = Image::fromFile($path);

				$origWidth = $image->width;
				$origHeight = $image->height;

				// preserve alpha channel in PNG
				$image->alphaBlending(FALSE);
				$image->saveAlpha(TRUE);

				if ($origWidth != $width || $origHeight != $height) {
					$ratioOrig = $origWidth / $origHeight;
					$ratioThumb = $width / $height;

					if ($ratioOrig > $ratioThumb) { // use orig height, crop width
						$image->resize(NULL, $height);
					} else { // use orig width, crop height
						$image->resize($width, NULL);
					}

					$x = (int) (($image->getWidth() - $width) / 2);
					$y = (int) (($image->getHeight() - $height) / 2);

					$image->crop($x, $y, $width, $height);

					if ($ext !== 'png') {
						$image->sharpen();
					}
				}

				$image->save($thumbPath);
			}

			return $thumbUrl;
		} catch (\Exception $e) {
			Debugger::log($e);
			return ($this->domainDirUrl ? $this->domainDirUrl : $this->cacheDirUrl) . '/' . $file->path;
		}
	}

	/**
	 * @param string $url
	 * @param int $width
	 * @param int $height
	 * @param int $crop
	 * @return string
	 * @register
	 */
	public function fitImage($url, $width, $height = NULL, $crop = NULL)
	{
		$origName = substr($url, strrpos($url, '/') + 1);
		$origPath = ($this->domainDir ? $this->domainDir : $this->cacheDir) . $url;

		if (!file_exists($origPath)) {
			return $url;
		}

		$a = explode('.', $origPath);
		$ext = strtolower(array_pop($a));

		$thumbName = $this->getThumbName($origName, $width, $height, filemtime($origPath), $crop);

		$origDirectory = explode('/', $url);
		array_pop($origDirectory);
		$origDirectory = implode('/', $origDirectory);

		$name = md5(($this->domainDirUrl ? $this->domainDirUrl : $this->cacheDirUrl) . '/' . $origDirectory . '/fits/' . $thumbName) . ".$ext";
		$thumbPath = ($this->domainDir ? $this->domainDir : $this->cacheDir)  . $origDirectory . '/fits/' . $name;
		$thumbUrl = ($this->domainDirUrl ? $this->domainDirUrl : $this->cacheDirUrl) . $origDirectory . '/fits/' . $name;

		FileSystem::createDir(($this->domainDir ? $this->domainDir : $this->cacheDir) . $origDirectory . '/fits/');

		// thumb already exits
		if (is_file($thumbPath)) {
			return $thumbUrl;
		}

		try {
			if (class_exists('Imagick')) {
				$image = new Imagick($origPath);

				$origWidth = $image->getImageWidth();
				$origHeight = $image->getImageHeight();

				if (is_array($crop) && count($crop) === 4) { // crop according to given values
					$image->cropImage($crop[2], $crop[3], $crop[0], $crop[1]);
					$image->thumbnailImage($width, $height, TRUE);
				} elseif ($origWidth != $width || $origHeight != $height) {
					$ratioOrig = $origWidth / $origHeight;
					$ratioThumb = $width / $height;

					if ($ratioOrig > $ratioThumb) { // use orig height, crop width
						$image->thumbnailImage(0, $height);
					} else { // use orig width, crop height
						$image->thumbnailImage($width, 0);
					}

					switch ($crop) {
						case 'top':
							$x = (int)(($image->getImageWidth() - $width) / 2);
							$y = 0;
							break;
						case 'bottom':
							$x = (int)(($image->getImageWidth() - $width) / 2);
							$y = (int)($image->getImageHeight() - $height);
							break;
						case 'left':
							$x = 0;
							$y = (int)(($image->getImageHeight() - $height) / 2);
							break;
						case 'right':
							$x = (int)($image->getImageWidth() - $width);
							$y = (int)(($image->getImageHeight() - $height) / 2);
							break;
						case 'center':
						default:
							$x = (int)(($image->getImageWidth() - $width) / 2);
							$y = (int)(($image->getImageHeight() - $height) / 2);
					}

					$image->cropImage($width, $height, $x, $y);
				}

				$image->writeImage($thumbPath);
				$image->destroy();
			} else {
				$image = Image::fromFile($origPath);

				$origWidth = $image->getWidth();
				$origHeight = $image->getHeight();

				// zachovani pruhlednosti u PNG
				$image->alphaBlending(FALSE);
				$image->saveAlpha(TRUE);

				if (is_array($crop) && count($crop) === 4) { // crop according to given values
					$image->crop($crop[0], $crop[1], $crop[2], $crop[3])->resize($width, $height);
				} elseif ($origWidth != $width || $origHeight != $height) {
					$ratioOrig = $origWidth / $origHeight;
					$ratioThumb = $width / $height;

					if ($ratioOrig > $ratioThumb) { // use orig height, crop width
						$image->resize(NULL, $height);
					} else { // use orig width, crop height
						$image->resize($width, NULL);
					}

					switch ($crop) {
						case 'top':
							$x = (int) (($image->getWidth() - $width) / 2);
							$y = 0;
							break;
						case 'bottom':
							$x = (int) (($image->getWidth() - $width) / 2);
							$y = (int) ($image->getHeight() - $height);
							break;
						case 'left':
							$x = 0;
							$y = (int) (($image->getHeight() - $height) / 2);
							break;
						case 'right':
							$x = (int) ($image->getWidth() - $width);
							$y = (int) (($image->getHeight() - $height) / 2);
							break;
						case 'center':
						default:
							$x = (int) (($image->getWidth() - $width) / 2);
							$y = (int) (($image->getHeight() - $height) / 2);
					}

					$image->crop($x, $y, $width, $height);

					if ($ext !== 'png') {
						$image->sharpen();
					}
				}

				$image->save($thumbPath);
			}

			return $thumbUrl;
		} catch (\Exception $e) {
			Debugger::log($e);
			return ($this->domainDirUrl ? $this->domainDirUrl : $this->cacheDirUrl) . $url;
		}
	}

	/**
	 * @param string $url
	 * @param int $width
	 * @param int $height
	 * @param int $crop
	 * @return string
	 * @register fitThumb
	 * @todo refactoring fit/thumb helpers, move common parts of code to separate methods
	 */
	public function fitThumbImage($url, $width, $height, $crop = NULL)
	{
		$origName = substr($url, strrpos($url, '/') + 1);
		$origPath = ($this->domainDir ? $this->domainDir : $this->cacheDir) . $url;

		if (!file_exists($origPath)) {
			return $url;
		}

		$a = explode('.', $origPath);
		$ext = strtolower(array_pop($a));

		$thumbName = $this->getThumbName($origName, $width, $height, filemtime($origPath), $crop);

		$origDirectory = explode('/', $url);
		array_pop($origDirectory);
		$origDirectory = implode('/', $origDirectory);

		$name = md5(($this->domainDirUrl ? $this->domainDirUrl : $this->cacheDirUrl) . '/' . $origDirectory . '/fitThumbs/' . $thumbName) . ".$ext";
		$thumbPath = ($this->domainDir ? $this->domainDir : $this->cacheDir)  . $origDirectory . '/fitThumbs/' . $name;
		$thumbUrl = ($this->domainDirUrl ? $this->domainDirUrl : $this->cacheDirUrl) . $origDirectory . '/fitThumbs/' . $name;

		FileSystem::createDir(($this->domainDir ? $this->domainDir : $this->cacheDir) . $origDirectory . '/fitThumbs/');

		// thumb already exits
		if (is_file($thumbPath)) {
			return $thumbUrl;
		}

		try {
			if (class_exists('Imagick')) {
				$canvas = new \Imagick();
				$canvas->newImage($width, $height, "rgba(0, 0, 0, 0)");
				$image = new Imagick($origPath);

				$origWidth = $image->getImageWidth();
				$origHeight = $image->getImageHeight();

				if ($origWidth > $width || $origHeight > $height) {

					if ($origHeight > $height) { // use orig height, crop width
						$image->resizeImage(0, $height, Imagick::FILTER_LANCZOS, 1);
					} else { // use orig width, crop height
						$image->resizeImage($width, 0, Imagick::FILTER_LANCZOS, 1);
					}

					switch ($crop) {
						case 'top':
							$x = (int)(($image->getImageWidth() - $width) / 2);
							$y = 0;
							break;
						case 'bottom':
							$x = (int)(($image->getImageWidth() - $width) / 2);
							$y = (int)($image->getImageHeight() - $height);
							break;
						case 'left':
							$x = 0;
							$y = (int)(($image->getImageHeight() - $height) / 2);
							break;
						case 'right':
							$x = (int)($image->getImageWidth() - $width);
							$y = (int)(($image->getImageHeight() - $height) / 2);
							break;
						case 'center':
						default:
							$x = (int)(($image->getImageWidth() - $width) / 2);
							$y = (int)(($image->getImageHeight() - $height) / 2);
					}

					$image->cropImage($width, $height, $x, $y);
				}

				$left = $width / 2 - $image->getImageWidth() / 2;
				$top = $height / 2 - $image->getImageHeight() / 2;

				$canvas->compositeImage($image, Imagick::COMPOSITE_ADD, $left, $top);

				$canvas->writeImage($thumbPath);
				$image->destroy();
				$canvas->destroy();
			} else {
				$canvas = Image::fromBlank($width, $height, Image::rgb(0, 0, 0, 127));
				$image = Image::fromFile($origPath);

				$origWidth = $image->getWidth();
				$origHeight = $image->getHeight();

				if ($origWidth > $width || $origHeight > $height) {
					// zachovani pruhlednosti u PNG
					$image->alphaBlending(FALSE);
					$image->saveAlpha(TRUE);

					if ($origHeight > $height) { // use orig height, crop width
						$image->resize(NULL, $height);
					} else { // use orig width, crop height
						$image->resize($width, NULL);
					}

					switch ($crop) {
						case 'top':
							$x = (int) (($image->getWidth() - $width) / 2);
							$y = 0;
							break;
						case 'bottom':
							$x = (int) (($image->getWidth() - $width) / 2);
							$y = (int) ($image->getHeight() - $height);
							break;
						case 'left':
							$x = 0;
							$y = (int) (($image->getHeight() - $height) / 2);
							break;
						case 'right':
							$x = (int) ($image->getWidth() - $width);
							$y = (int) (($image->getHeight() - $height) / 2);
							break;
						case 'center':
						default:
							$x = (int) (($image->getWidth() - $width) / 2);
							$y = (int) (($image->getHeight() - $height) / 2);
					}

					$image->crop($x, $y, $width, $height);

					if ($ext !== 'png') {
						$image->sharpen();
					}
				}

				$left = $width / 2 - $image->getWidth() / 2;
				$top = $height / 2 - $image->getHeight() / 2;
				$canvas->place($image, $left, $top);

				$canvas->save($thumbPath);
			}

			return $thumbUrl;
		} catch (\Exception $e) {
			return ($this->domainDirUrl ? $this->domainDirUrl : $this->cacheDirUrl) . $url;
		}
	}

	/**
	 * @param $relPath
	 * @param $width
	 * @param $height
	 * @param $mtime
	 * @param array|null $crop
	 * @return string
	 */
	public function getThumbName($relPath, $width, $height, $mtime, $crop = NULL)
	{
		$sep = '.';
		$tmp = explode($sep, basename($relPath));
		$ext = array_pop($tmp);

		// cesta k obrazku (ale bez pripony)
		$relPath = implode($sep, $tmp);

		$crop = is_array($crop) && count($crop) === 4 ? implode('-', $crop) . '-' : '';

		// pripojime rozmery a mtime
		$relPath .= '_' . $width . 'x' . $height . '-' . $crop . $mtime . $sep . $ext;

		return $relPath;
	}

	/**
	 * @param string $imgUrl
	 * @param int $width
	 * @param int $height
	 * @param int $crop
	 * @return string
	 * @register imgOffset
	 */
	public function imageOffset($imgUrl, $width, $height = NULL, $crop = NULL)
	{
		$origName = substr($imgUrl, strrpos($imgUrl, '/') + 1);
		$origPath = ($this->domainDir ? $this->domainDir : $this->cacheDir) . $imgUrl;

		if (!is_file($origPath)) {
			return '';
		}

		$ext = pathinfo($imgUrl, PATHINFO_EXTENSION);
		$a = explode('/', $imgUrl);
		array_pop($a);
		$a = implode('/', $a);

		$thumbName = $this->getThumbName($origName, $width, $height, filemtime($origPath), $crop);
		$thumbName = md5(($this->domainDirUrl ? $this->domainDirUrl : $this->cacheDirUrl) . '/' . $a . '/thumbs/' . $thumbName) . ".$ext";

		$path = (($this->domainDir ? $this->domainDir : $this->cacheDir)) . $a . '/thumbs/' . $thumbName;
		if (file_exists($path)) {
			$size = getimagesize($path);
			$newSize = Image::calculateSize($size[0], $size[1], $width, $height);
			$widthOffset = floor(($width - $newSize[0]) / 2);
			$heightOffset = floor(($height - $newSize[1]) / 2);
			return 'padding: ' . $heightOffset . 'px ' . $widthOffset . 'px;';
		} elseif (file_exists($this->cacheDir . $imgUrl)) {
			$size = getimagesize($this->cacheDir . $imgUrl);
			$newSize = Image::calculateSize($size[0], $size[1], $width, $height);
			$widthOffset = floor(($width - $newSize[0]) / 2);
			$heightOffset = floor(($height - $newSize[1]) / 2);
			return 'padding: ' . $heightOffset . 'px ' . $widthOffset . 'px;';
		}
	}

	/**
	 * @param $str
	 * @param $condition
	 * @return string
	 * @register
	 */
	public function ifTrue($str, $condition)
	{
		return $condition ? $str : '';
	}

	/**
	 * Formats phone number
	 * @param string|int $number
	 * @return string
	 * @register
	 */
	public function phone($number)
	{
		if (strpos($number, '00') === 0) {
			$number = str_replace('00', '+', $number);
		}
		$number = trim(preg_replace('~(\d{3})? ?(\d{3}) ?(\d{3}) ?(\d{3})$~', "\\1 \\2 \\3 \\4", $number));
		return $number ?: $this->translator->translate('-');
	}

	/**
	 * Formats zip code
	 * @param string|int $number
	 * @return string
	 * @register
	 */
	public function zipCode($number)
	{
		return trim(preg_replace('~(\d{3})(\d{2})$~', "\\1 \\2", $number));
	}

	/**
	 * formats date to ISO 8601 (used by timeago js plugin)
	 *
	 * @param float $num
	 * @param int
	 * @param string
	 * @param string
	 * @return string
	 * @register
	 */
	public function number($num, $decimals = 0, $decPoint = '.', $thousandsSep = ' ')
	{
		return number_format($num, $decimals, $decPoint, $thousandsSep);
	}

	/**
	 * formats date to ISO 8601 (used by timeago js plugin)
	 *
	 * @param \DateTime $date
	 * @return string
	 * @register
	 */
	public function isodate($date)
	{
		return $date->format(\DateTime::ISO8601);
	}

	/**
	 * formats translated date
	 *
	 * @param \DateTime $date
	 * @param null $format
	 * @return string
	 * @register
	 */
	public function tdate($date, $format = NULL)
	{
		$format = $format ?: 'j.n.Y H:i';
		$format = $this->translator->translate($format);
		if (strpos($format, '%') !== FALSE) {
			return strftime($format, $date->getTimestamp());
		}
		return $date->format($format);
	}

	/**
	 * @param int $amount in hunderths of CZK (haléře)
	 * @param int $decimals
	 * @return string
	 * @register
	 */
	public function formatAmount($amount, $decimals = 0)
	{
		return number_format($amount, $decimals, ',', ' ');
	}

	/**
	 * convert name in nominative to vocative in czech lang
	 *
	 * @param string $name
	 * @return string
	 * @register
	 */
	public function vocative($name)
	{
		$lang = $this->translator->lang;
		if ($lang === 'cs') {
			return Common::czechVocative($name);
		}
		return $name;
	}

	/**
	 * @param \Nette\Utils\ArrayHash $file file object
	 * @return string
	 * @register
	 */
	public function imageDimensions($file)
	{
		$path = realpath($this->parameters->dirs->storage . '/' . $file->path);

		if (!$path) {
			return '0x0';
		}

		$d = getimagesize($path);
		return $d[0] . 'x' . $d[1];
	}

	/**
	 * @param string $url
	 * @return string
	 * @register link
	 */
	public function wrapWithLink($url)
	{
		$el = Html::el('a');
		$el->setText($url);
		$el->href = $url;
		return $el;
	}

	/**
	 * @param bool $value
	 * @return string
	 * @register
	 */
	public function bool($value)
	{
		return $this->translator->translate($value ? 'Yes' : 'No');
	}

	/**
	 * gets image cache on https domain
	 *
	 * @param string $url absolute url
	 * @param bool $allowFailure return source url on failure?
	 * @return string thumb url, FALSE on failure if $allowFailure set to TRUE
	 */
	public function cachedImage($url, $allowFailure = TRUE)
	{
		FileSystem::createDir($this->cacheDir);

		$a = explode('.', $url);
		$ext = strtolower(array_pop($a));
		$name = md5($url) . ".$ext";

		$thumbPath = $this->cacheDir . '/' . $name;
		$thumbUrl = $this->cacheDirUrl . '/' . $name;

		// thumb already exits
		if (is_file($thumbPath)) {
			return $thumbUrl;
		}

		try {
			if (class_exists('Imagick')) {
				$image = new Imagick($url);
				$image->writeImage($thumbPath);
				$image->destroy();
			} else {
				$image = Image::fromFile($url);
				$image->alphaBlending(FALSE);
				$image->saveAlpha(TRUE);
				$image->save($thumbPath);
			}

			return $thumbUrl;
		} catch (\Exception $e) {
			return $allowFailure ? $url : FALSE;
		}
	}

	/**
	 * Converts name and surname to short form. E.g. "John Doe" => "John D."
	 *
	 * @param $name
	 * @return string
	 * @register
	 */
	public static function shortName($name)
	{
		$name = explode(' ', $name, 2);
		if (isset($name[1]) && Strings::length($name[1])) {
			return $name[0] . ' ' . Strings::upper(Strings::substring($name[1], 0, 1)) . '.';

		} else {
			return $name[0];
		}
	}

}
