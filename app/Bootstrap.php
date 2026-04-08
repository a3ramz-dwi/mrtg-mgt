<?php
declare(strict_types=1);

require_once __DIR__ . '/Support/Autoload.php';

use App\Config\Env;
use App\Support\ErrorHandler;

Env::load(__DIR__ . '/../.env');

ErrorHandler::register();

// date_default_timezone_set('UTC');
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Jakarta');

session_name(Env::get('SESSION_COOKIE_NAME', 'mrtg_mgt_sid'));
$secure = Env::get('SESSION_SECURE_COOKIE', '0') === '1';
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'domain' => '',
  'secure' => $secure,
  'httponly' => true,
  'samesite' => Env::get('SESSION_SAMESITE', 'Lax'),
]);
session_start();

// Mitigate session fixation
if (empty($_SESSION['_initiated'])) {
  session_regenerate_id(true);
  $_SESSION['_initiated'] = 1;
}
