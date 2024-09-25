<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../../vendor/autoload.php';
use Dotenv\Dotenv;

use App\Model\CrawlFenceAPI;

$api_key = "crawlfence_74b83ae96ae99132121391cd9d51d4e1";
$crawlFence = new CrawlFenceAPI($api_key);

// Gérer la requête
try {
    $crawlFence->handleRequest();


} catch (Exception $e) {
    // Gérer les exceptions
    echo 'Erreur : ' . $e->getMessage();
}


$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Router {
    private $dispatcher;

    public function __construct() {
        $this->dispatcher = simpleDispatcher(function(RouteCollector $r) {
     
            
            $r->addRoute('GET', '/', ['App\\Controllers\\MainController', 'index']);
        

        });
        
    }

    public function run() {
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
   
        // Remove the base path from URI for correct routing
        $base_path = '/CrawlFenceIntegration/';  // Adjust the base path as per your setup
        $uri = rtrim(substr($uri, strlen($base_path)), '/') . '/';

        if (preg_match('/\.(?:css|js|png|jpg|jpeg|gif|ico|woff|woff2|ttf|svg|eot|otf)$/', $uri)) {
            return false; // Let the server handle the request as a static file
        }
    
        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);
    
    
        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                // Send a 404 HTTP status code
                http_response_code(404);
        
                // Path to the 404 error page
                           $filePath = __DIR__ . '/../../views/404/404.html';
                // Check if the file exists and is readable
                if (file_exists($filePath) && is_readable($filePath)) {
                    // Display the HTML content of the 404 error page
                    readfile($filePath);
                } else {
                    echo "Debug: 404 Not Found - Custom error page missing.";
                }
                break;
        
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                http_response_code(405);
                 $filePath = __DIR__ . '/../../views/404/405.html';
                // Check if the file exists and is readable
                if (file_exists($filePath) && is_readable($filePath)) {
                    // Display the HTML content of the 404 error page
                    readfile($filePath);
                } else {
                    echo "Debug: 404 Not Found - Custom error page missing.";
                }
                break;
        
            case FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $params = $routeInfo[2];
                if (class_exists($handler[0]) && method_exists($handler[0], $handler[1])) {
                    (new $handler[0])->{$handler[1]}($params);
                } else {
                    http_response_code(500);
                    
                }
                break;
        }
    }
    
}

