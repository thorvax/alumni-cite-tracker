import { Head, Link, router } from '@inertiajs/react';
import type { RequestPayload } from '@inertiajs/core';
import { useState } from 'react';
import AdminLayout from '@/layouts/admin-layout';

type Recipient = {
    id: number;
    student_id: string | null;
    last_name: string;
    email: string | null;
    status: string;
    error: string | null;
    sent_at: string | null;
    reminders: number;
};

type Props = {
    flash: string | null;
    counts: Record<string, number>;
    filter: string | null;
    recipients: Recipient[];
    recipientsHidden: number;
    noEmail: Recipient[];
};

const STATUS_LABELS: Record<string, string> = {
    pending: 'Pending',
    queued: 'Queued',
    sent: 'Sent',
    failed: 'Failed',
    answered: 'Answered',
    no_email: 'No email',
};

const FILTERS = ['pending', 'queued', 'sent', 'failed', 'answered'];

function Badge({ status }: { status: string }) {
    return (
        <span className={`ad-badge ad-badge-${status}`}>
            {STATUS_LABELS[status] ?? status}
        </span>
    );
}

/**
 * Invitations: the email status of everyone uploaded (Send / Resend), and the
 * "No email address" list. Survey reminders are under sidebar > Reminders.
 */
export default function Invitations({
    flash,
    counts,
    filter,
    recipients,
    recipientsHidden,
    noEmail,
}: Props) {
    const [emails, setEmails] = useState<Record<number, string>>({});
    const [pageErrors, setPageErrors] = useState<Record<string, string>>({});

    const post = (url: string, data: RequestPayload = {}) =>
        router.post(url, data, { preserveScroll: true });

    const sendOne = (id: number) =>
        router.patch(
            `/graduates/import/${id}/email`,
            { email: emails[id] ?? '' },
            {
                preserveScroll: true,
                onError: (e) => setPageErrors(e),
                onSuccess: () => setPageErrors({}),
            },
        );

    const sendAll = () =>
        router.post(
            '/graduates/import/emails',
            { emails },
            {
                preserveScroll: true,
                onError: (e) => setPageErrors(e),
                onSuccess: () => {
                    setPageErrors({});
                    setEmails({});
                },
            },
        );

    const typedCount = Object.values(emails).filter((v) => v.trim()).length;

    return (
        <>
            <Head title="Invitations" />

            <AdminLayout
                active="invitations"
                page="graduates"
                title="Invitations"
                subtitle="Email status of the personal survey links sent to uploaded graduates."
                actions={
                    <>
                        <Link href="/graduates/import" className="ad-btn">
                            Bulk Upload
                        </Link>
                        <Link href="/reminders/survey" className="ad-btn">
                            Survey reminders
                        </Link>
                    </>
                }
            >
                <div className="ad-stack">
                    {flash && (
                        <p className="ad-flash" role="status">
                            {flash}
                        </p>
                    )}

                    <section className="ad-card">
                        <h2 className="ad-card-title">Email status</h2>
                        <p className="ad-note" style={{ margin: '8px 0 0' }}>
                            {counts.total} uploaded. Emails are sent by the
                            queue, so a row can stay Queued for a moment.
                        </p>

                        <div className="ad-chips">
                            <Link
                                href="/graduates/invitations"
                                className="ad-btn ad-btn-sm"
                                aria-current={filter === null}
                                preserveScroll
                            >
                                All
                            </Link>
                            {FILTERS.map((status) => (
                                <Link
                                    key={status}
                                    href={`/graduates/invitations?status=${status}`}
                                    className="ad-btn ad-btn-sm"
                                    aria-current={filter === status}
                                    preserveScroll
                                >
                                    {STATUS_LABELS[status]} (
                                    {counts[status] ?? 0})
                                </Link>
                            ))}
                            {(counts.failed ?? 0) > 0 && (
                                <button
                                    type="button"
                                    className="ad-btn ad-btn-sm"
                                    onClick={() =>
                                        post('/graduates/import/resend-failed')
                                    }
                                >
                                    Resend all failed
                                </button>
                            )}
                        </div>

                        <div
                            className="ad-table"
                            style={{
                                ['--cols' as string]:
                                    '110px 1fr 1.4fr 110px 90px',
                            }}
                        >
                            <div className="ad-tr ad-th">
                                <span>Student ID</span>
                                <span>Last Name</span>
                                <span>Email</span>
                                <span>Status</span>
                                <span>Action</span>
                            </div>

                            {recipients.length === 0 && (
                                <p className="ad-empty">No one here yet.</p>
                            )}

                            {recipients.map((person) => (
                                <div key={person.id} className="ad-tr">
                                    <span>{person.student_id}</span>
                                    <span>{person.last_name}</span>
                                    <span style={{ justifySelf: 'start' }}>
                                        {person.email}
                                    </span>
                                    <span style={{ justifySelf: 'start' }}>
                                        <Badge status={person.status} />
                                    </span>
                                    {person.status === 'answered' ? (
                                        <span className="ad-faint">Done</span>
                                    ) : (
                                        <button
                                            type="button"
                                            className="ad-btn ad-btn-sm"
                                            disabled={
                                                person.status === 'queued'
                                            }
                                            onClick={() =>
                                                post(
                                                    `/graduates/import/${person.id}/resend`,
                                                )
                                            }
                                        >
                                            {person.status === 'failed'
                                                ? 'Resend'
                                                : person.status === 'pending'
                                                  ? 'Send'
                                                  : 'Resend'}
                                        </button>
                                    )}
                                    {person.status === 'failed' &&
                                        person.error && (
                                            <p className="ad-error ad-row-error">
                                                {person.error}
                                            </p>
                                        )}
                                </div>
                            ))}
                        </div>
                        {recipientsHidden > 0 && (
                            <p className="ad-note" style={{ marginTop: 8 }}>
                                Showing the latest {recipients.length};{' '}
                                {recipientsHidden} more. Use a filter to narrow
                                the list.
                            </p>
                        )}
                    </section>

                    {noEmail.length > 0 && (
                        <section className="ad-card">
                            <h2 className="ad-card-title">
                                No email address ({noEmail.length})
                            </h2>
                            <p
                                className="ad-note"
                                style={{ margin: '8px 0 14px' }}
                            >
                                Type an email for each person, then send one at
                                a time or all at once.
                            </p>

                            <div
                                className="ad-table"
                                style={{
                                    ['--cols' as string]:
                                        '110px 1fr 1.6fr 80px',
                                }}
                            >
                                <div className="ad-tr ad-th">
                                    <span>Student ID</span>
                                    <span>Last Name</span>
                                    <span>Email</span>
                                    <span>Action</span>
                                </div>
                                {noEmail.map((person) => (
                                    <div
                                        key={person.id}
                                        className="ad-tr"
                                        style={{ padding: '6px 16px' }}
                                    >
                                        <span>{person.student_id}</span>
                                        <span>{person.last_name}</span>
                                        <div>
                                            <input
                                                className="ad-input"
                                                type="email"
                                                aria-label={`Email for ${person.last_name}`}
                                                placeholder="name@example.com"
                                                value={emails[person.id] ?? ''}
                                                onChange={(e) =>
                                                    setEmails({
                                                        ...emails,
                                                        [person.id]:
                                                            e.target.value,
                                                    })
                                                }
                                            />
                                            {pageErrors[
                                                `emails.${person.id}`
                                            ] && (
                                                <p className="ad-error">
                                                    {
                                                        pageErrors[
                                                            `emails.${person.id}`
                                                        ]
                                                    }
                                                </p>
                                            )}
                                        </div>
                                        <button
                                            type="button"
                                            className="ad-btn ad-btn-sm"
                                            disabled={
                                                !emails[person.id]?.trim()
                                            }
                                            onClick={() => sendOne(person.id)}
                                        >
                                            Send
                                        </button>
                                    </div>
                                ))}
                            </div>
                            {(pageErrors.emails || pageErrors.email) && (
                                <p className="ad-error" role="alert">
                                    {pageErrors.emails ?? pageErrors.email}
                                </p>
                            )}

                            <div className="ad-actions">
                                <button
                                    type="button"
                                    className="ad-btn ad-btn-primary"
                                    disabled={typedCount === 0}
                                    onClick={sendAll}
                                >
                                    Send all ({typedCount})
                                </button>
                            </div>
                        </section>
                    )}
                </div>
            </AdminLayout>
        </>
    );
}
