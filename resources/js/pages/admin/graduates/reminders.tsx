import { Head, Link, router } from '@inertiajs/react';
import Pagination from '@/components/admin/pagination';
import WhoToRemind, {
    type ReminderTarget,
} from '@/components/admin/who-to-remind';
import AdminLayout from '@/layouts/admin-layout';
import type { Paginated } from '@/lib/admin';

type ListKey = 'due' | 'manual' | 'sent';

type Row = {
    id: number;
    name: string | null;
    course: string | null;
    year: number | null;
    email: string | null;
    lastAnswer: string | null;
    reminded: string | null;
    activated: boolean;
};

type Filters = {
    list: ListKey;
    target: ReminderTarget;
    stale_months: number;
    per_page: number;
};

type Props = {
    graduates: Paginated<Row>;
    filters: Filters;
    counts: Record<ListKey, number>;
    automatic: {
        enabled: boolean;
        months: number;
        target: ReminderTarget;
        staleMonths: number;
    };
    monthOptions: number[];
    perPageOptions: number[];
};

const LIST_LABELS: Record<ListKey, string> = {
    due: 'Next automatic run',
    manual: 'Send now preview',
    sent: 'Already reminded',
};

/** /graduates/reminders?list=...&target=...&stale_months=...&per_page=... (page 1) */
const listUrl = (filters: Filters) =>
    `/graduates/reminders?${new URLSearchParams({
        list: filters.list,
        target: filters.target,
        stale_months: String(filters.stale_months),
        per_page: String(filters.per_page),
    }).toString()}`;

function Explanation({
    filters,
    automatic,
}: Pick<Props, 'filters' | 'automatic'>) {
    if (filters.list === 'due') {
        const who =
            automatic.target === 'all'
                ? 'all alumni who answered the survey'
                : `alumni who have not updated in the past ${automatic.staleMonths} months`;

        return (
            <p className="ad-note">
                {automatic.enabled
                    ? `Automatic reminders are on: every ${automatic.months} months to ${who}. These alumni will be emailed at the next daily run.`
                    : `Automatic reminders are off, so nobody is emailed automatically. With the saved settings (every ${automatic.months} months, ${who}) these alumni would be emailed.`}{' '}
                <Link href="/settings/system">Change in Settings</Link>.
            </p>
        );
    }

    if (filters.list === 'manual') {
        return (
            <p className="ad-note">
                Who &quot;Send Update Reminders Now&quot; on{' '}
                <Link href="/settings/system">Settings</Link> would email with
                this choice.
            </p>
        );
    }

    return (
        <p className="ad-note">
            Everyone who has been sent an update reminder (automatic or by
            hand), newest first.
        </p>
    );
}

export default function GraduateReminders({
    graduates,
    filters,
    counts,
    automatic,
    monthOptions,
    perPageOptions,
}: Props) {
    const go = (patch: Partial<Filters>) =>
        router.get(
            listUrl({ ...filters, ...patch }),
            {},
            { preserveScroll: true },
        );

    return (
        <>
            <Head title="Reminder List" />

            <AdminLayout
                active="graduates"
                page="graduates"
                title="Reminder List"
                subtitle="Alumni who are getting “please update your answers” emails."
                actions={
                    <>
                        <Link href="/graduates/updates" className="ad-btn">
                            Update Status
                        </Link>
                        <Link href="/settings/system" className="ad-btn">
                            Reminder settings
                        </Link>
                    </>
                }
            >
                <div className="ad-chips" aria-label="Which list">
                    {(Object.keys(LIST_LABELS) as ListKey[]).map((key) => (
                        <Link
                            key={key}
                            href={listUrl({ ...filters, list: key })}
                            className="ad-btn ad-btn-sm"
                            aria-current={filters.list === key}
                            preserveScroll
                        >
                            {LIST_LABELS[key]} ({counts[key]})
                        </Link>
                    ))}
                </div>

                <Explanation filters={filters} automatic={automatic} />

                {filters.list === 'manual' && (
                    <section className="ad-card" style={{ margin: '12px 0' }}>
                        <WhoToRemind
                            id="preview"
                            target={filters.target}
                            staleMonths={filters.stale_months}
                            monthOptions={monthOptions}
                            onChange={(patch) => go(patch)}
                        />
                    </section>
                )}

                <section
                    className="ad-card"
                    style={{ padding: 4, marginTop: 12 }}
                >
                    <div
                        className="ad-table"
                        style={{
                            ['--cols' as string]:
                                '1.3fr 1.4fr 0.9fr 1.1fr 0.9fr 70px',
                            padding: '8px 4px 0',
                        }}
                    >
                        <div className="ad-tr ad-th">
                            <span>Graduate</span>
                            <span>Email</span>
                            <span>Last answered</span>
                            <span>Last reminder</span>
                            <span>Account</span>
                            <span>Action</span>
                        </div>

                        {graduates.data.length === 0 && (
                            <p className="ad-empty">Nobody in this list.</p>
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
                                <span>{row.email ?? '—'}</span>
                                <span>{row.lastAnswer ?? '—'}</span>
                                <span>{row.reminded ?? '—'}</span>
                                <span
                                    className={
                                        row.activated ? undefined : 'ad-faint'
                                    }
                                    title={
                                        row.activated
                                            ? undefined
                                            : 'Their reminder also includes their activation link'
                                    }
                                >
                                    {row.activated
                                        ? 'Activated'
                                        : 'Not activated'}
                                </span>
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
                                        go({ per_page: Number(e.target.value) })
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
