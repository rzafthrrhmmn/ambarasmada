<?php

namespace Database\Seeders;

use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Database\Seeder;

class LearningMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::where('username', 'pembina')->first()
            ?? User::first()
            ?? User::create([
                'username' => 'admin',
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'role' => 'Pembina',
                'is_active' => true,
                'status' => 'approved',
            ]);

        $materials = [
            [
                'nama' => 'Sejarah Gerakan Pramuka',
                'deskripsi' => 'Kepramukaan',
                'konten' => "Gerakan Pramuka didirikan pada tanggal 1 Juli 1961 dihadapan Sidang Pimpinan Besar Nahdlatul Ulama (PBNU) di Masjid Agung Jakarta oleh Drs. Sahur, S.H.\n\nTujuan Gerakan Pramuka adalah untuk mendidik anak-anak dan pemuda untuk menjadi bangsa yang lebih baik dan berguna bagi negara dan bangsa.\n\nBenda-benda Gerakan Pramuka meliputi:\n1. Merajut kebudayaan bangsa\n2. Menumbuhkan rasa cinta dan bangga pada tanah air\n3. Membentuk karakter pemuda yang tangguh dan mandiri",
                'is_restricted' => false,
            ],
            [
                'nama' => 'Pancasila sebagai Ideologi Gerakan Pramuka',
                'deskripsi' => 'Kepramukaan',
                'konten' => "Pancasila adalah dasar filosofi dan ideologi Gerakan Pramuka. Pramuka didirikan untuk mengabdi pada Bangsa dan Negara Kesatuan Republik Indonesia melalui pembinaan karakter dan patriotisme berdasarkan Pancasila.\n\nLandasan Pancasila dalam Pramuka:\n- Sila I: Kesatuan Tuhan, Bangsa, dan Negara\n- Sila II: Kemanusiaan yang adil\n- Sila III: Persatuan Indonesia\n- Sila IV: Kerakyatan yang dibimbing oleh hikmat kebijaksanaan dalam Permusyawaratan\n- Sila V: Keadamaian dan Kesejahteraan untuk seluruh Bangsa",
                'is_restricted' => false,
            ],
            [
                'nama' => 'Lambang dan Tanda Kepramukaan',
                'deskripsi' => 'Kepramukaan',
                'konten' => "Lambang Gerakan Pramuka melambangkan semangat dan tujuan gerakan.\n\nTanda Kepramukaan meliputi:\n- Jaket kegiatan\n- Bintang lima\n- Lencana kepramukaan\n- Topi kepala\n- Sabuk kayu atau tali pinggang",
                'is_restricted' => false,
            ],
            [
                'nama' => 'Lagu Kebangsaan dan Lagu Kibar',
                'deskripsi' => 'Kepramukaan',
                'konten' => "Lagu Kebangsaan: Indonesia Raya\n\nMushikalimat Lagu Kibar Pramuka wajib dinyanyikan setiap kegiatan pengibaran bendera.\n\nPestrofi Lagu Kibar menggambarkan semangat kekeluargaan dan persatuan.",
                'is_restricted' => false,
            ],
            [
                'nama' => 'Sandi Kepramukaan',
                'deskripsi' => 'Kepramukaan',
                'konten' => "Sandi Kepramukaan adalah aturan ethik dan moral yang ditaati oleh setiap anggota Pramuka.\n\nLima peraturan sandi:\n1. Jujur dan tidak pernah berdusta\n2. Rajin dan tidak pernah malas\n3. Berani dan tidak pernah penakut\n4. Taat dan tidak pernah pelit\n5. Bersikerasi dan tidak pernah angkara",
                'is_restricted' => false,
            ],
            [
                'nama' => 'Etika dan Esti Keadepan',
                'deskripsi' => 'Kepramukaan',
                'konten' => "Etika adalah peraturan etika yang mengatur hubungan antar kakak dan adik dalam satu regu.\n\nEsti Keadepan adalah aturan moral yang mengatur laku anggota Pramuka dalam kehidupan sehari-hari untuk menjaga martabat dan kebesaran diri serta orang lain.",
                'is_restricted' => false,
            ],
            [
                'nama' => 'Tata Tertib Jejayaran',
                'deskripsi' => 'Kepramukaan',
                'konten' => "Tata tertib jejayaran mengatur penempatan dan panggulangan anggota Pramuka.\n\nAturan jejaya meliputi:\n- Kepala regu ditempatkan di depan\n- Posisi anggota ditentukan berdasarkan kepangkatan\n- Setiap regu wajib seragam",
                'is_restricted' => false,
            ],
            [
                'nama' => 'Aturan Keselamatan dalam Kegiatan Kepramukaan',
                'deskripsi' => 'Kepramukaan',
                'konten' => "Setiap kegiatan kepramukaan harus dilaksanakan dengan memperhatikan aturan keselamatan.\n\nAturan utama:\n1. Selalu mengenakan seragam lengkap\n2. Membawa perlengkapan kebutuhan pribadi\n3. Mengikuti arahan pembimbing\n4. Membawa jenkendu bawaan makan dan minum\n5. Membawa bekal makanan dan minuman",
                'is_restricted' => false,
            ],
            [
                'nama' => 'Pengenalan Lingkungan Hidup (PLH)',
                'deskripsi' => 'Kepramukaan',
                'konten' => "PLH adalah kegiatan pemahaman dan penerapan nilai pembibitan dan pelestarian lingkungan.\n\nTopik PLH meliputi:\n- Pengelolaan sampah\n- Penggunaan air\n- Energi terbarukan\n- Konservasi flora dan fauna",
                'is_restricted' => false,
            ],
            [
                'nama' => 'Undang-Undang No. 12 Tahun 1961 tentang Pramuka',
                'deskripsi' => 'Kepramukaan',
                'konten' => "Undang-Undang ini menetapkan pokok dan cita-cita Gerakan Pramuka di Indonesia.\n\nPasal 1: Dengan Undang-Undang ini diselenggarakan Gerakan Kebangsaan Pramuka sebagai wadah pendidikan anak bangsa dan pemuda berdasarkan nilai-nilai pancasila.\n\nPasal 3: Dalam menjalankan Gerakan Pramuka, anggotanya wajib mentaati dan menaati Pancasila serta Undang-Undang ini serta Ketetapan Majelis Tertinggi Gerakan Pramuka.",
                'is_restricted' => false,
            ],
        ];

        foreach ($materials as $material) {
            LearningMaterial::firstOrCreate(
                ['nama' => $material['nama']],
                [
                    'deskripsi' => $material['deskripsi'],
                    'konten' => $material['konten'],
                    'is_restricted' => $material['is_restricted'],
                    'created_by_user_id' => $creator->id,
                ]
            );
        }
    }
}
