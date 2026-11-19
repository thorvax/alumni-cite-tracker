import { Head, Link } from '@inertiajs/react';
import Pagination from '@/components/admin/pagination';
import AdminLayout from '@/layouts/admin-layout';
import type { Paginated } from '@/lib/admin';

type Section = {
    key: string;
    title: string;
    rows: { label: string; value: string }[];
};

type Version = {
    id: number;
    number: number;
    kind: 'First answer' | 'Update';
    submitted: string | null;
    /** Questions whose answer changed compared with the version before. */
    changes: {
        section: string;
        label: string;
        before: string;
        after: string;
    }[];
    /** All answers in this version. */
    sections: Section[];
};

type Props = {
    graduate: {
        id: number;
        name: string | null;
        course: string | null;
        year: number | null;
        answered: string | null;
        updated: string | null;
    };
    versions: Paginated<Version>;
};

/** Graduate Details > Update History: every submission, newest first. */
export default function GraduateHistory({ graduate, versions }: Props) {
    return (
        <>
            <Head title={`Update History - ${graduate.name ?? ''}`} />

            <AdminLayout
                active="graduates"
                page="graduate-details"
                title="Update History"
                subtitle={`${graduate.name ?? '—'} · ${graduate.course ?? '—'} · Class of ${graduate.year ?? '—'}`}
                actions={
                    <Link href={`/graduates/${graduate.id}`} className="ad-btn">
                        ← Graduate Details
                    </Link>
                }
            >
                <p className="ad-note" style={{ margin: '0 0 14px' }}>
                    First answered: {graduate.answered ?? '—'}. Last updated:{' '}
                    {graduate.updated ?? 'not updated yet'}. Submissions made
                    before update history was added were not linked to
                    graduates, so the oldest versions may be missing.
                </p>

                {versions.total === 0 && (
                    <section className="ad-card">
                        <p className="ad-empty">
                            No submissions recorded for this graduate yet.
                        </p>
                    </section>
                )}

                <ol className="ad-history">
                    {versions.data.map((version) => (
                        <li key={version.id} className="ad-card">
                            <div className="ad-card-head">
                                <h2 className="ad-card-title">
                                    #{version.number} · {version.kind}
                                </h2>
                                <span className="ad-faint">
                                    {version.submitted ?? '—'}
                                </span>
                            </div>

                            {version.kind === 'Update' &&
                                version.changes.length === 0 && (
                                    <p className="ad-sub">
                                        No changes: the graduate confirmed their
                                        data.
                                    </p>
                                )}

                            {version.changes.length > 0 && (
                                <table className="ad-diff">
                                    <caption className="ad-sub">
                                        What changed ({version.changes.length})
                                    </caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Question</th>
                                            <th scope="col">Before</th>
                                            <th scope="col">After</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {version.changes.map((change) => (
                                            <tr
                                                key={`${change.section}-${change.label}`}
                                            >
                                                <th scope="row">
                                                    {change.label}
                                                    <span className="ad-faint">
                                                        {change.section}
                                                    </span>
                                                </th>
                                                <td>
                                                    <del>{change.before}</del>
                                                </td>
                                                <td>
                                                    <ins>{change.after}</ins>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}

                            <details className="ad-history-all">
                                <summary>
                                    View all answers in this version
                                </summary>
                                {version.sections.map((section) => (
                                    <div key={section.key}>
                                        <h3>{section.title}</h3>
                                        <dl className="ad-kv">
                                            {section.rows.map((row) => (
                                                <div key={row.label}>
                                                    <dt>{row.label}</dt>
                                                    <dd>{row.value}</dd>
                                                </div>
                                            ))}
                                        </dl>
                                    </div>
                                ))}
                            </details>
                        </li>
                    ))}
                </ol>

                {versions.last_page > 1 && (
                    <div className="ad-foot">
                        <span>
                            Showing {versions.from ?? 0} to {versions.to ?? 0}{' '}
                            of {versions.total}
                        </span>
                        <Pagination
                            current={versions.current_page}
                            last={versions.last_page}
                        />
                    </div>
                )}
            </AdminLayout>
        </>
    );
}
