<?php
namespace Errors;

use \shgysk8zer0\PHPAPI\{PDO, HTTPException, API, Headers};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use \shgysk8zer0\{User};
use const \Consts\{HMAC_KEY, DEBUG};
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
			} elseif (! $user->can('debug')) {
				throw new HTTPException('You do not have permission for this', HTTP::FORBIDDEN);
			} elseif ($req->get->has('id')) {
				$stm = $pdo->prepare('SELECT `id`,
					`type`,
					`message`,
					`file`,
					`line`,
					`code`,
					DATE_FORMAT(`datetime`, "%Y-%m-%dT%TZ") AS `datetime`
				FROM `ServerErrors`
				WHERE `id` = :id
				LIMIT 1;');

				if ($stm->execute(['id' => $req->get->get('id')]) and $result = $stm->fetchObject()) {
					Headers::contentType('application/json');
					echo json_encode($result);
				} else {
					throw new HTTPException('Error not found', HTTP::NOT_FOUND);
				}
			} else {
				$stm = $pdo->prepare('SELECT `id`,
					`type`,
					`message`,
					`file`,
					`line`,
					`code`,
					DATE_FORMAT(`datetime`, "%Y-%m-%dT%TZ") AS `datetime`,
					`remoteIP`,
					`url`
				FROM `ServerErrors`
				ORDER BY `datetime` DESC;');

				if ($stm->execute() and $results = $stm->fetchAll(PDO::FETCH_CLASS)) {
					Headers::contentType('application/json');
					echo json_encode($results);
				} else {
					throw new HTTPException('No errors found', HTTP::NOT_FOUND);
				}
			}
		} else {
			throw new HTTPException('Missing token', HTTP::BAD_REQUEST);
		}
	});

	$api->on('DELETE', function (API $req): void
	{
		if ($req->get->has('token')) {
			$pdo = PDO::load();
			$user = new User($pdo, HMAC_KEY);

			if (! $user->loginWithToken($req->get)) {
				throw new HTTPException('Invalid or expired token', HTTP::UNAUTHORIZED);
			} elseif (! $user->can('debug')) {
				throw new HTTPException('You do not have permission for this', HTTP::FORBIDDEN);
			} elseif ($req->get->has('id')) {
				$stm = $pdo->prepare('DELETE FROM `ServerErrors` WHERE `id` = :id LIMIT 1;');

				if ($stm->execute(['id' => $req->get->get('id')]) and $stm->rowCount() === 1) {
					Headers::status(HTTP::NO_CONTENT);
				} else {
					throw new HTTPException('Error not found', HTTP::NOT_FOUND);
				}
			} else {
				$stm = $pdo->prepare('TRUNCATE `ServerErrors`;');

				if ($stm->execute()) {
					Headers::status(HTTP::NO_CONTENT);
				} else {
					throw new HTTPException('Error deleting errors', HTTP::INTERNAL_SERVER_ERROR);
				}
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
