import { Link } from '@inertiajs/react';
import type { ReaderAnnouncement } from '@/components/announcements/announcement-list';

/**
 * One announcement in full (alumni page and public page). The message is
 * plain text: React escapes it and CSS keeps its line breaks.
 */
export default function AnnouncementDetail({
    announcement: a,
    backHref,
    backLabel,
}: {
    announcement: ReaderAnnouncement;
    backHref: string;
    backLabel: string;
}) {
    return (
        <div className="sv-auth-body">
            <p className="sv-ann-meta">
                <span className="sv-ann-type">
                    {a.type === 'event' ? 'Event' : 'Announcement'}
                </span>
                {a.date && <span>· Posted {a.date}</span>}
            </p>

            {a.type === 'event' && (
                <dl className="sv-summary sv-ann-when">
                    {a.event_date && (
                        <div>
                            <dt>When</dt>
                            <dd>{a.event_date}</dd>
                        </div>
                    )}
                    {a.event_place && (
                        <div>
                            <dt>Where</dt>
                            <dd>{a.event_place}</dd>
                        </div>
                    )}
                    {a.event_link && (
                        <div className="sv-col-span">
                            <dt>Link</dt>
                            <dd>
                                <a
                                    href={a.event_link}
                                    target="_blank"
                                    rel="noreferrer noopener"
                                    className="sv-auth-link"
                                >
                                    {a.event_link}
                                </a>
                            </dd>
                        </div>
                    )}
                </dl>
            )}

            <p className="sv-ann-body">{a.body}</p>

            <p>
                <Link href={backHref} className="sv-auth-link">
                    ← {backLabel}
                </Link>
            </p>
        </div>
    );
}
