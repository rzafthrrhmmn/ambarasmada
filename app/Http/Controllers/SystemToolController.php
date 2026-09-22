<?php

namespace App\Http\Controllers;

use App\Models\BackupLog;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SystemToolController extends Controller
{
    public function backups(Request $request)
    {
        $logs = BackupLog::query()->with(['createdBy'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return inertia('SystemTools/Backups', [
            'logs' => $logs,
            'user' => Auth::user(),
        ]);
    }

    public function createBackup(Request $request)
    {
        $request->validate([
            'keterangan' => 'nullable|string|max:255',
        ]);

        $backupPath = 'backups/' . now()->format('Y-m-d_H-i-s') . '.sql';

        try {
            $output = [];
            $returnVar = 0;
            $dbPath = database_path('database.sqlite');
            exec('sqlite3 ' . escapeshellarg($dbPath) . ' .dump > ' . escapeshellarg(storage_path('app/' . $backupPath)), $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception('Backup command failed');
            }

            $size = file_exists(storage_path('app/' . $backupPath)) ? filesize(storage_path('app/' . $backupPath)) : 0;

            BackupLog::create([
                'created_by' => Auth::id(),
                'keterangan' => $request->keterangan,
                'file_path' => $backupPath,
                'size' => $size,
            ]);

            return redirect()->back()->with('success', 'Backup created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function webhooks(Request $request)
    {
        $webhooks = Webhook::query()->with(['logs' => fn($q) => $q->latest()->limit(5)])
            ->paginate(20);

        return inertia('SystemTools/Webhooks', [
            'webhooks' => $webhooks,
            'user' => Auth::user(),
        ]);
    }

    public function storeWebhook(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'url' => 'required|url',
            'event' => 'nullable|string|max:255',
            'headers' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        Webhook::create([
            'created_by' => Auth::id(),
            'nama' => $request->nama,
            'url' => $request->url,
            'event' => $request->event,
            'headers' => $request->headers,
            'is_active' => $request->is_active,
        ]);

        return redirect()->back()->with('success', 'Webhook created successfully.');
    }

    public function triggerWebhook(Webhook $webhook)
    {
        $response = Http::timeout(30)->post($webhook->url, [
            'event' => $webhook->event ?? 'generic',
            'timestamp' => now()->toIso8601String(),
            'data' => [],
        ]);

        WebhookLog::create([
            'webhook_id' => $webhook->id,
            'payload' => ['event' => $webhook->event, 'timestamp' => now()],
            'status_code' => $response->status(),
            'response' => $response->body(),
            'success' => $response->successful(),
        ]);

        return redirect()->back()->with('success', 'Webhook triggered. Status: ' . $response->status());
    }

    public function deleteWebhook(Webhook $webhook)
    {
        $webhook->delete();

        return redirect()->back()->with('success', 'Webhook deleted successfully.');
    }
}
