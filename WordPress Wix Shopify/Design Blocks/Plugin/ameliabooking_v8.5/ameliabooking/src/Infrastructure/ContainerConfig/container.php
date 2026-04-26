<?php

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;

// Handle the 404 API calls
$entries['notFoundHandler'] = function () {
    return function ($request, \Slim\Http\Response $response) {
        return $response->withStatus(404);
    };
};

// Handle the Method Not Allowed API calls
$entries['notAllowedHandler'] = function () {
    return function ($request, \Slim\Http\Response $response) {
        return $response->withStatus(405);
    };
};

// Handle the errors
$entries['errorHandler'] = function (Container $c) {
    return function ($request, \Slim\Http\Response $response, $exception) {
        /** @var Exception $exception */

        switch (get_class($exception)) {
            case \AmeliaBooking\Application\Common\Exceptions\AccessDeniedException::class:
                $status = \AmeliaBooking\Application\Controller\Controller::STATUS_FORBIDDEN;
                break;
            default:
                $status = \AmeliaBooking\Application\Controller\Controller::STATUS_INTERNAL_SERVER_ERROR;
        }

        $responseMessage = ['message' => $exception->getMessage()];

        if (method_exists($request, 'getParam') && $request->getParam('showAmeliaSqlExceptions')) {
            $responseMessage['exception'] = $exception->getPrevious() ? $exception->getPrevious()->getMessage() : '';
        }

        return $response->withStatus($status)
            ->withHeader('Content-Type', 'text/html')
            ->write(json_encode($responseMessage));
    };
};


// Disabled for now for easier debug
//// Handle PHP errors
//$entries['phpErrorHandler'] = function (Container $c) {
//    return function ($request, \Slim\Http\Response $response, $exception) use ($c) {
//        /** @var Exception $exception */
//
//        return $response->withStatus(500)
//            ->withHeader('Content-Type', 'text/html')
//            ->write($exception->getMessage());
//    };
//};

##########################################################################
##########################################################################
# App common
##########################################################################
##########################################################################

//
$entries['app.connection'] = function () {
    $config = new \AmeliaBooking\Infrastructure\WP\config\Database();

    $settingsService = new SettingsService(new SettingsStorage());

    $mysqliEnabled = $settingsService->getSetting('db', 'mysqliEnabled');

    $dbSettingsPort = $settingsService->getSetting('db', 'port');

    $port = !empty($dbSettingsPort) ? $dbSettingsPort : 3306;

    if (!extension_loaded('pdo_mysql') || $mysqliEnabled) {
        $config::$connection = $config::$connection ?: new \AmeliaBooking\Infrastructure\DB\MySQLi\Connection(
            $config('host'),
            $config('database'),
            $config('username'),
            $config('password'),
            $config('charset'),
            $port
        );

        return $config::$connection;
    }

    $config::$connection = $config::$connection ?: new \AmeliaBooking\Infrastructure\DB\PDO\Connection(
        $config('host'),
        $config('database'),
        $config('username'),
        $config('password'),
        $config('charset'),
        $port
    );

    return $config::$connection;
};

################
# Repositories #
################
require 'repositories.php';

############################
# Currently logged in user #
############################
require 'infrastructure.user.php';

###################
# Domain Services #
###################
require 'domain.services.php';

########################
# Application Services #
########################
require 'application.services.php';

########################
# Infrastructure Services #
########################
require 'infrastructure.services.php';

###############
# Command bus #
###############
require 'command.bus.php';

####################
# Domain event bus #
####################
require 'domain.event.bus.php';

$entries['settings'] = [
    // Slim Settings
    'determineRouteBeforeAppMiddleware' => true,
    'displayErrorDetails'               => true,
    'addContentLengthHeader'            => false, //added due to error on dev server (check the cause)
];

######################
# Request overriding #
######################
require 'request.php';

return new Container($entries);
