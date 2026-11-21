import { Head } from '@inertiajs/react';
import AnnouncementList, {
    type ReaderAnnouncement,
} from '@/components/announcements/announcement-list';
import AlumniLayout from '@/layouts/alumni-layout';
import type { Paginated } from '@/lib/admin';

/** Logged-in alumni: public announcements plus the ones sent to them. */
export default function AlumniAnnouncements({
    announcements,
}: {
    announcements: Paginated<ReaderAnnouncement>;
}) {
    return (
        <>
            <Head title="Announcements" />

            <AlumniLayout
                wide
                title="Announcements"
                description="News and events from CITE, newest first. A dot means you have not opened it yet."
            >
                <div className="sv-auth-body">
                    <AnnouncementList
                        page={announcements}
                        hrefFor={(id) => `/alumni/announcements/${id}`}
                        empty="There are no announcements for you right now."
                    />
                </div>
            </AlumniLayout>
        </>
    );
}
