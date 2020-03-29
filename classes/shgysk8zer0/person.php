<?php
namespace shgysk8zer0;
use \shgysk8zer0\{ImageObject};
use \shgysk8zer0\PHPAPI\{File};
use \PDO;
use \Throwable;

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
			header('Content-Type: application/json');
			http_response_code(500);
			exit(json_encode($this));
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
				`email`     = COALESCE(:email, `Person`.`email`),
				`telephone` = COALESCE(:telephone, `Person`.`telephone`),
				`address`   = COALESCE(:address, `Person`.`address`)');

			if ($stm->execute([
				'uuid'      => $this->getIdentifier(),
				'name'      => $this->getName(),
				'email'     => $this->getEmail(),
				'telephone' => $this->getTelephone(),
				'address'   => $this->getAddress() !== null ? $this->getAddress()->save($pdo) : null,
			]) and $stm->rowCount() !== 0) {
				header('X-Person-UUID: ' . $this->getIdentifier() ?? 'null');
				return $this->getIdentifier();
			} else {
				return null;
			}
		}
	}

	final public function setImageFromFile(PDO $pdo, File $img, string $fname):? ImageObject
	{
		try {
			if ($img->hasError()) {
				return null;
			} elseif (@file_exists($fname)) {
				return null;
			} elseif ($img->saveAs($fname)) {
				$image = new ImageObject();
				$image->setUrl($img->url);
				// @TODO get image encoding & dimensions
				// @TODO resize & optimize image
				if ($img_uuid = $image->save($pdo)) {
					$stm = $pdo->prepare('UPDATE `Person`
						SET `image` = :img
						WHERE `identifier` = :uuid
						LIMIT 1;');

					if ($stm->execute([
						'img' => $img_uuid,
						'uuid' => $this->getIdentifier(),
					])) {
						return $image;
					} else {
						return null;
					}
				} else {
					return null;
				}
			} else {
				return null;
			}
		} catch (Throwable $e) {
			return null;
		}
	}

	public function valid(): bool
	{
		return $this->getName() !== null && $this->getEmail() !== null;
	}

	public static function getJoins(): array
	{
		return [
			'LEFT OUTER JOIN `PostalAddress` ON `' . static:: TYPE . '`.`address` = `PostalAddress`.`identifier`',
			'LEFT OUTER JOIN `ImageObject` ON `' . static::TYPE . '`.`image` = `ImageObject`.`identifier`',
		];
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

	public static function getFromIdentifier(PDO $pdo, string $uuid):? self
	{
		$stm = $pdo->prepare('SELECT ' . static::getSQL() .' AS `json`
			FROM ' . static::TYPE .'
			' . join("\n", static::getJoins()) .'
			WHERE `' . static::TYPE . '`.`identifier` = :uuid
			LIMIT 1;');

		if ($stm->execute(['uuid' => $uuid]) and $result = $stm->fetchObject()) {
			return new self(json_decode($result->json));
		} else {
			return null;
		}
	}

	public static function searchByName(PDO $pdo, string $name, int $page = 1, int $count = 25): array
	{
		$q = '%' . str_replace([' '], ['%'], $name) . '%';
		$stm = $pdo->prepare('SELECT ' . static::getSQL() .' AS `json`
			FROM ' . static::TYPE .'
			' . join("\n", static::getJoins()) .'
			WHERE `' . static::TYPE . '`.`name` LIKE :name
			LIMIT ' . self::_getPages($page, $count) . ';');

		if ($stm->execute(['name' => $q]) and $results = $stm->fetchAll(PDO::FETCH_CLASS)) {
			return array_map(function(object $result): self
			{
				return new self(json_decode($result->json));
			}, $results);
		} else {
			return [];
		}
	}
}
