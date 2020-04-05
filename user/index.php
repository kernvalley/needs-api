<?php
namespace User;

use \Throwable;
use \shgysk8zer0\{User, Person, ImageObject};
use \shgysk8zer0\PHPAPI\{API, PDO, Headers, HTTPException, Files};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use \shgysk8zer0\PHPAPI\Interfaces\{InputData};
use const \Consts\{HMAC_KEY, UPLOADS_DIR};

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';


try {
	$api = new API();

	$api->on('GET', function(API $req): void
	{
		$pdo = PDO::load();
		$user = new User($pdo, HMAC_KEY);

		if ($req->get->has('token', 'uuid')) {
			// Get individual user
			if (! $user->loginWithToken($req->get)) {
				throw new HTTPException('Invalid or expired token', HTTP::UNAUTHROIZED);
			} elseif (! $user->can('listUser')) {
				throw new HTTPException('You do not have permission for that', HTTP::FORBIDDEN);
			} elseif ($match = User::getFromIdentifier($pdo, $req->get->get('uuid', false))) {
				Headers::contentType('application/json');
				echo json_encode($match);
			} else {
				throw new HTTPException('User not found', HTTP::NOT_FOUND);
			}
		} elseif ($req->get->has('token')) {
			// List Users
			if (! $user->loginWithToken($req->get)) {
				throw new HTTPException('Invalid or expired token', HTTP::UNAUTHROIZED);
			} elseif (! $user->can('listUser')) {
				throw new HTTPException('You do not have permission for that', HTTP::FORBIDDEN);
			} elseif ($users = User::fetchAll($pdo, $req->get->get('offset', false, 0))) {
				Headers::contentType('application/json');
				echo json_encode($users);
			} else {
				throw new HTTPException('No users found', HTTP::NOT_FOUND);
			}
		} else {
			throw new HTTPException('Invalid request', HTTP::BAD_REQUEST);
		}
	});

	$api->on('POST', function(API $req): void
	{
		Files::setAllowedTypes('image/jpeg', 'image/png');

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
		} elseif ($req->post->has('token') and $req->files->has('image')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if ($user->loginWithToken($req->post)) {
				$fname = sprintf('%s/%s.%s', rtrim(UPLOADS_DIR, '/'), $req->files->image->md5, $req->files->image->ext);
				$pdo->beginTransaction();

				if ($req->files->image->hasError()) {
					throw $req->files->image->error;
				} elseif (@file_exists($fname)) {
					throw new HTTPException('Image already uploaded: ' . $fname, HTTP::CONFLICT);
				} elseif ($img = $user->setImageFromFile($pdo, $req->files->image, $fname)) {
					Headers::status(HTTP::CREATED);
					Headers::contentType('application/json');
					$pdo->commit();
					echo json_encode($img);
				} else {
					if ($pdo->inTransaction()) {
						$pdo->rollBack();
					}
					if (@file_exists($fname)) {
						@unlink($fname);
					}
					throw new HTTPException('Error uploading or saving image', HTTP::INTERNAL_SERVER_ERROR);
				}
			}
		} elseif ($req->post->has('token', 'user', 'role')) {
			// Updating User role
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if (! $user->loginWithToken($req->post)) {
				throw new HTTPException('Token invalid or expired', HTTP::UNAUTHORIZED);
			} elseif ($user->getIdentifier() === $req->post->get('user')) {
				throw new HTTPException('You are not allowed to change your own role', HTTP::FORBIDDEN);
			} elseif (! $user->can('editUser')) {
				throw new HTTPException('You do not have permission to do that', HTTP::FORBIDDEN);
			} elseif (! $other_user = User::getFromIdentifier($pdo, $req->post->get('user'))) {
				throw new HTTPException('User not found', HTTP::NOT_FOUND);
			} else {
				$stm = $pdo->prepare('Update `users` SET `role` = :role WHERE `identifier` = :uuid LIMIT 1;');

				if ($stm->execute([
					'role' => $req->post->get('role', false),
					'uuid' => $other_user->identifier,
				]) and $stm->rowCount() === 1) {
					Headers::status(HTTP::NO_CONTENT);
				} else {
					throw new HTTPException('Error updating user', HTTP::INTERNAL_SERVER_ERROR);
				}
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
