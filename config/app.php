<?php
// config/app.php

if (!function_exists('app_base_path')) {
    function app_base_path(): string
    {
        $configured = $_ENV['APP_BASE_PATH'] ?? getenv('APP_BASE_PATH') ?: null;
        if ($configured !== null) {
            $configured = '/' . trim((string)$configured, '/');
            return $configured === '/' ? '' : $configured;
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        foreach (['/view/', '/assets/'] as $marker) {
            $pos = strpos($scriptName, $marker);
            if ($pos !== false) {
                return rtrim(substr($scriptName, 0, $pos), '/');
            }
        }

        $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        return $dir === '/' || $dir === '.' ? '' : $dir;
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        return app_base_path() . $path;
    }
}

if (!function_exists('project_path')) {
    function project_path(string $path = ''): string
    {
        return dirname(__DIR__) . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }
}
