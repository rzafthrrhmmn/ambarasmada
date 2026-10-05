<template>
  <AppLayout>
    <!-- Kepala halaman.
         Kendali lapis dulunya lima tombol berlabel panjang ("Sembunyikan
         Kontur", "Sembunyikan Label Kontur", ...) yang berderet satu baris.
         Di ponsel lima label sepanjang itu membungkus jadi beberapa baris dan
         mendorong judul halaman jauh ke bawah, sementara isinya persis
         menduplikasi checkbox "Tampilan Peta" di dialog unduhan. Sekarang
         jadi lima chip pendek yang keadaan aktifnya ditandai warnanya, jadi
         yang sedang aktif terlihat tanpa harus membaca teks tombolnya. -->
    <header class="mb-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-daun-400">Peta Kontur</p>
        <h1 class="mt-1 text-2xl font-extrabold text-[#f0ead8] sm:text-3xl">Peta Kontur Sulawesi Selatan</h1>
        <p class="mt-2 max-w-2xl text-sm text-[#8fa06a]">
          Cari kabupaten, lihat batas administratif, dan jelajahi kontur topografi.
        </p>

        <!-- Ringkasan tetap yang selalu benar dan tidak bergantung pilihan
             pengguna, jadi pembaca punya konteks sebelum menyentuh peta. -->
        <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-xs">
          <div class="flex items-baseline gap-1.5">
            <dt class="text-[#8fa06a]">Jangkauan</dt>
            <dd class="font-semibold text-[#d4dc9a]">24 kabupaten/kota</dd>
          </div>
          <div class="flex items-baseline gap-1.5">
            <dt class="text-[#8fa06a]">Interval kontur</dt>
            <dd class="font-semibold text-[#d4dc9a]">10 meter</dd>
          </div>
          <div class="flex items-baseline gap-1.5">
            <dt class="text-[#8fa06a]">Kontur indeks</dt>
            <dd class="font-semibold text-[#d4dc9a]">Setiap 50 meter</dd>
          </div>
          <div class="flex items-baseline gap-1.5">
            <dt class="text-[#8fa06a]">Status data</dt>
            <dd class="font-semibold" :class="hasPmtiles ? 'text-[#A7B92A]' : 'text-[#f87171]'">
              {{ hasPmtiles ? 'Kontur tersedia' : 'Kontur belum tersedia' }}
            </dd>
          </div>
        </dl>
      </div>

      <section class="rounded-xl border-2 border-daun-500/40 bg-hutan-600/70 p-3">
        <div class="mb-2 flex items-center justify-between gap-2">
          <h2 class="text-xs font-semibold uppercase tracking-wider text-[#A7B92A]">Lapisan Peta</h2>
          <button
            v-if="hasPmtiles"
            @click="showOfflineModal = true"
            class="inline-flex items-center rounded-lg border-2 border-[#A7B92A] bg-daun-400/10 px-3 py-1.5 text-xs font-bold text-[#A7B92A] transition hover:bg-daun-400/20"
          >
            Unduh Offline
          </button>
        </div>
        <div class="flex flex-wrap gap-1.5">
          <button
            v-for="lapisan in LAPISAN_PETA"
            :key="lapisan.key"
            type="button"
            @click="lapisan.toggle"
            :aria-pressed="lapisan.aktif"
            class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-bold transition"
            :class="lapisan.aktif
              ? 'border-emas-400 bg-emas-400/15 text-emas-400'
              : 'border-daun-500/50 bg-hutan-800/60 text-lumut-400 hover:border-daun-500 hover:text-krem-300'"
          >
            <NavIcon :name="lapisan.icon" class="h-4 w-4" />
            {{ lapisan.nama }}
          </button>

          <!-- Legenda bukan lapisan data, jadi tombolnya berdiri sendiri.
               Dipisah supaya state-nya tidak ikutTerIkut v-for di atas. -->
          <button
            type="button"
            @click="showElevationLegend = !showElevationLegend"
            :aria-pressed="showElevationLegend"
            class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-bold transition"
            :class="showElevationLegend
              ? 'border-emas-400 bg-emas-400/15 text-emas-400'
              : 'border-daun-500/50 bg-hutan-800/60 text-lumut-400 hover:border-daun-500 hover:text-krem-300'"
          >
            <NavIcon name="legend" class="h-4 w-4" />
            Legenda
          </button>
        </div>
      </section>
    </header>

    <div class="mb-4 rounded-xl border-2 border-[#A7B92A]/40 bg-[#335233] p-4">
      <label for="peta-kabupaten" class="block text-xs font-medium text-[#d4dc9a] mb-2">Cari Kabupaten</label>
      <div class="flex flex-col gap-2 sm:flex-row">
        <select
          id="peta-kabupaten"
          v-model="selectedKabupaten"
          @change="onKabupatenSelect"
          class="flex-1 rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2.5 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        >
          <option value="">-- Pilih Kabupaten --</option>
          <option
            v-for="kab in kabupatens"
            :key="kab.id_kab"
            :value="kab.id_kab"
          >
            {{ kab.nama_kab }}
          </option>
        </select>

        <!-- Dropdown kecamatan mengikuti kabupaten yang dipilih. Daftar seluruh
             Sulawesi hampir 313 nama dan hampir semuanya di luar kabupaten
             aktif, jadi yang ditampilkan selalu daftar milik kabupaten
             terpilih saja. -->
        <select
          id="peta-kecamatan"
          v-model="selectedKecamatan"
          @change="onKecamatanSelect"
          :disabled="!selectedKabupaten"
          class="flex-1 rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2.5 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330] disabled:cursor-not-allowed disabled:opacity-50"
        >
          <option value="">
            {{ selectedKabupaten ? '-- Pilih Kecamatan --' : '-- Pilih Kabupaten dulu --' }}
          </option>
          <option
            v-for="kec in kecamatanOptions"
            :key="kec.id_kec"
            :value="kec.id_kec"
          >
            {{ kec.nama_kec }}
          </option>
        </select>

        <div class="flex shrink-0 gap-2">
          <button
            v-if="selectedKabupaten"
            @click="printWilayah"
            class="rounded-lg border-2 border-[#A7B92A] bg-[#A7B92A]/10 px-3 py-2 text-xs font-bold text-[#A7B92A] transition hover:bg-daun-400/20"
            :title="`Cetak PNG wilayah yang dipilih: ${selectedWilayah?.label ?? ''}`"
          >
            Cetak PNG
          </button>
          <button
            v-if="selectedKabupaten"
            @click="clearSelection"
            class="rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30"
          >
            Hapus
          </button>
        </div>
      </div>
      <div v-if="selectedKabData" class="mt-2 flex flex-wrap gap-3 text-xs text-[#8fa06a]">
        <span>Kabupaten: <strong class="text-[#EDD330]">{{ selectedKabData.nama_kab }}</strong></span>
        <span v-if="selectedKecData">
          Kecamatan:
          <strong class="text-[#EDD330]">{{ selectedKecData.nama_kec }}</strong>
          <span class="text-[#8fa06a]">({{ selectedKecData.id_kec }})</span>
        </span>
        <span>ID: {{ selectedKabData.id_kab }}</span>
        <span v-if="selectedWilayahBBox">BBox: {{ selectedWilayahBBox.join(', ') }}</span>
        <span v-if="selectedKabupaten && !selectedKecamatan">
          {{ kecamatanOptions.length }} kecamatan di {{ selectedKabData.nama_kab }}
        </span>
      </div>
    </div>

    <div class="peta-shell relative overflow-hidden rounded-2xl border-2 border-[#A7B92A]/40 bg-[#263D26] shadow-lg">
      <div ref="mapContainer" class="h-[70vh] w-full min-h-[400px]"></div>

      <!-- Pojok kiri bawah: skala 1:N dan readout koordinat.
           Keduanya duduk dalam satu wadah flex column dengan satu titik jangkar,
           jadi keduanya tidak pernah saling menimpa dan tidak pernah menabrak
           bilah skala bawaan MapLibre yang juga di pojok itu.

           Skala ditulis sebagai 1:N karena ScaleControl bawaan hanya menggambar
           batang tanpa angkanya, sehingga pembaca tidak tahu batangnya mewakili
           berapa. Angkanya dihitung ulang tiap kamera bergerak, karena skala 1:N
           ikut berubah begitu zoom atau lintang pusatnya berubah. -->
      <div class="peta-panel pointer-events-none absolute bottom-12 left-3 flex flex-col items-start gap-1.5">
        <div
          v-if="mapScale"
          class="max-w-[min(260px,calc(100%_-_13rem))] rounded-lg border border-[#6F9435]/30 bg-[#1a1a1a]/90 px-3 py-1.5 font-mono text-xs text-[#EDD330] shadow-lg backdrop-blur-sm"
        >
          Skala 1:{{ mapScale }}
        </div>
        <div
          v-if="mouseCoords"
          class="max-w-[min(260px,calc(100%_-_13rem))] rounded-lg border border-[#6F9435]/30 bg-[#1a1a1a]/90 px-3 py-1.5 font-mono text-xs text-[#EDD330] shadow-lg backdrop-blur-sm"
        >
          Lon: {{ mouseCoords.lng.toFixed(6) }}° | Lat: {{ mouseCoords.lat.toFixed(6) }}°
        </div>
      </div>

      <!-- Bilah skala dan kompas memakai kontrol bawaan MapLibre
           (ScaleControl dan NavigationControl) yang ditambahkan di initMap().
           Wadah kosong untuk keduanya pernah ada di sini sehingga peta
           menampilkan dua bilah skala dan satu di antaranya tidak pernah diisi.
           Legenda elevasi digambar lewat markup supaya Vue yangmemilkinya. -->

      <!-- Pojok kanan bawah: legenda elevasi dan tombol lokasi saya.
           Keduanya ditumpuk dalam satu wadah flex column. Sebelumnya legenda
           dipatok bottom-4 right-4 dengan lebar lebih dari 150 piksel, sementara
           tombol GPS dipatok bottom-4 right-14 (56 piksel), sehingga tombol GPS
           berdiri tepat di atas legenda. -->
      <div class="absolute bottom-3 right-3 z-20 flex flex-col items-end gap-2">
        <div
          v-if="showElevationLegend"
          class="rounded-lg border border-[#6F9435]/40 bg-[#1a1a1a]/90 p-3 text-xs text-[#d4dc9a] shadow-lg backdrop-blur-sm min-w-[150px]"
        >
          <div class="mb-2 flex items-center gap-1.5 font-bold text-[#EDD330]">
            <NavIcon name="legend" class="h-4 w-4" />
            Legenda Elevasi
          </div>
          <!-- Isi legenda ini sengaja digambar dengan kelas Tailwind, bukan
               atribut style inline. Warna dan tebalnya juga harus mengikuti
               layer aslinya: garis kontur hanya satu warna, dan yang membedakan
               kontur indeks dari kontur biasa adalah tebalnya, bukan warnanya.
               Versi lama memakai empat warna yang tidak pernah muncul di peta
               mana pun, sehingga legenda mengarang warna yang tidak ada.

               Legenda di halaman cetak berbeda: dokumen itu berdiri sendiri
               tanpa CSS aplikasi, jadi warna di sana memang ditulis inline. -->
          <div class="space-y-1.5">
            <div class="flex items-center gap-2">
              <span class="h-[3px] w-6 rounded bg-[#8c510a]" aria-hidden="true"></span>
              Kontur indeks (setiap 50 m)
            </div>
            <div class="flex items-center gap-2">
              <span class="h-px w-6 bg-[#8c510a]" aria-hidden="true"></span>
              Kontur biasa (setiap 10 m)
            </div>
            <div class="mt-2 flex items-center gap-2 border-t border-[#6F9435]/30 pt-2">
              <span class="h-0 w-6 border-t-2 border-dashed border-[#2563eb]" aria-hidden="true"></span>
              Batas kabupaten/kota
            </div>
            <div class="flex items-center gap-2">
              <span class="h-0 w-6 border-t-2 border-[#0f766e]" aria-hidden="true"></span>
              Batas kecamatan
            </div>
            <div class="flex items-center gap-2">
              <span class="h-3 w-6 rounded-sm border border-[#EDD330] bg-[#EDD330]/40" aria-hidden="true"></span>
              Kecamatan yang dipilih
            </div>
          </div>
        </div>

        <!-- GPS locate button.
             Kontrol GeolocateControl bawaan MapLibre sengaja tidak dipakai supaya
             tidak ada dua tombol lokasi yang tumpang tindih; locateUser() juga
             menampilkan popup "Lokasi Anda" dan menulis alasannya ke mapStatus. -->
        <div
          v-if="hasGeolocation"
          class="rounded-lg border-2 border-[#6F9435]/50 bg-[#263D26]/95 px-3 py-2 text-sm font-bold text-[#EDD330] shadow-lg backdrop-blur-sm transition hover:bg-[#335233]/95 disabled:opacity-50"
        >
          <button
            @click="locateUser"
            :disabled="locating"
            class="inline-flex items-center gap-1.5 disabled:cursor-not-allowed"
            title="Lokasi saya"
          >
            <NavIcon v-if="locating" name="spinner" class="h-4 w-4 animate-spin" />
            <NavIcon v-else name="locate" class="h-4 w-4" />
            <span class="text-xs">{{ locating ? 'Mencari...' : 'Lokasi saya' }}</span>
          </button>
        </div>
      </div>

      <!-- Lapisan atas: panel kiri dan alat kanan atas.
           Keduanya hidup di satu wadah flex yang boleh membungkus baris.
           Sebelumnya masing-masing dipatok ke pojoknya sendiri, sehingga pada
           layar sempit panel kiri menimpa alat kanan. pr-12 menyisakan ruang
           untuk NavigationControl di pojok kanan atas. -->
      <div class="peta-panel absolute inset-x-3 top-3 flex flex-wrap items-start justify-between gap-2 pr-12">
      <div class="flex w-[min(300px,100%)] flex-col items-start gap-2" data-nama="PanelKiri">
        <!-- Offline indicator -->
        <div v-if="isOffline" class="inline-flex items-center gap-1.5 rounded-lg bg-[#f59e0b]/90 px-3 py-1.5 text-xs font-bold text-white shadow-lg">
          <NavIcon name="offline" class="h-4 w-4" />
          Mode Offline
        </div>

        <!-- Search panel -->
        <div class="flex w-full gap-2">
          <div class="relative flex-1">
            <input
              v-model="searchQuery"
              @keyup.enter="searchPlace"
              @focus="searchFocused = true"
              @blur="searchFocused = false"
              placeholder="Cari tempat atau koordinat (lat, lng)..."
              class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330] pr-10"
            />
            <button
              v-if="searchQuery"
              type="button"
              @click="searchQuery = ''"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-[#8fa06a] transition hover:text-[#EDD330]"
              title="Bersihkan pencarian"
            >
              <NavIcon name="close" class="h-4 w-4" />
            </button>
          </div>
          <button
            @click="searchPlace"
            :disabled="!searchQuery.trim()"
            class="rounded-lg border-2 border-[#A7B92A] bg-[#A7B92A]/10 px-3 py-2 text-sm font-bold text-[#A7B92A] transition hover:bg-daun-400/20 disabled:opacity-50"
          >
            Cari
          </button>
        </div>

        <!-- Search results dropdown -->
        <div v-if="searchFocused && searchResults.length > 0" class="max-h-60 w-full overflow-y-auto rounded-lg border border-[#6F9435] bg-[#263D26] shadow-lg">
          <div v-for="result in searchResults" :key="result.place_id || result.lat + ',' + result.lon" @click="selectSearchResult(result)" class="cursor-pointer border-b border-[#6F9435]/20 px-4 py-2 last:border-0 hover:bg-[#335233]">
            <div class="font-medium text-[#f0ead8]">{{ result.display_name || result.label }}</div>
            <div class="text-xs text-[#8fa06a]">{{ result.lat }}, {{ result.lon }}</div>
          </div>
        </div>

        <!-- Measurement tool panel -->
        <div v-if="measurementMode !== 'none'" class="w-full rounded-lg border border-[#6F9435]/30 bg-[#1a1a1a]/90 p-3 text-xs text-[#EDD330]">
          <div class="mb-2 flex items-center justify-between">
            <span class="inline-flex items-center gap-1.5 font-bold">
              <NavIcon :name="measurementMode === 'distance' ? 'ruler' : 'polygon'" class="h-4 w-4" />
              {{ measurementMode === 'distance' ? 'Ukur Jarak' : 'Ukur Luas' }}
            </span>
            <button @click="cancelMeasurement" class="text-[#f87171] hover:underline">Batal</button>
          </div>
          <div v-if="measurementPoints.length > 0" class="space-y-1">
            <div>Titik: {{ measurementPoints.length }}</div>
            <div v-if="measurementMode === 'distance' && measurementDistance > 0">
              Jarak: {{ formatDistance(measurementDistance) }}
            </div>
            <div v-if="measurementMode === 'area' && measurementArea > 0">
              Luas: {{ formatArea(measurementArea) }}
            </div>
          </div>
          <button @click="finishMeasurement" class="mt-2 w-full rounded bg-[#A7B92A] px-3 py-1.5 text-xs font-bold text-white">Selesai</button>
        </div>
      </div>

      <!-- Alat kanan atas: basemap, alat ukur, bookmark, dan cetak.
           Berada di dalam wadah lapisan atas yang sama dengan panel kiri,
           jadi keduanya tidak mungkin bertumpuk. -->
      <div class="flex flex-col items-end gap-2" data-nama="AlatKananAtas">
        <div class="flex flex-wrap items-center justify-end gap-2">
          <!-- Basemap selector -->
          <label class="sr-only" for="peta-basemap-cari">Pilih basemap</label>
          <select
            id="peta-basemap-cari"
            v-model="basemap"
            @change="changeBasemap"
            class="w-[168px] rounded-lg border-2 border-[#6F9435]/60 bg-[#263D26]/95 px-3 py-2 text-sm text-[#f0ead8] shadow-lg backdrop-blur-sm outline-none focus:border-[#EDD330]"
          >
            <!-- Opsi basemap memakai teks saja. Elemen <option> hanya bisa memuat
             teks, jadi SVG di dalamnya tidak akan dirender dan ikon yang
             dicoba ditaruh di situ akan hilang diam-diam. -->
            <option value="osm">OpenStreetMap</option>
            <option value="satellite">Satelit</option>
            <option value="terrain">Terrain</option>
            <option value="dark">Gelap</option>
          </select>

          <!-- Measurement toggles -->
          <button
            type="button"
            @click="startMeasurement('distance')"
            :disabled="measurementMode !== 'none'"
            class="inline-flex h-[38px] w-[38px] items-center justify-center rounded-lg border-2 border-[#6F9435]/60 bg-[#263D26]/95 text-[#EDD330] shadow-lg backdrop-blur-sm transition hover:bg-[#335233]/95 disabled:opacity-50"
            title="Ukur jarak"
          >
            <NavIcon name="ruler" class="h-4 w-4" />
          </button>
          <button
            type="button"
            @click="startMeasurement('area')"
            :disabled="measurementMode !== 'none'"
            class="inline-flex h-[38px] w-[38px] items-center justify-center rounded-lg border-2 border-[#6F9435]/60 bg-[#263D26]/95 text-[#EDD330] shadow-lg backdrop-blur-sm transition hover:bg-[#335233]/95 disabled:opacity-50"
            title="Ukur luas"
          >
            <NavIcon name="polygon" class="h-4 w-4" />
          </button>

          <!-- Bookmark/save view button -->
          <button
            type="button"
            @click="saveBookmark"
            class="inline-flex h-[38px] w-[38px] items-center justify-center rounded-lg border-2 border-[#6F9435]/60 bg-[#263D26]/95 text-[#EDD330] shadow-lg backdrop-blur-sm transition hover:bg-[#335233]/95"
            title="Simpan tampilan"
          >
            <NavIcon name="bookmark" class="h-4 w-4" />
          </button>

<!-- Print button. Mencetak PNG isi kanvas persis seperti yang sedang
tampil, bukan membuka jendela cetak terpisah dan bukan memakai wilayah
yang kebetulan sedang dipilih di dropdown. -->
<button
  type="button"
  @click="printMapPng({ matchViewport: true })"
  :disabled="isExporting"
  class="inline-flex items-center gap-1.5 rounded-lg border-2 border-[#6F9435]/60 bg-[#263D26]/95 px-3 py-2 text-sm font-bold text-[#EDD330] shadow-lg backdrop-blur-sm transition hover:bg-[#335233]/95 disabled:cursor-not-allowed disabled:opacity-50"
  title="Cetak PNG peta yang sedang terlihat"
>
  <NavIcon :name="isExporting ? 'spinner' : 'printer'" class="h-4 w-4" :class="isExporting ? 'animate-spin' : ''" />
  <span class="text-xs">{{ isExporting ? 'Menyiapkan...' : 'Cetak' }}</span>
</button>
        </div>

        <!-- Bookmark list -->
        <div v-if="bookmarks.length > 0" class="max-h-56 w-[min(280px,calc(100vw_-_2rem))] overflow-y-auto rounded-lg border border-[#6F9435]/30 bg-[#1a1a1a]/90 p-2 text-xs shadow-lg backdrop-blur-sm">
          <p class="mb-1 px-1 font-bold text-[#EDD330]">Tampilan Tersimpan ({{ bookmarks.length }})</p>
          <div
            v-for="(bookmark, index) in bookmarks"
            :key="bookmark.timestamp ?? index"
            class="flex items-center gap-2 rounded px-1 py-1 hover:bg-[#335233]"
          >
            <button @click="goToBookmark(bookmark)" class="flex-1 truncate text-left text-[#f0ead8] hover:text-[#EDD330]" :title="bookmark.name">
              {{ bookmark.name }}
            </button>
            <button
              type="button"
              @click="deleteBookmark(index)"
              class="shrink-0 text-[#f87171] transition hover:text-[#fca5a5]"
              title="Hapus bookmark"
            >
              <NavIcon name="trash" class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
      </div>

      <div
        v-if="!hasPmtiles"
        class="peta-panel absolute bottom-16 left-1/2 -translate-x-1/2 rounded-lg border border-[#EDD330]/50 bg-[#263D26]/95 px-4 py-2 text-center shadow-lg backdrop-blur-sm"
      >
        <p class="text-xs font-bold text-[#EDD330]">Garis kontur belum tersedia</p>
        <p class="mt-0.5 text-[11px] text-[#8fa06a]">File PMTiles tidak ada di server. Peta dasar dan batas kabupaten tetap dapat dipakai.</p>
      </div>
      <div v-if="loading" class="absolute inset-0 z-30 flex items-center justify-center bg-[#263D26]/70 backdrop-blur-sm">
        <p class="text-lg font-bold text-[#EDD330]">Memuat peta...</p>
      </div>
      <div v-if="mapError" class="absolute inset-0 z-30 flex items-center justify-center bg-[#263D26]/90 backdrop-blur-sm">
        <div class="text-center p-6">
          <p class="text-2xl font-bold text-[#f87171]">Gagal Memuat Peta</p>
          <p class="mt-2 text-sm text-[#8fa06a]">{{ mapError }}</p>
          <button
            @click="initMap"
            class="mt-4 inline-flex items-center rounded-lg border-2 border-[#A7B92A] bg-[#A7B92A]/10 px-4 py-2 text-sm font-bold text-[#A7B92A] transition hover:bg-daun-400/20"
          >
            Coba Lagi
          </button>
        </div>
      </div>
    </div>

    <div v-if="mapStatus" class="mt-3 rounded-lg border border-[#6F9435]/30 bg-[#335233] p-3 text-xs text-[#d4dc9a]">
      {{ mapStatus }}
    </div>

    <!-- Bagian statis di bawah peta.
         Semuanya ditulis sebagai markup, bukan dari props atau hasil
         computations, karena isinya memang tidak berubah: sumber data,
         interval kontur, cara membacanya, dan urutan zamannya. Bagian ini
         membuat halaman tetap punya penjelasan ketika peta gagal dimuat,
         karena tidak bergantung pada apa pun milik peta. -->
    <section class="mt-5 grid gap-4 lg:grid-cols-3" aria-labelledby="peta-tentang-judul">
      <div class="peta-kartu">
        <h2 id="peta-tentang-judul" class="peta-kartu-judul">
          <NavIcon name="layers" class="h-4 w-4" />
          Tentang Peta Ini
        </h2>
        <dl class="grid gap-3 sm:grid-cols-2">
          <div class="peta-info">
            <dt class="peta-info-label">Wilayah</dt>
            <dd class="peta-info-nilai">Sulawesi Selatan</dd>
          </div>
          <div class="peta-info">
            <dt class="peta-info-label">Interval kontur</dt>
            <dd class="peta-info-nilai">10 meter</dd>
          </div>
          <div class="peta-info">
            <dt class="peta-info-label">Kontur indeks</dt>
            <dd class="peta-info-nilai">Setiap 50 meter</dd>
          </div>
          <div class="peta-info">
            <dt class="peta-info-label">Jumlah wilayah</dt>
            <dd class="peta-info-nilai">24 kabupaten/kota</dd>
          </div>
        </dl>
        <p class="mt-3 text-xs leading-relaxed text-lumut-400">
          Batas wilayah mengikuti data administratif resmi. Garis kontur berasal dari data elevasi
          terolah dan bukan pengganti survei lapangan, sehingga jangan dipakai menentukan batas
          atau elevasi secara presisi.
        </p>
      </div>

      <div class="peta-kartu">
        <h2 class="peta-kartu-judul">
          <NavIcon name="contour" class="h-4 w-4" />
          Cara Membaca Kontur
        </h2>
        <ul class="space-y-2 text-sm leading-relaxed text-krem-300">
          <li class="flex gap-2">
            <span class="mt-[7px] h-[3px] w-6 shrink-0 rounded bg-[#8c510a]" aria-hidden="true"></span>
            <span>
              Garis kontur yang tebal adalah kontur indeks, digambar setiap kelipatan
              50 meter, jadi paling mudah dipakai sebagai acuan elevasi.
            </span>
          </li>
          <li class="flex gap-2">
            <span class="mt-[7px] h-px w-6 shrink-0 bg-[#8c510a]" aria-hidden="true"></span>
            <span>
              Garis tipis mengisi di antara dua kontur indeks, berjarak 10 meter satu sama lain.
            </span>
          </li>
          <li class="flex gap-2">
            <span class="mt-[9px] h-0 w-6 shrink-0 border-t-2 border-dashed border-[#2563eb]" aria-hidden="true"></span>
            <span>Garis putus-putus biru adalah batas kabupaten atau kota.</span>
          </li>
          <li class="flex gap-2">
            <span class="mt-[9px] h-0 w-6 shrink-0 border-t-2 border-[#0f766e]" aria-hidden="true"></span>
            <span>Garis hijau tua adalah batas kecamatan.</span>
          </li>
          <li class="flex gap-2">
            <span class="mt-[5px] h-4 w-6 shrink-0 rounded-sm border border-[#EDD330] bg-[#EDD330]/30" aria-hidden="true"></span>
            <span>Area kuning adalah kecamatan yang sedang dipilih.</span>
          </li>
        </ul>
      </div>

      <div class="peta-kartu">
        <h2 class="peta-kartu-judul">
          <NavIcon name="guides" class="h-4 w-4" />
          Langkah Pemakaian
        </h2>
        <ol class="space-y-2.5">
          <li class="peta-langkah">
            <span class="peta-langkah-nomor" aria-hidden="true">1</span>
            <span class="peta-langkah-teks">
              Pilih kabupaten, lalu kecamatan bila perlu. Peta akan menyesuaikan wilayahnya.
            </span>
          </li>
          <li class="peta-langkah">
            <span class="peta-langkah-nomor" aria-hidden="true">2</span>
            <span class="peta-langkah-teks">
              Gunakan alat ukur untuk mengukur jarak atau luas langsung di atas peta.
            </span>
          </li>
          <li class="peta-langkah">
            <span class="peta-langkah-nomor" aria-hidden="true">3</span>
            <span class="peta-langkah-teks">
              Simpan tampilan yang sedang dilihat agar bisa dibuka lagi nanti.
            </span>
          </li>
          <li class="peta-langkah">
            <span class="peta-langkah-nomor" aria-hidden="true">4</span>
            <span class="peta-langkah-teks">
              Cetak PNG untuk laporan, atau unduh paket offline untuk dipakai tanpa jaringan.
            </span>
          </li>
        </ol>
        <p class="mt-3 border-t border-daun-500/30 pt-3 text-xs leading-relaxed text-lumut-400">
          Pencarian menerima nama tempat maupun koordinat. Urutannya latitude lalu longitude,
          jadi tulis <code class="text-krem-300">lat, lng</code> dengan tanda pisah koma.
        </p>
      </div>
    </section>
  </AppLayout>

  <Modal v-if="showOfflineModal" title="Unduh Peta Offline" @close="showOfflineModal = false">
    <div class="space-y-4">
      <!-- Download Mode Selection -->
      <div class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
        <label class="block text-xs font-medium text-[#d4dc9a] mb-3">Mode Unduh</label>
        <div class="grid gap-2 sm:grid-cols-2">
          <label class="relative cursor-pointer">
            <input
              type="radio"
              name="downloadMode"
              value="full"
              v-model="downloadMode"
              class="sr-only peer"
            />
            <div class="relative rounded-lg border-2 border-[#6F9435] bg-[#263D26] p-4 text-center transition-all peer-checked:border-[#A7B92A] peer-checked:bg-[#A7B92A]/10 peer-checked:ring-2 peer-checked:ring-[#A7B92A]/20">
              <div class="mx-auto mb-1 w-fit text-[#EDD330]"><NavIcon name="map" class="h-6 w-6" /></div>
              <div class="mt-1 text-sm font-semibold text-[#f0ead8]">Seluruh Peta</div>
              <div class="mt-1 text-xs text-[#8fa06a]">Sulawesi Selatan lengkap</div>
            </div>
          </label>
          <label class="relative cursor-pointer">
            <input
              type="radio"
              name="downloadMode"
              value="region"
              v-model="downloadMode"
              class="sr-only peer"
            />
            <div class="relative rounded-lg border-2 border-[#6F9435] bg-[#263D26] p-4 text-center transition-all peer-checked:border-[#A7B92A] peer-checked:bg-[#A7B92A]/10 peer-checked:ring-2 peer-checked:ring-[#A7B92A]/20">
              <div class="mx-auto mb-1 w-fit text-[#EDD330]"><NavIcon name="locate" class="h-6 w-6" /></div>
              <div class="mt-1 text-sm font-semibold text-[#f0ead8]">Satu Daerah</div>
              <div class="mt-1 text-xs text-[#8fa06a]">Pilih kabupaten/kota, lalu kecamatan</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Region Selection (when region mode) -->
      <div v-if="downloadMode === 'region'" class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
        <label for="unduh-kabupaten" class="block text-xs font-medium text-[#d4dc9a] mb-2">Pilih Kabupaten/Kota</label>
        <select
          id="unduh-kabupaten"
          v-model="selectedOfflineRegion"
          class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2.5 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        >
          <option value="">-- Pilih Daerah --</option>
          <option
            v-for="kab in kabupatens"
            :key="kab.id_kab"
            :value="kab.id_kab"
          >
            {{ kab.nama_kab }}
          </option>
        </select>

        <!-- Unduhan bisa dibatasi sampai satu kecamatan. Tanpa ini paket tile
             selalu memuat seluruh kabupaten padahal yang dibutuhkan peta satu
             kecamatan. -->
        <label for="unduh-kecamatan" class="mt-3 block text-xs font-medium text-[#d4dc9a] mb-2">
          Pilih Kecamatan <span class="text-[#8fa06a]">(opsional)</span>
        </label>
        <select
          id="unduh-kecamatan"
          v-model="selectedOfflineKecamatan"
          :disabled="!selectedOfflineRegion"
          class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2.5 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330] disabled:cursor-not-allowed disabled:opacity-50"
        >
          <option value="">
            {{ selectedOfflineRegion ? '-- Semua Kecamatan --' : '-- Pilih Kabupaten dulu --' }}
          </option>
          <option
            v-for="kec in offlineKecamatanOptions"
            :key="kec.id_kec"
            :value="kec.id_kec"
          >
            {{ kec.nama_kec }}
          </option>
        </select>

        <div v-if="selectedOfflineWilayah" class="mt-2 text-xs text-[#8fa06a]">
          Area: {{ selectedOfflineWilayah.label }}
          <span class="text-[#EDD330]">({{ selectedOfflineWilayah.kode }})</span>
          <span v-if="selectedOfflineKecData && !selectedOfflineKecData.bbox" class="mt-1 block text-[#EDD330]">
            Kecamatan ini belum punya batas sendiri, paket tile mengikuti seluruh kabupaten.
          </span>
        </div>
      </div>

      <!-- Mini Map Preview (when region selected) -->
      <div v-if="showMiniMap" class="rounded-lg border border-[#6F9435]/30 bg-[#263D26] p-3">
        <div class="flex items-center justify-between mb-2">
          <label class="text-xs font-medium text-[#d4dc9a]">Preview Wilayah</label>
          <span v-if="selectedOfflineWilayah" class="text-xs text-[#EDD330]">{{ selectedOfflineWilayah.name }}</span>
        </div>
        <div ref="miniMapContainer" class="peta-shell h-[200px] w-full rounded-lg overflow-hidden border border-[#6F9435]/30 relative">
          <div v-if="miniMapLoading" class="absolute inset-0 flex items-center justify-center bg-[#263D26]/90">
            <p class="text-sm text-[#8fa06a]">Memuat preview...</p>
          </div>
          <div v-if="miniMapError" class="absolute inset-0 flex items-center justify-center bg-[#263D26]/90 text-center p-4">
            <p class="text-sm text-[#f87171]">{{ miniMapError }}</p>
            <button @click="initMiniMap" class="mt-2 text-xs text-[#A7B92A] hover:underline">Coba lagi</button>
          </div>
        </div>
      </div>

      <!-- Zoom Settings -->
      <div class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
        <label class="block text-xs font-medium text-[#d4dc9a] mb-3">Level Zoom</label>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-xs font-medium text-[#d4dc9a] mb-1">Zoom Min</label>
            <input
              v-model.number="offlineZoomMin"
              type="range"
              :min="zoomBounds.min"
              :max="zoomBounds.max"
              class="w-full"
            />
            <p class="text-xs text-[#8fa06a] text-right">Zoom: {{ offlineZoomMin }}</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-[#d4dc9a] mb-1">Zoom Max</label>
            <input
              v-model.number="offlineZoomMax"
              type="range"
              :min="zoomBounds.min"
              :max="zoomBounds.max"
              class="w-full"
            />
            <p class="text-xs text-[#8fa06a] text-right">Zoom: {{ offlineZoomMax }}</p>
          </div>
        </div>
        <div class="mt-3 rounded-lg bg-[#263D26] p-3 text-xs text-[#8fa06a]">
          <p>Estimasi tile: {{ estimatedTiles.toLocaleString() }}</p>
          <p>Estimasi ukuran: ~{{ estimatedSize }}</p>
          <p v-if="archiveZoom" class="mt-1">
            Arsip peta memuat zoom {{ archiveZoom.min }}&ndash;{{ archiveZoom.max }}.
          </p>
        </div>
      </div>

<!-- Tampilan Peta: berlaku untuk peta utama, preview di atas, dan PNG yang
 diunduh. Ketiganya harus ikut berubah karena PNG diambil dari kanvas utama,
 jadi menyembunyikan kontur hanya di preview akan membuat berkas unduhan
 tetap berisi kontur. -->
<div class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
<label class="block text-xs font-medium text-[#d4dc9a] mb-3">Tampilan Peta</label>
<div class="space-y-2">
<label class="flex items-center gap-2 cursor-pointer">
<input
type="checkbox"
:checked="showContour"
@change="toggleLayer"
class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
/>
<span class="text-sm text-[#f0ead8]">Garis Kontur</span>
</label>
<label class="flex items-center gap-2 cursor-pointer">
<input
type="checkbox"
:checked="showHillshade"
@change="toggleHillshade"
class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
/>
<span class="text-sm text-[#f0ead8]">Hillshade</span>
</label>
<label class="flex items-center gap-2 cursor-pointer">
<input
type="checkbox"
:checked="showDistrictLabels"
@change="toggleDistrictLabels"
class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
/>
<span class="text-sm text-[#f0ead8]">Label Kabupaten/Kota</span>
</label>
<label class="flex items-center gap-2 cursor-pointer">
<input
type="checkbox"
:checked="showContourLabels"
@change="toggleContourLabels"
class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
/>
<span class="text-sm text-[#f0ead8]">Label Kontur</span>
</label>
</div>
<p class="mt-2 text-xs text-[#8fa06a]">
Berlaku untuk peta utama, preview, dan PNG yang diunduh.
</p>
</div>

<div class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
<label for="unduh-orientasi" class="block text-xs font-medium text-[#d4dc9a] mb-2">Format Lembar PNG</label>
<select
id="unduh-orientasi"
v-model="printOrientation"
class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2.5 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
>
<option v-for="option in SHEET_ORIENTATION_LIST" :key="option.value" :value="option.value">
{{ option.label }}
</option>
</select>
<p class="mt-2 text-xs text-[#8fa06a]">
Berlaku untuk tombol Cetak dan untuk PNG yang diunduh.
</p>
<!-- Pratinjau format. Kotak digambar dengan rasio sisi lembar yang benar-benar dipilih,
sehingga potrait terlihat memanjang dan landscape terlihat mendatar.
Angkanya di bawah diambil dari fungsi yang sama dengan penggambar lembar. -->
<div class="mt-3 flex items-start gap-4">
<div
class="flex shrink-0 flex-col overflow-hidden rounded border-2 border-[#A7B92A]/70 bg-[#f0ead8]"
:style="{
width: printFormatPreview.height <= printFormatPreview.width ? '160px' : '110px',
aspectRatio: `${printFormatPreview.width} / ${printFormatPreview.height}`,
}"
aria-hidden="true"
>
<div class="bg-[#A7B92A]/50" :style="{ height: '14%' }"></div>
<div class="flex-1 bg-[#c9d6b0]"></div>
<div class="bg-[#8fa06a]/40" :style="{ height: '7%' }"></div>
</div>
<div class="text-xs text-[#d4dc9a]">
<p class="font-semibold text-[#EDD330]">{{ printFormatPreview.label }}</p>
<p class="mt-1">Lembar: {{ printFormatPreview.width }} &times; {{ printFormatPreview.height }}</p>
<p>Berkas PNG: {{ printFormatPreview.outputWidth }} &times; {{ printFormatPreview.outputHeight }} piksel</p>
<p class="mt-1 text-[#8fa06a]">
Area peta dipatok supaya judul dan kaki halaman tetap muat.
</p>
</div>
</div>
</div>

<!-- Map Layout Options -->
<div class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
<label class="block text-xs font-medium text-[#d4dc9a] mb-3">Komponen Peta Offline</label>
        <div class="space-y-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="includeScaleBar"
              class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
            />
            <span class="text-sm text-[#f0ead8]">Scale Bar (Skala)</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="includeNorthArrow"
              class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
            />
            <span class="text-sm text-[#f0ead8]">Kompas (North Arrow)</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="includeLegend"
              class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
            />
            <span class="text-sm text-[#f0ead8]">Legenda Kontur</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="includeHistogram"
              class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
            />
            <span class="text-sm text-[#f0ead8]">Histogram Elevasi</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="includeGrid"
              class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
            />
            <span class="text-sm text-[#f0ead8]">Grid Koordinat</span>
          </label>
        </div>
      </div>

      <div v-if="downloadStatus" class="rounded-lg border border-[#6F9435]/30 bg-[#263D26] p-3 text-xs text-[#d4dc9a]">
        {{ downloadStatus }}
      </div>
      <button
        @click="downloadOffline"
        :disabled="downloading || (downloadMode === 'region' && !selectedOfflineRegion)"
        class="w-full rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ downloading ? 'Mengunduh...' : 'Mulai Unduh' }}
      </button>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import NavIcon from '@/Components/NavIcon.vue';
import { getActiveServiceWorker, SW_PROTOCOL } from '@/ServiceWorker.js';
import { useMapPngExport, SHEET_ORIENTATION_LIST, SHEET_LOGO_URL, describeSheet, scaleLabel } from '@/Composables/useMapPngExport.js';
import { BASEMAPS, PRINT_BASEMAP, basemapAttribution } from '@/basemaps.js';

const props = defineProps({
  mapConfig: Object,
  kabupatens: Array,
});

const page = usePage();
const mapContainer = ref(null);
const miniMapContainer = ref(null);
const map = ref(null);
const miniMap = ref(null);

const { isExporting, exportError, exportMapPng } = useMapPngExport();
const loading = ref(true);
const mapError = ref(null);
const showContour = ref(true);
const showHillshade = ref(false);
const showDistrictLabels = ref(false);
const showContourLabels = ref(false);

/**
 * Daftar lapisan untuk kartu "Lapisan Peta" di kepala halaman.
 *
 * Ojokan layer dikumpulkan di sini supaya tombolnya bisa di-v-for. Ojokan ini
 * memakai fungsi toggel yang sama dengan yang dipakai setLayerVisibility, jadi
 * toggel di kepala halaman dan checkbox di dialog unduhan tidak mungkin
 * navegar ke tempat berbeda.
 */
const LAPISAN_PETA = computed(() => [
  { key: 'contour', nama: 'Kontur', icon: 'contour', aktif: showContour.value, toggle: toggleLayer },
  { key: 'hillshade', nama: 'Relief', icon: 'hillshade', aktif: showHillshade.value, toggle: toggleHillshade },
  { key: 'label-kabupaten', nama: 'Label wilayah', icon: 'label', aktif: showDistrictLabels.value, toggle: toggleDistrictLabels },
  { key: 'label-kontur', nama: 'Label kontur', icon: 'label', aktif: showContourLabels.value, toggle: toggleContourLabels },
]);
const showOfflineModal = ref(false);
const downloading = ref(false);
const downloadStatus = ref('');
const mapStatus = ref('');
const offlineZoomMin = ref(8);
const offlineZoomMax = ref(14);
const selectedKabupaten = ref('');

// Kecamatan hanya bermakna kalau kabupatennya sudah dipilih. Reset-nya
// dilakukan di handler yang memang mengubah tampilan peta, bukan lewat
// watch, supaya tidak ada dua sumber kebenaran untuk pilihan ini.
const selectedKecamatan = ref('');
const miniMapLoading = ref(false);
const miniMapError = ref('');
const miniMapLoadingTimer = ref(null);

// Timer pesan "dimuat sebagian" dan handler window disimpan sebagai nama,
// bukan ditulis inline, karena semuanya harus bisa dibatalkan dan dilepas
// di onBeforeUnmount. Listener yang dibuat inline tidak punya rujukan untuk
// dilepas, jadi setiap kunjungan ke halaman ini akan menambah satu pasang.
let partialLoadTimer = null;

function onWindowOnline() {
  isOffline.value = false;
}

function onWindowOffline() {
  isOffline.value = true;
}

// Penanda urut pencarian, dipakai searchPlace() supaya jawaban yang sudah
// basi tidak menimpa jawaban pencarian yang lebih baru.
let searchSequence = 0;

// Mode unduh peta offline: 'full' untuk seluruh Sulawesi Selatan,
// 'region' untuk satu kabupaten/kota, atau satu kecamatan di dalamnya.
const downloadMode = ref('full');
const selectedOfflineRegion = ref('');

// Pilihan kecamatan pada dialog unduhan sengaja terpisah dari peta utama.
// Satu dialog boleh mengunduh wilayah yang berbeda dari yang sedang terlihat,
// jadi keduanya tidak boleh berbagi state.
const selectedOfflineKecamatan = ref('');

// Rentang zoom yang benar-benar ada di arsip PMTiles, dibaca dari header
// arsip. Tanpa ini slider menawarkan z13/z14 padahal arsip hanya memuat
// z8-z12, sehingga estimasi tile meng jauh lebih besar dari kenyataan.
const archiveZoom = ref(null);

const zoomBounds = computed(() => ({
  min: archiveZoom.value?.min ?? 6,
  max: archiveZoom.value?.max ?? 14,
}));

// Komponen yang ikut dibundel saat peta diunduh untuk offline.
const includeScaleBar = ref(true);
const includeNorthArrow = ref(true);
const includeLegend = ref(true);
const includeHistogram = ref(true);
const includeGrid = ref(true);

/**
 * Format lembar PNG yang dipilih pengguna.
 *
 * Satu untuk tombol Cetak dan untuk unduhan offline, supaya keduanya tidak
 * bisa keluar dengan format berbeda hanya karena salah satunya lupa mengirim
 * pilihannya.
 */
const printOrientation = ref('landscape');

/**
 * Pratinjau format lembar yang sedang dipilih.
 *
 * Angka dan rasionya berasal dari describeSheet(), jadi ukurannya sama dengan
 * berkas yang nanti diunduh. Pratinjau yang dikarang sendiri di halaman akan
 * menyimpang begitu ada format baru dan justru bikin pengguna salah paham,
 * persis yang pratinjau ini mau cegah.
 */
const printFormatPreview = computed(() => describeSheet(printOrientation.value));

/**
 * Lambang yang dicetak di header lembar PNG.
 *
 * Bukan logo ambalan yang diunggah lewat pengaturan, melainkan lambang resmi
 * urutan organisasi Kepramukaan yang juga dipakai sebagai bawaan ekspor PNG di
 * modul export. Alasannya lembar ini dibagikan sebagai bahan ajar: identitas
 * header harus sama di semua wilayah, tidak ikut berubah setiap kali admin
 * mengganti logo ambalan.
 */
const logoUrl = computed(() => SHEET_LOGO_URL);

/**
 * Satu-satunya sumber pilihan komponen cetak.
 *
 * Dipakai oleh tombol Cetak dan oleh unduhan offline. Keduanya sebelumnya
 * punya jalurnya sendiri: tombol Cetak tidak mengirim apa pun, jadi
 * buildMapPng memakai DEFAULT_COMPONENTS yang histogram dan grid-nya mati.
 * Akibatnya PNG "tampilan saat ini" diam-diam berbeda dari berkas unduhan
 * offline, padahal yang dimaksud dua hal itu sama.
 */
const printComponents = computed(() => ({
  scaleBar: includeScaleBar.value,
  northArrow: includeNorthArrow.value,
  legend: includeLegend.value,
  histogram: includeHistogram.value,
  grid: includeGrid.value,
}));

// Map controls state.
// Bilah skala dan kompas memakai kontrol bawaan MapLibre, jadi tidak ada
// state untuk keduanya; legenda dan readout koordinat dikendalikan di sini.
const showElevationLegend = ref(true);
const mouseCoords = ref(null);

// Skala 1:N yang sedang berlaku, ditulis sebagai pembilang saja supaya format
// Locale-nya dikendalikan di satu tempat. Kosong berarti peta belum punya kamera.
const mapScale = ref('');
const hasGeolocation = ref(false);
const locating = ref(false);

// Basemap
const basemap = ref('osm');

/**
 * Kredit sumber untuk PNG hasil ekspor.
 *
 * Ekspor PNG mengambil salinan kanvas WebGL, sedangkan atribusi MapLibre
 * digambar sebagai elemen DOM di atas kanvas itu. Elemen DOM tidak ikut
 * terbaca, jadi PNG harus menulis kreditnya sendiri. Kreditnya harus mengikuti
 * basemap yang sedang terlihat: berkas yang isinya citra satelit tidak boleh
 * mencantumkan OpenStreetMap.
 */
const sourceCredit = computed(() => `Sumber: ${basemapAttribution(basemap.value)}, PMTiles Kontur Sulsel`);

// Search
const searchQuery = ref('');
const searchFocused = ref(false);
const searchResults = ref([]);

// Measurement
const measurementMode = ref('none'); // 'none', 'distance', 'area'
const measurementPoints = ref([]);
const measurementDistance = ref(0);
const measurementArea = ref(0);

// Bookmarks
const bookmarks = ref([]);

// Offline detection
const isOffline = ref(!navigator.onLine);
const boundingBox = computed(() => props.mapConfig?.boundingBox ?? { west: 118.9, east: 121.6, south: -5.8, north: -1.8 });
const kabupatens = computed(() => props.kabupatens ?? []);
const hasPmtiles = computed(() => props.mapConfig?.hasPmtiles ?? false);

const geojsonUrl = computed(() => {
  const url = props.mapConfig?.geojsonUrl ?? '/storage/maps/batas_kabupaten_sulsel.geojson';
  if (url.startsWith('http')) {
    return url.replace(/^https?:\/\/[^\/]+/, '');
  }
  return url;
});

// Batas kecamatan bersifat opsional. Kalau berkasnya tidak ada, layer
// kecamatan sengaja tidak dibuat sama sekali: peta utama, peta mini, dan
// cetakan lalu kembali seperti sebelum batas kecamatan ditambahkan.
const hasKecamatanGeojson = computed(() => props.mapConfig?.hasKecamatanGeojson ?? false);

const kecamatanGeojsonUrl = computed(() => {
  const url = props.mapConfig?.kecamatanGeojsonUrl ?? '/storage/maps/batas_kecamatan_sulsel.geojson';
  if (url.startsWith('http')) {
    return url.replace(/^https?:\/\/[^\/]+/, '');
  }
  return url;
});

const selectedKabData = computed(() => {
  return kabupatens.value.find((k) => k.id_kab === selectedKabupaten.value) ?? null;
});

const selectedOfflineRegionData = computed(() => {
  return kabupatens.value.find((k) => k.id_kab === selectedOfflineRegion.value) ?? null;
});

/** Daftar kecamatan untuk-dialog unduhan, mengikuti kabupaten yang dipilih. */
const offlineKecamatanOptions = computed(() => selectedOfflineRegionData.value?.kecamatans ?? []);

const selectedOfflineKecData = computed(() => {
  return offlineKecamatanOptions.value.find((k) => k.id_kec === selectedOfflineKecamatan.value) ?? null;
});

// Daftar kecamatan milik kabupaten yang sedang dipilih. Kabupaten tanpa
// daftar menghasilkan daftar kosong, sehingga dropdown kedua tidak pernah
// menawarkan opsi yang bukan bagian dari kabupaten aktif.
const kecamatanOptions = computed(() => selectedKabData.value?.kecamatans ?? []);

const selectedKecData = computed(() => {
  return kecamatanOptions.value.find((k) => k.id_kec === selectedKecamatan.value) ?? null;
});

/**
 * Batas wilayah yang sedang dipilih: milik kecamatan kalau ada, kalau tidak
 * milik kabupaten.
 *
 * Sebelas dari 313 kecamatan belum punya batas sendiri karena lahir lebih baru
 * dari sumber batas yang dipakai. Fallback ke bbox kabupaten membuat
 * kecamatannya tetap bisa dicetak, hanya cakupannya mengikuti seluruh kabupaten.
 */
function bboxOf(wilayah, fallback) {
  if (Array.isArray(wilayah?.bbox) && wilayah.bbox.length === 4) return wilayah.bbox;
  return Array.isArray(fallback?.bbox) && fallback.bbox.length === 4 ? fallback.bbox : null;
}

/**
 * Rancang nama, kode, dan batas satu wilayah pilihan.
 *
 * Dua tempat butuh bentuk yang sama: peta utama untuk cetak, dan dialog unduhan
 * untuk paket tile offline. Bentuknya disatukan di sini supaya nama berkas PNG
 * dan halaman peta offline tidak pernah memakai rumusan berbeda untuk wilayah yang
 * sama.
 */
function wilayahInfo(kabupaten, kecamatan) {
  if (!kabupaten) return null;

  if (!kecamatan) {
    return {
      name: kabupaten.nama_kab,
      label: `Kabupaten ${kabupaten.nama_kab}`,
      kode: kabupaten.id_kab,
      bbox: bboxOf(kabupaten, null),
      namaKab: kabupaten.nama_kab,
      namaKec: null,
    };
  }

  return {
    name: `${kecamatan.nama_kec}, ${kabupaten.nama_kab}`,
    label: `Kecamatan ${kecamatan.nama_kec}, Kabupaten ${kabupaten.nama_kab}`,
    kode: kecamatan.id_kec,
    bbox: bboxOf(kecamatan, kabupaten),
    namaKab: kabupaten.nama_kab,
    namaKec: kecamatan.nama_kec,
  };
}

const selectedWilayah = computed(() => wilayahInfo(selectedKabData.value, selectedKecData.value));

const selectedWilayahBBox = computed(() => selectedWilayah.value?.bbox ?? null);

const selectedOfflineWilayah = computed(() =>
  wilayahInfo(selectedOfflineRegionData.value, selectedOfflineKecData.value)
);

// Peta mini hanya tampil setelah mode "satu daerah" aktif dan kabupaten/kota
// sudah dipilih, sehingga watch(showMiniMap) bisa membuat dan membuang peta.
const showMiniMap = computed(() => downloadMode.value === 'region' && selectedOfflineRegion.value !== '');

// Cakupan unduhan. Mode "satu daerah" memakai batas wilayah yang dipilih di
// dialog: batas kecamatannya kalau ada, kalau tidak batas kabupatennya.
const currentBBox = computed(() => {
  if (downloadMode.value === 'region' && selectedOfflineWilayah.value?.bbox) {
    const [west, south, east, north] = selectedOfflineWilayah.value.bbox;
    return { west, east, south, north };
  }
  return boundingBox.value;
});

const estimatedTiles = computed(() => {
  let total = 0;
  const bbox = currentBBox.value;
  const zStart = Math.max(offlineZoomMin.value, zoomBounds.value.min);
  const zEnd = Math.min(offlineZoomMax.value, zoomBounds.value.max);

  for (let z = zStart; z <= zEnd; z++) {
    const xMin = Math.floor(((bbox.west + 180) / 360) * Math.pow(2, z));
    const xMax = Math.ceil(((bbox.east + 180) / 360) * Math.pow(2, z)) - 1;
    const latRadN = (bbox.north * Math.PI) / 180;
    const latRadS = (bbox.south * Math.PI) / 180;
    const yMin = Math.ceil((1 - Math.log(Math.tan(latRadN) + 1 / Math.cos(latRadN)) / Math.PI) / 2 * Math.pow(2, z));
    const yMax = Math.floor((1 - Math.log(Math.tan(latRadS) + 1 / Math.cos(latRadS)) / Math.PI) / 2 * Math.pow(2, z));
    total += Math.max(0, (xMax - xMin + 1) * (yMax - yMin + 1));
  }
  return total;
});

const estimatedSize = computed(() => {
  const tiles = estimatedTiles.value;
  const avgTileSizeKb = 15;
  const mb = (tiles * avgTileSizeKb) / 1024;
  if (mb < 1) return `${Math.round(tiles * avgTileSizeKb)} KB`;
  if (mb < 1024) return `${mb.toFixed(1)} MB`;
  return `${(mb / 1024).toFixed(2)} GB`;
});

// Arsip PMTiles di-host di Supabase Storage, jadi URL-nya harus tetap absolut.
// Pustaka pmtiles membaca byte arsip langsung dari host tersebut lewat HTTP
// Range; membuang host membuat permintaan_RANGE mendarat di origin sendiri.
const pmtilesSourceUrl = computed(() => `pmtiles://${props.mapConfig?.pmtilesUrl ?? ''}`);

/**
 * Baca header arsip PMTiles untuk mengetahui rentang zoom yang tersedia.
 *
 * Header hanya 127 byte dan diambil lewat satu HTTP Range, jadi Murah. Kalau
 * pembacaan gagal, rentang slider dibiarkan apa adanya: service worker tetap
 * memotong rentang ke arsip sebelum mengunduh.
 */
async function loadArchiveZoomRange() {
  if (archiveZoom.value || !hasPmtiles.value || !props.mapConfig?.pmtilesUrl) return;

  try {
    const { PMTiles, FetchSource } = await import('pmtiles');
    const header = await new PMTiles(new FetchSource(props.mapConfig.pmtilesUrl)).getHeader();

    if (header?.minZoom != null && header?.maxZoom != null) {
      archiveZoom.value = { min: header.minZoom, max: header.maxZoom };
      offlineZoomMin.value = Math.min(Math.max(offlineZoomMin.value, header.minZoom), header.maxZoom);
      offlineZoomMax.value = Math.min(Math.max(offlineZoomMax.value, header.minZoom), header.maxZoom);
    }
  } catch {
    // Header tidak terbaca: biarkan rentang slider, service worker yang memotong.
  }
}

// Utility functions
function formatDistance(meters) {
  if (meters >= 1000) return `${(meters / 1000).toFixed(2)} km`;
  return `${Math.round(meters)} m`;
}

function formatArea(sqMeters) {
  if (sqMeters >= 1000000) return `${(sqMeters / 1000000).toFixed(2)} km²`;
  if (sqMeters >= 10000) return `${(sqMeters / 10000).toFixed(2)} ha`;
  return `${Math.round(sqMeters)} m²`;
}

function calculateDistance(coord1, coord2) {
  const R = 6371000; // Earth radius in meters
  const lat1 = coord1[1] * Math.PI / 180;
  const lat2 = coord2[1] * Math.PI / 180;
  const dLat = (coord2[1] - coord1[1]) * Math.PI / 180;
  const dLon = (coord2[0] - coord1[0]) * Math.PI / 180;
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon/2) * Math.sin(dLon/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}

function calculateArea(coords) {
  // Shoelace formula for polygon area on sphere (approximate)
  let area = 0;
  for (let i = 0; i < coords.length - 1; i++) {
    area += (coords[i+1][0] - coords[i][0]) * (2 + Math.sin(coords[i][1] * Math.PI/180) + Math.sin(coords[i+1][1] * Math.PI/180));
  }
  return Math.abs(area * 6371000 * 6371000 * Math.PI / 180 / 2);
}

/**
 * Pasangan layer: peta utama, lalu layer kembarannya di preview dialog.
 *
 * Preview memakai peta MapLibre kedua, jadi satu tombol tidak boleh hanya
 * mengubah peta utama. Kalau tidak, preview tetap menampilkan kontur yang
 * sudah disembunyikan di kanvas, dan PNG yang diunduh berbeda dari yang
 * dilihat pengguna.
 */
const MINI_LAYER_MIRROR = {
  'garis-kontur': 'mini-kontur',
  'hillshade-layer': 'mini-hillshade',
  'kabupaten-labels': 'mini-kabupaten-labels',
  'kontur-labels': 'mini-kontur-labels',
};

function setLayerVisibility(layerId, visible) {
  const visibility = visible ? 'visible' : 'none';

  const apply = (target, id) => {
    if (target?.getLayer?.(id)) {
      target.setLayoutProperty(id, 'visibility', visibility);
      return true;
    }

    return false;
  };

  const mainDone = apply(map.value, layerId);

  const miniLayerId = MINI_LAYER_MIRROR[layerId];
  const miniDone = miniLayerId ? apply(miniMap.value, miniLayerId) : true;

  // Layer belum ada karena peta masih disusun. Keadaannya sudah tersimpan di
  // ref, jadi cukup tunggu peta selesai lalu terapkan lagi. Tanpa ini toggles
  // yang ditekan sebelum peta selesai akan hilang begitu style selesai diurai.
  if (!mainDone && map.value && !map.value.loaded()) {
    map.value.once('load', () => setLayerVisibility(layerId, visible));
  }

  if (!miniDone && miniMap.value && !miniMap.value.loaded()) {
    miniMap.value.once('load', () => setLayerVisibility(layerId, visible));
  }
}

function toggleLayer() {
  showContour.value = !showContour.value;
  setLayerVisibility('garis-kontur', showContour.value);
}

function highlightKabupaten(idKab) {
  if (!map.value) return;
  const layerId = 'kabupaten-highlight';
  if (map.value.getLayer(layerId)) {
    if (idKab) {
      map.value.setFilter(layerId, ['==', ['get', 'id_kab'], idKab]);
    } else {
      map.value.setFilter(layerId, ['==', ['get', 'id_kab'], '']);
    }
  } else {
    // Layer not ready yet, wait for map load
    map.value.once('load', () => {
      if (map.value.getLayer(layerId)) {
        if (idKab) {
          map.value.setFilter(layerId, ['==', ['get', 'id_kab'], idKab]);
        } else {
          map.value.setFilter(layerId, ['==', ['get', 'id_kab'], '']);
        }
      }
    });
  }
}

/**
 * Sorot batas kecamatan yang dipilih.
 *
 * Layer kecamatan dibuat hanya kalau berkas geojsonnya tersedia, jadi fungsi
 * ini tidak boleh melempar galat saat layer-nya tidak ada. Filter kosong
 * ('id_kec' sama dengan string kosong) sengaja dipakai sebagai kondisi "tidak
 * ada yang tersorot", karena tidak ada nilai id_kec yang kosong di data.
 *
 * Highlight yang sama diberikan pada dua layer: pengisi area dan garis tepi.
 * Kalau hanya garis yang disorot, isi kecamatan_selected tampak sama dengan
 * tetangganya dan highlight-nya tidak terbaca.
 */
function highlightKecamatan(idKec) {
  if (!map.value) return;

  const apply = (target) => {
    target.setFilter('kecamatan-highlight', ['==', ['get', 'id_kec'], idKec ?? '']);
    target.setFilter('kecamatan-highlight-line', ['==', ['get', 'id_kec'], idKec ?? '']);
  };

  if (map.value.getLayer('kecamatan-highlight')) {
    apply(map.value);
    return;
  }

  // Layer belum siap. Menunggu 'load' hanya membantu ketika peta sedang
  // dimuat; kalau peta sudah selesai dimuat tanpa layer ini (berkas geojson
  // tidak ada), callback-nya tidak akan pernah jalan dan highlight dilewati.
  if (!map.value.loaded()) return;

  map.value.once('load', () => {
    if (map.value?.getLayer('kecamatan-highlight')) apply(map.value);
  });
}

function onKabupatenSelect() {
  // Kecamatan milik kabupaten sebelumnya harus dibuang lebih dulu. Kalau tidak,
  // pilihan lama masih tertahan di state dan tidak lagi ada di daftar yang
  // baru, sehingga selectedKecData bernilai null padahal layarnya masih
  // menampilkan nama kecamatan yang tidak berlaku.
  selectedKecamatan.value = '';

  const kab = selectedKabData.value;
  if (!map.value || !kab || !kab.bbox) return;

  const [west, south, east, north] = kab.bbox;
  map.value.fitBounds(
    [[west, south], [east, north]],
    { padding: 40, duration: 2000 }
  );
  highlightKabupaten(kab.id_kab);
  // Kabupaten yang baru dipilih tidak mewarisi sorotan kecamatan sebelumnya.
  highlightKecamatan('');
  mapStatus.value = `Menampilkan ${kab.nama_kab} (BBox: ${kab.bbox.join(', ')})`;
}

/**
 * Ambil peta ke kecamatan yang dipilih.
 *
 * Kecamatan memakai bbox sendiri supaya bingkai dan cetakannya mengikuti
 * kecamatan, bukan seluruh kabupaten. Kecamatan tanpa bbox memakai bbox
 * kabupaten sehingga tombol cetak tetap menghasilkan peta yang benar.
 */
function onKecamatanSelect() {
  const kec = selectedKecData.value;
  const kab = selectedKabData.value;
  if (!map.value || !kec || !kab) return;

  const bbox = selectedWilayahBBox.value;
  if (!bbox) return;

  const [west, south, east, north] = bbox;
  map.value.fitBounds(
    [[west, south], [east, north]],
    { padding: 40, duration: 2000 }
  );
  highlightKabupaten(kab.id_kab);
  // Sebelas kecamatan belum punya batas sendiri, jadi tidak ada yang bisa
  // disorot untuk mereka. Filter kosong dipakai untuk membatalkan sorotan
  // sebelumnya, dan status di bawah menjelaskan kenapa sorotan tidak muncul.
  highlightKecamatan(kec.id_kec);

  mapStatus.value = Array.isArray(kec.bbox)
    ? `Menampilkan Kecamatan ${kec.nama_kec} (${kec.id_kec}) di ${kab.nama_kab} (BBox: ${bbox.join(', ')})`
    : `Kecamatan ${kec.nama_kec} (${kec.id_kec}) di ${kab.nama_kab} belum punya batas sendiri, peta menampilkan seluruh kabupaten.`;
}

/** Cetak wilayah pilihan, atau seluruh Sulawesi Selatan kalau belum ada pilihan. */
async function printWilayah() {
  const wilayah = selectedWilayah.value;
  if (!wilayah) {
    mapStatus.value = 'Pilih kabupaten atau kecamatan lebih dulu supaya peta dicetak sesuai wilayah yang dimaksud.';
    return;
  }

  const bbox = selectedWilayahBBox.value;
  if (!bbox) return;

  await printMapPng({
    area: { name: wilayah.name, label: wilayah.label },
    region: { west: bbox[0], south: bbox[1], east: bbox[2], north: bbox[3], maxZoom: 14 },
    components: {
      scaleBar: includeScaleBar.value,
      northArrow: includeNorthArrow.value,
      legend: includeLegend.value,
      histogram: includeHistogram.value,
      grid: includeGrid.value,
    },
  });
}

function clearSelection() {
  selectedKabupaten.value = '';
  selectedKecamatan.value = '';
  highlightKabupaten('');
  highlightKecamatan('');
  if (map.value) {
    map.value.fitBounds(
      [[boundingBox.value.west, boundingBox.value.south], [boundingBox.value.east, boundingBox.value.north]],
      { padding: 40, duration: 2000 }
    );
  }
  mapStatus.value = 'Peta dikembalikan ke tampilan Sulawesi Selatan.';
}

function initMiniMap() {
  // Preview mengikuti wilayah yang dipilih di dialog, bukan hanya kabupatennya.
  // Kalau hanya kabupaten yang dipakai, preview untuk kecamatan yang lebih kecil
  // akan menampilkan potongan yang jauh lebih luas daripada yang diunduh.
  const wilayah = selectedOfflineWilayah.value;
  if (!miniMapContainer.value || !wilayah?.bbox || !hasPmtiles.value) return;
  if (miniMap.value) {
    miniMap.value.remove();
    miniMap.value = null;
  }

  const [west, south, east, north] = wilayah.bbox;

  miniMapLoading.value = true;
  miniMapError.value = '';

  import('../../maplibre').then(({ Map, addProtocol, GLYPHS_URL }) => {
    import('pmtiles').then(({ Protocol }) => {
      const protocol = new Protocol();
      addProtocol('pmtiles', protocol.tile);

      try {
        miniMap.value = new Map({
          container: miniMapContainer.value,
          style: {
            version: 8,
            // Wajib karena preview punya layer symbol. Tanpa glyphs MapLibre gagal
            // menyusun shader teks, render loop berhenti, dan preview hanya
            // garis kontur tanpa label apa pun.
            glyphs: GLYPHS_URL,
            sources: {
              // Preview tanpa basemap hanya menampilkan garis kontur dan
              // batas wilayah di atas latar kosong, jadi bentuk wilayahnya
              // tidak terbaca. OSM disamakan dengan peta utama supaya yang
              // dipratinjau sama dengan yang akan diunduh.
              'mini-basemap': BASEMAPS.osm,
              'kontur': { type: 'vector', url: pmtilesSourceUrl.value },
              'batas': { type: 'geojson', data: geojsonUrl.value },
              // DEM yang sama dengan peta utama, supaya hillshade di preview
              // sama persis dengan yang terlihat di kanvas utama.
              'mini-hillshade-tiles': {
                type: 'raster-dem',
                tiles: ['https://s3.amazonaws.com/elevation-tiles-prod/terrarium/{z}/{x}/{y}.png'],
                encoding: 'terrarium',
                tileSize: 256,
                maxzoom: 14,
              },
              // Source kecamatan dibuat bersyarat supaya preview tetap jalan
              // di pemasangan yang belum punya berkas batas kecamatan.
              ...(hasKecamatanGeojson.value
                ? { 'kecamatan': { type: 'geojson', data: kecamatanGeojsonUrl.value } }
                : {}),
            },
            layers: [
              { id: 'mini-basemap-layer', type: 'raster', source: 'mini-basemap' },
              {
                id: 'mini-hillshade',
                type: 'hillshade',
                source: 'mini-hillshade-tiles',
                layout: { visibility: showHillshade.value ? 'visible' : 'none' },
                paint: {
                  'hillshade-illumination-direction': 315,
                  'hillshade-illumination-anchor': 'map',
                  'hillshade-exaggeration': 0.5,
                  'hillshade-shadow-color': 'rgba(0, 0, 0, 0.5)',
                  'hillshade-highlight-color': 'rgba(255, 255, 255, 0.5)',
                  'hillshade-accent-color': 'rgba(140, 81, 10, 0.3)',
                },
              },
              { id: 'mini-kontur', type: 'line', source: 'kontur', 'source-layer': 'kontur',
                layout: {
                  'line-join': 'round',
                  'line-cap': 'round',
                  visibility: showContour.value ? 'visible' : 'none',
                },
                paint: {
                  'line-color': '#8c510a',
                  'line-width': [
                    'case',
                    ['==', ['%', ['get', 'ELEV'], 50], 0],
                    1.8,
                    0.8,
                  ],
                  'line-opacity': 0.8,
                } },
              { id: 'mini-kontur-labels', type: 'symbol', source: 'kontur', 'source-layer': 'kontur',
                filter: ['==', ['%', ['get', 'ELEV'], 50], 0],
                layout: {
                  visibility: showContourLabels.value ? 'visible' : 'none',
                  'symbol-placement': 'line',
                  'text-field': ['concat', ['get', 'ELEV'], ' m'],
                  'text-font': ['Open Sans Regular'],
                  'text-size': 10,
                },
                // Warna dan halo adalah paint, bukan layout. MapLibre menolak
                // seluruh style kalau satu properti diletakkan di blok yang salah,
                // lalu preview tidak menggambar apa pun. Nilainya disamakan dengan
                // layer kontur peta utama supaya preview tidak bohong soal tampilan.
                paint: {
                  'text-color': '#8c510a',
                  'text-halo-color': '#fff',
                  'text-halo-width': 1.5,
                  'text-halo-blur': 1,
                } },
              ...(hasKecamatanGeojson.value
                ? [{ id: 'mini-kecamatan', type: 'line', source: 'kecamatan',
                    paint: { 'line-color': '#0f766e', 'line-width': 0.6, 'line-opacity': 0.7 } }]
                : []),
              // Isi wilayah yang diunduh disorot supaya preview menunjukkan
              // tepat apa yang akan diterima pengguna, bukan hanya kotanya.
              ...(hasKecamatanGeojson.value && wilayah.namaKec
                ? [{ id: 'mini-kecamatan-highlight', type: 'fill', source: 'kecamatan',
                    filter: ['==', ['get', 'id_kec'], wilayah.kode],
                    paint: { 'fill-color': '#EDD330', 'fill-opacity': 0.25 } }]
                : []),
              { id: 'mini-batas', type: 'line', source: 'batas',
                paint: { 'line-color': '#2563eb', 'line-width': 1, 'line-dasharray': [1, 1] } },
              { id: 'mini-kabupaten-labels', type: 'symbol', source: 'batas',
                filter: ['==', ['get', 'id_kab'], ['get', 'id_kab']],
                layout: {
                  visibility: showDistrictLabels.value ? 'visible' : 'none',
                  'text-field': ['get', 'nama_kab'],
                  'text-font': ['Open Sans Bold', 'Open Sans Regular'],
                  'text-size': 11,
                  'text-anchor': 'center',
                  'text-allow-overlap': true,
                },
                paint: {
                  'text-color': '#1f2937',
                  'text-halo-color': '#fff',
                  'text-halo-width': 2,
                  'text-halo-blur': 1,
                } },
            ],
          },
          center: [(west + east) / 2, (south + north) / 2],
          zoom: 8,
          maxZoom: 14,
        });

        miniMap.value.on('load', () => {
          miniMapLoading.value = false;
          clearTimeout(miniMapLoadingTimer.value);
          // Fit to the selected region's bbox for better preview
          miniMap.value?.fitBounds(
            [[west, south], [east, north]],
            { padding: 20, duration: 1000 }
          );
        });

        miniMap.value.on('error', (e) => {
          miniMapLoading.value = false;
          clearTimeout(miniMapLoadingTimer.value);
          miniMapError.value = `Gagal memuat preview: ${e.error?.message || 'Kesalahan peta'}`;
          console.error('Mini map error:', e);
        });

        // Peta utama punya batas waktu, preview belum punya. Kalau satu source
        // tidak pernah menyelesaikan diri, event load maupun error sama sekali
        // tidak datang dan "Memuat preview..." menutupi peta selamanya tanpa
        // ada tombol untuk membukanya lagi. Batas waktunya tidak membatalkan
        // peta: preview yang telat tetap tampil begitu tile-nya tiba, hanya
        // teks penutupnya yang lebih dulu hilang.
        clearTimeout(miniMapLoadingTimer.value);
        miniMapLoadingTimer.value = setTimeout(() => {
          if (miniMapLoading.value) {
            miniMapLoading.value = false;
          }
        }, 8000);
      } catch (error) {
        miniMapLoading.value = false;
        miniMapError.value = `Gagal inisialisasi preview: ${error.message}`;
        console.error('Mini map init error:', error);
      }
    });
  });
}

/**
 * Beri tahu peta kalau ukuran wadahnya berubah.
 *
 * MapLibre tidak pernah memeriksa ukuran wadah sendiri. Padahal ukuran wadah
 * peta ini berubah beberapa kali setelah halaman pertama kali dibuka, dan
 * hampir semuanya terjadi di ponsel:
 *
 * - `h-[70vh]` memakai satuan vh, yang ukurannya ikut berubah saat bilah
 *   alamat browser muncul atau menghilang. Kanvas peta kalau tidak ikut
 *   resize akan tertinggal dari wadahnya, jadi kontrol di pojok peta berdiri
 *   di atas peta yang salah tempat dan sebagian peta tampak kosong.
 * - Memutar ponsel menukar lebar dan tinggi.
 * - Panel di atas peta membungkus ke baris baru saat layarnya menyempit.
 *
 * Semua itu diperbaiki dengan satu `resize()`. Permintaannya dikumpulkan per
 * frame karena `resize()` mengalokasikan ulang kanvas WebGL, jadi menjalankannya
 * untuk setiap callback ResizeObserver akan berat dan membuat peta berkedip.
 */
let mapResizeObserver = null;
let mapResizeFrame = null;

function observeMapSize() {
  if (typeof ResizeObserver === 'undefined' || !mapContainer.value) return;

  mapResizeObserver = new ResizeObserver(() => {
    if (!map.value || mapResizeFrame !== null) return;

    mapResizeFrame = requestAnimationFrame(() => {
      mapResizeFrame = null;

      try {
        map.value?.resize();
      } catch {
        // Peta yang sudah dibuang tidak bisa diukur ulang. Halaman sudah tidak
        // aktif, jadi tidak ada yang perlu diberitahukan ke pengguna.
      }
    });
  });

  mapResizeObserver.observe(mapContainer.value);
}

function stopObservingMapSize() {
  mapResizeObserver?.disconnect();
  mapResizeObserver = null;

  if (mapResizeFrame !== null) {
    cancelAnimationFrame(mapResizeFrame);
    mapResizeFrame = null;
  }
}

async function initMap() {
  if (!mapContainer.value) return;

  try {
    const { Map, addProtocol, Popup, ScaleControl, NavigationControl, GLYPHS_URL } = await import('../../maplibre');

    // Source kontur hanya boleh dibuat bila file PMTiles benar-benar ada.
    // Kalau tidak, basemap dan batas kabupaten tetap bisa digambar.
    let konturSource = null;
    if (hasPmtiles.value) {
      const { Protocol } = await import('pmtiles');
      const protocol = new Protocol();
      addProtocol('pmtiles', protocol.tile);
      konturSource = { type: 'vector', url: pmtilesSourceUrl.value };
    }

    const konturLayers = konturSource
      ? [
          {
            id: 'garis-kontur',
            type: 'line',
            source: 'kontur-sulsel',
            'source-layer': 'kontur',
            layout: {
              'line-join': 'round',
              'line-cap': 'round',
              visibility: showContour.value ? 'visible' : 'none',
            },
            paint: {
              'line-color': '#8c510a',
              'line-width': [
                'case',
                ['==', ['%', ['get', 'ELEV'], 50], 0],
                1.8,
                0.8,
              ],
              'line-opacity': 0.8,
            },
          },
          {
            id: 'kontur-labels',
            type: 'symbol',
            source: 'kontur-sulsel',
            'source-layer': 'kontur',
            filter: ['==', ['%', ['get', 'ELEV'], 50], 0],
            layout: {
              visibility: showContourLabels.value ? 'visible' : 'none',
              'symbol-placement': 'line',
              'text-field': ['concat', ['get', 'ELEV'], ' m'],
              'text-font': ['Open Sans Regular'],
              'text-size': 10,
            },
            // Properti warna dan halo adalah paint, bukan layout. MapLibre menolak
            // seluruh style kalau satu properti diletakkan di blok yang salah,
            // lalu peta tidak menggambar apa pun. Nama warnanya text-color;
            // tidak ada properti text-fill di spesifikasi MapLibre.
            paint: {
              'text-color': '#8c510a',
              'text-halo-color': '#fff',
              'text-halo-width': 1.5,
              'text-halo-blur': 1,
            },
          },
        ]
      : [];

    const sources = {
      ...(konturSource ? { 'kontur-sulsel': konturSource } : {}),
      'batas-kabupaten': {
        type: 'geojson',
        data: geojsonUrl.value,
      },
      // Batas kecamatan hanya perlu ada kalau berkasnya benar-benar tersedia.
      // MapLibre menolak style yang memakai source yang gagal dimuat, jadi
      // source-nya dibuat bersyarat, bukan selalu dideklarasikan.
      ...(hasKecamatanGeojson.value
        ? { 'kecamatan-batas': { type: 'geojson', data: kecamatanGeojsonUrl.value } }
        : {}),
      // Keempat basemap disalin dari definisi tunggal supaya teks atribusi
      // lisensinya tidak bisa berbeda dari halaman Peta/Index.vue.
      'osm-tiles': BASEMAPS.osm,
      'satellite-tiles': BASEMAPS.satellite,
      'terrain-tiles': BASEMAPS.terrain,
      'dark-tiles': BASEMAPS.dark,
      'hillshade-tiles': {
        type: 'raster-dem',
        // DEM diambil dari Terrarium di S3, bukan dari host OpenTopoMap. Bentuk
        // jamak tiles.opentopomap.org menyajikan sertifikat TLS yang tidak cocok
        // dengan nama hostnya sehingga browser menolak koneksi dan layer
        // hillshade tidak pernah punya data; bentuk tunggalnya hanya menyajikan
        // gambar raster, bukan DEM. Terrarium menyajikan DEM SRTM yang sama
        // dengan sertifikat yang sah.
        tiles: ['https://s3.amazonaws.com/elevation-tiles-prod/terrarium/{z}/{x}/{y}.png'],
        encoding: 'terrarium',
        tileSize: 256,
        maxzoom: 14,
      },
    };

    const layers = [
      {
        id: 'osm-layer',
        type: 'raster',
        source: 'osm-tiles',
        layout: { visibility: 'visible' },
      },
      {
        id: 'satellite-layer',
        type: 'raster',
        source: 'satellite-tiles',
        layout: { visibility: 'none' },
      },
      {
        id: 'terrain-layer',
        type: 'raster',
        source: 'terrain-tiles',
        layout: { visibility: 'none' },
      },
      {
        id: 'dark-layer',
        type: 'raster',
        source: 'dark-tiles',
        layout: { visibility: 'none' },
      },
      {
        id: 'hillshade-layer',
        type: 'hillshade',
        source: 'hillshade-tiles',
        layout: { visibility: showHillshade.value ? 'visible' : 'none' },
        paint: {
          'hillshade-illumination-direction': 315,
          'hillshade-illumination-anchor': 'map',
          'hillshade-exaggeration': 0.5,
          'hillshade-shadow-color': 'rgba(0, 0, 0, 0.5)',
          'hillshade-highlight-color': 'rgba(255, 255, 255, 0.5)',
          'hillshade-accent-color': 'rgba(140, 81, 10, 0.3)',
        },
      },
      ...konturLayers,
      // Batas kecamatan digambar di atas garis kontur tapi di bawah garis batas
      // kabupaten, supaya yang terlihat paling depan tetap batas wilayah yang
      // sedang disorot.
      ...(hasKecamatanGeojson.value
        ? [
            {
              id: 'kecamatan-batas',
              type: 'line',
              source: 'kecamatan-batas',
              paint: {
                'line-color': '#0f766e',
                'line-width': 0.6,
                'line-opacity': 0.7,
              },
            },
            {
              id: 'kecamatan-highlight',
              type: 'fill',
              source: 'kecamatan-batas',
              filter: ['==', ['get', 'id_kec'], ''],
              paint: {
                'fill-color': '#EDD330',
                'fill-opacity': 0.25,
              },
            },
            {
              id: 'kecamatan-highlight-line',
              type: 'line',
              source: 'kecamatan-batas',
              filter: ['==', ['get', 'id_kec'], ''],
              paint: {
                'line-color': '#A7B92B',
                'line-width': 2.5,
              },
            },
          ]
        : []),
      {
        id: 'kabupaten-border',
        type: 'line',
        source: 'batas-kabupaten',
        paint: {
          'line-color': '#2563eb',
          'line-width': 1.5,
          'line-dasharray': [2, 2],
        },
      },
      {
        id: 'kabupaten-highlight',
        type: 'fill',
        source: 'batas-kabupaten',
        filter: ['==', ['get', 'id_kab'], ''],
        paint: {
          'fill-color': '#3b82f6',
          'fill-opacity': 0.15,
        },
      },
      {
        id: 'kabupaten-labels',
        type: 'symbol',
        source: 'batas-kabupaten',
        filter: ['==', ['get', 'id_kab'], ['get', 'id_kab']],
        layout: {
          visibility: showDistrictLabels.value ? 'visible' : 'none',
          'text-field': ['get', 'nama_kab'],
          'text-font': ['Open Sans Bold', 'Open Sans Regular'],
          'text-size': [
            'interpolate',
            ['linear'],
            ['zoom'],
            8, 10,
            12, 14,
          ],
          'text-anchor': 'center',
          'text-allow-overlap': true,
        },
        paint: {
          'text-color': '#1f2937',
          'text-halo-color': '#fff',
          'text-halo-width': 2,
          'text-halo-blur': 1,
        },
      },
    ];

    // Create map with multiple basemap sources
    map.value = new Map({
      container: mapContainer.value,
      // Wajib agar kanvas peta bisa dibaca sebagai gambar saat diekspor jadi
      // PNG. Tanpa ini browser boleh membuang buffer WebGL tepat setelah frame
      // selesai digambar, sehingga toDataURL() mengembalikan kanvas kosong.
      // Preview daerah di bawah tidak perlu, jadi hanya peta utama yang
      // mengaktifkannya.
      preserveDrawingBuffer: true,
      // Batas kanvas WebGL ditulis eksplisit, bukan mengandalkan bawaan
      // MapLibre. Saat PNG dicetak, rasio piksel kanvas sengaja dinaikkan supaya
      // petanya tajam; kalau batasnya lebih kecil, MapLibre menurunkannya lagi
      // secara diam-diam dan hasilnya tetap buram tanpa ada tanda yang terlihat.
      // Nilai 4096 adalah ukuran yang aman di hampir semua GPU, termasuk ponsel.
      maxCanvasSize: [4096, 4096],
      style: {
        version: 8,
        // Layer symbol (kontur-labels, kabupaten-labels) memakai text-field,
        // jadi style wajib punya glyphs. Tanpa itu MapLibre gagal menyusun
        // shader teks: error-nyauncaught dan render loop berhenti, sehingga
        // kanvas tetap abu-abu walau peta dan konturnya sudah termuat.
        glyphs: GLYPHS_URL,
        sources,
        layers,
      },
      center: [120.2, -3.3],
      zoom: 10,
      maxZoom: 14,
    });

    // Bilah skala dan kompas. Keduanya kontrol bawaan MapLibre, bukan wadah kosong:
    // versi lama pernah membuat wadah sendiri dengan ref yang tidak pernah diisi
    // sambil tetap menambahkan ScaleControl, jadi peta menampilkan dua bilah
    // skala dan satu di antaranya tidak pernah berisi apa pun.
    map.value.addControl(new ScaleControl({ maxWidth: 200, unit: 'metric' }), 'bottom-left');
    map.value.addControl(new NavigationControl({ showCompass: true, showZoom: false }), 'top-right');

    // Kontrol lokasi bawaan tidak dipakai: tombol lokasi di template memanggil
    // locateUser() yang juga menampilkan popup "Lokasi Anda" dan menulis
    // alasannya ke mapStatus bila lokasi ditolak.
    if ('geolocation' in navigator) {
      hasGeolocation.value = true;
    }

    // Skala 1:N mengikuti kamera, jadi harus dihitung ulang setiap kali zoom atau
    // pusat peta berubah, bukan hanya sekali saat peta dimuat. Peta yang digeser
    // ke utara atau selatan pada zoom yang sama pun angkanya berubah, karena
    // cos(latitude) ikut berubah, jadi event geraknya ikut didengarkan.
    const updateMapScale = () => {
      const center = map.value?.getCenter?.();
      const zoom = map.value?.getZoom?.();

      if (!center || !Number.isFinite(zoom)) {
        mapScale.value = '';
        return;
      }

      const label = scaleLabel(center.lat, zoom);

      // Di dekat kutub cos(latitude) mendekati nol sehingga 1:N-nya jatuh ke
      // angka yang tidak masuk akal. scaleLabel() sudah mengembalikan "1:-" untuk
      // kasus itu, jadi di sini cukup disembunyikan saja.
      mapScale.value = label === '1:-' ? '' : label.slice(2);
    };

    map.value.on('move', updateMapScale);
    map.value.on('zoom', updateMapScale);
    updateMapScale();

    // Mouse move - coordinate display
    map.value.on('mousemove', (e) => {
      mouseCoords.value = { lng: e.lngLat.lng, lat: e.lngLat.lat };
    });

    map.value.on('mouseleave', () => {
      mouseCoords.value = null;
    });

    // Click - feature info (district popup)
    map.value.on('click', 'batas-kabupaten', (e) => {
      const feature = e.features[0];
      if (feature && feature.properties) {
        const props = feature.properties;
        const koordinat = [e.lngLat.lng, e.lngLat.lat];

        new Popup({ closeButton: true, maxWidth: '300px' })
          .setLngLat(e.lngLat)
          .setDOMContent(popupWilayah(
            props.nama_kab || 'Kabupaten',
            props.id_kab || 'N/A',
            props.nama_prov || 'Sulawesi Selatan',
            koordinat,
          ))
          .addTo(map.value);
      }
    });

    // Change cursor on hover
    map.value.on('mouseenter', 'batas-kabupaten', () => {
      map.value.getCanvas().style.cursor = 'pointer';
    });
    map.value.on('mouseleave', 'batas-kabupaten', () => {
      map.value.getCanvas().style.cursor = '';
    });

    // Status daring atau luar jaringan mengikuti event window, jadi keduanya
    // diberi nama dan dilepas lagi saat halaman ditutup. Tanpa itu setiap
    // kunjungan berikutnya menambah satu pasang listener lagi, dan instance
    // yang sudah ditutup tetap bereaksi terhadap event lama.
    window.addEventListener('online', onWindowOnline);
    window.addEventListener('offline', onWindowOffline);

    // Overlay "Memuat peta..." tidak boleh menggantung selamanya, tapi juga tidak
    // boleh hilang sebelum kontennya benar-benar ada.
    //
    // Melepasnya dari event `styledata` keliru: event itu fire paling awal,
    // hanya setelah JSON style terurai, sehingga overlay hilang seketika lalu
    // garis kontur terlihat macet selama tile-nya masih turun. Penanda loading
    // kini dilepas setelah peta benar-benar selesai (load atau idle), atau lewat
    // batas waktu kalau ada satu sumber yang menggantung. Galat yang sebenarnya
    // tetap dilaporkan lewat mapError.
    const clearLoading = () => {
      loading.value = false;
    };

    map.value.on('idle', clearLoading);

    // Batas waktu untuk pesan "dimuat sebagian", dibatalkan lagi di onBeforeUnmount.
    partialLoadTimer = setTimeout(() => {
      if (loading.value) {
        clearLoading();
        mapStatus.value = 'Peta dimuat sebagian: sebagian layer belum selesai masuk dan masih dimuat di latar belakang.';
      }
    }, 12000);

    map.value.on('load', () => {
      clearLoading();
      mapError.value = null;

      // Layout sering baru settles setelah style selesai diurai. Kalau ukuran
      // wadahnya berubah di saat itu, canvas lahir dengan ukuran lama dan tidak
      // pernah benar tanpa resize di sini.
      map.value?.resize();
      mapStatus.value = hasPmtiles.value
        ? 'Peta kontur Sulawesi Selatan dimuat. Garis kontur setiap 10 meter elevasi.'
        : 'Peta dasar dan batas kabupaten dimuat. Garis kontur belum tersedia karena file PMTiles tidak ada di server.';

      // Restore bookmarks from localStorage
      loadBookmarks();
    });

    map.value.on('error', (e) => {
      clearLoading();
      const errorMsg = e.error?.message || 'Kesalahan peta';
      mapStatus.value = `Peringatan: ${errorMsg}`;
      // Error peta harus tetap terlihat di console. Kalau hanya ditulis ke
      // refs, masalah seperti style ditolak MapLibre (peta kosong tanpa satu
      // pun request tile) tidak punya jejak sama sekali saat debugging.
      console.error('Map error:', e.error || errorMsg);
      if (errorMsg.includes('source') || errorMsg.includes('tile') || errorMsg.includes('network') || errorMsg.includes('404') || errorMsg.includes('500')) {
        mapError.value = `Gagal memuat data peta: ${errorMsg}. Periksa koneksi dan coba lagi.`;
      }
    });
  } catch (error) {
    loading.value = false;
    mapError.value = `Gagal memuat peta: ${error.message}`;
    console.error('Map initialization error:', error);
  }
}

// Toggle hillshade
function toggleHillshade() {
  showHillshade.value = !showHillshade.value;
  setLayerVisibility('hillshade-layer', showHillshade.value);
}

// Toggle district labels
function toggleDistrictLabels() {
  showDistrictLabels.value = !showDistrictLabels.value;
  setLayerVisibility('kabupaten-labels', showDistrictLabels.value);
}

// Toggle contour labels
function toggleContourLabels() {
  showContourLabels.value = !showContourLabels.value;
  setLayerVisibility('kontur-labels', showContourLabels.value);
}

// Change basemap
function changeBasemap() {
  if (!map.value) return;
  const layers = ['osm-layer', 'satellite-layer', 'terrain-layer', 'dark-layer'];
  layers.forEach(layer => {
    if (map.value.getLayer(layer)) {
      map.value.setLayoutProperty(layer, 'visibility', layer === `${basemap.value}-layer` ? 'visible' : 'none');
    }
  });
  mapStatus.value = `Basemap: ${basemap.value}`;
}

// Locate user
function locateUser() {
  if (!map.value || !hasGeolocation.value) return;
  locating.value = true;
  navigator.geolocation.getCurrentPosition(
    async (pos) => {
      const { longitude, latitude } = pos.coords;

      // Callback ini async, jadi isinya bisa melempar galat. Tanpa try di
      // sini locating tidak pernah kembali ke false dan tombolnya macet
      // menulis "Mencari..." selamanya, padahal lokasi sebenarnya sudah
      // ditemukan.
      try {
        map.value?.flyTo({ center: [longitude, latitude], zoom: 14, duration: 2000 });
        const { Popup } = await import('../../maplibre');
        new Popup()
          .setLngLat([longitude, latitude])
          .setHTML('<div class="p-2 text-[#1f2937]">Lokasi Anda</div>')
          .addTo(map.value);
      } catch (error) {
        mapStatus.value = `Lokasi ditemukan, tetapi peta gagal menampilkannya: ${error.message}`;
      } finally {
        locating.value = false;
      }
    },
    (err) => {
      locating.value = false;
      mapStatus.value = `Gagal mendapatkan lokasi: ${err.message}`;
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

// Search by place name (Nominatim)
async function searchPlace() {
  if (!searchQuery.value.trim()) return;
  searchFocused.value = false;

  // Check if query is coordinates
  const coordMatch = searchQuery.value.match(/^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/);
  if (coordMatch) {
    const lat = parseFloat(coordMatch[1]);
    const lng = parseFloat(coordMatch[2]);
    if (map.value) {
      map.value.flyTo({ center: [lng, lat], zoom: 14, duration: 2000 });
      new (await import('../../maplibre')).Popup()
        .setLngLat([lng, lat])
        .setHTML(`<div class="p-2 text-[#1f2937]">Koordinat: ${lat.toFixed(6)}, ${lng.toFixed(6)}</div>`)
        .addTo(map.value);
      searchQuery.value = '';
      return;
    }
  }

  // Search via Nominatim
  // Nomor urut penanda pencarian yang sedang berjalan. Dua pencarian yang
  // tumpang tindih bisa jawabannya datang terbalik, dan hasil yang lebih lama
  // akan menimpa hasil yang lebih baru. Hanya jawaban milik nomor terakhir
  // yang boleh menulis ke searchResults.
  const nomorPencarian = ++searchSequence;

  try {
    searchResults.value = [];
    const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=5&countrycodes=id&accept-language=id`);
    const data = await response.json();

    if (nomorPencarian !== searchSequence) return;

    searchResults.value = data.map(item => ({
      place_id: item.place_id,
      display_name: item.display_name,
      lat: parseFloat(item.lat),
      lon: parseFloat(item.lon),
    }));
    if (searchResults.value.length === 0) {
      mapStatus.value = 'Tidak ditemukan hasil untuk pencarian tersebut.';
    }
  } catch (error) {
    if (nomorPencarian !== searchSequence) return;

    console.error('Search error:', error);
    mapStatus.value = 'Gagal mencari lokasi. Periksa koneksi internet.';
  }
}

async function selectSearchResult(result) {
  if (!map.value) return;
  map.value.flyTo({ center: [result.lon, result.lat], zoom: 14, duration: 2000 });
  const { Popup } = await import('../../maplibre');

  // display_name berasal dari balasan server Nominatim, jadi ikut dirangkai
  // sebagai HTML apa adanya bisa menyisipkan tag. Isi teksnya dibuat lewat
  // textContent, yang tidak pernah mengartikan tag.
  const kotak = document.createElement('div');
  kotak.className = 'p-2 text-[#1f2937]';
  kotak.textContent = result.display_name || result.label || '';

  new Popup()
    .setLngLat([result.lon, result.lat])
    .setDOMContent(kotak)
    .addTo(map.value);
  searchQuery.value = '';
  searchResults.value = [];
  searchFocused.value = false;
}

// Measurement tools
const MEASURE_SOURCE = 'measurement';
const MEASURE_LINE_LAYER = 'measurement-line';
const MEASURE_FILL_LAYER = 'measurement-fill';

/**
 * Siapkan sumber dan layer pengukuran.
 *
 * Versi lama hanya menambahkan sumber GeoJSON tanpa satu pun layer, jadi
 * datanya benar tetapi tidak ada yang tergambar. FeatureCollection kosong
 * maupun LineString tanpa layer hanya diam di dalam peta.
 *
 * addSource/addLayer hanya boleh dipanggil setelah style selesai dimuat, jadi
 * pemanggil harus siap menunggu event 'load'.
 */
function ensureMeasurementLayers() {
  if (!map.value || !map.value.loaded()) return false;

  if (!map.value.getSource(MEASURE_SOURCE)) {
    map.value.addSource(MEASURE_SOURCE, {
      type: 'geojson',
      data: { type: 'FeatureCollection', features: [] },
    });
  }

  if (!map.value.getLayer(MEASURE_FILL_LAYER)) {
    map.value.addLayer({
      id: MEASURE_FILL_LAYER,
      type: 'fill',
      source: MEASURE_SOURCE,
      filter: ['==', '$type', 'Polygon'],
      paint: { 'fill-color': '#EDD330', 'fill-opacity': 0.2 },
    });
  }

  if (!map.value.getLayer(MEASURE_LINE_LAYER)) {
    map.value.addLayer({
      id: MEASURE_LINE_LAYER,
      type: 'line',
      source: MEASURE_SOURCE,
      filter: ['==', '$type', 'LineString'],
      layout: { 'line-cap': 'round', 'line-join': 'round' },
      paint: { 'line-color': '#EDD330', 'line-width': 2.5 },
    });
  }

  return true;
}

function startMeasurement(mode) {
  if (!map.value || measurementMode.value === mode) return;

  if (!ensureMeasurementLayers()) {
    map.value.once('load', () => startMeasurement(mode));
    mapStatus.value = 'Menyiapkan alat ukur...';
    return;
  }

  measurementMode.value = mode;
  measurementPoints.value = [];
  measurementDistance.value = 0;
  measurementArea.value = 0;

  map.value.on('click', onMeasureClick);
  map.value.getCanvas().style.cursor = 'crosshair';
  mapStatus.value = mode === 'distance' ? 'Klik untuk menambah titik ukur jarak' : 'Klik untuk menambah titik ukur luas';
}

function onMeasureClick(e) {
  if (measurementMode.value === 'none') return;
  measurementPoints.value.push([e.lngLat.lng, e.lngLat.lat]);
  updateMeasurement();
}

function updateMeasurement() {
  const source = ensureMeasurementLayers() ? map.value?.getSource(MEASURE_SOURCE) : null;
  if (!source) return;

  const coords = measurementPoints.value;
  const features = [];

  if (measurementMode.value === 'area' && coords.length >= 3) {
    features.push({
      type: 'Feature',
      geometry: { type: 'Polygon', coordinates: [[...coords, coords[0]]] },
      properties: {},
    });
  }

  if (coords.length >= 2) {
    features.push({
      type: 'Feature',
      geometry: { type: 'LineString', coordinates: coords },
      properties: {},
    });
  }

  source.setData({ type: 'FeatureCollection', features });

  if (measurementMode.value === 'distance' && coords.length >= 2) {
    let total = 0;
    for (let i = 1; i < coords.length; i++) {
      total += calculateDistance(coords[i - 1], coords[i]);
    }
    measurementDistance.value = total;
  } else if (measurementMode.value === 'area' && coords.length >= 3) {
    measurementArea.value = calculateArea([...coords, coords[0]]);
  }
}

/** Kosongkan layer pengukuran dan lepaskan penangkap klik. */
function resetMeasurement() {
  measurementMode.value = 'none';
  measurementPoints.value = [];
  measurementDistance.value = 0;
  measurementArea.value = 0;

  const source = map.value?.getSource(MEASURE_SOURCE);
  if (source) {
    source.setData({ type: 'FeatureCollection', features: [] });
  }

  map.value?.off?.('click', onMeasureClick);
  if (map.value) map.value.getCanvas().style.cursor = '';
}

function cancelMeasurement() {
  if (!map.value) return;
  resetMeasurement();
  mapStatus.value = 'Pengukuran dibatalkan';
}

function finishMeasurement() {
  if (!map.value) return;

  const mode = measurementMode.value;
  const dist = measurementDistance.value;
  const area = measurementArea.value;

  resetMeasurement();

  mapStatus.value = mode === 'distance'
    ? `Jarak: ${formatDistance(dist)}`
    : `Luas: ${formatArea(area)}`;
}

// Bookmark functions
function saveBookmark(name = null, coords = null) {
  if (!map.value) return;
  const center = coords || [map.value.getCenter().lng, map.value.getCenter().lat];
  const zoom = map.value.getZoom();
  const bearing = map.value.getBearing();
  const pitch = map.value.getPitch();
  const basemapCurrent = basemap.value;

  const bookmarkName = name || `Bookmark ${bookmarks.value.length + 1} - ${new Date().toLocaleString('id-ID')}`;
  const bookmark = { name: bookmarkName, center, zoom, bearing, pitch, basemap: basemapCurrent, timestamp: Date.now() };
  bookmarks.value.unshift(bookmark);
  if (bookmarks.value.length > 20) bookmarks.value.pop();
  localStorage.setItem('mapBookmarks', JSON.stringify(bookmarks.value));
  mapStatus.value = `Tampilan disimpan: ${bookmarkName}`;
}

function loadBookmarks() {
  try {
    const stored = localStorage.getItem('mapBookmarks');
    if (stored) bookmarks.value = JSON.parse(stored);
  } catch {}
}

function goToBookmark(bm) {
  if (!map.value) return;
  basemap.value = bm.basemap;
  changeBasemap();
  map.value.flyTo({ center: bm.center, zoom: bm.zoom, bearing: bm.bearing, pitch: bm.pitch, duration: 2000 });
  mapStatus.value = `Berpindah ke: ${bm.name}`;
}

function deleteBookmark(index) {
  bookmarks.value.splice(index, 1);
  localStorage.setItem('mapBookmarks', JSON.stringify(bookmarks.value));
}

/**
 * Cetak peta yang sedang tampil sebagai PNG.
 *
 * Hasilnya bukan screenshot polos: peta, kompas, dan skala digambar di atas
 * kanvas yang sama dengan uraian komponen peta kontur, supaya berkas tunggal
 * itu bisa langsung dipakai sebagai bahan ajar.
 *
 * `area` menentukan judul dan nama berkas, supaya PNG yang diunduh sama dengan
 * wilayah yang dipilih pengguna di dialog unduhan, bukan dengan viewport yang
 * kebetulan sedang terlihat. `region` menentukan isi peta di dalam PNG itu:
 * snapshot diambil setelah kamera disesuaikan ke wilayah tersebut, lalu kamera
 * dikembalikan seperti semula.
 *
 * @param {{area?: {name: string, label: string}, region?: object, components?: object}} options
 */
async function printMapPng({ area = null, region = null, components = printComponents.value, matchViewport = false } = {}) {
  if (!map.value) return { ok: false };

  const wilayah = selectedWilayah.value;

  // matchViewport menang atas wilayah yang sedang dipilih. Tombol Cetak
  // promised isinya sama dengan yang terlihat di kanvas utama, jadi kalau
  // ada kabupaten yang dipilih, isinya tetap bukan kabupaten itu.
  const fallbackName = matchViewport ? 'Tampilan Saat Ini' : wilayah?.name ?? 'Peta Kontur Sulawesi Selatan';
  const fallbackLabel = matchViewport
    ? 'Wilayah yang sedang terlihat di peta'
    : wilayah?.label ?? 'Provinsi Sulawesi Selatan';

  const name = area?.name ?? fallbackName;
  const label = area?.label ?? fallbackLabel;

  const result = await exportMapPng(map.value, {
    title: `Peta Kontur - ${name}`,
    subtitle: label,
    sources: sourceCredit.value,
    filename: `peta-kontur-${slugify(name)}.png`,
    orientation: printOrientation.value,
    logo: logoUrl.value,
    ...(matchViewport ? { matchViewport: true } : {}),
    ...(region
      ? { region, regionMaxZoom: Number.isFinite(region.maxZoom) ? region.maxZoom : null }
      : {}),
    ...(components ? { components } : {}),
  });

  if (result.ok) {
    // Peta kosong harus diberitahukan, bukan diam-diam disembunyikan di balik
    // "PNG tersimpan": yang tersimpan bukan peta.
    if (result.framingProblem) {
      // Berkasnya tetap ada, tapi isinya bukan wilayah yang dipilih. Menyembunyikan
      // ini membuat pengguna mengira pilihannya tidak berpengaruh.
      mapStatus.value = `PNG ${name} tersimpan, tetapi isinya BUKAN wilayah pilihan: ${result.framingProblem}`;
    } else if (result.mapMissing) {
      mapStatus.value = `PNG ${name} tersimpan TANPA area peta. ${result.mapProblem ?? ''}`.trim();
    } else if (result.blocked) {
      mapStatus.value = 'PNG tersimpan, tetapi area peta tidak ikut karena browser memblokir pembacaan tile.';
    } else {
      mapStatus.value = `PNG ${name} tersimpan.`;
    }
  } else {
    mapStatus.value = exportError.value ?? 'Gagal membuat PNG peta.';
  }

  return result;
}

/** Nama berkas yang aman: huruf, angka, dan tanda hubung saja. */
function slugify(text) {
  return String(text)
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '') || 'peta';
}

// Print layout
function printMap() {
  if (!map.value) return;

  const printWindow = window.open('', '_blank');

  // Popup bisa ditolak. Versi lama langsung menulis ke printWindow.document
  // sehingga galatnya hilang tanpa jejak dan tombolnya terasa tidak berfungsi.
  if (!printWindow) {
    mapStatus.value = 'Jendela diblokir browser. Izinkan pop-up untuk situs ini lalu ulangi.';
    return;
  }

  try {
    // Kalau ada wilayah yang dipilih, cetak mengikuti wilayah itu dan bukan
    // kamera yang sedang terlihat. Setelah wilayah dipilih lalu digeser atau
    // di-zoom sendiri, cetakan tetap memakai extent pilihan sehingga judul di
    // atas peta tidak berbohong soal isi peta.
    const wilayah = selectedWilayah.value;
    const bbox = selectedWilayahBBox.value;

    let camera = {
      center: map.value.getCenter(),
      zoom: map.value.getZoom(),
      bearing: map.value.getBearing(),
      pitch: map.value.getPitch(),
    };

    if (wilayah && bbox) {
      const regionCamera = map.value.cameraForBounds(
        [[bbox[0], bbox[1]], [bbox[2], bbox[3]]],
        { padding: 40 }
      );
      if (regionCamera) {
        camera = { ...regionCamera, pitch: 0 };
      }
    }

    printWindow.document.open();
    printWindow.document.write(generatePrintHTML(camera, wilayah));
    printWindow.document.close();
    printWindow.focus();
  } catch (error) {
    printWindow.close();
    mapStatus.value = `Gagal menyiapkan halaman cetak: ${error.message}`;
  }
}

/** Nama wilayah berasal dari data, jadi tetap harus di-escape sebelum masuk HTML. */
function escapeHtml(text) {
  return String(text ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  })[c]);
}

/**
 * Isi popup informasi kabupaten.
 *
 * Elemennya dibangun dengan createElement dan textContent, bukan dirangkai jadi
 * string HTML lalu setHTML. Dua alasannya:
 *
 * Nama kabupaten, id, dan nama provinsi berasal dari berkas GeoJSON, dan
 * display_name pada pencarian berasal dari balasan server Nominatim. Semuanya
 * masuk ke halaman ini sebagai HTML kalau dirangkai, sehingga isinya bisa
 * menyisipkan tag sendiri. textContent tidak pernah mengartikan tag.
 *
 * Tombolnya juga tidak memakai onclick yang isinya JSON.stringify. Atribut itu
 * diapit tanda kutip ganda, sedangkan JSON memakai tanda kutip ganda juga, jadi
 * nama kabupaten ber tanda kutip sudah cukup untuk menutup atribut lebih awal
 * dan menyisipkan atribut lain. Listener dipasang di sisi skrip, jadi isinya
 * tidak pernah diurai browser.
 *
 * @param {string} nama
 * @param {string} idKab
 * @param {string} namaProv
 * @param {[number, number]} koordinat
 * @returns {HTMLElement}
 */
function popupWilayah(nama, idKab, namaProv, koordinat) {
  const akar = document.createElement('div');
  akar.className = 'p-2 min-w-[200px]';

  const judul = document.createElement('h3');
  judul.className = 'font-bold text-[#1f2937] mb-1';
  judul.textContent = nama;
  akar.appendChild(judul);

  const meta = document.createElement('div');
  meta.className = 'text-sm text-[#6b7280]';

  const barisId = document.createElement('div');
  barisId.textContent = `ID: ${idKab}`;
  const barisProv = document.createElement('div');
  barisProv.textContent = `Provinsi: ${namaProv}`;
  meta.append(barisId, barisProv);
  akar.appendChild(meta);

  const aksi = document.createElement('div');
  aksi.className = 'mt-2 flex gap-2';

  // Dua tombolnya memanggil fungsi yang sama dengan yang dipakai panel di luar
  // peta, jadi tidak perlu CustomEvent yang harus dicari lewat window.
  const tombolZoom = document.createElement('button');
  tombolZoom.className = 'text-xs bg-[#A7B92A] text-white px-2 py-1 rounded';
  tombolZoom.type = 'button';
  tombolZoom.textContent = 'Zoom';
  tombolZoom.addEventListener('click', () => {
    map.value?.flyTo({ center: koordinat, zoom: 12, duration: 2000 });
  });

  const tombolBookmark = document.createElement('button');
  tombolBookmark.className = 'text-xs bg-[#6F9435] text-white px-2 py-1 rounded';
  tombolBookmark.type = 'button';
  tombolBookmark.textContent = 'Bookmark';
  tombolBookmark.addEventListener('click', () => {
    saveBookmark(nama, koordinat);
  });

  aksi.append(tombolZoom, tombolBookmark);
  akar.appendChild(aksi);

  return akar;
}

function generatePrintHTML(camera, wilayah = null) {
  const center = camera.center;
  const zoom = camera.zoom;
  const bearing = camera.bearing ?? 0;
  const pitch = camera.pitch ?? 0;

  const title = wilayah
    ? `Peta Kontur - ${wilayah.name}`
    : 'Peta Kontur Sulawesi Selatan';
  const wilayahBaris = wilayah
    ? `<p>Wilayah: <strong>${escapeHtml(wilayah.label)}</strong> | Kode wilayah: ${escapeHtml(wilayah.kode)}</p>`
    : '';
  const date = new Date().toLocaleString('id-ID');
  // Skala ditulis 1:N. Versi lama memakai rumus meter per piksel di sini, sehingga
  // yang tampil "Skala ~1:76": angka 76 itu meter per piksel, bukan pembilang
  // rasio, sehingga yang tertulis di halaman cetak bukan skala peta sama sekali.
  // Rumus yang sama dipakai subjudul PNG supaya keduanya tidak berbeda.
  // Bentuk yang sama dipakai readout di layar dan subjudul PNG, supaya angka
  // skala yang sama tidak ditulis dengan ejaan berbeda di beberapa tempat.
  const skalaLabel = scaleLabel(center.lat, zoom);
  // batas kabupaten dari mapConfig. Versi lama menulis ${url} di dalam template
  // tanpa pernah mendeklarasikan variabelnya, jadi memanggil fungsi ini melempar
  // ReferenceError sebelum HTML-nya sempat ditulis ke jendela cetak.
  const url = geojsonUrl.value;
  // Halaman cetak memakai OSM dan tidak mengikuti pilihan basemap pengguna,
  // jadi kreditnya diambil dari definisi basemap yang sama dengan peta utama.
  const sumberCetak = escapeHtml(`Sumber: ${basemapAttribution('osm')}, PMTiles Kontur Sulsel`);
  // Highlight kecamatan ikut dibawa ke halaman cetak. Variabel ditulis sebagai
  // literal JSON supaya kode id_kec dan URL-nya tidak bisa keluar dari string
  // HTML di dalam <script> ketika wilayah berasal dari data.
  const cetakKecamatan = hasKecamatanGeojson.value
    ? `        'kecamatan': { type: 'geojson', data: ${JSON.stringify(kecamatanGeojsonUrl.value)} },`
    : '';
  const cetakKecamatanLayers = hasKecamatanGeojson.value
    ? `        { id: 'kecamatan', type: 'line', source: 'kecamatan', paint: { 'line-color': '#0f766e', 'line-width': 0.6, 'line-opacity': 0.7 } },${
        wilayah?.namaKec
          ? `        { id: 'kecamatan-highlight', type: 'fill', source: 'kecamatan', filter: ['==', ['get', 'id_kec'], ${JSON.stringify(wilayah.kode)}], paint: { 'fill-color': '#EDD330', 'fill-opacity': 0.25 } },`
          : ''
      }`
    : '';
  // Versi MapLibre harus sama dengan EXTERNAL_LIBS di public/sw.js. Cetak lewat
  // 4.7.1 sedangkan peta offline dan cache service worker memakai 3.6.2, sehingga
  // salinan cetaknya tidak pernah ada di cache dan gagal dimuat saat offline.
  const maplibreVersion = '3.6.2';
  return `<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>${escapeHtml(title)} - Cetak</title>
<style>
  @page { margin: 1cm; size: A4 landscape; }
  body { margin: 0; font-family: Arial, sans-serif; }
  .header { text-align: center; margin-bottom: 10px; }
  .header h1 { margin: 0; font-size: 24px; color: #1f2937; }
  .header p { margin: 5px 0; font-size: 12px; color: #6b7280; }
  .map-container { position: relative; width: 100%; height: 70vh; border: 2px solid #6F9435; }
  #print-map { width: 100%; height: 100%; }
  .footer { display: flex; justify-content: space-between; margin-top: 10px; font-size: 11px; color: #6b7280; }
  .legend { display: flex; gap: 20px; flex-wrap: wrap; }
  .legend-item { display: flex; align-items: center; gap: 5px; }
  .legend-color { width: 20px; height: 3px; border-radius: 2px; }
</style></head><body>
<div class="header">
  <h1>${escapeHtml(title)}</h1>
  ${wilayahBaris}
  <p>Dicetak pada: ${date} | Koordinat tengah: ${center.lng.toFixed(6)}, ${center.lat.toFixed(6)} | Zoom: ${zoom.toFixed(1)} | Skala ${skalaLabel}</p>
</div>
<div class="map-container" id="print-map"></div>
<div class="footer">
  <div>${sumberCetak}</div>
  <div class="legend">
    <div class="legend-item"><span class="legend-color" style="background:#8c510a"></span> Kontur</div>
    <div class="legend-item"><span class="legend-color" style="background:#2563eb; border:1px dashed #2563eb"></span> Batas Kabupaten</div>${hasKecamatanGeojson.value ? `
    <div class="legend-item"><span class="legend-color" style="background:#0f766e"></span> Batas Kecamatan</div>` : ''}${wilayah?.namaKec ? `
    <div class="legend-item"><span class="legend-color" style="background:#EDD330"></span> Kecamatan Dipilih</div>` : ''}
  </div>
</div>
<script src="https://unpkg.com/maplibre-gl@${maplibreVersion}/dist/maplibre-gl.js"><\/script>
<link href="https://unpkg.com/maplibre-gl@${maplibreVersion}/dist/maplibre-gl.css" rel="stylesheet" />
<script src="https://unpkg.com/pmtiles/dist/pmtiles.js"><\/script>
<script>
  // Build pmtiles dari unpkg hanya tersedia tanpa nomor versi, dan itu IIFE
  // yang mengekspos global 'pmtiles' huruf kecil. new PMTiles.Protocol() akan
  // ReferenceError karena PMTiles tidak terdefinisi.
  const protocol = new pmtiles.Protocol();
  maplibregl.addProtocol('pmtiles', protocol.tile);
  const map = new maplibregl.Map({
    container: 'print-map',
    // Wajib, bukan sekadar langkah export PNG. Tanpa preserveDrawingBuffer
    // browser boleh membuang buffer WebGL begitu frame selesai digambar, dan
    // halaman cetak yang mengambil tangkapan layar lalu menampilkan kotak kosong
    // padahal petanya sudah termuat. Gejalanya persis seperti peta yang tidak
    // dimuat: PDF-nya hanya berisi header.
    preserveDrawingBuffer: true,
    style: {
      version: 8,
      sources: {
        'kontur': { type: 'vector', url: '${pmtilesSourceUrl.value}' },
        'batas': { type: 'geojson', data: '${url}' },
${cetakKecamatan}
        'osm': ${JSON.stringify(PRINT_BASEMAP)}
      },
      layers: [
        { id: 'osm', type: 'raster', source: 'osm' },
        { id: 'kontur', type: 'line', source: 'kontur', 'source-layer': 'kontur',
          layout: { 'line-join': 'round', 'line-cap': 'round' },
          paint: { 'line-color': '#8c510a', 'line-width': ['case', ['==', ['%', ['get', 'ELEV'], 50], 0], 1.8, 0.8] }
        },
${cetakKecamatanLayers}
        { id: 'batas', type: 'line', source: 'batas', paint: { 'line-color': '#2563eb', 'line-width': 1.5, 'line-dasharray': [2, 2] } }
      ]
    },
    center: [${center.lng}, ${center.lat}],
    zoom: ${zoom},
    bearing: ${bearing},
    pitch: ${pitch},
  });
  // Menunggu 'idle', bukan 'load'.
  //
  // 'load' hanya berarti style sudah terurai dan frame pertama selesai
  // digambar; tile raster dan vektor biasanya masih turun saat itu.
  // window.print() yang dipanggil pada 'load' membuka dialog cetak saat kanvas
  // masih kosong, jadi PDF hasil "Simpan sebagai PDF" hanya berisi header
  // tanpa peta.
  //
  // 'idle' baru datang setelah semua tile selesai dimuat dan digambar. Penge-nya
  // diletakkan sebelum map.once-nya, bukan sesudah, karena peta yang sudah
  // selesai lebih dulu tidak akan pernah memancarkan 'idle' lagi.
  let printed = false;
  const cetakSekarang = () => {
    if (printed) return;
    printed = true;

    // Dua frame supaya frame terakhir benar-benar sudah terkomposisi ke halaman
    // sebelum dialog cetak mengambil tangkapan layarnya.
    requestAnimationFrame(() => requestAnimationFrame(() => window.print()));
  };

  map.once('idle', cetakSekarang);

  // 'idle' tidak pernah datang kalau satu sumber menggantung. Dialog cetak yang
  // tidak pernah muncul lebih buruk daripada peta yang tercetak sebelum semua
  // tile masuk, jadi ada batas waktunya.
  setTimeout(cetakSekarang, 15000);
<\/script>
</body></html>`;
}

/**
 * Unduh peta yang dipilih pengguna.
 *
 * Satu klik menghasilkan dua berkas, dan urutannya penting:
 *
 * 1. Peta digeser ke wilayah yang dipilih, lalu PNG-nya dicetak. Snapshot PNG
 *    diambil dari kanvas peta yang sedang tampil, jadi langkah ini harus selesai
 *    sebelum hasilnya dicetak. Kalau PNG dicetak belakangan, yang masuk ke berkas
 *    adalah viewport terakhir, bukan daerah yang diminta.
 * 2. Paket tile untuk dipakai tanpa sinyal diambil service worker, dan halaman
 *    peta offline disusun dari komponen yang dicentang.
 *
* Nama wilayah pada langkah 1 dan 2 diambil dari tempat yang sama supaya judul
 * PNG, isi paket tile, dan halaman peta offline tidak pernah menyebut wilayah
 * yang berbeda dalam satu unduhan.
 *
 * Tombol "Cetak Peta PNG" yang dulu terpisah tidak ada lagi: cetak PNG memang
 * hasil akhir dari unduhan peta offline, bukan fitur lain.
 */
async function downloadOffline() {
  if (!hasPmtiles.value) return;

  downloading.value = true;

  const wilayah = downloadMode.value === 'region' ? selectedOfflineWilayah.value : null;
  const areaName = wilayah?.name ?? 'Sulawesi Selatan';
  const areaLabel = wilayah?.label ?? 'Provinsi Sulawesi Selatan';

  const components = printComponents.value;

  const bbox = currentBBox.value;

  downloadStatus.value = `Menyiapkan PNG peta ${areaName}...`;

  // 1. Peta diarahkan ke wilayah yang dipilih supaya PNG berisi daerah itu,
  //   bukan kebetulan sedang terlihat. Penyesuaian kamera, pengambilan snapshot,
  //   dan pemulihan kamera dilakukan useMapPngExport. Versi lama hanya memusatkan
  //   peta pada zoom tetap, jadi preview di dialog menampilkan seluruh wilayah
  //   sementara PNG-nya hanya berisi potongan kecil di tengahnya.
  const png = await printMapPng({
    area: { name: areaName, label: areaLabel },
    region: { ...bbox, maxZoom: offlineZoomMax.value },
    components,
  });

  if (!('serviceWorker' in navigator)) {
    downloadStatus.value = 'Service Worker tidak didukung browser ini, jadi paket tile offline tidak bisa dibuat.';
    downloading.value = false;
    return;
  }

  try {
    const worker = await getActiveServiceWorker();

    const minZoom = offlineZoomMin.value;
    const maxZoom = offlineZoomMax.value;
    // PMTiles di-host di Supabase Storage, jadi service worker harus memakai
    // URL absolut agar HTTP Range langsung dibaca dari host arsip. GeoJSON
    // masih dilayani dari origin sendiri sehingga boleh dibuat relatif.
    const pmtilesUrl = props.mapConfig.pmtilesUrl;
    const geojsonUrlRelative = geojsonUrl.value.replace(/^https?:\/\/[^\/]+/, '');
    // Batas kecamatan dan kode yang perlu disorot diteruskan ke worker. Tanpa
    // keduanya, halaman peta offline yang dihasilkan hanya menampilkan garis
    // batas kabupaten sehingga pengguna tidak bisa mencocokkan isi unduhan
    // dengan peta yang diunduh.
    const kecamatanGeojsonUrlRelative = hasKecamatanGeojson.value
      ? kecamatanGeojsonUrl.value.replace(/^https?:\/\/[^\/]+/, '')
      : null;
    const highlightKecId = selectedOfflineWilayah.value?.namaKec
      ? selectedOfflineWilayah.value.kode
      : null;

    const layoutOptions = components;

    const channel = new MessageChannel();
    channel.port1.onmessage = (event) => {
      const data = event.data;

      // Service worker yang aktif bisa saja versi lama: browser memakai
      // salinan yang sedang berjalan sampai install dan activate selesai, dan
      // itu bisa memakan waktu setelah deploy. Balasan versi lama tidak
      // membawa zoomMin/zoomMax sehingga unduhan yang gagal tampil sebagai
      // "0 tile (zoom undefined-undefined)". Tolak lebih dulu, lalu suruh
      // pengguna memuat ulang.
      if (data.protocol !== SW_PROTOCOL) {
        downloadStatus.value =
          'Service Worker di perangkat ini masih versi lama. Muat ulang halaman (Ctrl+Shift+R), lalu ulangi unduhan.';
        downloading.value = false;
        return;
      }

      if (data.type === 'DOWNLOAD_PROGRESS') {
        downloadStatus.value = `${data.status} (${data.downloaded}/${data.total} tile)`;
      }
      if (data.type === 'DOWNLOAD_COMPLETE') {
        const skipped = data.skipped
          ? `, ${data.skipped} tile tidak tersedia di arsip`
          : '';
        // surelyPNGResult di atas sudah membawa alasan gagalnya. Menulis
        // "PNG peta gagal dibuat" tanpa alasannya membuat galat ini mustahil
        // ditelusuri karena yang terlihat hanya dua UI yang diam.
        const pngNote = png.ok
          ? 'PNG peta tersimpan.'
          : `PNG peta gagal: ${png.error ?? 'alasan tidak diketahui'}.`;
        downloadStatus.value =
          `Selesai! ${data.downloaded} tile berhasil diunduh untuk offline (zoom ${data.zoomMin}-${data.zoomMax}${skipped}). ${pngNote}`;
        downloading.value = false;
      }
      if (data.type === 'DOWNLOAD_ERROR') {
        downloadStatus.value = data.error;
        downloading.value = false;
      }
    };

    downloadStatus.value = png.ok
      ? `PNG ${areaName} tersimpan. Mengunduh paket tile untuk offline...`
      : `PNG gagal dibuat (${png.error ?? 'tidak diketahui'}). Mengunduh paket tile untuk offline...`;

    worker.postMessage(
      {
        type: 'DOWNLOAD_OFFLINE_TILES',
        bbox: { west: bbox.west, east: bbox.east, south: bbox.south, north: bbox.north },
        zoomMin: minZoom,
        zoomMax: maxZoom,
        pmtilesUrl,
        geojsonUrl: geojsonUrlRelative,
        kecamatanGeojsonUrl: kecamatanGeojsonUrlRelative,
        highlightKecId,
        layoutOptions,
        areaName,
        protocol: SW_PROTOCOL,
      },
      [channel.port2]
    );
  } catch (error) {
    downloadStatus.value = `Gagal mengunduh: ${error.message}`;
    downloading.value = false;
  }
}

onMounted(() => {
  initMap();
  observeMapSize();
});

watch(showMiniMap, async (visible) => {
  if (visible) {
    await nextTick();
    initMiniMap();
  } else {
    if (miniMap.value) {
      miniMap.value.remove();
      miniMap.value = null;
    }
  }
});

/**
 * Ganti kabupaten di dialog unduhan.
 *
 * Kecamatan lama harus dibuang bersama kabupatennya. Kalau tidak, kodemya masih
 * tertahan padahal tidak lagi ada di daftar kabupaten yang baru, sehingga preview
 * dan estimasi tile memakai batas yang salah tanpa memberi tanda.
 */
watch(selectedOfflineRegion, () => {
  selectedOfflineKecamatan.value = '';

  if (showMiniMap.value) {
    nextTick().then(() => initMiniMap());
  }
});

// Mengganti kecamatan hanya mengubah wilayah, bukan menampilkannya, jadi peta
// mini cukup dibangun ulang.
watch(selectedOfflineKecamatan, () => {
  if (showMiniMap.value) {
    nextTick().then(() => initMiniMap());
  }
});

// Rentang zoom arsip dibaca saat modal dibuka, sehingga estimasi tile langsung
// memakai rentang yang benar-benar ada di arsip.
watch(showOfflineModal, (open) => {
  if (open) loadArchiveZoomRange();
});

onBeforeUnmount(() => {
  stopObservingMapSize();
  clearTimeout(miniMapLoadingTimer.value);

  // Timer dan listener window punya hidup yang lebih panjang daripada
  // komponen ini, jadi harus dilepas sendiri. map.remove() hanya membersihkan
  // listener milik peta itu sendiri, bukan yang menempel di window.
  if (partialLoadTimer) {
    clearTimeout(partialLoadTimer);
    partialLoadTimer = null;
  }

  window.removeEventListener('online', onWindowOnline);
  window.removeEventListener('offline', onWindowOffline);

  if (map.value) {
    map.value.remove();
    map.value = null;
  }
  if (miniMap.value) {
    miniMap.value.remove();
    miniMap.value = null;
  }
});
</script>
