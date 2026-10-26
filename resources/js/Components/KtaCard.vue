<template>
  <div ref="stage" class="kta-stage" :style="stageStyle">
    <div class="kta-card" :style="cardStyle">
      <div class="kta-accent kta-accent-top" />
      <div class="kta-accent kta-accent-bottom" />

      <div class="kta-inner">
        <header class="kta-header">
          <div class="kta-header-text">
            <p class="kta-ambalan">{{ ambalanName }}</p>
            <p class="kta-title">Kartu Tanda Anggota</p>
            <p class="kta-subtitle">Ekosistem Digital UPT SMAN 2 Maros</p>
          </div>
          <div class="kta-badges">
            <span class="kta-badge kta-badge-ghost">{{ member.tingkatan }}</span>
            <span class="kta-badge kta-badge-gold">{{ member.status_aktif }}</span>
          </div>
        </header>

        <div class="kta-body">
          <div class="kta-photo">
              <img
                v-if="photoSrc"
                :src="photoSrc"
                alt="Foto anggota"
                class="kta-photo-img"
                loading="eager"
                @error="photoFailed = true"
              />
            <span v-else class="kta-photo-initial">{{ initials }}</span>
            <span class="kta-photo-tag">FOTO 3×4</span>
          </div>

          <div class="kta-info">
            <p class="kta-field-label">Nama Anggota</p>
            <h3 class="kta-name" :title="member.nama_lengkap">{{ member.nama_lengkap }}</h3>
            <p class="kta-position">{{ positionLabel }}</p>

            <dl class="kta-grid">
              <div class="kta-grid-item">
                <dt>NTA</dt>
                <dd class="kta-mono kta-gold">{{ member.nta || '-' }}</dd>
              </div>
              <div class="kta-grid-item">
                <dt>Angkatan</dt>
                <dd>{{ member.angkatan || '-' }}</dd>
              </div>
              <div class="kta-grid-item">
                <dt>Kelas</dt>
                <dd>{{ member.kelas || '-' }}</dd>
              </div>
              <div class="kta-grid-item">
                <dt>No. Urut</dt>
                <dd>{{ nomorUrut }}</dd>
              </div>
              <div class="kta-grid-item">
                <dt>TTL</dt>
                <dd>{{ ttl }}</dd>
              </div>
              <div class="kta-grid-item">
                <dt>Jenis Kelamin</dt>
                <dd>{{ member.jenis_kelamin || '-' }}</dd>
              </div>
              <div v-if="member.tahun_lulus" class="kta-grid-item">
                <dt>Tahun Lulus</dt>
                <dd>{{ member.tahun_lulus }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <footer class="kta-footer">
          <div class="kta-footer-text">
            <p class="kta-footer-label">Diterbitkan oleh</p>
            <p class="kta-footer-value">{{ ambalanName }}</p>
            <p class="kta-footer-date">Cetak: {{ today }}</p>
          </div>
          <div class="kta-qr">
            <div class="kta-qr-box">
              <img v-if="qrDataUrl" :src="qrDataUrl" alt="Kode QR verifikasi" />
            </div>
            <p class="kta-qr-code">{{ member.qr_code || '-' }}</p>
          </div>
        </footer>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import QRCode from 'qrcode';
import '../../css/kta-card.css';

const props = defineProps({
  member: { type: Object, required: true },
  ambalan: { type: Object, default: null },
});

const CARD_WIDTH_MM = 86;
const CARD_HEIGHT_MM = 54;
const MM_TO_PX = 96 / 25.4;

const stage = ref(null);
const scale = ref(1);
let observer = null;

const stageStyle = computed(() => ({
  height: `${CARD_HEIGHT_MM * scale.value * MM_TO_PX}px`,
}));

const cardStyle = computed(() => ({
  transform: `scale(${scale.value})`,
}));

onMounted(() => {
  if (!stage.value) return;

  observer = new ResizeObserver((entries) => {
    const width = entries[0]?.contentRect.width ?? 0;
    scale.value = width > 0 ? Math.min(1, width / (CARD_WIDTH_MM * MM_TO_PX)) : 1;
  });
  observer.observe(stage.value);
});

onUnmounted(() => observer?.disconnect());

const photoFailed = ref(false);

watch(() => props.member?.user?.foto, () => {
  photoFailed.value = false;
});

const photoSrc = computed(() => {
  const foto = props.member?.user?.foto;
  if (!foto || photoFailed.value) return null;
  if (foto.startsWith('data:') || foto.startsWith('http') || foto.startsWith('blob:')) {
    return foto;
  }
  return `/storage/${foto}`;
});

const qrDataUrl = ref('');
let qrSequence = 0;

watch(() => props.member?.qr_code, async (code) => {
  if (!code) {
    qrDataUrl.value = '';
    return;
  }

  const sequence = ++qrSequence;
  const payload = `${window.location.origin}/attendance/scan/${code}`;

  try {
    const url = await QRCode.toDataURL(payload, {
      width: 256,
      margin: 0,
      errorCorrectionLevel: 'M',
      color: { dark: '#000000', light: '#ffffff' },
    });
    if (sequence === qrSequence) qrDataUrl.value = url;
  } catch {
    if (sequence === qrSequence) qrDataUrl.value = '';
  }
}, { immediate: true });

const ambalanName = computed(() => props.ambalan?.nama || 'Ambalan Pramuka UPT SMAN 2 Maros');
const positionLabel = computed(() => props.member?.position_label || 'Anggota');
const initials = computed(() => (props.member?.nama_lengkap || 'PA').trim().charAt(0).toUpperCase());
const nomorUrut = computed(() => (props.member?.nomor_urut
  ? String(props.member.nomor_urut).padStart(3, '0')
  : '-'));
const ttl = computed(() => {
  const m = props.member;
  if (!m?.tempat_lahir && !m?.tanggal_lahir) return '-';
  const place = m.tempat_lahir || '-';
  const date = m.tanggal_lahir ? new Date(m.tanggal_lahir).toLocaleDateString('id-ID') : '-';
  return `${place}, ${date}`;
});
const today = computed(() => new Date().toLocaleDateString('id-ID'));
</script>
