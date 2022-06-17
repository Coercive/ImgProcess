<?php
namespace Coercive\Utility\ImgProcess;

/**
 * ImgProcess
 *
 * @package 	Coercive\Utility\ImgProcess
 * @link		https://github.com/Coercive/ImgProcess
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2022 Anthony Moral
 * @license 	MIT
 */
class ImgProcess
{
	const DEFAULT_JPG_QUALITY = 60;
	const DEFAULT_PNG_COMPRESSION = 0;
	const DEFAULT_WEBP_QUALITY = 80;

	const FORMAT_VERTICAL = 'VERTICAL';
	const FORMAT_HORIZONTAL = 'HORIZONTAL';

	const EXTENSIONS = [
		'jpg', 'jpeg',
		'png',
		'gif',
		'webp',
	];

	/**
	 * JPG QUALITY
	 *
	 * @var int $_iJpgQuality
	 */
	private int $_iJpgQuality = self::DEFAULT_JPG_QUALITY;

	/**
	 * PNG COMPRESSION
	 *
	 * @var int $_iPngCompression
	 */
	private int $_iPngCompression = self::DEFAULT_PNG_COMPRESSION;

	/**
	 * WEBP QUALITY
	 *
	 * @var int $_iWebpQuality
	 */
	private int $_iWebpQuality = self::DEFAULT_WEBP_QUALITY;

	/**
	 * INPUT /path/name.extension of the image
	 *
	 * @var string $sInputPath
	 */
	private $sInputPath;

	/**
	 * OUTPUT /path/name.extension of the image
	 *
	 * @var string $sOutputPath
	 */
	private $sOutputPath;

	/**
	 * INPUT WIDTH
	 *
	 * @var int $iInputWidth
	 */
	private $iInputWidth;

	/**
	 * INPUT HEIGHT
	 *
	 * @var int $iInputHeight
	 */
	private $iInputHeight;

	/**
	 * OUTPUT WIDTH
	 *
	 * @var int $iOutputWidth
	 */
	private $iOutputWidth;

	/**
	 * OUTPUT HEIGHT
	 *
	 * @var int $iOutputHeight
	 */
	private $iOutputHeight;

	/**
	 * INPUT EXTENSION of the image file
	 *
	 * @var string $sInputExtension
	 */
	private $sInputExtension;

	/**
	 * OUTPUT EXTENSION of the image file
	 *
	 * @var string $sOutputExtension
	 */
	private $sOutputExtension;

	/**
	 * INPUT RESSOURCE image
	 *
	 * @var resource $rInputRessource
	 */
	private $rInputRessource;

	/**
	 * OUTPUT RESSOURCE image
	 *
	 * @var resource $rOutputRessource
	 */
	private $rOutputRessource;

	/**
	 * HORIZONTAL X SRC POINT
	 *
	 * @var mixed $mSrcX horizontal coordinate of source point.
	 */
	private $mSrcX = 'CENTER';

	/**
	 * VERTICAL Y SRC POINT
	 *
	 * @var mixed $mSrcY vertical coordinate of source point.
	 */
	private $mSrcY = 0;

	/**
	 * RATIO X_Y IMAGE
	 *
	 * @var int $iRatio
	 */
	private $iRatio;

	/**
	 * ERROR(s) Message(s)
	 *
	 * @var array $errors
	 */
	private array $errors = [];

	/**
	 * OVERWRITING output file if exist
	 *
	 * @var bool $overwriting
	 */
	private bool $bOverwriting = false;

	/**
	 * Set a background to your image
	 *
	 * @var array
	 */
	private array $fill = [
		'enable' => false,
		'red' => null,
		'green' => null,
		'blue' => null,
		'alpha' => null,
	];

	/**
	 * Set a background to your image
	 *
	 * @param int $red
	 * @param int $green
	 * @param int $blue
	 * @param int|null $alpha [optional]
	 * @return $this
	 */
	public function fill(int $red, int $green, int $blue, ? int $alpha = null): ImgProcess
	{
		$this->fill = [
			'enable' => true,
			'red' => $red,
			'green' => $green,
			'blue' => $blue,
			'alpha' => $alpha,
		];
		return $this;
	}

