<?php

use Path\Http\MiddleWare\isProd;
use Path\Http\Request;
use Path\Http\Response;
use Path\Http\Router;
use Path\Misc\Validator;

require_once "core/kernel.php";

import(
    "Core/Classes/Http/Router",
    "Core/Classes/Http/Response",
    "Core/Classes/Misc/Validator",
    "Core/Classes/Cookies",
    "Core/Classes/Sessions"
);



try {
    require_once "path/Routes.php";
}catch (Throwable $e) {
    echo "Path error: " . $e->getMessage() . " trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
