<?php
declare(strict_types=1);

function auth_check(array $cfg): void {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!str_starts_with($header, 'Bearer ')) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
    $token = substr($header, 7);
    if (!hash_equals($cfg['SHARE_TOKEN'], $token)) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
}