	/**
	 * Error Messages
	 *
	 * @return array
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * SET OVERWRITING
	 *
	 * @param bool $status [optional]
	 * @return $this
	 */
	public function setOverwriting(bool $status = false): ImgProcess
	{
		$this->bOverwriting = $status;
		return $this;
	}

	/**
	 * SET JPG QUALITY
	 *
	 * @param int|null $quality [optional]
	 * @return $this
	 */
	public function setJpgQuality(? int $quality = null): ImgProcess
	{
		$this->_iJpgQuality = null === $quality ? self::DEFAULT_JPG_QUALITY : $quality;
		return $this;
	}

	/**
	 * SET PNG QUALITY
	 *
	 * @param int|null $compression [optional] 0-9
	 * @return $this
	 */
	public function setPngCompression(? int $compression = null): ImgProcess
	{
		$this->_iPngCompression = null === $compression ? self::DEFAULT_PNG_COMPRESSION : $compression;
		return $this;
	}

	/**
	 * SET WEBP QUALITY
	 *
	 * @param int|null $quality [optional]
	 * @return $this
	 */
	public function setWebpQuality(? int $quality = null): ImgProcess
	{
		$this->_iWebpQuality = null === $quality ? self::DEFAULT_WEBP_QUALITY : $quality;
		return $this;
	}

	/**
	 * SET SOURCE COORDINATE
	 *
	 * @param mixed $x
	 * @param mixed $y
	 * @return $this
	 */
	public function setSourceCoordinate($x, $y): ImgProcess
	{
		if(!is_numeric($x) && ($x !== 'LEFT' && $x !== 'CENTER' && $x !== 'RIGHT')) {
			$this->errors[] = '->setSourceCoordinate(...) @var (mixed) $mSrcX : Integer or String(\'LEFT\',\'CENTER\',\'RIGHT\') needed.';
			return $this;
		}

		if(!is_numeric($y) && ($y !== 'TOP' && $y !== 'MIDDLE' && $y !== 'BOTTOM')) {
			$this->errors[] = '->setSourceCoordinate(...) @var (mixed) $mSrcY : Integer or String(\'TOP\',\'MIDDLE\',\'BOTTOM\') needed.';
			return $this;
		}

		$this->mSrcX = $x;
		$this->mSrcY = $y;
		return $this;
	}

	/**
	 * CALCULATING SOURCE COORDINATE
	 *
	 * @return bool
	 */
	private function calculatingSourceCoordinate(): bool
	{
		if(!$this->iInputWidth) {
			$this->errors[] = '->calculatingSourceCoordinate(...) @var (int) input_width : Can\'t find Source Width.';
		}
		if(!$this->iInputHeight) {
			$this->errors[] = '->calculatingSourceCoordinate(...) @var (int) input_height : Can\'t find Source Height.';
		}
		if(!$this->iOutputWidth) {
			$this->errors[] = '->calculatingSourceCoordinate(...) @var (int) output_width : Can\'t find output Width.';
		}
		if(!$this->iOutputHeight) {
			$this->errors[] = '->calculatingSourceCoordinate(...) @var (int) output_height : Can\'t find output Height.';
		}
		if(!$this->iRatio) {
			$this->errors[] = '->calculatingSourceCoordinate(...) @var (int) ratio : Can\'t find Source Ratio.';
		}
		if($this->errors) {
			return false;
		}

		if(!is_numeric($this->mSrcX)) {
			if($this->mSrcX === 'LEFT') {
				$this->mSrcX = 0;
			}
			if($this->mSrcX === 'CENTER') {
				$this->mSrcX = ($this->iInputWidth - $this->iOutputWidth / $this->iRatio) / 2;
			}
			if($this->mSrcX === 'RIGHT') {
				$this->mSrcX = $this->iInputWidth - $this->iOutputWidth / $this->iRatio;
			}
		}

		if(!is_numeric($this->mSrcY)) {
			if($this->mSrcY === 'TOP') {
				$this->mSrcY = 0;
			}
			if($this->mSrcY === 'MIDDLE') {
				$this->mSrcY = ($this->iInputHeight - $this->iOutputHeight / $this->iRatio) / 2;
			}
			if( $this->mSrcY === 'BOTTOM' ) {
				$this->mSrcY = $this->iInputHeight - $this->iOutputHeight / $this->iRatio;
			}
		}

		return is_numeric($this->mSrcX) && is_numeric($this->mSrcY);
	}

