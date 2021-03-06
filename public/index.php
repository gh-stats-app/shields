<?php
error_reporting(E_PARSE);

use Pecee\SimpleRouter\SimpleRouter;

require_once '../vendor/autoload.php';
include '../config.php';

SimpleRouter::get('/badge', function () use ($db_host, $db_user, $db_password, $db_table) {
    $request = SimpleRouter::request();
    $action = $request->getInputHandler()->get('action', '');

    if (!str_contains($action, '/')) {
        header('HTTP/1.1 400 Bad request');
        http_response_code(400);
        die();
    }

    $split = preg_split('/@/', $action, 2);
    $action = $split[0];
    $tag = $split[1];

    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_table);
    if ($mysqli->connect_error) die("Connection to database failed.");

    if ($tag) {
        $stmt = $mysqli->prepare('SELECT COUNT(id) FROM `stats` WHERE action LIKE ? and tag LIKE ?');
        $stmt->bind_param("ss", $action, $tag);
    } else {
        $stmt = $mysqli->prepare('SELECT COUNT(id) FROM `stats` WHERE action LIKE ?');
        $stmt->bind_param("s", $action);
    }

    $stmt->execute();

    $label = 'Used ';
    $message = sprintf('%d times', $stmt->get_result()->fetch_column(0));
    $color = $request->getInputHandler()->get('color', 'brightgreen');
    $style = $request->getInputHandler()->get('style', '');

    $url = sprintf("https://img.shields.io/badge/%s-%s-%s?style=%s", rawurlencode($label), rawurlencode($message), rawurlencode($color), rawurldecode($style));

    $opts = ['http' => ['method' => "GET", 'header' => "Accept-language: en\r\n" . "User-Agent: gh-stats.app\r\n"]];
    $context = stream_context_create($opts);

    header('Content-Type: image/svg+xml');
    header('Cache-Control: max-age=60, public');

    return file_get_contents($url, false, $context);
});

SimpleRouter::start();