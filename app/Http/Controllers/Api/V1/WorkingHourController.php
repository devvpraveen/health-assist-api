<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkingHourResource;
use App\Models\Clinic;
use App\Models\WorkingHour;
use App\Models\WorkingHourException;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class WorkingHourController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clinicId = $request->integer('clinic_id') ?: null;

        $hours = WorkingHour::query()
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->orderBy('day_of_week')
            ->orderBy('shift_index')
            ->get();

        $exceptions = WorkingHourException::query()
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => [
                'hours' => WorkingHourResource::collection($hours),
                'exceptions' => $exceptions,
            ],
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
            'hours' => ['required', 'array', 'min:1'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'hours.*.opens_at' => ['required', 'date_format:H:i'],
            'hours.*.closes_at' => ['required', 'date_format:H:i'],
            'hours.*.break_starts_at' => ['nullable', 'date_format:H:i'],
            'hours.*.break_ends_at' => ['nullable', 'date_format:H:i'],
            'hours.*.shift_index' => ['nullable', 'integer', 'min:1', 'max:5'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
            'hours.*.label' => ['nullable', 'string', 'max:80'],
        ]);

        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;
        abort_unless($tenantId, 422, 'Tenant required');

        $clinicId = $data['clinic_id'] ?? Clinic::query()->where('tenant_id', $tenantId)->orderBy('id')->value('id');

        WorkingHour::query()
            ->where('tenant_id', $tenantId)
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->delete();

        $created = [];
        foreach ($data['hours'] as $row) {
            $created[] = WorkingHour::query()->create([
                'tenant_id' => $tenantId,
                'clinic_id' => $clinicId,
                'day_of_week' => $row['day_of_week'],
                'opens_at' => $row['opens_at'],
                'closes_at' => $row['closes_at'],
                'break_starts_at' => $row['break_starts_at'] ?? null,
                'break_ends_at' => $row['break_ends_at'] ?? null,
                'shift_index' => $row['shift_index'] ?? 1,
                'is_closed' => (bool) ($row['is_closed'] ?? false),
                'label' => $row['label'] ?? null,
            ]);
        }

        return response()->json([
            'data' => WorkingHourResource::collection(collect($created)),
        ]);
    }

    public function storeException(Request $request): JsonResponse
    {
        $data = $request->validate([
            'clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
            'date' => ['required', 'date'],
            'is_closed' => ['nullable', 'boolean'],
            'opens_at' => ['nullable', 'date_format:H:i'],
            'closes_at' => ['nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;
        $clinicId = $data['clinic_id'] ?? Clinic::query()->where('tenant_id', $tenantId)->orderBy('id')->value('id');

        $exception = WorkingHourException::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'clinic_id' => $clinicId,
                'date' => $data['date'],
            ],
            [
                'is_closed' => $data['is_closed'] ?? true,
                'opens_at' => $data['opens_at'] ?? null,
                'closes_at' => $data['closes_at'] ?? null,
                'reason' => $data['reason'] ?? null,
            ],
        );

        return response()->json(['data' => $exception], 201);
    }
}
