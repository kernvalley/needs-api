<?php
namespace Needs;

use \Throwable;
use \shgysk8zer0\{User, Person, Need};
use \shgysk8zer0\PHPAPI\{API, PDO, Headers, HTTPException};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use const \Consts\{HMAC_KEY};

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';

try {
	$api = new API();

	$api->on('GET', function(API $req): void
	{
		if ($req->get->has('token', 'uuid')) {
			// Get full details
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if ($user->loginWithToken($req->get) and $user->can('listNeed')) {
				$needs = new Need($pdo);
				$result = $needs->getByIdentifier($req->get->get('uuid'));

				if (isset($result)) {
					Headers::contentType('application/json');
					echo json_encode($result);
				} else {
					throw new HTTPException('No available needs', HTTP::NOT_FOUND);
				}
			} else {
				throw new HTTPException('Permission not granted', HTTP::FORBIDDEN);
			}
		} elseif ($req->get->has('token')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if ($user->loginWithToken($req->get) and $user->can('listNeed')) {
				$needs = new Need($pdo);
				$unassigned = $needs->listUnassigned($req->get->get('uuid'));
				$assigned = $needs->searchByAssignee($user->getPerson()->getIdentifier());

				if (empty($assigned) and empty($unassigned)) {
					throw new HTTPException('No available needs', HTTP::NOT_FOUND);
				} else {
					Headers::contentType('application/json');
					$filter = function(object $req): object
					{
						unset($req->description);
						unset($req->user->address->streetAddress);
						unset($req->user->telephone);
						unset($req->user->email);

						return $req;
					};
					echo json_encode([
						'assigned'   => array_map($filter, $assigned),
						'unassigned' => array_map($filter, $unassigned),
					]);
				}
			} else {
				throw new HTTPException('Permission not granted', HTTP::FORBIDDEN);
			}
		} else {
			throw new HTTPException('Missing token', HTTP::BAD_REQUEST);
		}
	});

	$api->on('POST', function(API $req): void
	{
		if ($req->post->has('token', 'uuid')) {
			// Update existing Request
			throw new HTTPException('Not implemented', HTTP::NOT_IMPLEMENTED);
		} elseif ($req->post->has('token')) {
			// Creating request
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if ($user->loginWithToken($req->post) and $user->can('createNeed')) {
				$needs = new Needs($pdo);
				$uuid = $needs->createFromUserInput($req->post);

				if (isset($uuid)) {
					Headers::contentType('application/json');
					Headers::status(HTTP::CREATED);
					echo json_encode(['uuid' => $uuid]);
				} else {
					throw new HTTPException('Error creating requet', HTTP::INTERNAL_SERVER_ERROR);
				}
			} else {
				throw new HTTPException('Login rejected or permission denied', HTTP::FORBIDDEN);
			}
		} else {
			throw new HTTPException('Missing token', HTTP::BAD_REQUEST);
		}
	});

	$api->on('DELTE', function(API $req): void
	{
		if ($req->get->has('token', 'uuid')) {
			throw new HTTPException('Not yet implmented', HTTP::NOT_IMPLEMENTED);
		} else {
			throw new HTTPException('Missing token', HTTP::BAD_REQUEST);
		}
	});

	$api();
} catch (HTTPException $e) {
	Headers::status($e->getCode());
	Headers::contentType('application/json');
	echo json_encode($e);
}
