<?php
namespace shgysk8zer0;
use \PDO;

class ImageObject extends MediaObject implements Interfaces\ImageObject
{
	// @TODO Create from POST + FILES
	public const TYPE = 'ImageObject';

	private $_caption = null;

	public function jsonSerialize(): array
	{
		return array_filter(array_merge(
			parent::jsonSerialize(),
			[
				'caption' => $this->getCaption(),
			]
		));
	}

	final public function getCaption():? string
	{
		return $this->_caption;
	}

	final public function setCaption(?string $val): void
	{
		$this->_caption = $val;
	}

	public function setFromObject(?object $data): void
	{
		parent::setFromObject($data);
		$this->setCaption($data->caption ?? null);
	}

	public function save(PDO $pdo):? string
	{
		if ($this->getIdentifier() === null) {
			$this->setIdentifier(self::generateUUID());
		}

		$stm = $pdo->prepare('INSERT INTO `ImageObject` (
			`identifier`,
			`url`,
			`height`,
			`width`,
			`caption`
		) VALUES (
			:identifier,
			:url,
			:height,
			:width,
			:caption
		) ON DUPLICATE KEY UPDATE
			`url`     = :url,
			`height`  = :height,
			`width`   = :width,
			`caption` = :caption;');

		if ($stm->execute([
			'identifier' => $this->getIdentifier(),
			'url'        => $this->getUrl(),
			'height'     => $this->getHeight(),
			'width'      => $this->getWidth(),
			'caption'    => $this->getCaption(),
		]) and $stm->rowCount() === 1) {
			return $this->getIdentifier();
		} else {
			return null;
		}
	}

	public static function getSQL(): string
	{
		return 'JSON_OBJECT(
			"identifier", `ImageObject`.`identifier`,
			"url", `ImageObject`.`url`,
			"height", `ImageObject`.`height`,
			"width", `ImageObject`.`width`,
			"caption", `ImageObject`.`caption`,
			"uploadDate", DATE_FORMAT(`ImageObject`.`uploadDate`, "%Y-%m-%dT%TZ")
		)';
	}

	public function valid(): bool
	{
		return $this->getUrl() !== null;
	}

	public static function getJoins(): array
	{
		return [];
	}
}
