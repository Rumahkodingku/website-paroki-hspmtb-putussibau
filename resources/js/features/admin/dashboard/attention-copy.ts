import type { LucideIcon } from 'lucide-react';
import { Clock, ImageOff } from 'lucide-react';

export type AttentionCopy = {
    icon: LucideIcon;
    title: (count: number) => string;
    description: string;
};

export const ATTENTION_COPY: Record<string, AttentionCopy> = {
    expired_documents: {
        icon: Clock,
        title: (count) => `${count} dokumen kedaluwarsa`,
        description:
            'Periksa masa berlaku dokumen, perbarui berkas jika diperlukan, atau ubah statusnya menjadi arsip.',
    },
    media_alt_text: {
        icon: ImageOff,
        title: (count) =>
            `${count} konten bergambar belum memiliki teks alternatif`,
        description:
            'Lengkapi teks alternatif gambar untuk membantu pengguna pembaca layar memahami konten.',
    },
};
