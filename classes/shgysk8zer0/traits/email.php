<?php
namespace shgysk8zer0\Traits;

trait Email
{
	private $_email = null;

	final public function getEmail():? string
	{
		return $this->_email;
	}

	final public function setEmail(?string $val): void
	{
		if (isset($val)) {
			if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
				$this->_email = $val;
			} else {
				throw new \InvalidArgumentException('Expected a valid email address');
			}
		}
	}
}