	/**
	 * SET EXTENSION of the image file
	 *
	 * @param string $direction 'INPUT' | 'OUTPUT'
	 * @param string $path
	 * @return bool
	 */
	private function setExtension(string $direction, string $path): bool
	{
		if ($direction !== 'INPUT' && $direction !== 'OUTPUT') {
			$this->errors[] = '->setExtension(...) @var (string) $sWhich is needed and have to be equal of \'INPUT\' or \'OUTPUT\'.';
			return false;
		}

		if (!$path) {
			$this->errors[] = '->setExtension(...) @var (string) $sFullPath is needed.';
			return false;
		}

		$extension = strtolower(substr(strrchr($path, '.'), 1));
		if (!in_array($extension, self::EXTENSIONS, true)) {
			$this->errors[] = "->setExtension(...) The extension '$extension' of the image is not recognized.";
			return false;
		}

		# Remap JPEG
		if ($extension === 'jpeg') {
			$extension = 'jpg';
		}

		if ($direction === 'INPUT') {
			$this->sInputExtension = $extension;
		}
		else {
			$this->sOutputExtension = $extension;
		}

		return true;
	}

	/**
	 * SET INPUT SIZE
	 *
	 * @param string $sInputPath
	 * @return bool
	 */
	private function setInputSize(string $sInputPath): bool
	{
		if (!$sInputPath) {
			$this->errors[] = '->setInputSize(...) @var (string) $sInputPath is needed.';
			return false;
		}

		if (!list($this->iInputWidth, $this->iInputHeight) = getimagesize($sInputPath)) {
			$this->errors[] = '->setInputSize(...) @var (string) input_path : Can\'t set Width & Height.';
			return false;
		}

		if (!$this->iInputWidth || !$this->iInputHeight) {
			$this->errors[] = '->setInputSize(...) Width or Height empty.';
			return false;
		}

		return true;
	}

	/**
	 * SET OUTPUT SIZE
	 *
	 * @param int $w IN PIXEL (px)
	 * @param int $h IN PIXEL (px)
	 * @return $this
	 */
	public function setOutputSize(int $w, int $h): ImgProcess
	{
		if ($w && $h) {
			$this->iOutputWidth = $w;
			$this->iOutputHeight = $h;
		}
		else {
			$this->errors[] = '->setOutputSize(...) @var (int) $w & $h are needed and can\'t be empty.';
		}
		return $this;
	}

	/**
	 * SET INPUT PATH
	 *
	 * @param string $path
	 * @return $this
	 */
	public function setInputPath(string $path): ImgProcess
	{
		if (!$path) {
			$this->errors[] = '->setInputPath(...) @var (string) $sInputPath is needed.';
			return $this;
		}

		if (!is_file($path)) {
			$this->errors[] = '->setInputPath(...) $sInputPath is not a valid file.';
			return $this;
		}

		if (!$this->setExtension('INPUT', $path)) {
			$this->errors[] = '->setInputPath(...) Extension problem. Can not continue.';
			return $this;
		}

		if (!$this->setInputSize($path)) {
			$this->errors[] = '->setInputPath(...) Size problem. Can not continue.';
			return $this;
		}

		$this->sInputPath = $path;
		return $this;
	}

	/**
	 * SET OUTPUT PATH
	 *
	 * @param string $path
	 * @return $this
	 */
	public function setOutputPath(string $path): ImgProcess
	{
		if (!$path) {
			$this->errors[] = '->setOutputPath(...) @var (string) $sOutputPath is needed.';
			return $this;
		}

		if (!$this->bOverwriting && file_exists($path)) {
			$this->errors[] = '->setOutputPath(...) $sOutputPath already exist.';
			return $this;
		}

		if (!$this->setExtension('OUTPUT', $path)) {
			$this->errors[] = '->setOutputPath(...) Extension problem. Can not continue.';
			return $this;
		}

		$this->sOutputPath = $path;
		return $this;
	}

