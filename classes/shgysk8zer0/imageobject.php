<?php
namespace shgysk8zer0;

class ImageObject extends MediaObject implements Interfaces\ImageObject
{
	public const TYPE = 'ImageObject';

	private $_caption = null;

	public function jsonSerialize(): array
	{
		return array_filter(array_merge(
			parent::jsonSerialize(),
			[
				'caption' => $this->getCaption(),
			]
		));
	}

	final public function getCaption():? string
	{
		return $this->_caption;
	}

	final public function setCaption(?string $val): void
	{
		$this->_caption = $val;
	}

	public function setFromObject(?object $data): void
	{
		parent::setFromObject($data);
		$this->setCaption($data->caption ?? null);
	}
}
