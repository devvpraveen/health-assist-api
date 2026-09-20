<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Medical content disclaimer
    |--------------------------------------------------------------------------
    |
    | Shown on public SEO health topic pages. Content is educational and is
    | not a diagnosis or a substitute for professional medical advice.
    |
    */

    'disclaimer' => 'This information from Health Assist is for education only and is not a diagnosis, treatment plan, or substitute for professional medical advice. Seek care from a licensed clinician for personal health concerns.',

    'entity_types' => [
        'condition',
        'symptom',
        'treatment',
        'specialty',
        'service',
        'location',
        'glossary',
        'faq_topic',
    ],

    'entity_statuses' => [
        'draft',
        'in_review',
        'published',
        'archived',
    ],

    'relation_types' => [
        'related',
        'symptom_of',
        'treats',
        'specialty_for',
        'faq_for',
    ],

];
