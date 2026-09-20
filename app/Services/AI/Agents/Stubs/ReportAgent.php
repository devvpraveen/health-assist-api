<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;

class ReportAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'report';
    }

    public function name(): string
    {
        return 'Report AI';
    }

    public function description(): string
    {
        return 'Assistive medical report interpretation. Distinguishes extracted facts from interpretation; never auto-approves.';
    }

    public function defaultFeature(): string
    {
        return 'report.interpretation';
    }

    public function defaultTaskType(): string
    {
        return 'medical_document';
    }
}
