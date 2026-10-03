<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Peta Kontur</p>
        <h1 class="mt-1 text-2xl font-extrabold text-[#f0ead8]">Peta Kontur Sulawesi Selatan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Cari kabupaten, lihat batas administratif, dan jelajahi kontur topografi.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-if="hasPmtiles"
          @click="showOfflineModal = true"
          class="inline-flex items-center rounded-lg border-2 border-[#A7B92A] bg-[#A7B92A]/10 px-4 py-2 text-sm font-bold text-[#A7B92A] transition hover:bg-[#A7B92A]/20"
        >
          Unduh Peta Offline
        </button>
        <button
          @click="toggleLayer"
          class="inline-flex items-center rounded-lg border-2 border-[#6F9435] px-4 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30"
        >
          {{ showContour ? 'Sembunyikan Kontur' : 'Tampilkan Kontur' }}
        </button>
        <button
          @click="toggleHillshade"
          class="inline-flex items-center rounded-lg border-2 border-[#6F9435] px-4 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30"
        >
          {{ showHillshade ? 'Sembunyikan Hillshade' : 'Tampilkan Hillshade' }}
        </button>
        <button
          @click="toggleDistrictLabels"
          class="inline-flex items-center rounded-lg border-2 border-[#6F9435] px-4 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30"
        >
          {{ showDistrictLabels ? 'Sembunyikan Label' : 'Tampilkan Label' }}
        </button>
        <button
          @click="toggleContourLabels"
          class="inline-flex items-center rounded-lg border-2 border-[#6F9435] px-4 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30"
        >
          {{ showContourLabels ? 'Sembunyikan Label Kontur' : 'Tampilkan Label Kontur' }}
        </button>
        <button
          @click="showElevationLegend = !showElevationLegend"
          class="inline-flex items-center rounded-lg border-2 border-[#6F9435] px-4 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30"
        >
          {{ showElevationLegend ? 'Sembunyikan Legenda' : 'Tampilkan Legenda' }}
        </button>
      </div>
    </div>

    <div class="mb-4 rounded-xl border-2 border-[#A7B92A]/40 bg-[#335233] p-4">
      <label class="block text-xs font-medium text-[#d4dc9a] mb-2">Cari Kabupaten</label>
      <div class="flex gap-2">
        <select
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
        <button
          v-if="selectedKabupaten"
          @click="clearSelection"
          class="rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30"
        >
          Hapus
        </button>
      </div>
      <div v-if="selectedKabData" class="mt-2 flex flex-wrap gap-3 text-xs text-[#8fa06a]">
        <span>Kabupaten: <strong class="text-[#EDD330]">{{ selectedKabData.nama_kab }}</strong></span>
        <span>ID: {{ selectedKabData.id_kab }}</span>
        <span v-if="selectedKabData.bbox">BBox: {{ selectedKabData.bbox.join(', ') }}</span>
      </div>
    </div>

    <div class="peta-shell relative overflow-hidden rounded-2xl border-2 border-[#A7B92A]/40 bg-[#263D26] shadow-lg">
      <div ref="mapContainer" class="h-[70vh] w-full min-h-[400px]"></div>

      <!-- Coordinate display on mouse hover.
           Diletakkan di atas bilah skala bawaan MapLibre di pojok kiri bawah;
           dua-duanya pernah memakai bottom-4 left-4 dan saling menutupi.
           Lebarnya dibatasi supaya tidak menabrak tumpukan pojok kanan bawah
           pada layar sempit. -->
      <div v-if="mouseCoords" class="peta-panel pointer-events-none absolute bottom-12 left-3 max-w-[min(260px,calc(100%-13rem))] rounded-lg border border-[#6F9435]/30 bg-[#1a1a1a]/90 px-3 py-1.5 font-mono text-xs text-[#EDD330] shadow-lg backdrop-blur-sm">
        Lon: {{ mouseCoords.lng.toFixed(6) }}° | Lat: {{ mouseCoords.lat.toFixed(6) }}°
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
            <span aria-hidden="true">📶</span>
            Legenda Elevasi
          </div>
          <div class="space-y-1">
            <div class="flex items-center gap-2"><span class="h-1.5 w-6 rounded" style="background: #8c510a;"></span> Kontur 10m</div>
            <div class="flex items-center gap-2"><span class="h-1.5 w-6 rounded" style="background: #a0522d;"></span> Kontur 50m (Index)</div>
            <div class="flex items-center gap-2"><span class="h-1.5 w-6 rounded" style="background: #cd853f;"></span> Kontur 100m</div>
            <div class="flex items-center gap-2"><span class="h-1.5 w-6 rounded" style="background: #8b4513;"></span> Kontur 500m+</div>
            <div class="mt-2 flex items-center gap-2 border-t border-[#6F9435]/30 pt-2"><span class="h-1.5 w-6 rounded" style="background: #2563eb;"></span> Batas Kabupaten</div>
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
            class="flex items-center gap-1.5 disabled:cursor-not-allowed"
            title="Lokasi saya"
          >
            <span aria-hidden="true">{{ locating ? '⟳' : '📍' }}</span>
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
        <div v-if="isOffline" class="rounded-lg bg-[#f59e0b]/90 px-3 py-1.5 text-xs font-bold text-white shadow-lg">
          📴 Mode Offline
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
              @click="searchQuery = ''"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-[#8fa06a] hover:text-[#EDD330]"
            >
              ✕
            </button>
          </div>
          <button
            @click="searchPlace"
            :disabled="!searchQuery.trim()"
            class="rounded-lg border-2 border-[#A7B92A] bg-[#A7B92A]/10 px-3 py-2 text-sm font-bold text-[#A7B92A] transition hover:bg-[#A7B92A]/20 disabled:opacity-50"
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
            <span class="font-bold">{{ measurementMode === 'distance' ? '📏 Ukur Jarak' : '📐 Ukur Luas' }}</span>
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
            <option value="osm">🗺️ OpenStreetMap</option>
            <option value="satellite">🛰️ Satelit</option>
            <option value="terrain">🏔️ Terrain</option>
            <option value="dark">🌙 Dark</option>
          </select>

          <!-- Measurement toggles -->
          <button
            @click="startMeasurement('distance')"
            :disabled="measurementMode !== 'none'"
            class="rounded-lg border-2 border-[#6F9435]/60 bg-[#263D26]/95 px-3 py-2 text-sm font-bold text-[#EDD330] shadow-lg backdrop-blur-sm transition hover:bg-[#335233]/95 disabled:opacity-50"
            title="Ukur jarak"
          >
            <span aria-hidden="true">📏</span>
          </button>
          <button
            @click="startMeasurement('area')"
            :disabled="measurementMode !== 'none'"
            class="rounded-lg border-2 border-[#6F9435]/60 bg-[#263D26]/95 px-3 py-2 text-sm font-bold text-[#EDD330] shadow-lg backdrop-blur-sm transition hover:bg-[#335233]/95 disabled:opacity-50"
            title="Ukur luas"
          >
            <span aria-hidden="true">📐</span>
          </button>

          <!-- Bookmark/save view button -->
          <button
            @click="saveBookmark"
            class="rounded-lg border-2 border-[#6F9435]/60 bg-[#263D26]/95 px-3 py-2 text-sm font-bold text-[#EDD330] shadow-lg backdrop-blur-sm transition hover:bg-[#335233]/95"
            title="Simpan tampilan"
          >
            <span aria-hidden="true">🔖</span>
          </button>

          <!-- Print button -->
          <button
            @click="printMap"
            class="inline-flex items-center gap-1.5 rounded-lg border-2 border-[#6F9435]/60 bg-[#263D26]/95 px-3 py-2 text-sm font-bold text-[#EDD330] shadow-lg backdrop-blur-sm transition hover:bg-[#335233]/95"
            title="Cetak peta"
          >
            <span aria-hidden="true">🖨️</span>
            <span class="text-xs">Cetak</span>
          </button>
        </div>

        <!-- Bookmark list -->
        <div v-if="bookmarks.length > 0" class="max-h-56 w-[min(280px,calc(100vw-2rem))] overflow-y-auto rounded-lg border border-[#6F9435]/30 bg-[#1a1a1a]/90 p-2 text-xs shadow-lg backdrop-blur-sm">
          <p class="mb-1 px-1 font-bold text-[#EDD330]">Tampilan Tersimpan ({{ bookmarks.length }})</p>
          <div
            v-for="(bookmark, index) in bookmarks"
            :key="bookmark.timestamp ?? index"
            class="flex items-center gap-2 rounded px-1 py-1 hover:bg-[#335233]"
          >
            <button @click="goToBookmark(bookmark)" class="flex-1 truncate text-left text-[#f0ead8] hover:text-[#EDD330]" :title="bookmark.name">
              {{ bookmark.name }}
            </button>
            <button @click="deleteBookmark(index)" class="shrink-0 text-[#f87171] hover:underline" title="Hapus bookmark">
              ✕
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
            class="mt-4 inline-flex items-center rounded-lg border-2 border-[#A7B92A] bg-[#A7B92A]/10 px-4 py-2 text-sm font-bold text-[#A7B92A] transition hover:bg-[#A7B92A]/20"
          >
            Coba Lagi
          </button>
        </div>
      </div>
    </div>

    <div v-if="mapStatus" class="mt-3 rounded-lg border border-[#6F9435]/30 bg-[#335233] p-3 text-xs text-[#d4dc9a]">
      {{ mapStatus }}
    </div>
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
              <div class="text-lg font-bold text-[#EDD330]">🗺️</div>
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
              <div class="text-lg font-bold text-[#EDD330]">📍</div>
              <div class="mt-1 text-sm font-semibold text-[#f0ead8]">Satu Daerah</div>
              <div class="mt-1 text-xs text-[#8fa06a]">Pilih kabupaten/kota</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Region Selection (when region mode) -->
      <div v-if="downloadMode === 'region'" class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
        <label class="block text-xs font-medium text-[#d4dc9a] mb-2">Pilih Kabupaten/Kota</label>
        <select
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
        <div v-if="selectedOfflineRegion" class="mt-2 text-xs text-[#8fa06a]">
          Area: {{ selectedOfflineRegionData?.nama_kab }} ({{ selectedOfflineRegionData?.id_kab }})
        </div>
      </div>

      <!-- Mini Map Preview (when region selected) -->
      <div v-if="showMiniMap" class="rounded-lg border border-[#6F9435]/30 bg-[#263D26] p-3">
        <div class="flex items-center justify-between mb-2">
          <label class="text-xs font-medium text-[#d4dc9a]">Preview Wilayah</label>
          <span v-if="selectedOfflineRegionData" class="text-xs text-[#EDD330]">{{ selectedOfflineRegionData.nama_kab }}</span>
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
import { getActiveServiceWorker, SW_PROTOCOL } from '@/ServiceWorker.js';
import { useMapPngExport } from '@/Composables/useMapPngExport.js';

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
const showOfflineModal = ref(false);
const downloading = ref(false);
const downloadStatus = ref('');
const mapStatus = ref('');
const offlineZoomMin = ref(8);
const offlineZoomMax = ref(14);
const selectedKabupaten = ref('');
const miniMapLoading = ref(false);
const miniMapError = ref('');

// Mode unduh peta offline: 'full' untuk seluruh Sulawesi Selatan,
// 'region' untuk satu kabupaten/kota.
const downloadMode = ref('full');
const selectedOfflineRegion = ref('');

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

// Map controls state.
// Bilah skala dan kompas memakai kontrol bawaan MapLibre, jadi tidak ada
// state untuk keduanya; legenda dan readout koordinat dikendalikan di sini.
const showElevationLegend = ref(true);
const mouseCoords = ref(null);
const hasGeolocation = ref(false);
const locating = ref(false);

// Basemap
const basemap = ref('osm');
const basemapSources = {
  osm: { type: 'raster', tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'], tileSize: 256, attribution: '© OpenStreetMap' },
  satellite: { type: 'raster', tiles: ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'], tileSize: 256, attribution: '© Esri' },
  terrain: { type: 'raster', tiles: ['https://tile.opentopomap.org/{z}/{x}/{y}.png'], tileSize: 256, attribution: '© OpenTopoMap' },
  dark: { type: 'raster', tiles: ['https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png'], tileSize: 256, attribution: '© Stadia Maps' },
};

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

const selectedKabData = computed(() => {
  return kabupatens.value.find((k) => k.id_kab === selectedKabupaten.value) ?? null;
});

const selectedOfflineRegionData = computed(() => {
  return kabupatens.value.find((k) => k.id_kab === selectedOfflineRegion.value) ?? null;
});

// Peta mini hanya tampil setelah mode "satu daerah" aktif dan kabupaten/kota
// sudah dipilih, sehingga watch(showMiniMap) bisa membuat dan membuang peta.
const showMiniMap = computed(() => downloadMode.value === 'region' && selectedOfflineRegion.value !== '');

const currentBBox = computed(() => {
  if (downloadMode.value === 'region' && selectedOfflineRegionData.value?.bbox) {
    const [west, south, east, north] = selectedOfflineRegionData.value.bbox;
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

function toggleLayer() {
  if (!map.value) return;
  showContour.value = !showContour.value;
  const layerId = 'garis-kontur';
  if (map.value.getLayer(layerId)) {
    map.value.setLayoutProperty(layerId, 'visibility', showContour.value ? 'visible' : 'none');
  } else {
    // Layer not ready yet, wait for map load
    if (map.value.loaded()) {
      // Map already loaded but layer not found - log error
      console.warn(`Layer ${layerId} not found on loaded map`);
    } else {
      map.value.once('load', () => {
        if (map.value?.getLayer(layerId)) {
          map.value.setLayoutProperty(layerId, 'visibility', showContour.value ? 'visible' : 'none');
        }
      });
    }
  }
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

function onKabupatenSelect() {
  const kab = selectedKabData.value;
  if (!map.value || !kab || !kab.bbox) return;

  const [west, south, east, north] = kab.bbox;
  map.value.fitBounds(
    [[west, south], [east, north]],
    { padding: 40, duration: 2000 }
  );
  highlightKabupaten(kab.id_kab);
  mapStatus.value = `Menampilkan ${kab.nama_kab} (BBox: ${kab.bbox.join(', ')})`;
}

function clearSelection() {
  selectedKabupaten.value = '';
  highlightKabupaten('');
  if (map.value) {
    map.value.fitBounds(
      [[boundingBox.value.west, boundingBox.value.south], [boundingBox.value.east, boundingBox.value.north]],
      { padding: 40, duration: 2000 }
    );
  }
  mapStatus.value = 'Peta dikembalikan ke tampilan Sulawesi Selatan.';
}

function initMiniMap() {
  if (!miniMapContainer.value || !selectedOfflineRegionData.value || !hasPmtiles.value) return;
  if (miniMap.value) {
    miniMap.value.remove();
    miniMap.value = null;
  }

  const kab = selectedOfflineRegionData.value;
  const [west, south, east, north] = kab.bbox;

  miniMapLoading.value = true;
  miniMapError.value = '';

  import('../../maplibre').then(({ Map, addProtocol }) => {
    import('pmtiles').then(({ Protocol }) => {
      const protocol = new Protocol();
      addProtocol('pmtiles', protocol.tile);

      try {
        miniMap.value = new Map({
          container: miniMapContainer.value,
          style: {
            version: 8,
            sources: {
              // Preview tanpa basemap hanya menampilkan garis kontur dan
              // batas wilayah di atas latar kosong, jadi bentuk wilayahnya
              // tidak terbaca. OSM disamakan dengan peta utama supaya yang
              // dipratinjau sama dengan yang akan diunduh.
              'mini-basemap': {
                type: 'raster',
                tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                tileSize: 256,
                attribution: '© OpenStreetMap',
              },
              'kontur': { type: 'vector', url: pmtilesSourceUrl.value },
              'batas': { type: 'geojson', data: geojsonUrl.value },
            },
            layers: [
              { id: 'mini-basemap-layer', type: 'raster', source: 'mini-basemap' },
              { id: 'mini-kontur', type: 'line', source: 'kontur', 'source-layer': 'kontur',
                layout: { 'line-join': 'round', 'line-cap': 'round' },
                paint: { 'line-color': '#8c510a', 'line-width': 0.8 } },
              { id: 'mini-batas', type: 'line', source: 'batas',
                paint: { 'line-color': '#2563eb', 'line-width': 1, 'line-dasharray': [1, 1] } },
            ],
          },
          center: [(west + east) / 2, (south + north) / 2],
          zoom: 8,
          maxZoom: 14,
        });

        miniMap.value.on('load', () => {
          miniMapLoading.value = false;
          // Fit to the region's bbox for better preview
          if (miniMap.value && kab.bbox) {
            miniMap.value.fitBounds(
              [[kab.bbox[0], kab.bbox[1]], [kab.bbox[2], kab.bbox[3]]],
              { padding: 20, duration: 1000 }
            );
          }
        });

        miniMap.value.on('error', (e) => {
          miniMapLoading.value = false;
          miniMapError.value = `Gagal memuat preview: ${e.error?.message || 'Kesalahan peta'}`;
          console.error('Mini map error:', e);
        });
      } catch (error) {
        miniMapLoading.value = false;
        miniMapError.value = `Gagal inisialisasi preview: ${error.message}`;
        console.error('Mini map init error:', error);
      }
    });
  });
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
      'osm-tiles': {
        type: 'raster',
        tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
        tileSize: 256,
        attribution: '© OpenStreetMap',
      },
      'satellite-tiles': {
        type: 'raster',
        tiles: ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'],
        tileSize: 256,
        attribution: '© Esri',
      },
      'terrain-tiles': {
        type: 'raster',
        tiles: ['https://tile.opentopomap.org/{z}/{x}/{y}.png'],
        tileSize: 256,
        attribution: '© OpenTopoMap',
      },
      'dark-tiles': {
        type: 'raster',
        tiles: ['https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png'],
        tileSize: 256,
        attribution: '© Stadia Maps',
      },
      'hillshade-tiles': {
        type: 'raster-dem',
        // Host jamak (tiles.opentopomap.org) menyajikan sertifikat TLS yang
        // tidak cocok dengan nama hostnya, sehingga browser menolak koneksi
        // dan layer hillshade tidak pernah punya data. Terrarium di S3
        // menyajikan DEM yang sama dengan sertifikat yang sah.
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

    // Kontrol lokasi bawaan tidak dipakai: tombol 📍 di template memanggil
    // locateUser() yang juga menampilkan popup "Lokasi Anda" dan menulis
    // alasannya ke mapStatus bila lokasi ditolak.
    if ('geolocation' in navigator) {
      hasGeolocation.value = true;
    }

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
        const content = `
          <div class="p-2 min-w-[200px]">
            <h3 class="font-bold text-[#1f2937] mb-1">${props.nama_kab || 'Kabupaten'}</h3>
            <div class="text-sm text-[#6b7280]">
              <div>ID: ${props.id_kab || 'N/A'}</div>
              <div>Provinsi: ${props.nama_prov || 'Sulawesi Selatan'}</div>
            </div>
            <div class="mt-2 flex gap-2">
              <button onclick="window.dispatchEvent(new CustomEvent('map-zoom-to', {detail: ${JSON.stringify([e.lngLat.lng, e.lngLat.lat])})}))" class="text-xs bg-[#A7B92A] text-white px-2 py-1 rounded">Zoom</button>
              <button onclick="window.dispatchEvent(new CustomEvent('map-bookmark', {detail: ${JSON.stringify({name: props.nama_kab, coords: [e.lngLat.lng, e.lngLat.lat]})})}))" class="text-xs bg-[#6F9435] text-white px-2 py-1 rounded">Bookmark</button>
            </div>
          </div>
        `;
        new Popup({ closeButton: true, maxWidth: '300px' })
          .setLngLat(e.lngLat)
          .setHTML(content)
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

    // Offline/online detection
    window.addEventListener('online', () => { isOffline.value = false; });
    window.addEventListener('offline', () => { isOffline.value = true; });

    // Listen for bookmark events from popup
    window.addEventListener('map-bookmark', (e) => {
      const { name, coords } = e.detail;
      saveBookmark(name, coords);
    });

    window.addEventListener('map-zoom-to', (e) => {
      const [lng, lat] = e.detail;
      map.value.flyTo({ center: [lng, lat], zoom: 12, duration: 2000 });
    });

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

    setTimeout(() => {
      if (loading.value) {
        clearLoading();
        mapStatus.value = 'Peta dimuat sebagian: sebagian layer belum selesai masuk dan masih dimuat di latar belakang.';
      }
    }, 12000);

    map.value.on('load', () => {
      clearLoading();
      mapError.value = null;
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
  if (!map.value) return;
  const layerId = 'hillshade-layer';
  if (map.value.getLayer(layerId)) {
    map.value.setLayoutProperty(layerId, 'visibility', showHillshade.value ? 'visible' : 'none');
  }
}

// Toggle district labels
function toggleDistrictLabels() {
  showDistrictLabels.value = !showDistrictLabels.value;
  if (!map.value) return;
  const layerId = 'kabupaten-labels';
  if (map.value.getLayer(layerId)) {
    map.value.setLayoutProperty(layerId, 'visibility', showDistrictLabels.value ? 'visible' : 'none');
  }
}

// Toggle contour labels
function toggleContourLabels() {
  showContourLabels.value = !showContourLabels.value;
  if (!map.value) return;
  const layerId = 'kontur-labels';
  if (map.value.getLayer(layerId)) {
    map.value.setLayoutProperty(layerId, 'visibility', showContourLabels.value ? 'visible' : 'none');
  }
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
      map.value.flyTo({ center: [longitude, latitude], zoom: 14, duration: 2000 });
      const { Popup } = await import('../../maplibre');
      new Popup()
        .setLngLat([longitude, latitude])
        .setHTML('<div class="p-2 text-[#1f2937]">Lokasi Anda</div>')
        .addTo(map.value);
      locating.value = false;
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
  try {
    searchResults.value = [];
    const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=5&countrycodes=id&accept-language=id`);
    const data = await response.json();
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
    console.error('Search error:', error);
    mapStatus.value = 'Gagal mencari lokasi. Periksa koneksi internet.';
  }
}

async function selectSearchResult(result) {
  if (!map.value) return;
  map.value.flyTo({ center: [result.lon, result.lat], zoom: 14, duration: 2000 });
  const { Popup } = await import('../../maplibre');
  new Popup()
    .setLngLat([result.lon, result.lat])
    .setHTML(`<div class="p-2 text-[#1f2937]">${result.display_name}</div>`)
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
 * although datanya benar, tidak ada yang tergambar. FeatureCollection kosong
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
async function printMapPng({ area = null, region = null, components = null } = {}) {
  if (!map.value) return { ok: false };

  const selected = selectedKabData.value;
  const name = area?.name ?? selected?.nama_kab ?? 'Peta Kontur Sulawesi Selatan';
  const label = area?.label
    ?? (selected ? `Kabupaten ${selected.nama_kab}` : 'Provinsi Sulawesi Selatan');

  const result = await exportMapPng(map.value, {
    title: `Peta Kontur - ${name}`,
    subtitle: label,
    filename: `peta-kontur-${slugify(name)}.png`,
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
    mapStatus.value = 'Jendela cetak diblokir browser. Izinkan pop-up untuk situs ini lalu ulangi.';
    return;
  }

  try {
    const center = map.value.getCenter();
    const zoom = map.value.getZoom();
    const bearing = map.value.getBearing();
    const pitch = map.value.getPitch();

    printWindow.document.open();
    printWindow.document.write(generatePrintHTML(center, zoom, bearing, pitch));
    printWindow.document.close();
    printWindow.focus();
  } catch (error) {
    printWindow.close();
    mapStatus.value = `Gagal menyiapkan halaman cetak: ${error.message}`;
  }
}

function generatePrintHTML(center, zoom, bearing, pitch = 0) {
  const title = 'Peta Kontur Sulawesi Selatan';
  const date = new Date().toLocaleString('id-ID');
  const scale = Math.round(156543.03392 * Math.cos(center.lat * Math.PI / 180) / Math.pow(2, zoom));
  // batas kabupaten dari mapConfig. Versi lama menulis ${url} di dalam template
  // tanpa pernah mendeklarasikan variabelnya, jadi memanggil fungsi ini melempar
  // ReferenceError sebelum HTML-nya sempat ditulis ke jendela cetak.
  const url = geojsonUrl.value;
  // Versi MapLibre harus sama dengan EXTERNAL_LIBS di public/sw.js. Cetak lewat
  // 4.7.1 sedangkan peta offline dan cache service worker memakai 3.6.2, sehingga
  // salinan cetaknya tidak pernah ada di cache dan gagal dimuat saat offline.
  const maplibreVersion = '3.6.2';
  return `<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>${title} - Cetak</title>
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
  <h1>${title}</h1>
  <p>Dicetak pada: ${date} | Koordinat tengah: ${center.lng.toFixed(6)}, ${center.lat.toFixed(6)} | Zoom: ${zoom.toFixed(1)} | Skala ~1:${scale.toLocaleString()}</p>
</div>
<div class="map-container" id="print-map"></div>
<div class="footer">
  <div>Sumber: OpenStreetMap, PMTiles Kontur Sulsel</div>
  <div class="legend">
    <div class="legend-item"><span class="legend-color" style="background:#8c510a"></span> Kontur</div>
    <div class="legend-item"><span class="legend-color" style="background:#2563eb; border:1px dashed #2563eb"></span> Batas Kabupaten</div>
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
    style: {
      version: 8,
      sources: {
        'kontur': { type: 'vector', url: '${pmtilesSourceUrl.value}' },
        'batas': { type: 'geojson', data: '${url}' },
        'osm': { type: 'raster', tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'], tileSize: 256 }
      },
      layers: [
        { id: 'osm', type: 'raster', source: 'osm' },
        { id: 'kontur', type: 'line', source: 'kontur', 'source-layer': 'kontur',
          layout: { 'line-join': 'round', 'line-cap': 'round' },
          paint: { 'line-color': '#8c510a', 'line-width': ['case', ['==', ['%', ['get', 'ELEV'], 50], 0], 1.8, 0.8] }
        },
        { id: 'batas', type: 'line', source: 'batas', paint: { 'line-color': '#2563eb', 'line-width': 1.5, 'line-dasharray': [2, 2] } }
      ]
    },
    center: [${center.lng}, ${center.lat}],
    zoom: ${zoom},
    bearing: ${bearing},
    pitch: ${pitch},
  });
  map.once('load', () => { window.print(); });
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
 * Tombol "Cetak Peta PNG" yang dulu terpisah tidak ada lagi: cetak PNG memang
 * hasil akhir dari unduhan peta offline, bukan fitur lain.
 */
async function downloadOffline() {
  if (!hasPmtiles.value) return;

  downloading.value = true;

  const region = downloadMode.value === 'region' ? selectedOfflineRegionData.value : null;
  const areaName = region ? region.nama_kab : 'Sulawesi Selatan';
  const areaLabel = region ? `Kabupaten ${region.nama_kab}` : 'Provinsi Sulawesi Selatan';

  const components = {
    scaleBar: includeScaleBar.value,
    northArrow: includeNorthArrow.value,
    legend: includeLegend.value,
    histogram: includeHistogram.value,
    grid: includeGrid.value,
  };

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

watch(selectedOfflineRegion, () => {
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
