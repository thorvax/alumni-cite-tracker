import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import GraduateForm, {
    type GraduateData,
} from '@/components/admin/graduate-form';
import AdminLayout from '@/layouts/admin-layout';

type Props = {
    graduate: { id: number } & {
        [K in Exclude<keyof GraduateData, 'send_invite'>]: string | null;
    };
    /** Already has a personal survey link. */
    hasLink: boolean;
};

const tabs = [
    'Personal Information',
    'Educational Background',
    'Employment Information',
    'Career Development',
    'Further Studies',
    'Skills and Competencies',
    'Feedback and Suggestions',
];

export default function EditGraduate({ graduate, hasLink }: Props) {
    const { data, setData, patch, processing, errors } = useForm<GraduateData>({
        full_name: graduate.full_name ?? '',
        birth_date: graduate.birth_date ?? '',
        gender: graduate.gender ?? '',
        civil_status: graduate.civil_status ?? '',
        email: graduate.email ?? '',
        contact_number: graduate.contact_number ?? '',
        current_address: graduate.current_address ?? '',
        region: graduate.region ?? '',
        province: graduate.province ?? '',
        city: graduate.city ?? '',
        student_id: graduate.student_id ?? '',
        last_name: graduate.last_name ?? '',
        course: graduate.course ?? '',
        year_graduated: graduate.year_graduated ?? '',
        employment_status: graduate.employment_status ?? '',
        send_invite: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        patch(`/graduates/${graduate.id}`);
    };

    return (
        <>
            <Head title="Edit Graduate" />

            <AdminLayout
                active="graduates"
                page="graduate-form"
                title="Edit Graduate"
                subtitle="Update the graduate's information."
                actions={
                    <Link href={`/graduates/${graduate.id}`} className="ad-btn">
                        ← Back
                    </Link>
                }
            >
                <div className="ad-split">
                    <section
                        className="ad-card"
                        style={{ padding: 0, overflow: 'hidden' }}
                    >
                        <ul className="ad-tabs" style={{ margin: 0 }}>
                            {tabs.map((tab, i) => (
                                <li key={tab}>
                                    <button
                                        type="button"
                                        aria-current={i === 0}
                                        disabled={i !== 0}
                                        title={
                                            i === 0
                                                ? undefined
                                                : 'Only personal information can be edited for now'
                                        }
                                        style={
                                            i !== 0
                                                ? {
                                                      cursor: 'not-allowed',
                                                      opacity: 0.8,
                                                  }
                                                : undefined
                                        }
                                    >
                                        {tab}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section className="ad-card" style={{ padding: 26 }}>
                        <GraduateForm
                            data={data}
                            errors={errors}
                            processing={processing}
                            onChange={(p) =>
                                setData((prev) => ({ ...prev, ...p }))
                            }
                            onSubmit={submit}
                            cancelHref={`/graduates/${graduate.id}`}
                            submitLabel="Update"
                            hasLink={hasLink}
                        />
                    </section>
                </div>
            </AdminLayout>
        </>
    );
}
