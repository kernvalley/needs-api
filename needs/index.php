<?php
namespace Needs;

use \Throwable;
use \shgysk8zer0\{User, Person, Need, NeedRequest};
use \shgysk8zer0\PHPAPI\{API, PDO, Headers, HTTPException, Files};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use const \Consts\{HMAC_KEY, UPLOADS_DIR, IMAGE_TYPES};

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';
Files::setAllowedTypes('image/jpeg', 'image/png');
try {
	$api = new API();

	$api->on('GET', function(API $req): void
	{
		if ($req->get->has('token', 'uuid')) {
			// Get full details
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if ($user->loginWithToken($req->get) and $user->can('listNeed')) {
				if ($need = NeedRequest::getByIdentifier($pdo, $req->get->get('uuid'))) {
					Headers::contentType('application/json');
					exit(json_encode($need));
				} else {
					throw new HTTPException('Not found', HTTP::NOT_FOUND);
				}
			} else {
				throw new HTTPException('Permission not granted', HTTP::FORBIDDEN);
			}
		} elseif ($req->get->has('token')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if ($user->loginWithToken($req->get) and $user->can('listNeed')) {
				$needs = NeedRequest::searchByAssigned($pdo, $user->getPerson());

				if (! empty($needs)) {
					Headers::contentType('application/json');
					echo json_encode($needs);
				} else {
					Headers::status(HTTP::NO_CONTENT);
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
			if ($user->loginWithToken($req->post) and $user->can('editNeed')) {
				// @TODO Get `Person`.`identifier`
				$need = NeedRequest::getByIdentifier($pdo, $req->post->get('uuid'));
				if (isset($need)) {
					$assignee = Person::getFromIdentifier($pdo, $req->post->get('assignee'));
					if (isset($assignee)) {
						$need->assignPerson($assignee);

						if ($need->save($pdo) !== null) {
							Headers::status(HTTP::NO_CONTENT);
						} else {
							throw new HTTPException('Error updating assignee', HTTP::INTERNAL_SERVER_ERROR);
						}
					} else {
						throw new HTTPException('Assignee not found', HTTP::NOT_FOUND);
					}
				} else {
					throw new HTTPException('Request not found', HTTP::NOT_FOUND);
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
				$need = NeedRequest::getByIdentifier($pdo, $req->post->get('uuid'));

				if (isset($need)) {
					$need->setStatus($req->post->get('status'));

					if ($need->save($pdo) !== null) {
						Headers::status(HTTP::NO_CONTENT);
					} else {
						throw new HTTPException('Error updating status', HTTP::INTERNAL_SERVER_ERROR);
					}
				} else {
					throw new HTTPException('Not found', HTTP::NOT_FOUND);
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
				$needs = new NeedRequest();
				$needs->setFromUserInput($req->post);
				$needs->setUserFromUser($user);

				if ($uuid = $needs->save($pdo)) {
					Headers::contentType('application/json');
					Headers::status(HTTP::CREATED);
					echo json_encode(['uuid' => $uuid]);
				} else {
					throw new HTTPException('Error creating requet', HTTP::INTERNAL_SERVER_ERROR);
				}
			} else {
				throw new HTTPException('Login rejected or permission denied', HTTP::FORBIDDEN);
			}
		} elseif ($req->post->has('token', 'uuid') and $req->files->has('upload')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if ($req->files->upload->hasError()) {
				throw $req->files->upload->error;
			} elseif ($user->loginWithToken($req->post) and $user->can('editNeed')) {
				$pdo->beginTransaction();
				$fname = UPLOADS_DIR . "{$req->files->upload->md5}.{$req->files->upload->ext}";
				$stm = $pdo->prepare("INSERT INTO `ImageObject` (
					`identifier`,
					`url`,
					`height`,
					`width,
				) VALUES (
					:uuid,
					:url,
					:height,
					:width
				);");
				$req->files->upload->saveAs(UPLOADS_DIR . "{$req->files->upload->md5}.{$req->files->upload->ext}", true);
				Headers::contentType('application/json');
				exit(json_encode([
					'upload' => $req->files->upload,
					'user'   => $user,
				]));
				throw new HTTPException('Missing token', HTTP::NOT_IMPLEMENTED);
			} else {
				throw new HTTPException('Not authorize', HTTP::NOT_AUTHORIZED);
			}
		} else {
			Headers::contentType('application/json');
			Headers::status(HTTP::BAD_REQUEST);
			exit(json_encode($req));
			throw new HTTPException('Invalid request', HTTP::BAD_REQUEST);
		}
	});

	$api->on('DELETE', function(API $req): void
	{
		if ($req->get->has('token', 'uuid')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if ($user->loginWithToken($req->get) and $user->can('deleteNeed')) {
				if (NeedRequest::delete($pdo, $req->get->get('uuid'))) {
					Headers::status(HTTP::NO_CONTENT);
				} else {
					throw new HTTPException('Not found', HTTP::NOT_FOUND);
				}
			} else {
				throw new HTTPException('Permission denied', HTTP::FORBIDDEN);
			}
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
