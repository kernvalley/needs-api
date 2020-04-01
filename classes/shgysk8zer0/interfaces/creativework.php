<?php
namespace shgysk8zer0\Interfaces;

use \shgysk8zer0\{Person};

interface CreativeWork extends Thing
{
	// @SEE https://schema.org/CreativeWork

	public function getAuthor():? Person;

	public function setAuthor(?Person $val): void;

	public function getCopyrightYear():? int;

	public function setCopyrightYear(?int $val): void;

	public function getHeadline():? string;

	public function setHeadline(?string $val): void;

	public function getText():? string;

	public function setText(?string $val): void;
}
