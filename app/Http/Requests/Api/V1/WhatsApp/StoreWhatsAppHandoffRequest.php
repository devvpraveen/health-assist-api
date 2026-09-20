<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppHandoff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWhatsAppHandoffRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var WhatsAppConversation $conversation */
        $conversation = $this->route('conversation');

        return $this->user()?->can('manage', $conversation) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'summary' => ['nullable', 'string', 'max:5000'],
            'urgency' => ['nullable', 'string', 'max:50'],
            'requested_by' => ['nullable', 'string', Rule::in([
                WhatsAppHandoff::REQUESTED_BY_PATIENT,
                WhatsAppHandoff::REQUESTED_BY_SYSTEM,
                WhatsAppHandoff::REQUESTED_BY_STAFF,
            ])],
        ];
    }
}
