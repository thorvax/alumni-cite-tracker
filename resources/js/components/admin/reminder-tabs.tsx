import { Link } from '@inertiajs/react';

const TABS = [
    { key: 'survey', label: 'Survey reminders', href: '/reminders/survey' },
    { key: 'update', label: 'Update reminders', href: '/reminders/update' },
    { key: 'list', label: 'Reminder list', href: '/graduates/reminders' },
] as const;

/** Tabs across the Reminders section (sidebar > Reminders). */
export default function ReminderTabs({
    active,
}: {
    active: (typeof TABS)[number]['key'];
}) {
    return (
        <nav className="ad-chips ad-subnav" aria-label="Reminders">
            {TABS.map((tab) => (
                <Link
                    key={tab.key}
                    href={tab.href}
                    className="ad-btn ad-btn-sm"
                    aria-current={tab.key === active ? 'page' : undefined}
                >
                    {tab.label}
                </Link>
            ))}
        </nav>
    );
}
