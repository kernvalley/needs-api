<?php
namespace shgysk8zer0;

use PHPMailer\PHPMailer\{PHPMailer, SMTP, Exception as MailerExeception};
use \ErrorException;
use \InvalidArgumentException;
use \JSONSerializable;

final class EmailCredentials
{
	private $_host = null;

	private $_name = null;

	private $_password = null;

	private $_port = 465;

	private $_star_tls = false;

	private $_username = null;

	final public function __construct(?string $fname = null)
	{
		if (isset($fname)) {
			if ($creds = json_decode(file_get_contents($fname))) {
				$this->setHost($creds->host);
				$this->setUsername($creds->username);
				$this->setPassword($creds->password);

				if (isset($creds->port)) {
					$this->setPort($creds->port);
				}

				if (isset($creds->starTLS)) {
					$this->setStarTLS($creds->starTLS);
				}

				if (isset($creds->name)) {
					$this->setName($creds->name);
				}
			} else {
				throw new ErrorException('Error locating or parsing email credentials');
			}
		}
	}

	final public function __debugInfo(): array
	{
		return [
			'host'     => $this->getHost(),
			'port'     => $this->getPort(),
			'username' => $this->getUsername(),
			'password' => isset($this->_password) ? '********' : null,
			'name'     => $this->getName(),
			'StarTLS'  => $this->star_tls,
		];
	}

	final public function getHost():? string
	{
		return $this->_host;
	}

	final public function setHost(string $val): void
	{
		$this->_host = $val;
	}

	final public function getName():? string
	{
		return $this->_name;
	}

	final public function setName(string $val): void
	{
		$this->_name = $val;
	}

	final public function setPassword(string $val): void
	{
		$this->_password = $val;
	}

	final public function getPort(): int
	{
		return $this->_port;
	}

	final public function setPort(int $val): void
	{
		$this->_port = $val;
	}

	final public function getStarTLS(): bool
	{
		return $this->_star_tls;
	}

	final public function setStarTLS(bool $val): void
	{
		$this->_star_tls = $val;
	}

	final public function getUsername():? string
	{
		return $this->_username;
	}

	final public function setUsername(string $val): void
	{
		if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
			$this->_username = $val;
		} else {
			throw new InvalidArgumentException('Username is not a valid email address');
		}
	}

	final public function valid(): bool
	{
		return isset($this->_host, $this->_username, $this->_password);
	}

	final public function loginToMailer(PHPMailer &$mail): bool
	{
		if ($this->valid()) {
			try {
				$mail->isSMTP();                          // Send using SMTP
				$mail->Host       = $this->getHost();     // Set the SMTP server to send through
				$mail->SMTPAuth   = true;                 // Enable SMTP authentication
				$mail->Username   = $this->getUsername(); // SMTP username
				$mail->Password   = $this->_password;     // SMTP password
				$mail->SMTPSecure = $this->getStarTLS()
					? PHPMailer::ENCRYPTION_STARTTLS
					: PHPMailer::ENCRYPTION_SMTPS;       // Enable TLS
				$mail->Port       = $this->getPort();    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
				return true;
			} catch (MailerExeception $e) {
				return false;
			}
		} else {
			return false;
		}
	}
}
