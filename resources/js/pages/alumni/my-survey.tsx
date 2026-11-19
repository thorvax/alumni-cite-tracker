import { Head, Link } from '@inertiajs/react';
import AlumniLayout from '@/layouts/alumni-layout';
import { edit } from '@/routes/alumni/survey';
import { start as surveyStart } from '@/routes/survey';

type Section = {
    key: string;
    title: string;
    rows: { label: string; value: string }[];
};

type Props = {
    graduate: {
        name: string | null;
        answered: boolean;
        completed_at: string | null;
        updated_at: string | null;
        /** Has saved answers that can be loaded into the survey. */
        can_edit: boolean;
    };
    /** Every answered question, grouped like the admin Graduate Details page. */
    sections: Section[];
    status?: string | null;
};

/** The logged-in graduate's page: thank-you and all of their answers (read only). */
export default function MySurvey({ graduate, sections, status }: Props) {
    return (
        <>
            <Head title="My Survey" />

            <AlumniLayout
                wide
                title={
                    graduate.answered ? 'Thank you for answering!' : 'My Survey'
                }
                description={
                    graduate.answered
                        ? `You answered the Graduate Tracer Survey on ${graduate.completed_at}${graduate.updated_at ? ` and last updated it on ${graduate.updated_at}` : ''}.`
                        : 'You have not answered the Graduate Tracer Survey yet.'
                }
            >
                <div className="sv-auth-body">
                    {status && (
                        <p className="sv-auth-status" role="status">
                            {status}
                        </p>
                    )}

                    {graduate.answered && graduate.can_edit && (
                        <>
                            <p className="sv-auth-note">
                                Changed jobs, moved, or got a new number? Please
                                keep your answers up to date. It helps CITE
                                improve its programs.
                            </p>
                            <Link
                                href={edit()}
                                as="button"
                                className="sv-btn sv-btn-primary"
                            >
                                Update my answers
                            </Link>
                        </>
                    )}

                    {sections.length > 0 && (
                        <div className="sv-answers">
                            <h2 className="sv-intro-heading">Your answers</h2>

                            {sections.map((section, i) => (
                                <details key={section.key} open={i === 0}>
                                    <summary>{section.title}</summary>
                                    <dl className="sv-summary">
                                        {section.rows.map((row) => (
                                            <div key={row.label}>
                                                <dt>{row.label}</dt>
                                                <dd>{row.value}</dd>
                                            </div>
                                        ))}
                                    </dl>
                                </details>
                            ))}
                        </div>
                    )}

                    {!graduate.answered && (
                        <Link
                            href={surveyStart()}
                            className="sv-btn sv-btn-primary"
                        >
                            Answer the survey
                        </Link>
                    )}
                </div>
            </AlumniLayout>
        </>
    );
}
