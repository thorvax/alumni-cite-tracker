import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import Pagination from '@/components/admin/pagination';
import AdminLayout from '@/layouts/admin-layout';
import type { GraduateRow, Paginated } from '@/lib/admin';

type Props = {
    graduates: Paginated<GraduateRow>;
    filters: { q: string; per_page: number };
    perPageOptions: number[];
};

export default function GraduatesIndex({
    graduates,
    filters,
    perPageOptions,
}: Props) {
    const [q, setQ] = useState(filters.q);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            '/graduates',
            { ...(q ? { q } : {}), per_page: filters.per_page },
            { preserveState: true },
        );
    };

    // A new page size starts again at page 1.
    const changePerPage = (perPage: string) => {
        router.get(
            '/graduates',
            { ...(filters.q ? { q: filters.q } : {}), per_page: perPage },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Graduates" />

            <AdminLayout
                active="graduates"
                page="graduates"
                title="Graduates"
                subtitle="View and manage graduate records."
            >
                <form className="ad-searchbar" onSubmit={submit} role="search">
                    <div className="ad-search">
                        <Search size={15} aria-hidden />
                        <input
                            className="ad-input"
                            type="search"
                            aria-label="Search graduates"
                            placeholder="Search name, course, year, or status..."
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                        />
                    </div>
                    <Link
                        href={
                            q ? `/search?q=${encodeURIComponent(q)}` : '/search'
                        }
                        className="ad-btn ad-btn-link"
                    >
                        Filter
                    </Link>
                    <Link href="/graduates/import" className="ad-btn">
                        Bulk Upload
                    </Link>
                    <Link href="/graduates/create" className="ad-btn">
                        Add Graduate
                    </Link>
                </form>

                <section className="ad-card" style={{ padding: 4 }}>
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

                        {graduates.data.length === 0 && (
                            <p className="ad-empty">No graduates found.</p>
                        )}

                        {graduates.data.map((graduate, i) => (
                            <div key={graduate.id} className="ad-tr">
                                <span className="ad-faint">
                                    {(graduates.from ?? 1) + i}
                                </span>
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

                    <div className="ad-foot">
                        <div className="ad-foot-info">
                            <label className="ad-perpage">
                                Show
                                <select
                                    className="ad-select"
                                    value={filters.per_page}
                                    onChange={(e) =>
                                        changePerPage(e.target.value)
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
                                {graduates.to ?? 0} of {graduates.total} records
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
