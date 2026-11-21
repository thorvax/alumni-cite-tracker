import { Head, Link, router } from '@inertiajs/react';
import Pagination from '@/components/admin/pagination';
import AudiencePreview, {
    type AudiencePreviewData,
} from '@/components/admin/audience-preview';
import AdminLayout from '@/layouts/admin-layout';
import type { Paginated } from '@/lib/admin';

type Announcement = {
    id: number;
    type: 'announcement' | 'event';
    title: string;
    body: string;
    audience: string;
    audience_type: string;
    visibility: 'public' | 'selected';
    send_email: boolean;
    status: 'draft' | 'scheduled' | 'sent';
    event_date: string | null;
    event_place: string;
    event_link: string;
    show_until_label: string | null;
    publish_at_label: string | null;
    published_at: string | null;
    created_by: string | null;
};

type Recipient = {
    id: number;
    student_id: string | null;
    name: string | null;
    email: string | null;
    activated: boolean;
    status: string;
    error: string | null;
    emailed_at: string | null;
    read_at: string | null;
};

const STATUS_LABELS: Record<string, string> = {
    draft: 'Draft',
    scheduled: 'Scheduled',
    sent: 'Sent',
    pending: 'Pending',
    queued: 'Queued',
    failed: 'Failed',
    skipped: 'No email',
};

const FILTERS = ['queued', 'sent', 'failed', 'skipped'];

/**
 * One announcement: its message, who it went to, the email status per
 * person (Resend / Resend all failed) and who has read it.
 */
