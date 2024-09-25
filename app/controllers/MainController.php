<?php

namespace App\Controllers;

use Core\Controller;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use Carbon\Carbon;

class MainController extends Controller
{

    public function index()
    {
        $mainModel = $this->model('MainModel');
        $this->view('hello');
    }


}
