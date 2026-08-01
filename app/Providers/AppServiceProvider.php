<?php

declare(strict_types=1);

namespace App\Providers;

use App\Mail\Transport\BrevoApiTransport;
use App\Models\CriticalMetric;
use App\Models\Owner;
use App\Policies\CriticalMetricPolicy;
use App\Policies\OwnerPolicy;
use App\Contracts\SheetReader;
use App\Services\DashboardMetricsService;
use App\Services\Sheets\LinkSheetReader;
use App\Services\Sheets\ServiceAccountSheetReader;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DashboardMetricsService::class);

        // Driver pembaca sheet dipilih melalui config('dbena.sheets.driver').
        $this->app->bind(SheetReader::class, fn () => match (config('dbena.sheets.driver')) {
            'service' => new ServiceAccountSheetReader,
            default => new LinkSheetReader,
        });
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::policy(CriticalMetric::class, CriticalMetricPolicy::class);
        Gate::policy(Owner::class, OwnerPolicy::class);

        // Pemacu mel melalui HTTPS. Port SMTP disekat di server ini, jadi
        // port 443 satu-satunya jalan keluar untuk emel.
        Mail::extend('brevo-api', fn (array $config) => new BrevoApiTransport(
            (string) ($config['key'] ?? ''),
            (int) ($config['timeout'] ?? 15),
        ));

        Gate::define('access-admin-panel', fn ($user) => $user->isAdmin());
        Gate::define('manage-users', fn ($user) => $user->isAdmin());
        Gate::define('view-audit-log', fn ($user) => $user->isAdmin());
        Gate::define('manage-sheet-integration', fn ($user) => $user->isAdmin());
    }
}
