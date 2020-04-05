<?php
namespace Roles;

use \shgysk8zer0\PHPAPI\{PDO, API, Headers, HTTPException};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use \shgysk8zer0\{Role};

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';

try {
	$api = new API('*');

	$api->on('GET', function(API $req): void
	{
		if ($roles = Role::fetchAll(PDO::load())) {
			Headers::contentType('application/json');
			echo json_encode($roles);
		} else {
			throw new HTTPException('Error fetching roles', HTTP::INTERNAL_SERVER_ERROR);
		}
	});

	$api();
} catch (HTTPException $e) {
	Headers::status($e->getCode());
	Headers::contentType('application/json');
	echo json_encode($e);
}
