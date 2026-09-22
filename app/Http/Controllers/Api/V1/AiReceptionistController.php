<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Assistive FAQ / booking stub for AI Receptionist module.
 * Does not diagnose, prescribe, or invent clinical advice.
 */
class AiReceptionistController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $org = Organization::query()->where('tenant_id', $user?->tenant_id)->orderBy('id')->first();
        $clinic = Clinic::query()->where('tenant_id', $user?->tenant_id)->orderBy('id')->first();
        $name = $org?->name ?? $clinic?->name ?? 'the clinic';
        $message = mb_strtolower($data['message']);

        $reply = 'I can help with hours, services, and booking questions for '.$name.'. '
            .'This assistant is informational only — not a diagnosis. '
            .'Ask about opening hours, services, or how to book an appointment.';

        if (str_contains($message, 'hour') || str_contains($message, 'open')) {
            $reply = $name.' working hours are managed in clinic settings. '
                .'Please check the public provider page or ask the front desk for today’s schedule.';
        } elseif (str_contains($message, 'book') || str_contains($message, 'appointment')) {
            $reply = 'You can book online from the public clinic page or ask reception to schedule a visit. '
                .'I cannot confirm clinical urgency — seek emergency care if this is urgent.';
        } elseif (str_contains($message, 'price') || str_contains($message, 'cost') || str_contains($message, 'fee')) {
            $reply = 'Service prices are listed under clinic services when published. '
                .'Contact the clinic for package pricing — I do not invent fees.';
        }

        return response()->json([
            'data' => [
                'reply' => $reply,
                'suggested_actions' => [
                    ['label' => 'View hours', 'href' => '/clinic/settings/hours'],
                    ['label' => 'Book appointment', 'href' => '/clinic'],
                    ['label' => 'Public page', 'href' => $clinic?->slug ? '/provider/'.$clinic->slug : '/clinic/website'],
                ],
                'disclaimer' => 'Assistive only — not a diagnosis or medical advice.',
            ],
        ]);
    }
}
