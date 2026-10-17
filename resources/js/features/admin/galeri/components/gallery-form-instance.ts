import { useForm } from '@inertiajs/react';
import type { GalleryAlbumFormData } from '../types';

export type GalleryAlbumFormInstance = ReturnType<
    typeof useForm<GalleryAlbumFormData>
>;
