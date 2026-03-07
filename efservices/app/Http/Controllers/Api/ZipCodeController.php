<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ZipCodeController extends Controller
{
    /**
     * Validate ZIP code for a specific state
     */
    public function validateZipForState(Request $request): JsonResponse
    {
        $request->validate([
            'zip_code' => 'required|string|regex:/^\d{5}(-\d{4})?$/',
            'state' => 'required|string|size:2'
        ]);

        $zipCode = substr($request->zip_code, 0, 5);
        $state = strtoupper($request->state);
        
        $isValid = $this->isValidZipForState($zipCode, $state);
        
        return response()->json([
            'valid' => $isValid,
            'message' => $isValid 
                ? "ZIP code {$request->zip_code} is valid for {$state}."
                : "ZIP code {$request->zip_code} is not valid for {$state}."
        ]);
    }

    /**
     * Check if ZIP code is valid for the given state
     */
    private function isValidZipForState(string $zipCode, string $state): bool
    {
        // Comprehensive ZIP code ranges for US states
        $zipRanges = [
            'AL' => [[35004, 36925]],
            'AK' => [[99501, 99950]],
            'AZ' => [[85001, 86556]],
            'AR' => [[71601, 72959]],
            'CA' => [[90001, 96162]],
            'CO' => [[80001, 81658]],
            'CT' => [[6001, 6928]],
            'DE' => [[19701, 19980]],
            'FL' => [[32003, 34997]],
            'GA' => [[30002, 39901]],
            'HI' => [[96701, 96898]],
            'ID' => [[83201, 83877]],
            'IL' => [[60001, 62999]],
            'IN' => [[46001, 47997]],
            'IA' => [[50001, 52809]],
            'KS' => [[66002, 67954]],
            'KY' => [[40003, 42788]],
            'LA' => [[70001, 71497]],
            'ME' => [[3901, 4992]],
            'MD' => [[20588, 21930]],
            'MA' => [[1001, 5544]],
            'MI' => [[48001, 49971]],
            'MN' => [[55001, 56763]],
            'MS' => [[38601, 39776]],
            'MO' => [[63001, 65899]],
            'MT' => [[59001, 59937]],
            'NE' => [[68001, 69367]],
            'NV' => [[88901, 89883]],
            'NH' => [[3031, 3897]],
            'NJ' => [[7001, 8989]],
            'NM' => [[87001, 88441]],
            'NY' => [[10001, 14925]],
            'NC' => [[27006, 28909]],
            'ND' => [[58001, 58856]],
            'OH' => [[43001, 45999]],
            'OK' => [[73001, 74966]],
            'OR' => [[97001, 97920]],
            'PA' => [[15001, 19640]],
            'RI' => [[2801, 2940]],
            'SC' => [[29001, 29948]],
            'SD' => [[57001, 57799]],
            'TN' => [[37010, 38589]],
            'TX' => [[73301, 88595]],
            'UT' => [[84001, 84791]],
            'VT' => [[5001, 5907]],
            'VA' => [[20101, 24658]],
            'WA' => [[98001, 99403]],
            'WV' => [[24701, 26886]],
            'WI' => [[53001, 54990]],
            'WY' => [[82001, 83128]]
        ];

        if (!isset($zipRanges[$state])) {
            return false;
        }

        $zipNum = (int) $zipCode;
        
        foreach ($zipRanges[$state] as $range) {
            if ($zipNum >= $range[0] && $zipNum <= $range[1]) {
                return true;
            }
        }

        return false;
    }
}