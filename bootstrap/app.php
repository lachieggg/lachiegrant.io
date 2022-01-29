<?php

use LoginApp\Auth\Auth;
use LoginApp\Config;
use LoginApp\Validation\Validator;
use LoginApp\Controllers\HomeController;
use LoginApp\Controllers\AuthController;
use LoginApp\Controllers\ContactController;
use LoginApp\Controllers\BlogController;
use LoginApp\Controllers\ForumController;
use LoginApp\Middleware\OldInputMiddleware;
use LoginApp\Middleware\CsrfViewMiddleware;
use LoginApp\Middleware\ValidationErrorsMiddleware;
use Illuminate\Database\Capsule\Manager;
use Respect\Validation\Validator as RespectValidation;
use Slim\Csrf\Guard;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

use Dotenv\Dotenv;

use DI\Container;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactoryInterface;

use Slim\Factory\AppFactory;


// Start new session
session_start();

// Autoload dependencies
require __DIR__ . '/../vendor/autoload.php';

// Configure Slim Application settings
$settings = [
    'displayErrorDetails' => true
];


/**
 * Instantiate App
 */

$container = new DI\Container();
AppFactory::setContainer($container);

$app = AppFactory::create();
// Response factory for CSRF
$responseFactory = $app->getResponseFactory();

// Fetch the slim container
$container = $app->getContainer();

$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Set up the routes
require __DIR__ . '/../app/routes.php';

// Fetch database settings
$host = getenv('MYSQL_HOST');
$username = getenv('MYSQL_USER');
$driver = getenv('DATABASE_DRIVER');
$database = getenv('MYSQL_DATABASE');
$password = getenv('MYSQL_PASSWORD');


// Configure database settings
$db = [
    'driver' => $driver,
    'host' => $host,
    'database' => $database,
    'username' => $username,
    'password' => $password,
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci'
];


// Configure Eloquent
$capsule = new Manager;
$capsule->addConnection($db);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Set container parameters
setDotEnv($container);
setAuth($container);
setView($container);
setCsrf($container, $responseFactory);
setDatabase($container, $capsule);
setValidator($container);
setControllers($container);

$config = new Config($container);

$app->add(new ValidationErrorsMiddleware($container));
$app->add(new OldInputMiddleware($container));
$app->add(new CsrfViewMiddleware($container));

RespectValidation::with('\\LoginApp\\Validation\\Rules\\');

// CSRF protection for Slim 3
$app->add($container->get('csrf'));

$app->run();

/**
 * @param $container
 */
function setControllers($container) {
    $container->set('HomeController', function ($container) {
        return new HomeController($container);
    });
    $container->set('AuthController', function ($container) {
        return new AuthController($container);
    });
    $container->set('ContactController', function ($container) {
        return new ContactController($container);
    });
    $container->set('BlogController', function ($container) {
        return new BlogController($container);
    });
    $container->set('ForumController', function ($container) {
        return new ForumController($container);
    });
}

/**
 * @param $container
 */
function setDotEnv($container) {
    $container->set('dotenv', function ($container) {
        $dotenv = Dotenv::createImmutable(__DIR__ . "/../");
        $dotenv->load();
        return $dotenv;
    });
}

/**
 * @param $container
 */
function setDatabase($container, $capsule) {
    $container->set('db', function ($container) use ($capsule) {
        return $capsule;
    });
}

/**
 * @param $container
 */
function setAuth($container) {
    $container->set('auth', function ($container) {
        return new Auth($container);
    });
}

/**
 * @param $container
 */
function setView($container) {


    $container->set('view', function ($container) {
        //$view = new Twig(__DIR__ . '/../resources/views', ['cache'], false);

        $view =  Twig::create(__DIR__ . '/../resources/views', ['cache']);
    
        // $view->addExtension(new TwigExtension(
        //     $container->router,
        //     $container->request->getUri()
        // ));
    
        $view->getEnvironment()->addGlobal('auth', [
          'check' => $container->get('auth')->check(),
          'user' => $container->get('auth')->user(),
          'admin' => $container->get('auth')->admin()
        ]);

        // Add env variables to twig environment
        $view->getEnvironment()->addGlobal('sliderMode', Config::sliderMode());
        $view->getEnvironment()->addGlobal('testMode', Config::testMode());
    
        return $view;
    });
}

/**
 * @param $container
 */
function setValidator($container) {
    $container->set('validator', function ($container) {
        return new Validator;
    });
}

/**
 * @param $container
 * @param $responseFactory
 */
function setCsrf($container, $responseFactory) {
    $container->set('csrf', function ($container) use ($responseFactory) {
        $csrf = new Guard($responseFactory);
        $csrf->setPersistentTokenMode(true);
        return $csrf;
    });
}


// Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app));