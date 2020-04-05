<?php
namespace Test;
use \shgysk8zer0\{User, Person, Message};
use \shgysk8zer0\PHPAPI\{API, PDO, Headers, HTTPException, UUID};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use \Throwable;
use const \Consts\{HMAC_KEY};

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';

try {
	$api = new API();

	$api->on('GET', function(API $req): void
	{
		if ($req->get->has('token', 'uuid')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if (! $user->loginWithToken($req->get)) {
				throw new HTTPException('Invalid or expired token', HTTP::UNAUTHORIZED);
			} elseif (! $user->can('viewMessage')) {
				throw new HTTPException('You do not have permission', HTTP::FORBIDDEN);
			} elseif (! $msg = Message::getFromIdentifier($pdo, $req->get->get('uuid'))) {
				throw new HTTPException('No message found', HTTP::NOT_FOUND);
			} else {
				Headers::contentType('application/json');
				$msg->markAsRead($pdo, true);
				echo json_encode($msg);
			}
		} elseif ($req->get->has('token')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if (! $user->loginWithToken($req->get)) {
				throw new HTTPException('Invalid or expired token', HTTP::UNAUTHORIZED);
			} elseif (! $user->can('viewMessage')) {
				throw new HTTPException('You do not have permission', HTTP::FORBIDDEN);
			} elseif (! $msgs = Message::fetchAll($pdo, $req->get->get('offset', false, 0))) {
				throw new HTTPException('No message found', HTTP::NOT_FOUND);
			} else {
				Headers::contentType('application/json');
				echo json_encode($msgs);
			}
		} else {
			throw new HTTPException('Missing token', HTTP::BAD_REQUEST);
		}
	});

	$api->on('POST', function(API $req): void
	{
		if ($req->post->has('identifier')) {
			throw new HTTPException('Not implemented', HTTP::NOT_IMPLEMENTED);
		} elseif (! $req->post->has('email', 'telephone', 'name', 'subject', 'message')) {
			throw new HTTPException('Missing required inputs', HTTP::BAD_REQUEST);
		} elseif (! filter_var($req->post->get('email'), FILTER_VALIDATE_EMAIL)) {
			throw new HTTPException('Email address invalid', HTTP::BAD_REQUEST);
		} elseif (! $msg = Message::createFromUserInput($req->post) and $msg->valid()) {
			throw new HTTPException('Missing input', HTTP::BAD_REQUEST);
		} elseif (! $msg->save(PDO::load())) {
			throw new HTTPException('Error saving message', HTTP::INTERNAL_SERVER_ERROR);
		} else {
			Headers::status(HTTP::CREATED);
			Headers::contentType('application/json');
			exit(json_encode(['uuid' => $msg->getIdentifier()]));
		}
	});

	$api->on('DELETE', function(API $req): void
	{
		if ($req->get->has('token', 'uuid')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if (! $user->loginWithToken($req->get)) {
				throw new HTTPException('Invalid or expired token', HTTP::UNAUTHORIZED);
			} elseif (! $user->can('viewMessage')) {
				throw new HTTPException('You do not have permission', HTTP::FORBIDDEN);
			} elseif (! $msg = Message::getFromIdentifier($pdo, $req->get->get('uuid'))) {
				throw new HTTPException('No message found', HTTP::NOT_FOUND);
			} elseif (! $msg->delete($pdo)) {
				throw new HTTPException('Error deleting message', HTTP::INTERNAL_SERVER_ERROR);
			} else {
				Headers::status(HTTP::NO_CONTENT);
			}
		} else {
			throw new HTTPException('Missing required inputs', HTTP::BAD_REQUEST);
		}
	});

	$api();
} catch (HTTPException $e) {
	Headers::status($e->getCode());
	Headers::contentType('application/json');
	echo json_encode($e);
}
