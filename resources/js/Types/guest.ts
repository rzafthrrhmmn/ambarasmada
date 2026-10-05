export interface GuestStats {
  members: number;
  alumni: number;
}

export interface GalleryItem {
  src: string;
  title: string;
  description?: string;
  kategori?: string;
}

export interface Announcement {
  id: number;
  judul: string;
  isi: string;
  published_at: string;
  kategori?: string;
}

export interface GuestHomeData {
  ambalan: {
    id: number;
    nama: string;
    kode: string;
    logo_url: string;
  } | null;
  announcements: Announcement[];
  sliderSlides: Array<{
    src: string;
    title: string;
    description: string;
    alt?: string;
    label?: string;
  }>;
  gallery: GalleryItem[];
  stats: GuestStats;
}
