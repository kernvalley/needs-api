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
					throw new HTTPException('No available needs', HTTP::NO_CONTENT);
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
		if ($req->post->has('token', 'uuid', 'assignee')) {
			// Assign user
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);
			if ($user->loginWithToken($req->post) and ($user->getPerson()->getIdentifier() === $req->post->get('assignee') or $user->can('editNeed'))) {
				// @TODO Get `Person`.`identifier`
				$stm = $pdo->prepare('UPDATE `needs`
					SET `assigned` = :assigned
					WHERE `identifier` = :uuid
					LIMIT 1;');
				if ($stm->execute([
					'assigned' => $req->post->get('assignee'),
					'uuid' => $req->post->get('uuid'),
				]) and $stm->rowCount() === 1) {
					Headers::status(HTTP::NO_CONTENT);
				} else {
					throw new HTTPException('Error updating request', HTTP::INTERNAL_SERVER_ERROR);
				}
			} else {
				throw new HTTPException('Token not valid or permission denied', HTTP::FORBIDDEN);
			}
		} elseif ($req->post->has('token', 'uuid', 'status')) {
			// Update existing Request
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);
			// @TODO allow assigned users to update status, regardless of permissions
			if ($user->loginWithToken($req->post) and $user->can('editNeed')) {
				$stm = $pdo->prepare('Update `needs` SET `status` = :status WHERE `identifier` = :uuid LIMIT 1;');
				if ($stm->execute([
					'status' => $req->post->get('status'),
					'uuid'   => $req->post->get('uuid'),
				]) and $stm->rowCount() === 1) {
					Headers::status(HTTP::NO_CONTENT);
				} else {
					throw new HTTPException('Failed to update status', HTTP::INTERNAL_SERVER_ERROR);
				}
			} else {
				// Login failed
				throw new HTTPException('Token not valid or permission denied', HTTP::FORBIDDEN);
			}
		} elseif ($req->post->has('token', 'tags', 'title', 'description')) {
			// Creating request
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if ($user->loginWithToken($req->post) and $user->can('createNeed')) {
				$needs = new Need($pdo);
				$uuid = $needs->createFromUserInput($user, $req->post);

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
			Headers::status(HTTP::BAD_REQUEST);
			Headers::contentType('application/json');
			exit(json_encode($req));
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
