import { Head, Link } from '@inertiajs/react';
import { Printer, User } from 'lucide-react';
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
        student_id: string | null;
        course_name: string | null;
        answered_at: string | null;
        updated_at: string | null;
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
                        <button
                            type="button"
                            className="ad-btn"
                            onClick={() => window.print()}
                        >
                            <Printer size={14} aria-hidden /> Print
                        </button>
                        <Link
                            href={`/graduates/${graduate.id}/edit`}
                            className="ad-btn ad-btn-solid"
                        >
                            Edit
                        </Link>
                    </>
                }
            >
                <PrintRecord graduate={graduate} sections={sections} />

                <div className="ad-split ad-screen-only">
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

/**
 * The whole record, all sections at once. Hidden on screen; this is what
 * the Print button prints (see @media print in admin.css).
 */
function PrintRecord({ graduate, sections }: Props) {
    const details: [string, string | number | null][] = [
        ['Name', graduate.name],
        ['Student ID Number', graduate.student_id],
        ['Course', graduate.course_name],
        ['Year Graduated', graduate.year],
        ['Survey answered', graduate.answered_at],
        ['Last updated', graduate.updated_at],
    ];

    return (
        <article className="ad-print-record">
            <header className="ad-print-head">
                <p>
                    College of Information and Technology Education, Urdaneta
                    City University
                </p>
                <h2>CITE Graduate Tracer Study: Graduate Record</h2>
                <dl className="ad-kv">
                    {details.map(([label, value]) => (
                        <div key={label}>
                            <dt>{label}</dt>
                            <dd>{value ?? '—'}</dd>
                        </div>
                    ))}
                </dl>
            </header>

            {sections.map((section) => (
                <section key={section.key} className="ad-print-section">
                    <h3>{section.title}</h3>
                    <dl className="ad-kv">
                        {section.rows.map((row) => (
                            <div key={row.label}>
                                <dt>{row.label}</dt>
                                <dd>{row.value}</dd>
                            </div>
                        ))}
                    </dl>
                </section>
            ))}

            <footer className="ad-print-foot">
                Printed {new Date().toLocaleDateString()}. Confidential:
                contains personal data protected under the Data Privacy Act of
                2012 (RA 10173).
            </footer>
        </article>
    );
}
