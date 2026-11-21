import { Head, Link, usePage } from '@inertiajs/react';
import AnnouncementList, {
    type ReaderAnnouncement,
} from '@/components/announcements/announcement-list';
import AlumniLayout from '@/layouts/alumni-layout';
import type { Paginated } from '@/lib/admin';

/** Public page (no login): only announcements marked public. */
export default function PublicAnnouncements({
    announcements,
}: {
    announcements: Paginated<ReaderAnnouncement>;
}) {
    const { alumni } = usePage().props;

    return (
        <>
            <Head title="Announcements" />

            <AlumniLayout
                wide
                title="Announcements"
                description="News and events from the College of Information and Technology Education."
            >
                <div className="sv-auth-body">
                    <p className="sv-auth-status">
                        {alumni ? (
                            <>
                                Some announcements are only for certain alumni.{' '}
                                <Link
                                    href="/alumni/announcements"
                                    className="sv-auth-link"
                                >
                                    See all of yours
                                </Link>
                            </>
                        ) : (
                            <>
                                CITE alumni:{' '}
                                <Link
                                    href="/alumni/login"
                                    className="sv-auth-link"
                                >
                                    log in
                                </Link>{' '}
                                to also see announcements meant for you.
                            </>
                        )}
                    </p>

                    <AnnouncementList
                        page={announcements}
                        hrefFor={(id) => `/announcements/${id}`}
                        empty="There are no public announcements right now."
                    />

                    <p>
                        <Link href="/" className="sv-auth-link">
                            ← Back to home
                        </Link>
                    </p>
                </div>
            </AlumniLayout>
        </>
    );
}
