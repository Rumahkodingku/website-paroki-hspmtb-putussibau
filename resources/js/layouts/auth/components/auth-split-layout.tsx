import { Link, usePage } from '@inertiajs/react';
import { Text } from '@/components/text';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';
import ParishLogo from '@/components/parish-logo';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="relative grid h-dvh grid-cols-1 flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-[70%_30%] lg:px-0">
            {/* Left section */}
            <div className="relative hidden h-full flex-col overflow-hidden p-10 text-white lg:flex xl:p-12">
                {/* Background image */}
                <img
                    src="/auth-section.webp"
                    alt=""
                    aria-hidden="true"
                    className="absolute inset-0 size-full object-cover object-center"
                />

                {/* Cinematic overlay */}
                <div className="absolute inset-0 bg-linear-to-b from-black/80 via-black/30 to-black/90" />

                {/* Additional contrast */}
                <div className="absolute inset-0 bg-linear-to-r from-black/30 via-transparent to-transparent" />

                {/* Content wrapper */}
                <div className="relative z-10 flex h-full flex-col">
                    {/* Header */}
                    <div className="flex items-start justify-between gap-6">
                        {/* Parish branding */}
                        <Link
                            href={home()}
                            className="flex min-w-0 items-center gap-4"
                        >
                            <ParishLogo className="size-14 shrink-0" />

                            <div className="flex min-w-0 flex-col gap-1">
                                <Text
                                    as="span"
                                    variant="body-lg"
                                    weight="semibold"
                                    className="text-white"
                                >
                                    {name}
                                </Text>

                                <Text
                                    as="span"
                                    variant="overline"
                                    color="inherit"
                                    tracking="widest"
                                    className="text-white/65"
                                >
                                    Gereja Katolik
                                </Text>

                                <Text
                                    as="span"
                                    variant="fine-print"
                                    color="inherit"
                                    tracking="wide"
                                    leading="relaxed"
                                    className="max-w-md text-white/75 uppercase"
                                >
                                    Hati Santa Perawan Maria Tak Bernoda
                                </Text>
                            </div>
                        </Link>

                        {/* Scripture quote */}
                        <div className="hidden max-w-64 shrink-0 text-right xl:block">
                            <Text
                                as="p"
                                variant="body"
                                color="inherit"
                                italic
                                leading="relaxed"
                                className="text-white/90"
                            >
                                “Lakukanlah segala sesuatu dengan kasih.”
                            </Text>

                            <div className="mt-3 flex items-center justify-end gap-3">
                                <span
                                    aria-hidden="true"
                                    className="h-px w-7 bg-amber-500"
                                />

                                <Text
                                    as="p"
                                    variant="caption"
                                    color="inherit"
                                    className="text-white/65"
                                >
                                    1 Korintus 16:14
                                </Text>
                            </div>
                        </div>
                    </div>

                    {/* Hero content */}
                    <div className="mt-auto max-w-3xl pt-16 pb-6">
                        <Text
                            as="p"
                            variant="overline"
                            color="inherit"
                            tracking="widest"
                            className="mb-4 text-white/80"
                        >
                            Portal Administrasi Paroki
                        </Text>

                        <Text
                            as="h2"
                            variant="display-md"
                            color="inherit"
                            weight="semibold"
                            leading="tight"
                            className="max-w-3xl text-white xl:text-5xl"
                        >
                            Melayani dengan iman,
                            <br />
                            mengelola dengan lebih baik.
                        </Text>

                        <Text
                            as="p"
                            variant="body"
                            color="inherit"
                            leading="relaxed"
                            className="mt-5 max-w-xl text-white/80 sm:text-base"
                        >
                            Ruang pengelolaan informasi, pelayanan, dan kegiatan
                            Gereja Paroki HSPMTB Putussibau.
                        </Text>
                    </div>

                    {/* Footer */}
                    <div className="flex items-end justify-between gap-6">
                        <div className="flex flex-col gap-1.5">
                            <div
                                aria-hidden="true"
                                className="mb-4 h-px w-10 shrink-0 bg-amber-500"
                            />
                            <Text
                                as="p"
                                variant="body-sm"
                                color="inherit"
                                weight="medium"
                                className="text-white/90"
                            >
                                Gereja Paroki HSPMTB Putussibau
                            </Text>

                            <Text
                                as="p"
                                variant="fine-print"
                                color="inherit"
                                leading="relaxed"
                                className="text-white/60"
                            >
                                Putussibau, Kapuas Hulu, Kalimantan Barat
                            </Text>
                        </div>
                    </div>
                </div>
            </div>

            {/* Right section */}
            <div className="flex min-h-dvh w-full flex-col px-6 py-8 sm:px-8 lg:h-full lg:min-h-0 lg:px-8 lg:py-10 xl:px-10">
                {/* Main content */}
                <div className="flex flex-1 flex-col justify-center">
                    <div className="mx-auto flex w-full flex-col sm:max-w-100">
                        {/* Parish logo */}
                        <Link
                            href={home()}
                            aria-label={`${name} - Beranda`}
                            className="mb-4"
                        >
                            <ParishLogo width={48} height={48} />
                        </Link>

                        {/* Heading */}
                        <div className="mb-8 flex flex-col items-start gap-4 text-left">
                            <Text
                                as="p"
                                variant="caption"
                                tracking="widest"
                                weight="semibold"
                                className="text-primary uppercase"
                            >
                                Panel Administrasi
                            </Text>

                            <div className="flex flex-col gap-1">
                                <Text as="h1" variant="heading-lg">
                                    {title}
                                </Text>

                                <Text as="p" variant="body-sm" color="muted">
                                    {description}
                                </Text>
                            </div>
                        </div>

                        {/* Authentication form */}
                        {children}
                    </div>
                </div>

                {/* Footer */}
                <div className="mx-auto mt-8 flex w-full max-w-100 flex-col items-center gap-2 border-border-subtle pt-5 text-center">
                    <Text as="p" variant="fine-print" color="muted">
                        Akses khusus pengelola yang berwenang
                    </Text>

                    <Text as="p" variant="fine-print" color="subtle">
                        {name}
                    </Text>
                </div>
            </div>
        </div>
    );
}
