import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import GraduateForm, {
    type GraduateData,
    emptyGraduate,
} from '@/components/admin/graduate-form';
import AdminLayout from '@/layouts/admin-layout';

export default function CreateGraduate() {
    const { data, setData, post, processing, errors } =
        useForm<GraduateData>(emptyGraduate);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/graduates');
    };

    return (
        <>
            <Head title="Add Graduate" />

            <AdminLayout
                active="graduates"
                page="graduate-form"
                title="Add Graduate"
                subtitle="Encode the graduate's information."
            >
                <section
                    className="ad-card"
                    style={{ maxWidth: 1060, margin: '34px auto 0' }}
                >
                    <GraduateForm
                        data={data}
                        errors={errors}
                        processing={processing}
                        onChange={(patch) =>
                            setData((prev) => ({ ...prev, ...patch }))
                        }
                        onSubmit={submit}
                        cancelHref="/graduates"
                        submitLabel="Save"
                        showInviteOption
                    />
                </section>
            </AdminLayout>
        </>
    );
}
