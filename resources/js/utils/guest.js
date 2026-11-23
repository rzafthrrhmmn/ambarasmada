export const CATEGORY_ICON_MAP = {
  Kegiatan: 'events',
  Latihan: 'assessments',
  Outbound: 'map',
  Jambore: 'teams',
  Peringatan: 'announcements',
  Umum: 'announcements',
};

export function categoryIcon(kategori) {
  return CATEGORY_ICON_MAP[kategori] || 'announcements';
}

export function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
}
