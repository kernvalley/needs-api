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
	Files::setAllowedTypes('image/jpeg', 'image/png');

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
