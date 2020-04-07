<?php
namespace shgysk8zer0;
use \JSONSerializable;

final class NotificationAction implements JSONSerializable
{
	private $_action = null;

	private $_title = null;

	private $_icon = null;

	final public function __construct(?object $action = null)
	{
		if (isset($action)) {
			$this->setAction($action->action);
			$this->setIcon($action->icon);
			$this->setTitle($action->title);
		}
	}

	final public function jsonSerialize(): array
	{
		return array_filter([
			'action' => $this->getAction(),
			'icon'   => $this->getIcon(),
			'title'  => $this->getTitle(),
		]);
	}

	final public function __debugInfo(): array
	{
		return [
			'action' => $this->getAction(),
			'icon'   => $this->getIcon(),
			'title'  => $this->getTitle(),
		];
	}

	final public function getAction():? string
	{
		return $this->_action;
	}

	final public function setAction(?string $val = null): void
	{
		$this->_action = $val;
	}

	final public function getIcon():? string
	{
		return $this->_icon;
	}

	final public function setIcon(?string $val = null): void
	{
		$this->_icon = $val;
	}

	final public function getTitle():? string
	{
		return $this->_title;
	}

	final public function setTitle(?string $val = null): void
	{
		$this->_title = $val;
	}

	final public function valid(): bool
	{
		return isset($this->_title, $this->_action);
	}
}