	/**
	 * PROCESS
	 *
	 * @param float $w width
	 * @param float $h heigth
	 * @return bool
	 */
	private function process(float $w, float $h): bool
	{
		if($this->errors) {
			$this->errors[] = '->process(...) Shutdown due to an error upstream process.';
			return false;
		}

		$bCreateInputRessource = $this->createInputRessource();
		if(!$bCreateInputRessource) {
			$this->errors[] = '->process(...) Shutdown due to an error in creating input image ressource.';
			return false;
		}

		$bCreateOutputRessource = $this->createOutputRessource();
		if(!$bCreateOutputRessource) {
			$this->errors[] = '->process(...) Shutdown due to an error in creating output image ressource.';
			return false;
		}

		$bCoordinate = $this->calculatingSourceCoordinate();
		if(!$bCoordinate) {
			$this->errors[] = '->process(...) Shutdown due to an error in calculating source coordinate of the image.';
			return false;
		}

		$bResampled = $this->outputImageResampled($w, $h);
		if(!$bResampled) {
			$this->errors[] = '->process(...) Shutdown due to an error in resampling image.';
			return false;
		}

		$bSave = $this->saveOutputImage();
		if(!$bSave) {
			$this->errors[] = '->process(...) Shutdown due to an error in saving final image.';
			return false;
		}

		$this->clear_cache();

		return true;

	}

	/**
	 * Create Image Ressource from Input Path
	 *
	 * @return bool
	 */
	private function createInputRessource(): bool
	{
		switch($this->sInputExtension) {
			case 'gif':
				$this->rInputRessource = imagecreatefromgif($this->sInputPath);
				break;
			case 'jpg':
				$this->rInputRessource = imagecreatefromjpeg($this->sInputPath);
				break;
			case 'png':
				$this->rInputRessource = imagecreatefrompng($this->sInputPath);
				break;
			case 'webp':
				$this->rInputRessource = imagecreatefromwebp($this->sInputPath);
				break;
			default :
				$this->errors[] = '->setExtension(...) $sFullPath : The extension of the image is not recognized.';
				return false;
		}

		if (!$this->rInputRessource) {
			$this->errors[] = '->setExtension(...) $sFullPath : The extension of the image is not recognized.';
			return false;
		}
		return true;
	}

	/**
	 * Create Output Image Ressource
	 *
	 * imagecreatetruecolor() : Empty ressource from true color for better quality
	 * imagecolortransparent() & others : Preserve transparency from alpha grey 127
	 *
	 * @return bool
	 */
	private function createOutputRessource(): bool
	{
		if(!$this->iOutputWidth || !$this->iOutputHeight) {
			$this->errors[] = '->createOutputRessource(...) output_width | output_height  : New width and height are needed.';
			return false;
		}

		$this->rOutputRessource = imagecreatetruecolor( $this->iOutputWidth, $this->iOutputHeight ) ;
		if (empty($this->sOutputExtension)) {
			$this->errors[] = '->createOutputRessource(...) Can\'t find output_extension.';
			return false;
		}

		if($this->fill['enable']) {
			if(null === $this->fill['alpha']) {
				$bgcolor = imagecolorallocate($this->rOutputRessource, $this->fill['red'], $this->fill['green'], $this->fill['blue']);
			}
			else {
				$bgcolor = imagecolorallocatealpha($this->rOutputRessource, $this->fill['red'], $this->fill['green'], $this->fill['blue'], $this->fill['alpha']);
			}
			imagefill($this->rOutputRessource, 0, 0, $bgcolor);
		}

		if($this->sOutputExtension === 'gif' || $this->sOutputExtension === 'png') {
			if(!$this->fill['enable']) {
				imagecolortransparent($this->rOutputRessource, imagecolorallocatealpha($this->rOutputRessource, 0, 0, 0, 127));
				imagealphablending($this->rOutputRessource, false);
			}
			imagesavealpha($this->rOutputRessource, true);
		}

		return (bool) $this->rOutputRessource;
	}

	/**
	 * Image Resizing
	 *
	 * @param float $w
	 * @param float $h
	 * @return bool
	 */
	private function outputImageResampled(float $w, float $h): bool
	{
		return imagecopyresampled($this->rOutputRessource, $this->rInputRessource, 0, 0, $this->mSrcX, $this->mSrcY, $this->iOutputWidth, $this->iOutputHeight, $w, $h);
	}

