import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { CiteLogo, CollegeCredit } from '@/components/cite-logo';
import ThemeToggle from '@/components/survey/theme-toggle';
import { SURVEY_STEPS, stepUrl } from '@/lib/survey';

export const SURVEY_FORM_ID = 'survey-form';

type Props = PropsWithChildren<{
    step: number; // 1-based
    page: string; // picks the light-mode palette in survey.css
    title: string;
    description?: string;
    backHref: string;
    processing?: boolean;
    submitLabel?: string;
}>;

export default function SurveyLayout({
    step,
    page,
    title,
    description,
    backHref,
    processing = false,
    submitLabel = 'Next →',
    children,
}: Props) {
    return (
        <div className="sv" data-page={page}>
            <aside className="sv-side">
                <div>
                    <div className="sv-side-top">
                        <CiteLogo />
                    </div>

                    <ol className="sv-steps" aria-label="Survey steps">
                        {SURVEY_STEPS.map((s, i) => {
                            const number = i + 1;
                            const done = number < step;
                            const content = (
                                <>
                                    <span className="sv-step-circle">
                                        {done ? (
                                            <Check
                                                size={12}
                                                strokeWidth={2.5}
                                                aria-hidden
                                            />
                                        ) : (
                                            number
                                        )}
                                    </span>
                                    <span>{s.label}</span>
                                </>
                            );

                            return (
                                <li key={s.slug}>
                                    {done ? (
                                        <Link
                                            href={stepUrl(s.slug)}
                                            className="sv-step"
                                        >
                                            {content}
                                        </Link>
                                    ) : (
                                        <div
                                            className="sv-step"
                                            aria-current={
                                                number === step
                                                    ? 'step'
                                                    : undefined
                                            }
                                        >
                                            {content}
                                        </div>
                                    )}
                                </li>
                            );
                        })}
                    </ol>
                </div>

                <div className="sv-credit">
                    <CollegeCredit />
                </div>
            </aside>

            <div className="sv-col">
                <ThemeToggle />

                <main className="sv-main">
                    <h1 className="sv-title">{title}</h1>
                    {description && <p className="sv-desc">{description}</p>}
                    <div className="sv-body">{children}</div>
                </main>

                <footer className="sv-footer">
                    <Link href={backHref} className="sv-btn sv-btn-ghost">
                        ← Back
                    </Link>
                    <button
                        type="submit"
                        form={SURVEY_FORM_ID}
                        disabled={processing}
                        className="sv-btn sv-btn-primary"
                    >
                        {submitLabel}
                    </button>
                </footer>
            </div>
        </div>
    );
}
