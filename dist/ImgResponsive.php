<?php
namespace Coercive\Utility\ImgProcess;

use Closure;
use Coercive\Utility\Xml\ExtractArray;
use Coercive\Utility\Xml\FormatML;
use Coercive\Utility\Xml\XmlCleaner;
use Coercive\Utility\Xml\XmlToArray;
use Exception;
use tidy;

/**
 * Resize images inside html content and add responsive tags
 *
 * @package 	Coercive\Utility\ImgProcess
 * @link		https://github.com/Coercive/ImgProcess
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2024 Anthony Moral
 * @license 	MIT
 */
class ImgResponsive
{
	const MODE_SRCSET = 'srcset';
	const MODE_PICTURE = 'picture';
	const MODES = [
		self::MODE_SRCSET,
		self::MODE_PICTURE,
	];

	/** @var string */
	private string $mode;

	/** @var bool */
	private bool $multiplier = false;

	/** @var string[] */
	private array $tagConversion = ['br', 'img', 'source'];

	/** @var ImgProcess|null */
	private ? ImgProcess $imgp = null;

	/** @var string */
	private string $inputHtml;

	/** @var string */
	private string $outputHtml = '';

	/**
	 * @see ResizeInnerImages::path()
	 * @var string
	 */
	private string $filepath = '';

	/**
	 * @see ResizeInnerImages::path()
	 * @var string
	 */
	private string $relpath = '';

	/**
	 * @see ResizeInnerImages::overwrite()
	 * @var bool
	 */
	private bool $overwrite = false;

	/**
	 * @see ResizeInnerImages::size()
	 * @var array
	 */
	private array $sizes = [];

	/**
	 * @see ResizeInnerImages::resolve()
	 * @var Closure|null
	 */
	private ? Closure $resolve = null;

	/**
	 * @see ResizeInnerImages::addSource()
	 * @see ResizeInnerImages::addImage()
	 * @var array
	 */
	private array $pictures = [];

	/**
	 * @see ResizeInnerImages::getResized()
	 * @see ResizeInnerImages::addResized()
	 * @see ResizeInnerImages::resetResized()
	 * @see ResizeInnerImages::resize()
	 * @var array
	 */
	private array $resized = [];

	/**
	 * @see ResizeInnerImages::addAttr()
	 * @see ResizeInnerImages::resetAttrs()
	 * @var array
	 */
	private array $attrs = [];

	/**
	 * @return void
	 * @throws Exception
	 */
	private function handleError()
	{
		if($errors = $this->imgp->getErrors()) {
			throw new Exception(implode(', & — ', $errors));
		}
	}

	/**
	 * Tidy options doc
	 *
	 * show-body-only extrait uniquement le body (et ne reconstruit pas html/head/title etc...)
	 * no indent (des espaces vides peuvent se retrouver dans le document sinon)
	 * no wrap (il peut faire un retour à la ligne avant un attribut x="" qui ne sera pas considéré comme un espace avant, donc plante)
	 *
	 * @link http://tidy.sourceforge.net/docs/quickref.html#newline
	 *
	 * @param string $haystack
	 * @return void
	 */
	private function tidy(string $haystack)
	{
		$this->inputHtml = (new tidy)->repairString($haystack, [
			'show-body-only' => true,
			'indent-spaces' => 0,
			'wrap' => 0
		]);
	}

	/**
	 * Encapsulate HTML for the XML parser because only one tag is allowed at root entry.
	 *
	 * @return string
	 */
	private function encapsulate(): string
	{
		return "<root>{$this->inputHtml}</root>";
	}

