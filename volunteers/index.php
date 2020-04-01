<?php
namespace Volunteers;
use \shgysk8zer0\{User};
use \shgysk8zer0\PHPAPI\{API, PDO, Headers, HTTPException};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use \Throwable;
use const \Consts\{VOLUNTEER_ROLES};

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';

try {
	$api = new API();

	$api->on('GET', function(API $req): void
	{
		$pdo = PDO::load();

		if ($req->get->has('uuid')) {
			$stm = $pdo->prepare('SELECT `Person`.`name` AS `name`,
				`Person`.`identifier` AS `identifier`,
				`ImageObject`.`url` AS `image`,
				`roles`.`name` AS `role`
			FROM `users`
			LEFT OUTER JOIN `Person` ON `users`.`person` = `Person`.`identifier`
			LEFT OUTER JOIN `ImageObject` ON `Person`.`image` = `ImageObject`.`identifier`
			LEFT OUTER JOIN `roles` ON `users`.`role` = `roles`.`id`
			WHERE `Person`.`identifier` = :uuid
			AND `users`.`role` IN (' . join(', ', VOLUNTEER_ROLES). ')
			LIMIT 1;');

			if ($stm->execute(['uuid' => $req->get->get('uuid')]) and $person = $stm->fetchObject()) {
				Headers::contentType('application/json');
				echo json_encode($person);
			} else {
				throw new HTTPException('Volunteer not found', HTTP::NOT_FOUND);
			}
		} else {
			$stm = $pdo->prepare('SELECT `Person`.`name` AS `name`,
				`Person`.`identifier` AS `identifier`,
				`ImageObject`.`url` AS `image`,
				`roles`.`name` AS `role`
			FROM `users`
			LEFT OUTER JOIN `Person` ON `users`.`person` = `Person`.`identifier`
			LEFT OUTER JOIN `ImageObject` ON `Person`.`image` = `ImageObject`.`identifier`
			LEFT OUTER JOIN `roles` ON `users`.`role` = `roles`.`id`
			WHERE `users`.`role` IN (' . join(', ', VOLUNTEER_ROLES). ');');

			if ($stm->execute() and $people = $stm->fetchAll(PDO::FETCH_CLASS)) {
				Headers::contentType('application/json');
				echo json_encode($people);
			} else {
				throw new HTTPException('Volunteer not found', HTTP::NOT_FOUND);
			}
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
