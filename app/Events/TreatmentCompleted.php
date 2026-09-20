<?php

namespace App\Events;

use App\Models\ClinicalTreatmentPlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TreatmentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public ClinicalTreatmentPlan $treatmentPlan) {}
}
