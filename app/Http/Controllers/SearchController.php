<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\Letter;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));

        $results = [];

        if ($query !== '') {
            // Members
            $members = Member::where('nama_lengkap', 'like', "%{$query}%")
                ->orWhere('nta', 'like', "%{$query}%")
                ->limit(8)
                ->get(['id', 'nama_lengkap', 'nta', 'tingkatan', 'kelas', 'status_aktif'])
                ->map(fn ($m) => [
                    'type' => 'member',
                    'title' => $m->nama_lengkap,
                    'subtitle' => 'NTA '.$m->nta.' • '.($m->tingkatan ?? '-').' • '.($m->kelas ?? '-'),
                    'url' => '/members/'.$m->id,
                    'icon' => 'users',
                ]);

            // Events
            $events = Event::where('nama', 'like', "%{$query}%")
                ->limit(8)
                ->get(['id', 'nama', 'tanggal', 'lokasi'])
                ->map(fn ($e) => [
                    'type' => 'event',
                    'title' => $e->nama,
                    'subtitle' => 'Acara • '.($e->tanggal ? Carbon::parse($e->tanggal)->format('d M Y') : '-'),
                    'url' => '/events/'.$e->id,
                    'icon' => 'calendar',
                ]);

            // Letters
            $letters = Letter::where('perihal', 'like', "%{$query}%")
                ->orWhere('nomor_surat', 'like', "%{$query}%")
                ->limit(8)
                ->get(['id', 'perihal', 'nomor_surat', 'jenis_surat', 'created_at'])
                ->map(fn ($l) => [
                    'type' => 'letter',
                    'title' => $l->perihal,
                    'subtitle' => 'Surat '.$l->jenis_surat.' • No. '.($l->nomor_surat ?? '-'),
                    'url' => '/letters/'.$l->id,
                    'icon' => 'mail',
                ]);

            // Attendance sessions
            $sessions = AttendanceSession::where('nama', 'like', "%{$query}%")
                ->limit(8)
                ->get(['id', 'nama', 'tanggal', 'lokasi'])
                ->map(fn ($s) => [
                    'type' => 'session',
                    'title' => $s->nama,
                    'subtitle' => 'Sesi latihan • '.($s->tanggal ? Carbon::parse($s->tanggal)->format('d M Y') : '-'),
                    'url' => '/attendance/'.$s->id,
                    'icon' => 'clock',
                ]);

            $results = $members->concat($events)->concat($letters)->concat($sessions)->values()->all();
        }

        return Inertia::render('Search/Index', [
            'query' => $query,
            'results' => $results,
            'resultCount' => count($results),
        ]);
    }
}
