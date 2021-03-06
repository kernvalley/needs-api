<?php
namespace shgysk8zer0;
use \PDO;
use \JSONSerializable;
use \Throwable;
use \StdClass;
use \shgysk8zer0\{User, Person, ImageObject};
use \shgysk8zer0\PHPAPI\{UUID, File, HTTPException};
use \shgysk8zer0\PHPAPI\Interfaces\{InputData};

final class NeedRequest implements JSONSerializable
{
	public const TABLE = 'needs';

	private $_identifier = null;

	private $_title = null;

	private $_description = null;

	private $_status = null;

	private $_tags = [];

	private $_user = null;

	private $_assignee = null;

	private $_created = null;

	private $_updated = null;

	private $_items = [];

	private $_attachments = [];

	final public function __construct(?object $data = null)
	{
		if (isset($data)) {
			$this->setFromObject($data);
		}
	}

	final public function jsonSerialize(): array
	{
		return [
			'identifier'  => $this->getIdentifier(),
			'title'       => $this->getTitle(),
			'description' => $this->getDescription(),
			'tags'        => $this->getTags(),
			'status'      => $this->getStatus(),
			'user'        => $this->getUser(),
			'assigned'    => $this->getAssignee(),
			'created'     => $this->getCreated(),
			'updated'     => $this->getUpdated(),
			'items'       => $this->getItems(),
			'attachments' => $this->getAttachments(),
		];
	}

	final public function __toString(): string
	{
		return json_encode($this);
	}

	final public function setAssignedUser(?User $val): void
	{
		if (isset($val)) {
			$this->setAssignee($val->getPerson()->getIdentifier());
		}
	}

	final public function getAssignee():? string
	{
		return $this->_assignee;
	}

	final public function setAssignee(?string $val): void
	{
		$this->_assignee = $val;
	}

	final public function getCreated():? Date
	{
		return $this->_created;
	}

	final public function getIdentifier():? string
	{
		return $this->_identifier;
	}

	final public function setIdentifier(?string $val): void
	{
		$this->_identifier = $val;
	}

	final public function getTitle():? string
	{
		return $this->_title;
	}

	final public function setTitle(?string $val): void
	{
		$this->_title = $val;
	}

	final public function getDescription():? string
	{
		return $this->_description;
	}

	final public function setDescription(?string $val): void
	{
		$this->_description = $val;
	}

	final public function getStatus():? string
	{
		return $this->_status;
	}

	final public function setStatus(?string $val): void
	{
		$this->_status = $val;
	}

	final public function getTags(): array
	{
		return $this->_tags;
	}

	final public function setTags(string ...$val): void
	{
		$this->_tags = $val;
	}

	final public function getUser():? Person
	{
		return $this->_user;
	}

	final public function getUpdated():? Date
	{
		return $this->_updated;
	}

	final public function setUser(?Person $val): void
	{
		$this->_user = $val;
	}

	final public function setUserFromUser(?User $val): void
	{
		$this->setUser($val->getPerson());
	}

	final public function getItems():? array
	{
		return $this->_items;
	}

	final public function setItems(?array $val): void
	{
		$this->_items = $val;
	}

	final public function getAttachments(): array
	{
		return $this->_attachments;
	}

	final public function setAttachments(array $attachments): void
	{
		$this->_attachments = $attachments;
	}

