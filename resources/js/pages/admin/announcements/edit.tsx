import { Head, useForm } from '@inertiajs/react';
import AnnouncementForm, {
    type AnnouncementAction,
    type AnnouncementData,
} from '@/components/admin/announcement-form';
import AdminLayout from '@/layouts/admin-layout';

export default function EditAnnouncement({
    announcement,
    firstYear,
    lastYear,
}: {
    announcement: AnnouncementData & { id: number; status: string };
    firstYear: number;
    lastYear: number;
}) {
    const { id, status, ...initial } = announcement;
    const form = useForm<AnnouncementData>(initial);
    const locked = status === 'sent';

    const submit = (action: AnnouncementAction) => {
        form.transform((data) => ({ ...data, action }));
        form.put(`/admin/announcements/${id}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Edit Announcement" />

            <AdminLayout
                active="announcements"
                page="announcements"
                title={locked ? 'Edit Sent Announcement' : 'Edit Announcement'}
                subtitle={announcement.title}
            >
                <div style={{ maxWidth: 900 }}>
                    <AnnouncementForm
                        data={form.data}
                        errors={form.errors}
                        processing={form.processing}
                        locked={locked}
                        firstYear={firstYear}
                        lastYear={lastYear}
                        onChange={(patch) =>
                            form.setData((prev) => ({ ...prev, ...patch }))
                        }
                        onSubmit={submit}
                        cancelHref={`/admin/announcements/${id}`}
                    />
                </div>
            </AdminLayout>
        </>
    );
}
