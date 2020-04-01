<?php
namespace Person;
use \shgysk8zer0\PHPAPI\{PDO, HTTPException, API, Headers, PostData, UUID};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use \shgysk8zer0\{User, Person};
use const \Consts\{HMAC_KEY, DEBUG};
use \Throwable;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';

try {
	$api = new API('*');

	$api->on('GET', function (API $req): void
	{
		if ($req->get->has('token')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if (! $user->loginWithToken($req->get)) {
				throw new HTTPException('Invalid or expired token', HTTP::UNAUTHORIZED);
			} elseif (! $user->can('listPerson')) {
				throw new HTTPException('Permission denied', HTTP::FORBIDDEN);
			} elseif ($req->get->has('uuid')) {
				if ($person = Person::getFromIdentifier($pdo, $req->get->get('uuid'))) {
					Headers::contentType('application/json');
					echo json_encode($person);
				} else {
					throw new HTTPException('Not found', HTTP::NOT_FOUND);
				}
			} elseif ($req->get->has('name')) {
				$stm = $pdo->prepare(sprintf('SELECT `name`, `identifier`
					FROM `Person`
					WHERE `name` LIKE :name
					ORDER BY `name` DESC
					LIMIT %d, 25;', $req->get->get('offset', 1)));
				if ($stm->execute([
					'name' => '%' . str_replace([' '], ['%'], $req->get->get('name')) . '%',
				]) and $matches = $stm->fetchAll(PDO::FETCH_CLASS)) {
					Headers::contentType('application/json');
					echo json_encode($matches);
				} else {
					throw new HTTPException('No results', HTTP::NOT_FOUND);
				}
			} else {
				$stm = $pdo->prepare(sprintf('SELECT `identifier`, `name`
					FROM `Person`
					ORDER BY `name` DESC
					LIMIT %d, 25;', $req->get->get('offset', 1)));

				if ($stm->execute() and $results = $stm->fetchAll(PDO::FETCH_CLASS)) {
					Headers::contentType('application/json');
					echo json_encode($results);
				} else {
					throw new HTTPException('Error querying `Person`s', HTTP::INTERNAL_SERVER_ERROR);
				}
			}
		} else {
			throw new HTTPException('Missing token', HTTP::BAD_REQUEST);
		}
	});

	$api->on('POST', function(API $req): void
	{
		if ($req->post->has('token', 'person') and $req->post->get('person') instanceof PostData) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if (! $user->loginWithToken($req->post)) {
				throw new HTTPException('Invalid or expired token', HTTP::UNAUTHORIZED);
			} elseif (! $user->can('createPerson')) {
				throw new HTTPException('Permission denied', HTTP::FORBIDDEN);
			} else {
				$person = new Person();
				$person->setfromUserInput($req->post->get('person'));
				$person->setIdentifier(new UUID());
				$pdo->beginTransaction();

				try {
					if (! $person->valid()) {
						throw new HTTPException('Invalid or missing data creating Person', HTTP::BAD_REQUEST);
					} elseif (! $uuid = $person->save($pdo)) {
						throw new HTTPException('Error saving new Person', HTTP::INTERNAL_SERVER_ERROR);
					} else {
						Headers::status(HTTP::CREATED);
						$pdo->commit();
						echo json_encode($person);
					}
				} catch(Throwable $e) {
					if ($pdo->inTransaction()) {
						$pdo->rollBack();
						throw $e;
					}
				}
			}
		} else {
			throw new HTTPException('Missing required data', HTTP::BAD_REQUEST);
		}
	});

	$api->on('DELETE', function (API $req): void
	{
		if ($req->get->has('token')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if (! $user->loginWithToken($req->get)) {
				throw new HTTPException('Invalid or expired token', HTTP::UNAUTHORIZED);
			} elseif (! $user->can('deletePerson')) {
				throw new HTTPException('You do not have permission', HTTP::FORBIDDEN);
			} else {
				// Need to also delete `users`, `PostalAddress`, & `ImageObject`
				throw new HTTPException('Not implmeented', HTTP::NOT_IMPLEMENTED);
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
}
