<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ClinicalDocumentWorkflow
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_CLINICIAN_REVIEW = 'clinician_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_CLINICIAN_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_ARCHIVED,
    ];

    public const SOURCE_CLINICIAN = 'clinician';

    public const SOURCE_AI_ASSISTED = 'ai_assisted';

    public const SOURCES = [
        self::SOURCE_CLINICIAN,
        self::SOURCE_AI_ASSISTED,
    ];

    /**
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        self::STATUS_DRAFT => [
            self::STATUS_CLINICIAN_REVIEW,
            self::STATUS_ARCHIVED,
        ],
        self::STATUS_CLINICIAN_REVIEW => [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_DRAFT,
        ],
        self::STATUS_APPROVED => [
            self::STATUS_ARCHIVED,
        ],
        self::STATUS_REJECTED => [
            self::STATUS_DRAFT,
            self::STATUS_CLINICIAN_REVIEW,
            self::STATUS_ARCHIVED,
        ],
        self::STATUS_ARCHIVED => [],
    ];

    public static function assertEditable(Model $document): void
    {
        if ($document->getAttribute('status') === self::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'status' => ['Approved clinical documents cannot be edited. Archive and create a new version instead.'],
            ]);
        }

        if ($document->getAttribute('status') === self::STATUS_ARCHIVED) {
            throw ValidationException::withMessages([
                'status' => ['Archived clinical documents cannot be edited.'],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function applyTransition(Model $document, string $newStatus): array
    {
        $current = (string) $document->getAttribute('status');
        $allowed = self::TRANSITIONS[$current] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot transition from {$current} to {$newStatus}."],
            ]);
        }

        $payload = ['status' => $newStatus];

        if ($newStatus === self::STATUS_APPROVED) {
            $payload['approved_by_user_id'] = Auth::id();
            $payload['approved_at'] = now();
        }

        if (in_array($newStatus, [self::STATUS_DRAFT, self::STATUS_CLINICIAN_REVIEW, self::STATUS_REJECTED], true)) {
            $payload['approved_by_user_id'] = null;
            $payload['approved_at'] = null;
        }

        return $payload;
    }
}
