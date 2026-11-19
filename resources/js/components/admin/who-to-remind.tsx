export type ReminderTarget = 'all' | 'stale';

/**
 * "Who to remind" for update reminders (used by the automatic and the manual
 * cards on Settings): everyone who answered, or only those who have not
 * updated in the past 3/6/9/12 months.
 */
export default function WhoToRemind({
    id,
    target,
    staleMonths,
    monthOptions,
    onChange,
    errors = {},
}: {
    id: string;
    target: ReminderTarget;
    staleMonths: number;
    monthOptions: number[];
    onChange: (patch: {
        target?: ReminderTarget;
        stale_months?: number;
    }) => void;
    errors?: { target?: string; stale_months?: string };
}) {
    return (
        <fieldset className="ad-who">
            <legend className="ad-label">Who to remind</legend>

            <label className="ad-check">
                <input
                    type="radio"
                    name={`${id}-target`}
                    checked={target === 'all'}
                    onChange={() => onChange({ target: 'all' })}
                />
                All alumni who answered the survey, even if they answered
                recently
            </label>

            <label className="ad-check">
                <input
                    type="radio"
                    name={`${id}-target`}
                    checked={target === 'stale'}
                    onChange={() => onChange({ target: 'stale' })}
                />
                Alumni who have not updated their data in the past
                <select
                    aria-label="Months without an update"
                    className="ad-select ad-select-inline"
                    value={staleMonths}
                    onChange={(e) =>
                        onChange({
                            target: 'stale',
                            stale_months: Number(e.target.value),
                        })
                    }
                >
                    {monthOptions.map((m) => (
                        <option key={m} value={m}>
                            {m}
                        </option>
                    ))}
                </select>
                months
            </label>

            {(errors.target || errors.stale_months) && (
                <p className="ad-row-error" role="alert">
                    {errors.target ?? errors.stale_months}
                </p>
            )}
        </fieldset>
    );
}
