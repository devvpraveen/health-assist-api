<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use App\Models\WhatsAppConversation;
use Illuminate\Foundation\Http\FormRequest;

class SendWhatsAppStaffMessageRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:4096'],
        ];
    }
}
