<?php
namespace Test;
use \shgysk8zer0\{User, Person, NeedRequest};
use \shgysk8zer0\PHPAPI\{API, PDO, Headers, HTTPException};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use \Throwable;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';

try {
	$api = new API();

	$api->on('GET', function(API $req): void
	{
		Headers::contentType('application/json');
		echo json_encode($req);
	});

	$api->on('POST', function(API $req): void
	{
		Headers::contentType('application/json');
		echo json_encode($req);
	});

	$api->on('DELETE', function(API $req): void
	{
		Headers::contentType('application/json');
		echo json_encode($req);
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
