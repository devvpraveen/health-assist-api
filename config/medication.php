<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Medication reminder window
    |--------------------------------------------------------------------------
    |
    | Look-ahead window (minutes) when creating pending reminders for active
    | schedules. Grace minutes after scheduled_for before auto-marking missed.
    |
    */

    'reminder_lookahead_minutes' => (int) env('MEDICATION_REMINDER_LOOKAHEAD_MINUTES', 120),

    'missed_grace_minutes' => (int) env('MEDICATION_MISSED_GRACE_MINUTES', 60),

    'default_channel' => env('MEDICATION_REMINDER_CHANNEL', 'database'),

    'whatsapp_reminders_enabled' => (bool) env('MEDICATION_WHATSAPP_REMINDERS_ENABLED', true),

    'default_reminder_template' => 'Health Assist medication reminder: take {name} ({dosage}) scheduled for {scheduled_for}.',

    'default_missed_template' => 'Health Assist: a dose of {name} ({dosage}) scheduled for {scheduled_for} was marked missed. Log if you took it late.',

    'disclaimer' => 'Medication records are managed by clinicians or staff. Health Assist AI does not prescribe, change, or delete medications. Always follow your clinician\'s instructions.',

];
