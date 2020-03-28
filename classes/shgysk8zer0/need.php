<?php
namespace shgysk8zer0;
use \PDO;
use \PDOStatement;
use \shgysk8zer0\{Date, User, PostalAddress};
use \shgysk8zer0\PHPAPI\{UUID};
use \shgysk8zer0\PHPAPI\Interfaces\{InputData};

// @TODO rewrite to contain own data for modification & saving
final class Need
{
	private const TABLE = 'needs';

	private $_pdo = null;

	private $_identifier = '';

	private $_title = '';

	private $_description = '';

	private $_tags = [];

	private $_user = null;

	private $_assigned = null;

	private $_created;

	private $_updated;

	public function __construct(PDO $pdo)
	{
		$this->_pdo = $pdo;
	}

	public function createFromUserInput(User $user, InputData $data):? string
	{
		if ($user->isLoggedIn()) {
			if ($data->has('title', 'description', 'tags')) {
				$uuid = new UUID();
				$stm = $this->_prepare('INSERT INTO `' . self::TABLE . '` (
					`identifier`,
					`user`,
					`title`,
					`description`,
					`tags`
				) VALUES (
					:uuid,
					:user,
					:title,
					:description,
					:tags
				);');

				if ($stm->execute([
					'uuid'        => $uuid,
					'user'        => $user->getPerson()->getIdentifier(),
					'title'       => $data->get('title'),
					'description' => $data->get('description'),
					'tags'        => $data->get('tags'),
				]) and $stm->rowCount() !== 0) {
					return $uuid;
				} else {
					return null;
				}
			} else {
				throw new HTTPException('Missing required inputs', HTTP::BAD_REQUEST);
			}

		} else {
			throw new HTTPException('You must be logged in', HTTP::FORBIDDEN);
		}
	}

	public function searchByStatus(string $status = 'open', int $limit = 25): array
	{
		$stm = $this->_prepare(
			'SELECT ' . static::getSQL() . 'as `json`
			FROM `' . static::TABLE .'`
			LEFT OUTER JOIN `Person` on `Person`.`identifier` = `' . static::TABLE .'`.`user`
			LEFT OUTER JOIN `PostalAddress` ON `Person`.`address` = `PostalAddress`.`identifier`
			LEFT OUTER JOIN `ImageObject` ON `Person`.`image` = `ImageObject`.`identifier`
			WHERE `' . static::TABLE . '`.`status` = :status
			LIMIT ' . $limit .';'
		);

		$stm->execute(['status' => strtolower($status)]);

		$results = $stm->fetchAll(PDO::FETCH_CLASS);

		return array_map(function(object $result): object
		{
			return json_decode($result->json);
		}, $results);
	}

	public function listUnassigned(?string $status = 'open', int $limit = 25): array
	{
		$stm = $this->_prepare(
			'SELECT ' . static::getSQL() . 'as `json`
			FROM `' . static::TABLE .'`
			LEFT OUTER JOIN `Person` on `Person`.`identifier` = `' . static::TABLE .'`.`user`
			LEFT OUTER JOIN `PostalAddress` ON `Person`.`address` = `PostalAddress`.`identifier`
			LEFT OUTER JOIN `ImageObject` ON `Person`.`image` = `ImageObject`.`identifier`
			WHERE `' . static::TABLE . '`.`assigned` IS NULL
			AND `' . static::TABLE .'`.`status` = :status
			LIMIT ' . $limit .';'
		);

		$stm->execute(['status' => strtolower($status ?? 'open')]);

		$results = $stm->fetchAll(PDO::FETCH_CLASS);

		return array_map([$this, '_mapNeeds'], $results);
	}

	public function searchByAssignee(?string $assigned = null, string $status = 'open', int $limit = 25): array
	{
		$stm = $this->_prepare(
			'SELECT ' . static::getSQL() . 'as `json`
			FROM `' . static::TABLE .'`
			LEFT OUTER JOIN `Person` on `Person`.`identifier` = `' . static::TABLE .'`.`user`
			LEFT OUTER JOIN `PostalAddress` ON `Person`.`address` = `PostalAddress`.`identifier`
			LEFT OUTER JOIN `ImageObject` ON `Person`.`image` = `ImageObject`.`identifier`
			WHERE `' . static::TABLE . '`.`assigned` = :assigned
			AND `' . static::TABLE .'`.`status` = :status
			LIMIT ' . $limit .';'
		);

		$stm->execute(['assigned' => isset($assigned) ? $assigned : null, 'status' => strtolower($status)]);

		$results = $stm->fetchAll(PDO::FETCH_CLASS);

		return array_map([$this, '_mapNeeds'], $results);
	}

	public function getByIdentifier(string $uuid):? object
	{
		$stm = $this->_prepare(
			'SELECT ' . static::getSQL() . 'as `json`
			FROM `' . static::TABLE .'`
			LEFT OUTER JOIN `Person` on `Person`.`identifier` = `' . static::TABLE .'`.`user`
			LEFT OUTER JOIN `PostalAddress` ON `Person`.`address` = `PostalAddress`.`identifier`
			LEFT OUTER JOIN `ImageObject` ON `Person`.`image` = `ImageObject`.`identifier`
			WHERE `' . static::TABLE . '`.`identifier` = :uuid
			LIMIT 1;'
		);

		$stm->execute(['uuid' => $uuid]);

		if ($result = $stm->fetchObject()) {
			return $this->_mapNeeds($result);
		} else {
			return null;
		}
	}


	private function _prepare(string $sql): PDOStatement
	{
		return $this->_pdo->prepare($sql);
	}

	public static function getSQL(): string
	{
		return 'JSON_OBJECT(
			"identifier", `' . static::TABLE . '`.`identifier`,
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

	private function _mapNeeds(object $result): object
	{
		$need = json_decode($result->json);
		$need->created = new Date($need->created);
		$need->updated = new Date($need->created);
		$need->foo = [
			'file' => __FILE__,
			'line' => __LINE__,
			'class' => __CLASS__,
			'method' => __METHOD__,
			'function' => __FUNCTION__,
		];
		$need->tags = array_map('trim', explode(',', $need->tags));
		$need->user = new Person($need->user);

		// if (! isset($need->user->address->url)) {
		// 	$query = http_build_query([
		// 		'api'        => PostalAddress::GMAPS_API_VERSION,
		// 		'paramaters' =>join(' ', array_filter([
		// 			$need->user->address->streetAddress,
		// 			$need->user->address->addressLocality,
		// 			$need->user->address->addressRegion,
		// 			$need->user->address->postalCode,
		// 			$need->user->address->addressCountry,
		// 		])),
		// 	]);
		// 	$need->user->address->url = "https://www.google.com/maps/search/?{$query}";
		// }
		return $need;
	}
}
