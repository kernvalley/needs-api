<?php
namespace shgysk8zer0;
use \shgysk8zer0\PHPAPI\{File};

class Image
{
	private $_resource = null;

	final public function __destruct()
	{
		if ($this->loaded()) {
			imagedestroy($this->_resource);
		}
	}

	final public function __debugInfo(): array
	{
		return [
			'height' => $this->getHeight(),
			'width'  => $this->getWidth(),
		];
	}

	final public function getHeight():? int
	{
		if ($this->loaded()) {
			return imagesy($this->_resource);
		} else {
			return null;
		}
	}

	final public function getWidth():? int
	{
		if ($this->loaded()) {
			return imagesx($this->_resource);
		} else {
			return null;
		}
	}

	final public function loaded(): bool
	{
		return is_resource($this->_resource);
	}

	final public function rotate(
		float $angle,
		int   $bg_color           = 0,
		bool  $ignore_transparent = false
	):? self
	{
		if ($this->loaded()) {
			$res = imagerotate($this->_resource, $angle, $bg_color, $ignore_transparent ? 1 : 0);
			$img = new self();
			$img->_setResource($res);
			return $img;
		} else {
			return null;
		}
	}

	final public function resize(
		int $width,
		int $height = -1,
		int $mode   = IMG_BICUBIC
	):? self
	{
		if ($this->loaded()) {
			$res = imagescale($this->_resource, $width, $height, $mode);
			$img = new self();
			$img->_setResource($res);
			return $img;
		} else {
			return null;
		}
	}

	final public function rgb(int $red, int $green, int $blue):? int
	{
		if ($this->loaded()) {
			return imagecolorallocate($this->_resource, $red, $green, $blue);
		} else {
			return null;
		}
	}

	final public function rgba(int $red, int $green, int $blue, int $alpha = 0):? int
	{
		if ($this->loaded()) {
			return imagecolorallocatealpha($this->_resource, $red, $green, $blue, $alpha);
		} else {
			return null;
		}
	}

	final public function saveAsGIF(?string $fname = null): bool
	{
		if ($this->loaded()) {
			return imagegif($this->_resource, $fname);
		} else {
			return false;
		}
	}

	final public function saveAsJPEG(?string $fname = null, int $quality = 80): bool
	{
		if ($this->loaded()) {
			return imagejpeg($this->_resource, $fname, $quality) ?? false;
		} else {
			return false;
		}
	}

	final public function saveAsPNG(
		?string $fname   = null,
		int     $quality = 80,
		int     $filters = PNG_NO_FILTER
	): bool
	{
		if ($this->loaded()) {
			return imagepng($this->_resource, $fname, $quality / 10, $filters) ?? false;
		} else {
			return false;
		}
	}

	final public function saveAsWebP(?string $fname = null, int $quality = 80): bool
	{
		if ($this->loaded()) {
			return imagewebp($this->_resource, $fname, $quality) ?? false;
		} else {
			return false;
		}
	}

	final private function _setResource($resource): bool
	{
		if (is_resource($resource)) {
			$this->_resource = $resource;
			return true;
		} else {
			return false;
		}
	}

	final public static function loadFromFile(string $fname):? self
	{
		$resouce = null;

		switch(exif_imagetype($fname)) {
			case IMAGETYPE_JPEG:
				$resource = imagecreatefromjpeg($fname);
				break;

			case IMAGETYPE_PNG:
				$resource = imagecreatefrompng($fname);
				imagepalettetotruecolor($resource);
				imagealphablending($resource, true);
				imagesavealpha($resource, true);
				break;

			case IMAGETYPE_WEBP:
				$resource = imagecreatefromwebp($fname);
				imagepalettetotruecolor($resource);
				imagealphablending($resource, true);
				imagesavealpha($resource, true);
				break;

			case IMAGETYPE_GIF:
				$resource = imagecreatefromgif($fname);
				imagepalettetotruecolor($resource);
				imagealphablending($resource, true);
				imagesavealpha($resource, true);
				break;

			default:
				return false;
		}

		if (is_resource($resource)) {
			$img = new self();
			$img->_setResource($resource);
			return $img;
		} else {
			return null;
		}
	}

	final public static function loadFromUpload(File $file):? self
	{
		if (! $file->hasError()) {
			return static::loadFromFile($file->tmpName);
		} else {
			return null;
		}
	}
}
