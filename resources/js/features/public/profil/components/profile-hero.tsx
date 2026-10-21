import { SectionHeading } from '@/components/section-heading';
import type { ReactNode } from 'react';

type ProfileHeroProps = {
    title: string;
    description?: ReactNode;
    eyebrow?: string;
    ariaLabel?: string;
    ariaLabelledBy?: string;
    sectionClassName?: string;
    overlayClassName?: string;
    containerClassName?: string;
};

const DEFAULT_SECTION =
    'relative isolate h-110 overflow-hidden bg-muted bg-cover bg-center';

const DEFAULT_OVERLAY =
    'absolute inset-0 z-0 bg-linear-to-r from-background/95 via-background/75 via-40% to-transparent to-85%';

const DEFAULT_CONTAINER =
    'relative z-10 mx-auto flex h-full min-h-87.5 w-full max-w-7xl items-center px-5 py-14 sm:min-h-97.5 sm:px-8 sm:py-16 lg:min-h-87.5 lg:px-12 lg:py-20';

export function ProfileHero({
    title,
    description,
    eyebrow = 'Profil Paroki',
    ariaLabel,
    ariaLabelledBy,
    sectionClassName = DEFAULT_SECTION,
    overlayClassName = DEFAULT_OVERLAY,
    containerClassName = DEFAULT_CONTAINER,
}: ProfileHeroProps) {
    return (
        <section
            aria-label={ariaLabel}
            aria-labelledby={ariaLabelledBy}
            className={sectionClassName}
            style={{ backgroundImage: "url('/hero-section-2.webp')" }}
        >
            <div aria-hidden="true" className={overlayClassName} />

            <div className={containerClassName}>
                <div className="max-w-xl ">
                    <SectionHeading
                        eyebrow={eyebrow}
                        title={title}
                        level={2}
                        className="gap-3 sm:gap-4"
                    />

                    {description}
                </div>
            </div>
        </section>
    );
}

export default ProfileHero;
