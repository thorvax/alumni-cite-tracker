import { Link, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { CiteLogo, CollegeCredit } from '@/components/cite-logo';
import ThemeToggle from '@/components/survey/theme-toggle';
import { type StepSlug, stepUrl, stepsFor } from '@/lib/survey';

export const SURVEY_FORM_ID = 'survey-form';

type Props = PropsWithChildren<{
    slug: StepSlug;
    page: string; // picks the light-mode palette in survey.css
    title: string;
    description?: string;
    processing?: boolean;
    submitLabel?: string;
    /** Live answer to "presently employed?" (the server sends the saved one). */
    employed?: string | null;
}>;

export default function SurveyLayout({
    slug,
    page,
    title,
    description,
    processing = false,
    submitLabel = 'Next →',
    employed,
    children,
}: Props) {
    const { props } = usePage<{ employed?: string | null }>();

    const steps = stepsFor(employed !== undefined ? employed : props.employed);
    const step = steps.findIndex((s) => s.slug === slug) + 1;
    const previous = steps[step - 2];
    const backHref = previous ? stepUrl(previous.slug) : '/';

    return (
        <div className="sv" data-page={page}>
            <aside className="sv-side">
                <div>
                    <div className="sv-side-top">
                        <CiteLogo />
                    </div>

                    <ol className="sv-steps" aria-label="Survey steps">
                        {steps.map((s, i) => {
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
