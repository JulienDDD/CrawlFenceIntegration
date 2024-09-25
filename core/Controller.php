<?php

namespace Core;

class Controller {
    protected function model($model) {
        require_once __DIR__ . '/../app/models/' . $model . '.php';
        return new $model();
    }

    protected function view($view, $data = []) {
        $path = __DIR__ . '/../app/views/' . $view . '.php';
        if (file_exists($path)) {
            extract($data);
            require_once $path;
        } else {
            echo "View cannot be found.";
        }
    }
}
