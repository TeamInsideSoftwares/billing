<?php

$publicIndex = __DIR__.'/public/index.php';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$basePath = dirname($scriptName);
$basePath = $basePath === '\\' ? '/' : $basePath;
if ($basePath !== '/' && $basePath !== '') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $parsedPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
    if (str_starts_with($parsedPath, $basePath.'/')) {
        $newPath = substr($parsedPath, strlen($basePath));
        if ($newPath === '') {
            $newPath = '/';
        }
        $query = parse_url($requestUri, PHP_URL_QUERY);
        $newRequestUri = $newPath.($query ? '?'.$query : '');
        $_SERVER['REQUEST_URI'] = $newRequestUri;
        $_SERVER['QUERY_STRING'] = $query ?? '';
    } elseif ($parsedPath === $basePath) {
        $query = parse_url($requestUri, PHP_URL_QUERY);
        $_SERVER['REQUEST_URI'] = '/'.($query ? '?'.$query : '');
        $_SERVER['QUERY_STRING'] = $query ?? '';
    }
}

$_SERVER['SCRIPT_FILENAME'] = $publicIndex;
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
$_SERVER['PHP_SELF'] = '/public/index.php';

require $publicIndex;
