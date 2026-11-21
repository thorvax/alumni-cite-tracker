import { Head, Link, router } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import { AdInput, AdSelect } from '@/components/admin/controls';
import Pagination from '@/components/admin/pagination';
import AdminLayout from '@/layouts/admin-layout';
import type { GraduateRow, Paginated } from '@/lib/admin';

type Props = {
    results: Paginated<GraduateRow>;
    filters: { q: string; year: string; status: string };
    years: number[];
    statuses: string[];
};

export default function SearchGraduates({
    results,
    filters,
    years,
    statuses,
}: Props) {
    const [q, setQ] = useState(filters.q);
    const [year, setYear] = useState(filters.year);
    const [status, setStatus] = useState(filters.status);

    const submit = (e: FormEvent) => {
        e.preventDefault();

        router.get(
            '/search',
            {
                ...(q ? { q } : {}),
                ...(year ? { year } : {}),
                ...(status ? { status } : {}),
            },
            { preserveState: true },
        );
    };

    const reset = () => {
        setQ('');
        setYear('');
        setStatus('');
        router.get('/search');
    };

    return (
        <>
            <Head title="Search Graduates" />

            <AdminLayout
                active="graduates"
                page="search"
                title="Search Graduates"
                subtitle="Find graduate records quickly."
            >
                <form
                    className="ad-card"
                    style={{ marginTop: 26, padding: 22 }}
                    onSubmit={submit}
                    role="search"
                >
                    <AdInput
                        type="search"
                        aria-label="Search by name or course"
                        placeholder="Search name, student ID, course, year, or status..."
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                    />

                    <div className="ad-grid-2" style={{ marginTop: 14 }}>
                        <div>
                            <label htmlFor="year" className="ad-label">
                                Year Graduated
                            </label>
                            <AdSelect
                                id="year"
                                placeholder="All Years"
                                options={years.map(String)}
                                value={year}
                                onChange={(e) => setYear(e.target.value)}
                            />
                        </div>
                        <div>
                            <label htmlFor="status" className="ad-label">
                                Employment Status
                            </label>
                            <AdSelect
                                id="status"
                                placeholder="All Statuses"
                                options={statuses}
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                            />
                        </div>
                    </div>

                    <div style={{ display: 'flex', gap: 10, marginTop: 16 }}>
                        <button type="submit" className="ad-btn ad-btn-solid">
                            Search
                        </button>
                        <button
                            type="button"
                            className="ad-btn"
                            onClick={reset}
                        >
                            Reset
                        </button>
                    </div>
                </form>

                <section
                    className="ad-card"
                    style={{ marginTop: 20, padding: 4 }}
                >
                    <div
                        className="ad-table"
                        style={{
                            ['--cols' as string]:
                                '56px 1.4fr 1fr 0.8fr 1.4fr 70px',
                            padding: '8px 4px 0',
                        }}
                    >
                        <div className="ad-tr ad-th">
                            <span>#</span>
                            <span>Name</span>
                            <span>Course</span>
                            <span>Year</span>
                            <span>Employment Status</span>
                            <span>Action</span>
                        </div>

                        {results.data.length === 0 && (
                            <p className="ad-empty">
                                No graduates match your search.
                            </p>
                        )}

                        {results.data.map((graduate, i) => (
                            <div key={graduate.id} className="ad-tr">
                                <span>{(results.from ?? 1) + i}</span>
                                <span>{graduate.name}</span>
                                <span>{graduate.course ?? '—'}</span>
                                <span>{graduate.year ?? '—'}</span>
                                <span>{graduate.status ?? '—'}</span>
                                <Link
                                    href={`/graduates/${graduate.id}`}
                                    className="ad-btn ad-btn-sm"
                                >
                                    View
                                </Link>
                            </div>
                        ))}
                    </div>

                    {results.last_page > 1 && (
                        <div className="ad-foot">
                            <span>
                                Showing {results.from ?? 0} to {results.to ?? 0}{' '}
                                of {results.total} records
                            </span>
                            <Pagination
                                current={results.current_page}
                                last={results.last_page}
                            />
                        </div>
                    )}
                </section>
            </AdminLayout>
        </>
    );
}
