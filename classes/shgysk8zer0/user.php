<?php
namespace shgysk8zer0;
use \shgysk8zer0\PHPAPI\{File};
use \shgysk8zer0\PHPAPI\Interfaces\{InputData};
use \shgysk8zer0\{Date, Person, ImageObject};
use \DateTimeImmutable;
use \PDO;
use \Exception;
use \InvalidArgumentException;
use \Throwable;
use \JsonSerializable;

// @TODO Deprecate all UUID methods in favor of identifier
final class User implements JsonSerializable
{
	private const TABLE = 'users';

	private const TOKEN_TYPE = 'login';

	private const _PASSWORD_ALGO = PASSWORD_DEFAULT;

	private const _PASSWORD_OPTS = [
		'cost' => 10,
	];

	public const HASH_ALGO = 'sha3-256';

	private $_uuid     = null;

	private $_role     = null;

	private $_created  = null;

	private $_updated  = null;

	private $_loggedIn = false;

	private $_pdo      = null;

	private $_token    = null;

	private $_pwned    = null;

	private $_key      = null;

	private $_person = null;

	private static $_expires = [
		'value' => 1,
		'units' => 'year',
	];

	final public function __construct(PDO $pdo, string $hash_key)
	{
		$this->_pdo = $pdo;
		$this->_key = $hash_key;
		$this->_created = new DateTimeImmutable();
		$this->_updated = new DateTimeImmutable();
	}

	final public function __toString(): string
	{
		if ($this->isLoggedIn()) {
			return $this->getPerson()->getName();
		} else {
			return '';
		}
	}

	final public function jsonSerialize(): array
	{
		if ($this->isLoggedIn()) {
			return [
				'identifier' => $this->getUUID(),
				'created'    => $this->getCreated(),
				'updated'    => $this->getUpdated(),
				'person'     => $this->getPerson(),
				'role'       => $this->getRole(),
				'token'      => $this->_generateToken($this->getUUID()),
			];
		} else {
			return [
				'identifier' => null,
				'created'    => null,
				'updated'    => null,
				'image'      => null,
				'person'     => null,
				'role'       => null,
				'token'      => null,
			];
		}
	}

	final public function getCreated():? Date
	{
		return $this->_created;
	}

	final public function getUpdated():? Date
	{
		return $this->_updated;
	}

	final public function getPerson():? object
	{
		return $this->_person;
	}

	final public function getRole():? object
	{
		return $this->_role;
	}

	final public function getIdentifier():? string
	{
		return $this->_uuid;
	}

	final public function setIdentifier(?string $val): void
	{
		$this->_uuid = $val;
	}

	final public function getUUID():? string
	{
		return $this->_uuid;
	}

	final public function isLoggedIn(): bool
	{
		return isset($this->_uuid);
	}

	final public function setImageFromFile(PDO $pdo, File $img, string $fname):? ImageObject
	{
		return $this->getPerson()->setImageFromFile($pdo, $img, $fname);
	}