	final public function fetchItems(PDO $pdo, ?int $offset = 0): array
	{
		if (is_null($offset)) {
			$offset = 0;
		}
		$stm = $pdo->prepare('SELECT `id`,
			`quantity`,
			`item`,
			DATE_FORMAT(`created`, "%Y-%m-%dT%TZ") AS `created`
			FROM `items`
			WHERE `request` = :uuid
			LIMIT ' . $offset . ', 50;');

		if ($stm->execute(['uuid' => $this->getIdentifier()]) and $items = $stm->fetchAll(PDO::FETCH_CLASS)) {
			return array_map(function(object $item): object
			{
				$item->id = intval($item->id);
				$item->quantity = intval($item->quantity);
				return $item;
			}, $items);
		} else {
			return [];
		}
	}

	final public function fetchAttachments(PDO $pdo, int $offset = 0): array
	{
		$stm = $pdo->prepare('SELECT `uploads`.`identifier` AS `identifier`,
			`uploads`.`pathname` AS `pathname`,
			`uploads`.`filename` AS `filename`,
			`uploads`.`mimeType` AS `mimeType`,
			`ImageObject`.`url` AS `url`,
			`ImageObject`.`height` AS `height`,
			`ImageObject`.`width` AS `width`,
			DATE_FORMAT(`ImageObject`.`uploadDate`, "%Y-%m-%dT%TZ") AS `uploadDate`,
			`ImageObject`.`caption` AS `caption`,
			`uploads`.`uploader` AS `uploader`
		FROM `uploads`
		LEFT OUTER JOIN `ImageObject` ON `uploads`.`image` = `ImageObject`.`identifier`
		WHERE `uploads`.`need` = :uuid
		LIMIT ' . $offset . ', 25;');

		header('X-REQUEST-UUID: '. $this->getIdentifier());

		if ($stm->execute(['uuid' => $this->getIdentifier()]) and $results = $stm->fetchAll(PDO::FETCH_CLASS)) {
			return $results;
		} else {
			return [];
		}
	}

	final public function save(PDO $pdo):? string
	{
		if (! $this->valid()) {
			return null;
		} elseif ($this->getIdentifier() === null) {
			$this->setIdentifier(new UUID());
		}

		$stm = $pdo->prepare('INSERT INTO `' . self::TABLE . '` (
			`identifier`,
			`title`,
			`description`,
			`tags`,
			`status`,
			`assigned`,
			`user`
		) VALUES (
			:identifier,
			:title,
			:description,
			:tags,
			COALESCE(:status, "open"),
			:assigned,
			:user
		) ON DUPLICATE KEY UPDATE
			`title`       = COALESCE(:title, `title`),
			`description` = COALESCE(:description, `description`),
			`tags`        = COALESCE(:tags, `tags`),
			`status`      = COALESCE(:status, `status`),
			`assigned`    = COALESCE(:assigned, `assigned`),
			`user`        = COALESCE(:user, `user`);');

		if ($stm->execute([
			'identifier'  => $this->getidentifier(),
			'title'       => $this->getTitle(),
			'description' => $this->getDescription(),
			'tags'        => join(',', $this->getTags()),
			'status'      => $this->getStatus(),
			'assigned'    => $this->getAssignee(),
			'user'        => isset($this->_user) ? $this->getUser()->getIdentifier() : null,
		])) {
			// @TODO Ensure it was saved/updated
			// `rowCount` does not seem to work when updating
			return $this->getIdentifier();
		} else {
			return null;
		}
	}

	final public function valid(): bool
	{
		return isset($this->_title, $this->_description, $this->_user);
	}

	final public function isOpen(): bool
	{
		return $this->getStatus() === 'open';
	}

	final public function isClosed(): bool
	{
		return $this->getStatus() === 'closed';
	}

	final public function isAssigned(User $user): bool
	{
		return $user->getUUID() === $this->getAssignee();
	}

	final public function assignUser(User $val): void
	{
		$this->assignPerson($val->getPerson());
	}

	final public function assignPerson(Person $val): void
	{
		$this->setAssignee($val->getIdentifier());
	}

	final public static function getSQL(): string
	{
		return 'JSON_OBJECT(
			"identifier", `needs`.`identifier`,
			"title", `' . static::TABLE . '`.`title`,
			"description", `' . static::TABLE . '`.`description`,
			"status", `' . static::TABLE . '`.`status`,
			"tags", `' . static::TABLE . '`.`tags`,
			"user", ' . Person::getSQL() . ',
			"assigned", `' . static::TABLE .'`.`assigned`,
			"created",  DATE_FORMAT(`' . static::TABLE . '`.`created`, "%Y-%m-%dT%TZ"),
			"updated",  DATE_FORMAT(`' . static::TABLE . '`.`updated`, "%Y-%m-%dT%TZ")
		)';
	}

	final public static function getFromIdentifier(PDO $pdo, string $uuid):? self
	{
		$stm = $pdo->prepare('SELECT ' . static::getSQL(). ' AS `json`
			FROM `' . self::TABLE . '`
			' . join("\n", static::getJoins()) .'
			WHERE `' . self::TABLE . '`.`identifier` = :uuid
			LIMIT 1;');

		if ($stm->execute(['uuid' => $uuid]) and $result = $stm->fetchObject()) {
			return new self(json_decode($result->json));
		} else {
			return null;
		}
	}

	final public static function searchByStatus(
		PDO    $pdo,
		string $status   = 'open',
		int    $page     = 1,
		int    $limit    = 25,
		string $order_by = 'created'
	): array
	{
		$stm = $pdo->prepare('SELECT ' . static::getSQL(). ' AS `json`
			FROM `' . self::TABLE . '`
			' . join("\n", static::getJoins()) .'
			ORDER BY `created` ASC
			LIMIT ' . static::_getPages($page, $limit) .';');

		if ($stm->execute(['status' => $status]) and $results = $stm->fetchAll(PDO::FETCH_CLASS)) {
			return array_map(function(object $result): self
			{
				return new self(json_decode($result->json));
			}, $results);
		} else {
			return [];
		}
	}

	final public static function searchByAssigned(
		PDO     $pdo,
		?Person $assignee = null,
		string  $status   = 'open',
		int     $page     = 1,
		int     $limit    = 25,
		string  $order_by = 'created'
	): array
	{
		header('Content-Type: application/json');
		$stm = $pdo->prepare('SELECT ' . static::getSQL(). ' AS `json`
			FROM `' . self::TABLE . '`
			' . join("\n", static::getJoins()) .'
			WHERE `' . static::TABLE . '`.`assigned` = :person
			OR `' . static::TABLE . '`.`assigned` IS NULL
			ORDER BY `' . self::TABLE . '`.`created` ASC
			LIMIT ' . static::_getPages($page, $limit) .';');

		if ($stm->execute(['person' => $assignee->getIdentifier()]) and $results = $stm->fetchAll(PDO::FETCH_CLASS)) {
			return array_map(function(object $result): self
			{
				return new self(json_decode($result->json));
			}, $results);
		} else {
			return [];
		}
	}

	final public function attach(PDO $pdo, File $image, string $path, User $user):? string
	{
		if (file_exists($path)) {
			return null;
		}
		try {
			if ($image->saveAs($path, true)) {
				$data = new StdClass();
				$data->url = $image->url;
				$data->identifier = new UUID();

				if ($img_uuid = (new ImageObject($data))->save($pdo)) {
					$stm = $pdo->prepare('INSERT INTO `uploads` (
						`identifier`,
						`image`,
						`uploader`,
						`need`,
						`pathname`,
						`filename`,
						`mimeType`
					) VALUES (
						:uuid,
						:image,
						:user,
						:need,
						:pathname,
						:filename,
						:mimetype
					);');

					$uuid = new UUID();

					if ($stm->execute([
						'uuid'     => $uuid,
						'image'    => $img_uuid,
						'user'     => $user->getIdentifier(),
						'need'     => $this->getIdentifier(),
						'pathname' => str_replace($_SERVER['DOCUMENT_ROOT'], null, $image->location),
						'filename' => $image->name,
						'mimetype' => $image->type,
					]) and $stm->rowCount() === 1) {
						return $uuid;
					} else {
						throw new \ErrorException('Error executing `uploads` statemtent');
					}
				} else {
					throw new HTTPException('Error saving `ImageObject`', HTTP::INTERNAL_SERVER_ERROR);
				}
			}
		} catch (Throwable $e) {
			// @TODO Check it is not a duplicate file
			if (file_exists($path)) {
				unlink($path);
			}
			return null;
		}
	}

	final public function setFromObject(?object $data): void
	{
		if (isset($data)) {
			$this->setIdentifier($data->identifier ?? null);
			$this->setTitle($data->title ?? null);
			$this->setDescription($data->description ?? null);
			$this->setStatus($data->status ?? null);

			if (isset($data->created)) {
				$this->_created = new Date($data->created);
			}

			if (isset($data->updated)) {
				$this->_updated = new Date($data->updated);
			}

			if (isset($data->tags)) {
				if (@is_string($data->tags)) {
					$this->setTags(...explode(',', $data->tags));
				} elseif (@is_array($data->tags)) {
					$this->setTags($data->tags);
				}
			}

			if (isset($data->user) and is_object($data->user)) {
				$this->setUser(new Person($data->user));
			}

			if (isset($data->assigned) and is_string($data->assigned)) {
				$this->setAssignee($data->assigned);
			}
		}
	}

	// final public static function getAttachments(PDO $pdo, string $need_uuid, int $page = 1, int $count = 25): array
	// {
	// 	$stm = $pdo->prepare('SELECT  `ImageObject`.`url` AS `image`,
	// 		`ImageObject`.`identifier` AS `identifier`,
	// 		`Person`.`name` AS `uploadedBy`,
	// 		DATE_FORMAT(`ImageObject`.`uploadDate`, "%Y-%m-%dT%TZ") AS `uploadDate`
	// 	FROM `uploads`
	// 	LEFT OUTER JOIN `users` ON `uploads`.`uploader` = `users`.`identifier`
	// 	LEFT OUTER JOIN `ImageObject` ON `uploads`.`image` = `ImageObject`.`identifier`
	// 	LEFT OUTER JOIN `Person` ON `users`.`person` = `Person`.`identifier`
	// 	WHERE `need` = :need
	// 	ORDER BY `ImageObject`.`uploadDate` DESC
	// 	LIMIT ' . static::_getPages($page, $count) . ';');

	// 	if ($stm->execute(['need' => $need_uuid]) and $results = $stm->fetchAll(PDO::FETCH_CLASS)) {
	// 		return $results;
	// 	} else {
	// 		return [];
	// 	}
	// }

	final public function setFromUserInput(InputData $data): void
	{
		$this->setFromObject(json_decode(json_encode($data)));
	}

	final public static function delete(PDO $pdo, string $uuid): bool
	{
		$stm = $pdo->prepare('DELETE FROM `' . static::TABLE . '`
			WHERE `identifier` = :uuid
			LIMIT 1;');
		return $stm->execute(['uuid' => $uuid]) and $stm->rowCount() === 1;
	}

	final public static function getCount(PDO $pdo): int
	{
		$stm = $pdo->query('SELECT COUNT(*) AS `count` FROM `' . self::TABLE . '`;');

		if ($stm->execute() and $result = $stm->fetchObject()) {
			return $result->count;
		} else {
			return -1;
		}
	}

	final public static function getJoins(): array
	{
		return array_merge([
			'LEFT OUTER JOIN `Person` ON `' . self::TABLE . '`.`user` = `Person`.`identifier`',
		], Person::getJoins());
	}

	final protected static function _getPages(int $page = 1, int $count = 25): string
	{
		$offset = ($page - 1) * $count;
		return "{$offset}, {$count}";
	}
}
