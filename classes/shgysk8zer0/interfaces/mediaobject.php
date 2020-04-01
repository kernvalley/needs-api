<?php
namespace shgysk8zer0\Interfaces;
use \shgysk8zer0\Date as Date;

interface MediaObject extends CreativeWork
{
	public function getHeight():? int;

	public function setHeight(?int $val): void;

	public function getUploadDate():? Date;

	public function setUploadDate(?Date $val): void;

	public function getWidth():? int;

	public function setWidth(?int $val): void;
}
