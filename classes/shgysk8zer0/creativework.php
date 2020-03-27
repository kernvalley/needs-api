<?php
namespace shgysk8zer0;

class CreativeWork extends Thing implements Interfaces\CreativeWork
{
	public const TYPE = 'CreativeWork';

	private $_author = null;

	private $_copyrightYear = null;

	private $_headline = null;

	private $_text = null;

	// @SEE https://schema.org/CreativeWork

	public function jsonSerialize(): array
	{
		return array_filter(array_merge(
			parent::jsonSerialize(),
			[
				'author'        => $this->getAuthor(),
				'headline'      => $this->getHeadline(),
				'copyrightyear' => $this->getCopyrightYear(),
				'text'          => $this->getText(),
			]
		));
	}

	public function getAuthor():? Person
	{
		return $this->_author;
	}

	public function setAuthor(?Person $val): void
	{
		$this->_author = $val;
	}

	public function getCopyrightYear():? int
	{
		return $this->_copyrightYear;
	}

	public function setCopyrightYear(?int $val): void
	{
		$this->_copyrightYear = $val;
	}

	public function getHeadline():? string
	{
		return $this->_headline;
	}

	public function setHeadline(?string $val): void
	{
		$this->_headline = $val;
	}

	public function getText():? string
	{
		return $this->_text;
	}

	public function setText(?string $val): void
	{
		$this->_text = $val;
	}

	public function setFromObject(?object $data): void
	{
		parent::setFromObject($data);
		if (isset($data->author)) {
			if (is_string($data->author)) {
				$author = new Person();
				$author->setName($data->author);
				$this->setAuthor($author);
				unset($author);
			} elseif (is_object($data->author)) {
				$this->setAuthor(new Person($data->author));
			}
		}

		$this->setCopyrightYear($data->copyrightYear ?? null);
		$this->setHeadline($data->headline ?? null);
		$this->setText($data->text ?? null);
	}
}
