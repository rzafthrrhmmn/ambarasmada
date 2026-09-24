<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->with(['actor']);

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        $logs = $query->orderByDesc('created_at')->paginate(20);

        return inertia('AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['action', 'entity_type']),
        ]);
    }
}
