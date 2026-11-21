import { Head } from '@inertiajs/react';
import AnnouncementDetail from '@/components/announcements/announcement-detail';
import type { ReaderAnnouncement } from '@/components/announcements/announcement-list';
import AlumniLayout from '@/layouts/alumni-layout';

export default function AlumniAnnouncement({
    announcement,
}: {
    announcement: ReaderAnnouncement;
}) {
    return (
        <>
            <Head title={announcement.title} />

            <AlumniLayout wide title={announcement.title}>
                <AnnouncementDetail
                    announcement={announcement}
                    backHref="/alumni/announcements"
                    backLabel="All announcements"
                />
            </AlumniLayout>
        </>
    );
}
