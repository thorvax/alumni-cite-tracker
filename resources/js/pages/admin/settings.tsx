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
};

const flags = [
    { key: 'allow_registration', label: 'Allow new record registration' },
    { key: 'email_notifications', label: 'Enable email notification' },
    { key: 'activity_logs', label: 'Maintain activity logs' },
] as const;

export default function Settings({ account, system }: Props) {
    const accountForm = useForm({
        name: account.name ?? '',
        email: account.email ?? '',
        position: account.position ?? '',
    });

    const systemForm = useForm({ ...system });

    const saveAccount = (e: FormEvent) => {
        e.preventDefault();
        accountForm.patch('/settings/system/account', { preserveScroll: true });
    };

    const saveSystem = (e: FormEvent) => {
        e.preventDefault();
        systemForm.patch('/settings/system', { preserveScroll: true });
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
                </div>
            </AdminLayout>
        </>
    );
}
