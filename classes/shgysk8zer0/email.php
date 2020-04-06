<?php
namespace shgysk8zer0;

use \shgysk8zer0\{EmailCredentials, Person};
use \InvalidArguimentException;
use \StdClass;
use PHPMailer\PHPMailer\{PHPMailer, SMTP, Exception as MailException};

final class Email
{
	private $_creds = null;

	private $_subject = null;

	private $_body = null;

	private $_alt_body = null;

	private $_recipients = [];

	private $_reply_to = null;

	private $_cc = [];

	private $_bcc = [];

	private $_from = null;

	private $_is_html = true;

	private $_attachments = [];

	final public function __construct(EmailCredentials $creds)
	{
		if ($creds->valid()) {
			$this->_creds = $creds;
		} else {
			throw new InvalidArgumentException('Invalid email credentials given');
		}
	}

	final public function addAttachment(string $path, ?string $name = null): bool
	{
		if (@file_exists($path)) {
			$attachment = new StdClass();
			$attachment->path = $path;
			$attachment->name = $name;
			$this->_attachments[] = $attachment;
			return true;
		} else {
			return false;
		}
	}

	final public function addBCC(Person... $people): void
	{
		$this->_bcc = array_merge($this->_bcc, $people);
	}

	final public function addCC(Person... $people): void
	{
		$this->_cc = array_merge($this->_cc, $people);
	}

	final public function addRecipients(Person... $people): void
	{
		$this->_recipients = array_merge($this->_recipients, $people);
	}

	final public function isHTML(bool $val): void
	{
		$this->_is_html = $val;
	}

	final public function setBody(string $val): void
	{
		$this->_body = $val;
	}

	final public function setAltBody(string $val): void
	{
		$this->_alt_body = null;
	}

	final public function setReplyTo(Person $val): void
	{
		$this->_reply_to = $val;
	}

	final public function setSubject(string $val): void
	{
		$this->_subject = $val;
	}

	final public function send(): bool
	{
		$mail = new PHPMailer(true);

		if ($this->_creds->valid() and $this->_creds->loginToMailer($mail)) {
			try {
				if (isset($this->_from)) {
					$mail->setFrom($this->_from->getEmail(), $this->_from->getName());
				} else {
					$mail->setFrom($this->_creds->getUsername(), $this->_creds->getName());
				}

				foreach ($this->_recipients as $person) {
					$mail->addAddress($person->getEmail(), $person->getName());
				}

				foreach ($this->_cc as $person) {
					$mail->addCC($person->getEmail(), $person->getName());
				}

				foreach ($this->_bcc as $person) {
					$mail->addBCC($person->getEmail(), $person->getName());
				}

				foreach ($this->_attachments as $attachment) {
					if (isset($attachment->name)) {
						$mail->addAttachment($attachment->path, $attachment->name);
					} else {
						$mail->addAttachment($attachment->path);
					}
				}
				$mail->isHTML($this->_is_html);
				$mail->Subject = $this->_subject;
				$mail->Body    = $this->_body;
				$mail->send();
				return true;
			} catch (MailException $e) {
				return false;
			}
		} else {
			return false;
		}
	}

	final public function valid(): bool
	{
		return isset($this->_subject, $this->_body)
			and (! empty($this->_recipients) or ! empty($this->_cc) or ! empty($this->_bcc));
	}
}