	/**
	 * Clean the input html.
	 *
	 * @return string
	 * @throws Exception
	 */
	private function clean(): string
	{
		$cleaner = new XmlCleaner([
			XmlCleaner::OPTION_DECODE           => [['&', '&amp;']],
			XmlCleaner::OPTION_DELETE_DOCTYPE   => true,
			XmlCleaner::OPTION_DELETE_PARASITIC => true,
			XmlCleaner::OPTION_TAGS_CONVERSION  => $this->tagConversion
		]);

		$cleaner->loadString($this->encapsulate());
		$cleaner->clean();
		$cleaner->addHeader();
		return $cleaner->get();
	}

	/**
	 * Transform input HTML to an array.
	 *
	 * @return array
	 * @throws Exception
	 */
	private function transform(): array
	{
		return (new XmlToArray($this->clean()))->parse()->get();
	}

	/**
	 * Extract array data from the root tag.
	 *
	 * @return array
	 * @throws Exception
	 */
	private function extract(): array
	{
		$root = new ExtractArray($this->transform(), '/root');
		return $root->get();
	}

	/**
	 * Reformat img tags with the compressed image.
	 *
	 * @return string
	 * @throws Exception
	 */
	private function format(): string
	{
		return (new FormatML($this->extract(), [
			'picture' => function($str, $attr) {
				if($this->overwrite) {
					return $str;
				}
				else {
					return FormatML::autoRebuild('picture', $str, $attr);
				}
			},

			'source' => function($str, $attr) {
				if($this->overwrite) {
					return '';
				}
				else {
					return FormatML::autoRebuild('source', $str, $attr);
				}
			},

			'img' => function($str, $attr) {

				# Retrieve img tag attributes
				$src = $attr['src'] ?? '';
				$datasrc = $attr['data-source'] ?? '';
				$compressed = boolval($attr['data-compressed'] ?? false);

				# Customize attributes
				$attr = $this->mapAttrs($attr);

				# Img already prepared
				if(!$this->overwrite && $datasrc && $compressed) {
					return FormatML::autoRebuild('img', $str, $attr);
				}

				# Recovery of the original image if already processed
				if($datasrc) {
					$src = $datasrc;
				}

				# Retrieve img realpath
				$filepath = $this->find($src);
				if(!$filepath) {
					return FormatML::autoRebuild('img', $str, $attr);
				}

				# Get image size
				$imgSizes = ImgProcess::getImageSize($filepath);
				$w = intval($imgSizes['width']);
				$h = intval($imgSizes['height']);
				if(!$w || !$h) {
					return FormatML::autoRebuild('img', $str, $attr);
				}

				# Resize process
				$this->resetPicture();
				$this->resetResized();
				foreach ($this->sizes as $s) {

					# If the image is too small and it is not the default image
					if($w <= $s['width'] && !$s['default']) {
						continue;
					}

					# If the image is larger than the current width, we resize
					if($w > $s['width']) {
						$resized = $this->resize($filepath, $s['width']);
						if(!$resized) {
							break;
						}
					}
					else {
						$s['width'] = $w;
						$resized = $src;
					}

					# Default image which will also be in the src attribute
					if($s['default']) {

						# Adding default width and height for the webbrowser will be able to reserved block space.
						$attr['width'] = $w;
						$attr['height'] = $h;

						$data = array_merge($attr, [
							'data-source' => $src,
							'data-compressed' => true,
							'src' => $resized
						]);
						$this->addImage($resized, $s, $data);
					}
					else {
						$this->addSource($resized, $s);
					}
				}

				# Stop if crash
				if(empty($resized)) {
					return FormatML::autoRebuild('img', $str, $attr);
				}

				return $this->createPictureTag();
			},
		], [
			FormatML::OPTION_ALL_TAGS_REQUIRED => false,
			FormatML::OPTION_SKIP_MISSING_TAGS_CONTENT => false,
			FormatML::OPTION_AUTO_REBUILD_MISSING_TAGS => true,
		]))->get();
	}

