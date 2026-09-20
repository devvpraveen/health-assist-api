<?php

namespace App\Services\WhatsApp;

/**
 * Normalize Evolution API webhook payloads into structured inbound messages.
 */
class EvolutionWebhookParser
{
    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{
     *     event: string,
     *     instance: string|null,
     *     remote_jid: string|null,
     *     remote_phone: string,
     *     from_me: bool,
     *     message_id: string|null,
     *     type: string,
     *     text: string|null,
     *     media_stub: array<string, mixed>|null,
     *     raw: array<string, mixed>
     * }>
     */
    public function parseMessages(array $payload): array
    {
        $event = $this->normalizeEventName(
            (string) ($payload['event'] ?? $payload['type'] ?? data_get($payload, 'data.event') ?? '')
        );

        if (! in_array($event, ['MESSAGES_UPSERT', 'messages.upsert'], true)
            && ! str_ends_with(strtoupper(str_replace('.', '_', $event)), 'MESSAGES_UPSERT')) {
            // Still attempt to parse if data looks like messages.upsert
            if (! isset($payload['data']['messages']) && ! isset($payload['data']['key'])) {
                return [];
            }
            $event = 'MESSAGES_UPSERT';
        }

        $instance = $payload['instance']
            ?? data_get($payload, 'data.instance')
            ?? data_get($payload, 'instanceName');

        $data = $payload['data'] ?? $payload;
        $messages = [];

        if (isset($data['messages']) && is_array($data['messages'])) {
            foreach ($data['messages'] as $message) {
                if (is_array($message)) {
                    $parsed = $this->parseOne($message, $event, is_string($instance) ? $instance : null);
                    if ($parsed !== null) {
                        $messages[] = $parsed;
                    }
                }
            }
        } elseif (isset($data['key']) || isset($data['message'])) {
            $parsed = $this->parseOne($data, $event, is_string($instance) ? $instance : null);
            if ($parsed !== null) {
                $messages[] = $parsed;
            }
        }

        return $messages;
    }

    public function normalizeEventName(string $event): string
    {
        $event = trim($event);
        if ($event === '') {
            return '';
        }

        $upper = strtoupper(str_replace(['.', '-'], '_', $event));

        return match (true) {
            str_contains($upper, 'MESSAGES_UPSERT') => 'MESSAGES_UPSERT',
            str_contains($upper, 'CONNECTION_UPDATE') => 'CONNECTION_UPDATE',
            default => $event,
        };
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{
     *     event: string,
     *     instance: string|null,
     *     remote_jid: string|null,
     *     remote_phone: string,
     *     from_me: bool,
     *     message_id: string|null,
     *     type: string,
     *     text: string|null,
     *     media_stub: array<string, mixed>|null,
     *     raw: array<string, mixed>
     * }|null
     */
    private function parseOne(array $message, string $event, ?string $instance): ?array
    {
        $key = $message['key'] ?? [];
        $remoteJid = is_array($key) ? ($key['remoteJid'] ?? null) : null;
        $fromMe = (bool) (is_array($key) ? ($key['fromMe'] ?? false) : false);
        $messageId = is_array($key) ? ($key['id'] ?? null) : null;

        $phone = PhoneNormalizer::fromRemoteJid(is_string($remoteJid) ? $remoteJid : null);
        if ($phone === '' && isset($message['pushName'])) {
            // cannot match without phone
        }

        if ($phone === '') {
            return null;
        }

        $body = $message['message'] ?? [];
        if (! is_array($body)) {
            $body = [];
        }

        [$type, $text, $mediaStub] = $this->extractContent($body);

        return [
            'event' => $event,
            'instance' => $instance,
            'remote_jid' => is_string($remoteJid) ? $remoteJid : null,
            'remote_phone' => $phone,
            'from_me' => $fromMe,
            'message_id' => is_string($messageId) ? $messageId : null,
            'type' => $type,
            'text' => $text,
            'media_stub' => $mediaStub,
            'raw' => $this->sanitizePayload($message),
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{0: string, 1: string|null, 2: array<string, mixed>|null}
     */
    private function extractContent(array $body): array
    {
        if (isset($body['conversation']) && is_string($body['conversation'])) {
            return ['text', $body['conversation'], null];
        }

        if (isset($body['extendedTextMessage']['text']) && is_string($body['extendedTextMessage']['text'])) {
            return ['text', $body['extendedTextMessage']['text'], null];
        }

        if (isset($body['imageMessage'])) {
            $caption = data_get($body, 'imageMessage.caption');

            return [
                'image',
                is_string($caption) ? $caption : null,
                ['mimetype' => data_get($body, 'imageMessage.mimetype'), 'stub' => true],
            ];
        }

        if (isset($body['documentMessage'])) {
            $caption = data_get($body, 'documentMessage.caption')
                ?? data_get($body, 'documentMessage.fileName');

            return [
                'document',
                is_string($caption) ? $caption : null,
                ['mimetype' => data_get($body, 'documentMessage.mimetype'), 'stub' => true],
            ];
        }

        if (isset($body['audioMessage'])) {
            return ['audio', null, ['mimetype' => data_get($body, 'audioMessage.mimetype'), 'stub' => true]];
        }

        if (isset($body['locationMessage'])) {
            return ['location', null, [
                'lat' => data_get($body, 'locationMessage.degreesLatitude'),
                'lng' => data_get($body, 'locationMessage.degreesLongitude'),
                'stub' => true,
            ]];
        }

        return ['other', null, ['stub' => true]];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $copy = $payload;
        unset($copy['message']['imageMessage']['jpegThumbnail']);
        unset($copy['message']['documentMessage']['jpegThumbnail']);

        // Drop large base64 blobs if present
        array_walk_recursive($copy, function (&$value): void {
            if (is_string($value) && strlen($value) > 2048) {
                $value = '[truncated]';
            }
        });

        return $copy;
    }
}
