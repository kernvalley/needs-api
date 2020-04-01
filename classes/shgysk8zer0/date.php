<?php
namespace shgysk8zer0;

final class Date extends \DateTimeImmutable implements \JSONSerializable
{
	final public function __toString(): string
	{
		return $this->format(self::W3C);
	}

	final public function jsonSerialize(): string
	{
		return $this->format(self::W3C);
	}
}