	/**
	 * Save Image
	 *
	 * @return bool
	 */
	private function saveOutputImage(): bool
	{
		if (!$this->sOutputExtension) {
			$this->errors[] = '->saveOutputImage(...) Can\'t find output extension.';
			return false;
		}

		if (!$this->rOutputRessource) {
			$this->errors[] = '->saveOutputImage(...) Can\'t find output ressource.';
			return false;
		}

		if (!$this->sOutputPath) {
			$this->errors[] = '->saveOutputImage(...) Can\'t find output path.';
			return false;
		}

		# Prepare output tmp empty file
		$tmp = tempnam(sys_get_temp_dir(), 'tmp_coercive_imgprocess_');

		switch($this->sOutputExtension) {
			case 'gif':
				imagegif($this->rOutputRessource, $tmp);
				break;
			case 'jpg':
				imagejpeg($this->rOutputRessource, $tmp, $this->_iJpgQuality);
				break;
			case 'png':
				imagepng($this->rOutputRessource, $tmp, $this->_iPngCompression);
				break;
			case 'webp':
				imagewebp($this->rOutputRessource, $tmp, $this->_iWebpQuality);
				break;
			default :
				$this->errors[] = '->saveOutputImage(...) output extension : The extension of the image is not recognized.';
				return false;
		}

		# File move from tmp to preserve atomicity
		return rename($tmp, $this->sOutputPath);
	}

	/**
	 * CLEAR CACHE
	 *
	 * @return void
	 */
	private function clear_cache()
	{
		@ImageDestroy($this->rInputRessource);
		@ImageDestroy($this->rOutputRessource);
	}

	/**
	 * IMAGE BACKGROUND FULL COVERED
	 *
	 * @param bool $enlarge [optional] Force enlarge covered, else the source image can't be smaller than the ouput
	 * @return bool
	 */
	public function cover(bool $enlarge = false): bool
	{
		if (!$enlarge && ($this->iInputWidth < $this->iOutputWidth || $this->iInputHeight < $this->iOutputHeight)) {
			$this->errors[] = '->cover(...) Input image is too small. If you wan\'t to enlarge, you can force it';
			return false;
		}

		$this->iRatio = max($this->iOutputWidth/$this->iInputWidth, $this->iOutputHeight/$this->iInputHeight);

		$iSourceHeight = floatval($this->iOutputHeight / $this->iRatio);
		$iSourceWidth = floatval($this->iOutputWidth / $this->iRatio);
		if (!$this->process($iSourceWidth, $iSourceHeight)) {
			$this->errors[] = '->cover(...) Process crash.';
			return false;
		}
		return true;
	}

	/**
	 * IMAGE CROP
	 *
	 * @param bool $enlarge [optional] Force enlarge crop, else the source image do not resized
	 * @return bool
	 */
	public function crop(bool $enlarge = false): bool
	{
		if (!$enlarge && ($this->iInputWidth < $this->iOutputWidth || $this->iInputHeight < $this->iOutputHeight)) {
			$this->iRatio = 1;
			$iSourceHeight = floatval($this->iOutputHeight);
			$iSourceWidth = floatval($this->iOutputWidth);
		}
		else {
			$this->iRatio = min($this->iOutputWidth/$this->iInputWidth , $this->iOutputHeight/$this->iInputHeight);
			$iSourceHeight = floatval($this->iOutputHeight / $this->iRatio);
			$iSourceWidth = floatval($this->iOutputWidth / $this->iRatio);
		}

		if (!$this->process($iSourceWidth, $iSourceHeight)) {
			$this->errors[] = '->crop(...) Process crash.';
			return false;
		}
		return true;
	}

