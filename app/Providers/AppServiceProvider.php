<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Make Setting available in all Blade templates
        Blade::directive('setting', function ($key) {
            return "<?php echo App\Models\Setting::get($key); ?>";
        });

        // Admin gate
        Gate::define('admin', function ($user) {
            return $user->role === 'admin';
        });

        // Share Setting class to all views
        view()->share('Setting', new Setting());
    }
}
