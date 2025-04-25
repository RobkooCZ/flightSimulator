<?php
declare(strict_types=1);

namespace WebDev\API;

use WebDev\Assets\ConstantsLoader;

class ApiResponse {
    public static function success(
        mixed $data = null, 
        string $message = ''
    ): never {
        $apiAjax = ConstantsLoader::getApiAjax();

        echo json_encode([
            $apiAjax->success->success => true,
            $apiAjax->success->message => $message,
            $apiAjax->success->data => $data
        ]);
        exit;
    }

    public static function failure(
        string $message = '',
        int $code = 400,
        ?string $errors = null
    ): never {
        $apiAjax = ConstantsLoader::getApiAjax();

        http_response_code($code);

        echo json_encode([
            $apiAjax->failure->success => false,
            $apiAjax->failure->message => $message,
            $apiAjax->failure->error  => $errors
        ]);
        exit;
    }
}