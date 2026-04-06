<?php

namespace App\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        if (!$media->relationLoaded('model')) {
            $media->load('model');
        }

        $model = $media->model;
        $collection = $media->collection_name;
        $customProperties = $media->custom_properties;

        if (!$model) {
            return "others/{$media->getKey()}/";
        }

        if ($model instanceof \App\Models\TempDriverUpload) {
            $sessionId = $model->session_id;
            return "temp/driver/{$sessionId}/{$collection}/";
        }

        if ($model instanceof \App\Models\UserCarrierDetail) {
            return "user_carrier/{$model->id}/";
        }

        if ($model instanceof \App\Models\Admin\Driver\DriverLicense) {
            $driverId = $model->user_driver_detail_id ?? 'unknown';
            return "driver/{$driverId}/licenses/";
        }

        if ($model instanceof \App\Models\Admin\Driver\DriverMedicalQualification) {
            $driverId = $model->user_driver_detail_id ?? 'unknown';
            return "driver/{$driverId}/medical/";
        }

        if ($model instanceof \App\Models\Admin\Driver\DriverTrafficConviction) {
            $driverId = $model->user_driver_detail_id ?? 'unknown';
            return "driver/{$driverId}/traffic_convictions/{$model->getKey()}/";
        }

        if ($model instanceof \App\Models\Admin\Driver\DriverAccident) {
            $driverId = $model->user_driver_detail_id ?? 'unknown';
            return "driver/{$driverId}/accidents/{$model->getKey()}/";
        }

        if ($model instanceof \App\Models\Admin\Driver\DriverTrainingSchool) {
            $driverId = $model->user_driver_detail_id ?? 'unknown';
            return "driver/{$driverId}/training_schools/{$model->getKey()}/";
        }

        if ($model instanceof \App\Models\Admin\Driver\DriverCourse) {
            $driverId = $model->user_driver_detail_id ?? 'unknown';
            return "driver/{$driverId}/courses/{$model->getKey()}/";
        }

        if ($model instanceof \App\Models\Admin\Driver\DriverTesting) {
            $driverId = $model->user_driver_detail_id ?? 'unknown';
            return "driver/{$driverId}/testing/{$model->getKey()}/";
        }

        if ($model instanceof \App\Models\UserDriverDetail) {
            $driverId = $model->id ?? 'unknown';

            if ($collection === 'profile_photo_driver') {
                return "driver/{$driverId}/profile/";
            }

            if ($collection === 'trip_reports') {
                $documentDate = $customProperties['document_date'] ?? now()->format('Y-m-d');
                $date = \Carbon\Carbon::parse($documentDate);
                return "driver/{$driverId}/hos/{$date->year}/" . str_pad($date->month, 2, '0', STR_PAD_LEFT) . "/trip_reports/";
            } elseif ($collection === 'inspection_reports') {
                $documentDate = $customProperties['document_date'] ?? now()->format('Y-m-d');
                $date = \Carbon\Carbon::parse($documentDate);
                return "driver/{$driverId}/hos/{$date->year}/" . str_pad($date->month, 2, '0', STR_PAD_LEFT) . "/inspection_reports/";
            } elseif ($collection === 'daily_logs') {
                $documentDate = $customProperties['document_date'] ?? now()->format('Y-m-d');
                $date = \Carbon\Carbon::parse($documentDate);
                return "driver/{$driverId}/hos/{$date->year}/" . str_pad($date->month, 2, '0', STR_PAD_LEFT) . "/daily_logs/";
            } elseif ($collection === 'monthly_summaries') {
                $yearMonth = $customProperties['year_month'] ?? now()->format('Y-m');
                [$year, $month] = explode('-', $yearMonth);
                return "driver/{$driverId}/hos/{$year}/" . str_pad($month, 2, '0', STR_PAD_LEFT) . "/monthly_summaries/";
            } elseif ($collection === 'signatures') {
                return "driver/{$driverId}/hos/signatures/";
            }

            return "driver/{$model->id}/";
        }

        if ($model instanceof \App\Models\User) {
            if ($collection === 'profile_photo_carrier') {
                return "user_carrier/{$model->id}/";
            }
            return "users/{$model->id}/";
        }

        if ($model instanceof \App\Models\Membership) {
            return "memberships/{$model->id}/";
        }

        if ($model instanceof \App\Models\Carrier) {
            return "carriers/{$model->id}/";
        }

        if ($model instanceof \App\Models\CarrierDocument) {
            if (!$model->relationLoaded('carrier')) {
                $model->load('carrier:id,name');
            }
            if (!$model->relationLoaded('documentType')) {
                $model->load('documentType:id,name');
            }
            $carrierName = strtolower(str_replace(' ', '_', $model->carrier->name ?? 'unknown'));
            $documentTypeName = strtolower(str_replace(' ', '_', $model->documentType->name ?? 'unknown'));
            return "carrier_document/{$carrierName}/{$documentTypeName}/";
        }

        if ($model instanceof \App\Models\DocumentType) {
            $documentTypeName = strtolower(str_replace(' ', '_', $model->name));
            return "carrier_document/default/{$documentTypeName}/";
        }

        return "others/{$model->getKey()}/";
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'responsive/';
    }
}
