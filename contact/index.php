<?php
namespace Test;
use \shgysk8zer0\{User, Person};
use \shgysk8zer0\PHPAPI\{API, PDO, Headers, HTTPException, UUID};
use \shgysk8zer0\PHPAPI\Abstracts\{HTTPStatusCodes as HTTP};
use \Throwable;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoloader.php';

try {
	$api = new API();

	$api->on('GET', function(API $req): void
	{
		throw new HTTPException('Not implemented', HTTP::NOT_IMPLEMENTED);
	});

	$api->on('POST', function(API $req): void
	{
		if ($req->post->has('identifier')) {
			throw new HTTPException('Not implemented', HTTP::NOT_IMPLEMENTED);
		} elseif ($req->post->has('name', 'email', 'telephone', 'message') and filter_var($req->post->get('email'), FILTER_VALIDATE_EMAIL)) {
			$stm = PDO::load()->prepare('INSERT INTO `messages` (
				`identifier`,
				`name`,
				`email`,
				`telephone`,
				`message`
			) VALUES (
				:uuid,
				:name,
				:email,
				:telephone,
				:message
			);');

			if ($stm->execute([
				'uuid'      => new UUID(),
				'name'      => $req->post->get('name'),
				'email'     => $req->post->get('email'),
				'telephone' => $req->post->get('telephone'),
				'message'   => $req->post->get('message'),
			]) and $stm->rowCount() === 1) {
				Headers::status(HTTP::CREATED);
			} else {
				throw new HTTPException('Error saving message', HTTP::INTERNAL_SERVER_ERROR);
			}
		}
	});

	$api->on('DELETE', function(API $req): void
	{
		throw new HTTPException('Not implemented', HTTP::NOT_IMPLEMENTED);
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
