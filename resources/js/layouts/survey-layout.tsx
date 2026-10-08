import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { CiteLogo, CollegeCredit } from '@/components/cite-logo';
import { cn } from '@/lib/utils';

export const SURVEY_FORM_ID = 'survey-form';

const steps = [
    'A. General Information',
    'B. Educational Background',
    'C. Trainings & Advance Studies',
    'D. Employment Data',
    'D. Job Details',
    'D. First / Current Job',
    'Referral & Submit',
];

type Props = PropsWithChildren<{
    current: number; // 1-based step number
    title: string;
    description: string;
    backHref: string;
    processing?: boolean;
}>;

export default function SurveyLayout({
    current,
    title,
    description,
    backHref,
    processing = false,
    children,
}: Props) {
    return (
        <div className="flex min-h-screen bg-[#2b2b2b] text-white">
            {/* Step sidebar (desktop) */}
            <aside className="hidden w-[230px] shrink-0 flex-col justify-between border-r border-white/20 py-6 md:flex">
                <div>
                    <div className="px-6">
                        <CiteLogo />
                    </div>

                    <ol className="mt-12 flex flex-col gap-1 px-3">
                        {steps.map((label, i) => {
                            const number = i + 1;
                            const active = number === current;

                            return (
                                <li
                                    key={label}
                                    aria-current={active ? 'step' : undefined}
                                    className={cn(
                                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-[13px]',
                                        active
                                            ? 'bg-[#1c1c1c] font-medium text-white'
                                            : 'text-white/80',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'flex size-5 shrink-0 items-center justify-center rounded-full border text-[10px] font-semibold',
                                            active
                                                ? 'border-white bg-white text-black'
                                                : 'border-white/40 text-white/50',
                                        )}
                                    >
                                        {number}
                                    </span>
                                    {label}
                                </li>
                            );
                        })}
                    </ol>
                </div>

                <div className="px-5">
                    <CollegeCredit />
                </div>
            </aside>

            {/* Content */}
            <div className="flex min-w-0 flex-1 flex-col">
                <div className="flex-1 px-6 pt-8 md:px-10 md:pt-10">
                    <p className="mb-4 text-xs text-white/60 md:hidden">
                        Step {current} of {steps.length}
                    </p>

                    <h1 className="text-2xl font-bold tracking-tight">
                        {title}
                    </h1>
                    <p className="mt-2 text-[13px] text-white/90">
                        {description}
                    </p>

                    <div className="mt-8 max-w-[820px]">{children}</div>
                </div>

                <div className="flex justify-end gap-3 px-6 py-8 md:px-10">
                    <Link
                        href={backHref}
                        className="rounded-md bg-[#1a1a1a] px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-black focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:outline-none"
                    >
                        ← Back
                    </Link>
                    <button
                        type="submit"
                        form={SURVEY_FORM_ID}
                        disabled={processing}
                        className="rounded-md bg-[#f0f0f0] px-6 py-3 text-sm font-semibold text-black transition-colors hover:bg-white focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:outline-none disabled:opacity-60"
                    >
                        Next →
                    </button>
                </div>
            </div>
        </div>
    );
}