	final public function login(InputData $data): bool
	{
		if (! $data->has('email', 'password')) {
			return false;
		} elseif (! filter_var($data->get('email', FILTER_VALIDATE_EMAIL))) {
			return false;
		} else {
			$stm = $this->_pdo->prepare('SELECT `users`.`identifier`,
				`users`.`password`
			FROM `Person`
			LEFT OUTER JOIN `users` ON `Person`.`identifier` = `users`.`person`
			WHERE `Person`.`email` = :email
			LIMIT 1;');

			if ($stm->execute(['email' => $data->get('email')]) and $user = $stm->fetchObject() and isset($user->identifier)) {
				return $this->_getUserByUUID($user->identifier);
			} else {
				return false;
			}
		}
	}

	final public function register(InputData $data): bool
	{
		if (! $data->has('password', 'person') or ! $data->get('person') instanceof InputData) {
			return false;
		} else {
			$stm = $this->_pdo->prepare('INSERT INTO `users` (
				`identifier`,
				`password`,
				`person`
			) VALUES (
				:uuid,
				:password,
				:person
			);');

			try {
				$person = new Person();
				$person->setFromUserInput($data->get('person'));
				$this->_pdo->beginTransaction();

				if ($this->getUUID() === null) {
					$this->_uuid = Person::generateUUID();
				}

				$args = [
					'uuid'     => $this->getUUID(),
					'password' => password_hash($data->get('password', false), self::_PASSWORD_ALGO, self::_PASSWORD_OPTS),
					'person'   => $person->save($this->_pdo),
				];

				if ($stm->execute($args) and $stm->rowCount() !== 0) {
					$this->_pdo->commit();
					return true;
				} else {
					throw new \Exception('Error saving `user`.`person`');
				}
			} catch (\Throwable $e) {
				$this->_pdo->rollback();
				return false;
			}
		}
	}

	final public function loginWithToken(InputData $input): bool
	{
		return $input->has('token') and $this->_checkToken($input->get('token', false));
	}

	final private function _setData(object $user): bool
	{
		if (isset($user->identifier, $user->created, $user->updated, $user->person, $user->role)) {
			$this->_uuid = $user->identifier;

			$this->_created = new Date($user->created);
			$this->_updated = new Date($user->updated);
			$this->_person = new Person($user->person);
			$this->_role = new Role($user->role);
			$this->_loggedIn = true;
			return true;
		} else {
			return false;
		}
	}

	final public function can(string ...$perms): bool
	{
		return isset($this->_role) and $this->getRole()->can(...$perms);
	}

	final private function _getUserByUUID(string $uuid): bool
	{
		$sql = 'SELECT ' . static::getSQL() . ' AS `json`
		FROM `' . self::TABLE . '`
		' . join("\n", static::getJoins()) . '
		WHERE `' . self::TABLE . '`.`identifier` = :uuid
		LIMIT 1;';
		$stm = $this->_pdo->prepare($sql);

		if ($stm->execute(['uuid' => $uuid]) and $user = $stm->fetchObject() and isset($user->json)) {
			$user = json_decode($user->json);
			unset($user->password);
			return $this->_setData($user);
		} else {
			return false;
		}
	}

	final public static function getSQL(): string
	{
		return 'JSON_OBJECT(
			"identifier", `users`.`identifier`,
			"created", DATE_FORMAT(`users`.`created`, "%Y-%m-%dT%TZ"),
			"updated", DATE_FORMAT(`users`.`updated`, "%Y-%m-%dT%TZ"),
			"person", ' . Person::getSQL() .',
			"role", ' . Role::getSQL() .'
		)';
	}

	final public static function getJoins(): array
	{
		return array_merge(
			[
				'LEFT OUTER JOIN `' . Person::TYPE . '` ON `' . self::TABLE . '`.`person` = `' . Person::TYPE . '`.`identifier`',
				'LEFT OUTER JOIN `' . Role::TABLE . '` ON `' . self::TABLE . '`.`role` = `' . Role::TABLE .'`.`id`',

			],
			Person::getJoins()
		);
	}

