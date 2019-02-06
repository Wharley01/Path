<?php

session_start();
use Path\Http\MiddleWare\isProd;
use Path\Http\Request;
use Path\Http\Response;
use Path\Http\Router;
use Path\Misc\Validator;

require_once "core/kernel.php";
require_once "core/bootstrap.php";

try {
    require_once "path/Routes.php";
}catch (Throwable $e) {
    echo "Path error: " . $e->getMessage() . " trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
