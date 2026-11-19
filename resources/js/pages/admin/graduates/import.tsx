import { Head, Link, router, useForm } from '@inertiajs/react';
import type { RequestPayload } from '@inertiajs/core';
import { Download } from 'lucide-react';
import { type FormEvent, useState } from 'react';
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

type SheetRow = {
    row: number;
    student_id: string;
    last_name: string;
    email: string | null;
};

type Preview = {
    file: string;
    total: number;
    errors: { row: number; cell: string; type: string; message: string }[];
    already: SheetRow[];
    new_count: number;
    with_email: number;
    without_email: number;
};

type Props = {
    flash: string | null;
    preview: Preview | null;
    counts: Record<string, number>;
    filter: string | null;
    recipients: Recipient[];
    recipientsHidden: number;
    noEmail: Recipient[];
    reminders: {
        enabled: boolean;
        interval_days: number;
        max: number;
        hour: number;
        waiting: number;
    };
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
const SHOWN = 200;

function Badge({ status }: { status: string }) {
    return (
        <span className={`ad-badge ad-badge-${status}`}>
            {STATUS_LABELS[status] ?? status}
        </span>
    );
}

export default function ImportGraduates({
    flash,
    preview,
    counts,
    filter,
    recipients,
    recipientsHidden,
    noEmail,
    reminders,
}: Props) {
    const upload = useForm<{ file: File | null }>({ file: null });
    const schedule = useForm({
        enabled: reminders.enabled,
        interval_days: reminders.interval_days,
        max: reminders.max,
        hour: reminders.hour,
    });
    const [emails, setEmails] = useState<Record<number, string>>({});
    const errors = upload.errors as Record<string, string | undefined>;
    const [pageErrors, setPageErrors] = useState<Record<string, string>>({});

    const sendUpload = (e: FormEvent) => {
        e.preventDefault();
        upload.post('/graduates/import/preview', {
            forceFormData: true,
            preserveScroll: true,
        });
    };

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

    const saveSchedule = (e: FormEvent) => {
        e.preventDefault();
        schedule.patch('/graduates/import/schedule', { preserveScroll: true });
    };

    const hasErrors = (preview?.errors.length ?? 0) > 0;
    const typedCount = Object.values(emails).filter((v) => v.trim()).length;

    return (
        <>
            <Head title="Bulk Upload" />

            <AdminLayout
                active="graduates"
                page="graduates"
                title="Bulk Upload"
                subtitle="Upload graduates and email each one a personal survey link."
                actions={
                    <>
                        <Link href="/graduates" className="ad-btn">
                            ← Back
                        </Link>
                        <a
                            href="/graduates/import/template"
                            className="ad-btn ad-btn-solid"
                        >
                            <Download size={14} aria-hidden /> Download Template
                        </a>
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
                        <h2 className="ad-card-title">1. Upload a file</h2>
                        <p className="ad-note" style={{ margin: '8px 0 16px' }}>
                            Excel (.xlsx) or CSV with Student ID Number (8
                            digits, like 20244012), Last Name and Email. Email
                            can be left blank. Use the template for the right
                            format.
                        </p>

                        <form className="ad-inline" onSubmit={sendUpload}>
                            <input
                                className="ad-input"
                                type="file"
                                aria-label="Excel or CSV file"
                                accept=".xlsx,.csv"
                                onChange={(e) =>
                                    upload.setData(
                                        'file',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <button
                                type="submit"
                                className="ad-btn ad-btn-primary"
                                disabled={
                                    upload.processing || !upload.data.file
                                }
                            >
                                {upload.processing ? 'Checking…' : 'Check File'}
                            </button>
                        </form>
                        {errors.file && (
                            <p className="ad-error" role="alert">
                                {errors.file}
                            </p>
                        )}
                    </section>

                    {preview && (
                        <section className="ad-card">
                            <h2 className="ad-card-title">
                                2. Review {preview.file}
                            </h2>
                            <p
                                className="ad-note"
                                style={{ margin: '8px 0 0' }}
                            >
                                {preview.total} row(s) read.{' '}
                                {hasErrors
                                    ? `${preview.errors.length} problem(s) found. Nothing was saved.`
                                    : `${preview.new_count} new, ${preview.already.length} already uploaded.`}
                            </p>

                            {hasErrors && (
                                <>
                                    <p
                                        className="ad-note"
                                        style={{ margin: '12px 0 8px' }}
                                    >
                                        Fix these cells in your file, then
                                        upload it again.
                                    </p>
                                    <div
                                        className="ad-table"
                                        style={{
                                            ['--cols' as string]: '90px 1fr',
                                        }}
                                    >
                                        <div className="ad-tr ad-th">
                                            <span>Cell</span>
                                            <span>Problem</span>
                                        </div>
                                        {preview.errors
                                            .slice(0, SHOWN)
                                            .map((error, i) => (
                                                <div key={i} className="ad-tr">
                                                    <span className="ad-cell">
                                                        {error.cell}
                                                    </span>
                                                    <span
                                                        style={{
                                                            justifySelf:
                                                                'start',
                                                        }}
                                                    >
                                                        {error.message}
                                                    </span>
                                                </div>
                                            ))}
                                    </div>
                                    {preview.errors.length > SHOWN && (
                                        <p
                                            className="ad-note"
                                            style={{ marginTop: 8 }}
                                        >
                                            Showing the first {SHOWN} of{' '}
                                            {preview.errors.length} problems.
                                        </p>
                                    )}
                                </>
                            )}

                            {!hasErrors && preview.already.length > 0 && (
                                <>
                                    <p
                                        className="ad-note"
                                        style={{ margin: '12px 0 8px' }}
                                    >
                                        These Student ID Numbers are already
                                        uploaded and will be skipped:
                                    </p>
                                    <div
                                        className="ad-table"
                                        style={{
                                            ['--cols' as string]:
                                                '70px 130px 1fr 1.2fr',
                                        }}
                                    >
                                        <div className="ad-tr ad-th">
                                            <span>Row</span>
                                            <span>Student ID</span>
                                            <span>Last Name</span>
                                            <span>Email</span>
                                        </div>
                                        {preview.already
                                            .slice(0, SHOWN)
                                            .map((row) => (
                                                <div
                                                    key={row.row}
                                                    className="ad-tr"
                                                >
                                                    <span className="ad-faint">
                                                        {row.row}
                                                    </span>
                                                    <span>
                                                        {row.student_id}
                                                    </span>
                                                    <span>{row.last_name}</span>
                                                    <span
                                                        style={{
                                                            justifySelf:
                                                                'start',
                                                        }}
                                                    >
                                                        {row.email ?? '—'}
                                                    </span>
                                                </div>
                                            ))}
                                    </div>
                                </>
                            )}

                            {!hasErrors && (
                                <p
                                    className="ad-note"
                                    style={{ marginTop: 14 }}
                                >
                                    Importing {preview.new_count}:{' '}
                                    {preview.with_email} will be emailed,{' '}
                                    {preview.without_email} have no email.
                                </p>
                            )}

                            <div className="ad-actions">
                                <button
                                    type="button"
                                    className="ad-btn"
                                    onClick={() =>
                                        router.delete('/graduates/import', {
                                            preserveScroll: true,
                                        })
                                    }
                                >
                                    {hasErrors ? 'Clear' : 'Cancel'}
                                </button>
                                {!hasErrors && (
                                    <button
                                        type="button"
                                        className="ad-btn ad-btn-primary"
                                        disabled={preview.new_count === 0}
                                        onClick={() =>
                                            post('/graduates/import/confirm')
                                        }
                                    >
                                        {preview.already.length > 0
                                            ? `Skip ${preview.already.length} duplicate(s) and import ${preview.new_count}`
                                            : `Import ${preview.new_count}`}
                                    </button>
                                )}
                            </div>
                        </section>
                    )}

                    <section className="ad-card">
                        <h2 className="ad-card-title">Email status</h2>
                        <p className="ad-note" style={{ margin: '8px 0 0' }}>
                            {counts.total} uploaded. Emails are sent by the
                            queue, so a row can stay Queued for a moment.
                        </p>

                        <div className="ad-chips">
                            <Link
                                href="/graduates/import"
                                className="ad-btn ad-btn-sm"
                                aria-current={filter === null}
                                preserveScroll
                            >
                                All
                            </Link>
                            {FILTERS.map((status) => (
                                <Link
                                    key={status}
                                    href={`/graduates/import?status=${status}`}
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

                    <section className="ad-card">
                        <h2 className="ad-card-title">Reminders</h2>
                        <p className="ad-note" style={{ margin: '8px 0 14px' }}>
                            {reminders.waiting} person(s) were emailed and have
                            not answered yet.
                        </p>

                        <div
                            className="ad-actions"
                            style={{
                                margin: '0 0 18px',
                                justifyContent: 'flex-start',
                            }}
                        >
                            <button
                                type="button"
                                className="ad-btn"
                                disabled={reminders.waiting === 0}
                                onClick={() => post('/graduates/import/remind')}
                            >
                                Send reminder now
                            </button>
                        </div>

                        <form onSubmit={saveSchedule} noValidate>
                            <label className="ad-check">
                                <input
                                    type="checkbox"
                                    checked={schedule.data.enabled}
                                    onChange={(e) =>
                                        schedule.setData(
                                            'enabled',
                                            e.target.checked,
                                        )
                                    }
                                />
                                Send reminders automatically
                            </label>

                            <div
                                className="ad-grid-3"
                                style={{ marginTop: 16 }}
                            >
                                <div>
                                    <label
                                        className="ad-label"
                                        htmlFor="interval_days"
                                    >
                                        Repeat every (days)
                                    </label>
                                    <input
                                        id="interval_days"
                                        className="ad-input"
                                        type="number"
                                        min={1}
                                        max={90}
                                        value={schedule.data.interval_days}
                                        onChange={(e) =>
                                            schedule.setData(
                                                'interval_days',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                    {schedule.errors.interval_days && (
                                        <p className="ad-error">
                                            {schedule.errors.interval_days}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <label className="ad-label" htmlFor="max">
                                        Up to (reminders per person)
                                    </label>
                                    <input
                                        id="max"
                                        className="ad-input"
                                        type="number"
                                        min={1}
                                        max={10}
                                        value={schedule.data.max}
                                        onChange={(e) =>
                                            schedule.setData(
                                                'max',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                    {schedule.errors.max && (
                                        <p className="ad-error">
                                            {schedule.errors.max}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <label className="ad-label" htmlFor="hour">
                                        Send at (hour, 0–23)
                                    </label>
                                    <input
                                        id="hour"
                                        className="ad-input"
                                        type="number"
                                        min={0}
                                        max={23}
                                        value={schedule.data.hour}
                                        onChange={(e) =>
                                            schedule.setData(
                                                'hour',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                    {schedule.errors.hour && (
                                        <p className="ad-error">
                                            {schedule.errors.hour}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="ad-actions">
                                {schedule.recentlySuccessful && (
                                    <span
                                        className="ad-sub"
                                        style={{ alignSelf: 'center' }}
                                        role="status"
                                    >
                                        Saved.
                                    </span>
                                )}
                                <button
                                    type="submit"
                                    className="ad-btn"
                                    disabled={schedule.processing}
                                >
                                    Save Schedule
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </AdminLayout>
        </>
    );
}
