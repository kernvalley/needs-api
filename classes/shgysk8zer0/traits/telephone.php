<?php
namespace shgysk8zer0\Traits;

trait Telephone
{
	private $_telephone;

	final public function getTelephone():? string
	{
		return $this->_telephone;
	}

	final public function setTelephone(?string $val): void
	{
		$this->_telephone = $val;
	}
}
