<?php

namespace Functions;
use const \Consts\{DEBUG, ERROR_LOG, UPLOADS_DIR, BASE, EMAIL_CREDS_FILE};
use \shgysk8zer0\PHPAPI\{PDO, User, JSONFILE, Headers, HTTPException, Request, URL};
use PHPMailer\PHPMailer\{PHPMailer, SMTP, Exception as MailException};
use \shgysk8zer0\{Person, EmailCredentials};
use \StdClass;
use \DateTime;
use \Throwable;
use \ErrorException;

function is_pwned(string $pwd): bool
{
	$hash   = strtoupper(sha1($pwd));
	$prefix = substr($hash, 0, 5);
	$rest   = substr($hash, 5);
	$req    = new Request("https://api.pwnedpasswords.com/range/{$prefix}");
	$resp   = $req->send();

	if ($resp->ok) {
		return strpos($resp->body, "{$rest}:") !== false;
	} else {
		return false;
	}
}

function hmac_verify(string $data, string $hmac_key, $algo = 'sha2-256', string $key = 'hmac'):? object
{
	if ($obj = @json_decode(@base64_decode($data)) and isset($obj->{$key}) and is_string($obj->{$key})) {
		$hmac = $obj->{$key};
		unset($obj->{$key});

		$hash = hash_hmac($algo, json_encode($obj), $hmac_key);
		if (isset($hash) and hash_equals($hash, $hmac)) {
			return $obj;
		} else {
			return null;
		}
	} else {
		return null;
	}
}

function hmac_sign_array(array $data, string $hmac_key, string $algo = 'sha3-256', string $key = 'hmac'):? string
{
	$json = json_encode($data);

	if ($hmac = hash_hmac($algo, $json, $hmac_key)) {
		$data[$key] = $hmac;
		return base64_encode(json_encode($data));
	} else {
		return null;
	}
}

function email(EmailCredentials $creds, Person $person, ?Person $from = null, string $subject, string $body): bool
{
	$mail = new PHPMailer(true);

	if ($creds->valid() and $creds->loginToMailer($mail)) {
		try {
			if (isset($from)) {
				$mail->setFrom($from->getEmail(), $from->getName());
			} else {
				$mail->setFrom($creds->getUsername(), $creds->getName());
			}
			$mail->addAddress($person->getEmail(), $person->getName());
			$mail->isHTML(true);                                             // Set email format to HTML
			$mail->Subject = $subject;
			$mail->Body    = $body;
			$mail->send();
			return true;
		} catch (MailException $e) {
			return false;
		}
	} else {
		return false;
	}
}

function upload_path(): string
{
	$date = new DateTime();
	return UPLOADS_DIR . $date->format(sprintf('Y%sm%s', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR));
}

function https(): bool
{
	return array_key_exists('HTTPS', $_SERVER) and ! empty($_SERVER['HTTPS']);
}

function dnt(): bool
{
	return array_key_exists('HTTP_DNT', $_SERVER) and $_SERVER['HTTP_DNT'] === '1';
}

function is_cli(): bool
{
	return in_array(PHP_SAPI, ['cli']);
}

function error_handler(int $errno, string $errstr, string $errfile, int $errline = 0): bool
{
	return log_exception(new ErrorException($errstr, 0, $errno, $errfile, $errline));
}

function exception_handler(Throwable $e)
{
	if ($e instanceof HTTPException) {
		log_exception($e);
		Headers::status($e->getCode());
		Headers::contentType('application/json');
		exit(json_encode($e));
	} else {
		log_exception($e);
		Headers::status(Headers::INTERNAL_SERVER_ERROR);
		Headers::contentType('application/json');
		exit(json_encode([
			'error' => [
				'message' => 'Internal Server Error',
				'status'  => Headers::INTERNAL_SERVER_ERROR,
			],
		]));
	}
}

function log_exception(Throwable $e): bool
{
	static $stm = null;

	if (is_null($stm)) {
		$pdo = PDO::load();
		$stm = $pdo->prepare('INSERT INTO `ServerErrors` (
			`type`,
			`message`,
			`file`,
			`line`,
			`code`,
			`remoteIP`,
			`url`
		) VALUES (
			:type,
			:message,
			:file,
			:line,
			:code,
			:ip,
			:url
		);');
	}

	$url = URL::getRequestUrl();
	unset($url->password, $url->search);
	$code = $e->getCode();

	return $stm->execute([
		':type'    => get_class($e),
		':message' => $e->getMessage(),
		':file'    => str_replace(BASE, null, $e->getFile()),
		':line'    => $e->getLine(),
		':code'    => is_int($code) ? $code : 0,
		':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
		':url'     => $url,
	]);
}