	/**
	 * Initialize directory and ImgProcess class
	 *
	 * @return void
	 * @throws Exception
	 */
	private function prepare()
	{
		# Makedir
		if(!is_dir($this->filepath)) {
			if(!mkdir($this->filepath, 0777, true)) {
				throw new Exception("Can't make dir: {$this->filepath}");
			}
		}

		# Load image processor
		$this->imgp = (new ImgProcess)
			->setPngCompression(9)
			->setJpgQuality(70)
			->setWebpQuality(70)
			->setOverwriting(true)
			->setExifRotate(true);
	}

	/**
	 * Resolve filepath from img tag src attributes.
	 * Use the custom Closure "resolve" if given.
	 *
	 * @param string $path
	 * @return string
	 */
	private function find(string $path): string
	{
		$filepath = $this->resolve ? ($this->resolve)($path) : $path;
		return $filepath && is_file($filepath) ? $filepath : '';
	}

	/**
	 * Resize process
	 *
	 * @param string $source
	 * @param int $size
	 * @return string
	 * @throws Exception
	 */
	private function resize(string $source, int $size): string
	{
		# Verify source file
		if (!$source) {
			return '';
		}

		# Already prepared
		if($rel = $this->getResized($size)) {
			return $rel;
		}

		# Autoset dest extension
		$extension = (string) pathinfo($source, PATHINFO_EXTENSION);
		$dest =  '/' . hash('sha512', $source) . '_' . $size . 'w.' . $extension;

		# Load img processor
		$this->imgp
			->setInputPath($source)
			->setOutputPath($this->filepath . $dest);

		# Redimension et sauvegarde
		$status = $this->imgp->max($size, 0);

		# Handle errors
		$this->handleError();

		# New img name
		if($status) {
			$this->addResized($size, $this->relpath . $dest);
			chmod($this->filepath . $dest, 0777);
			return $this->relpath . $dest;
		}
		return '';
	}

	/**
	 * Get an already prepared path for a given size
	 *
	 * @param int $size
	 * @return string
	 */
	private function getResized(int $size): string
	{
		return $this->resized[$size] ?? '';
	}

	/**
	 * Add prepared path for a specific size
	 *
	 * @param int $size
	 * @param string $relpath
	 * @return void
	 */
	private function addResized(int $size, string $relpath)
	{
		$this->resized[$size] = $relpath;
	}

	/**
	 * Reset prepared paths / sizes
	 *
	 * @return void
	 */
	private function resetResized()
	{
		$this->resized = [];
	}

	/**
	 * Extra checks before process
	 *
	 * @return void
	 * @throws Exception
	 */
	private function checks()
	{
		if(!$this->inputHtml) {
			throw new Exception("Html content must be provided.");
		}
		if(!$this->filepath) {
			throw new Exception("Root filepath must be provided.");
		}
		if(!$this->relpath) {
			throw new Exception("Relative path must be provided.");
		}
		if(!$this->sizes) {
			throw new Exception("You must add some sizes options.");
		}
		$default = false;
		foreach ($this->sizes as $s) {
			if($s['default']) {
				$default = true;
				break;
			}
			if(!$s['width']) {
				throw new Exception("Parametter width cannot be empty in sizes options.");
			}
		}
		if(!$default) {
			throw new Exception("At least one of sizes options must be the default size (true).");
		}
	}

	/**
	 * Add alternative source of an image
	 *
	 * @param string $src
	 * @param array $size
	 * @return void
	 */
	private function addSource(string $src, array $size)
	{
		$this->pictures['sources'][] = [
			'src' => $src,
			'size' => $size,
		];
	}

	/**
	 * Add main source of an image
	 *
	 * @param string $src
	 * @param array $size
	 * @param array $attr
	 * @return void
	 */
	private function addImage(string $src, array $size, array $attr)
	{
		$this->pictures['image'] = [
			'src' => $src,
			'size' => $size,
			'attr' => $attr,
		];
	}

	/**
	 * Reset sources of an image
	 *
	 * @return void
	 */
	private function resetPicture()
	{
		$this->pictures = [];
	}

