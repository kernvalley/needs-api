<?php
namespace Test;
use \shgysk8zer0\{User, Person, Notification, NotificationAction};
use \shgysk8zer0\PHPAPI\{API, PDO, Headers, HTTPException, UUID, URL};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use \Throwable;
use const \Consts\{HMAC_KEY};

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';

try {
	$api = new API();

	$api->on('GET', function(API $req): void
	{
		$pdo = PDO::load();
		$user = new User($pdo, HMAC_KEY);

		if (! $req->get->has('token')) {
			throw new HTTPexception('Missing token', HTTP::BAD_REQUEST);
		} elseif (! $user->loginWithToken($req->get)) {
			throw new HTTPException('Invalid or expired token', HTTP::UNAUTHORIZED);
		} elseif ($notifications = $user->getNotifications($pdo)) {
			Headers::contentType('application/json');
			echo json_encode($notifications);
		} else {
			Headers::status(HTTP::NO_CONTENT);
		}
	});

	$api->on('POST', function(API $req): void
	{
		throw new HTTPException('Not implemented', HTTP::NOT_IMPLEMENTED);
	});

	$api->on('DELETE', function(API $req): void
	{
		throw new HTTPException('Not implemented', HTTP::NOT_IMPLEMENTED);
	});

	$api();
} catch (HTTPException $e) {
	Headers::status($e->getCode());
	Headers::contentType('application/json');
	echo json_encode($e);
} catch (Throwable $e) {
	http_response_code(500);
	header('Content-Type: application/json');
	echo json_encode(['error' => [
		'message' => $e->getMessage(),
		'file'    => $e->getFile(),
		'line'    => $e->getLine(),
		'trace'   => $e->getTrace(),
	]]);
}
