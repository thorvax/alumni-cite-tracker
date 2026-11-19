import { Head, Link } from '@inertiajs/react';
import { User } from 'lucide-react';
import { useState } from 'react';
import AdminLayout from '@/layouts/admin-layout';

type Section = {
    key: string;
    title: string;
    rows: { label: string; value: string }[];
};

type Props = {
    graduate: {
        id: number;
        name: string;
        course: string | null;
        year: number | null;
    };
    sections: Section[];
};

export default function ShowGraduate({ graduate, sections }: Props) {
    const [activeKey, setActiveKey] = useState(sections[0]?.key ?? 'personal');
    const active = sections.find((s) => s.key === activeKey) ?? sections[0];

    return (
        <>
            <Head title={graduate.name} />

            <AdminLayout
                active="graduates"
                page="graduate-details"
                title="Graduate Details"
                subtitle="View complete information of the graduate."
                actions={
                    <>
                        <Link href="/graduates" className="ad-btn">
                            ← Back
                        </Link>
                        <Link
                            href={`/graduates/${graduate.id}/edit`}
                            className="ad-btn ad-btn-solid"
                        >
                            Edit
                        </Link>
                    </>
                }
            >
                <div className="ad-split">
                    <div>
                        <section className="ad-card ad-profile">
                            <div className="ad-avatar">
                                <User size={26} aria-hidden />
                            </div>
                            <div className="ad-profile-name">
                                {graduate.name}
                            </div>
                            <div className="ad-profile-meta">
                                {graduate.course ?? '—'} | Class of{' '}
                                {graduate.year ?? '—'}
                            </div>
                        </section>

                        <ul className="ad-tabs" aria-label="Sections">
                            {sections.map((section) => (
                                <li key={section.key}>
                                    <button
                                        type="button"
                                        aria-current={
                                            section.key === active?.key
                                        }
                                        onClick={() =>
                                            setActiveKey(section.key)
                                        }
                                    >
                                        {section.title}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {active && (
                        <section className="ad-card" style={{ padding: 22 }}>
                            <div className="ad-card-head">
                                <h2 className="ad-card-title">
                                    {active.title}
                                </h2>
                                {active.key === 'personal' && (
                                    <Link
                                        href={`/graduates/${graduate.id}/edit`}
                                        className="ad-btn ad-btn-sm"
                                    >
                                        Edit
                                    </Link>
                                )}
                            </div>

                            <dl className="ad-kv">
                                {active.rows.map((row) => (
                                    <div key={row.label}>
                                        <dt>{row.label}</dt>
                                        <dd>{row.value}</dd>
                                    </div>
                                ))}
                            </dl>
                        </section>
                    )}
                </div>
            </AdminLayout>
        </>
    );
}
