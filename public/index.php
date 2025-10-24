<?php
declare(strict_types=1);

$bootstrap = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

// Strict schema gate: in non-local environments, require schema v2 to be present
try {
	$config = $bootstrap['config'] ?? null;
	$env = $config ? (string)$config->get('app.env', 'local') : 'local';
	$isV2 = isset($GLOBALS['schema_v2']) ? (bool)$GLOBALS['schema_v2'] : false;
	if ($env !== 'local' && !$isV2) {
		http_response_code(503);
		header('Content-Type: text/html; charset=utf-8');
		echo '<!doctype html><html><head><meta charset="utf-8"><title>Service Unavailable</title></head><body>';
		echo '<h1>Service Unavailable (Schema Upgrade Required)</h1>';
		echo '<p>The database schema is not on v2. Please apply <code>docs/nexus.schema.v2.sql</code> and set <code>system_settings.schema_version = 2</code>.</p>';
		echo '<p>This strict check only applies outside local development.</p>';
		echo '</body></html>';
		return;
	}
} catch (\Throwable $e) {
	// If anything goes wrong, do not block local; default to continue
}

// Normalize the request URI to be relative to the public/ directory so routing works under subfolders like /nexus/public
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Extract only the path component
$path = parse_url($requestUri, PHP_URL_PATH) ?? '/';

// Determine the base path (directory of the front controller), e.g., "/nexus/public"
$scriptDir = str_replace('\\', '/', rtrim(dirname($scriptName), '/\\'));
if ($scriptDir !== '' && $scriptDir !== '/' && strncmp($path, $scriptDir, strlen($scriptDir)) === 0) {
	$path = substr($path, strlen($scriptDir));
	if ($path === '' || $path[0] !== '/') {
		$path = '/' . $path;
	}
}

// If someone visits /.../index.php directly, treat it as the site root
if ($path === '/index.php') {
	$path = '/';
}

$router = $bootstrap['router'];
$router->dispatch($method, $path);
