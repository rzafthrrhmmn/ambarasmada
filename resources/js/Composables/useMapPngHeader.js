/**
 * Konfigurasi dan konstanta untuk header lembar cetak PNG.
 *
 * Semua konstanta yang berkaitan dengan tata letak header, palet warna,
 * ukuran lembar, dan orientasi dikumpulkan di sini agar konsisten antara
 * pengukur tinggi (`measureSheet`) dan penggambar (`buildMapPng`).
 */

/** Palet cetak: terang, supaya hasil unduhan masih terbaca di printer. */
export const INK = {
  paper: '#ffffff',
  title: '#1f2937',
  body: '#374151',
  muted: '#6b7280',
  rule: '#d1d5db',
  accent: '#8c510a',
  accentSoft: '#f3e8d8',
  frame: '#9ca3af',
  north: '#b91c1c',
  south: '#374151',
};

export const FONT = "'Segoe UI', 'Helvetica Neue', Arial, sans-serif";

/** Ukuran lembar cetak (dipatok, tidak mengikuti isi). */
export const SHEET_WIDTH = 1600;
export const SHEET_HEIGHT = 900;

/** Format lembar yang bisa dipilih pengguna. */
export const SHEET_ORIENTATIONS = {
  landscape: { label: 'Landscape 16:9', width: SHEET_WIDTH, height: SHEET_HEIGHT },
  portrait: { label: 'Potrait 3:4', width: 1000, height: 1333 },
};

/** Format yang dipakai kalau pemanggil tidak menyebut format. */
export const DEFAULT_ORIENTATION = 'landscape';

/** Daftar format untuk dipilih di dialog unduhan. */
export const SHEET_ORIENTATION_LIST = Object.entries(SHEET_ORIENTATIONS).map(
  ([value, preset]) => ({ value, label: preset.label }),
);

/**
 * Lambang yang dicetak di header lembar PNG.
 *
 * Yang dipakai adalah lambang resmi urutan organisasi Kepramukaan, bukan logo
 * ambalan yang bisa diunggah lewat pengaturan. Alasannya isi lembar ini adalah
 * bahan ajar: identitas lembar harus sama di semua wilayah yang diunduh, dan
 * logo yang bisa diganti admin membuat dua unduhan dari wilayah yang sama
 * terlihat seperti berasal dari dua sumber berbeda.
 *
 * Logonya mendatar, jadi kotak logo di header dibuat lebar dan pendek.
 */
export const SHEET_LOGO_URL = '/images/Logo_Urutan_Organiasasi_Kepramukaan.png';

/** Jarak tepi lembar ke konten. */
export const MARGIN = 48;

/** Batas tinggi area peta. */
export const MAP_MIN_HEIGHT = 260;
export const MAP_MAX_RATIO = 0.82;

/** Lebar minimum teks header setelah kolom logo dipotong. */
export const MAP_MIN_WIDTH = 320;

/** Jarak area peta ke baris keterangan sumber di bawahnya. */
export const FOOTER_GAP = 16;

/** Tinggi baris keterangan sumber. */
export const FOOTER_ROW = 18;

/** Jarak baris keterangan sumber ke garis pemisah kaki halaman. */
export const FOOTER_RULE_GAP = 12;

/** Tinggi baris paling bawah (waktu cetak dan nama sistem). */
export const FOOTER_ROW_BOTTOM = 16;

/** Total tinggi kaki halaman. */
export const FOOTER_HEIGHT = FOOTER_ROW + FOOTER_RULE_GAP + FOOTER_ROW_BOTTOM;

/** Skala render lembar cetak. */
export const SHEET_SCALE = 3;

/** Batas luas kanvas yang aman untuk satu lembar. */
export const MAX_SHEET_PIXELS = 16_000_000;

/** Sisi terpanjang kanvas WebGL yang boleh diminta saat ekspor. */
export const EXPORT_CANVAS_MAX_SIDE = 4096;

/** Batas atas rasio piksel ekspor. */
export const EXPORT_MAX_PIXEL_RATIO = 4;

/** Batas luas kanvas WebGL saat ekspor. */
export const EXPORT_CANVAS_MAX_PIXELS = 12_000_000;

/** Lebar kanvas peta CSS saat ekspor. */
export const EXPORT_SURFACE_WIDTH = 1400;

/** Batas ukuran logo di header, dalam satuan CSS lembar. */
export const LOGO_MAX_WIDTH = 224;
export const LOGO_MAX_HEIGHT = 56;

/** Jarak logo ke teks di sebelahnya dan ke baris pertama yang ada di bawahnya. */
export const LOGO_GAP = 20;

/** Baris judul, subjudul, dan jaraknya. */
export const TITLE_LINE_HEIGHT = 38;
export const TITLE_GAP = 8;
export const SUBTITLE_LINE_HEIGHT = 20;
export const SUBTITLE_GAP = 26;

/** Ukuran elemen yang digantung di atas area peta. */
export const PANEL = {
  legendRowHeight: 17,
  legendHeader: 28,
  histogramWidth: 220,
  histogramHeight: 150,
  compassRadius: 34,
  scaleBarMaxWidth: 220,
  inset: 16,
};

/** Item legenda peta. */
export const LEGEND_ITEMS = [
  { color: '#8c510a', dash: [], width: 2.4, label: 'Kontur indeks (tebal)' },
  { color: '#8c510a', dash: [], width: 1, label: 'Garis kontur' },
  { color: '#2563eb', dash: [5, 3], width: 1.5, label: 'Batas kabupaten' },
];

/** Lebar tetap panel legenda, termasuk ruang untuk contoh simbol. */
export const LEGEND_LABEL_X = 48;

/** Komponen cetak yang bisa dipilih pengguna. */
export const DEFAULT_COMPONENTS = {
  scaleBar: true,
  northArrow: true,
  legend: true,
  histogram: false,
  grid: false,
};

/** Langkah grid "bulat" dalam derajat, dari yang paling rapat ke paling jarang. */
export const GRID_STEPS = [0.001, 0.002, 0.005, 0.01, 0.02, 0.05, 0.1, 0.2, 0.5, 1, 2];