	/**
	 * MY OWN SIZE
	 *
	 * @param int|null $w [optional]
	 * @param int|null $h [optional]
	 * @return bool
	 */
	public function myOwnSize(? int $w = null, ? int $h = null): bool
	{
		if ($w === null && $h === null) {
			$this->errors[] = '->myOwnSize(...) @var $sWidth or $iHeight needed.';
			return false;
		}

		if ($w && $h) {
			$this->errors[] = '->myOwnSize(...) @var $sWidth & $iHeight ONLY ONE is needed, the other keeps ratio.';
			return false;
		}

		if ($w && !is_numeric($w) || $h && !is_numeric($h)) {
			$this->errors[] = '->myOwnSize(...) @var $sWidth or $iHeight have to be numeric values.';
			return false;
		}

		if ($w) {
			$this->iOutputWidth = $w;
			$this->iOutputHeight = $this->iInputHeight / $this->iInputWidth * $w;
			$this->iRatio = $this->iOutputWidth / $this->iInputWidth;
		} else {
			$this->iOutputHeight = $h;
			$this->iOutputWidth = $this->iInputWidth / $this->iInputHeight * $h;
			$this->iRatio = $this->iOutputHeight / $this->iInputHeight;
		}

		if (!$this->process(floatval($this->iInputWidth), floatval($this->iInputHeight))) {
			$this->errors[] = '->myOwnSize(...) Process crash.';
			return false;
		}
		return true;
	}

	/**
	 * MAX
	 *
	 * @param int $w
	 * @param int $h
	 * @return bool
	 */
	public function max(int $w, int $h): bool
	{
		# Verify empty
		if (!$w && !$h) {
			$this->errors[] = __METHOD__ . ' width or height needed.';
			return false;
		}

		# Detect format
		$format = $this->iInputWidth > $this->iInputHeight ? self::FORMAT_HORIZONTAL : self::FORMAT_VERTICAL;

		# No resize if small
		if($format === self::FORMAT_HORIZONTAL && (!$w || $w >= $this->iInputWidth)
			|| $format === self::FORMAT_VERTICAL && (!$h || $h >= $this->iInputHeight)) {
			return $this->sameSize();
		}

		# Prepare
		switch ($format) {

			case self::FORMAT_HORIZONTAL:
				$this->iOutputWidth = $w;
				$this->iOutputHeight = $this->iInputHeight / $this->iInputWidth * $w;
				$this->iRatio = $this->iOutputWidth / $this->iInputWidth;
				break;

			case self::FORMAT_VERTICAL:
				$this->iOutputHeight = $h;
				$this->iOutputWidth = $this->iInputWidth / $this->iInputHeight * $h;
				$this->iRatio = $this->iOutputHeight / $this->iInputHeight;
				break;

			default:
				$this->errors[] = __METHOD__ . ' unknow format.';
				return false;
		}

		# Handle error / send status
		if (!$this->process(floatval($this->iInputWidth), floatval($this->iInputHeight))) {
			$this->errors[] = __METHOD__ . ' process crash.';
			return false;
		}
		return true;
	}

	/**
	 * SAME SIZE
	 *
	 * @return bool
	 */
	public function sameSize(): bool
	{
		$this->iRatio = 1;
		$this->iOutputWidth = $this->iInputWidth;
		$this->iOutputHeight = $this->iInputHeight;

		if (!$this->process(floatval($this->iInputWidth), floatval($this->iInputHeight))) {
			$this->errors[] = '->sameSize(...) Process crash.';
			return false;
		}
		return true;
	}

	/**
	 * GET IMAGE QUALITY FROM FILE
	 *
	 * @param string $path
	 * @return int|null
	 */
	static public function getImageQuality(string $path): ? int
	{
		# VERIFY
		if(!is_file($path)) {
			return null;
		}

		# GET INFOS
		exec("identify -verbose $path", $outpout);
		if(!$outpout) {
			return null;
		}

		# DETECT "Quality" line
		foreach ($outpout as $line) {
			$line = trim($line);
			if(strpos($line, 'Quality: ') !== 0) {
				continue;
			}
			$aQuality = explode(': ', $line);
			if(isset($aQuality[1])) {
				return intval($aQuality[1]);
			}
		}
		return null;
	}

	/**
	 * RETRIEVE WIDTH AND HEIGHT FROM IMAGE PATH
	 *
	 * @param string $path
	 * @return array
	 */
	public function getImageSize(string $path): array
	{
		$datas = @getimagesize($path);
		return [
			'width' => $datas[0] ?? 0,
			'height' => $datas[1] ?? 0
		];
	}
}