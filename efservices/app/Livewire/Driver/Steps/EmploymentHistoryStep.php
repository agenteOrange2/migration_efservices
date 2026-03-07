<?php

namespace App\Livewire\Driver\Steps;
use App\Helpers\Constants;
use App\Helpers\DateHelper;
use App\Traits\DriverValidationTrait;
use App\Traits\CacheableListsTrait;
use App\Traits\AutoSaveTrait;
use App\Traits\ProgressiveValidationTrait;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\MasterCompany;
use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\DriverUnemploymentPeriod;
use App\Models\Admin\Driver\DriverRelatedEmployment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\Admin\Driver\EmploymentVerificationToken;
use App\Mail\EmploymentVerification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;

class EmploymentHistoryStep extends Component
{
    use DriverValidationTrait, CacheableListsTrait, AutoSaveTrait, ProgressiveValidationTrait;
    
    // Unemployment Periods
    public $has_unemployment_periods = false;
    public $unemployment_periods = [];
    public $combinedEmploymentHistory = [];

    // Unemployment Form Modal
    public $showUnemploymentForm = false;
    public $editing_unemployment_index = null;
    public $unemployment_form = [
        'id' => null,
        'start_date' => '',
        'end_date' => '',
        'comments' => '',
    ];

    // Employment Companies
    public $employment_companies = [];
    public $has_completed_employment_history = false;
    public $years_of_history = 0;
    
    // Related Employment (driving-related jobs like taxi driver, forklift operator, etc.)
    public $related_employments = [];
    public $showRelatedEmploymentForm = false;
    public $editing_related_employment_index = null;
    public $related_employment_form = [
        'id' => null,
        'start_date' => '',
        'end_date' => '',
        'position' => '',
        'comments' => '',
    ];

    // Company Form
    public $showCompanyForm = false;
    public $editing_company_index = null;
    public $company_form = [
        'id' => null,
        'master_company_id' => null,
        'company_name' => '',
        'address' => '',
        'city' => '',
        'state' => '',
        'zip' => '',
        'contact' => '',
        'phone' => '',
        'email' => '',
        'fax' => '',
        'employed_from' => '',
        'employed_to' => '',
        'positions_held' => '',
        'subject_to_fmcsr' => false,
        'safety_sensitive_function' => false,
        'reason_for_leaving' => '',
        'other_reason_description' => '',
        'explanation' => '',
        'is_from_master' => false,
        'email_sent' => false
    ];

    // Search Company Modal
    public $showSearchCompanyModal = false;
    public $companySearchTerm = '';
    public $searchResults = [];
    public $searchPage = 1;
    public $searchPerPage = 20;
    public $hasMoreResults = false;


    // Propiedades para la confirmación de eliminación
    public $showDeleteConfirmationModal = false;
    public $deleteType = null; // 'employment' o 'unemployment'
    public $deleteIndex = null;
    
    // Property for bulk email confirmation
    public $showBulkEmailConfirmationModal = false;

    // References
    public $driverId;

    // Validation rules
    protected function rules()
    {
        return array_merge(
            $this->getDriverRegistrationRules('employment'),
            [
                'has_unemployment_periods' => 'sometimes|boolean',
                'has_completed_employment_history' => 'accepted',
            ]
        );
    }

    // Rules for partial saves
    protected function partialRules()
    {
        return [
            'has_unemployment_periods' => 'sometimes|boolean',
        ];
    }

    /**
     * Get required validation rules for progressive validation
     * Requirement 3.1: Validar solo campos requeridos al avanzar
     * 
     * @return array
     */
    protected function getRequiredRules(): array
    {
        return [
            'has_completed_employment_history' => 'accepted',
        ];
    }

    /**
     * Get optional validation rules for progressive validation
     * Requirement 3.2: Permitir avanzar con campos opcionales vacíos
     * 
     * @return array
     */
    protected function getOptionalRules(): array
    {
        return [
            'has_unemployment_periods' => 'boolean',
        ];
    }

    /**
     * Get human-readable field names for validation messages
     * 
     * @return array
     */
    protected function getFieldNames(): array
    {
        return [
            'has_completed_employment_history' => 'Employment History Confirmation',
            'has_unemployment_periods' => 'Unemployment Periods',
        ];
    }

