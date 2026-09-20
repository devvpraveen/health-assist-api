<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Wellness content
    |--------------------------------------------------------------------------
    |
    | Phase 11 wellness is lifestyle guidance only — not clinical advice.
    | is_clinical_advice must remain false for wellness CMS-lite content.
    |
    */

    'disclaimer' => 'Wellness content is general lifestyle guidance for education only. It is not a diagnosis, prescription, or clinical advice. Speak with a qualified clinician for medical decisions.',

    'force_non_clinical' => true,

    'recommendation' => [
        'interest_match_weight' => 40,
        'tag_overlap_weight' => 30,
        'recency_weight' => 20,
        'base_score' => 10,
    ],

];
