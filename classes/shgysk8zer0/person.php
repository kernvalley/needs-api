<?php
namespace shgysk8zer0;
use \PDO;

class Person extends Thing
{
	public const TYPE = 'Person';
	use Traits\Email;
	use Traits\Address;
	use Traits\Telephone;

	public function jsonSerialize(): array
	{
		return array_filter(array_merge(
			parent::jsonSerialize(),
			[
				'email'     => $this->getEmail(),
				'telephone' => $this->getTelephone(),
				'address'   => $this->getAddress(),
			]
		));
	}

	public function setFromObject(object $data): void
	{
		parent::setFromObject($data);
		$this->setEmail($data->email ?? null);
		$this->setTelephone($data->telephone ?? null);

		if (isset($data->address)) {
			$this->setAddress(new PostalAddress($data->address));
		}
	}

	public function save(PDO $pdo):? string
	{
		if (! $this->valid()) {
			return null;
		} else {
			if ($this->getIdentifier() === null) {
				$this->setIdentifier(self::generateUUID());
			}
			$stm = $pdo->prepare('INSERT INTO `Person` (
				`identifier`,
				`name`,
				`email`,
				`telephone`,
				`address`
			) VALUES (
				:uuid,
				:name,
				:email,
				:telephone,
				:address
			) ON DUPLICATE KEY UPDATE
				`name`      = COALESCE(:name, `Person`.`name`),
				`email`     = :email,
				`telephone` = :telephone,
				`address`   = :address;');

			if ($stm->execute([
				'uuid'      => $this->getIdentifier(),
				'name'      => $this->getName(),
				'email'     => $this->getEmail(),
				'telephone' => $this->getTelephone(),
				'address'   => $this->getAddress(),
			])) {
				return $this->getIdentifier();
			} else {
				return null;
			}
		}
	}

	public function valid(): bool
	{
		return $this->getName() !== null;
	}

	public static function getSQL(): string
	{
		return sprintf('JSON_OBJECT(
			"identifier", `Person`.`identifier`,
			"name", `Person`.`name`,
			"email", `Person`.`email`,
			"telephone", `Person`.`telephone`,
			"image", %s,
			"address", %s
		)', ImageObject::getSQL(), PostalAddress::getSQL());
	}
}
