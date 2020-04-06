<?php
namespace shgysk8zer0;

use \shgysk8zer0\PHPAPI\{UUID};
use \JSONSerializable;

class RequestItem implements JSONSerializable
{
	private $_identifier = null;

	private $_text       = null;

	private $_qty        = null;

	private $_items      = null;

	public function toString(): string
	{
		return json_encode($this);
	}

	public function jsonSerialize(): array
	{
		return aray_filter([
			'identifier' => $this->getIdentifier(),
			'quantity'   => $this->getQuantity(),
			'text'       => $this->getText(),
		]);
	}

	final public function getIdentifier():? string
	{
		return $this->_identifier;
	}

	final public function setIdentifier(?string $val): void
	{
		$this->_identifier = $val;
	}

	final public function getQuantity():? int
	{
		return $this->_qty;
	}

	final public function setQuantity(?int $val): void
	{
		$this->_qty = $val;
	}

	final public function getText():? string
	{
		return $this->_text;
	}

	final public function setText(?string $val): void
	{
		$this->_text = $val;
	}

	final public function getItems():? array
	{
		return $this->items;
	}

	final public function setItems(object... $val): void
	{
		$this->_items = $val;
	}

	final public function fetchItems(PDO $pdo, int $offset = 0): array
	{
		$stm = $pdo->prepare(sprintf('SELECT `id`,
			`quantity`,
			`item`,
			DATE_FORMAT(`created`, "%s") AS `created`
			FROM `items`
			WHERE `request` = :uuid
			LIMIT %d, 50', '%Y-%m-%dT%TZ', $offset));

		if ($stm->execute(['request' => $this->getIdentifier()]) and $items = $stm->fetchAll(PDO::FETCH_CLASS)) {
			return $items;
		} else {
			return [];
		}
	}

	final public function valid(): bool
	{
		return isset($this->_text, $this->_quantity);
	}
}
