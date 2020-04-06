<?php
namespace PasswordReset;

use \shgysk8zer0\PHPAPI\{PDO, API, Headers, HTTPException, URL};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use \shgysk8zer0\{User, EmailCredentials, Template, Email, Date, Person};
use const \Consts\{HAS_EMAIL_CREDS, EMAIL_CREDS_FILE, HMAC_FILE, TEMPLATES_DIR, SITE_NAME, SITE_URL};
use function \Functions\{hmac_sign_array, hmac_verify};
const ALGO = 'sha3-256';
const TYPE = 'password-recover';

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';

try {
	$api = new API('*');

	$api->on('GET', function(API $req): void
	{
		if ($req->get->has('token')) {
			if ($data = hmac_verify($req->get->get('token', false), HMAC_KEY, ALGO, 'hmac')) {
				Headers::contentType('application/json');

				if (isset($data->date, $data->expires, $data->uuid, $data->type)) {
					$data->expires = new Date($data->expires);
					$data->date = new Date($data->date);
					$now = new Date();

					if ($data->type !== TYPE) {
						throw new HTTPException('Invalid token', HTTP::UNAUTHORIZED);
					} elseif ($now > $data->expires or $now < $data->date) {
						throw new HTTPException('Token expired', HTTP::FORBIDDEN);
					} elseif (! $person = Person::getFromIdentifier(PDO::load(), $data->uuid)) {
						throw new HTTPException('User not found', HTTP::NOT_FOUND);
					} else {
						unset($person->address, $person->telephone);
						echo json_encode($person);
					}
				} else {
					throw new HTTPException('Token data invalid');
				}
			} else {
				throw new HTTPException('Invalid or expired token', HTTP::NOT_FOUND);
			}
		} else {
			throw new HTTPException('Missing token', HTTP::BAD_REQUEST);
		}
	});

	$api->on('POST', function(API $req): void
	{
		if ($req->post->has('token', 'password')) {
			if ($data = hmac_verify($req->post->get('token', false), HMAC_KEY, ALGO, 'hmac')) {
				Headers::contentType('application/json');

				if (isset($data->date, $data->expires, $data->uuid, $data->form)) {
					$data->expires = new Date($data->expires);
					$data->date = new Date($data->date);
					$now = new Date();

					if ($data->form !== FORM) {
						throw new HTTPException('Invalid token', HTTP::UNAUTHORIZED);
					} elseif ($now > $data->expires or $now < $data->date) {
						throw new HTTPException('Token expired', HTTP::FORBIDDEN);
					} elseif (! $person = Person::getFromIdentifier(PDO::load(), $data->uuid)) {
						throw new HTTPException('User not found', HTTP::NOT_FOUND);
					} elseif (! strlen($req->post->get('password', false)) > 8) {
						throw new HTTPException('Password too short');
					} elseif (User::resetPasswordforPerson(PDO::load(), $person, $req->post->get('password', false))) {
						Headers::status(HTTP::NO_CONTENT);
					} else {
						throw new HTTPException('Error updating password', HTTP::INTERNAL_SERVER_ERROR);
					}
				} else {
					throw new HTTPException('Token data invalid');
				}
			} else {
				throw new HTTPException('Invalid or expired token', HTTP::NOT_FOUND);
			}
		} elseif ($req->post->has('email')) {
			if (! filter_var($req->post->get('email', false), FILTER_VALIDATE_EMAIL)) {
				throw new HTTPException('Invalid email address', HTTP::BAD_REQUEST);
			} else {
				ignore_user_abort(true);
				set_time_limit(0);
				ob_start();
				Headers::status(HTTP::ACCEPTED);
				Headers::set('Connection', 'close');
				Headers::set('Content-Length', '0');
				ob_end_flush();
				ob_flush();
				flush();

				if ($user = User::getPersonFromEmail(PDO::load(), $req->post->get('email'))) {
					if (HAS_EMAIL_CREDS) {
						$url = new URL(SITE_URL);
						$creds = new EmailCredentials(EMAIL_CREDS_FILE);
						$now = new Date();
						$expires = $now->modify('+6 hours');
						$email = new Email($creds);

						$hmac = hmac_sign_array([
							'uuid'    => $user->getIdentifier(),
							'date'    => $now->format(Date::W3C),
							'expires' => $expires->format(Date::W3C),
							'type'    => type,
						], HMAC_KEY);

						$template = new Template(TEMPLATES_DIR . 'password-reset.html');
						$template->name = $user->getName();
						$template->site = SITE_NAME;
						$template->expiresDateTime = $expires->format(Date::W3C);
						$template->expires = $expires->format('M jS h:i A');
						$template->siteURL = $url;
						$url->hash = "#password/reset/{$hmac}";
						$template->resetLink = $url;
						$email->addRecipients($user);
						$email->setSubject(sprintf('Password Reset for %s', SITE_NAME));
						$email->isHTML(true);
						$email->setBody($template);
						$email->send();
					}
				}
			}
		} else {
			throw new HTTPException('Bad request', HTTP::BAD_REQUEST);
		}
	});

	$api();
} catch (HTTPException $e) {
	Headers::status($e->getCode());
	Headers::contentType('application/json');
	echo json_encode($e);
} catch (\Throwable $e) {
	http_response_code(500);
	header('Content-Type: application/json');
	echo json_encode(['error' => [
		'message' => $e->getMessage(),
		'file'    => $e->getFile(),
		'line'    => $e->getLine(),
		'trace'   => $e->getTrace(),
	]]);
}
