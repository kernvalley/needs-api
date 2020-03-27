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
		$this->setStreetAddress($data->streetAddress ?? null);
		$this->setPostOfficeBoxNumber($data->postOfficeBoxNumber ?? null);
		$this->setAddressLocality($data->addressLocality ?? null);
		$this->setAddressRegion($data->addressRegion ?? null);
		$this->setAddressCountry($data->addressCountry ?? null);
		$this->setPostalCode($data->postalCode ?? null);
	}

	public function save(PDO $pdo):? string
	{
		if (! $this->valid()) {
			return null;
		} else {
			if ($this->getIdentifier() === null) {
				$this->setIdentifier(self::generateUUID());
			}

			header('X-ADDR_UUID: ' . $this->getIdentifier());

			$stm = $pdo->prepare('INSERT INTO `PostalAddress` (
				`identifier`,
				`streetAddress`,
				`postOfficeBoxNumber`,
				`addressLocality`,
				`addressRegion`,
				`postalCode`,
				`addressCountry`
			) VALUES (
				:identifier,
				:streetAddress,
				:postOfficeBoxNumber,
				:addressLocality,
				:addressRegion,
				:postalCode,
				:addressCountry
			);');
			// $stm = $pdo->prepare('INSERT INTO `PostalAddress` (
			// 	`identifier`,
			// 	`streetAddress`,
			// 	`postOfficeBoxNumber`,
			// 	`addressLocality`,
			// 	`addressRegion`,
			// 	`postalCode`,
			// 	`addressCountry`
			// ) VALUES (
			// 	:identifier,
			// 	:streetAddress,
			// 	:postOfficeBoxNumber,
			// 	:addressLocality,
			// 	:addressRegion,
			// 	:postalCode,
			// 	:addressCountry
			// ) ON DUPLICATE KEY UPDATE
			// 	`streetAddress`       = :streetAddress,
			// 	`postOfficeBoxNumber` = :postOfficeBoxNumber,
			// 	`addressLocality`     = :addressLocality,
			// 	`addressRegion`       = :addressRegion,
			// 	`postalCode`          = :postalCode,
			// 	`addressCountry`      = :addressCounty;');

			if ($stm->execute([
				':identifier'          => $this->getIdentifier(),
				':streetAddress'       => $this->getStreetAddress(),
				':postOfficeBoxNumber' => $this->getPostOfficeBoxNumber() ?? '',
				':addressLocality'     => $this->getAddressLocality(),
				':addressRegion'       => $this->getAddressRegion(),
				':postalCode'          => $this->getPostalCode(),
				':addressCountry'      => $this->getAddressCountry() ?? 'US',
			]) and $stm->rowCount() === 1) {
				return $this->getIdentifier();
			} else {
				return null;
			}
		}
	}

	public function valid(): bool
	{
		return true;// isset($this->_streetAddress, $this->_addressLocality, $this->_addressRegion, $this->_postalCode);
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
