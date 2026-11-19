import { Head, router } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import AdminLayout from '@/layouts/admin-layout';

type Stat = { count: number; percent: number };

type Props = {
    year: string;
    years: number[];
    employment: {
        total: number;
        employed: Stat;
        unemployed: Stat;
        further: Stat;
    };
    sectors: { label: string; percent: number }[];
};

const RADIUS = 54;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

export default function Reports({ year, years, employment, sectors }: Props) {
    const [selected, setSelected] = useState(year);

    const generate = (e: FormEvent) => {
        e.preventDefault();
        router.get('/reports', selected ? { year: selected } : {}, {
            preserveState: true,
        });
    };

    const segments = [
        {
            label: 'Employed',
            color: 'var(--ad-c-employed)',
            ...employment.employed,
        },
        {
            label: 'Unemployed',
            color: 'var(--ad-c-unemployed)',
            ...employment.unemployed,
        },
        {
            label: 'Further Studies',
            color: 'var(--ad-c-further)',
            ...employment.further,
        },
    ];

    const counted = segments.reduce((sum, s) => sum + s.count, 0);
    let offset = 0;

    return (
        <>
            <Head title="Reports" />

            <AdminLayout
                active="reports"
                page="reports"
                title="Reports"
                subtitle="Generate reports and statistics."
                actions={
                    <form className="ad-year" onSubmit={generate}>
                        <label htmlFor="year">Year</label>
                        <select
                            id="year"
                            className="ad-select"
                            value={selected}
                            onChange={(e) => setSelected(e.target.value)}
                        >
                            {years.length === 0 && <option value="">—</option>}
                            {years.map((y) => (
                                <option key={y} value={String(y)}>
                                    {y}
                                </option>
                            ))}
                        </select>
                        <button
                            type="submit"
                            className="ad-btn ad-btn-solid"
                            style={{ height: 36, padding: '0 22px' }}
                        >
                            Generate
                        </button>
                    </form>
                }
            >
                <div className="ad-reports">
                    <section className="ad-card ad-report-card">
                        <h2 className="ad-card-title">Employment Status</h2>

                        <div className="ad-donut">
                            <div className="ad-donut-wrap">
                                <svg
                                    viewBox="0 0 142 142"
                                    width="142"
                                    height="142"
                                    role="img"
                                    aria-label="Employment status chart"
                                >
                                    <circle
                                        cx="71"
                                        cy="71"
                                        r={RADIUS}
                                        fill="none"
                                        stroke="var(--ad-bar-track)"
                                        strokeWidth="24"
                                        opacity={counted === 0 ? 0.5 : 0}
                                    />
                                    {counted > 0 &&
                                        segments.map((segment) => {
                                            const length =
                                                (segment.count / counted) *
                                                CIRCUMFERENCE;
                                            const circle = (
                                                <circle
                                                    key={segment.label}
                                                    cx="71"
                                                    cy="71"
                                                    r={RADIUS}
                                                    fill="none"
                                                    stroke={segment.color}
                                                    strokeWidth="24"
                                                    strokeDasharray={`${Math.max(length - 2, 0)} ${CIRCUMFERENCE}`}
                                                    strokeDashoffset={-offset}
                                                    transform="rotate(-90 71 71)"
                                                />
                                            );

                                            offset += length;

                                            return circle;
                                        })}
                                </svg>
                                <div className="ad-donut-center">
                                    <span className="ad-donut-total">
                                        {employment.total}
                                    </span>
                                    <span className="ad-donut-label">
                                        Graduates
                                    </span>
                                </div>
                            </div>

                            <ul className="ad-legend">
                                {segments.map((segment) => (
                                    <li key={segment.label}>
                                        <i
                                            style={{
                                                background: segment.color,
                                            }}
                                        />
                                        <span>{segment.label}</span>
                                        <span className="ad-faint">
                                            {segment.percent}%
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </section>

                    <section className="ad-card ad-report-card">
                        <h2 className="ad-card-title">
                            Top Employment Sectors
                        </h2>

                        <div className="ad-bars">
                            {sectors.length === 0 && (
                                <p
                                    className="ad-empty"
                                    style={{ padding: 0, textAlign: 'left' }}
                                >
                                    No sector data yet.
                                </p>
                            )}

                            {sectors.map((sector) => (
                                <div key={sector.label} className="ad-bar-row">
                                    <div className="ad-bar-head">
                                        <span>{sector.label}</span>
                                        <span className="ad-faint">
                                            {sector.percent}%
                                        </span>
                                    </div>
                                    <div className="ad-bar">
                                        <i
                                            style={{
                                                width: `${sector.percent}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </AdminLayout>
        </>
    );
}
