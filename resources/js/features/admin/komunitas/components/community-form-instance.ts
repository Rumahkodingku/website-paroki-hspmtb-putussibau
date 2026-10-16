import { useForm } from '@inertiajs/react';
import type { CommunityFormData } from '../types';

export type CommunityFormInstance = ReturnType<
    typeof useForm<CommunityFormData>
>;
