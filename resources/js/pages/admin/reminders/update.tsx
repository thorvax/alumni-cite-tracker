import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import ReminderTabs from '@/components/admin/reminder-tabs';
import WhoToRemind, {
    type ReminderTarget,
} from '@/components/admin/who-to-remind';
import AdminLayout from '@/layouts/admin-layout';

type Props = {
    /** Automatic update reminders (saved settings). */
    updateReminders: {
        enabled: boolean;
        /** Remind every N months. */
        months: number;
        /** all = everyone who answered; stale = not updated in the past staleMonths. */
        target: ReminderTarget;
        staleMonths: number;
        monthOptions: number[];
        hour: number;
        /** Alumni who would get a reminder at the next run. */
        due: number;
    };
    /** Manual "Send now": how many each choice would email. */
    manualCounts: { all: number; stale: Record<number, number> };
    /** Message after "Send now". */
    flash?: string | null;
};

/** 8 -> "8:00 AM", 13 -> "1:00 PM" */
const hourLabel = (hour: number) =>
    `${hour % 12 === 0 ? 12 : hour % 12}:00 ${hour < 12 ? 'AM' : 'PM'}`;

/** Reminders > Update reminders: automatic schedule and "Send now". */
export default function UpdateReminders({
    updateReminders,
    manualCounts,
    flash,
}: Props) {
    const reminderForm = useForm({
        enabled: updateReminders.enabled,
        months: updateReminders.months,
        target: updateReminders.target,
        stale_months: updateReminders.staleMonths,
    });

    const manualForm = useForm({
        target: 'stale' as ReminderTarget,
        stale_months: updateReminders.staleMonths,
    });

    const manualCount =
        manualForm.data.target === 'all'
            ? manualCounts.all
            : (manualCounts.stale[manualForm.data.stale_months] ?? 0);

    // Manual: sends right away to the chosen group, even if automatic reminders are off.
    const sendRemindersNow = (e: FormEvent) => {
        e.preventDefault();

        if (
            window.confirm(
                `Email an update reminder to ${manualCount} alumni now?`,
            )
        ) {
            manualForm.post('/settings/system/update-reminders/send', {
                preserveScroll: true,
            });
        }
    };

    const saveReminders = (e: FormEvent) => {
        e.preventDefault();
        reminderForm.patch('/settings/system/update-reminders', {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Update Reminders" />

            <AdminLayout
                active="reminders"
                page="settings"
                title="Reminders"
                subtitle="Remind graduates to answer, or to keep their answers up to date."
            >
                <ReminderTabs active="update" />

                <div className="ad-settings">
                    <form
                        className="ad-card"
                        onSubmit={saveReminders}
                        noValidate
                    >
                        <h2 className="ad-card-title">
                            Automatic Update Reminders
                        </h2>
                        <p className="ad-sub" style={{ marginTop: 6 }}>
                            Emails alumni on a schedule so their data stays
                            current. Sent daily at{' '}
                            {hourLabel(updateReminders.hour)} (the time set on
                            the Survey reminders tab). Each person gets at most
                            one reminder per period.
                        </p>

                        <div className="ad-checks">
                            <label className="ad-check">
                                <input
                                    type="checkbox"
                                    checked={reminderForm.data.enabled}
                                    onChange={(e) =>
                                        reminderForm.setData(
                                            'enabled',
                                            e.target.checked,
                                        )
                                    }
                                />
                                Send update reminders automatically
                            </label>
                        </div>

                        <div className="ad-grid-3" style={{ marginTop: 14 }}>
                            <div>
                                <label
                                    className="ad-label"
                                    htmlFor="update-months"
                                >
                                    Remind every
                                </label>
                                <select
                                    id="update-months"
                                    className="ad-select"
                                    value={reminderForm.data.months}
                                    onChange={(e) =>
                                        reminderForm.setData(
                                            'months',
                                            Number(e.target.value),
                                        )
                                    }
                                >
                                    {updateReminders.monthOptions.map((m) => (
                                        <option key={m} value={m}>
                                            {m} months
                                        </option>
                                    ))}
                                </select>
                                {reminderForm.errors.months && (
                                    <p className="ad-row-error" role="alert">
                                        {reminderForm.errors.months}
                                    </p>
                                )}
                            </div>

                            <div className="ad-span-2">
                                <WhoToRemind
                                    id="auto"
                                    target={reminderForm.data.target}
                                    staleMonths={reminderForm.data.stale_months}
                                    monthOptions={updateReminders.monthOptions}
                                    onChange={(patch) =>
                                        reminderForm.setData((prev) => ({
                                            ...prev,
                                            ...patch,
                                        }))
                                    }
                                    errors={reminderForm.errors}
                                />
                            </div>
                        </div>

                        <p className="ad-sub" style={{ marginTop: 14 }}>
                            Due at the next run (with the saved settings):{' '}
                            <strong>{updateReminders.due}</strong> alumni (
                            <Link href="/graduates/reminders?list=due">
                                see the list
                            </Link>
                            ). See who has updated and what changed on{' '}
                            <Link href="/graduates/updates">Update Status</Link>
                            .
                        </p>

                        <div className="ad-actions">
                            {reminderForm.recentlySuccessful && (
                                <span
                                    className="ad-sub"
                                    style={{ alignSelf: 'center' }}
                                    role="status"
                                >
                                    Saved.
                                </span>
                            )}
                            <button
                                type="submit"
                                className="ad-btn"
                                disabled={reminderForm.processing}
                            >
                                Save Automatic Reminders
                            </button>
                        </div>
                    </form>

                    <form
                        className="ad-card"
                        onSubmit={sendRemindersNow}
                        noValidate
                    >
                        <h2 className="ad-card-title">
                            Send Update Reminders Now
                        </h2>
                        <p className="ad-sub" style={{ marginTop: 6 }}>
                            Emails the chosen alumni right away, whether or not
                            automatic reminders are on. Nothing here is saved.
                        </p>

                        <div style={{ marginTop: 14 }}>
                            <WhoToRemind
                                id="manual"
                                target={manualForm.data.target}
                                staleMonths={manualForm.data.stale_months}
                                monthOptions={updateReminders.monthOptions}
                                onChange={(patch) =>
                                    manualForm.setData((prev) => ({
                                        ...prev,
                                        ...patch,
                                    }))
                                }
                                errors={manualForm.errors}
                            />
                        </div>

                        <div
                            className="ad-actions"
                            style={{ justifyContent: 'flex-start' }}
                        >
                            <button
                                type="submit"
                                className="ad-btn ad-btn-solid"
                                disabled={
                                    manualCount === 0 || manualForm.processing
                                }
                            >
                                Send now to {manualCount} alumni
                            </button>
                            <Link
                                href={`/graduates/reminders?list=manual&target=${manualForm.data.target}&stale_months=${manualForm.data.stale_months}`}
                                className="ad-btn"
                            >
                                See who
                            </Link>
                            {flash && (
                                <span
                                    className="ad-sub"
                                    style={{ alignSelf: 'center' }}
                                    role="status"
                                >
                                    {flash}
                                </span>
                            )}
                        </div>
                    </form>
                </div>
            </AdminLayout>
        </>
    );
}
