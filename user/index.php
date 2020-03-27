<?php
namespace User;

use \Throwable;
use \shgysk8zer0\{User, Person};
use \shgysk8zer0\PHPAPI\{API, PDO, Headers, HTTPException};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use \shgysk8zer0\PHPAPI\Interfaces\{InputData};
use const \Consts\{HMAC_KEY};

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';

try {
	$api = new API();

	$api->on('GET', function(API $req): void
	{
		if (! $req->get->has('token', 'uuid')) {
			throw new HTTPException('Missing UUID or token', HTTP::BAD_REQUEST);
		} else {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

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
		if ($req->post->has('password', 'person') and $req->post->get('person')) {
			// This is a registration
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);
			if ($user->register($req->post)) {
				Header::contentType('application/json');
				echo $user;
			} else {
				throw new HTTPException('Registration failed. Missing data or user already exists', HTTP::BAD_REQUEST);
			}
		} elseif ($req->post->has('email', 'password')) {
			$user = new User(PDO::load(), HMAC_KEY);
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
			$user = new User($pdo, HMAC_KEY);

			if ($user->loginWithToken($req->get) and $user->can('deleteUser')) {
				throw new HTTPException('Not yet implemented', HTTP::NOT_IMPLEMENTED);
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
}