	public static function getFromIdentifier(PDO $pdo, string $uuid):? object
	{
		$stm = $pdo->prepare('SELECT ' . static::getSQL() .' AS `json`
			FROM ' . static::TABLE .'
			' . join("\n", static::getJoins()) .'
			WHERE `' . static::TABLE . '`.`identifier` = :uuid
			LIMIT 1;');

		if ($stm->execute(['uuid' => $uuid]) and $result = $stm->fetchObject()) {
			$user = json_decode($result->json);
			return $user;
		} else {
			return null;
		}
	}

	public static function resetPasswordforPerson(PDO $pdo, Person $person, string $password): bool
	{
		$stm = $pdo->prepare('UPDATE `' . self::TABLE .'`
		SET `password` = :hash
		WHERE `person` = :person
		LIMIT 1;');

		return $person->getIdentifier() !== null && $stm->execute([
			'person' => $person->getIdentifier(),
			'hash' => password_hash($password, self::_PASSWORD_ALGO, self::_PASSWORD_OPTS),
		]) and $stm->rowCount() === 1;
	}

	public static function fetchAll(PDO $pdo, int $offset = 0, int $count = 25): array
	{
		$stm = $pdo->query('SELECT `users`.`identifier`,
			JSON_OBJECT(
				"identifier", `Person`.`identifier`,
				"name", `Person`.`name`,
				"image", JSON_OBJECT(
					"identifier", `ImageObject`.`identifier`,
					"url", `ImageObject`.`url`,
					"width", `ImageObject`.`width`,
					"height", `ImageObject`.`height`
				)
			) AS `person`,
			JSON_OBJECT(
				"id", `roles`.`id`,
				"name", `roles`.`name`
			) AS `role`
		FROM `users`
		LEFT OUTER JOIN `Person` ON `users`.`person` = `Person`.`identifier`
		LEFT OUTER JOIN `roles` ON `users`.`role` = `roles`.`id`
		LEFT OUTER JOIN `ImageObject` ON `Person`.`image` = `ImageObject`.`identifier`
		LIMIT ' . $offset . ', ' . $count . ';');

		if ($stm->execute() and $users = $stm->fetchAll(PDO::FETCH_CLASS)) {
			return array_map(function(object $user): object
			{
				$user->person = json_decode($user->person);
				$user->role = json_decode($user->role);
				return $user;
			}, $users);
		} else {
			return [];
		}
	}

	final public function getNotifications(PDO $pdo, int $offset = 0, int $limit = 4): array
	{
		return Notification::getNotificationsForUser($pdo, $this, $offset, $limit);
	}

	public static function getPersonFromEmail(PDO $pdo, string $email):? Person
	{
		if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return null;
		} else {
			$stm = $pdo->prepare('SELECT `users`.`person` AS `person`
			FROM `users`
			LEFT OUTER JOIN `Person` ON `users`.`person` = `Person`.`identifier`
			WHERE `Person`.`email` = :email
			LIMIT 1;');

			if ($stm->execute(['email' => $email]) and $result = $stm->fetchObject()) {
				return Person::getFromIdentifier($pdo, $result->person);
			} else {
				return null;
			}
		}
	}

	final private function _generateToken(string $uuid): string
	{
		$now = new DateTimeImmutable();
		$expires = $now->modify(sprintf('+ %d %s', static::$_expires['value'], static::$_expires['units']));
		$data = [
			'user'      => $this->getUUID(),
			'generated' => $now->format(DateTimeImmutable::W3C),
			'expires'   => $expires->format(DateTimeImmutable::W3C),
			'type'      => self::TOKEN_TYPE,
		];

		$data['hmac'] = hash_hmac(self::HASH_ALGO, json_encode($data), $this->_key);
		return base64_encode(json_encode($data));
	}

	final private function _checkToken(string $token):? bool
	{
		try {
			$data = json_decode(base64_decode($token));
			if (@is_object($data) and isset($data->user, $data->generated, $data->expires, $data->hmac, $data->type)) {
				$now = new DateTimeImmutable();
				$generated = new DateTimeImmutable($data->generated);
				$expires = new DateTimeImmutable($data->expires);

				if ($data->type !== self::TOKEN_TYPE) {
					return false;
				} elseif ($now >= $generated && $now <= $expires) {
					$hmac = $data->hmac;
					unset($data->hmac);
					$expected = hash_hmac(self::HASH_ALGO, json_encode($data), $this->_key);
					return hash_equals($expected, $hmac) and $this->_getUserByUUID($data->user);
				} else {
					return false;
				}
			} else {
				return false;
			}
		} catch(Throwable $e) {
			// @TODO handle this error?
			return false;
		}
	}
}
