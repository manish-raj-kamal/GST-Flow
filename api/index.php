<?php

/**
 * Forward Vercel requests to normal index.php
 */
if (isset($_GET['uri']) && is_string($_GET['uri'])) {
    $forwardedUri = '/' . ltrim($_GET['uri'], '/');

    $query = $_GET;
    unset($query['uri']);

    $_GET = $query;
    $_SERVER['QUERY_STRING'] = http_build_query($query);
    $_SERVER['REQUEST_URI'] = $forwardedUri . ($_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
}

require __DIR__ . '/../public/index.php';
