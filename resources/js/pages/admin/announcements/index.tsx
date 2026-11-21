import { Head, Link } from '@inertiajs/react';
import Pagination from '@/components/admin/pagination';
import AdminLayout from '@/layouts/admin-layout';
import type { Paginated } from '@/lib/admin';

type Row = {
    id: number;
    type: 'announcement' | 'event';
    title: string;
    audience: string;
    visibility: 'public' | 'selected';
    status: 'draft' | 'scheduled' | 'sent';
    date: string | null;
    counts: {
        recipients: number;
        sent: number;
        failed: number;
        no_email: number;
        read: number;
    };
};

const STATUS_LABELS = { draft: 'Draft', scheduled: 'Scheduled', sent: 'Sent' };

/**
 * Announcements history: everything posted or sent, newest first.
 */
export default function Announcements({
    flash,
    announcements,
}: {
    flash: string | null;
    announcements: Paginated<Row>;
}) {
    return (
        <>
            <Head title="Announcements" />

            <AdminLayout
                active="announcements"
                page="announcements"
                title="Announcements"
                subtitle="Events and announcements for alumni: what was posted, to whom, and the email status."
                actions={
                    <Link
                        href="/admin/announcements/create"
                        className="ad-btn ad-btn-primary"
                    >
                        New announcement
                    </Link>
                }
            >
                <div className="ad-stack">
                    {flash && (
                        <p className="ad-flash" role="status">
                            {flash}
                        </p>
                    )}

                    <section className="ad-card">
                        <h2 className="ad-card-title">History</h2>

                        <div
                            className="ad-table ad-gap-top ad-ann-table"
                            style={{
                                ['--cols' as string]:
                                    '1.8fr 1fr 96px 150px repeat(4, 58px)',
                            }}
                        >
                            <div className="ad-tr ad-th">
                                <span>Title</span>
                                <span>Audience</span>
                                <span>Status</span>
                                <span>Date</span>
                                <span title="Emails sent">Sent</span>
                                <span title="Emails that failed">Failed</span>
                                <span title="No email address: site only">
                                    No email
                                </span>
                                <span title="Opened on the site">Read</span>
                            </div>

                            {announcements.data.length === 0 && (
                                <p className="ad-empty">
                                    Nothing posted yet. Choose "New
                                    announcement" to post the first one.
                                </p>
                            )}

                            {announcements.data.map((row) => (
                                <div key={row.id} className="ad-tr">
                                    <span className="ad-ann-title">
                                        <Link
                                            href={`/admin/announcements/${row.id}`}
                                            className="ad-btn-link"
                                        >
                                            {row.title}
                                        </Link>
                                        <span className="ad-faint">
                                            {row.type === 'event'
                                                ? 'Event'
                                                : 'Announcement'}
                                        </span>
                                    </span>
                                    <span>
                                        {row.audience}
                                        {row.visibility === 'public' && (
                                            <span className="ad-faint">
                                                {' '}
                                                · Public
                                            </span>
                                        )}
                                    </span>
                                    <span>
                                        <span
                                            className={`ad-badge ad-badge-${row.status}`}
                                        >
                                            {STATUS_LABELS[row.status]}
                                        </span>
                                    </span>
                                    <span className="ad-faint">
                                        {row.date ?? '—'}
                                    </span>
                                    {row.status === 'sent' ? (
                                        <>
                                            <span data-label="Sent">
                                                {row.counts.sent}
                                            </span>
                                            <span
                                                data-label="Failed"
                                                className={
                                                    row.counts.failed > 0
                                                        ? 'ad-text-error'
                                                        : undefined
                                                }
                                            >
                                                {row.counts.failed}
                                            </span>
                                            <span data-label="No email">
                                                {row.counts.no_email}
                                            </span>
                                            <span data-label="Read">
                                                {row.counts.read}
                                            </span>
                                        </>
                                    ) : (
                                        <span className="ad-faint ad-ann-span">
                                            Not sent yet
                                        </span>
                                    )}
                                </div>
                            ))}
                        </div>

                        {announcements.last_page > 1 && (
                            <div className="ad-foot">
                                <span className="ad-faint">
                                    Showing {announcements.from} to{' '}
                                    {announcements.to} of {announcements.total}
                                </span>
                                <Pagination
                                    current={announcements.current_page}
                                    last={announcements.last_page}
                                    label="Announcement pages"
                                />
                            </div>
                        )}
                    </section>
                </div>
            </AdminLayout>
        </>
    );
}
