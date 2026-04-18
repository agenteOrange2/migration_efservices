<?php

namespace App\Services;

use App\Models\SiteBranding;
use Illuminate\Support\Facades\Schema;

class BrandingService
{
    private const LEGACY_DEFAULT_APP_NAME = 'EF Services';

    private ?SiteBranding $branding = null;

    public function getModel(): SiteBranding
    {
        if ($this->branding) {
            return $this->branding;
        }

        if (! Schema::hasTable('site_brandings')) {
            return $this->branding = new SiteBranding([
                'app_name' => config('app.name', self::LEGACY_DEFAULT_APP_NAME),
                'login_title' => 'Welcome back',
                'login_subtitle' => 'Sign in to access your transportation compliance workspace.',
                'login_heading' => 'Transportation compliance in one place',
                'login_description' => 'Manage drivers, vehicles, documents, and trips with a single operational view.',
            ]);
        }

        return $this->branding = SiteBranding::current();
    }

    public function getSharedData(): array
    {
        $branding = $this->getModel();
        $configAppName = config('app.name', self::LEGACY_DEFAULT_APP_NAME);

        $appName = filled($branding->app_name) ? $branding->app_name : $configAppName;

        if ($branding->app_name === self::LEGACY_DEFAULT_APP_NAME && $configAppName !== self::LEGACY_DEFAULT_APP_NAME) {
            $appName = $configAppName;
        }

        return [
            'appName' => $appName,
            'loginTitle' => $branding->login_title,
            'loginSubtitle' => $branding->login_subtitle,
            'loginHeading' => $branding->login_heading,
            'loginDescription' => $branding->login_description,
            'logoUrl' => $branding->logo_url,
            'faviconUrl' => $branding->favicon_url,
            'loginBackgroundUrl' => $branding->login_background_url,
        ];
    }
}
