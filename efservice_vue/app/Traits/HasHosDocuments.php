<?php

namespace App\Traits;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Carbon\Carbon;

trait HasHosDocuments
{
    /**
     * Register media collections for HOS documents.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('trip_reports')
            ->useDisk('public')
            ->acceptsMimeTypes(['application/pdf']);

        $this->addMediaCollection('daily_logs')
            ->useDisk('public')
            ->acceptsMimeTypes(['application/pdf']);

        $this->addMediaCollection('monthly_summaries')
            ->useDisk('public')
            ->acceptsMimeTypes(['application/pdf']);

        $this->addMediaCollection('inspection_reports')
            ->useDisk('public')
            ->acceptsMimeTypes(['application/pdf']);

        $this->addMediaCollection('signatures')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/png', 'image/jpeg']);
    }

    /**
     * Get trip report PDF (latest).
     */
    public function getTripReportPdf(int $tripId): ?Media
    {
        return $this->getMedia('trip_reports')
            ->filter(function ($media) use ($tripId) {
                return $media->getCustomProperty('trip_id') == $tripId;
            })
            ->sortByDesc('created_at')
            ->first();
    }

    /**
     * Get all trip report PDFs for a trip.
     */
    public function getTripReportPdfs(int $tripId)
    {
        return $this->getMedia('trip_reports')
            ->filter(function ($media) use ($tripId) {
                return $media->getCustomProperty('trip_id') == $tripId;
            })
            ->sortByDesc('created_at');
    }

    /**
     * Get inspection report PDFs for a trip.
     */
    public function getInspectionReportPdfs(int $tripId)
    {
        return $this->getMedia('inspection_reports')
            ->filter(function ($media) use ($tripId) {
                return $media->getCustomProperty('trip_id') == $tripId;
            })
            ->sortByDesc('created_at');
    }

    /**
     * Get pre-trip inspection PDF for a trip.
     */
    public function getPreTripInspectionPdf(int $tripId): ?Media
    {
        return $this->getMedia('inspection_reports')
            ->filter(function ($media) use ($tripId) {
                return $media->getCustomProperty('trip_id') == $tripId 
                    && $media->getCustomProperty('report_type') == 'pre_trip_inspection';
            })
            ->sortByDesc('created_at')
            ->first();
    }

    /**
     * Get post-trip inspection PDF for a trip.
     */
    public function getPostTripInspectionPdf(int $tripId): ?Media
    {
        return $this->getMedia('inspection_reports')
            ->filter(function ($media) use ($tripId) {
                return $media->getCustomProperty('trip_id') == $tripId 
                    && $media->getCustomProperty('report_type') == 'post_trip_inspection';
            })
            ->sortByDesc('created_at')
            ->first();
    }

    /**
     * Get daily log PDF.
     */
    public function getDailyLogPdf(Carbon $date): ?Media
    {
        return $this->getMedia('daily_logs')
            ->filter(function ($media) use ($date) {
                return $media->getCustomProperty('document_date') == $date->format('Y-m-d');
            })
            ->first();
    }

    /**
     * Get monthly summary PDF.
     */
    public function getMonthlySummaryPdf(int $year, int $month): ?Media
    {
        $dateKey = sprintf('%04d-%02d', $year, $month);
        return $this->getMedia('monthly_summaries')
            ->filter(function ($media) use ($dateKey) {
                return $media->getCustomProperty('year_month') == $dateKey;
            })
            ->first();
    }

    /**
     * Get all HOS documents.
     */
    public function getAllHosDocuments()
    {
        return $this->getMedia('trip_reports')
            ->merge($this->getMedia('daily_logs'))
            ->merge($this->getMedia('monthly_summaries'))
            ->sortByDesc('created_at');
    }

    /**
     * Get signature for a specific date.
     */
    public function getSignature(Carbon $date): ?Media
    {
        return $this->getMedia('signatures')
            ->filter(function ($media) use ($date) {
                return $media->getCustomProperty('signature_date') == $date->format('Y-m-d');
            })
            ->first();
    }
}
