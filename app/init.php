<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/InternalDatabase.php';




// Instantiate the main Router
$router = new Router();
$router->run();
