<?php
declare(strict_types=1);

namespace Nexus\Core;

use Closure;

final class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];
    private array $paramRoutes = [
        'GET' => [],
        'POST' => [],
    ];

    public function __construct(private Config $config)
    {
    }

    public function get(string $path, $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, $handler): void
    {
        $norm = $this->normalize($path);
        if (str_contains($norm, '{')) {
            [$regex, $params] = $this->compilePattern($norm);
            $this->paramRoutes[$method][] = [
                'regex' => $regex,
                'params' => $params,
                'handler' => $handler,
            ];
        } else {
            $this->routes[$method][$norm] = $handler;
        }
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = $this->normalize($path);
        $handler = $this->routes[$method][$path] ?? null;

        $params = [];
        if ($handler === null) {
            // Try param routes
            foreach ($this->paramRoutes[$method] as $entry) {
                if (preg_match($entry['regex'], $path, $m)) {
                    $handler = $entry['handler'];
                    // Collect params in order
                        // Collect params in order and cast numeric strings to int (strict_types)
                        foreach ($entry['params'] as $p) {
                            $val = $m[$p] ?? null;
                            if (is_string($val) && ctype_digit($val)) {
                                $val = (int)$val;
                            }
                            $params[] = $val;
                        }
                    break;
                }
            }
        }

        if ($handler === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if ($handler instanceof Closure) {
            echo $handler(...$params);
            return;
        }

        // Controller@method or Controller:method
        if (is_string($handler)) {
            [$controller, $mname] = preg_split('/[@:]/', $handler);
            $fqcn = 'Nexus\\Controllers\\' . $controller;
            if (!class_exists($fqcn)) {
                http_response_code(500);
                echo 'Controller not found';
                return;
            }
            $instance = new $fqcn($this->config);
            if (!method_exists($instance, $mname)) {
                http_response_code(500);
                echo 'Method not found';
                return;
            }
            // Respect method arity
            $ref = new \ReflectionMethod($instance, $mname);
            $argCount = $ref->getNumberOfParameters();
            $args = array_slice($params, 0, $argCount);
            echo $instance->$mname(...$args);
            return;
        }

        http_response_code(500);
        echo 'Invalid route handler';
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    private function compilePattern(string $path): array
    {
        // Convert /foo/{id}/bar to regex with named capture groups
        $params = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) use (&$params) {
            $params[] = $m[1];
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $path);
        $regex = '#^' . $regex . '$#';
        return [$regex, $params];
    }
}
