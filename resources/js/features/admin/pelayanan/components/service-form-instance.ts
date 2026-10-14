import { useForm } from '@inertiajs/react';
import type { ServiceFormData } from '../types';

/*
 * The shared form contract for the service create/edit screen.
 *
 * The three scalar tabs — Informasi Umum, Jadwal & Biaya, Kontak & Pendaftaran —
 * share one `useForm` instance, so each renders its own <form> against the same
 * state (D-54 point 14: one form per scalar tab, so a submit inside a portaled
 * child dialog cannot bubble into a parent form). Each tab therefore has to
 * receive the instance as a prop, and this type is what they are typed against.
 *
 * It lives in its own module rather than in service-form.tsx so the dependency
 * stays one-directional: the tabs and the orchestrator both import this, and no
 * tab imports the form that renders it.
 *
 * The `ReturnType<typeof useForm<...>>` shape mirrors location-form.tsx's
 * LocationFormInstance.
 */
export type ServiceFormInstance = ReturnType<typeof useForm<ServiceFormData>>;
