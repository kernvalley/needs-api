<?php
namespace User;

use \Throwable;
use \shgysk8zer0\{User};
use \shgysk8zer0\PHPAPI\{API, PDO, Headers, HTTPException};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use const \Consts\{HMAC_FILE};

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';

try {
	$api = new API();

	$api->on('GET', function(API $req): void
	{
		if (! $req->get->has('token', 'uuid')) {
			throw new HTTPException('Missing UUID or token', HTTP::BAD_REQUEST);
		} else {
			$pdo = PDO::load();
			$user = new User($pdo, file_get_contents(HMAC_FILE));

			if ($user->loginWithToken($req->get) and $user->can('listUser')) {
				Headers::contentType('application/json');
				echo json_encode($user);
			} else {
				throw new HTTPException('Cannot query users', HTTP::UNAUTHORIZED);
			}
		}
	});

	$api->on('POST', function(API $req): void
	{
		if ($req->post->has('email', 'password', 'name', 'telephone', 'address')) {
			// This is a registration
			// @ TODO Check address is valid
		} elseif ($req->post->has('email', 'password')) {
			$user = new User(PDO::load(), file_get_contents(\Consts\HMAC_FILE));
			if ($user->login($req->post)) {
				Headers::contentType('application/json');
				echo json_encode($user);
			} else {
				throw new HTTPException('Invalid username or password', HTTP::BAD_REQUEST);
			}
		} else {
			throw new HTTPException('Missing request data', HTTP::BAD_REQUEST);
		}
	});

	$api->on('DELETE', function(API $req): void
	{
		if (! $req->get->has('token', 'uuid')) {
			throw new HTTPException('Missing UUID or token', HTTP::BAD_REQUEST);
		} else {
			$pdo = PDO::load();
			$user = new User($pdo, file_get_contents(HMAC_FILE));

			if ($user->loginWithToken($req->get) and $user->can('deleteUser')) {
				Headers::contentType('application/json');
				echo json_encode($user);
			} else {
				throw new HTTPException('Cannot delete users', HTTP::UNAUTHORIZED);
			}
		}
	});

	$api();
} catch (HTTPException $e) {
	Headers::status($e->getCode());
	Headers::contentType('application/json');
	echo json_encode($e);
} catch(Throwable $e) {
	Headers::status(HTTP::INTERNAL_SERVER_ERROR);
	Headers::contentType('application/json');
	echo json_encode([
		'message' => $e->getMessage(),
		'file'    => $e->getFile(),
		'line'    => $e->getLine(),
		'trace'   => $e->getTrace(),
	]);
}
