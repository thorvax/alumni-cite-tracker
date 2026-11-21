import { Head, useForm } from '@inertiajs/react';
import AnnouncementForm, {
    type AnnouncementAction,
    type AnnouncementData,
    emptyAnnouncement,
} from '@/components/admin/announcement-form';
import AdminLayout from '@/layouts/admin-layout';

export default function CreateAnnouncement({
    firstYear,
    lastYear,
}: {
    firstYear: number;
    lastYear: number;
}) {
    const form = useForm<AnnouncementData>(emptyAnnouncement);

    const submit = (action: AnnouncementAction) => {
        form.transform((data) => ({ ...data, action }));
        form.post('/admin/announcements', { preserveScroll: true });
    };

    return (
        <>
            <Head title="New Announcement" />

            <AdminLayout
                active="announcements"
                page="announcements"
                title="New Event or Announcement"
                subtitle="Post it on the site and, if you like, email it to the alumni you choose."
            >
                <div style={{ maxWidth: 900 }}>
                    <AnnouncementForm
                        data={form.data}
                        errors={form.errors}
                        processing={form.processing}
                        firstYear={firstYear}
                        lastYear={lastYear}
                        onChange={(patch) =>
                            form.setData((prev) => ({ ...prev, ...patch }))
                        }
                        onSubmit={submit}
                        cancelHref="/admin/announcements"
                    />
                </div>
            </AdminLayout>
        </>
    );
}
