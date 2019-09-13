<?php
namespace Path\Core\Http;
use Throwable;

require_once "core/kernel.php";
require_once "vendor/autoload.php";

try {

    $__routes = new Router();
    $__routes->get("SSE/@controller/@action", 'Path\Plugins\SSEController\SSEServer->watch');
    require_once "path/Routes.php";
} catch (Throwable $e) {
    echo "<pre>";
    echo "Path error: " . $e->getMessage() . " trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
