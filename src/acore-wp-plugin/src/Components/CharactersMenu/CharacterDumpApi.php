<?php

namespace ACore\Components\CharactersMenu;

use ACore\Manager\ACoreServices;
use ACore\Manager\Opts;

add_action('rest_api_init', function () {
    register_rest_route(ACORE_SLUG . '/v1', 'pdump/(?P<guid>\d+)', [
        'methods'             => 'GET',
        'callback'            => __NAMESPACE__ . '\handlePdump',
        'permission_callback' => '__return_true',
    ]);
});

function handlePdump(\WP_REST_Request $request): void
{
    $guid  = (int) $request->get_param('guid');
    $accId = ACoreServices::I()->getAcoreAccountId();

    if (Opts::I()->acore_pdump_enabled != '1') {
        wp_send_json_error(['message' => 'PDUMP export is not enabled on this server.'], 403);
        exit;
    }

    if (!$accId || $guid < 1) {
        wp_send_json_error(['message' => 'Forbidden.'], 403);
        exit;
    }

    $conn = ACoreServices::I()->getCharacterEm()->getConnection();
    $row  = $conn->executeQuery(
        "SELECT `name` FROM `characters`
         WHERE `guid` = ? AND `account` = ? AND `deleteDate` IS NULL
         LIMIT 1",
        [$guid, $accId]
    )->fetchAssociative();

    if (!$row) {
        wp_send_json_error(['message' => 'Character not found.'], 403);
        exit;
    }

    $charName = $row['name'];

    $phpWarnings = [];
    set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use (&$phpWarnings): bool {
        $phpWarnings[] = "[{$errno}] {$errstr} in {$errfile}:{$errline}";
        return true;
    });

    ob_start();
    $dump = null;
    try {
        $writer = new CharacterDumpWriter($conn);
        $dump   = $writer->getDump($guid);
    } catch (\Throwable $e) {
        $phpWarnings[] = get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }
    ob_end_clean();
    restore_error_handler();

    if (!empty($phpWarnings)) {
        wp_send_json_error([
            'message' => 'An internal error occurred while generating the dump.',
            'detail'  => implode("\n", $phpWarnings),
        ], 500);
        exit;
    }

    if ($dump === null) {
        wp_send_json_error(['message' => 'Character is deleted and cannot be exported.'], 400);
        exit;
    }

    $filename = strtoupper($charName) . '_' . date('Ymd_His') . '.dump';

    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($dump));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    echo $dump;
    exit;
}
