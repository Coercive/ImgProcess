<?php
namespace Coercive\Utility\ImgProcess;

/**
 * ImgProcess
 * PHP Version 	7
 *
 * @package 	Coercive\Utility\ImgProcess
 * @link		@link https://github.com/Coercive/ImgProcess
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2017 - 2018 Anthony Moral
 * @license 	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
class ImgProcess {

	const DEFAULT_JPG_QUALITY = 60;
	const DEFAULT_PNG_COMPRESSION = 0;

	/**
	 * JPG QUALITY
	 *
	 * @var int $_iJpgQuality
	 */
	private $_iJpgQuality = null;

	/**
	 * PNG COMPRESSION
	 *
	 * @var int $_iPngCompression
	 */
	private $_iPngCompression = null;

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
	 * @var array $aError
	 */
	private $aError = [];

	/**
	 * OVERWRITING output file if exist
	 *
	 * @var bool $overwriting
	 */
	private $bOverwriting = false;

	/**
	 * ImgProcess constructor.
	 */
	public function __construct() {

		# INIT JPG QUALITY
		$this->_iJpgQuality = self::DEFAULT_JPG_QUALITY;

		# INIT PNG COMPRESSION
		$this->_iPngCompression = self::DEFAULT_PNG_COMPRESSION;

	}

	/**
	 * ERROR(s) Message(s)
	 *
	 * @return array $this->aError
	 */
	public function getError() {
		return empty($this->aError) ? [] : $this->aError;
	}

	/**
	 * SET OVERWRITING
	 *
	 * @param bool $bOverwriting [optional]
	 * @return $this
	 */
	public function setOverwriting($bOverwriting = false) {
		$this->bOverwriting = (bool) $bOverwriting;
		return $this;
	}

	/**
	 * SET JPG QUALITY
	 *
	 * @param int $iJpgQuality [optional]
	 * @return $this
	 */
	public function setJpgQuality($iJpgQuality = null) {
		$this->_iJpgQuality = (int) (null === $iJpgQuality ? self::DEFAULT_JPG_QUALITY : $iJpgQuality);
		return $this;
	}

	/**
	 * SET PNG QUALITY
	 *
	 * @param int $iPngCompression [optional] 0-9
	 * @return $this
	 */
	public function setPngCompression($iPngCompression = null) {
		$this->_iPngCompression = (int) (null === $iPngCompression ? self::DEFAULT_PNG_COMPRESSION : $iPngCompression);
		return $this;
	}

	/**
	 * SET SOURCE COORDINATE
	 *
	 * @param mixed $mSrcX
	 * @param mixed $mSrcY
	 * @return $this
	 */
	public function setSourceCoordinate($mSrcX, $mSrcY) {

		if(!is_numeric($mSrcX) && (!is_string($mSrcX) || ($mSrcX !== 'LEFT' && $mSrcX !== 'CENTER' && $mSrcX !== 'RIGHT'))) {
			$this->aError[] = '->setSourceCoordinate(...) @var (mixed) $mSrcX : Integer or String(\'LEFT\',\'CENTER\',\'RIGHT\') needed.';
			return $this;
		}

		if(!is_numeric($mSrcY) && (!is_string($mSrcY) || ($mSrcY !== 'TOP' && $mSrcY !== 'MIDDLE' && $mSrcY !== 'BOTTOM'))) {
			$this->aError[] = '->setSourceCoordinate(...) @var (mixed) $mSrcY : Integer or String(\'TOP\',\'MIDDLE\',\'BOTTOM\') needed.';
			return $this;
		}

		$this->mSrcX = $mSrcX;
		$this->mSrcY = $mSrcY;

		return $this;

	}

	/**
	 * CALCULATING SOURCE COORDINATE
	 *
	 * @return bool
	 */
	private function calculatingSourceCoordinate() {

		if(!$this->iInputWidth || !$this->iOutputWidth || !$this->iInputHeight || !$this->iOutputHeight || !$this->iRatio) {
			if(!$this->iInputWidth) { $this->aError[] = '->calculatingSourceCoordinate(...) @var (int) input_width : Can\'t find Source Width.'; }
			if(!$this->iInputHeight) { $this->aError[] = '->calculatingSourceCoordinate(...) @var (int) input_height : Can\'t find Source Height.'; }
			if(!$this->iOutputWidth) { $this->aError[] = '->calculatingSourceCoordinate(...) @var (int) output_width : Can\'t find Source Width.'; }
			if(!$this->iOutputHeight) { $this->aError[] = '->calculatingSourceCoordinate(...) @var (int) output_height : Can\'t find Height.'; }
			if(!$this->iRatio) { $this->aError[] = '->calculatingSourceCoordinate(...) @var (int) ratio : Can\'t find Source Ratio.'; }
			$this->aError[] = '->calculatingSourceCoordinate(...) WARNING : Use this method after setting image src & dst and new width & height!';
			return false;
		}

		if(!is_numeric($this->mSrcX)) {
			// LEFT X
			if($this->mSrcX === 'LEFT') { $this->mSrcX = 0; }

			// CENTER X
			if($this->mSrcX === 'CENTER') { $this->mSrcX = ($this->iInputWidth - $this->iOutputWidth / $this->iRatio) / 2; }

			// RIGHT X
			if($this->mSrcX === 'RIGHT') { $this->mSrcX = $this->iInputWidth - $this->iOutputWidth / $this->iRatio; }
		}

		if(!is_numeric($this->mSrcY)) {
			// TOP Y
			if($this->mSrcY === 'TOP') { $this->mSrcY = 0; }

			// MIDDLE Y
			if($this->mSrcY === 'MIDDLE') { $this->mSrcY = ($this->iInputHeight - $this->iOutputHeight / $this->iRatio) / 2; }

			// RIGHT Y
			if( $this->mSrcY === 'BOTTOM' ) { $this->mSrcY = $this->iInputHeight - $this->iOutputHeight / $this->iRatio; }
		}

		return (is_numeric($this->mSrcX) && is_numeric($this->mSrcY));

	}

	/**
	 * SET EXTENSION of the image file
	 *
	 * @param string $sWhich 'INPUT' | 'OUTPUT'
	 * @param string $sFullPath
	 * @return bool
	 */
	private function setExtension($sWhich, $sFullPath) {

		if (!$sWhich || !is_string($sWhich) || ($sWhich != 'INPUT' && $sWhich != 'OUTPUT')) {
			$this->aError[] = '->setExtension(...) @var (string) $sWhich is needed and have to be equal of \'INPUT\' or \'OUTPUT\'.';
			return false;
		}

		if (!$sFullPath || !is_string($sFullPath)) {
			$this->aError[] = '->setExtension(...) @var (string) $sFullPath is needed.';
			return false;
		}

		$sExtension = strtolower(substr(strrchr($sFullPath, '.'), 1));
		if (!$sExtension || !is_string($sExtension) || ($sExtension !== 'jpg' && $sExtension !== 'jpeg' && $sExtension !== 'gif' && $sExtension !== 'png')) {
			$this->aError[] = '->setExtension(...) $sFullPath : The extension of the image is not recognized.';
			return false;
		}

		if ($sExtension === 'jpeg') { $sExtension = 'jpg'; }

		if ($sWhich == 'INPUT') {
			$this->sInputExtension = $sExtension;
		}
		else {
			$this->sOutputExtension = $sExtension;
		}

		return true;

	}

	/**
	 * SET INPUT SIZE
	 *
	 * @param string $sInputPath
	 * @return bool
	 */
	private function setInputSize($sInputPath) {

		if (!$sInputPath || !is_string($sInputPath)) {
			$this->aError[] = '->setInputSize(...) @var (string) $sInputPath is needed.';
			return false;
		}

		if (!list($this->iInputWidth, $this->iInputHeight) = getimagesize($sInputPath)) {
			$this->aError[] = '->setInputSize(...) @var (string) input_path : Can\'t set Width & Height.';
			return false;
		}

		if (!$this->iInputWidth || !$this->iInputHeight) {
			$this->aError[] = '->setInputSize(...) Width or Height empty.';
			return false;
		}

		return true;

	}

	/**
	 * SET OUTPUT SIZE
	 *
	 * @param int $sWidth IN PIXEL (px)
	 * @param int $sHeight IN PIXEL (px)
	 * @return $this
	 */
	public function setOutputSize($sWidth, $sHeight) {

		if (!$sWidth || !is_int($sWidth) || !$sHeight || !is_int($sHeight) ) {
			$this->aError[] = '->setOutputSize(...) @var (int) $sWidth & $iSourceHeighteigh is needed and can\'t be null.';
			return $this;
		}

		$this->iOutputWidth = $sWidth;
		$this->iOutputHeight = $sHeight;

		return $this;

	}

	/**
	 * SET INPUT PATH
	 *
	 * @param string $sInputPath
	 * @return $this
	 */
	public function setInputPath($sInputPath) {

		if (!$sInputPath || !is_string($sInputPath)) {
			$this->aError[] = '->setInputPath(...) @var (string) $sInputPath is needed.';
			return $this;
		}

		if (!file_exists($sInputPath) || !is_file($sInputPath)) {
			$this->aError[] = '->setInputPath(...) $sInputPath is not a valid file.';
			return $this;
		}

		if (!$this->setExtension('INPUT', $sInputPath)) {
			$this->aError[] = '->setInputPath(...) Extension problem. Can not continue.';
			return $this;
		}

		if (!$this->setInputSize($sInputPath)) {
			$this->aError[] = '->setInputPath(...) Size problem. Can not continue.';
			return $this;
		}

		$this->sInputPath = $sInputPath;

		return $this;

	}

	/**
	 * SET OUTPUT PATH
	 *
	 * @param string $sOutputPath
	 * @return $this
	 */
	public function setOutputPath($sOutputPath) {

		if (!$sOutputPath || !is_string($sOutputPath)) {
			$this->aError[] = '->setOutputPath(...) @var (string) $sOutputPath is needed.';
			return $this;
		}

		if (!$this->bOverwriting && file_exists($sOutputPath)) {
			$this->aError[] = '->setOutputPath(...) $sOutputPath already exist.';
			return $this;
		}

		if (!$this->setExtension('OUTPUT', $sOutputPath)) {
			$this->aError[] = '->setOutputPath(...) Extension problem. Can not continue.';
			return $this;
		}

		$this->sOutputPath = $sOutputPath;

		return $this;

	}

	/**
	 * PROCESS
	 *
	 * @param int $iSourceWidth Source width
	 * @param int $iSourceHeight Source heigth
	 * @return bool
	 */
	private function process($iSourceWidth, $iSourceHeight) {

		if($this->aError) {
			$this->aError[] = '->process(...) Shutdown due to an error upstream process.';
			return false;
		}

		$bCreateInputRessource = $this->createInputRessource();
		if(!$bCreateInputRessource) {
			$this->aError[] = '->process(...) Shutdown due to an error in creating input image ressource.';
			return false;
		}

		$bCreateOutputRessource = $this->createOutputRessource();
		if(!$bCreateOutputRessource) {
			$this->aError[] = '->process(...) Shutdown due to an error in creating output image ressource.';
			return false;
		}

		$bCoordinate = $this->calculatingSourceCoordinate();
		if(!$bCoordinate) {
			$this->aError[] = '->process(...) Shutdown due to an error in calculating source coordinate of the image.';
			return false;
		}

		$bResampled = $this->outputImageResampled($iSourceWidth, $iSourceHeight);
		if(!$bResampled) {
			$this->aError[] = '->process(...) Shutdown due to an error in resampling image.';
			return false;
		}

		$bSave = $this->saveOutputImage();
		if(!$bSave) {
			$this->aError[] = '->process(...) Shutdown due to an error in saving final image.';
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
	private function createInputRessource() {

		switch($this->sInputExtension) {
			case 'gif':
				$this->rInputRessource = imagecreatefromgif( $this->sInputPath );
				break;
			case 'jpg':
				$this->rInputRessource = imagecreatefromjpeg( $this->sInputPath );
				break;
			case 'png':
				$this->rInputRessource = imagecreatefrompng( $this->sInputPath );
				break;
			default :
				$this->aError[] = '->setExtension(...) $sFullPath : The extension of the image is not recognized.';
				return false;
		}

		if (!$this->rInputRessource) {
			$this->aError[] = '->setExtension(...) $sFullPath : The extension of the image is not recognized.';
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
	private function createOutputRessource() {

		if(!$this->iOutputWidth || !$this->iOutputHeight) {
			$this->aError[] = '->createOutputRessource(...) output_width | output_height  : New width and height are needed.';
			return false;
		}

		$this->rOutputRessource = imagecreatetruecolor( $this->iOutputWidth, $this->iOutputHeight ) ;
		if (empty($this->sOutputExtension)) {
			$this->aError[] = '->createOutputRessource(...) Can\'t find output_extension.';
			return false;
		}

		if($this->sOutputExtension === 'gif' || $this->sOutputExtension === 'png') {
			imagecolortransparent($this->rOutputRessource, imagecolorallocatealpha($this->rOutputRessource, 0, 0, 0, 127));
			imagealphablending($this->rOutputRessource, false);
			imagesavealpha($this->rOutputRessource, true);
		}

		return (bool) $this->rOutputRessource;

	}

	/**
	 * Image Resizing
	 *
	 * @param int $iSourceWidth
	 * @param int $iSourceHeight
	 * @return bool
	 */
	private function outputImageResampled($iSourceWidth, $iSourceHeight) {
		return imagecopyresampled($this->rOutputRessource, $this->rInputRessource, 0, 0, $this->mSrcX, $this->mSrcY, $this->iOutputWidth, $this->iOutputHeight, $iSourceWidth, $iSourceHeight);
	}

	/**
	 * Save Image
	 *
	 * @return bool
	 */
	private function saveOutputImage() {

		if (!$this->sOutputExtension) {
			$this->aError[] = '->saveOutputImage(...) Can\'t find output_extension.';
			return false;
		}

		if (!$this->rOutputRessource) {
			$this->aError[] = '->saveOutputImage(...) Can\'t find output_ressource.';
			return false;
		}

		if (!$this->sOutputPath) {
			$this->aError[] = '->saveOutputImage(...) Can\'t find output_path.';
			return false;
		}

		switch($this->sOutputExtension) {
			case 'gif':
				imagegif($this->rOutputRessource, $this->sOutputPath);
				break;
			case 'jpg':
				imagejpeg($this->rOutputRessource, $this->sOutputPath, $this->_iJpgQuality);
				break;
			case 'png':
				imagepng($this->rOutputRessource, $this->sOutputPath, $this->_iPngCompression);
				break;
			default :
				$this->aError[] = '->saveOutputImage(...) output_extension : The extension of the image is not recognized.';
				return false;
		}

		return true;

	}

	/**
	 * CLEAR CACHE
	 */
	private function clear_cache() {
		@ImageDestroy($this->rInputRessource);
		@ImageDestroy($this->rOutputRessource);
	}

	/**
	 * IMAGE BACKGROUND FULL COVERED
	 *
	 * @param bool $bForce [optional] Force enlarge covered, else the source image can't be smaller than the ouput
	 * @return bool
	 */
	public function cover($bForce = false) {

		if (!$bForce && ($this->iInputWidth < $this->iOutputWidth || $this->iInputHeight < $this->iOutputHeight)) {
			$this->aError[] = '->cover(...) Input image is too small. If you wan\'t to enlarge, you can force it';
			return false;
		}

		$this->iRatio = max($this->iOutputWidth/$this->iInputWidth, $this->iOutputHeight/$this->iInputHeight);

		$iSourceHeight = $this->iOutputHeight / $this->iRatio;
		$iSourceWidth = $this->iOutputWidth / $this->iRatio;

		$bProcess = $this->process($iSourceWidth, $iSourceHeight);
		if (!$bProcess) {
			$this->aError[] = '->cover(...) Process crash.';
			return false;
		}

		return true;

	}

	/**
	 * IMAGE CROP
	 *
	 * @param bool $bForce [optional] Force enlarge crop, else the source image do not resized
	 * @return bool
	 */
	public function crop($bForce = false) {

		if (!$bForce && ($this->iInputWidth < $this->iOutputWidth || $this->iInputHeight < $this->iOutputHeight)) {
			$this->iRatio = 1;
			$iSourceHeight = $this->iOutputHeight;
			$iSourceWidth = $this->iOutputWidth;
		}
		else {
			$this->iRatio = min($this->iOutputWidth/$this->iInputWidth , $this->iOutputHeight/$this->iInputHeight);
			$iSourceHeight = $this->iOutputHeight / $this->iRatio;
			$iSourceWidth = $this->iOutputWidth / $this->iRatio;
		}

		$bProcess = $this->process($iSourceWidth, $iSourceHeight);
		if (!$bProcess) {
			$this->aError[] = '->crop(...) Process crash.';
			return false;
		}

		return true;

	}

	/**
	 * MY OWN SIZE
	 *
	 * @param int $iWidth [optional]
	 * @param int $iHeight [optional]
	 * @return bool
	 */
	public function myOwnSize($iWidth = null, $iHeight = null) {

		if ($iWidth === null && $iHeight === null) {
			$this->aError[] = '->myOwnSize(...) @var $sWidth or $iHeight needed.';
			return false;
		}

		if ($iWidth && $iHeight) {
			$this->aError[] = '->myOwnSize(...) @var $sWidth & $iHeight ONLY ONE is needed, the other keeps ratio.';
			return false;
		}

		if ($iWidth && !is_numeric($iWidth) || $iHeight && !is_numeric($iHeight)) {
			$this->aError[] = '->myOwnSize(...) @var $sWidth or $iHeight have to be numeric values.';
			return false;
		}

		if ($iWidth) {
			$iSourceWidth = $this->iOutputWidth / $iWidth * $this->iInputWidth;
			$iSourceHeight = $this->iOutputHeight / ($this->iInputHeight / $this->iInputWidth * $iWidth) * $this->iInputHeight;
		} else {
			$iSourceWidth = $this->iOutputWidth / ($this->iInputWidth / $this->iInputHeight * $iHeight) * $this->iInputWidth;
			$iSourceHeight = $this->iOutputHeight / $iHeight * $this->iInputHeight;
		}

		$this->iRatio = min($this->iOutputWidth/$iSourceWidth, $this->iOutputHeight, $iSourceHeight);

		$bProcess = $this->process($iSourceWidth, $iSourceHeight);
		if (!$bProcess) {
			$this->aError[] = '->myOwnSize(...) Process crash.';
			return false;
		}

		return true;

	}

	/**
	 * SAME SIZE
	 *
	 * @return bool
	 */
	public function sameSize() {

		$this->iRatio = 1;
		$this->iOutputWidth = $this->iInputWidth;
		$this->iOutputHeight = $this->iInputHeight;

		$bProcess = $this->process($this->iInputWidth, $this->iInputHeight);
		if (!$bProcess) {
			$this->aError[] = '->sameSize(...) Process crash.';
			return false;
		}

		return true;

	}

	/**
	 * GET IMAGE QUALITY FROM FILE
	 *
	 * @param string $sImgFilePath
	 * @return int|null
	 */
	public function getImageQuality($sImgFilePath) {

		# VERIFY
		if(!file_exists($sImgFilePath) || !is_file($sImgFilePath)) { return null; }

		# GET INFOS
		exec("identify -verbose $sImgFilePath", $aOutpout);
		if(!$aOutpout) { return null; }

		# DETECT "Quality" line
		foreach ($aOutpout as $sLine) {
			$sLine = trim($sLine);
			if(strpos($sLine, 'Quality: ') !== 0) { continue; }
			$aQuality = explode(': ', $sLine);
			if(isset($aQuality[1])) { return intval($aQuality[1]); }
		}

		return null;

	}

}
