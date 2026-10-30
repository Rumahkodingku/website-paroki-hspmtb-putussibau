import { usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Image from '@/components/image';
import { ParishLogo } from '@/components/parish-logo';
import { Text } from '@/components/text';
import type { InstallationStep } from '@/types';
import StepIndicator from './components/step-indicator';

type InstallationLayoutProps = {
    steps?: InstallationStep[];
    currentStep?: number;
};

/**
 * The installer shell: branding and step navigation on the left, the current
 * step on the right, and a compact progress header on small screens.
 *
 * It reads `steps`/`currentStep` from page props because this layout is chosen
 * by page name in app.tsx and receives no props of its own. Every wizard page
 * sends both.
 */
export default function InstallationLayout({ children }: PropsWithChildren) {
    const { steps = [], currentStep = 0 } =
        usePage<InstallationLayoutProps>().props;

    const total = steps.length || 7;
    const current = steps[currentStep];
    const progress =
        total > 0 ? Math.round(((currentStep + 1) / total) * 100) : 0;

    return (
        <div className="min-h-svh bg-muted/30 lg:grid lg:grid-cols-[minmax(0,42%)_minmax(0,58%)]">
            <aside className="relative hidden overflow-hidden bg-navy-deep text-white lg:flex lg:flex-col lg:justify-between lg:p-10 xl:p-14">
                <Image
                    src="/auth-section.webp"
                    alt=""
                    aria-hidden="true"
                    fit="cover"
                    className="inset-0 size-full"
                    position="absolute"
                />
                <div className="absolute inset-0 bg-linear-to-b from-navy-deep/95 via-navy-deep/75 to-navy-deep/95" />

                <div className="relative z-10 flex items-center gap-3">
                    <ParishLogo width={44} height={46} className="shrink-0" />

                    <div className="flex min-w-0 flex-col gap-0.5">
                        <Text
                            as="p"
                            variant="body"
                            weight="semibold"
                            color="inherit"
                        >
                            Paroki HSPMTB Putussibau
                        </Text>
                        <Text
                            as="p"
                            variant="caption"
                            color="inherit"
                            className="text-white/70"
                        >
                            Gereja Katolik
                        </Text>
                    </div>
                </div>

                <div className="relative z-10 mt-12 max-w-md">
                    <Text
                        as="p"
                        variant="overline"
                        color="inherit"
                        className="text-amber-400"
                    >
                        Pemasangan aplikasi
                    </Text>

                    <Text
                        as="h2"
                        variant="h2"
                        color="inherit"
                        className="mt-3 text-white"
                    >
                        Tujuh langkah menuju website paroki yang siap digunakan.
                    </Text>

                    <Text
                        as="p"
                        variant="body"
                        color="inherit"
                        leading="relaxed"
                        className="mt-4 text-white/75"
                    >
                        Pemeriksaan server, verifikasi database, akun Super
                        Admin, dan identitas paroki. Tidak ada data paroki yang
                        dibuat secara otomatis.
                    </Text>

                    <div className="mt-10">
                        <StepIndicator
                            steps={steps}
                            currentStep={currentStep}
                        />
                    </div>
                </div>

                <Text
                    as="p"
                    variant="fine-print"
                    color="inherit"
                    leading="relaxed"
                    className="relative z-10 mt-12 text-white/45"
                >
                    Installer hanya untuk instalasi baru. Setelah selesai,
                    installer terkunci dan tidak dapat dijalankan kembali.
                </Text>
            </aside>

            <main className="flex min-h-svh flex-col">
                <header className="border-b border-border bg-background px-4 py-3 lg:hidden">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <ParishLogo width={30} height={32} />
                            <Text as="span" variant="body-sm" weight="semibold">
                                HSPMTB
                            </Text>
                        </div>

                        <Text as="span" variant="caption" color="muted">
                            Langkah {currentStep + 1} dari {total}
                        </Text>
                    </div>

                    <div
                        className="mt-3 h-1.5 overflow-hidden rounded-full bg-muted"
                        role="presentation"
                    >
                        <div
                            className="h-full rounded-full bg-primary transition-[width] duration-300"
                            style={{ width: `${progress}%` }}
                        />
                    </div>

                    {current ? (
                        <Text
                            as="p"
                            variant="caption"
                            color="muted"
                            className="mt-2"
                        >
                            {current.label}
                        </Text>
                    ) : null}
                </header>

                <div className="flex flex-1 justify-center px-4 py-8 sm:px-6 lg:items-center lg:px-12 lg:py-12">
                    <div className="w-full max-w-3xl">{children}</div>
                </div>

                <footer className="px-4 pb-6 lg:px-12">
                    <Text
                        as="p"
                        variant="fine-print"
                        color="muted"
                        className="text-center lg:text-left"
                    >
                        Installation Wizard · Website Paroki HSPMTB Putussibau
                    </Text>
                </footer>
            </main>
        </div>
    );
}
