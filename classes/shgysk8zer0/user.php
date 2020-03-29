<?php
namespace shgysk8zer0;
use \shgysk8zer0\PHPAPI\Interfaces\{InputData};
use \shgysk8zer0\{Date, Person};
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

	final private function _generateToken(string $uuid): string
	{
		$now = new DateTimeImmutable();
		$expires = $now->modify(sprintf('+ %d %s', static::$_expires['value'], static::$_expires['units']));
		$data = [
			'user'      => $this->getUUID(),
			'generated' => $now->format(DateTimeImmutable::W3C),
			'expires'   => $expires->format(DateTimeImmutable::W3C),
		];

		$data['hmac'] = hash_hmac(self::HASH_ALGO, json_encode($data), $this->_key);
		return base64_encode(json_encode($data));
	}

	final private function _checkToken(string $token):? bool
	{
		header('Content-Type: application/json');
		try {
			$data = json_decode(base64_decode($token));
			if (@is_object($data) and isset($data->user, $data->generated, $data->expires, $data->hmac)) {
				$now = new DateTimeImmutable();
				$generated = new DateTimeImmutable($data->generated);
				$expires = new DateTimeImmutable($data->expires);

				if ($now >= $generated && $now <= $expires) {
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
