<?php

namespace Helpers;

class View {
    public static function render($viewName, $data = [], $layout = 'layouts/app') {
        extract($data);
        
        // Se for partial, ou auth que não tem layout de dash, passamos layout null
        if (strpos($viewName, 'auth/') === 0 || $layout === null) {
            $viewPath = __DIR__ . '/../views/' . $viewName . '.php';
            if (file_exists($viewPath)) {
                require $viewPath;
            }
            return;
        }

        $contentView = $viewName;
        $layoutPath = __DIR__ . '/../views/' . $layout . '.php';
        
        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            die("Layout $layout not found.");
        }
    }
}
