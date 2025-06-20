<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    // 'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'), // KIRIM PERMISSIONS
                ] : null,
            ],
            'ziggy' => function () use ($request) {
                $ziggy = new Ziggy(null, $request->url());

                // Jika user adalah admin, berikan semua rute.
                // Jika tidak, berikan hanya rute dari grup 'public'.
                if (! $request->user() || !$request->user()->hasRole('admin|user')) {
                    $ziggy->filter(config('ziggy.groups.public'));
                }

                return $ziggy->toArray();
            },
            'flash' => [ // Pastikan flash message di-handle
                'message' => fn () => $request->session()->get('message'),
                'type' => fn () => $request->session()->get('type'),
            ],
            'app_settings' => function () {
                return Cache::rememberForever('app_settings', function () {
                    return Setting::all()->pluck('value', 'key');
                });
            },
        ]);
    }
}
