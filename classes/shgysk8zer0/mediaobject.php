<?php
namespace shgysk8zer0;

class MediaObject extends CreativeWork implements Interfaces\MediaObject
{
	public const TYPE = 'MediaObject';

	private $_height = null;

	private $_uploadDate = null;

	private $_width = null;

	public function jsonSerialize(): array
	{
		return array_filter(array_merge(
			parent::jsonSerialize(),
			[
				'height'      => $this->getHeight(),
				'width'       => $this->getWidth(),
				'uploadDate'  => $this->getUploadDate(),
			]
		));
	}

	final public function getHeight():? int
	{
		return $this->_height;
	}

	final public function setHeight(?int $val): void
	{
		$this->_height = $val;
	}

	final public function getUploadDate():? Date
	{
		return $this->_uploadDate;
	}

	final public function setUploadDate(?Date $val): void
	{
		$this->_uploadDate = $val;
	}

	final public function getWidth():? int
	{
		return $this->_width;
	}

	final public function setWidth(?int $val): void
	{
		$this->_width = $val;
	}

	public function setFromObject(?object $data): void
	{
		parent::setFromObject($data);
		$this->setHeight($data->height ?? null);
		$this->setWidth($data->width ?? null);

		if (isset($data->uploadDate)) {
			$this->setUploadDate(is_string($data->uploadDate) ? new Date($data->uploadDate) : $data->uploadDate);
		}
	}
}
