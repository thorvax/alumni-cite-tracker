import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';
import type { GraduateRow } from '@/lib/admin';

type Stat = { count: number; percent: number };

type Props = {
    today: string;
    stats: {
        total: number;
        employed: Stat;
        unemployed: Stat;
        further: Stat;
    };
    recent: GraduateRow[];
};

export default function Dashboard({ today, stats, recent }: Props) {
    const cards = [
        { label: 'Total Graduates', value: stats.total },
        {
            label: 'Employed',
            value: stats.employed.count,
            percent: stats.employed.percent,
        },
        {
            label: 'Unemployed',
            value: stats.unemployed.count,
            percent: stats.unemployed.percent,
        },
        {
            label: 'Further Studies',
            value: stats.further.count,
            percent: stats.further.percent,
        },
    ];

    return (
        <>
            <Head title="Dashboard" />

            <AdminLayout
                active="dashboard"
                page="dashboard"
                title="Dashboard"
                subtitle="Track. Connect. Empower."
                actions={<span className="ad-date">{today}</span>}
            >
                <div className="ad-stats">
                    {cards.map((card) => (
                        <div key={card.label} className="ad-card ad-stat">
                            <span className="ad-stat-label">{card.label}</span>
                            <span className="ad-stat-value">{card.value}</span>
                            {card.percent !== undefined && (
                                <span className="ad-stat-pct">
                                    {card.percent}%
                                </span>
                            )}
                        </div>
                    ))}
                </div>

                <section
                    className="ad-card"
                    style={{ marginTop: 22, padding: 18 }}
                >
                    <div
                        className="ad-card-head"
                        style={{ padding: '4px 4px 6px' }}
                    >
                        <h2
                            className="ad-card-title"
                            style={{ fontSize: 20, color: 'inherit' }}
                        >
                            Recent Graduates
                        </h2>
                        <Link href="/graduates" className="ad-btn">
                            View All
                        </Link>
                    </div>

                    <div
                        className="ad-table"
                        style={{
                            ['--cols' as string]: '48px 1.3fr 1fr 1fr 64px',
                        }}
                    >
                        <div className="ad-tr ad-th">
                            <span>#</span>
                            <span>Name</span>
                            <span>Year Graduated</span>
                            <span>Employment Status</span>
                            <span>Action</span>
                        </div>

                        {recent.length === 0 && (
                            <p className="ad-empty">No graduates yet.</p>
                        )}

                        {recent.map((graduate, i) => (
                            <div key={graduate.id} className="ad-tr">
                                <span>{i + 1}</span>
                                <span>{graduate.name}</span>
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
                </section>
            </AdminLayout>
        </>
    );
}
