<?php
namespace shgysk8zer0\Interfaces;

interface PostalAddress extends ContactPoint
{
	public function getStreetAddress():? string;

	public function setStreetAddress(?string $val): void;

	public function getPostOfficeBoxNumber():? string;

	public function setPostOfficeBoxNumber(?string $val): void;

	public function getAddressLocality():? string;

	public function setAddressLocality(?string $val): void;

	public function getAddressRegion():? string;

	public function setAddressRegion(?string $val): void;

	public function getPostalCode():? string;

	public function setPostalCode(?string $val): void;

	public function getAddressCountry():? string;

	public function setAddressCountry(?string $val): void;
}
