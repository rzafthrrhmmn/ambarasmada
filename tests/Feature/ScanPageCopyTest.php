<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Pesan galat presensi menyuruh anggota menekan sebuah tombol, misalnya
 * 'tekan "Catat Kehadiran" lagi'. Tombol di Scan.vue pernah tertulis
 * 'Batalkan Kehadiran', sehingga anggota yang mengikuti petunjuk tidak pernah
 * menemukan tombolnya dan menekan ulang tidak berhasil.
 *
 * Kopisan UI sulit diuji lewat browser, tapi kontrak ini bisa dijaga: setiap
 * nama tombol yang disebut di dalam pesan harus benar-benar ada di template.
 */
class ScanPageCopyTest extends TestCase
{
    private function scanVue(): string
    {
        return file_get_contents(base_path('resources/js/Pages/Attendance/Scan.vue'));
    }

    /**
     * Label tombol yang benar-benar dirender halaman scan.
     *
     * Isi tombol berada di dalam <span> dengan v-if/v-else, misalnya
     * "Menyimpan..." saat proses berjalan dan "Catat Kehadiran" saat idle.
     * Setiap cabang diambil terpisah karena hanya salah satunya yang terlihat
     * pada satu waktu.
     */
    private function renderedButtonLabels(): array
    {
        preg_match_all('/<button\b[^>]*>(.*?)<\/button>/s', $this->scanVue(), $matches);

        $labels = [];

        foreach ($matches[1] as $body) {
            // Teks langsung di luar <span> tetap menjadi label tombol.
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($body)));

            if ($plain !== '') {
                $labels[] = $plain;
            }

            if (preg_match_all('/<span\b[^>]*>(.*?)<\/span>/s', $body, $spans)) {
                foreach ($spans[1] as $span) {
                    $text = trim(preg_replace('/\s+/', ' ', strip_tags($span)));

                    if ($text !== '') {
                        $labels[] = $text;
                    }
                }
            }
        }

        return array_values(array_unique($labels));
    }

    public function test_submit_button_uses_record_attendance_label(): void
    {
        $labels = $this->renderedButtonLabels();

        $this->assertContains(
            'Catat Kehadiran',
            $labels,
            'Tombol submit presensi harus memakai label "Catat Kehadiran".'
        );

        $this->assertNotContains(
            'Batalkan Kehadiran',
            $labels,
            'Tombol submit tidak boleh memakai label "Batalkan Kehadiran", karena itu membingungkan dan tidak cocok dengan pesan galat.'
        );
    }

    public function test_every_button_named_in_instructions_exists(): void
    {
        $sources = [
            'Scan.vue' => $this->scanVue(),
            'AttendanceController.php' => file_get_contents(base_path('app/Http/Controllers/AttendanceController.php')),
            'useDevicePermissions.js' => file_get_contents(base_path('resources/js/Composables/useDevicePermissions.js')),
        ];

        $labels = $this->renderedButtonLabels();

        foreach ($sources as $file => $content) {
            preg_match_all('/tekan\s+"([^"]+)"/i', $content, $matches);

            foreach ($matches[1] as $referenced) {
                $this->assertContains(
                    $referenced,
                    $labels,
                    "{$file}: pesan menyuruh menekan \"{$referenced}\", tapi tombol itu tidak ada di Scan.vue."
                );
            }
        }
    }
}
