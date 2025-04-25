<?php
declare(strict_types=1);

namespace WebDev\Assets;

class ConstantsLoader {
    const CONSTANTS_FILE_PATH = __DIR__ . '/constants.json';

    private static array $data = [];

    private static function loadJson(): void {
        if (empty(self::$data)){
            $rawData = file_get_contents(self::CONSTANTS_FILE_PATH);
            self::$data = json_decode($rawData, true);

            if (self::$data === null){
                throw new \Exception("Error decoding configuration file.");
            }
        }
    }

    public static function get(string $key){
        self::loadJson();

        $keys = explode('.', $key);
        $value = self::$data;

        foreach ($keys as $keyPart){
            if (!isset($value[$keyPart])){
                throw new \Exception("Config key not found: $key");
            }
            $value = $value[$keyPart];
        }

        return $value;
    }

    public static function getAdminSchool(): object {
        self::loadJson();

        return json_decode(json_encode(self::$data['adminSchool']), false);
    }

    public static function getApiAjax(): object {
        if (empty(self::$data)){
            self::loadJson();
        }

        return json_decode(json_encode(self::$data['api']['ajaxApi']), false);
    }
}

// gay PHP :)
$consts = (object)[
    'adminSchool' => ConstantsLoader::getAdminSchool(),
    'apiAjax' => ConstantsLoader::getApiAjax()
    // more in the future will be added as the consts file will be expanded
];