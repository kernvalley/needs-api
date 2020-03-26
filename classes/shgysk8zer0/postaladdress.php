<?php
namespace shgysk8zer0;
use \PDO;

class PostalAddress extends Thing implements Interfaces\PostalAddress
{
	public const TYPE = 'PostalAddress';

	private $_streetAddress = null;

	private $_postOfficeBoxNumber = null;

	private $_addressLocality = null;

	private $_addressRegion = null;

	private $_postalCode = null;

	private $_addressCountry = null;

	public function jsonSerialize(): array
	{
		return array_filter(array_merge(
			parent::jsonSerialize(),
			[
				'streetAddress'       => $this->getStreetAddress(),
				'postOfficeBoxNumber' => $this->getPostOfficeBoxNumber(),
				'addressLocality'     => $this->getAddressLocality(),
				'addressRegion'       => $this->getAddressRegion(),
				'postalCode'          => $this->getPostalCode(),
				'addressCountry'      => $this->getAddressCountry(),
			]
		));
	}

	final public function getStreetAddress():? string
	{
		return $this->_streetAddress;
	}

	final public function setStreetAddress(?string $val): void
	{
		$this->_streetAddress = $val;
	}

	final public function getPostOfficeBoxNumber():? string
	{
		return $this->_postOfficeBoxNumber;
	}

	final public function setPostOfficeBoxNumber(?string $val): void
	{
		$this->_postOfficeBoxNumber = $val;
	}

	final public function getAddressLocality():? string
	{
		return $this->_addressLocality;
	}

	final public function setAddressLocality(?string $val): void
	{
		$this->_addressLocality = $val;
	}

	final public function getAddressRegion():? string
	{
		return $this->_addressRegion;
	}

	final public function setAddressRegion(?string $val): void
	{
		$this->_addressRegion = $val;
	}

	final public function getPostalCode():? string
	{
		return $this->_postalCode;
	}

	final public function setPostalCode(?string $val): void
	{
		$this->_postalCode = $val;
	}

	final public function getAddressCountry():? string
	{
		return $this->_addressCountry;
	}

	final public function setAddressCountry(?string $val): void
	{
		$this->_addressCountry = $val;
	}

	public function setFromObject(?object $data): void
	{
		$this->setStreetAddress($data->streetAddress);
		$this->setPostOfficeBoxNumber($data->postOfficeBoxNumber);
		$this->setAddressLocality($data->addressLocality);
		$this->setAddressRegion($data->addressRegion);
		$this->setAddressCountry($data->addressCountry);
		$this->setPostalCode($data->postalCode);
	}

	public static function getSQL(): string
	{
		return 'JSON_OBJECT(
			"identifier", `PostalAddress`.`identifier`,
			"streetAddress", `PostalAddress`.`streetAddress`,
			"postOfficeBoxNumber", `PostalAddress`.`postOfficeBoxNumber`,
			"addressLocality", `PostalAddress`.`addressLocality`,
			"addressRegion", `PostalAddress`.`addressRegion`,
			"postalCode", `PostalAddress`.`postalCode`,
			"addressCountry", `PostalAddress`.`addressCountry`
		)';
	}
}
