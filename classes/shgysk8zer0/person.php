<?php
namespace shgysk8zer0;
use \PDO;

class Person extends Thing
{
	use Traits\Email;
	use Traits\Address;
	use Traits\Telephone;

	const TYPE = 'Person';

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

	public function save(PDO $pdo): bool
	{
		$stm = $pdo->prepare('INSERT OR UPDATE INTO `Person` (
			`uuid`,
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
		)');

		return false;
	}
}
