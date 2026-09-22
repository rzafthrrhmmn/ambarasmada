<?php

namespace App\Http\Controllers;

use App\Models\Finance;
use App\Models\Member;
use Illuminate\Http\Request;
use Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function financePdf(Request $request): BinaryFileResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $finances = Finance::with(['member', 'category', 'period'])
            ->where('status', 'Posted')
            ->orderByDesc('tgl_transaksi')
            ->get();

        $html = view('reports.finance', [
            'finances' => $finances,
            'balance' => Finance::where('status', 'Posted')->where('jenis_transaksi', 'Masuk')->sum('nominal')
                - Finance::where('status', 'Posted')->where('jenis_transaksi', 'Keluar')->sum('nominal'),
            'income' => Finance::where('status', 'Posted')->where('jenis_transaksi', 'Masuk')->sum('nominal'),
            'expense' => Finance::where('status', 'Posted')->where('jenis_transaksi', 'Keluar')->sum('nominal'),
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade::Pdf::loadHTML($html);

        return $pdf->download('laporan-keuangan-' . now()->format('Y-m-d') . '.pdf');
    }

    public function membersCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $members = Member::with('user')->get();

        $filename = 'data-anggota-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment;filename=' . $filename,
        ];

        $callback = function () use ($members) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['No', 'Nama Lengkap', 'NIM/NIS', 'Angkatan', 'Kelas', 'Tingkatan', 'Status Aktif', 'No HP', 'Email', 'Role']);

            foreach ($members as $index => $member) {
                fputcsv($file, [
                    $index + 1,
                    $member->nama_lengkap,
                    $member->nta ?? '',
                    $member->angkatan,
                    $member->kelas,
                    $member->tingkatan,
                    $member->status_aktif,
                    $member->no_hp ?? '',
                    $member->user?->email ?? '',
                    $member->user?->role ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function attendancePdf(Request $request): BinaryFileResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $sessions = \App\Models\AttendanceSession::with(['attendances.member.user', 'ambalan'])
            ->whereHas('attendances')
            ->orderByDesc('tanggal')
            ->get();

        $html = view('reports.attendance', ['sessions' => $sessions])->render();
        $pdf = \Barryvdh\DomPDF\Facade::Pdf::loadHTML($html);

        return $pdf->download('rekap-kehadiran-' . now()->format('Y-m-d') . '.pdf');
    }

    public function skuPdf(Request $request): BinaryFileResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $members = \App\Models\Member::with(['skuSubmissions.skuPoint'])->get();

        $html = view('reports.sku', ['members' => $members])->render();
        $pdf = \Barryvdh\DomPDF\Facade::Pdf::loadHTML($html);

        return $pdf->download('rekap-sku-' . now()->format('Y-m-d') . '.pdf');
    }
}
