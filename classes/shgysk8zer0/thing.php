<?php
namespace shgysk8zer0;

class Thing extends Abstracts\Schema implements Interfaces\Thing
{
	public const TYPE = 'Thing';

	public const CONTEXT = 'https://schema.org';

	private $_identifier = null;

	private $_name = null;

	private $_image = null;

	private $_url = null;

	public function __construct(?object $data = null)
	{
		if (isset($data)) {
			$this->setFromObject($data);
		}
	}

	public function jsonSerialize(): array
	{
		return [
			'@context'   => $this::CONTEXT,
			'@type'      => $this::TYPE,
			'identifier' => $this->getIdentifier(),
			'name'       => $this->getName(),
			'image'      => $this->getImage(),
			'url'        => $this->getUrl(),
		];
	}

	public function __toString(): string
	{
		return json_encode($this);
	}

	final public function getIdentifier():? string
	{
		return $this->_identifier;
	}

	final public function setIdentifier(?string $val): void
	{
		$this->_identifier = $val;
	}

	final public function getImage():? Interfaces\ImageObject
	{
		return $this->_image;
	}

	final public function setImage(?Interfaces\ImageObject $val): void
	{
		$this->_image = $val;
	}

	final public function getName():? string
	{
		return $this->_name;
	}

	final public function setName(?string $val): void
	{
		$this->_name = $val;
	}

	final public function getUrl():? string
	{
		return $this->_url;
	}

	final public function setUrl(?string $val): void
	{
		if (filter_var($val, FILTER_VALIDATE_URL)) {
			$this->_url = $val;
		}
	}

	public function setFromObject(object $data): void
	{
		$this->setIdentifier($data->identifier ?? null);
		$this->setName($data->name ?? null);
		$this->setUrl($data->url ?? null);

		if (isset($data->image)) {
			$this->setImage(new ImageObject($data->image));
		}
	}

	public function valid(): bool
	{
		return isset($this->_identifier);
	}
}
