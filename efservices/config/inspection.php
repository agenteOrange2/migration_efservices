<?php

/**
 * DOT/FMCSA Pre-Trip and Post-Trip Vehicle Inspection Configuration
 * Based on Federal Motor Carrier Safety Regulations (49 CFR 396.11)
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Tractor/Truck Inspection Items
    |--------------------------------------------------------------------------
    |
    | These are the standard DOT inspection items for tractors and trucks.
    | Each item must be checked during pre-trip and post-trip inspections.
    |
    */
    'tractor_items' => [
        // Column 1
        'air_compressor' => 'Air Compressor',
        'air_lines' => 'Air Lines',
        'battery' => 'Battery',
        'brake_accessories' => 'Brake Accessories',
        'brakes' => 'Brakes',
        'carburetor' => 'Carburetor',
        'clutch' => 'Clutch',
        'defroster' => 'Defroster',
        'drive_line' => 'Drive Line',
        'engine' => 'Engine',
        'fifth_wheel' => 'Fifth Wheel',
        'front_axle' => 'Front Axle',
        'fuel_tanks' => 'Fuel Tanks',
        'heater' => 'Heater',

        // Column 2
        'horn' => 'Horn',
        'lights_head_stop' => 'Lights: Head - Stop',
        'lights_tail_dash' => 'Lights: Tail - Dash',
        'lights_turn_indicators' => 'Lights: Turn Indicators',
        'mirrors' => 'Mirrors',
        'muffler' => 'Muffler',
        'oil_pressure' => 'Oil Pressure',
        'on_board_recorder' => 'On-Board Recorder',
        'radiator' => 'Radiator',
        'rear_end' => 'Rear End',
        'reflectors' => 'Reflectors',
        'safety_equipment' => 'Safety Equipment',
        'safety_fire_extinguisher' => 'Fire Extinguisher',
        'safety_flags_flares_fuses' => 'Flags - Flares - Fuses',
        'safety_spare_bulbs_fuses' => 'Spare Bulbs & Fuses',
        'safety_spare_seal_beam' => 'Spare Seal Beam',

        // Column 3
        'springs' => 'Springs',
        'starter' => 'Starter',
        'steering' => 'Steering',
        'tachograph' => 'Tachograph',
        'tires' => 'Tires',
        'transmission' => 'Transmission',
        'wheels' => 'Wheels',
        'windows' => 'Windows',
        'windshield_wipers' => 'Windshield Wipers',
        'other_tractor' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trailer Inspection Items
    |--------------------------------------------------------------------------
    |
    | These items are only required when a trailer is attached to the trip.
    |
    */
    'trailer_items' => [
        'brake_connections' => 'Brake Connections',
        'brakes_trailer' => 'Brakes',
        'coupling_chains' => 'Coupling Chains',
        'coupling_king_pin' => 'Coupling (King) Pin',
        'doors' => 'Doors',
        'hitch' => 'Hitch',
        'landing_gear' => 'Landing Gear',
        'lights_all' => 'Lights - All',
        'roof' => 'Roof',
        'springs_trailer' => 'Springs',
        'tarpaulin' => 'Tarpaulin',
        'tires_trailer' => 'Tires',
        'wheels_trailer' => 'Wheels',
        'other_trailer' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tractor Items by Column (for 3-column layout)
    |--------------------------------------------------------------------------
    |
    | Organizes tractor items into 3 columns for the UI display.
    |
    */
    'tractor_columns' => [
        1 => [
            'air_compressor',
            'air_lines',
            'battery',
            'brake_accessories',
            'brakes',
            'carburetor',
            'clutch',
            'defroster',
            'drive_line',
            'engine',
            'fifth_wheel',
            'front_axle',
            'fuel_tanks',
            'heater',
        ],
        2 => [
            'horn',
            'lights_head_stop',
            'lights_tail_dash',
            'lights_turn_indicators',
            'mirrors',
            'muffler',
            'oil_pressure',
            'on_board_recorder',
            'radiator',
            'rear_end',
            'reflectors',
            'safety_equipment',
            'safety_fire_extinguisher',
            'safety_flags_flares_fuses',
            'safety_spare_bulbs_fuses',
            'safety_spare_seal_beam',
        ],
        3 => [
            'springs',
            'starter',
            'steering',
            'tachograph',
            'tires',
            'transmission',
            'wheels',
            'windows',
            'windshield_wipers',
            'other_tractor',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Trailer Items by Column (for 3-column layout)
    |--------------------------------------------------------------------------
    |
    | Organizes trailer items into 3 columns for the UI display.
    |
    */
    'trailer_columns' => [
        1 => [
            'brake_connections',
            'brakes_trailer',
            'coupling_chains',
            'coupling_king_pin',
            'doors',
        ],
        2 => [
            'hitch',
            'landing_gear',
            'lights_all',
            'roof',
            'springs_trailer',
        ],
        3 => [
            'tarpaulin',
            'tires_trailer',
            'wheels_trailer',
            'other_trailer',
        ],
    ],
];