	/**
	 * Create HTML responsive picture
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Learn/HTML/Multimedia_and_embedding/Responsive_images
	 *
	 * @return string
	 */
	private function createPictureTag(): string
	{
		# Skip on error
		if(!$this->pictures) {
			return '';
		}

		/**
		  <img src="small-image.png"
				srcset="small-image.png 1x,
						medium-image.png 2x,
						large-image.png 3x">

		  <img src="small-image.png"
				srcset="small-image.png 300w,
						medium-image.png 600w,
						large-image.png 1200w"
				sizes="(min-width: 66em) 33vw,
						(min-width: 44em) 50vw,
						100vw">
		 */
		if($this->mode === self::MODE_SRCSET) {
			$size = $this->pictures['image']['size'];
			$attr = $this->pictures['image']['attr'];
			$attr['src'] = $this->pictures['image']['src'];

			$srcset = $sizes = [];
			if($this->multiplier) {
				$srcset[] = $attr['src'] . ($size['media'] ? ' ' . $size['media'] : '');
			}
			foreach ($this->pictures['sources'] ?? [] as $source) {
				if($this->multiplier) {
					$srcset[] = $source['src'] . ($source['size']['media'] ? ' ' . $source['size']['media'] : '');
				}
				else {
					$srcset[] = $source['src'] . ' ' . $source['size']['width'] . 'w';
					if($m = $source['size']['media']) {
						$sizes[] = $m;
					}
				}
			}
			if(!$this->multiplier && $m = $this->pictures['image']['size']['media']) {
				$sizes[] = $m;
			}
			if($sizes) {
				$attr['sizes'] = implode(', ', $sizes);
			}
			if($srcset) {
				$attr['srcset'] = implode(', ', $srcset);
			}

			return FormatML::autoRebuild('img', '', $attr);
		}

		/**
		  	<picture>
				<source media="(max-width: 799px)" srcset="small-image.jpg" />
				<source media="(min-width: 800px)" srcset="large-image.jpg" />
				<img src="large-image.jpg" alt="..." />
			</picture>
		 */
		if($this->mode === self::MODE_PICTURE) {
			$sources = '';
			foreach ($this->pictures['sources'] as $source) {
				$attr['srcset'] = $source['src'];
				if($m = $source['size']['media']) {
					$attr['media'] = $m;
				}
				$sources .= FormatML::autoRebuild('source', '', $attr);
			}

			$image = FormatML::autoRebuild('img', '', $this->pictures['image']['attr']);

			return FormatML::autoRebuild('picture', $sources.$image);
		}

		return '';
	}

	/**
	 * Add or remove attributes from img tag
	 *
	 * @param array $attr
	 * @return array
	 */
	private function mapAttrs(array $attr): array
	{
		foreach ($this->attrs as $a) {
			if($a['remove']) {
				if(array_key_exists($a['name'], $attr)) {
					unset($attr[$a['name']]);
				}
				continue;
			}
			if(!array_key_exists($a['name'], $attr) || $a['override']) {
				$attr[$a['name']] = $a['value'];
			}
		}
		return $attr;
	}

	/**
	 * Set responsive mode
	 *
	 * @param string $mode
	 * @return void
	 * @throws Exception
	 */
	private function setMode(string $mode)
	{
		if(!in_array($mode, self::MODES, true)) {
			throw new Exception("Parametter mode is incorrect,  must be : " . implode(' or ', self::MODES));
		}
		$this->mode = $mode;
	}

	/**
	 * ResizeInnerImages constructor.
	 *
	 * @param string $html [optional]
	 * @return void
	 * @throws Exception
	 */
	public function __construct(string $html = '')
	{
		if($html) {
			$this->data($html);
		}
		$this->modePicture();
	}

