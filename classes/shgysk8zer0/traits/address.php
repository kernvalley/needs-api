<?php
namespace shgysk8zer0\Traits;
use \shgysk8zer0\Interfaces\PostalAddress;

trait Address
{
	private $_address = null;

	final public function getAddress():? PostalAddress
	{
		return $this->_address;
	}

	final public function setAddress(?PostalAddress $val): void
	{
		$this->_address = $val;
	}
}
