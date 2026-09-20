<?php

namespace App\Services\AI;

/**
 * Canonical Layer C workforce keys (spec) plus product extras.
 */
final class LayerCCatalog
{
    /** @var list<string> */
    public const LAYER_C_KEYS = [
        'patient',
        'report',
        'clinical',
        'physiotherapy',
        'receptionist',
        'appointment',
        'follow_up',
        'billing',
        'marketing',
        'review',
        'analytics',
        'provider_recommendation',
        'lead_qualification',
        'voice',
        'admin',
    ];

    /** @var array<string, string> */
    public const LAYERS = [
        'patient' => 'patient',
        'health_guide' => 'patient',
        'report' => 'clinical',
        'clinical' => 'clinical',
        'physiotherapy' => 'clinical',
        'medication' => 'clinical',
        'receptionist' => 'business',
        'appointment' => 'business',
        'follow_up' => 'clinical',
        'billing' => 'business',
        'marketing' => 'business',
        'review' => 'clinical',
        'analytics' => 'business',
        'provider_recommendation' => 'patient',
        'lead_qualification' => 'business',
        'voice' => 'business',
        'admin' => 'business',
        'wellness' => 'patient',
    ];

    /** @var array<string, string> */
    public const MATURITY = [
        'health_guide' => 'production',
        'report' => 'production',
        'receptionist' => 'production',
        'follow_up' => 'beta',
        'patient' => 'stub',
        'clinical' => 'stub',
        'physiotherapy' => 'stub',
        'appointment' => 'stub',
        'billing' => 'stub',
        'marketing' => 'stub',
        'review' => 'stub',
        'analytics' => 'stub',
        'provider_recommendation' => 'stub',
        'lead_qualification' => 'stub',
        'voice' => 'stub',
        'admin' => 'stub',
        'medication' => 'stub',
        'wellness' => 'stub',
    ];
}
