<?php
namespace Coercive\Utility\ImgProcess;

use Exception;

/**
 * ImgProcess Orientation
 *
 * Exif info
 * @doc https://exiftool.org/TagNames/EXIF.html
 *
 * @package 	Coercive\Utility\ImgProcess
 * @link		https://github.com/Coercive/ImgProcess
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2023 Anthony Moral
 * @license 	MIT
 */
class Orientation
{
	const NotSpecified = 0;
	const Horizontal = 1; # NormalOrientation
	const MirrorHorizontal = 2;
	const UpsideDown = 3;
	const MirrorVertical = 4;
	const MirrorHorizontalAndRotateRight = 5;
	const RotateLeft = 6;
	const MirorHorizontalAndRotateLeft = 7;
	const RotateRight = 8;

	/**
	 * EXIFS data
	 *
	 * @var array $exifs
	 */
	private array $exifs;

	/**
	 * Current image orientation info from exif
	 *
	 * @var int $orientation
	 */
	private int $orientation = 0;

	/**
	 * ImgProcess Orientation constructor.
	 *
	 * @param string|resource $image
	 * @return void
	 * @throws Exception
	 */
	public function __construct($image)
	{
		if(!function_exists('exif_read_data')) {
			throw new Exception('exif_read_data() function does not exist.');
		}

		if (!is_string($image) && !is_resource($image)) {
			throw new Exception('Invalid $image param type : must be string (path) or resource.');
		}

		if(is_string($image) && !is_file($image)) {
			throw new Exception('Image path does not exist, or is not a file.');
		}

		$this->exifs = exif_read_data($image) ?: [];
		$this->orientation = intval($this->exifs['Orientation'] ?? $this->exifs['IFD0']['Orientation'] ?? 0);
	}

	/**
	 * Raw exifs from file
	 *
	 * @return array
	 */
	public function getExifs(): array
	{
		return $this->exifs;
	}

	/**
	 * Raw orientation number
	 *
	 * @return int
	 */
	public function get(): int
	{
		return $this->orientation;
	}

	/**
	 * Interpret the angle of rotation in degrees.
	 *
	 * @return int
	 */
	public function getAngle(): int
	{
		switch($this->orientation)
		{
			case self::RotateRight:
			case self::MirrorHorizontalAndRotateRight:
				return 90;

			case self::UpsideDown:
				return 180;

			case self::RotateLeft:
			case self::MirorHorizontalAndRotateLeft:
				return 270;

			case self::NotSpecified:
			case self::Horizontal:
			case self::MirrorHorizontal:
			case self::MirrorVertical:
			default:
				return 0;
		}
	}

	/**
	 * Interpret the rotation mode.
	 *
	 * @return int
	 */
	public function getFlip(): int
	{
		switch($this->orientation)
		{
			case self::MirrorHorizontal:
			case self::MirorHorizontalAndRotateLeft:
			case self::MirrorHorizontalAndRotateRight:
				return IMG_FLIP_HORIZONTAL;

			case self::MirrorVertical:
				return IMG_FLIP_VERTICAL;

			case self::NotSpecified:
			case self::Horizontal:
			case self::RotateRight:
			case self::RotateLeft:
			case self::UpsideDown:
			default:
				return 0;
		}
	}
}