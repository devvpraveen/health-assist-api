<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Resources\WhatsApp\WhatsAppHandoffResource;
use App\Models\WhatsAppHandoff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WhatsAppHandoffController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WhatsAppHandoff::class);

        $handoffs = WhatsAppHandoff::query()
            ->with(['conversation.patient', 'assignedUser'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->orderByDesc('id')
            ->paginate();

        return WhatsAppHandoffResource::collection($handoffs);
    }
}
