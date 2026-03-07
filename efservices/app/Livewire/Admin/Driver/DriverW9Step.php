<?php

namespace App\Livewire\Admin\Driver;

use Livewire\Component;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverW9Form;
use App\Services\W9PdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriverW9Step extends Component
{
    public $driverId;

    // Form fields
    public $name = '';
    public $business_name = '';
    public $tax_classification = '';
    public $llc_classification = '';
    public $other_classification = '';
    public $has_foreign_partners = false;
    public $exempt_payee_code = '';
    public $fatca_exemption_code = '';
    public $address = '';
    public $city = '';
    public $state = '';
    public $zip_code = '';
    public $account_numbers = '';
    public $tin_type = 'ssn';
    public $tin = '';

    // State flags
    public $saved = false;
    public $formId = null;
    public $pdfPath = null;
    public $generating = false;

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'tax_classification' => 'required|in:individual,c_corporation,s_corporation,partnership,trust_estate,llc,other',
            'llc_classification' => 'nullable|required_if:tax_classification,llc|in:C,S,P',
            'other_classification' => 'nullable|required_if:tax_classification,other|string|max:255',
            'has_foreign_partners' => 'boolean',
            'exempt_payee_code' => 'nullable|string|max:50',
            'fatca_exemption_code' => 'nullable|string|max:50',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|size:2',
            'zip_code' => 'required|string|max:10',
            'account_numbers' => 'nullable|string|max:255',
            'tin_type' => 'required|in:ssn,ein',
            'tin' => 'required|string',
        ];

        // Dynamic TIN validation based on type
        if ($this->tin_type === 'ssn') {
            $rules['tin'] = 'required|regex:/^\d{3}-?\d{2}-?\d{4}$/';
        } else {
            $rules['tin'] = 'required|regex:/^\d{2}-?\d{7}$/';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'name.required' => 'Name is required.',
            'tax_classification.required' => 'Please select a tax classification.',
            'llc_classification.required_if' => 'LLC tax classification letter is required (C, S, or P).',
            'other_classification.required_if' => 'Please specify the other classification.',
            'address.required' => 'Address is required.',
            'city.required' => 'City is required.',
            'state.required' => 'State is required.',
            'state.size' => 'State must be a 2-letter code.',
            'zip_code.required' => 'ZIP code is required.',
            'tin.required' => 'Taxpayer Identification Number is required.',
            'tin.regex' => $this->tin_type === 'ssn'
                ? 'SSN must be in format XXX-XX-XXXX or XXXXXXXXX.'
                : 'EIN must be in format XX-XXXXXXX or XXXXXXXXX.',
        ];
    }

    public function mount($driverId = null)
    {
        $this->driverId = $driverId;
        if ($this->driverId) {
            $this->loadExistingData();
        }
    }

    protected function loadExistingData()
    {
        $userDriverDetail = UserDriverDetail::find($this->driverId);
        if (!$userDriverDetail) {
            return;
        }

        $w9 = $userDriverDetail->w9Form;
        if ($w9) {
            $this->formId = $w9->id;
            $this->name = $w9->name;
            $this->business_name = $w9->business_name ?? '';
            $this->tax_classification = $w9->tax_classification;
            $this->llc_classification = $w9->llc_classification ?? '';
            $this->other_classification = $w9->other_classification ?? '';
            $this->has_foreign_partners = $w9->has_foreign_partners;
            $this->exempt_payee_code = $w9->exempt_payee_code ?? '';
            $this->fatca_exemption_code = $w9->fatca_exemption_code ?? '';
            $this->address = $w9->address;
            $this->city = $w9->city;
            $this->state = $w9->state;
            $this->zip_code = $w9->zip_code;
            $this->account_numbers = $w9->account_numbers ?? '';
            $this->tin_type = $w9->tin_type;
            // Show masked TIN for security
            $decryptedTin = $w9->tin_encrypted;
            $this->tin = $decryptedTin;
            $this->pdfPath = $w9->pdf_path;
            $this->saved = true;
        } else {
            // Pre-fill with driver's data if available
            $user = $userDriverDetail->user;
            if ($user) {
                $this->name = $user->name . ' ' . ($userDriverDetail->last_name ?? '');
            }
            // Pre-fill address from driver's current address
            if ($userDriverDetail->application) {
                $currentAddress = $userDriverDetail->application->addresses()->orderBy('id', 'desc')->first();
                if ($currentAddress) {
                    $this->address = $currentAddress->address_line1 ?? '';
                    $this->city = $currentAddress->city ?? '';
                    $this->state = $currentAddress->state ?? '';
                    $this->zip_code = $currentAddress->zip_code ?? '';
                }
            }
        }
    }

    public function updatedTaxClassification()
    {
        if ($this->tax_classification !== 'llc') {
            $this->llc_classification = '';
        }
        if ($this->tax_classification !== 'other') {
            $this->other_classification = '';
        }
        // Reset foreign partners if not applicable
        if (!in_array($this->tax_classification, ['partnership', 'trust_estate', 'llc'])) {
            $this->has_foreign_partners = false;
        }
    }

    public function save()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $userDriverDetail = UserDriverDetail::find($this->driverId);
            if (!$userDriverDetail) {
                throw new \Exception('Driver not found');
            }

            $tinDigits = preg_replace('/\D/', '', $this->tin);

            $data = [
                'name' => $this->name,
                'business_name' => $this->business_name ?: null,
                'tax_classification' => $this->tax_classification,
                'llc_classification' => $this->tax_classification === 'llc' ? $this->llc_classification : null,
                'other_classification' => $this->tax_classification === 'other' ? $this->other_classification : null,
                'has_foreign_partners' => $this->has_foreign_partners,
                'exempt_payee_code' => $this->exempt_payee_code ?: null,
                'fatca_exemption_code' => $this->fatca_exemption_code ?: null,
                'address' => $this->address,
                'city' => $this->city,
                'state' => strtoupper($this->state),
                'zip_code' => $this->zip_code,
                'account_numbers' => $this->account_numbers ?: null,
                'tin_type' => $this->tin_type,
                'tin_encrypted' => $tinDigits,
                'signed_date' => now()->toDateString(),
            ];

            $w9 = $userDriverDetail->w9Form()->updateOrCreate([], $data);

            // Generate PDF
            try {
                $pdfService = app(W9PdfService::class);
                $pdfPath = $pdfService->generate($w9);
                $w9->update(['pdf_path' => $pdfPath]);
                $this->pdfPath = $pdfPath;

                // Save to Spatie Media Library so it appears in driver documents section
                if (file_exists($pdfPath)) {
                    $userDriverDetail->clearMediaCollection('w9_documents');
                    $userDriverDetail->addMedia($pdfPath)
                        ->preservingOriginal()
                        ->usingFileName('W9_' . str_replace(' ', '_', $this->name) . '_' . now()->format('Y-m-d') . '.pdf')
                        ->toMediaCollection('w9_documents');
                }
            } catch (\Exception $e) {
                Log::warning('W9 PDF generation failed, data saved successfully', [
                    'error' => $e->getMessage(),
                    'w9_id' => $w9->id,
                ]);
                session()->flash('warning', 'W-9 data saved, but PDF generation failed: ' . $e->getMessage());
            }

            $this->formId = $w9->id;
            $this->saved = true;

            // Update current step
            $userDriverDetail->update(['current_step' => 13]);

            DB::commit();
            session()->flash('success', 'W-9 form saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving W-9 form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Error saving W-9 form: ' . $e->getMessage());
        }
    }

    public function next()
    {
        $this->validate();

        if ($this->driverId) {
            $this->save();
        }

        $this->dispatch('nextStep');
    }

    public function previous()
    {
        $this->dispatch('prevStep');
    }

    public function saveAndExit()
    {
        if ($this->driverId) {
            $this->save();
        }

        $this->dispatch('saveAndExit');
    }

    public function render()
    {
        return view('livewire.admin.driver.steps.driver-w9-step');
    }
}
