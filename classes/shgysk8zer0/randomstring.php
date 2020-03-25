<?php
namespace shgysk8zer0;
// (new \shgysk8zer0\RandomString(30, true, true, true, true))->saveAs(HMAC_FILE);
class RandomString
{
	private const LOWER = [
		'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
		'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'
	];

	private const UPPER = [
		'A','B', 'C', 'D',' E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
		'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
	];

	private const NUMS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

	private const SPECIAL = [];

	private $_length  = 12;
	private $_lower   = true;
	private $_upper   = true;
	private $_nums    = false;
	private $_special = false;

	public function __construct(
		int  $length  = 12,
		bool $lower   = true,
		bool $upper   = true,
		bool $nums    = false,
		bool $special = false
	)
	{
		$this->_length = $length;
		$this->_lower = $lower;
		$this->_upper = $upper;
		$this->_nums = $nums;
		$this->_special = $special;
	}

	final public function __toString(): string
	{

	}

	final public function saveAs(string $fname): bool
	{
		return file_put_contents($fname, $this);
	}
}
