import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import ReminderTabs from '@/components/admin/reminder-tabs';
import AdminLayout from '@/layouts/admin-layout';

type Props = {
    reminders: {
        enabled: boolean;
        interval_days: number;
        max: number;
        hour: number;
        /** Emailed a survey link and not answered yet. */
        waiting: number;
    };
    flash: string | null;
};

/** Reminders > Survey reminders: for people who have not answered yet. */
export default function SurveyReminders({ reminders, flash }: Props) {
    const schedule = useForm({
        enabled: reminders.enabled,
        interval_days: reminders.interval_days,
        max: reminders.max,
        hour: reminders.hour,
    });

    const saveSchedule = (e: FormEvent) => {
        e.preventDefault();
        schedule.patch('/graduates/import/schedule', { preserveScroll: true });
    };

    const sendNow = () => {
        if (
            window.confirm(
                `Email a survey reminder to ${reminders.waiting} person(s) now?`,
            )
        ) {
            router.post(
                '/graduates/import/remind',
                {},
                { preserveScroll: true },
            );
        }
    };

    return (
        <>
            <Head title="Survey Reminders" />

            <AdminLayout
                active="reminders"
                page="graduates"
                title="Reminders"
                subtitle="Remind graduates to answer, or to keep their answers up to date."
            >
                <ReminderTabs active="survey" />

                <div className="ad-stack">
                    {flash && (
                        <p className="ad-flash" role="status">
                            {flash}
                        </p>
                    )}

                    <section className="ad-card">
                        <h2 className="ad-card-title">Send a reminder now</h2>
                        <p className="ad-note" style={{ margin: '8px 0 14px' }}>
                            {reminders.waiting} person(s) were emailed a survey
                            link and have not answered yet (see{' '}
                            <Link href="/graduates/invitations?status=sent">
                                Invitations
                            </Link>
                            ).
                        </p>
                        <button
                            type="button"
                            className="ad-btn ad-btn-solid"
                            disabled={reminders.waiting === 0}
                            onClick={sendNow}
                        >
                            Send reminder now ({reminders.waiting})
                        </button>
                    </section>

                    <form
                        className="ad-card"
                        onSubmit={saveSchedule}
                        noValidate
                    >
                        <h2 className="ad-card-title">Automatic schedule</h2>
                        <p className="ad-note" style={{ margin: '8px 0 14px' }}>
                            The send hour is also used for update reminders.
                        </p>

                        <label className="ad-check">
                            <input
                                type="checkbox"
                                checked={schedule.data.enabled}
                                onChange={(e) =>
                                    schedule.setData(
                                        'enabled',
                                        e.target.checked,
                                    )
                                }
                            />
                            Send reminders automatically
                        </label>

                        <div className="ad-grid-3" style={{ marginTop: 16 }}>
                            <div>
                                <label
                                    className="ad-label"
                                    htmlFor="interval_days"
                                >
                                    Repeat every (days)
                                </label>
                                <input
                                    id="interval_days"
                                    className="ad-input"
                                    type="number"
                                    min={1}
                                    max={90}
                                    value={schedule.data.interval_days}
                                    onChange={(e) =>
                                        schedule.setData(
                                            'interval_days',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                                {schedule.errors.interval_days && (
                                    <p className="ad-error">
                                        {schedule.errors.interval_days}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="ad-label" htmlFor="max">
                                    Up to (reminders per person)
                                </label>
                                <input
                                    id="max"
                                    className="ad-input"
                                    type="number"
                                    min={1}
                                    max={10}
                                    value={schedule.data.max}
                                    onChange={(e) =>
                                        schedule.setData(
                                            'max',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                                {schedule.errors.max && (
                                    <p className="ad-error">
                                        {schedule.errors.max}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="ad-label" htmlFor="hour">
                                    Send at (hour, 0–23)
                                </label>
                                <input
                                    id="hour"
                                    className="ad-input"
                                    type="number"
                                    min={0}
                                    max={23}
                                    value={schedule.data.hour}
                                    onChange={(e) =>
                                        schedule.setData(
                                            'hour',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                                {schedule.errors.hour && (
                                    <p className="ad-error">
                                        {schedule.errors.hour}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="ad-actions">
                            {schedule.recentlySuccessful && (
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
                                disabled={schedule.processing}
                            >
                                Save Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </AdminLayout>
        </>
    );
}
