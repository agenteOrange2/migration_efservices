<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SiteBranding extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'app_name',
        'login_title',
        'login_subtitle',
        'login_heading',
        'login_description',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'app_name' => config('app.name', 'EF Services'),
                'login_title' => 'Welcome back',
                'login_subtitle' => 'Sign in to access your transportation compliance workspace.',
                'login_heading' => 'Transportation compliance in one place',
                'login_description' => 'Manage drivers, vehicles, documents, and trips with a single operational view.',
            ],
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('branding_logo')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('branding_favicon')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('login_background')
            ->useDisk('public')
            ->singleFile();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('branding_logo') ?: null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('branding_favicon') ?: null;
    }

    public function getLoginBackgroundUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('login_background') ?: null;
    }
}