export default function ShowAnnouncement({
    flash,
    announcement: a,
    counts,
    filter,
    recipients,
    preview,
}: {
    flash: string | null;
    announcement: Announcement;
    counts: Record<string, number>;
    filter: string | null;
    recipients: Paginated<Recipient>;
    preview: AudiencePreviewData | null;
}) {
    const sent = a.status === 'sent';
    const post = (url: string) =>
        router.post(url, {}, { preserveScroll: true });

    const sendNow = () => {
        if (window.confirm(`Send "${a.title}" now?`)) {
            post(`/admin/announcements/${a.id}/send`);
        }
    };

    const remove = () => {
        if (window.confirm(`Delete the draft "${a.title}"?`)) {
            router.delete(`/admin/announcements/${a.id}`);
        }
    };

    const filterUrl = (status: string | null) =>
        `/admin/announcements/${a.id}${status ? `?status=${status}` : ''}`;

    return (
        <>
            <Head title={a.title} />

            <AdminLayout
                active="announcements"
                page="announcements"
                title={a.title}
                subtitle={`${a.type === 'event' ? 'Event' : 'Announcement'} · ${a.audience}${a.visibility === 'public' ? ' · Public' : ''}`}
                actions={
                    <>
                        <Link href="/admin/announcements" className="ad-btn">
                            All announcements
                        </Link>
                        <Link
                            href={`/admin/announcements/${a.id}/edit`}
                            className="ad-btn"
                        >
                            {sent ? 'Fix typos / show longer' : 'Edit'}
                        </Link>
                        {!sent && (
                            <>
                                <button
                                    type="button"
                                    className="ad-btn"
                                    onClick={remove}
                                >
                                    Delete
                                </button>
                                <button
                                    type="button"
                                    className="ad-btn ad-btn-primary"
                                    onClick={sendNow}
                                >
                                    Send now
                                </button>
                            </>
                        )}
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
                        <div className="ad-card-head">
                            <h2 className="ad-card-title">Message</h2>
                            <span className={`ad-badge ad-badge-${a.status}`}>
                                {STATUS_LABELS[a.status]}
                            </span>
                        </div>

                        <dl className="ad-kv ad-gap-top">
                            <div>
                                <dt>Status</dt>
                                <dd>
                                    {sent
                                        ? `Sent ${a.published_at ?? ''}`
                                        : a.status === 'scheduled'
                                          ? `Will be sent ${a.publish_at_label ?? ''}`
                                          : 'Draft (not sent)'}
                                </dd>
                            </div>
                            <div>
                                <dt>Who can see it</dt>
                                <dd>
                                    {a.visibility === 'public'
                                        ? 'Everyone (public page)'
                                        : 'Only its recipients, after logging in'}
                                </dd>
                            </div>
                            <div>
                                <dt>Email</dt>
                                <dd>{a.send_email ? 'Yes' : 'Site only'}</dd>
                            </div>
                            <div>
                                <dt>Show until</dt>
                                <dd>{a.show_until_label ?? 'No end date'}</dd>
                            </div>
                            {a.type === 'event' && (
                                <>
                                    <div>
                                        <dt>Event date</dt>
                                        <dd>{a.event_date}</dd>
                                    </div>
                                    <div>
                                        <dt>Place</dt>
                                        <dd>{a.event_place}</dd>
                                    </div>
                                    {a.event_link && (
                                        <div>
                                            <dt>Link</dt>
                                            <dd>
                                                <a
                                                    href={a.event_link}
                                                    target="_blank"
                                                    rel="noreferrer noopener"
                                                    className="ad-btn-link"
                                                >
                                                    {a.event_link}
                                                </a>
                                            </dd>
                                        </div>
                                    )}
                                </>
                            )}
                            {a.created_by && (
                                <div>
                                    <dt>Posted by</dt>
                                    <dd>{a.created_by}</dd>
                                </div>
                            )}
                        </dl>

                        {/* Plain text: React escapes it; CSS keeps the line breaks. */}
                        <p className="ad-message ad-gap-top">{a.body}</p>
                    </section>

                    {preview && (
                        <section className="ad-card">
                            <h2 className="ad-card-title">
                                Who it will reach (today)
                            </h2>
                            <div className="ad-gap-top">
                                <AudiencePreview
                                    preview={preview}
                                    audienceType={a.audience_type}
                                    visibility={a.visibility}
                                    sendEmail={a.send_email}
                                />
                            </div>
                        </section>
                    )}

                    {sent && (
                        <section className="ad-card">
                            <h2 className="ad-card-title">
                                Recipients ({counts.total})
                            </h2>
                            <p className="ad-note" style={{ marginTop: 8 }}>
                                {counts.read} read it on the site. Emails are
                                sent by the queue, so a row can stay Queued for
                                a moment.
                            </p>

                            <div className="ad-chips">
                                <Link
                                    href={filterUrl(null)}
                                    className="ad-btn ad-btn-sm"
                                    aria-current={filter === null}
                                    preserveScroll
                                >
                                    All
                                </Link>
                                {FILTERS.map((status) => (
                                    <Link
                                        key={status}
                                        href={filterUrl(status)}
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
                                            post(
                                                `/admin/announcements/${a.id}/resend-failed`,
                                            )
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
                                        '96px 1fr 1.4fr 110px 96px 1fr 84px',
                                }}
                            >
                                <div className="ad-tr ad-th">
                                    <span>Student ID</span>
                                    <span>Name</span>
                                    <span>Email</span>
                                    <span>Account</span>
                                    <span>Email status</span>
                                    <span>Read</span>
                                    <span>Action</span>
                                </div>

                                {recipients.data.length === 0 && (
                                    <p className="ad-empty">No one here.</p>
                                )}

                                {recipients.data.map((person) => (
                                    <div key={person.id} className="ad-tr">
                                        <span>{person.student_id ?? '—'}</span>
                                        <span>{person.name}</span>
                                        <span style={{ justifySelf: 'start' }}>
                                            {person.email ?? (
                                                <span className="ad-faint">
                                                    No email
                                                </span>
                                            )}
                                        </span>
                                        <span
                                            className={
                                                person.activated
                                                    ? undefined
                                                    : 'ad-faint'
                                            }
                                        >
                                            {person.activated
                                                ? 'Activated'
                                                : 'Not activated'}
                                        </span>
                                        <span style={{ justifySelf: 'start' }}>
                                            <span
                                                className={`ad-badge ad-badge-${person.status}`}
                                            >
                                                {STATUS_LABELS[person.status] ??
                                                    person.status}
                                            </span>
                                        </span>
                                        <span className="ad-faint">
                                            {person.read_at ?? 'Not yet'}
                                        </span>
                                        {person.email &&
                                        !['pending', 'queued'].includes(
                                            person.status,
                                        ) ? (
                                            <button
                                                type="button"
                                                className="ad-btn ad-btn-sm"
                                                onClick={() =>
                                                    post(
                                                        `/admin/announcements/${a.id}/recipients/${person.id}/resend`,
                                                    )
                                                }
                                            >
                                                {person.status === 'skipped'
                                                    ? 'Send'
                                                    : 'Resend'}
                                            </button>
                                        ) : (
                                            <span className="ad-faint">—</span>
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

                            {recipients.last_page > 1 && (
                                <div className="ad-foot">
                                    <span className="ad-faint">
                                        Showing {recipients.from} to{' '}
                                        {recipients.to} of {recipients.total}
                                    </span>
                                    <Pagination
                                        current={recipients.current_page}
                                        last={recipients.last_page}
                                        label="Recipient pages"
                                    />
                                </div>
                            )}
                        </section>
                    )}
                </div>
            </AdminLayout>
        </>
    );
}
