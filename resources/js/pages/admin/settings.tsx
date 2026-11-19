import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdField, AdInput } from '@/components/admin/controls';
import AdminLayout from '@/layouts/admin-layout';

type Props = {
    account: {
        name: string | null;
        email: string | null;
        position: string | null;
    };
    system: {
        allow_registration: boolean;
        email_notifications: boolean;
        activity_logs: boolean;
    };
    updateReminders: {
        enabled: boolean;
        months: number;
        monthOptions: number[];
        hour: number;
        /** Alumni who would get a reminder at the next run. */
        due: number;
    };
};

/** 8 -> "8:00 AM", 13 -> "1:00 PM" */
const hourLabel = (hour: number) =>
    `${hour % 12 === 0 ? 12 : hour % 12}:00 ${hour < 12 ? 'AM' : 'PM'}`;

const flags = [
    { key: 'allow_registration', label: 'Allow new record registration' },
    { key: 'email_notifications', label: 'Enable email notification' },
    { key: 'activity_logs', label: 'Maintain activity logs' },
] as const;

export default function Settings({ account, system, updateReminders }: Props) {
    const accountForm = useForm({
        name: account.name ?? '',
        email: account.email ?? '',
        position: account.position ?? '',
    });

    const systemForm = useForm({ ...system });

    const reminderForm = useForm({
        enabled: updateReminders.enabled,
        months: updateReminders.months,
    });

    const saveAccount = (e: FormEvent) => {
        e.preventDefault();
        accountForm.patch('/settings/system/account', { preserveScroll: true });
    };

    const saveSystem = (e: FormEvent) => {
        e.preventDefault();
        systemForm.patch('/settings/system', { preserveScroll: true });
    };

    const saveReminders = (e: FormEvent) => {
        e.preventDefault();
        reminderForm.patch('/settings/system/update-reminders', {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Settings" />

            <AdminLayout
                active="settings"
                page="settings"
                title="Settings"
                subtitle="Manage system settings."
            >
                <div className="ad-settings" style={{ marginTop: 22 }}>
                    <form className="ad-card" onSubmit={saveAccount} noValidate>
                        <h2 className="ad-card-title">Account Settings</h2>

                        <div className="ad-grid-3" style={{ marginTop: 18 }}>
                            <AdField
                                id="name"
                                label="Name"
                                error={accountForm.errors.name}
                            >
                                <AdInput
                                    id="name"
                                    value={accountForm.data.name}
                                    onChange={(e) =>
                                        accountForm.setData(
                                            'name',
                                            e.target.value,
                                        )
                                    }
                                />
                            </AdField>
                            <AdField
                                id="email"
                                label="Email"
                                error={accountForm.errors.email}
                            >
                                <AdInput
                                    id="email"
                                    type="email"
                                    value={accountForm.data.email}
                                    onChange={(e) =>
                                        accountForm.setData(
                                            'email',
                                            e.target.value,
                                        )
                                    }
                                />
                            </AdField>
                            <AdField
                                id="position"
                                label="Position"
                                error={accountForm.errors.position}
                            >
                                <AdInput
                                    id="position"
                                    value={accountForm.data.position}
                                    onChange={(e) =>
                                        accountForm.setData(
                                            'position',
                                            e.target.value,
                                        )
                                    }
                                />
                            </AdField>
                        </div>

                        <div className="ad-actions">
                            {accountForm.recentlySuccessful && (
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
                                disabled={accountForm.processing}
                            >
                                Update Account
                            </button>
                        </div>
                    </form>

                    <form className="ad-card" onSubmit={saveSystem} noValidate>
                        <h2 className="ad-card-title">System Settings</h2>

                        <div className="ad-checks">
                            {flags.map((flag) => (
                                <label key={flag.key} className="ad-check">
                                    <input
                                        type="checkbox"
                                        checked={systemForm.data[flag.key]}
                                        onChange={(e) =>
                                            systemForm.setData(
                                                flag.key,
                                                e.target.checked,
                                            )
                                        }
                                    />
                                    {flag.label}
                                </label>
                            ))}
                        </div>

                        <div className="ad-actions">
                            {systemForm.recentlySuccessful && (
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
                                disabled={systemForm.processing}
                            >
                                Save Changes
                            </button>
                        </div>
                    </form>

                    <form
                        className="ad-card"
                        onSubmit={saveReminders}
                        noValidate
                    >
                        <h2 className="ad-card-title">Update Reminders</h2>
                        <p className="ad-sub" style={{ marginTop: 6 }}>
                            Emails alumni who answered the survey and have not
                            updated it for a while, so their data stays current.
                            Sent daily at {hourLabel(updateReminders.hour)} (the
                            time set for survey reminders on the Bulk Upload
                            page).
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
                                Remind alumni to update their answers
                            </label>
                        </div>

                        <div className="ad-grid-3" style={{ marginTop: 14 }}>
                            <div>
                                <label
                                    className="ad-label"
                                    htmlFor="update-months"
                                >
                                    Remind when the last update is older than
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
                        </div>

                        <p className="ad-sub" style={{ marginTop: 14 }}>
                            Due now (with the saved setting):{' '}
                            <strong>{updateReminders.due}</strong> alumni. Each
                            person gets at most one reminder per period.
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
                                Save Reminders
                            </button>
                        </div>
                    </form>
                </div>
            </AdminLayout>
        </>
    );
}
