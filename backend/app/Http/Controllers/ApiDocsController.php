<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ApiDocsController extends Controller
{
    /**
     * Form đăng nhập để xem / thử API docs.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('api-docs');
        }

        return view('api-docs.login');
    }

    /**
     * Đăng nhập session web + cấp token Sanctum để thử API.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Vui lòng nhập email hoặc số điện thoại.',
        ]);

        $login = trim($credentials['login']);
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

        $user = User::query()
            ->when(
                $isEmail,
                fn ($q) => $q->where('email', $login),
                fn ($q) => $q->where('phone', $login),
            )
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Email/số điện thoại hoặc mật khẩu không đúng.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'login' => ['Tài khoản đã bị khóa hoặc không hoạt động.'],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Token dùng cho Try it out (Bearer)
        $user->tokens()->where('name', 'api-docs-token')->delete();
        $token = $user->createToken('api-docs-token')->plainTextToken;
        $request->session()->put('api_docs_token', $token);

        return redirect()->intended(route('api-docs'));
    }

    /**
     * Đăng xuất khỏi API docs.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            $user->tokens()->where('name', 'api-docs-token')->delete();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('api-docs.login');
    }

    /**
     * Hiển thị danh sách API + giao diện thử request.
     */
    public function index(Request $request): View
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

                preg_match_all('/\{([^}]+)\}/', $route->uri(), $paramMatches);
                $pathParams = $paramMatches[1] ?? [];

                return collect($route->methods())
                    ->reject(fn (string $method) => $method === 'HEAD')
                    ->map(fn (string $method) => [
                        'method' => $method,
                        'uri' => '/'.$route->uri(),
                        'name' => $route->getName(),
                        'middleware' => $middleware,
                        'action' => is_string($controller) ? $controller : null,
                        'auth_required' => $authRequired,
                        'path_params' => $pathParams,
                        'has_body' => in_array($method, ['POST', 'PUT', 'PATCH'], true),
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
            'user' => $request->user(),
            'apiToken' => $request->session()->get('api_docs_token', ''),
        ]);
    }
}
