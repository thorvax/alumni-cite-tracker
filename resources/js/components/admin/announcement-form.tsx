import { Link, useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AudiencePreview, {
    type AudiencePreviewData,
} from '@/components/admin/audience-preview';
import { AdField, AdInput } from '@/components/admin/controls';

export type AnnouncementAction = 'draft' | 'send' | 'schedule';

export type AnnouncementData = {
    type: 'announcement' | 'event';
    title: string;
    body: string;
    event_starts_at: string;
    event_place: string;
    event_link: string;
    audience_type: 'all' | 'student_ids' | 'year';
    /** Pasted Student ID Numbers (commas, spaces or new lines). */
    student_ids: string;
    year_from: string;
    year_to: string;
    visibility: 'public' | 'selected';
    send_email: boolean;
    show_until: string;
    publish_at: string;
};

export const emptyAnnouncement: AnnouncementData = {
    type: 'announcement',
    title: '',
    body: '',
    event_starts_at: '',
    event_place: '',
    event_link: '',
    audience_type: 'all',
    student_ids: '',
    year_from: '',
    year_to: '',
    visibility: 'selected',
    send_email: true,
    show_until: '',
    publish_at: '',
};

type Errors = Partial<Record<keyof AnnouncementData | 'action', string>>;

/**
 * The Event / Announcement form shared by "New" and "Edit". When `locked`
 * (already sent) only the title, message and "show until" can change.
 */
export default function AnnouncementForm({
    data,
    errors,
    processing,
    locked = false,
    firstYear,
    lastYear,
    onChange,
    onSubmit,
    cancelHref,
}: {
    data: AnnouncementData;
    errors: Errors;
    processing: boolean;
    locked?: boolean;
    firstYear: number;
    lastYear: number;
    onChange: (patch: Partial<AnnouncementData>) => void;
    onSubmit: (action: AnnouncementAction) => void;
    cancelHref: string;
}) {
    const [when, setWhen] = useState<'now' | 'later'>(
        data.publish_at ? 'later' : 'now',
    );
    const isEvent = data.type === 'event';

    // Live "This will reach N alumni" preview, asked again when the audience changes.
    const http = useHttp<Record<string, string>, AudiencePreviewData>();
    const [preview, setPreview] = useState<AudiencePreviewData | null>(null);
    const { audience_type, student_ids, year_from, year_to } = data;

    useEffect(() => {
        if (locked || (audience_type === 'year' && !year_from)) {
            return;
        }

        const timer = window.setTimeout(() => {
            http.transform(() => ({
                audience_type,
                student_ids,
                year_from,
                year_to,
            }));
            http.post('/admin/announcements/preview')
                .then(setPreview)
                .catch(() => setPreview(null));
        }, 400);

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps -- `http` changes every render
    }, [locked, audience_type, student_ids, year_from, year_to]);

    const setAudience = (audience: AnnouncementData['audience_type']) =>
        onChange({
            audience_type: audience,
            // Only "All alumni" may be public; a targeted audience stays private.
            ...(audience !== 'all' ? { visibility: 'selected' } : {}),
        });

    const sendNow = () => {
        const reach = preview?.reach ?? 0;
        const message =
            data.visibility === 'public'
                ? `Publish this on the public page${data.send_email ? ` and email ${reach} alumni` : ''} now?`
                : `Send this to ${reach} alumni now?`;

        if (window.confirm(message)) {
            onSubmit('send');
        }
    };

    return (
        <form
            className="ad-stack ad-ann-form"
            onSubmit={(e) => {
                e.preventDefault();

                if (locked) {
                    onSubmit('draft'); // a sent one only saves its changes
                } else if (when === 'later') {
                    onSubmit('schedule');
                } else {
                    sendNow();
                }
            }}
        >
            {locked && (
                <p className="ad-flash" role="note">
                    This was already sent. You can fix typos or show it longer;
                    the email was already sent, so changes appear on the site
                    only.
                </p>
            )}

            <section className="ad-card">
                <h2 className="ad-card-title">Message</h2>

                <fieldset className="ad-fieldset ad-gap-top" disabled={locked}>
                    <legend className="ad-label">Type</legend>
                    <div className="ad-inline">
                        {(['announcement', 'event'] as const).map((type) => (
                            <label key={type} className="ad-check">
                                <input
                                    type="radio"
                                    name="type"
                                    checked={data.type === type}
                                    onChange={() => onChange({ type })}
                                />
                                {type === 'event' ? 'Event' : 'Announcement'}
                            </label>
                        ))}
                    </div>
                </fieldset>

                <div className="ad-stack ad-gap-top" style={{ gap: 16 }}>
                    <AdField
                        id="title"
                        label="Title"
                        required
                        error={errors.title}
                    >
                        <AdInput
                            id="title"
                            value={data.title}
                            maxLength={150}
                            onChange={(e) =>
                                onChange({ title: e.target.value })
                            }
                        />
                    </AdField>

                    <AdField
                        id="body"
                        label="Message (plain text; line breaks are kept)"
                        required
                        error={errors.body}
                    >
                        <textarea
                            id="body"
                            className="ad-input ad-textarea"
                            value={data.body}
                            maxLength={5000}
                            onChange={(e) => onChange({ body: e.target.value })}
                        />
                    </AdField>
                </div>

                {isEvent && (
                    <div className="ad-grid-2 ad-gap-top">
                        <AdField
                            id="event_starts_at"
                            label="Event date and time"
                            required
                            error={errors.event_starts_at}
                        >
                            <AdInput
                                id="event_starts_at"
                                type="datetime-local"
                                value={data.event_starts_at}
                                disabled={locked}
                                onChange={(e) =>
                                    onChange({
                                        event_starts_at: e.target.value,
                                    })
                                }
                            />
                        </AdField>
                        <AdField
                            id="event_place"
                            label="Place"
                            required
                            error={errors.event_place}
                        >
                            <AdInput
                                id="event_place"
                                value={data.event_place}
                                maxLength={200}
                                disabled={locked}
                                onChange={(e) =>
                                    onChange({ event_place: e.target.value })
                                }
                            />
                        </AdField>
                        <AdField
                            id="event_link"
                            label="Link (optional, e.g. registration or online meeting)"
                            error={errors.event_link}
                            className="ad-span"
                        >
                            <AdInput
                                id="event_link"
                                type="url"
                                placeholder="https://"
                                value={data.event_link}
                                disabled={locked}
                                onChange={(e) =>
                                    onChange({ event_link: e.target.value })
                                }
                            />
                        </AdField>
                    </div>
                )}
            </section>

            <section className="ad-card">
                <h2 className="ad-card-title">Who it is for</h2>

                <fieldset className="ad-fieldset ad-gap-top" disabled={locked}>
                    <legend className="ad-label">Audience</legend>
                    <label className="ad-check">
                        <input
                            type="radio"
                            name="audience_type"
                            checked={data.audience_type === 'all'}
                            onChange={() => setAudience('all')}
                        />
                        All alumni (everyone registered, activated or not)
                    </label>
                    <label className="ad-check">
                        <input
                            type="radio"
                            name="audience_type"
                            checked={data.audience_type === 'student_ids'}
                            onChange={() => setAudience('student_ids')}
                        />
                        Specific Student ID Numbers
                    </label>
                    <label className="ad-check">
                        <input
                            type="radio"
                            name="audience_type"
                            checked={data.audience_type === 'year'}
                            onChange={() => setAudience('year')}
                        />
                        Graduation year (one year or a range)
                    </label>
                    {errors.audience_type && (
                        <p className="ad-error" role="alert">
                            {errors.audience_type}
                        </p>
                    )}
                </fieldset>

                {data.audience_type === 'student_ids' && (
                    <AdField
                        id="student_ids"
                        label="Student ID Numbers (separate with commas, spaces or new lines)"
                        required
                        error={errors.student_ids}
                        className="ad-gap-top"
                    >
                        <textarea
                            id="student_ids"
                            className="ad-input ad-textarea ad-textarea-sm"
                            placeholder={'20244012, 20244013\n20230007'}
                            value={data.student_ids}
                            disabled={locked}
                            onChange={(e) =>
                                onChange({ student_ids: e.target.value })
                            }
                        />
                    </AdField>
                )}

                {data.audience_type === 'year' && (
                    <div className="ad-grid-2 ad-gap-top">
                        <AdField
                            id="year_from"
                            label="Year graduated"
                            required
                            error={errors.year_from}
                        >
                            <AdInput
                                id="year_from"
                                type="number"
                                inputMode="numeric"
                                min={firstYear}
                                max={lastYear}
                                placeholder={String(lastYear - 1)}
                                value={data.year_from}
                                disabled={locked}
                                onChange={(e) =>
                                    onChange({ year_from: e.target.value })
                                }
                            />
                        </AdField>
                        <AdField
                            id="year_to"
                            label="To year (optional, for a range)"
                            error={errors.year_to}
                        >
                            <AdInput
                                id="year_to"
                                type="number"
                                inputMode="numeric"
                                min={firstYear}
                                max={lastYear}
                                value={data.year_to}
                                disabled={locked}
                                onChange={(e) =>
                                    onChange({ year_to: e.target.value })
                                }
                            />
                        </AdField>
                    </div>
                )}

                <fieldset className="ad-fieldset ad-gap-top" disabled={locked}>
                    <legend className="ad-label">
                        Who can see it on the site
                    </legend>
                    <label className="ad-check">
                        <input
                            type="radio"
                            name="visibility"
                            checked={data.visibility === 'selected'}
                            onChange={() =>
                                onChange({ visibility: 'selected' })
                            }
                        />
                        Only the alumni above, after they log in
                    </label>
                    <label className="ad-check">
                        <input
                            type="radio"
                            name="visibility"
                            checked={data.visibility === 'public'}
                            disabled={data.audience_type !== 'all'}
                            onChange={() => onChange({ visibility: 'public' })}
                        />
                        Public: anyone can read it on the Announcements page (no
                        login)
                    </label>
                    <p className="ad-note">
                        {data.audience_type === 'all'
                            ? 'Public also reaches alumni added later. "Only the alumni above" reaches the people registered when it is sent.'
                            : 'Announcements for chosen Student IDs or years are never public; only those alumni see them.'}
                    </p>
                    {errors.visibility && (
                        <p className="ad-error" role="alert">
                            {errors.visibility}
                        </p>
                    )}
                </fieldset>

                <label className="ad-check ad-gap-top">
                    <input
                        type="checkbox"
                        checked={data.send_email}
                        disabled={locked}
                        onChange={(e) =>
                            onChange({ send_email: e.target.checked })
                        }
                    />
                    Also send by email (demo graduates are never emailed)
                </label>

                {!locked && (
                    <div className="ad-gap-top">
                        <AudiencePreview
                            preview={preview}
                            audienceType={data.audience_type}
                            visibility={data.visibility}
                            sendEmail={data.send_email}
                            loading={http.processing}
                        />
                    </div>
                )}
            </section>

            <section className="ad-card">
                <h2 className="ad-card-title">When</h2>

                <div className="ad-grid-2 ad-gap-top">
                    <AdField
                        id="show_until"
                        label="Show until (optional; hidden after this day)"
                        error={errors.show_until}
                    >
                        <AdInput
                            id="show_until"
                            type="date"
                            value={data.show_until}
                            onChange={(e) =>
                                onChange({ show_until: e.target.value })
                            }
                        />
                    </AdField>
                </div>
                {isEvent && (
                    <p className="ad-note" style={{ marginTop: 8 }}>
                        Events are hidden after their date unless "show until"
                        is later.
                    </p>
                )}

                {!locked && (
                    <fieldset className="ad-fieldset ad-gap-top">
                        <legend className="ad-label">Send</legend>
                        <label className="ad-check">
                            <input
                                type="radio"
                                name="when"
                                checked={when === 'now'}
                                onChange={() => {
                                    setWhen('now');
                                    onChange({ publish_at: '' });
                                }}
                            />
                            Now
                        </label>
                        <label className="ad-check">
                            <input
                                type="radio"
                                name="when"
                                checked={when === 'later'}
                                onChange={() => setWhen('later')}
                            />
                            Schedule for later
                        </label>
                        {when === 'later' && (
                            <div className="ad-grid-2">
                                <AdField
                                    id="publish_at"
                                    label="Send on"
                                    required
                                    error={errors.publish_at}
                                >
                                    <AdInput
                                        id="publish_at"
                                        type="datetime-local"
                                        value={data.publish_at}
                                        onChange={(e) =>
                                            onChange({
                                                publish_at: e.target.value,
                                            })
                                        }
                                    />
                                </AdField>
                            </div>
                        )}
                        {when === 'later' && (
                            <p className="ad-note">
                                Scheduled ones are sent within 15 minutes of
                                this time.
                            </p>
                        )}
                    </fieldset>
                )}
            </section>

            <div className="ad-actions" style={{ marginTop: 0 }}>
                <Link href={cancelHref} className="ad-btn">
                    Cancel
                </Link>
                {locked ? (
                    <button
                        type="submit"
                        className="ad-btn ad-btn-primary"
                        disabled={processing}
                    >
                        Save changes
                    </button>
                ) : (
                    <>
                        <button
                            type="button"
                            className="ad-btn"
                            disabled={processing}
                            onClick={() => onSubmit('draft')}
                        >
                            Save draft
                        </button>
                        <button
                            type="submit"
                            className="ad-btn ad-btn-primary"
                            disabled={processing}
                        >
                            {when === 'later' ? 'Schedule' : 'Send now'}
                        </button>
                    </>
                )}
            </div>
        </form>
    );
}
