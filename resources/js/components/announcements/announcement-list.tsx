import { Link } from '@inertiajs/react';
import { CalendarDays, MapPin } from 'lucide-react';
import type { Paginated } from '@/lib/admin';

/** One announcement as the alumni and public pages show it. */
export type ReaderAnnouncement = {
    id: number;
    type: 'announcement' | 'event';
    title: string;
    body: string;
    excerpt: string;
    date: string | null;
    event_date: string | null;
    event_place: string | null;
    event_link: string | null;
    /** Alumni page only: sent to them and not opened yet. */
    unread?: boolean;
};

/**
 * Newest-first list of announcements (alumni page and public page).
 */
export default function AnnouncementList({
    page,
    hrefFor,
    empty,
}: {
    page: Paginated<ReaderAnnouncement>;
    hrefFor: (id: number) => string;
    empty: string;
}) {
    if (page.data.length === 0) {
        return <p className="sv-auth-note">{empty}</p>;
    }

    return (
        <>
            <ul className="sv-ann-list">
                {page.data.map((item) => (
                    <li key={item.id}>
                        <Link
                            href={hrefFor(item.id)}
                            className="sv-ann-item"
                            data-unread={item.unread ? 'true' : undefined}
                        >
                            <span className="sv-ann-meta">
                                {item.unread && (
                                    <span className="sv-ann-dot">
                                        <span className="sr-only">Unread</span>
                                    </span>
                                )}
                                <span className="sv-ann-type">
                                    {item.type === 'event'
                                        ? 'Event'
                                        : 'Announcement'}
                                </span>
                                {item.date && <span>· {item.date}</span>}
                            </span>
                            <span className="sv-ann-title">{item.title}</span>
                            {item.type === 'event' && item.event_date && (
                                <span className="sv-ann-event">
                                    <span>
                                        <CalendarDays size={13} aria-hidden />
                                        {item.event_date}
                                    </span>
                                    {item.event_place && (
                                        <span>
                                            <MapPin size={13} aria-hidden />
                                            {item.event_place}
                                        </span>
                                    )}
                                </span>
                            )}
                            <span className="sv-ann-excerpt">
                                {item.excerpt}
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>

            {page.last_page > 1 && (
                <nav className="sv-auth-row" aria-label="Pages">
                    {page.current_page > 1 ? (
                        <Link
                            href={`?page=${page.current_page - 1}`}
                            className="sv-auth-link"
                        >
                            ← Newer
                        </Link>
                    ) : (
                        <span />
                    )}
                    <span className="sv-auth-note">
                        Page {page.current_page} of {page.last_page}
                    </span>
                    {page.current_page < page.last_page ? (
                        <Link
                            href={`?page=${page.current_page + 1}`}
                            className="sv-auth-link"
                        >
                            Older →
                        </Link>
                    ) : (
                        <span />
                    )}
                </nav>
            )}
        </>
    );
}
