import { Head, Link } from '@inertiajs/react';
import AlumniLayout from '@/layouts/alumni-layout';
import { stepUrl } from '@/lib/survey';
import { edit } from '@/routes/alumni/survey';

type Props = {
    graduate: {
        name: string | null;
        student_id: string | null;
        email: string | null;
        contact_number: string | null;
        address: string | null;
        course: string | null;
        year_graduated: number | null;
        employment_status: string | null;
        answered: boolean;
        completed_at: string | null;
        updated_at: string | null;
        /** Has saved answers that can be loaded into the survey. */
        can_edit: boolean;
    };
    status?: string | null;
};

/** The logged-in graduate's page: thank-you and a summary of their answers. */
export default function MySurvey({ graduate, status }: Props) {
    const rows: [string, string | number | null][] = [
        ['Name', graduate.name],
        ['Student ID Number', graduate.student_id],
        ['Email', graduate.email],
        ['Contact number', graduate.contact_number],
        ['Address', graduate.address],
        ['Course', graduate.course],
        ['Year graduated', graduate.year_graduated],
        ['Employment status', graduate.employment_status],
    ];

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

                    {graduate.answered && (
                        <dl className="sv-summary">
                            {rows.map(([label, value]) => (
                                <div key={label}>
                                    <dt>{label}</dt>
                                    <dd>{value ?? '—'}</dd>
                                </div>
                            ))}
                        </dl>
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

                    {!graduate.answered && (
                        <Link
                            href={stepUrl('general-information')}
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
