<?php
namespace shgysk8zer0\Interfaces;

interface Thing extends \JSONSerializable
{
	// @SEE https://schema.org/Thing

	public function getIdentifier():? string;

	public function setIdentifier(?string $val): void;

	public function getImage():? ImageObject;

	public function setImage(?ImageObject $val): void;

	public function getName():? string;

	public function setName(?string $val): void;

	public function getUrl():? string;

	public function setUrl(?string $val): void;

	public function valid(): bool;

	public static function generateUUID(): string;
}
