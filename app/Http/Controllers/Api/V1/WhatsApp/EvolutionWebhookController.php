<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEvolutionWhatsAppWebhookJob;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppWebhookEvent;
use App\Services\WhatsApp\EvolutionWebhookParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $accountUuid,
        EvolutionWebhookParser $parser,
    ): JsonResponse {
        $account = WhatsAppAccount::query()
            ->withoutGlobalScopes()
            ->where('uuid', $accountUuid)
            ->where('is_active', true)
            ->first();

        if ($account === null) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (! $this->verifySecret($request, $account)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $eventName = $parser->normalizeEventName(
            (string) ($payload['event'] ?? $payload['type'] ?? 'unknown')
        );

        $event = WhatsAppWebhookEvent::query()->create([
            'account_id' => $account->id,
            'tenant_id' => $account->tenant_id,
            'event' => $eventName !== '' ? $eventName : 'unknown',
            'payload' => $payload,
        ]);

        $sync = app()->environment('testing')
            || (bool) config('whatsapp.process_sync')
            || config('queue.default') === 'sync';

        try {
            if ($sync) {
                ProcessEvolutionWhatsAppWebhookJob::dispatchSync($event->id);
            } else {
                ProcessEvolutionWhatsAppWebhookJob::dispatch($event->id);
            }
        } catch (\Throwable $e) {
            Log::error('Evolution webhook processing failed', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            // Still acknowledge to Evolution to avoid endless retries for app bugs;
            // event row retains error from processor when available.
        }

        return response()->json(['ok' => true, 'event_uuid' => $event->uuid]);
    }

    private function verifySecret(Request $request, WhatsAppAccount $account): bool
    {
        $expected = $account->webhook_secret
            ?: config('whatsapp.webhook_secret');

        if ($expected === null || $expected === '') {
            // No secret configured: allow in local/testing only.
            return app()->environment(['local', 'testing']);
        }

        $provided = $request->header('apikey')
            ?? $request->header('x-evolution-secret')
            ?? $request->query('token');

        if (! is_string($provided) || $provided === '') {
            return false;
        }

        return hash_equals((string) $expected, $provided);
    }
}
