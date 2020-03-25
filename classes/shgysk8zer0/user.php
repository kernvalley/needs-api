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

final class User implements JsonSerializable
{
	private const _PASSWORD_ALGO = PASSWORD_DEFAULT;

	private const _PASSWORD_OPTS = [
		'cost' => 10,
	];

	public const HASH_ALGO = 'sha3-256';

	private $_uuid     = '';

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

	final public function getUUID():? string
	{
		return $this->_uuid;
	}

	final public function isLoggedIn(): bool
	{
		return isset($this->_uuid);
	}

	final public function jsonSerialize(): array
	{
		if ($this->isLoggedIn()) {
			return [
				'uuid'    => $this->getUUID(),
				'created' => $this->getCreated(),
				'updated' => $this->getUpdated(),
				'image'   => sprintf('https://secure.gravatar.com/avatar/%s?d=mm', md5($this->getPerson()->email)),
				'person'  => $this->getPerson(),
				'role'    => $this->getRole(),
				'token'   => $this->_generateToken($this->getUUID()),
			];
		} else {
			return [
				'uuid'    => null,
				'created' => null,
				'updated' => null,
				'image'   => null,
				'person'  => null,
				'role'    => null,
				'token'   => null,
			];
		}
	}

	final public function login(InputData $data): bool
	{
		if (! $data->has('email', 'password')) {
			return false;
		} elseif (! filter_var($data->get('email', FILTER_VALIDATE_EMAIL))) {
			return false;
		} else {
			$stm = $this->_pdo->prepare('SELECT `users`.`uuid`,
				`users`.`password`
			FROM `Person`
			LEFT OUTER JOIN `users` ON `Person`.`uuid` = `users`.`person`
			LIMIT 1;');

			if ($stm->execute(['email' => $data->get('email')]) and $user = $stm->fetchObject() and isset($user->uuid)) {
				return $this->_getUserByUUID($user->uuid);
			} else {
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
		if (isset($user->uuid, $user->created, $user->updated, $user->person, $user->role)) {
			$this->_uuid = $user->uuid;
			$user->person->image = new \StdClass();
			$user->person->image->url = sprintf('https://secure.gravatar.com/avatar/%s?d=mm', md5($user->person->email));

			$this->_created = new Date($user->created);
			$this->_updated = new Date($user->updated);
			$this->_person = new Person($user->person);
			$this->_role = $user->role;
			$this->_loggedIn = true;
			return true;
		} else {
			return false;
		}
	}

	final public function can(string ...$perms): bool
	{
		if (isset($this->_role, $this->_role->permissions)) {
			$valid = true;
			$permissions = $this->_role->permissions;

			foreach($perms as $perm) {
				if ($permissions->{$perm} !== true) {
					$valid = false;
					break;
				}
			}
			return $valid;
		} else {
			return false;
		}

	}

	final private function _getUserByUUID(string $uuid): bool
	{
		$stm = $this->_pdo->prepare('SELECT JSON_OBJECT(
			"uuid", `users`.`uuid`,
			"created", DATE_FORMAT(`users`.`created`, "%Y-%m-%dT%TZ"),
			"updated", DATE_FORMAT(`users`.`updated`, "%Y-%m-%dT%TZ"),
			"person", JSON_OBJECT(
				"@context", "https://schema.org",
				"@type", "Person",
				"name", `Person`.`name`,
				"email", `Person`.`email`,
				"telephone", `Person`.`telephone`,
				"address", JSON_OBJECT(
					"@context", "PostalAddress",
					"identifier", `PostalAddress`.`uuid`,
					"streetAddress", `PostalAddress`.`streetAddress`,
					"postOfficeBoxNumber", `PostalAddress`.`postOfficeBoxNumber`,
					"addressLocality", `PostalAddress`.`addressLocality`,
					"addressRegion", `PostalAddress`.`addressRegion`,
					"postalCode", `PostalAddress`.`postalCode`,
					"addressCountry", `PostalAddress`.`addressCountry`
				)
			),
			"role", JSON_OBJECT(
				"name", `roles`.`name`,
				"permissions", JSON_OBJECT(
					"createNeed", `roles`.`createNeed` = 1,
					"editNeed", `roles`.`editNeed` = 1,
					"listNeed", `roles`.`listNeed` = 1,
					"deleteNeed", `roles`.`deleteNeed` = 1,
					"listUser", `roles`.`listUser` = 1,
					"editUser", `roles`.`editUser` = 1,
					"deleteUser", `roles`.`deleteUser` = 1,
					"debug", `roles`.`debug` = 1
				)
			)
		) AS `json`
		FROM `users`
		LEFT OUTER JOIN `Person` ON `users`.`person` = `Person`.`uuid`
		LEFT OUTER JOIN `PostalAddress` ON `Person`.`address` = `PostalAddress`.`uuid`
		LEFT OUTER JOIN `roles` ON `users`.`role` = `roles`.`id`
		WHERE `users`.`uuid` = :uuid
		LIMIT 1;');

		if ($stm->execute(['uuid' => $uuid]) and $user = $stm->fetchObject() and isset($user->json)) {
			$user = json_decode($user->json);
			unset($user->password);
			return $this->_setData($user);
		} else {
			return false;
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
