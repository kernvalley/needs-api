<?php
namespace shgysk8zer0;

use \DateTimeInterface;
use \JSONSerializable;
use \PDO;
use \shgysk8zer0\{User};

final class Notification implements JSONSerializable
{
	private $_title = null;

	private $_body = null;

	private $_icon = null;

	private $_dir = 'ltr';

	private $_data = null;

	private $_lang = 'en';

	private $_actions = [];

	private $_tag = null;

	private $_require_interaction = false;

	private $_silent = false;

	private $_timestamp = null;

	private $_vibrate = false;

	final public function __construct(?object $data = null)
	{
		if (isset($data)) {
			$this->setTitle($data->title);
			$this->setBody($data->body);
			$this->setIcon($data->icon);

			if (is_string($data->tag)) {
				$this->setTag($data->tag);
			}

			if (is_string($data->lang)) {
				$this->setLang($data->lang);
			}

			if (is_string($data->dir)) {
				$this->setDir($data->dir);
			}

			if (is_bool($data->vibrate)) {
				$this->setVibrate($data->vibrate);
			}

			if (is_bool($data->silent)) {
				$this->setSilent($data->silent);
			}

			if (is_bool($data->requireInteraction)) {
				$this->setRequireInteraction($data->requireInteraction);
			}

			if (is_string($data->timestamp)) {
				$this->setTimestamp(new \DateTimeImmutable($data->timestamp));
			} elseif (is_object($data->timestamp) and $data->timestamp instanceof DateTimeInterface) {
				$this->setTimestamp($data->timestamp);
			}

			if (is_array($data->actions)) {
				$actions = array_map(function(object $action):? NotificationAction
				{
					$act = new NotificationAction($action);
					if ($act->valid()) {
						return $act;
					} else {
						return null;
					}
				}, $data->actions);

				$this->addActions(...array_filter($actions));
			}
		}
	}

	final public function __toString(): string
	{
		return json_encode($this);
	}

	final public function jsonSerialize(): array
	{
		return array_filter([
			'title'              => $this->getTitle(),
			'body'               => $this->getBody(),
			'icon'               => $this->getIcon(),
			'actions'            => $this->getActions(),
			'tag'                => $this->getTag(),
			'data'               => $this->getData(),
			'dir'                => $this->getDir(),
			'lang'               => $this->getLang(),
			'requireInteraction' => $this->getRequireInteraction(),
			'silent'             => $this->getSilent(),
			'vibrate'            => $this->getVibrate(),
			'timestamp'          => $this->getTimestamp(),
		], function($val): bool
		{
			return isset($val);
		});
	}

	final public function addActions(NotificationAction... $actions): void
	{
		$this->_actions = array_merge($this->_actions, $actions);
	}

	final public function getActions(): array
	{
		return $this->_actions;
	}

	final public function getBody():? string
	{
		return $this->_body;
	}

	final public function setBody(string $val): void
	{
		$this->_body = $val;
	}

	final public function getData()
	{
		return $this->_data;
	}

	final public function setData($val): void
	{
		$this->_data = $val;
	}

	final public function getDir(): string
	{
		return $this->_dir;
	}

	final public function setDir(string $val): void
	{
		$this->_dir = $val;
	}

	final public function getIcon():? string
	{
		return $this->_icon;
	}

	final public function setIcon(?string $val = null): void
	{
		$this->_icon = $val;
	}

	final public function getLang():? string
	{
		return $this->_lang;
	}

	final public function setLang(?string $val = null): void
	{
		$this->_lang = $val;
	}

	final public function getRequireInteraction(): bool
	{
		return $this->_require_interaction;
	}

	final public function setRequireInteraction(bool $val = false): void
	{
		$this->_require_interaction = $val;
	}

	final public function getTag():? string
	{
		return $this->_tag;
	}

	final public function getSilent(): bool
	{
		return $this->_silent;
	}

	final public function setSilent(bool $val = false): void
	{
		$this->_silent = $val;
	}

	final public function setTag(?string $val = null): void
	{
		$this->_tag = $val;
	}

	final public function getTimestamp():? int
	{
		if (isset($this->_timestamp)) {
			return $this->_timestamp->getTimestamp();
		} else {
			return null;
		}
	}

	final public function setTimestamp(?DateTimeInterface $val = null): void
	{
		$this->_timestamp = $val;
	}

	final public function getTitle():? string
	{
		return $this->_title;
	}

	final public function setTitle(?string $val = null): void
	{
		$this->_title = $val;
	}

	final public function getVibrate(): bool
	{
		return $this->_vibrate;
	}

	final public function setVibrate(bool $val = false): void
	{
		$this->_vibrate = $val;
	}

	final public function markAsSeen(PDO $pdo, bool $seen = true): bool
	{
		$stm = $pdo->prepare('UPDATE `Notification` SET `seen` = :seen WHERE `identifier` = :uuid LIMIT 1;');
		return $stm->execute([
			'uuid' => $this->getData()->identifier,
		]);
	}

	final public function valid(): bool
	{
		return isset($this->_title, $this->_body);
	}

	final public static function getNotificationForUser(PDO $pdo, User $user):? Notification
	{
		$notifications = static::getNotificationsForUser($pdo, $user, 0, 1);
		return count($notifications) === 1 ? $notifications[0] : null;
	}

	final public static function getNotificationsForUser(PDO $pdo, User $user, int $offset = 0, int $limit = 4): array
	{
		$stm = $pdo->prepare('SELECT JSON_OBJECT (
				"data", JSON_OBJECT (
					"identifier", `identifier`
				),
				"title", `title`,
				"body", `body`,
				"icon", `icon`,
				"tag", `tag`,
				"timestamp", `timestamp`,
				"lang", `lang`,
				"dir", `dir`,
				"requireInteraction", `requireInteraction` = 1,
				"vibrate", `vibrate` = 1,
				"silent", `silent` = 1
			) AS `json`
			FROM `Notification`
			WHERE `user` = :user
			AND `seen` = 0
			LIMIT ' . $offset . ', ' . $limit . ';');

			if ($stm->execute(['user' => $user->getIdentifier()]) and $results = $stm->fetchAll(PDO::FETCH_CLASS)) {
				$stm = $pdo->prepare('SELECT `title`,
					`action`,
					`icon`
				FROM `NotificationAction`
				WHERE `notification` = :uuid;');

				return array_map(function(object $result) use ($stm): Notification
				{
					// $pdo->prepare('UPDATE `Notification`
					// SET `seen` = 1
					// WHERE `identifier` = :uuid
					// LIMIT 1;')->execute(['uuid' => $notif->data->identifier]);
					$notif = json_decode($result->json);
					$stm->execute(['uuid' => $notif->data->identifier]);
					$notif->actions = $stm->fetchAll(PDO::FETCH_CLASS);
					return new Notification($notif);

				}, $results);
			} else {
				return [];
			}
	}
}
