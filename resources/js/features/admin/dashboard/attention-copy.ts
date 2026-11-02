import type { LucideIcon } from 'lucide-react';
import {
    AlertTriangle,
    CalendarClock,
    Clock,
    FileText,
    ImageOff,
    Images,
    Settings,
} from 'lucide-react';
import type { AttentionKey } from './types';

export type AttentionCopy = {
    icon: LucideIcon;
    title: (count: number) => string;
    description: string;
    action?: string;
};

export const ATTENTION_COPY: Record<AttentionKey, AttentionCopy> = {
    backup_health: {
        icon: AlertTriangle,
        title: (count) => `${count} masalah backup`,
        description:
            'Periksa backup terakhir, worker, scheduler, dan ruang disk sebelum data bergantung pada salinan yang tidak sehat.',
        action: 'Buka Backup',
    },
    expired_documents: {
        icon: Clock,
        title: (count) => `${count} dokumen kedaluwarsa`,
        description:
            'Periksa masa berlaku dokumen, perbarui berkas jika diperlukan, atau ubah statusnya menjadi arsip.',
        action: 'Lihat Dokumen',
    },
    media_alt_text: {
        icon: ImageOff,
        title: (count) =>
            `${count} konten bergambar belum memiliki teks alternatif`,
        description:
            'Lengkapi teks alternatif gambar untuk membantu pengguna pembaca layar memahami konten.',
    },
    stale_drafts: {
        icon: FileText,
        title: (count) => `${count} draf belum diperbarui`,
        description:
            'Draf yang tidak berubah lebih dari dua minggu mungkin perlu dilanjutkan, diperbarui, atau diarsipkan.',
    },
    site_identity: {
        icon: Settings,
        title: (count) => `${count} pengaturan penting belum diisi`,
        description:
            'Identitas inti situs membantu halaman publik dan metadata tampil lengkap.',
        action: 'Buka Pengaturan',
    },
    unpublished_galleries: {
        icon: Images,
        title: (count) => `${count} album belum terbit`,
        description:
            'Album draf tidak tampil di halaman publik. Periksa apakah album sudah siap diterbitkan.',
        action: 'Lihat Galeri',
    },
    scheduled_posts: {
        icon: CalendarClock,
        title: (count) => `${count} artikel akan terbit minggu ini`,
        description:
            'Artikel terjadwal ini akan tampil otomatis. Periksa judul, gambar, dan jadwalnya.',
        action: 'Lihat Artikel Terjadwal',
    },
};
