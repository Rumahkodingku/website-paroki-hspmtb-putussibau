import { useForm } from '@inertiajs/react';
import type { LivestreamFormData } from '../types';

export type LivestreamFormInstance = ReturnType<
    typeof useForm<LivestreamFormData>
>;
