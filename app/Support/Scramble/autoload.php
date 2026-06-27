<?php

spl_autoload_register(static function (string $class): bool {
    if ($class !== 'Dedoc\\Scramble\\Support\\ResponseExtractor\\ModelInfo') {
        return false;
    }

    require __DIR__.'/Overrides/Dedoc/Scramble/Support/ResponseExtractor/ModelInfo.php';

    return true;
}, prepend: true);