	/**
	 * Mode responsive picture (for the HTML <picture> tag)
	 *
	 * @return $this
	 */
	public function modePicture(): self
	{
		$this->mode = self::MODE_PICTURE;
		$this->multiplier = false;
		$this->removeAttr('srcset');
		$this->removeAttr('sizes');
		return $this;
	}

	/**
	 * Mode responsive srcset (for the HTML <img> tag with srcset)
	 *
	 * @param bool $multiplier [optional] Switch width mode (default) to multiplier for support resolutions.
	 * @return $this
	 */
	public function modeSrcset(bool $multiplier = false): self
	{
		$this->mode = self::MODE_SRCSET;
		$this->multiplier = $multiplier;
		$this->resetAttr('srcset');
		$this->resetAttr('sizes');
		return $this;
	}

	/**
	 * Start process
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function process(): self
	{
		$this->checks();
		$this->prepare();
		$this->outputHtml = $this->format();
		return $this;
	}

	/**
	 * Set the html content to parse
	 *
	 * @return $this
	 */
	public function data(string $html): self
	{
		$this->tidy($html);
		return $this;
	}

	/**
	 * Allow to overwrite image files if already prepared and exists.
	 *
	 * @return $this
	 */
	public function overwrite(bool $status): self
	{
		$this->overwrite = $status;
		return $this;
	}

	/**
	 * Add resize options
	 *
	 * Examples:
	 * ->size(862, '(min-width: 939px) and (max-width: 1169px) 832px')
	 * if multiplier :
	 * ->size(1200, '2x', true)
	 *
	 * @param int $width
	 * @param string $media [optional]
	 * @param bool $default [optional]
	 * @return $this
	 */
	public function size(int $width, string $media = '', bool $default = false): self
	{
		$this->sizes[] = [
			'width' => $width,
			'media' => $media,
			'default' => $default,
		];
		return $this;
	}

	/**
	 * Set the root filepath (on server) and the relative filepath (in html img src)
	 *
	 * @param string $filepath
	 * @param string $relpath
	 * @return $this
	 */
	public function path(string $filepath, string $relpath): self
	{
		$this->filepath = $filepath;
		$this->relpath = $relpath;
		return $this;
	}

	/**
	 * Allow you to customize the path resolver to find complex images from src attribute.
	 * The closure receive the src from image attribute and will return the realpath to the source image file.
	 *
	 * @param Closure $func(string): string
	 * @return $this
	 */
	public function resolve(Closure $func): self
	{
		$this->resolve = $func;
		return $this;
	}

	/**
	 * Add some non-closing tag to the conversion list for XML parsing.
	 *
	 * @param string $tag
	 * @return $this
	 */
	public function addNonClosingTag(string $tag): self
	{
		$this->tagConversion[] = $tag;
		return $this;
	}

	/**
	 * Add custom attribute
	 *
	 * @param string $name
	 * @param string $value
	 * @param bool $override [optional]
	 * @return $this
	 */
	public function addAttr(string $name, string $value, bool $override = false): self
	{
		$this->attrs[$name] = [
			'name' => $name,
			'value' => $value,
			'override' => $override,
			'remove' => false,
		];
		return $this;
	}

	/**
	 * Remove attribute
	 *
	 * @param string $name
	 * @return $this
	 */
	public function removeAttr(string $name): self
	{
		$this->attrs[$name] = [
			'name' => $name,
			'value' => '',
			'override' => false,
			'remove' => true,
		];
		return $this;
	}

	/**
	 * Reset custom attributes
	 *
	 * @return $this
	 */
	public function resetAttr(string $name): self
	{
		unset($this->attrs[$name]);
		return $this;
	}

	/**
	 * Remove all custom attributes
	 *
	 * @return $this
	 */
	public function resetAttrs(): self
	{
		$this->attrs = [];
		return $this;
	}

	/**
	 * The HTML with img tag replaced by resized ones.
	 *
	 * @return string
	 */
	public function getHtml(): string
	{
		return trim($this->outputHtml);
	}
}