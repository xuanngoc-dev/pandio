<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class ApiDocsController extends Controller
{
    /**
     * Hiển thị danh sách tất cả API routes (tương tự Swagger UI).
     */
    public function index(): View
    {
        $routes = collect(Route::getRoutes())
            ->filter(fn (RouteInstance $route) => str_starts_with($route->uri(), 'api/'))
            ->flatMap(function (RouteInstance $route) {
                $action = $route->getAction();
                $controller = $action['controller'] ?? null;
                $middleware = collect($route->gatherMiddleware())
                    ->reject(fn (string $m) => $m === 'api')
                    ->values()
                    ->all();

                $authRequired = collect($middleware)->contains(
                    fn (string $m) => str_starts_with($m, 'auth')
                );

                return collect($route->methods())
                    ->reject(fn (string $method) => $method === 'HEAD')
                    ->map(fn (string $method) => [
                        'method' => $method,
                        'uri' => '/'.$route->uri(),
                        'name' => $route->getName(),
                        'middleware' => $middleware,
                        'action' => is_string($controller) ? $controller : null,
                        'auth_required' => $authRequired,
                    ]);
            })
            ->sortBy(['uri', 'method'])
            ->values();

        $grouped = $routes->groupBy(function (array $route) {
            $path = trim(str_replace('api/', '', $route['uri']), '/');

            return explode('/', $path)[0] ?: 'root';
        })->sortKeys();

        return view('api-docs.index', [
            'routes' => $routes,
            'grouped' => $grouped,
            'total' => $routes->count(),
        ]);
    }
}
