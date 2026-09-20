<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OCR driver
    |--------------------------------------------------------------------------
    |
    | Use "mock" for local/testing (deterministic sample lab text). "null"
    | returns empty OCR results. Real cloud OCR vendors are deferred.
    |
    */

    'ocr_driver' => env('OCR_DRIVER', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Reference ranges (simple heuristic)
    |--------------------------------------------------------------------------
    |
    | Units and bounds are illustrative for Phase 10 tests — not clinical
    | decision support. Flags are high|low|normal only.
    |
    */

    'reference_ranges' => [
        'hb' => [
            'aliases' => ['hb', 'hemoglobin', 'haemoglobin', 'hgb'],
            'unit' => 'g/dL',
            'low' => 12.0,
            'high' => 17.5,
        ],
        'wbc' => [
            'aliases' => ['wbc', 'white blood cell', 'white blood cells', 'leucocyte', 'leukocyte'],
            'unit' => 'x10^3/uL',
            'low' => 4.0,
            'high' => 11.0,
        ],
        'glucose' => [
            'aliases' => ['glucose', 'blood glucose', 'fasting glucose', 'fbs', 'rbs'],
            'unit' => 'mg/dL',
            'low' => 70.0,
            'high' => 99.0,
        ],
    ],

    'disclaimer' => 'Assistive report interpretation only. Extracted facts and AI interpretation are not a diagnosis. A clinician must review before sharing with patients.',

    'patient_explanation_disclaimer' => 'This explanation is assistive only and must be reviewed by a clinician before sharing with the patient.',

];
