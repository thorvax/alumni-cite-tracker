/** What POST /admin/announcements/preview returns (also sent to the detail page). */
export type AudiencePreviewData = {
    reach: number;
    withoutEmail: number;
    unknownIds: string[];
    withoutYear: number;
    notActivated: number;
    notActivatedNames: {
        id: number;
        student_id: string | null;
        name: string;
    }[];
};

const people = (n: number) => (n === 1 ? '1 alumnus' : `${n} alumni`);

/**
 * "This will reach N alumni. X have no email ..." shown before sending.
 */
export default function AudiencePreview({
    preview,
    audienceType,
    visibility,
    sendEmail,
    loading = false,
}: {
    preview: AudiencePreviewData | null;
    audienceType: string;
    visibility: string;
    sendEmail: boolean;
    loading?: boolean;
}) {
    if (preview === null) {
        return (
            <div className="ad-preview" aria-live="polite">
                {loading
                    ? 'Counting…'
                    : 'Choose an audience to see who it reaches.'}
            </div>
        );
    }

    const emailed = sendEmail ? preview.reach - preview.withoutEmail : 0;
    const extraNames = preview.notActivated - preview.notActivatedNames.length;

    return (
        <div className="ad-preview" aria-live="polite" aria-busy={loading}>
            <p>
                <strong>This will reach {people(preview.reach)}.</strong>{' '}
                {sendEmail
                    ? `${emailed} will get an email.`
                    : 'No emails will be sent; it is shown on the site only.'}
            </p>

            {sendEmail && preview.withoutEmail > 0 && (
                <p>
                    {preview.withoutEmail} ha
                    {preview.withoutEmail === 1 ? 's' : 've'} no email and will
                    only see it on the site.
                </p>
            )}

            {audienceType === 'student_ids' &&
                preview.unknownIds.length > 0 && (
                    <p className="ad-preview-warn">
                        {preview.unknownIds.length} ID
                        {preview.unknownIds.length === 1
                            ? ' was'
                            : 's were'}{' '}
                        not found: {preview.unknownIds.join(', ')}
                    </p>
                )}

            {audienceType === 'year' && preview.withoutYear > 0 && (
                <p>
                    {preview.withoutYear} graduate
                    {preview.withoutYear === 1 ? ' has' : 's have'} no year on
                    record and {preview.withoutYear === 1 ? 'is' : 'are'} not
                    included.
                </p>
            )}

            {preview.notActivated > 0 && (
                <details>
                    <summary>
                        {preview.notActivated} ha
                        {preview.notActivated === 1 ? 's' : 've'} not activated
                        their account yet
                        {visibility === 'public'
                            ? ' (they can still read it on the public page).'
                            : ', so they can only read it after activating. Their email includes their activation link.'}
                    </summary>
                    <ul>
                        {preview.notActivatedNames.map((person) => (
                            <li key={person.id}>
                                {person.student_id ?? 'No Student ID'} ·{' '}
                                {person.name}
                            </li>
                        ))}
                        {extraNames > 0 && <li>…and {extraNames} more</li>}
                    </ul>
                </details>
            )}
        </div>
    );
}
