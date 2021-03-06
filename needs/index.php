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
				if ($need = NeedRequest::getFromIdentifier($pdo, $req->get->get('uuid'))) {
					// $need->setIdentifier($req->get->get('uuid'));
					$need->setAttachments($need->fetchAttachments($pdo));
					$items = $need->fetchItems($pdo, $req->get->get('offset'));
					$need->setItems($items);
					Headers::contentType('application/json');
					exit(json_encode($need));
				} else {
					throw new HTTPException('Not found', HTTP::NOT_FOUND);
				}
			} else {
				throw new HTTPException('Permission not granted', HTTP::FORBIDDEN);
			}
		} elseif ($req->get->has('token', 'attachments')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if (! $user->loginWithToken($req->get)) {
				throw new HTTPException('Token invalid or expired', HTTP::UNAUTHORIZED);
			} elseif (! $user->can('listNeed')) {
				throw new HTTPException('You do not have permission to do that', HTTP::FORBIDDEN);
			} elseif (! $need = NeedRequest::getFromIdentifier($pdo, $req->get->get('attachments'))) {
				throw new HTTPException('Not found', HTTP::NOT_FOUND);
			} else {
				Headers::contentType('application/json');
				echo json_encode($need->fetchAttachments($pdo, $req->get->get('offset', false, 0)));
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
				$need = NeedRequest::getFromIdentifier($pdo, $req->post->get('uuid'));
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
				$need = NeedRequest::getFromIdentifier($pdo, $req->post->get('uuid'));

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
		} elseif ($req->post->has('token', 'user', 'tags', 'title', 'description', 'items')) {
			// Admin creating request
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if ($user->loginWithToken($req->post) and $user->can('adminCreateNeed')) {
				if ($person = Person::getIdentifierFromName($pdo, $req->post->get('user'))) {
					$needs = new NeedRequest();
					$needs->setFromUserInput($req->post);
					$needs->setUserFromUser($user);
					$pdo->beginTransaction();

				if ($uuid = $needs->save($pdo)) {
					$item_stm = $pdo->prepare('INSERT INTO `items` (
						`request`,
						`quantity`,
						`item`
					) VALUES (
						:request,
						:quantity,
						:item);');

					foreach ($req->post->get('items') as $item) {
						if (! $item_stm->execute([
							'request'  => $uuid,
							'quantity' => $item->quantity,
							'item'     => $item->item,
						]) or $item_stm->rowCount() !== 1) {
							throw new HTTPException('Error saving request item', HTTP::INTERNAL_SERVER_ERROR);
						}
					}

					$pdo->commit();
					Headers::contentType('application/json');
					Headers::status(HTTP::CREATED);
					echo json_encode(['uuid' => $uuid]);
				} else {
						throw new HTTPException('Error creating requet', HTTP::INTERNAL_SERVER_ERROR);
					}
				} else {
					throw new HTTPExeption('You do not have permission for that', HTTP::FORBIDDEN);
				}
			} else {
				throw new HTTPException('Login rejected or permission denied', HTTP::FORBIDDEN);
			}
		} elseif ($req->post->has('token', 'tags', 'title', 'description', 'items')) {
			// Creating request
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if ($user->loginWithToken($req->post) and $user->can('createNeed')) {
				$needs = new NeedRequest();
				$needs->setFromUserInput($req->post);
				$needs->setUserFromUser($user);
				$pdo->beginTransaction();

				if ($uuid = $needs->save($pdo)) {
					$item_stm = $pdo->prepare('INSERT INTO `items` (
						`request`,
						`quantity`,
						`item`
					) VALUES (
						:request,
						:quantity,
						:item);');

					foreach ($req->post->get('items') as $item) {
						if (! $item_stm->execute([
							'request'  => $uuid,
							'quantity' => $item->quantity,
							'item'     => $item->item,
						]) or $item_stm->rowCount() !== 1) {
							throw new HTTPException('Error saving request item', HTTP::INTERNAL_SERVER_ERROR);
						}
					}

					$pdo->commit();
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
			} elseif (! $need = NeedRequest::getFromIdentifier($pdo, $req->post->get('uuid'))) {
				throw new HTTPException('Not found', HTTP::NOT_FOUND);
			} elseif ($user->loginWithToken($req->post) and $user->can('editNeed')) {
				$pdo->beginTransaction();
				$fname = UPLOADS_DIR . "{$req->files->upload->md5}.{$req->files->upload->ext}";

				if (file_exists($fname)) {
					throw new HTTPException('File already uploaded', HTTP::CONFLICT);
				} elseif ($result = $need->attach($pdo, $req->files->upload, $fname, $user)) {
					Headers::status(HTTP::CREATED);
					$pdo->commit();
					echo json_encode(['uuid' => $result]);
				} else {
					$pdo->rollBack();
					throw new HTTPException('Error saving file', HTTP::INTERNAL_SERVER_ERROR);
				}
			} else {
				throw new HTTPException('Not authorize', HTTP::NOT_AUTHORIZED);
			}
		} else {
			throw new HTTPException('Invalid request', HTTP::BAD_REQUEST);
		}
	});

	$api->on('DELETE', function(API $req): void
	{
		if ($req->get->has('token', 'uuid')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if (! $user->loginWithToken($req->get)) {
				throw new HTTPException('Token invalid or expired', HTTP::UNAUTHORIZED);
			} elseif (! $user->can('deleteNeed')) {
				throw new HTTPException('Permission denied', HTTP::FORBIDDEN);
			} else {
				if (NeedRequest::delete($pdo, $req->get->get('uuid'))) {
					Headers::status(HTTP::NO_CONTENT);
				} else {
					throw new HTTPException('Not found', HTTP::NOT_FOUND);
				}
			}
		} else {
			throw new HTTPException('Missing token or UUID', HTTP::BAD_REQUEST);
		}
	});

	$api();
} catch (HTTPException $e) {
	Headers::status($e->getCode());
	Headers::contentType('application/json');
	echo json_encode($e);
}
