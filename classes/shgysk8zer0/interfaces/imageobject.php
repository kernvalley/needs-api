<?php
namespace shgysk8zer0\Interfaces;

interface ImageObject extends MediaObject
{
	public function getCaption():? string;

	public function setCaption(?string $val): void;
}
