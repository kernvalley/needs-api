<?php
namespace Consts;

const DEBUG             = true;
const BASE              = __DIR__ . DIRECTORY_SEPARATOR;
const DATA_DIR          = BASE . 'data' . DIRECTORY_SEPARATOR;
const LOGS_DIR          = BASE . 'logs' . DIRECTORY_SEPARATOR;
const UPLOADS_DIR       = BASE . 'uploads' . DIRECTORY_SEPARATOR;
const CREDS_FILE        = DATA_DIR . 'creds.json';
const HMAC_FILE         = DATA_DIR . 'hmac.key';
const GITHUB_WEBHOOK    = DATA_DIR . 'github.json';
const SQL_FILE          = DATA_DIR . 'db.sql';
const ERROR_LOG         = LOGS_DIR . 'errors.log';
const OPEN_WEATHER_MAP  = DATA_DIR . 'OpenWeatherMap.key';
const TIMEZONE          = 'America/Los_Angeles';
const EXCEPTION_HANDLER = '\Functions\exception_handler';
const ERROR_HANDLER     = '\Functions\error_handler';
const AUTOLOADER        = 'spl_autoload';
const AUTOLOAD_EXTS     = [
	'.php',
];
const INCLUDE_PATH      = [
	BASE . 'classes' . DIRECTORY_SEPARATOR,
];

const CSP_ALLOWED_HEADERS = [
	'Accept',
	'Content-Type',
	'User-Agent',
	'Upgrade-Insecure-Requests',
];

const TOKEN_EXPIRES = [
	'value' => 1,
	'units' => 'month',
];

const IMAGE_TYPES = [
	'image/jpeg',
	'image/png',
];

define(__NAMESPACE__ . '\HOST', sprintf('%s://%s',
	(array_key_exists('HTTPS', $_SERVER) and ! empty($_SERVER['HTTPS'])) ? 'https' : 'http',
	$_SERVER['HTTP_HOST'] ?? 'localhost'
));

define(__NAMESPACE__ . '\BASE_PATH',
	rtrim(
		(DIRECTORY_SEPARATOR === '/')
			? '/' . trim(str_replace($_SERVER['DOCUMENT_ROOT'], null, __DIR__), '/')
			: '/' . trim(str_replace(
				DIRECTORY_SEPARATOR,
				'/',
				str_replace($_SERVER['DOCUMENT_ROOT'], null, __DIR__)
			), '/')
	,'/') . '/'
);

define(__NAMESPACE__ . '\HMAC_KEY', file_get_contents(HMAC_FILE));

const BASE_URI = HOST . BASE_PATH;
