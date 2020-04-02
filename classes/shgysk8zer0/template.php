<?php
namespace shgysk8zer0;

final class Template
{
	public const DELIM = '%';

	private $_content = '';

	private $_fname = null;

	private $_data = [];

	final public function __construct(string $fname)
	{
		$this->_fname = $fname;
		$this->_content = file_get_contents($fname);
	}

	final public function __get(string $key):? string
	{
		return $this->_data[$key] ?? null;
	}

	final public function __set(string $key, string $val): void
	{
		$this->_data[$key] = $val;
	}

	final public function __isset(string $key): vool
	{
		return array_key_exists($key, $this->_data);
	}

	final public function __unset(string $key): void
	{
		usnet($this->_data[$key]);
	}

	final public function __toString(): string
	{
		$keys = array_map(function(string $key): string
		{
			return self::DELIM . strtoupper($key) . self::DELIM;
		}, array_keys($this->_data));

		return str_replace($keys, array_values($this->_data), $this->_content);
	}

	final public function __debugInfo(): array
	{
		return [
			'file'    => $this->_fname,
			'content' => $this->_content,
			'data'    => $this->_data,
		];
	}
}
