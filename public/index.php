<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Bootstrap.php';

\App\Support\SecurityHeaders::apply();

use App\Http\Router;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevicesController;
use App\Http\Controllers\InterfacesController;
use App\Http\Controllers\MrtgController;

$router = new Router();

$router->get('/login', [new AuthController(), 'showLogin']);
$router->post('/login', [new AuthController(), 'login']);
$router->post('/logout', [new AuthController(), 'logout']);

$router->get('/', [new DashboardController(), 'index']);

$router->get('/devices', [new DevicesController(), 'index']);
$router->get('/devices/create', [new DevicesController(), 'create']);
$router->post('/devices/create', [new DevicesController(), 'store']);
$router->get('/devices/edit', [new DevicesController(), 'edit']);            // ?id=
$router->post('/devices/edit', [new DevicesController(), 'update']);         // ?id=
$router->post('/devices/delete', [new DevicesController(), 'delete']);       // ?id=
$router->post('/devices/ping', [new DevicesController(), 'ping']);           // ?id=
$router->post('/devices/snmp-test', [new DevicesController(), 'snmpTest']);  // ?id=
$router->post('/devices/snmp-check', [new DevicesController(), 'snmpCheck']);        // ?id=
$router->post('/devices/snmp-check-all', [new DevicesController(), 'snmpCheckAll']); // (no params)

$router->get('/interfaces', [new InterfacesController(), 'index']);                 // ?device_id=
$router->get('/interfaces/discovery', [new InterfacesController(), 'discovery']);   // ?device_id=
$router->post('/interfaces/discovery-add', [new InterfacesController(), 'discoveryAdd']); // ?device_id=
$router->get('/interfaces/view', [new InterfacesController(), 'view']);             // ?id=
$router->post('/interfaces/toggle-mrtg', [new \App\Http\Controllers\InterfacesController(), 'toggleMrtg']); // ?id=
$router->post('/interfaces/bulk-mrtg', [new \App\Http\Controllers\InterfacesController(), 'bulkMrtg']); // ?device_id=
$router->post('/interfaces/bulk-mrtg-filter', [new \App\Http\Controllers\InterfacesController(), 'bulkMrtgByFilter']); // ?device_id=

$router->post('/mrtg/build-device', [new MrtgController(), 'buildDevice']);         // ?device_id=
$router->get('/mrtg/image', [new MrtgController(), 'image']);                       // ?file=
$router->get('/mrtg/debug', [new MrtgController(), 'debug']);                        // ?device_id=

$router->get('/snmp-profiles', [new \App\Http\Controllers\SnmpProfilesController(), 'index']);
$router->get('/snmp-profiles/create', [new \App\Http\Controllers\SnmpProfilesController(), 'create']);
$router->post('/snmp-profiles/create', [new \App\Http\Controllers\SnmpProfilesController(), 'store']);
$router->get('/snmp-profiles/edit', [new \App\Http\Controllers\SnmpProfilesController(), 'edit']);      // ?id=
$router->post('/snmp-profiles/edit', [new \App\Http\Controllers\SnmpProfilesController(), 'update']);   // ?id=
$router->post('/snmp-profiles/delete', [new \App\Http\Controllers\SnmpProfilesController(), 'delete']); // ?id=

$router->get('/data-centers', [new \App\Http\Controllers\DataCentersController(), 'index']);
$router->get('/data-centers/create', [new \App\Http\Controllers\DataCentersController(), 'create']);
$router->post('/data-centers/create', [new \App\Http\Controllers\DataCentersController(), 'store']);
$router->get('/data-centers/edit', [new \App\Http\Controllers\DataCentersController(), 'edit']);      // ?id=
$router->post('/data-centers/edit', [new \App\Http\Controllers\DataCentersController(), 'update']);   // ?id=
$router->post('/data-centers/delete', [new \App\Http\Controllers\DataCentersController(), 'delete']); // ?id=

$router->get('/diagnostics', [new \App\Http\Controllers\DiagnosticsController(), 'index']);
$router->post('/diagnostics/ping', [new \App\Http\Controllers\DiagnosticsController(), 'ping']);          // ?device_id=
$router->post('/diagnostics/traceroute', [new \App\Http\Controllers\DiagnosticsController(), 'traceroute']); // ?device_id=
$router->post('/diagnostics/snmp-test', [new \App\Http\Controllers\DiagnosticsController(), 'snmpTest']); // ?device_id=
$router->post('/diagnostics/snmp-walk', [new \App\Http\Controllers\DiagnosticsController(), 'snmpWalk']); // ?device_id=

$router->get('/event-timeline', [new \App\Http\Controllers\EventTimelineController(), 'index']);
$router->get('/settings', [new \App\Http\Controllers\ComingSoonController(), 'settings']);
$router->get('/users', [new \App\Http\Controllers\ComingSoonController(), 'users']);
$router->get('/roles', [new \App\Http\Controllers\ComingSoonController(), 'roles']);

$router->get('/alerts', [new \App\Http\Controllers\AlertsController(), 'index']);
$router->post('/alerts/recheck', [new \App\Http\Controllers\AlertsController(), 'recheck']);           // ids[]
$router->post('/alerts/recheck-fail', [new \App\Http\Controllers\AlertsController(), 'recheckFail']); // all fail
$router->post('/alerts/ack', [new \App\Http\Controllers\AlertsController(), 'ack']);                   // ids[] + note
$router->post('/alerts/unack', [new \App\Http\Controllers\AlertsController(), 'unack']);               // ids[]

$router->get('/history', [new \App\Http\Controllers\HistoryController(), 'index']);

$router->dispatch();
