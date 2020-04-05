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
				throw new HTTPException('You do not have permission to do that', HTTP::FORBIDDEN);
			} elseif ($msg = Message::getFromIdentifier($pdo, $req->get->get('uuid', false))) {
				$msg->markAsRead($pdo, true);
				Headers::contentType('application/json');
				echo json_encode($msg);
			} else {
				throw new HTTPException('No messages found', HTTP::NOT_FOUND);
			}
		} elseif ($req->get->has('token')) {
			// Get messages list
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if (! $user->loginWithToken($req->get)) {
				throw new HTTPException('Invalid or expired token', HTTP::UNAUTHORIZED);
			} elseif (! $user->can('viewMessage')) {
				throw new HTTPException('You do not have permission to do that', HTTP::FORBIDDEN);
			} elseif ($msgs = Message::fetchAll($pdo, $req->get->get('offset', false, 0))) {
				Headers::contentType('application/json');
				echo json_encode($msgs);
			} else {
				throw new HTTPException('No messages found', HTTP::NOT_FOUND);
			}
		} else {
			throw new HTTPException('Missing token in request', HTTP::BAD_REQUEST);
		}
	});

	$api->on('POST', function(API $req): void
	{
		if ($req->post->has('identifier')) {
			throw new HTTPException('Not implemented', HTTP::NOT_IMPLEMENTED);
		} elseif (! $req->post->has('name', 'email', 'telephone', 'subject', 'message')) {
			throw new HTTPException('Missing required inputs', HTTP::BAD_REQUEST);
		} elseif ($msg = Message::createFromUserInput($req->post) and $msg->valid()) {
			if ($uuid = $msg->save(PDO::load())) {
				Headers::status(HTTP::CREATED);
				Headers::contentType('application/json');
				echo json_encode(['identifier' => $uuid]);
			} else {
				throw new HTTPException('Error saving message to database', HTTP::INTERNAL_SERVER_ERROR);
			}
		} else {
			throw new HTTPException('An unknown error occured saving message', HTTP::INTERNAL_SERVER_ERROR);
		}
	});

	$api->on('DELETE', function(API $req): void
	{
		$pdo = PDO::load();
		$user = new User($pdo, HMAC_KEY);

		if (! $req->get->has('token', 'uuid')) {
			throw new HTTPException('Missing UUID or token', HTTP::BAD_REQUEST);
		} elseif (! $user->loginWithToken($req->get)) {
			throw new HTTPException('Invalid or expired token', HTTP::UNAUTHORIZED);
		} elseif (! $user->can('deleteMessage')) {
			throw new HTTPException('You do not have permission to do that', HTTP::FORBIDDEN);
		} elseif ($msg = Message::getFromIdentifier($pdo, $req->get->get('uuid'))) {
			if ($msg->delete($pdo)) {
				Headers::status(HTTP::NO_CONTENT);
			} else {
				throw new HTTPException('Error deleting message', HTTP::INTERNAL_SERVER_ERROR);
			}
		} else {
			throw new HTTPException('Message not found', HTTP::NOT_FOUND);
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
