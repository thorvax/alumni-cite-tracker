import { Head, Link, router } from '@inertiajs/react';
import Pagination from '@/components/admin/pagination';
import AdminLayout from '@/layouts/admin-layout';
import type { Paginated } from '@/lib/admin';

type Row = {
    id: number;
    name: string | null;
    course: string | null;
    year: number | null;
    answered: string | null;
    updated: string | null;
    reminded: string | null;
    status: 'up_to_date' | 'needs_update';
    /** null = not updated yet; [] = updated with no changes. */
    changes: string[] | null;
};

type Filter =
    | 'up_to_date'
    | 'needs_update'
    | 'changed'
    | 'no_changes'
    | 'never_updated';

type Props = {
    graduates: Paginated<Row>;
    filters: { status: Filter | ''; per_page: number };
    perPageOptions: number[];
    months: number;
    counts: Record<Filter | 'all', number>;
};

const FILTER_LABELS: Record<Filter, string> = {
    up_to_date: 'Up to date',
    needs_update: 'Needs update',
    changed: 'Updated with changes',
    no_changes: 'Updated, no changes',
    never_updated: 'Not updated yet',
};

/** Builds /graduates/updates?status=...&per_page=... (page 1). */
const listUrl = (status: string, perPage: number) => {
    const params = new URLSearchParams();

    if (status) {
        params.set('status', status);
    }

    params.set('per_page', String(perPage));

    return `/graduates/updates?${params.toString()}`;
};

function Changes({ row }: { row: Row }) {
    if (row.updated === null) {
        return <span className="ad-faint">Not updated yet</span>;
    }

    if (row.changes === null) {
        return <span className="ad-faint">Updated (changes not recorded)</span>;
    }

    if (row.changes.length === 0) {
        return <span>Updated, no changes</span>;
    }

    return <span>Changed: {row.changes.join(', ')}</span>;
}

export default function GraduateUpdates({
    graduates,
    filters,
    perPageOptions,
    months,
    counts,
}: Props) {
    return (
        <>
            <Head title="Update Status" />

            <AdminLayout
                active="graduates"
                page="graduates"
                title="Update Status"
                subtitle={`Who has kept their survey answers up to date (within the last ${months} months) and what changed.`}
                actions={
                    <>
                        <Link href="/graduates" className="ad-btn">
                            ← Graduates
                        </Link>
                        <Link href="/settings/system" className="ad-btn">
                            Reminder settings
                        </Link>
                    </>
                }
            >
                <div className="ad-chips" aria-label="Filter by status">
                    <Link
                        href={listUrl('', filters.per_page)}
                        className="ad-btn ad-btn-sm"
                        aria-current={filters.status === ''}
                        preserveScroll
                    >
                        All ({counts.all})
                    </Link>
                    {(Object.keys(FILTER_LABELS) as Filter[]).map((status) => (
                        <Link
                            key={status}
                            href={listUrl(status, filters.per_page)}
                            className="ad-btn ad-btn-sm"
                            aria-current={filters.status === status}
                            preserveScroll
                        >
                            {FILTER_LABELS[status]} ({counts[status]})
                        </Link>
                    ))}
                </div>

                <section className="ad-card" style={{ padding: 4 }}>
                    <div
                        className="ad-table"
                        style={{
                            ['--cols' as string]:
                                '1.3fr 0.8fr 0.8fr 0.8fr 0.9fr 1.6fr 70px',
                            padding: '8px 4px 0',
                        }}
                    >
                        <div className="ad-tr ad-th">
                            <span>Graduate</span>
                            <span>First answered</span>
                            <span>Last updated</span>
                            <span>Last reminder</span>
                            <span>Status</span>
                            <span>Changes in last update</span>
                            <span>Action</span>
                        </div>

                        {graduates.data.length === 0 && (
                            <p className="ad-empty">No graduates found.</p>
                        )}

                        {graduates.data.map((row) => (
                            <div key={row.id} className="ad-tr">
                                <span>
                                    {row.name ?? '—'}
                                    <br />
                                    <span className="ad-faint">
                                        {row.course ?? '—'} · {row.year ?? '—'}
                                    </span>
                                </span>
                                <span>{row.answered ?? '—'}</span>
                                <span>{row.updated ?? '—'}</span>
                                <span>{row.reminded ?? '—'}</span>
                                <span>
                                    <span
                                        className={`ad-badge ad-badge-${row.status}`}
                                    >
                                        {FILTER_LABELS[row.status]}
                                    </span>
                                </span>
                                <Changes row={row} />
                                <Link
                                    href={`/graduates/${row.id}`}
                                    className="ad-btn ad-btn-sm"
                                >
                                    View
                                </Link>
                            </div>
                        ))}
                    </div>

                    <div className="ad-foot">
                        <div className="ad-foot-info">
                            <label className="ad-perpage">
                                Show
                                <select
                                    className="ad-select"
                                    value={filters.per_page}
                                    onChange={(e) =>
                                        router.get(
                                            listUrl(
                                                filters.status,
                                                Number(e.target.value),
                                            ),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {perPageOptions.map((option) => (
                                        <option key={option} value={option}>
                                            {option}
                                        </option>
                                    ))}
                                </select>
                                per page
                            </label>
                            <span>
                                Showing {graduates.from ?? 0} to{' '}
                                {graduates.to ?? 0} of {graduates.total}
                            </span>
                        </div>
                        {graduates.last_page > 1 && (
                            <Pagination
                                current={graduates.current_page}
                                last={graduates.last_page}
                            />
                        )}
                    </div>
                </section>
            </AdminLayout>
        </>
    );
}