    /**
     * Implementación del método abstracto de AutoSaveTrait
     * Guarda automáticamente los datos del formulario
     */
    protected function performAutoSave(): void
    {
        if (!$this->driverId) {
            return;
        }

        try {
            $this->saveEmploymentHistoryData();
            Log::info('AutoSave completed for Driver EmploymentHistoryStep', [
                'driver_id' => $this->driverId,
            ]);
        } catch (\Exception $e) {
            Log::error('AutoSave failed for Driver EmploymentHistoryStep', [
                'driver_id' => $this->driverId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // Initialize
    public function mount($driverId = null)
    {
        $this->driverId = $driverId;
        
        // Initialize AutoSave
        $this->initializeAutoSave();

        Log::info('EmploymentHistoryStep component mounted', [
            'driver_id' => $this->driverId
        ]);

        if ($this->driverId) {
            $this->loadExistingData();
        }

        // Calculate years of history
        $this->calculateYearsOfHistory();
    }

    // Load existing data
    protected function loadExistingData()
    {
        try {
            $userDriverDetail = UserDriverDetail::find($this->driverId);
            if (!$userDriverDetail) {
                Log::warning('Driver not found when loading existing data', [
                    'driver_id' => $this->driverId
                ]);
                return;
            }

            // Default values
            $this->has_unemployment_periods = false;
            $this->has_completed_employment_history = false;

            // Check if has unemployment periods from application details
            if ($userDriverDetail->application && $userDriverDetail->application->details) {
                $this->has_unemployment_periods = (bool)($userDriverDetail->application->details->has_unemployment_periods ?? false);
                $this->has_completed_employment_history = (bool)($userDriverDetail->application->details->has_completed_employment_history ?? false);
            }

            // Load unemployment periods in chronological order (most recent first)
            $this->unemployment_periods = [];
            $unemploymentPeriods = DriverUnemploymentPeriod::select('id', 'start_date', 'end_date', 'comments')
                ->where('user_driver_detail_id', $this->driverId)
                ->orderBy('end_date', 'desc')
                ->get();
            
            foreach ($unemploymentPeriods as $period) {
                $this->unemployment_periods[] = [
                    'id' => $period->id,
                    'start_date' => DateHelper::toDisplay($period->start_date),
                    'end_date' => DateHelper::toDisplay($period->end_date),
                    'comments' => $period->comments,
                ];
            }
            
            // Si hay períodos de desempleo registrados, asegurarse de que has_unemployment_periods sea true
            if (count($this->unemployment_periods) > 0) {
                $this->has_unemployment_periods = true;
            }

            // Load employment companies with optimized eager loading
            $this->employment_companies = [];
            $employmentCompanies = DriverEmploymentCompany::with(['masterCompany' => function($query) {
                    // Only select needed columns to reduce memory usage
                    $query->select('id', 'company_name', 'address', 'city', 'state', 'zip', 'contact', 'phone', 'email', 'fax');
                }])
                ->select('id', 'user_driver_detail_id', 'master_company_id', 'employed_from', 'employed_to', 
                         'positions_held', 'subject_to_fmcsr', 'safety_sensitive_function', 'reason_for_leaving',
                         'other_reason_description', 'explanation', 'email', 'email_sent')
                ->where('user_driver_detail_id', $this->driverId)
                ->orderBy('employed_to', 'desc')
                ->get();

            foreach ($employmentCompanies as $company) {
                $masterCompany = $company->masterCompany;
                
                $this->employment_companies[] = [
                    'id' => $company->id,
                    'master_company_id' => $company->master_company_id,
                    'company_name' => $masterCompany ? $masterCompany->company_name : '',
                    'address' => $masterCompany ? $masterCompany->address : '',
                    'city' => $masterCompany ? $masterCompany->city : '',
                    'state' => $masterCompany ? $masterCompany->state : '',
                    'zip' => $masterCompany ? $masterCompany->zip : '',
                    'contact' => $masterCompany ? $masterCompany->contact : '',
                    'phone' => $masterCompany ? $masterCompany->phone : '',
                    'email' => $masterCompany ? $masterCompany->email : ($company->email ?? ''),
                    'fax' => $masterCompany ? $masterCompany->fax : '',
                    'employed_from' => DateHelper::toDisplay($company->employed_from),
                    'employed_to' => DateHelper::toDisplay($company->employed_to),
                    'positions_held' => $company->positions_held,
                    'subject_to_fmcsr' => $company->subject_to_fmcsr,
                    'safety_sensitive_function' => $company->safety_sensitive_function,
                    'reason_for_leaving' => $company->reason_for_leaving,
                    'other_reason_description' => $company->other_reason_description,
                    'explanation' => $company->explanation,
                    'is_from_master' => true,
                    'email_sent' => $company->email_sent ?? false,
                ];
            }

            // Load related employments in chronological order
            $this->related_employments = [];
            $relatedEmployments = DriverRelatedEmployment::select('id', 'start_date', 'end_date', 'position', 'comments')
                ->where('user_driver_detail_id', $this->driverId)
                ->orderBy('end_date', 'desc')
                ->get();
            
            foreach ($relatedEmployments as $employment) {
                $this->related_employments[] = [
                    'id' => $employment->id,
                    'start_date' => DateHelper::toDisplay($employment->start_date),
                    'end_date' => DateHelper::toDisplay($employment->end_date),
                    'position' => $employment->position,
                    'comments' => $employment->comments,
                ];
            }

            // Calculate years of history and update computed properties
            $this->calculateYearsOfHistory();
            
            Log::info('Successfully loaded existing employment data', [
                'driver_id' => $this->driverId,
                'employment_companies_count' => count($this->employment_companies),
                'unemployment_periods_count' => count($this->unemployment_periods),
                'related_employments_count' => count($this->related_employments),
                'years_of_history' => $this->years_of_history
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading existing employment data', [
                'driver_id' => $this->driverId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Error loading employment history data. Please refresh the page or contact support.');
        }
    }

    // Calculate years of employment history
    public function calculateYearsOfHistory()
    {
        $totalYears = 0;
        $combinedHistory = [];

        // Process employment periods
        foreach ($this->employment_companies as $index => $company) {
            if (!empty($company['employed_from']) && !empty($company['employed_to'])) {
                $from = Carbon::parse($company['employed_from']);
                $to = Carbon::parse($company['employed_to']);
                $years = $from->diffInDays($to) / 365.25;
                $totalYears += $years;
                $combinedHistory[] = [
                    'type' => 'employed',
                    'status' => 'EMPLOYED',
                    'note' => $company['company_name'],
                    'from_date' => $company['employed_from'],
                    'to_date' => $company['employed_to'],
                    'index' => $index,
                    'original_index' => $index,
                    'years' => $years
                ];
            }
        }

        // Process unemployment periods - siempre incluir si existen, independientemente del checkbox
        if (count($this->unemployment_periods) > 0) {
            foreach ($this->unemployment_periods as $index => $period) {
                if (!empty($period['start_date']) && !empty($period['end_date'])) {
                    $from = Carbon::parse($period['start_date']);
                    $to = Carbon::parse($period['end_date']);
                    $years = $from->diffInDays($to) / 365.25;
                    $totalYears += $years;
                    $combinedHistory[] = [
                        'type' => 'unemployed',
                        'status' => 'UNEMPLOYED',
                        'note' => $period['comments'] ?? 'Unemployment Period',
                        'from_date' => $period['start_date'],
                        'to_date' => $period['end_date'],
                        'index' => $index,
                        'original_index' => $index,
                        'years' => $years
                    ];
                }
            }
            
            // Si hay períodos de desempleo, asegurar que el checkbox esté marcado
            if (!$this->has_unemployment_periods) {
                $this->has_unemployment_periods = true;
            }
        }
        
        // Process related employment periods (taxi driver, forklift operator, etc.)
        foreach ($this->related_employments as $index => $employment) {
            if (!empty($employment['start_date']) && !empty($employment['end_date'])) {
                $from = Carbon::parse($employment['start_date']);
                $to = Carbon::parse($employment['end_date']);
                $years = $from->diffInDays($to) / 365.25;
                $totalYears += $years;
                $combinedHistory[] = [
                    'type' => 'related',
                    'status' => 'RELATED EMPLOYMENT',
                    'note' => $employment['position'] . (empty($employment['comments']) ? '' : ' - ' . $employment['comments']),
                    'from_date' => $employment['start_date'],
                    'to_date' => $employment['end_date'],
                    'index' => $index,
                    'original_index' => $index,
                    'years' => $years
                ];
            }
        }

        // Sort by date, most recent first
        usort($combinedHistory, function ($a, $b) {
            return strtotime($b['to_date']) - strtotime($a['to_date']);
        });

        // Save combined history for view
        $this->combinedEmploymentHistory = $combinedHistory;

        // Update total years
        $this->years_of_history = round($totalYears, 1);
        
        Log::debug('Calculated employment history years', [
            'driver_id' => $this->driverId,
            'total_years' => $this->years_of_history,
            'employment_count' => count($this->employment_companies),
            'unemployment_count' => count($this->unemployment_periods),
            'related_employment_count' => count($this->related_employments)
        ]);
        
        return $this->years_of_history;
    }

    /**
     * Calculate employment history coverage
     * Returns detailed coverage information including total years and percentage
     * 
     * @return array
     */
    public function calculateEmploymentCoverage()
    {
        $requiredYears = 10;
        $totalYears = 0;
        $employmentYears = 0;
        $unemploymentYears = 0;
        $relatedEmploymentYears = 0;
        $gaps = [];

        // Calculate employment years
        foreach ($this->employment_companies as $company) {
            if (!empty($company['employed_from']) && !empty($company['employed_to'])) {
                $from = Carbon::parse($company['employed_from']);
                $to = Carbon::parse($company['employed_to']);
                $years = $from->diffInDays($to) / 365.25;
                $employmentYears += $years;
            }
        }

        // Calculate unemployment years
        foreach ($this->unemployment_periods as $period) {
            if (!empty($period['start_date']) && !empty($period['end_date'])) {
                $from = Carbon::parse($period['start_date']);
                $to = Carbon::parse($period['end_date']);
                $years = $from->diffInDays($to) / 365.25;
                $unemploymentYears += $years;
            }
        }

        // Calculate related employment years
        foreach ($this->related_employments as $employment) {
            if (!empty($employment['start_date']) && !empty($employment['end_date'])) {
                $from = Carbon::parse($employment['start_date']);
                $to = Carbon::parse($employment['end_date']);
                $years = $from->diffInDays($to) / 365.25;
                $relatedEmploymentYears += $years;
            }
        }

        // Total coverage
        $totalYears = $employmentYears + $unemploymentYears + $relatedEmploymentYears;
        
        // Calculate coverage percentage
        $coveragePercentage = min(100, ($totalYears / $requiredYears) * 100);

        // Detect gaps in employment history
        $allPeriods = [];
        
        // Collect all periods
        foreach ($this->employment_companies as $company) {
            if (!empty($company['employed_from']) && !empty($company['employed_to'])) {
                $allPeriods[] = [
                    'start' => Carbon::parse($company['employed_from']),
                    'end' => Carbon::parse($company['employed_to']),
                    'type' => 'employment'
                ];
            }
        }
        
        foreach ($this->unemployment_periods as $period) {
            if (!empty($period['start_date']) && !empty($period['end_date'])) {
                $allPeriods[] = [
                    'start' => Carbon::parse($period['start_date']),
                    'end' => Carbon::parse($period['end_date']),
                    'type' => 'unemployment'
                ];
            }
        }
        
        foreach ($this->related_employments as $employment) {
            if (!empty($employment['start_date']) && !empty($employment['end_date'])) {
                $allPeriods[] = [
                    'start' => Carbon::parse($employment['start_date']),
                    'end' => Carbon::parse($employment['end_date']),
                    'type' => 'related'
                ];
            }
        }

        // Sort periods by start date
        usort($allPeriods, function ($a, $b) {
            return $a['start']->timestamp - $b['start']->timestamp;
        });

        // Find gaps between periods
        for ($i = 0; $i < count($allPeriods) - 1; $i++) {
            $currentEnd = $allPeriods[$i]['end'];
            $nextStart = $allPeriods[$i + 1]['start'];
            
            // If there's a gap of more than 1 day
            if ($currentEnd->diffInDays($nextStart) > 1) {
                $gapYears = $currentEnd->diffInDays($nextStart) / 365.25;
                $gaps[] = [
                    'start' => $currentEnd->format('Y-m-d'),
                    'end' => $nextStart->format('Y-m-d'),
                    'years' => round($gapYears, 2)
                ];
            }
        }

        return [
            'total_years' => round($totalYears, 1),
            'employment_years' => round($employmentYears, 1),
            'unemployment_years' => round($unemploymentYears, 1),
            'related_employment_years' => round($relatedEmploymentYears, 1),
            'required_years' => $requiredYears,
            'coverage_percentage' => round($coveragePercentage, 1),
            'is_complete' => $totalYears >= $requiredYears,
            'gaps' => $gaps,
            'gap_count' => count($gaps)
        ];
    }

    // Save employment history data to database
    protected function saveEmploymentHistoryData()
    {
        DB::beginTransaction();
        try {
            Log::info('Starting saveEmploymentHistoryData', [
                'driver_id' => $this->driverId,
                'employment_companies_count' => count($this->employment_companies),
                'unemployment_periods_count' => count($this->unemployment_periods),
                'related_employments_count' => count($this->related_employments)
            ]);
            
            $userDriverDetail = UserDriverDetail::find($this->driverId);
            if (!$userDriverDetail) {
                throw new \Exception('Driver not found with ID: ' . $this->driverId);
            }

            // Update user driver details
            $userDriverDetail->update([
                'has_completed_employment_history' => $this->has_completed_employment_history,
            ]);

            // Update application details for unemployment periods
            if ($userDriverDetail->application && $userDriverDetail->application->details) {
                $userDriverDetail->application->details->update([
                    'has_unemployment_periods' => $this->has_unemployment_periods,
                ]);
            }

            // Save unemployment periods
            $existingPeriodIds = $userDriverDetail->unemploymentPeriods()->pluck('id')->toArray();
            $updatedPeriodIds = [];

            foreach ($this->unemployment_periods as $period) {
                if (!empty($period['start_date']) && !empty($period['end_date'])) {
                    if (!empty($period['id'])) {
                        // Update existing period
                        $unemploymentPeriod = DriverUnemploymentPeriod::find($period['id']);
                        if ($unemploymentPeriod) {
                            $unemploymentPeriod->update([
                                'start_date' => DateHelper::toDatabase($period['start_date']),
                'end_date' => DateHelper::toDatabase($period['end_date']),
                                'comments' => $period['comments'] ?? null
                            ]);
                            $updatedPeriodIds[] = $unemploymentPeriod->id;
                        }
                    } else {
                        // Create new period
                        $unemploymentPeriod = $userDriverDetail->unemploymentPeriods()->create([
                            'start_date' => DateHelper::toDatabase($period['start_date']),
                'end_date' => DateHelper::toDatabase($period['end_date']),
                            'comments' => $period['comments'] ?? null
                        ]);
                        $updatedPeriodIds[] = $unemploymentPeriod->id;
                    }
                }
            }

            // Delete periods that are no longer needed
            $periodsToDelete = array_diff($existingPeriodIds, $updatedPeriodIds);
            if (!empty($periodsToDelete)) {
                $userDriverDetail->unemploymentPeriods()->whereIn('id', $periodsToDelete)->delete();
            }

            // Save employment companies
            $existingCompanyIds = $userDriverDetail->employmentCompanies()->pluck('id')->toArray();
            $updatedCompanyIds = [];

            foreach ($this->employment_companies as $company) {
                if (!empty($company['employed_from']) && !empty($company['employed_to'])) {
                    // Determine if we need to create or update a master company
                    $masterCompanyId = null;
                    
                    if (!empty($company['master_company_id'])) {
                        // Use existing master company
                        $masterCompanyId = $company['master_company_id'];
                    } else {
                        // Check if MasterCompany already exists by name
                        $masterCompany = MasterCompany::where('company_name', $company['company_name'])->first();
                        
                        if (!$masterCompany) {
                            // Create new master company
                            $masterCompany = MasterCompany::create([
                                'company_name' => $company['company_name'],
                                'address' => $company['address'] ?? null,
                                'city' => $company['city'] ?? null,
                                'state' => $company['state'] ?? null,
                                'zip' => $company['zip'] ?? null,
                                'contact' => $company['contact'] ?? null,
                                'phone' => $company['phone'] ?? null,
                                'email' => $company['email'] ?? null,
                                'fax' => $company['fax'] ?? null,
                            ]);
                            
                            // Clear cache since we added a new company
                            $this->clearMasterCompanyCache();
                        } else {
                            Log::info('Using existing MasterCompany', ['company_name' => $company['company_name'], 'id' => $masterCompany->id]);
                        }
                        $masterCompanyId = $masterCompany->id;
                    }

                    // Create or update employment company
                    if (!empty($company['id'])) {
                        // Update existing employment company
                        $employmentCompany = DriverEmploymentCompany::find($company['id']);
                        if ($employmentCompany) {
                            $employmentCompany->update([
                                'master_company_id' => $masterCompanyId,
                                'employed_from' => DateHelper::toDatabase($company['employed_from']),
                'employed_to' => DateHelper::toDatabase($company['employed_to']),
                                'positions_held' => $company['positions_held'],
                                'subject_to_fmcsr' => $company['subject_to_fmcsr'] ?? false,
                                'safety_sensitive_function' => $company['safety_sensitive_function'] ?? false,
                                'reason_for_leaving' => $company['reason_for_leaving'] ?? null,
                                'other_reason_description' => $company['reason_for_leaving'] === 'other' ? 
                                    $company['other_reason_description'] : null,
                                'email' => $company['email'] ?? null,
                                'explanation' => $company['explanation'] ?? null
                            ]);
                            $updatedCompanyIds[] = $employmentCompany->id;
                        }
                    } else {
                        // Create new employment company
                        $employmentCompany = $userDriverDetail->employmentCompanies()->create([
                            'master_company_id' => $masterCompanyId,
                            'employed_from' => DateHelper::toDatabase($company['employed_from']),
                'employed_to' => DateHelper::toDatabase($company['employed_to']),
                            'positions_held' => $company['positions_held'],
                            'subject_to_fmcsr' => $company['subject_to_fmcsr'] ?? false,
                            'safety_sensitive_function' => $company['safety_sensitive_function'] ?? false,
                            'reason_for_leaving' => $company['reason_for_leaving'] ?? null,
                            'other_reason_description' => $company['reason_for_leaving'] === 'other' ? 
                                $company['other_reason_description'] : null,
                            'email' => $company['email'] ?? null,
                            'explanation' => $company['explanation'] ?? null,
                            'email_sent' => $company['email_sent'] ?? false
                        ]);
                        $updatedCompanyIds[] = $employmentCompany->id;
                    }
                }
            }

            // Delete companies that are no longer needed
            $companiesToDelete = array_diff($existingCompanyIds, $updatedCompanyIds);
            if (!empty($companiesToDelete)) {
                $userDriverDetail->employmentCompanies()->whereIn('id', $companiesToDelete)->delete();
            }
            
            // Save related employments
            $existingRelatedEmploymentIds = DriverRelatedEmployment::where('user_driver_detail_id', $this->driverId)
                ->pluck('id')
                ->toArray();
            $updatedRelatedEmploymentIds = [];
            
            foreach ($this->related_employments as $employment) {
                if (!empty($employment['start_date']) && !empty($employment['end_date']) && !empty($employment['position'])) {
                    if (!empty($employment['id'])) {
                        // Update existing related employment
                        $relatedEmployment = DriverRelatedEmployment::find($employment['id']);
                        if ($relatedEmployment) {
                            $relatedEmployment->update([
                                'start_date' => DateHelper::toDatabase($employment['start_date']),
                'end_date' => DateHelper::toDatabase($employment['end_date']),
                                'position' => $employment['position'],
                                'comments' => $employment['comments'] ?? null
                            ]);
                            $updatedRelatedEmploymentIds[] = $relatedEmployment->id;
                        }
                    } else {
                        // Create new related employment
                        $relatedEmployment = DriverRelatedEmployment::create([
                            'user_driver_detail_id' => $this->driverId,
                            'start_date' => DateHelper::toDatabase($employment['start_date']),
                'end_date' => DateHelper::toDatabase($employment['end_date']),
                            'position' => $employment['position'],
                            'comments' => $employment['comments'] ?? null
                        ]);
                        $updatedRelatedEmploymentIds[] = $relatedEmployment->id;
                    }
                }
            }
            
            // Delete related employments that are no longer needed
            $relatedEmploymentsToDelete = array_diff($existingRelatedEmploymentIds, $updatedRelatedEmploymentIds);
            if (!empty($relatedEmploymentsToDelete)) {
                DriverRelatedEmployment::whereIn('id', $relatedEmploymentsToDelete)->delete();
            }

            // Update current step
            $userDriverDetail->update(['current_step' => 10]);

            DB::commit();
            
            Log::info('Successfully saved employment history data', [
                'driver_id' => $this->driverId
            ]);
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error saving employment history data', [
                'driver_id' => $this->driverId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'employment_companies_count' => count($this->employment_companies),
                'unemployment_periods_count' => count($this->unemployment_periods),
                'related_employments_count' => count($this->related_employments)
            ]);
            
            session()->flash('error', 'Error saving employment history: ' . $e->getMessage());
            return false;
        }
    }

    // Add unemployment period (abre el modal)
    public function addUnemploymentPeriod()
    {
        $this->resetUnemploymentForm();
        $this->showUnemploymentForm = true;
        $this->editing_unemployment_index = null;
    }

    // Edit unemployment period (abre el modal)
    public function editUnemploymentPeriod($index)
    {
        if (isset($this->unemployment_periods[$index])) {
            $this->editing_unemployment_index = $index;
            $this->unemployment_form = $this->unemployment_periods[$index];
            $this->showUnemploymentForm = true;
            
            Log::info('Editing unemployment period', [
                'driver_id' => $this->driverId,
                'period_index' => $index,
                'period_id' => $this->unemployment_form['id'] ?? null
            ]);
        }
    }

    // Close unemployment form
    public function closeUnemploymentForm()
    {
        $this->showUnemploymentForm = false;
        $this->resetUnemploymentForm();
    }

    // Reset unemployment form
    public function resetUnemploymentForm()
    {
        $this->unemployment_form = [
            'id' => null,
            'start_date' => '',
            'end_date' => '',
            'comments' => '',
        ];
        $this->editing_unemployment_index = null;
    }

    // Save unemployment period
    public function saveUnemploymentPeriod()
    {
        Log::info('Attempting to save unemployment period', [
            'driver_id' => $this->driverId,
            'period_id' => $this->unemployment_form['id'] ?? null,
            'start_date' => $this->unemployment_form['start_date'] ?? null,
            'end_date' => $this->unemployment_form['end_date'] ?? null,
            'is_editing' => !empty($this->unemployment_form['id'])
        ]);
        
        // Validate the unemployment form
        try {
            $this->validate([
                'unemployment_form.start_date' => 'required|date',
                'unemployment_form.end_date' => 'required|date|after_or_equal:unemployment_form.start_date',
                'unemployment_form.comments' => 'nullable|string|max:1000',
            ], [
                'unemployment_form.start_date.required' => 'Start date is required.',
                'unemployment_form.end_date.required' => 'End date is required.',
                'unemployment_form.end_date.after_or_equal' => 'End date must be on or after the start date.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Unemployment period validation failed', [
                'driver_id' => $this->driverId,
                'period_id' => $this->unemployment_form['id'] ?? null,
                'validation_errors' => $e->errors()
            ]);
            throw $e;
        }

        // Check for date overlap with employment periods (warning only)
        $overlapWarning = $this->checkUnemploymentOverlap(
            $this->unemployment_form['start_date'],
            $this->unemployment_form['end_date'],
            $this->unemployment_form['id'] ?? null
        );

        if ($overlapWarning) {
            Log::warning('Unemployment period overlaps with employment', [
                'driver_id' => $this->driverId,
                'period_id' => $this->unemployment_form['id'] ?? null,
                'start_date' => $this->unemployment_form['start_date'],
                'end_date' => $this->unemployment_form['end_date'],
                'warning' => $overlapWarning
            ]);
            session()->flash('warning', $overlapWarning);
        }

        DB::beginTransaction();
        try {
            $userDriverDetail = UserDriverDetail::find($this->driverId);
            if (!$userDriverDetail) {
                throw new \Exception('Driver not found');
            }

            // Create or update unemployment period
            if (!empty($this->unemployment_form['id'])) {
                // Update existing period
                $unemploymentPeriod = DriverUnemploymentPeriod::find($this->unemployment_form['id']);
                if ($unemploymentPeriod) {
                    $unemploymentPeriod->update([
                        'start_date' => DateHelper::toDatabase($this->unemployment_form['start_date']),
                        'end_date' => DateHelper::toDatabase($this->unemployment_form['end_date']),
                        'comments' => $this->unemployment_form['comments'] ?? null
                    ]);
                    
                    Log::info('Updated DriverUnemploymentPeriod', [
                        'driver_id' => $this->driverId,
                        'period_id' => $unemploymentPeriod->id,
                        'start_date' => $this->unemployment_form['start_date'],
                        'end_date' => $this->unemployment_form['end_date']
                    ]);
                }
            } else {
                // Create new period
                $unemploymentPeriod = $userDriverDetail->unemploymentPeriods()->create([
                    'start_date' => DateHelper::toDatabase($this->unemployment_form['start_date']),
                    'end_date' => DateHelper::toDatabase($this->unemployment_form['end_date']),
                    'comments' => $this->unemployment_form['comments'] ?? null
                ]);
                
                Log::info('Created new DriverUnemploymentPeriod', [
                    'driver_id' => $this->driverId,
                    'period_id' => $unemploymentPeriod->id,
                    'start_date' => $this->unemployment_form['start_date'],
                    'end_date' => $this->unemployment_form['end_date']
                ]);
            }

            DB::commit();
            
            Log::info('Successfully saved unemployment period', [
                'driver_id' => $this->driverId,
                'period_id' => $unemploymentPeriod->id
            ]);
            
            // Reload data from database
            $this->loadExistingData();
            
            // Close form and show success message
            $this->closeUnemploymentForm();
            session()->flash('success', 'Unemployment period saved successfully');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving unemployment period', [
                'driver_id' => $this->driverId,
                'period_id' => $this->unemployment_form['id'] ?? null,
                'start_date' => $this->unemployment_form['start_date'] ?? null,
                'end_date' => $this->unemployment_form['end_date'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Error saving unemployment period: ' . $e->getMessage());
        }
    }

    // Check for date overlap with employment periods (warning only)
    protected function checkUnemploymentOverlap($startDate, $endDate, $excludeId = null)
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            // Check overlap with employment companies
            foreach ($this->employment_companies as $company) {
                if (!empty($company['employed_from']) && !empty($company['employed_to'])) {
                    $empStart = Carbon::parse($company['employed_from']);
                    $empEnd = Carbon::parse($company['employed_to']);

                    // Check if dates overlap
                    if ($start->lte($empEnd) && $end->gte($empStart)) {
                        return 'Warning: This unemployment period overlaps with employment at ' . $company['company_name'] . 
                               ' (' . $company['employed_from'] . ' to ' . $company['employed_to'] . '). Please verify the dates are correct.';
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Error checking unemployment overlap', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete unemployment period
    public function deleteUnemploymentPeriod($index)
    {
        DB::beginTransaction();
        try {
            // Verify index exists
            if (!isset($this->unemployment_periods[$index])) {
                throw new \Exception('Unemployment period not found at index ' . $index);
            }
            
            $periodId = null;
            $startDate = $this->unemployment_periods[$index]['start_date'] ?? null;
            $endDate = $this->unemployment_periods[$index]['end_date'] ?? null;
            
            // If the unemployment period has an ID, delete it from the database
            if (!empty($this->unemployment_periods[$index]['id'])) {
                $periodId = $this->unemployment_periods[$index]['id'];
                DriverUnemploymentPeriod::where('id', $periodId)->delete();
                
                Log::info('Deleted DriverUnemploymentPeriod from database', [
                    'driver_id' => $this->driverId,
                    'period_id' => $periodId,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);
            }

            // Remove from array
            unset($this->unemployment_periods[$index]);
            $this->unemployment_periods = array_values($this->unemployment_periods);

            DB::commit();
            
            // Reload data from database
            $this->loadExistingData();
            
            session()->flash('success', 'Unemployment period deleted successfully');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting unemployment period', [
                'driver_id' => $this->driverId,
                'index' => $index,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Error deleting unemployment period: ' . $e->getMessage());
        }
    }

    // Remove unemployment period
    public function removeUnemploymentPeriod($index)
    {
        if (count($this->unemployment_periods) > 1) {
            unset($this->unemployment_periods[$index]);
            $this->unemployment_periods = array_values($this->unemployment_periods);
            $this->calculateYearsOfHistory();
        }
    }

    // Add employment company
    public function addEmploymentCompany()
    {
        $this->resetCompanyForm();
        $this->showCompanyForm = true;
        $this->editing_company_index = null;
    }

    // Edit employment company
    public function editEmploymentCompany($index)
    {
        try {
            if (!isset($this->employment_companies[$index])) {
                throw new \Exception('Employment company not found at index ' . $index);
            }
            
            $this->editing_company_index = $index;
            $this->company_form = $this->employment_companies[$index];
            $this->showCompanyForm = true;
            
            Log::info('Editing employment company', [
                'driver_id' => $this->driverId,
                'company_index' => $index,
                'company_id' => $this->company_form['id'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Error editing employment company', [
                'driver_id' => $this->driverId,
                'index' => $index,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Error loading company data: ' . $e->getMessage());
        }
    }

    // Close company form
    public function closeCompanyForm()
    {
        $this->showCompanyForm = false;
        $this->resetCompanyForm();
    }

    // Reset company form
    public function resetCompanyForm()
    {
        $this->company_form = [
            'id' => null,
            'master_company_id' => null,
            'company_name' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'contact' => '',
            'phone' => '',
            'fax' => '',
            'email' => '',
            'employed_from' => '',
            'employed_to' => '',
            'positions_held' => '',
            'subject_to_fmcsr' => false,
            'safety_sensitive_function' => false,
            'reason_for_leaving' => '',
            'other_reason_description' => '',
            'explanation' => '',
            'is_from_master' => false,
            'email_sent' => false
        ];
        $this->editing_company_index = null;
    }

    // Save company form
    public function saveCompany()
    {
        Log::info('Attempting to save employment company', [
            'driver_id' => $this->driverId,
            'company_id' => $this->company_form['id'] ?? null,
            'company_name' => $this->company_form['company_name'] ?? null,
            'is_editing' => !empty($this->company_form['id'])
        ]);
        
        // Comprehensive validation rules for all company form fields
        try {
            $this->validate([
                'company_form.company_name' => 'required|string|max:255',
                'company_form.address' => 'nullable|string|max:255',
                'company_form.city' => 'nullable|string|max:100',
                'company_form.state' => 'nullable|string|max:2',
                'company_form.zip' => 'nullable|string|max:10',
                'company_form.contact' => 'nullable|string|max:255',
                'company_form.phone' => 'nullable|string|max:20',
                'company_form.email' => 'nullable|email|max:255',
                'company_form.fax' => 'nullable|string|max:20',
                'company_form.employed_from' => 'required|date',
                'company_form.employed_to' => 'required|date|after_or_equal:company_form.employed_from',
                'company_form.positions_held' => 'required|string|max:255',
                'company_form.reason_for_leaving' => 'required|string|max:255',
                'company_form.other_reason_description' => 'required_if:company_form.reason_for_leaving,other|max:255',
                'company_form.explanation' => 'nullable|string|max:1000',
            ], [
                'company_form.company_name.required' => 'Company name is required.',
                'company_form.email.email' => 'Please enter a valid email address.',
                'company_form.employed_from.required' => 'Employment start date is required.',
                'company_form.employed_to.required' => 'Employment end date is required.',
                'company_form.employed_to.after_or_equal' => 'Employment end date must be on or after the start date.',
                'company_form.positions_held.required' => 'Position held is required.',
                'company_form.reason_for_leaving.required' => 'Reason for leaving is required.',
                'company_form.other_reason_description.required_if' => 'Please describe the other reason for leaving.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Company form validation failed', [
                'driver_id' => $this->driverId,
                'company_id' => $this->company_form['id'] ?? null,
                'validation_errors' => $e->errors()
            ]);
            throw $e;
        }

        DB::beginTransaction();
        try {
            $userDriverDetail = UserDriverDetail::find($this->driverId);
            if (!$userDriverDetail) {
                throw new \Exception('Driver not found');
            }

            // Determine master company ID
            $masterCompanyId = null;
            
            if (!empty($this->company_form['master_company_id'])) {
                // Use existing master company
                $masterCompanyId = $this->company_form['master_company_id'];
            } else {
                // Check if MasterCompany already exists by name
                $masterCompany = MasterCompany::where('company_name', $this->company_form['company_name'])->first();
                
                if (!$masterCompany) {
                    // Create new master company
                    $masterCompany = MasterCompany::create([
                        'company_name' => $this->company_form['company_name'],
                        'address' => $this->company_form['address'] ?? null,
                        'city' => $this->company_form['city'] ?? null,
                        'state' => $this->company_form['state'] ?? null,
                        'zip' => $this->company_form['zip'] ?? null,
                        'contact' => $this->company_form['contact'] ?? null,
                        'phone' => $this->company_form['phone'] ?? null,
                        'email' => $this->company_form['email'] ?? null,
                        'fax' => $this->company_form['fax'] ?? null,
                    ]);
                    
                    // Clear cache since we added a new company
                    $this->clearMasterCompanyCache();
                    
                    Log::info('Created new MasterCompany', [
                        'driver_id' => $this->driverId,
                        'company_name' => $masterCompany->company_name,
                        'master_company_id' => $masterCompany->id,
                        'city' => $masterCompany->city,
                        'state' => $masterCompany->state
                    ]);
                } else {
                    Log::info('Using existing MasterCompany', [
                        'driver_id' => $this->driverId,
                        'company_name' => $masterCompany->company_name,
                        'master_company_id' => $masterCompany->id
                    ]);
                }
                $masterCompanyId = $masterCompany->id;
            }

            // Create or update employment company
            if (!empty($this->company_form['id'])) {
                // Update existing employment company
                $employmentCompany = DriverEmploymentCompany::find($this->company_form['id']);
                if ($employmentCompany) {
                    $employmentCompany->update([
                        'master_company_id' => $masterCompanyId,
                        'employed_from' => DateHelper::toDatabase($this->company_form['employed_from']),
                        'employed_to' => DateHelper::toDatabase($this->company_form['employed_to']),
                        'positions_held' => $this->company_form['positions_held'],
                        'subject_to_fmcsr' => $this->company_form['subject_to_fmcsr'] ?? false,
                        'safety_sensitive_function' => $this->company_form['safety_sensitive_function'] ?? false,
                        'reason_for_leaving' => $this->company_form['reason_for_leaving'] ?? null,
                        'other_reason_description' => $this->company_form['reason_for_leaving'] === 'other' ? 
                            $this->company_form['other_reason_description'] : null,
                        'email' => $this->company_form['email'] ?? null,
                        'explanation' => $this->company_form['explanation'] ?? null
                    ]);
                    
                    Log::info('Updated DriverEmploymentCompany', [
                        'driver_id' => $this->driverId,
                        'company_id' => $employmentCompany->id,
                        'master_company_id' => $masterCompanyId,
                        'employed_from' => $this->company_form['employed_from'],
                        'employed_to' => $this->company_form['employed_to'],
                        'positions_held' => $this->company_form['positions_held']
                    ]);
                }
            } else {
                // Create new employment company
                $employmentCompany = $userDriverDetail->employmentCompanies()->create([
                    'master_company_id' => $masterCompanyId,
                    'employed_from' => DateHelper::toDatabase($this->company_form['employed_from']),
                    'employed_to' => DateHelper::toDatabase($this->company_form['employed_to']),
                    'positions_held' => $this->company_form['positions_held'],
                    'subject_to_fmcsr' => $this->company_form['subject_to_fmcsr'] ?? false,
                    'safety_sensitive_function' => $this->company_form['safety_sensitive_function'] ?? false,
                    'reason_for_leaving' => $this->company_form['reason_for_leaving'] ?? null,
                    'other_reason_description' => $this->company_form['reason_for_leaving'] === 'other' ? 
                        $this->company_form['other_reason_description'] : null,
                    'email' => $this->company_form['email'] ?? null,
                    'explanation' => $this->company_form['explanation'] ?? null,
                    'email_sent' => false
                ]);
                
                Log::info('Created new DriverEmploymentCompany', [
                    'driver_id' => $this->driverId,
                    'company_id' => $employmentCompany->id,
                    'master_company_id' => $masterCompanyId,
                    'employed_from' => $this->company_form['employed_from'],
                    'employed_to' => $this->company_form['employed_to'],
                    'positions_held' => $this->company_form['positions_held'],
                    'email' => $this->company_form['email'] ?? null
                ]);
            }

            DB::commit();
            
            Log::info('Successfully saved employment company', [
                'driver_id' => $this->driverId,
                'company_id' => $employmentCompany->id,
                'company_name' => $this->company_form['company_name']
            ]);
            
            // Reload data from database to ensure UI reflects current state
            $this->loadExistingData();
            
            // Close form and show success message
            $this->closeCompanyForm();
            $this->companySearchTerm = '';
            $this->searchResults = [];
            
            session()->flash('success', 'Company saved successfully');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving company', [
                'driver_id' => $this->driverId,
                'company_id' => $this->company_form['id'] ?? null,
                'company_name' => $this->company_form['company_name'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Error saving company: ' . $e->getMessage());
        }
    }

    // Open search company modal
    public function openSearchCompanyModal()
    {
        Log::info('Opening company search modal', [
            'driver_id' => $this->driverId
        ]);
        
        // Cerrar el formulario de empresa si está abierto
        $this->showCompanyForm = false;
        // Abrir el modal de búsqueda
        $this->showSearchCompanyModal = true;
        $this->searchCompanies();
    }

    // Close search company modal
    public function closeSearchCompanyModal()
    {
        $this->showSearchCompanyModal = false;
        $this->companySearchTerm = '';
        $this->searchResults = [];
        $this->searchPage = 1;
        $this->hasMoreResults = false;
    }

    // Clear master company cache (call when companies are created/updated)
    protected function clearMasterCompanyCache()
    {
        // Clear recent companies cache
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget('master_companies_recent_' . $page);
        }
        
        // Note: Search caches will expire naturally after 2 minutes
        Log::info('Cleared master company cache', [
            'driver_id' => $this->driverId
        ]);
    }

    /**
     * Get employment statistics efficiently with a single query
     * 
     * @return array
     */
    public function getEmploymentStatistics()
    {
        $stats = [
            'total_companies' => 0,
            'emails_sent' => 0,
            'emails_pending' => 0,
            'companies_with_email' => 0,
            'unemployment_periods' => 0,
            'related_employments' => 0,
        ];

        // Get employment company stats
        $employmentStats = DriverEmploymentCompany::where('user_driver_detail_id', $this->driverId)
            ->selectRaw('
                COUNT(*) as total_companies,
                SUM(CASE WHEN email_sent = 1 THEN 1 ELSE 0 END) as emails_sent,
                SUM(CASE WHEN email_sent = 0 AND email IS NOT NULL THEN 1 ELSE 0 END) as emails_pending,
                SUM(CASE WHEN email IS NOT NULL THEN 1 ELSE 0 END) as companies_with_email
            ')
            ->first();

        if ($employmentStats) {
            $stats['total_companies'] = $employmentStats->total_companies ?? 0;
            $stats['emails_sent'] = $employmentStats->emails_sent ?? 0;
            $stats['emails_pending'] = $employmentStats->emails_pending ?? 0;
            $stats['companies_with_email'] = $employmentStats->companies_with_email ?? 0;
        }

        // Get unemployment period count
        $stats['unemployment_periods'] = DriverUnemploymentPeriod::where('user_driver_detail_id', $this->driverId)
            ->count();

        // Get related employment count
        $stats['related_employments'] = DriverRelatedEmployment::where('user_driver_detail_id', $this->driverId)
            ->count();

        return $stats;
    }

    // Search companies efficiently with caching and pagination
    public function searchCompanies()
    {
        try {
            $this->searchPage = 1; // Reset to first page on new search
            $this->loadSearchResults();
        } catch (\Exception $e) {
            Log::error('Error searching companies', [
                'driver_id' => $this->driverId,
                'search_term' => $this->companySearchTerm,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->searchResults = [];
            session()->flash('error', 'Error searching companies. Please try again.');
        }
    }

    // Load search results with pagination
    protected function loadSearchResults()
    {
        $searchTerm = trim($this->companySearchTerm);
        
        // If search term is empty, show recent companies from cache
        if (empty($searchTerm)) {
            $cacheKey = 'master_companies_recent_' . $this->searchPage;
            $cacheDuration = 300; // 5 minutes
            
            $results = Cache::remember($cacheKey, $cacheDuration, function () {
                return MasterCompany::select('id', 'company_name', 'address', 'city', 'state', 'zip', 'phone', 'email')
                    ->orderBy('created_at', 'desc')
                    ->skip(($this->searchPage - 1) * $this->searchPerPage)
                    ->take($this->searchPerPage + 1) // Get one extra to check if there are more
                    ->get();
            });
            
            $this->hasMoreResults = $results->count() > $this->searchPerPage;
            $this->searchResults = $results->take($this->searchPerPage)->toArray();
            
            return;
        }

        // Search companies by term with optimized query using indexes
        // Cache search results for 2 minutes to reduce database load
        $cacheKey = 'master_companies_search_' . md5($searchTerm) . '_page_' . $this->searchPage;
        $cacheDuration = 120; // 2 minutes
        
        $results = Cache::remember($cacheKey, $cacheDuration, function () use ($searchTerm) {
            return MasterCompany::select('id', 'company_name', 'address', 'city', 'state', 'zip', 'phone', 'email')
                ->where(function($query) use ($searchTerm) {
                    // Prioritize exact matches and use indexed columns first
                    $query->where('company_name', 'like', $searchTerm . '%')
                        ->orWhere('company_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('city', 'like', $searchTerm . '%')
                        ->orWhere('state', 'like', $searchTerm . '%');
                })
                ->orderByRaw("CASE 
                    WHEN company_name LIKE ? THEN 1 
                    WHEN company_name LIKE ? THEN 2 
                    ELSE 3 
                END", [$searchTerm . '%', '%' . $searchTerm . '%'])
                ->orderBy('company_name', 'asc')
                ->skip(($this->searchPage - 1) * $this->searchPerPage)
                ->take($this->searchPerPage + 1) // Get one extra to check if there are more
                ->get();
        });
        
        $this->hasMoreResults = $results->count() > $this->searchPerPage;
        $this->searchResults = $results->take($this->searchPerPage)->toArray();
        
        Log::info('Company search executed', [
            'driver_id' => $this->driverId,
            'search_term' => $searchTerm,
            'page' => $this->searchPage,
            'results_count' => count($this->searchResults),
            'has_more' => $this->hasMoreResults
        ]);
    }

    // Load more search results (pagination)
    public function loadMoreSearchResults()
    {
        $this->searchPage++;
        $previousResults = $this->searchResults;
        $this->loadSearchResults();
        
        // Append new results to existing ones
        $this->searchResults = array_merge($previousResults, $this->searchResults);
    }

    // Handle company search term update
    public function updatedCompanySearchTerm()
    {
        $this->searchCompanies();
    }

    // Select company from search and pre-fill form
    public function selectCompany($companyId)
    {
        try {
            $masterCompany = MasterCompany::find($companyId);
            if ($masterCompany) {
                // Pre-fill form with selected company data
                $this->company_form = [
                    'id' => null, // New employment record
                    'master_company_id' => $masterCompany->id, // Link to master company
                    'company_name' => $masterCompany->company_name,
                    'address' => $masterCompany->address ?? '',
                    'city' => $masterCompany->city ?? '',
                    'state' => $masterCompany->state ?? '',
                    'zip' => $masterCompany->zip ?? '',
                    'contact' => $masterCompany->contact ?? '',
                    'phone' => $masterCompany->phone ?? '',
                    'fax' => $masterCompany->fax ?? '',
                    'email' => $masterCompany->email ?? '',
                    // Editable fields for employment period
                    'employed_from' => '',
                    'employed_to' => '',
                    'positions_held' => '',
                    'subject_to_fmcsr' => false,
                    'safety_sensitive_function' => false,
                    'reason_for_leaving' => '',
                    'other_reason_description' => '',
                    'explanation' => '',
                    'is_from_master' => true,
                    'email_sent' => false
                ];
                
                Log::info('Company selected from search', [
                    'driver_id' => $this->driverId,
                    'master_company_id' => $masterCompany->id,
                    'company_name' => $masterCompany->company_name
                ]);
                
                $this->closeSearchCompanyModal();
                $this->showCompanyForm = true;
            } else {
                // Handle case when company is not found
                Log::warning('Company not found in search', [
                    'driver_id' => $this->driverId,
                    'company_id' => $companyId
                ]);
                session()->flash('error', 'Company not found. Please try searching again or enter manually.');
            }
        } catch (\Exception $e) {
            Log::error('Error selecting company from search', [
                'driver_id' => $this->driverId,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Error loading company data: ' . $e->getMessage());
        }
    }

    // Get empty unemployment period structure
    protected function getEmptyUnemploymentPeriod()
    {
        return [
            'id' => null,
            'start_date' => '',
            'end_date' => '',
            'comments' => '',
        ];
    }
    
    // Get empty related employment structure
    protected function getEmptyRelatedEmployment()
    {
        return [
            'id' => null,
            'start_date' => '',
            'end_date' => '',
            'position' => '',
            'comments' => '',
        ];
    }
    
    // Add related employment
    public function addRelatedEmployment()
    {
        $this->resetRelatedEmploymentForm();
        $this->showRelatedEmploymentForm = true;
        $this->editing_related_employment_index = null;
    }
    
    // Edit related employment
    public function editRelatedEmployment($index)
    {
        if (isset($this->related_employments[$index])) {
            $this->related_employment_form = $this->related_employments[$index];
            $this->showRelatedEmploymentForm = true;
            $this->editing_related_employment_index = $index;
            
            Log::info('Editing related employment', [
                'driver_id' => $this->driverId,
                'employment_index' => $index,
                'employment_id' => $this->related_employment_form['id'] ?? null
            ]);
        }
    }
    
    // Close related employment form
    public function closeRelatedEmploymentForm()
    {
        $this->showRelatedEmploymentForm = false;
        $this->resetRelatedEmploymentForm();
    }
    
    // Reset related employment form
    public function resetRelatedEmploymentForm()
    {
        $this->related_employment_form = $this->getEmptyRelatedEmployment();
        $this->editing_related_employment_index = null;
    }
    
    // Save related employment
    public function saveRelatedEmployment()
    {
        Log::info('Attempting to save related employment', [
            'driver_id' => $this->driverId,
            'employment_id' => $this->related_employment_form['id'] ?? null,
            'position' => $this->related_employment_form['position'] ?? null,
            'is_editing' => !empty($this->related_employment_form['id'])
        ]);
        
        try {
            $this->validate([
                'related_employment_form.start_date' => 'required|date',
                'related_employment_form.end_date' => 'required|date|after_or_equal:related_employment_form.start_date',
                'related_employment_form.position' => 'required|string|max:255',
                'related_employment_form.comments' => 'nullable|string|max:1000',
            ], [
                'related_employment_form.start_date.required' => 'Start date is required.',
                'related_employment_form.end_date.required' => 'End date is required.',
                'related_employment_form.end_date.after_or_equal' => 'End date must be on or after the start date.',
                'related_employment_form.position.required' => 'Position is required.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Related employment validation failed', [
                'driver_id' => $this->driverId,
                'employment_id' => $this->related_employment_form['id'] ?? null,
                'validation_errors' => $e->errors()
            ]);
            throw $e;
        }
        
        DB::beginTransaction();
        try {
            $userDriverDetail = UserDriverDetail::find($this->driverId);
            if (!$userDriverDetail) {
                throw new \Exception('Driver not found');
            }

            // Create or update related employment
            if (!empty($this->related_employment_form['id'])) {
                // Update existing related employment
                $relatedEmployment = DriverRelatedEmployment::find($this->related_employment_form['id']);
                if ($relatedEmployment) {
                    $relatedEmployment->update([
                        'start_date' => DateHelper::toDatabase($this->related_employment_form['start_date']),
                        'end_date' => DateHelper::toDatabase($this->related_employment_form['end_date']),
                        'position' => $this->related_employment_form['position'],
                        'comments' => $this->related_employment_form['comments'] ?? null
                    ]);
                    
                    Log::info('Updated DriverRelatedEmployment', [
                        'driver_id' => $this->driverId,
                        'employment_id' => $relatedEmployment->id,
                        'position' => $this->related_employment_form['position'],
                        'start_date' => $this->related_employment_form['start_date'],
                        'end_date' => $this->related_employment_form['end_date']
                    ]);
                }
            } else {
                // Create new related employment
                $relatedEmployment = DriverRelatedEmployment::create([
                    'user_driver_detail_id' => $this->driverId,
                    'start_date' => DateHelper::toDatabase($this->related_employment_form['start_date']),
                    'end_date' => DateHelper::toDatabase($this->related_employment_form['end_date']),
                    'position' => $this->related_employment_form['position'],
                    'comments' => $this->related_employment_form['comments'] ?? null
                ]);
                
                Log::info('Created new DriverRelatedEmployment', [
                    'driver_id' => $this->driverId,
                    'employment_id' => $relatedEmployment->id,
                    'position' => $this->related_employment_form['position'],
                    'start_date' => $this->related_employment_form['start_date'],
                    'end_date' => $this->related_employment_form['end_date']
                ]);
            }

            DB::commit();
            
            Log::info('Successfully saved related employment', [
                'driver_id' => $this->driverId,
                'employment_id' => $relatedEmployment->id
            ]);
            
            // Reload data from database
            $this->loadExistingData();
            
            // Close form and show success message
            $this->closeRelatedEmploymentForm();
            session()->flash('success', 'Related employment saved successfully');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving related employment', [
                'driver_id' => $this->driverId,
                'employment_id' => $this->related_employment_form['id'] ?? null,
                'position' => $this->related_employment_form['position'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Error saving related employment: ' . $e->getMessage());
        }
    }
    
    // Delete related employment with confirmation modal
    public function deleteRelatedEmployment($index)
    {
        DB::beginTransaction();
        try {
            // Verify index exists
            if (!isset($this->related_employments[$index])) {
                throw new \Exception('Related employment not found at index ' . $index);
            }
            
            $employmentId = null;
            $position = $this->related_employments[$index]['position'] ?? 'Unknown';
            
            // If the related employment has an ID, delete it from the database
            if (!empty($this->related_employments[$index]['id'])) {
                $employmentId = $this->related_employments[$index]['id'];
                DriverRelatedEmployment::where('id', $employmentId)->delete();
                
                Log::info('Deleted DriverRelatedEmployment from database', [
                    'driver_id' => $this->driverId,
                    'employment_id' => $employmentId,
                    'position' => $position
                ]);
            }

            // Remove from array
            unset($this->related_employments[$index]);
            $this->related_employments = array_values($this->related_employments);

            DB::commit();
            
            // Reload data from database
            $this->loadExistingData();
            
            session()->flash('success', 'Related employment deleted successfully');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting related employment', [
                'driver_id' => $this->driverId,
                'index' => $index,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Error deleting related employment: ' . $e->getMessage());
        }
    }

    // Confirm deletion of related employment
    public function confirmDeleteRelatedEmployment($index)
    {
        $this->deleteType = 'related_employment';
        $this->deleteIndex = $index;
        $this->showDeleteConfirmationModal = true;
    }

    // Confirmar eliminación de empleo
    public function confirmDeleteEmploymentCompany($index)
    {
        $this->deleteType = 'employment';
        $this->deleteIndex = $index;
        $this->showDeleteConfirmationModal = true;
    }

    // Confirmar eliminación de desempleo
    public function confirmDeleteUnemploymentPeriod($index)
    {
        $this->deleteType = 'unemployment';
        $this->deleteIndex = $index;
        $this->showDeleteConfirmationModal = true;
    }

    // Cancelar eliminación
    public function cancelDelete()
    {
        $this->showDeleteConfirmationModal = false;
        $this->deleteType = null;
        $this->deleteIndex = null;
    }

    // Confirmar y ejecutar eliminación
    public function confirmDelete()
    {
        Log::info('Confirming deletion', [
            'driver_id' => $this->driverId,
            'delete_type' => $this->deleteType,
            'delete_index' => $this->deleteIndex
        ]);
        
        DB::beginTransaction();
        try {
            $recordId = null;
            $recordName = '';
            
            if ($this->deleteType === 'employment') {
                // Verify index exists
                if (!isset($this->employment_companies[$this->deleteIndex])) {
                    throw new \Exception('Employment company not found at index ' . $this->deleteIndex);
                }
                
                // Si tiene ID, eliminar de la base de datos
                if (!empty($this->employment_companies[$this->deleteIndex]['id'])) {
                    $recordId = $this->employment_companies[$this->deleteIndex]['id'];
                    $recordName = $this->employment_companies[$this->deleteIndex]['company_name'] ?? 'Unknown';
                    
                    DriverEmploymentCompany::where('id', $recordId)->delete();
                    
                    Log::info('Deleted DriverEmploymentCompany', [
                        'driver_id' => $this->driverId,
                        'company_id' => $recordId,
                        'company_name' => $recordName
                    ]);
                }
                // Eliminamos el registro de empleo del array
                unset($this->employment_companies[$this->deleteIndex]);
                $this->employment_companies = array_values($this->employment_companies);
                session()->flash('success', 'Employment company deleted successfully');
                
            } elseif ($this->deleteType === 'unemployment') {
                // Verify index exists
                if (!isset($this->unemployment_periods[$this->deleteIndex])) {
                    throw new \Exception('Unemployment period not found at index ' . $this->deleteIndex);
                }
                
                // Si tiene ID, eliminar de la base de datos
                if (!empty($this->unemployment_periods[$this->deleteIndex]['id'])) {
                    $recordId = $this->unemployment_periods[$this->deleteIndex]['id'];
                    
                    DriverUnemploymentPeriod::where('id', $recordId)->delete();
                    
                    Log::info('Deleted DriverUnemploymentPeriod', [
                        'driver_id' => $this->driverId,
                        'period_id' => $recordId,
                        'start_date' => $this->unemployment_periods[$this->deleteIndex]['start_date'] ?? null,
                        'end_date' => $this->unemployment_periods[$this->deleteIndex]['end_date'] ?? null
                    ]);
                }
                // Eliminamos el registro de desempleo del array
                unset($this->unemployment_periods[$this->deleteIndex]);
                $this->unemployment_periods = array_values($this->unemployment_periods);
                session()->flash('success', 'Unemployment period deleted successfully');
                
            } elseif ($this->deleteType === 'related_employment') {
                // Verify index exists
                if (!isset($this->related_employments[$this->deleteIndex])) {
                    throw new \Exception('Related employment not found at index ' . $this->deleteIndex);
                }
                
                // Si tiene ID, eliminar de la base de datos
                if (!empty($this->related_employments[$this->deleteIndex]['id'])) {
                    $recordId = $this->related_employments[$this->deleteIndex]['id'];
                    $recordName = $this->related_employments[$this->deleteIndex]['position'] ?? 'Unknown';
                    
                    DriverRelatedEmployment::where('id', $recordId)->delete();
                    
                    Log::info('Deleted DriverRelatedEmployment', [
                        'driver_id' => $this->driverId,
                        'employment_id' => $recordId,
                        'position' => $recordName
                    ]);
                }
                // Eliminamos el registro de empleo relacionado del array
                unset($this->related_employments[$this->deleteIndex]);
                $this->related_employments = array_values($this->related_employments);
                session()->flash('success', 'Related employment deleted successfully');
            } else {
                throw new \Exception('Invalid delete type: ' . $this->deleteType);
            }
            
            DB::commit();
            
            Log::info('Successfully deleted record', [
                'driver_id' => $this->driverId,
                'delete_type' => $this->deleteType,
                'record_id' => $recordId
            ]);
            
            // Reload data from database to ensure UI reflects current state
            $this->loadExistingData();
            
            // Cerramos el modal
            $this->showDeleteConfirmationModal = false;
            $this->deleteType = null;
            $this->deleteIndex = null;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting record', [
                'driver_id' => $this->driverId,
                'type' => $this->deleteType,
                'index' => $this->deleteIndex,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Error deleting record: ' . $e->getMessage());
            
            // Keep modal open so user can retry or cancel
        }
    }

    // Next step (or complete)
    public function next()
    {
        try {
            // Validate that confirmation checkbox is checked
            $this->validate([
                'has_completed_employment_history' => 'accepted',
            ], [
                'has_completed_employment_history.accepted' => 'You must confirm that the employment history information is correct and complete.'
            ]);
        
            // Validate that at least one employment company exists
            if (count($this->employment_companies) === 0) {
                $this->addError(
                    'employment_history',
                    'You must add at least one employment company to continue.'
                );
                
                Log::warning('Employment history validation failed - no companies', [
                    'driver_id' => $this->driverId
                ]);
                
                return;
            }
        
            // Calculate employment history coverage
            $coverage = $this->calculateEmploymentCoverage();
            $requiredYears = 10;
            
            // Display warning if coverage is less than required period (non-blocking)
            if ($coverage['total_years'] < $requiredYears) {
                session()->flash('warning', sprintf(
                    'Your employment history covers %.1f years out of the required %d years. Please ensure you have provided complete information including unemployment periods and related employment.',
                    $coverage['total_years'],
                    $requiredYears
                ));
                
                Log::warning('Employment history coverage below required period', [
                    'driver_id' => $this->driverId,
                    'coverage_years' => $coverage['total_years'],
                    'required_years' => $requiredYears,
                    'coverage_percentage' => $coverage['coverage_percentage']
                ]);
            }
        
            // Data is already saved immediately via saveCompany(), saveUnemploymentPeriod(), etc.
            // Only update completion status and step progression
            if ($this->driverId) {
                Log::info('EmploymentHistoryStep: Updating completion status', [
                    'driver_id' => $this->driverId,
                    'coverage_years' => $coverage['total_years'],
                    'coverage_percentage' => $coverage['coverage_percentage']
                ]);
                
                DB::beginTransaction();
                try {
                    $userDriverDetail = UserDriverDetail::find($this->driverId);
                    if (!$userDriverDetail) {
                        throw new \Exception('Driver not found with ID: ' . $this->driverId);
                    }
                    
                    // Update completion status
                    $userDriverDetail->update([
                        'has_completed_employment_history' => $this->has_completed_employment_history,
                        'current_step' => 10
                    ]);
                    
                    // Update application details for unemployment periods
                    if ($userDriverDetail->application && $userDriverDetail->application->details) {
                        $userDriverDetail->application->details->update([
                            'has_unemployment_periods' => $this->has_unemployment_periods,
                            'has_completed_employment_history' => $this->has_completed_employment_history,
                        ]);
                    }
                    
                    DB::commit();
                    
                    Log::info('Successfully updated employment history completion status', [
                        'driver_id' => $this->driverId
                    ]);
                    
                    // Note: Email verification is optional and doesn't block navigation
                    // Emails can be sent manually by the driver at any time
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    
                    Log::error('Error updating employment history completion status', [
                        'driver_id' => $this->driverId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    session()->flash('error', 'Error updating completion status: ' . $e->getMessage() . '. Please try again.');
                    return;
                }
            }

            // Advance to next step
            $this->dispatch('nextStep');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions so Livewire can handle them
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected error in next() method', [
                'driver_id' => $this->driverId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'An unexpected error occurred. Please try again or contact support.');
        }
    }

    // Previous step
    public function previous()
    {
        Log::info('Navigating to previous step from employment history', [
            'driver_id' => $this->driverId
        ]);
        
        // Data is already saved immediately, no need to save again
        $this->dispatch('prevStep');
    }

    // Save and exit
    public function saveAndExit()
    {
        Log::info('Saving and exiting employment history step', [
            'driver_id' => $this->driverId
        ]);
        
        // Data is already saved immediately, just exit
        $this->dispatch('saveAndExit');
    }



    /**
     * Send verification email to a specific company
     * 
     * @param int $companyId The employment company ID
     * @return void
     */
    public function sendVerificationEmail($companyId)
    {
        if (!$this->driverId) {
            session()->flash('error', 'Driver ID not found');
            return;
        }

        Log::info('Attempting to send verification email', [
            'driver_id' => $this->driverId,
            'company_id' => $companyId
        ]);

        DB::beginTransaction();
        try {
            // Find the employment company
            $employmentCompany = DriverEmploymentCompany::find($companyId);
            
            if (!$employmentCompany) {
                throw new \Exception('Employment company not found');
            }

            // Validate email exists
            if (empty($employmentCompany->email)) {
                throw new \Exception('No email address provided for this company');
            }

            // Validate email format
            if (!filter_var($employmentCompany->email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid email address format');
            }

            // Get master company
            $masterCompany = MasterCompany::find($employmentCompany->master_company_id);
            if (!$masterCompany) {
                throw new \Exception('Master company not found');
            }

            // Validate SMTP configuration (basic check)
            if (empty(config('mail.mailers.smtp.host'))) {
                throw new \Exception('SMTP configuration is not properly set up');
            }

            // Generate verification token
            $token = Str::random(64);
            
            // Create employment verification token record
            EmploymentVerificationToken::create([
                'driver_id' => $this->driverId,
                'employment_company_id' => $employmentCompany->id,
                'token' => $token,
                'email' => $employmentCompany->email,
                'expires_at' => now()->addDays(30),
            ]);
            
            // Get driver name
            $driver = UserDriverDetail::find($this->driverId);
            $driverName = $driver && $driver->user ? $driver->user->name : 'Driver';
            
            // Prepare employment data
            $employmentData = [
                'company_name' => $masterCompany->company_name,
                'contact_email' => $employmentCompany->email,
                'employed_from' => $employmentCompany->employed_from ?? 'Not specified',
                'employed_to' => $employmentCompany->employed_to ?? 'Not specified',
                'positions_held' => $employmentCompany->positions_held ?? 'Not specified',
                'reason_for_leaving' => $employmentCompany->reason_for_leaving ?? 'Not specified',
                'subject_to_fmcsr' => $employmentCompany->subject_to_fmcsr ?? false,
                'safety_sensitive_function' => $employmentCompany->safety_sensitive_function ?? false
            ];
            
            // Send email
            Mail::to($employmentCompany->email)->send(new EmploymentVerification(
                $masterCompany->company_name,
                $driverName,
                $employmentData,
                $token,
                $this->driverId,
                $employmentCompany->id
            ));

            // Update email_sent flag in database immediately after successful send
            $employmentCompany->update(['email_sent' => true]);

            // Count attempt number (after creating the new token)
            $attemptNumber = EmploymentVerificationToken::where('driver_id', $this->driverId)
                ->where('employment_company_id', $companyId)
                ->count();

            // Generate PDF for this attempt
            $pdfData = [
                'attemptNumber' => $attemptNumber,
                'attemptDate' => now()->format('m/d/Y'),
                'attemptTime' => now()->format('h:i:s A'),
                'emailSentTo' => $employmentCompany->email,
                'driverName' => $driverName,
                'driverId' => $this->driverId,
                'companyName' => $masterCompany->company_name,
                'companyEmail' => $employmentCompany->email,
                'employedFrom' => $employmentCompany->employed_from ? $employmentCompany->employed_from->format('m/d/Y') : 'Not specified',
                'employedTo' => $employmentCompany->employed_to ? $employmentCompany->employed_to->format('m/d/Y') : 'Not specified',
                'positionsHeld' => $employmentCompany->positions_held ?? 'Not specified',
                'reasonForLeaving' => $employmentCompany->reason_for_leaving ?? 'Not specified',
                'token' => $token,
                'expiresAt' => now()->addDays(30)->format('m/d/Y h:i A'),
                'generatedAt' => now()->format('m/d/Y h:i:s A'),
            ];

            $pdf = PDF::loadView('employment-verification.resend-attempt-pdf', $pdfData);
            
            // Save PDF to temp file
            $companySlug = preg_replace('/[^a-zA-Z0-9]/', '_', $masterCompany->company_name);
            $pdfFileName = 'employment_verification_attempt_' . $attemptNumber . '_' . $companySlug . '_' . time() . '.pdf';
            $tempPath = storage_path('app/temp/' . $pdfFileName);
            
            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            $pdf->save($tempPath);
            
            // Add PDF to driver's media collection
            if ($driver) {
                $driver->addMedia($tempPath)
                    ->usingFileName($pdfFileName)
                    ->usingName('Employment Verification Attempt #' . $attemptNumber . ' - ' . $masterCompany->company_name)
                    ->withCustomProperties([
                        'attempt_number' => $attemptNumber,
                        'company_name' => $masterCompany->company_name,
                        'company_id' => $companyId,
                        'email_sent_to' => $employmentCompany->email,
                        'sent_at' => now()->toDateTimeString(),
                    ])
                    ->toMediaCollection('employment_verification_attempts');

                Log::info('Employment verification attempt PDF generated and saved', [
                    'driver_id' => $this->driverId,
                    'company_id' => $companyId,
                    'attempt_number' => $attemptNumber,
                    'pdf_file' => $pdfFileName
                ]);
            }

            DB::commit();
            
            // Reload data from database to ensure UI reflects current state
            $this->loadExistingData();

            Log::info('Verification email sent successfully', [
                'company_id' => $employmentCompany->id,
                'email' => $employmentCompany->email,
                'driver_id' => $this->driverId
            ]);

            session()->flash('success', 'Verification email sent successfully to ' . $masterCompany->company_name);
            
        } catch (\Swift_TransportException $e) {
            DB::rollBack();
            Log::error('SMTP error sending verification email', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'driver_id' => $this->driverId
            ]);
            session()->flash('error', 'SMTP error: Unable to send email. Please check your email configuration.');
        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            DB::rollBack();
            Log::error('Mail transport error sending verification email', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'driver_id' => $this->driverId
            ]);
            session()->flash('error', 'Mail transport error: Unable to send email. Please try again later.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error sending verification email', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'driver_id' => $this->driverId
            ]);
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Resend verification email to a specific company
     * Reuses existing token if not expired, generates new one if expired
     * 
     * @param int $companyId The employment company ID
     * @return void
     */
    public function resendVerificationEmail($companyId)
    {
        if (!$this->driverId) {
            session()->flash('error', 'Driver ID not found');
            return;
        }

        Log::info('Attempting to resend verification email', [
            'driver_id' => $this->driverId,
            'company_id' => $companyId
        ]);

        DB::beginTransaction();
        try {
            // Check if maximum attempts (3) have been reached
            $attemptCount = EmploymentVerificationToken::where('driver_id', $this->driverId)
                ->where('employment_company_id', $companyId)
                ->count();
            
            if ($attemptCount >= 3) {
                throw new \Exception('Maximum verification attempts (3) reached for this company. No more emails can be sent.');
            }

            // Find the employment company
            $employmentCompany = DriverEmploymentCompany::find($companyId);
            
            if (!$employmentCompany) {
                throw new \Exception('Employment company not found');
            }

            // Validate email exists
            if (empty($employmentCompany->email)) {
                throw new \Exception('No email address provided for this company');
            }

            // Validate email format
            if (!filter_var($employmentCompany->email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid email address format');
            }

            // Get master company
            $masterCompany = MasterCompany::find($employmentCompany->master_company_id);
            if (!$masterCompany) {
                throw new \Exception('Master company not found');
            }

            // Always generate new token for each resend attempt
            $token = Str::random(64);
            
            // Create new employment verification token record
            EmploymentVerificationToken::create([
                'driver_id' => $this->driverId,
                'employment_company_id' => $employmentCompany->id,
                'token' => $token,
                'email' => $employmentCompany->email,
                'expires_at' => now()->addDays(30),
            ]);
            
            Log::info('Generated new verification token for resend', [
                'company_id' => $companyId
            ]);
            
            // Get driver
            $driver = UserDriverDetail::find($this->driverId);
            $driverName = $driver && $driver->user ? $driver->user->name : 'Driver';
            
            // Prepare employment data
            $employmentData = [
                'company_name' => $masterCompany->company_name,
                'contact_email' => $employmentCompany->email,
                'employed_from' => $employmentCompany->employed_from ?? 'Not specified',
                'employed_to' => $employmentCompany->employed_to ?? 'Not specified',
                'positions_held' => $employmentCompany->positions_held ?? 'Not specified',
                'reason_for_leaving' => $employmentCompany->reason_for_leaving ?? 'Not specified',
                'subject_to_fmcsr' => $employmentCompany->subject_to_fmcsr ?? false,
                'safety_sensitive_function' => $employmentCompany->safety_sensitive_function ?? false
            ];
            
            // Send email
            Mail::to($employmentCompany->email)->send(new EmploymentVerification(
                $masterCompany->company_name,
                $driverName,
                $employmentData,
                $token,
                $this->driverId,
                $employmentCompany->id
            ));

            // Update email_sent flag after successful resend
            $employmentCompany->update(['email_sent' => true]);

            // Count attempt number (after creating the new token)
            $attemptNumber = EmploymentVerificationToken::where('driver_id', $this->driverId)
                ->where('employment_company_id', $companyId)
                ->count();

            // Generate PDF for this attempt
            $pdfData = [
                'attemptNumber' => $attemptNumber,
                'attemptDate' => now()->format('m/d/Y'),
                'attemptTime' => now()->format('h:i:s A'),
                'emailSentTo' => $employmentCompany->email,
                'driverName' => $driverName,
                'driverId' => $this->driverId,
                'companyName' => $masterCompany->company_name,
                'companyEmail' => $employmentCompany->email,
                'employedFrom' => $employmentCompany->employed_from ? $employmentCompany->employed_from->format('m/d/Y') : 'Not specified',
                'employedTo' => $employmentCompany->employed_to ? $employmentCompany->employed_to->format('m/d/Y') : 'Not specified',
                'positionsHeld' => $employmentCompany->positions_held ?? 'Not specified',
                'reasonForLeaving' => $employmentCompany->reason_for_leaving ?? 'Not specified',
                'token' => $token,
                'expiresAt' => now()->addDays(30)->format('m/d/Y h:i A'),
                'generatedAt' => now()->format('m/d/Y h:i:s A'),
            ];

            $pdf = PDF::loadView('employment-verification.resend-attempt-pdf', $pdfData);
            
            // Save PDF to temp file
            $companySlug = preg_replace('/[^a-zA-Z0-9]/', '_', $masterCompany->company_name);
            $pdfFileName = 'employment_verification_attempt_' . $attemptNumber . '_' . $companySlug . '_' . time() . '.pdf';
            $tempPath = storage_path('app/temp/' . $pdfFileName);
            
            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            $pdf->save($tempPath);
            
            // Add PDF to driver's media collection
            if ($driver) {
                $driver->addMedia($tempPath)
                    ->usingFileName($pdfFileName)
                    ->usingName('Employment Verification Attempt #' . $attemptNumber . ' - ' . $masterCompany->company_name)
                    ->withCustomProperties([
                        'attempt_number' => $attemptNumber,
                        'company_name' => $masterCompany->company_name,
                        'company_id' => $companyId,
                        'email_sent_to' => $employmentCompany->email,
                        'sent_at' => now()->toDateTimeString(),
                    ])
                    ->toMediaCollection('employment_verification_attempts');

                Log::info('Employment verification attempt PDF generated and saved', [
                    'driver_id' => $this->driverId,
                    'company_id' => $companyId,
                    'attempt_number' => $attemptNumber,
                    'pdf_file' => $pdfFileName
                ]);
            }

            DB::commit();
            
            // Reload data from database to ensure UI reflects current state
            $this->loadExistingData();

            Log::info('Verification email resent successfully', [
                'company_id' => $employmentCompany->id,
                'email' => $employmentCompany->email,
                'driver_id' => $this->driverId
            ]);

            session()->flash('success', 'Verification email resent successfully to ' . $masterCompany->company_name);
            
        } catch (\Swift_TransportException $e) {
            DB::rollBack();
            Log::error('SMTP error resending verification email', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'driver_id' => $this->driverId
            ]);
            session()->flash('error', 'SMTP error: Unable to resend email. Please check your email configuration.');
        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            DB::rollBack();
            Log::error('Mail transport error resending verification email', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'driver_id' => $this->driverId
            ]);
            session()->flash('error', 'Mail transport error: Unable to resend email. Please try again later.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error resending verification email', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'driver_id' => $this->driverId
            ]);
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Show bulk email confirmation modal
     * 
     * @return void
     */
    public function confirmBulkEmailSend()
    {
        // Count companies that need verification emails
        $unsentCount = DriverEmploymentCompany::where('user_driver_detail_id', $this->driverId)
            ->where(function ($query) {
                $query->where('email_sent', false)
                    ->orWhereNull('email_sent');
            })
            ->whereNotNull('email')
            ->count();
        
        if ($unsentCount === 0) {
            session()->flash('info', 'No emails to send. All companies either have no contact email or verification emails have already been sent.');
            return;
        }
        
        $this->showBulkEmailConfirmationModal = true;
    }
    
    /**
     * Cancel bulk email send
     * 
     * @return void
     */
    public function cancelBulkEmailSend()
    {
        $this->showBulkEmailConfirmationModal = false;
    }

    /**
     * Send verification emails to all companies that haven't been sent yet
     * Sends emails sequentially and tracks success/failure counts
     * 
     * @return void
     */
    public function sendBulkVerificationEmails()
    {
        // Close confirmation modal
        $this->showBulkEmailConfirmationModal = false;
        
        if (!$this->driverId) {
            session()->flash('error', 'Driver ID not found');
            return;
        }

        Log::info('Starting bulk verification email send', [
            'driver_id' => $this->driverId
        ]);

        $successCount = 0;
        $failureCount = 0;
        $failedCompanies = [];

        // Find all companies that need verification emails with optimized query
        $driverCompanies = DriverEmploymentCompany::select('id', 'user_driver_detail_id', 'master_company_id', 'email', 'email_sent')
            ->where('user_driver_detail_id', $this->driverId)
            ->where(function ($query) {
                $query->where('email_sent', false)
                    ->orWhereNull('email_sent');
            })
            ->whereNotNull('email')
            ->get();

        Log::info('Companies requiring verification emails', [
            'count' => count($driverCompanies)
        ]);

        if ($driverCompanies->isEmpty()) {
            session()->flash('info', 'No emails to send. All companies either have no contact email or verification emails have already been sent.');
            return;
        }

        // Eager load master companies to reduce queries
        $driverCompanies->load(['masterCompany' => function($query) {
            $query->select('id', 'company_name', 'address', 'city', 'state', 'zip');
        }]);

        // Send emails sequentially to avoid rate limiting
        foreach ($driverCompanies as $dbCompany) {
            // Get master company from eager loaded relationship
            $masterCompany = $dbCompany->masterCompany;
            if (!$masterCompany) {
                Log::warning('Master company not found', [
                    'master_company_id' => $dbCompany->master_company_id,
                    'company_id' => $dbCompany->id
                ]);
                $failureCount++;
                $failedCompanies[] = [
                    'id' => $dbCompany->id,
                    'name' => 'Unknown Company',
                    'email' => $dbCompany->email
                ];
                continue;
            }

            DB::beginTransaction();
            try {
                // Validate email format
                if (!filter_var($dbCompany->email, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception('Invalid email address format');
                }

                // Generate verification token
                $token = Str::random(64);
                
                // Create employment verification token record
                EmploymentVerificationToken::create([
                    'driver_id' => $this->driverId,
                    'employment_company_id' => $dbCompany->id,
                    'token' => $token,
                    'email' => $dbCompany->email,
                    'expires_at' => now()->addDays(30),
                ]);
                
                // Get driver name
                $driver = UserDriverDetail::find($this->driverId);
                $driverName = $driver && $driver->user ? $driver->user->name : 'Driver';
                
                // Prepare employment data
                $employmentData = [
                    'company_name' => $masterCompany->company_name,
                    'contact_email' => $dbCompany->email,
                    'employed_from' => $dbCompany->employed_from ?? 'Not specified',
                    'employed_to' => $dbCompany->employed_to ?? 'Not specified',
                    'positions_held' => $dbCompany->positions_held ?? 'Not specified',
                    'reason_for_leaving' => $dbCompany->reason_for_leaving ?? 'Not specified',
                    'subject_to_fmcsr' => $dbCompany->subject_to_fmcsr ?? false,
                    'safety_sensitive_function' => $dbCompany->safety_sensitive_function ?? false
                ];
                
                // Send email
                Mail::to($dbCompany->email)->send(new EmploymentVerification(
                    $masterCompany->company_name,
                    $driverName,
                    $employmentData,
                    $token,
                    $this->driverId,
                    $dbCompany->id
                ));

                // Update email_sent flag only for successfully sent emails
                $dbCompany->update(['email_sent' => true]);

                // Update in memory
                foreach ($this->employment_companies as $index => $company) {
                    if (!empty($company['id']) && $company['id'] == $dbCompany->id) {
                        $this->employment_companies[$index]['email_sent'] = true;
                        break;
                    }
                }

                DB::commit();
                $successCount++;

                Log::info('Verification email sent successfully in bulk', [
                    'company_id' => $dbCompany->id,
                    'email' => $dbCompany->email
                ]);
                
            } catch (\Swift_TransportException $e) {
                DB::rollBack();
                $failureCount++;
                $failedCompanies[] = [
                    'id' => $dbCompany->id,
                    'name' => $masterCompany->company_name,
                    'email' => $dbCompany->email
                ];
                Log::error('SMTP error in bulk email send', [
                    'company_id' => $dbCompany->id,
                    'error' => $e->getMessage()
                ]);
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                DB::rollBack();
                $failureCount++;
                $failedCompanies[] = [
                    'id' => $dbCompany->id,
                    'name' => $masterCompany->company_name,
                    'email' => $dbCompany->email
                ];
                Log::error('Mail transport error in bulk email send', [
                    'company_id' => $dbCompany->id,
                    'error' => $e->getMessage()
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                $failureCount++;
                $failedCompanies[] = [
                    'id' => $dbCompany->id,
                    'name' => $masterCompany->company_name,
                    'email' => $dbCompany->email
                ];
                Log::error('Error in bulk email send', [
                    'company_id' => $dbCompany->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Bulk verification email send completed', [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'failed_companies' => $failedCompanies
        ]);
        
        // Reload data from database to ensure UI reflects current state
        if ($successCount > 0) {
            $this->loadExistingData();
        }

        // Display summary message
        if ($successCount > 0 && $failureCount === 0) {
            session()->flash('success', "$successCount verification email(s) sent successfully");
        } elseif ($successCount > 0 && $failureCount > 0) {
            $failedNames = implode(', ', array_column($failedCompanies, 'name'));
            session()->flash('warning', "$successCount email(s) sent successfully, but $failureCount failed. Failed companies: $failedNames. You can retry sending to failed companies individually.");
        } else {
            session()->flash('error', "Failed to send $failureCount verification email(s). Please check the logs and try again.");
        }
    }

    /**
     * Get employment coverage data as a computed property
     * 
     * @return array
     */
    public function getEmploymentCoverageProperty()
    {
        return $this->calculateEmploymentCoverage();
    }

    // Render
    public function render()
    {
        return view('livewire.driver.steps.employment-history-step', [
            'usStates' => Constants::usStates(),
        ]);
    }
}
