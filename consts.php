<?php
namespace Consts;
define(__NAMESPACE__ . '\DEBUG', in_array(PHP_SAPI, ['cli', 'cli-server']));
/* ======================== Site Specific Config ===================== */
const SITE_NAME = 'KRV COVID-19 Healthy Shopping Resource';
const SITE_URL  = 'https://needs.kernvalley.us';
/* =================================================================== */
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
const EMAIL_CREDS_FILE  = DATA_DIR . 'email.json';
const TEMPLATES_DIR     = BASE . 'templates' . DIRECTORY_SEPARATOR;
const TIMEZONE          = 'America/Los_Angeles';
const EXCEPTION_HANDLER = '\Functions\exception_handler';
const ERROR_HANDLER     = '\Functions\error_handler';
const AUTOLOADER        = 'spl_autoload';
const AUTOLOAD_EXTS     = [
	'.php',
];

const VOLUNTEER_ROLES = [
	1,
	2,
	5,
	6,
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

define(__NAMESPACE__ . '\HAS_EMAIL_CREDS', file_exists(EMAIL_CREDS_FILE));

define(__NAMESPACE__ . '\HMAC_KEY', file_get_contents(HMAC_FILE));

const BASE_URI = HOST . BASE_PATH;
