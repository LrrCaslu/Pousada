<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// In a fresh extraction Composer must install vendor/autoload.php first.
// Do not expose full server paths or a fatal stack trace to website visitors.
if (!is_file(__DIR__ . '/../vendor/autoload.php')) {
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalação pendente — Costa Serena</title><style>body{font:16px/1.7 system-ui,Arial;background:#f5f6f2;color:#284135;margin:0;display:grid;min-height:100vh;place-items:center}.card{max-width:670px;margin:24px;padding:32px;background:#fff;border-radius:18px;box-shadow:0 14px 50px #254a401a}h1{margin:0 0 10px;font-size:1.65rem}code{background:#edf4ed;color:#245842;padding:4px 7px;border-radius:5px}li{margin-bottom:7px}small{color:#698477}</style></head><body><div class="card"><h1>Instalação do Laravel ainda não concluída</h1><p>A pasta <code>vendor</code> não foi encontrada. Isso acontece quando o projeto é extraído sem instalar suas dependências.</p><ol><li>Abra a pasta do projeto (onde está o arquivo <code>artisan</code>).</li><li>Execute <code>INSTALAR-DEPENDENCIAS.bat</code> ou, no terminal, <code>composer install</code>.</li><li>Depois acesse esta página novamente.</li></ol><p><small>Não é necessário apagar o banco de dados, suas reservas ou seu arquivo .env.</small></p></div></body></html>';
    exit;
}

if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__ . '/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->handleRequest(Request::capture());
