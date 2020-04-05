<?php
namespace shgysk8zer0;
use \shgysk8zer0\{Date};
use \shgysk8zer0\PHPAPI\{UUID};
use \shgysk8zer0\PHPAPI\Interfaces\{InputData};
use \PDO;
use \InvalidArgumentException;
use \JSONSerializable;

class Message implements JSONSerializable
{
	public const TABLE   = 'messages';

	private $_identifier = null;

	private $_name       = null;

	private $_email      = null;

	private $_telephone  = null;

	private $_subject    = null;

	private $_message    = null;

	private $_opened     = false;

	private $_created    = null;

	public function __construct(?object $data = null)
	{
		if (isset($data)) {
			$this->createFromObject($data);
		}
	}

	final public function __debugInfo(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'name'       => $this->getName(),
			'email'      => $this->getEmail(),
			'telephone'  => $this->getTelephone(),
			'subject'    => $this->getSubject(),
			'message'    => $this->getMessage(),
			'opened'     => $this->getOpened(),
			'created'    => $this->getCreated(),
			'valid'      => $this->valid(),
		];
	}

	final public function jsonSerialize(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'name'       => $this->getName(),
			'email'      => $this->getEmail(),
			'telephone'  => $this->getTelephone(),
			'subject'    => $this->getSubject(),
			'message'    => $this->getMessage(),
			'opened'     => $this->getOpened(),
			'created'    => $this->getCreated(),
		];
	}

	final public function getIdentifier():? string
	{
		return $this->_identifier;
	}

	final public function setIdentifier(?string $val = null): void
	{
		$this->_identifier = $val;
	}

	final public function getName():? string
	{
		return $this->_name;
	}

	final public function setName(?string $val = null): void
	{
		$this->_name = $val;
	}

	final public function getEmail():? string
	{
		return $this->_email;
	}

	final public function setEmail(?string $val = null): void
	{
		if (isset($val)) {
			if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
				$this->_email = $val;
			} else {
				throw new InvalidArgumentException('Not a valid email address');
			}
		}
	}

	final public function getTelephone():? string
	{
		return $this->_telephone;
	}

	final public function setTelephone(?string $val = null): void
	{
		$this->_telephone = $val;
	}

	final public function getSubject():? string
	{
		return $this->_subject;
	}

	final public function setSubject(?string $val = null): void
	{
		$this->_subject = $val;
	}

	final public function getMessage():? string
	{
		return $this->_message;
	}

	final public function setMessage(?string $val = null): void
	{
		$this->_message = $val;
	}

	final public function getOpened(): bool
	{
		return $this->_opened;
	}

	final public function setOpened(bool $val = true): void
	{
		$this->_opened = $val;
	}

	final public function getCreated():? Date
	{
		return $this->_created;
	}

	final public function setCreated(?Date $val = nul): void
	{
		$this->_created = $val;
	}

	public function createFromObject(object $data): void
	{
		$this->setIdentifier($data->identifier ?? new UUID());
		$this->setName($data->name ?? null);
		$this->setEmail($data->email ?? null);
		$this->setTelephone($data->telephone ?? null);
		$this->setMessage($data->message ?? null);
		$this->setSubject($data->subject ?? null);

		if (isset($data->created)) {
			if ($data->created instanceof Date) {
				$this->setCreate($data->created);
			} else {
				$this->setCreated(new Date($data->created));
			}
		} else {
			$this->setCreated(new Date());
		}

		if (isset($data->opened)) {
			$this->setOpened($data->opened);
		}
	}

	final public function markAsRead(PDO $pdo, bool $read = true): bool
	{
		if ($this->getIdentifier() === null) {
			return false;
		} elseif ($this->getOpened()) {
			return false;
		} else {
			$this->setOpened($read);
			$stm = $pdo->prepare('UPDATE `' . self::TABLE . '`
				SET `opened` = :opened
				WHERE `identifier` = :uuid
				LIMIT 1;');

			return $stm->execute(['uuid' => $this->getIdentifier(), 'opened' => $read])
				and $stm->rowCount() === 1;
		}
	}

	final public function save(PDO $pdo):? string
	{
		if ($this->getIdentifier() === null) {
			$this->setIdentifier(new UUID());
		}

		if ($this->valid()) {
			$stm = $pdo->prepare('INSERT INTO `' . self::TABLE . '` (
				`identifier`,
				`name`,
				`email`,
				`telephone`,
				`subject`,
				`message`
			) VALUES (
				:identifier,
				:name,
				:email,
				:telephone,
				:subject,
				:message
			);');

			if ($stm->execute([
				'identifier' => $this->getIdentifier(),
				'name'       => $this->getName(),
				'email'      => $this->getEmail(),
				'telephone'  => $this->getTelephone(),
				'subject'    => $this->getSubject(),
				'message'    => $this->getMessage(),
				// 'opened'     => $this->getOpened(),
			]) and $stm->rowCount() === 1) {
				return $this->getIdentifier();
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	final public function valid(): bool
	{
		return isset($this->_name, $this->_email, $this->_subject, $this->_message);
	}

	final public static function createFromUserInput(InputData $data): self
	{
		return new self(json_decode(json_encode($data)));
	}

	public static function getFromIdentifier(PDO $pdo, string $uuid):? self
	{
		$stm = $pdo->prepare('SELECT `identifier`,
			`name`,
			`email`,
			`telephone`,
			`subject`,
			`message`,
			`opened`,
			DATE_FORMAT(`created`, "%Y-%m-%dT%TZ") AS `created`
		FROM `' . self::TABLE .'`
		WHERE `identifier` = :uuid
		LIMIT 1;');

		if ($stm->execute(['uuid' => $uuid]) and $result = $stm->fetchObject()) {
			return new self($result);
		} else {
			return null;
		}
	}

	public static function fetchAll(PDO $pdo, int $offset = 0, int $count = 25): array
	{
		$stm = $pdo->query('SELECT JSON_OBJECT(
			"identifier", `identifier`,
			"name", `name`,
			"subject", `subject`,
			"created", DATE_FORMAT(`created`, "%Y-%m-%dT%TZ"),
			"opened", `opened` = "1"
		) AS `json`
		FROM `' . self::TABLE . '`
		LIMIT ' . $offset . ', ' . $count . ';');

		if ($stm->execute() and $results = $stm->fetchAll(PDO::FETCH_CLASS)) {
			return array_map(function(object $msg): object
			{
				$data = json_decode($msg->json);
				$data->created = new Date($data->created);
				return $data;
			}, $results);
		} else {
			return [];
		}
	}

	final public function delete(PDO $pdo): bool
	{
		$stm = $pdo->prepare('DELETE FROM `' . self::TABLE . '`
			WHERE `identifier` = :uuid
			LIMIT 1;');

		return $stm->execute(['uuid' => $this->getIdentifier()]) and $stm->rowCount() === 1;
	}
}
