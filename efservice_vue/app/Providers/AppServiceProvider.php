<?php

namespace App\Providers;

use App\Models\Carrier;
use App\Models\CarrierDocument;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverTrafficConviction;
use App\Models\AdminMessage;
use App\Observers\CarrierObserver;
use App\Observers\CarrierDocumentObserver;
use App\Observers\UserDriverDetailObserver;
use App\Policies\DriverTrafficConvictionPolicy;
use App\Policies\AdminMessagePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        Carrier::observe(CarrierObserver::class);
        CarrierDocument::observe(CarrierDocumentObserver::class);
        UserDriverDetail::observe(UserDriverDetailObserver::class);

        Gate::policy(DriverTrafficConviction::class, DriverTrafficConvictionPolicy::class);
        Gate::policy(AdminMessage::class, AdminMessagePolicy::class);

        if (class_exists('Barryvdh\\DomPDF\\ServiceProvider')) {
            try {
                $pdf = app('dompdf.wrapper');
                $pdf->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'tempDir' => storage_path('app/temp'),
                    'chroot' => [
                        public_path(),
                        storage_path('app'),
                        storage_path('app/public'),
                        storage_path('app/temp'),
                    ],
                ]);
            } catch (\Exception $e) {
                //
            }
        }
    }
}
