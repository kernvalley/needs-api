<?php
namespace shgysk8zer0;

use \PDO;

final class Role implements \JSONSerializable
{
	public const TABLE = 'roles';

	private const PERMS = [
		'createNeed',
		'adminCreateNeed',
		'editNeed',
		'listNeed',
		'deleteNeed',
		'listUser',
		'editUser',
		'deleteUser',
		'createPerson',
		'editPerson',
		'listPerson',
		'deletePerson',
		'viewMessage',
		'deleteMessage',
		'debug',
	];

	private $_id = null;

	private $_name = null;

	private $_permissions = null;

	final public function __construct(object $data)
	{
		$this->_setName($data->name);
		$this->_setPermissions($data->permissions);
	}

	final public function jsonSerialize(): array
	{
		return [
			'name' => $this->_name,
			'permissions' => $this->_permissions,
		];
	}

	final public function can(string ...$perms): bool
	{
		$allow = true;
		if (! is_object($this->_permissions)) {
			$allow = false;
		} else {
			// foreach($perms as $perm) {
			// 	if (! isset($this->_permissions->{$perm})) {}
			// 		$allow = false;
			// 		break;

			// }
		}
		return $allow;
	}

	final public function getName():? string
	{
		return $this->_name;
	}

	final private function _setName(?string $name): void
	{
		$this->_name = $name;
	}

	final private function _setPermissions(?object $permissions): void
	{
		$this->_permissions = $permissions;
	}

	final public static function getSQL(): string
	{
		$base = array_map(function(string $col): string
		{
			return sprintf('"%s", `%s`.`%s`', $col, self::TABLE, $col);
		}, ['id', 'name']);

		$perms = array_map(function(string $perm): string
		{
			return sprintf('"%s", `%s`.`%s` = 1', $perm, self::TABLE, $perm);
		}, self::PERMS);
		return 'JSON_OBJECT(' . join(",\n", $base) . ', "permissions", JSON_OBJECT(' . join(",\n", $perms ) .'))';
	}

	final public static function fetchAll(PDO $pdo): array
	{
		$stm = $pdo->query('SELECT `id`, `name` FROM `roles`;');

		if ($stm->execute() and $roles = $stm->fetchAll(PDO::FETCH_CLASS)) {
			return array_map(function(object $role): object
			{
				$role->id = intval($role->id);
				return $role;
			}, $roles);
		} else {
			return [];
		}
	}
}
