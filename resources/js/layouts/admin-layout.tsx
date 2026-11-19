import { Link, router } from '@inertiajs/react';
import {
    BellRing,
    ClipboardCheck,
    FileText,
    House,
    LogOut,
    Mail,
    Settings,
    Upload,
    Users,
} from 'lucide-react';
import type { PropsWithChildren, ReactNode } from 'react';
import ThemeToggle from '@/components/survey/theme-toggle';

const nav = [
    { key: 'dashboard', label: 'Dashboard', href: '/dashboard', icon: House },
    { key: 'graduates', label: 'Graduates', href: '/graduates', icon: Users },
    {
        key: 'import',
        label: 'Bulk Upload',
        href: '/graduates/import',
        icon: Upload,
    },
    {
        key: 'invitations',
        label: 'Invitations',
        href: '/graduates/invitations',
        icon: Mail,
    },
    {
        key: 'reminders',
        label: 'Reminders',
        href: '/reminders/survey',
        icon: BellRing,
    },
    {
        key: 'updates',
        label: 'Update Status',
        href: '/graduates/updates',
        icon: ClipboardCheck,
    },
    { key: 'reports', label: 'Reports', href: '/reports', icon: FileText },
    {
        key: 'settings',
        label: 'Settings',
        href: '/settings/system',
        icon: Settings,
    },
] as const;

type Props = PropsWithChildren<{
    active: (typeof nav)[number]['key'];
    page: string; // picks the light-mode palette in admin.css
    title: string;
    subtitle?: string;
    actions?: ReactNode;
}>;

export default function AdminLayout({
    active,
    page,
    title,
    subtitle,
    actions,
    children,
}: Props) {
    return (
        <div className="ad" data-page={page}>
            <aside className="ad-side">
                <div>
                    <div className="ad-brand">
                        <img
                            src="/images/ucu-cite-logo.png"
                            alt="UCU CITE seal"
                        />
                        <div>
                            <div className="ad-brand-title">UCU | CITE</div>
                            <div className="ad-brand-sub">
                                Graduate Tracer System
                            </div>
                        </div>
                    </div>

                    <nav className="ad-nav" aria-label="Main">
                        {nav.map(({ key, label, href, icon: Icon }) => (
                            <Link
                                key={key}
                                href={href}
                                aria-current={
                                    key === active ? 'page' : undefined
                                }
                            >
                                <Icon size={16} aria-hidden />
                                {label}
                            </Link>
                        ))}
                    </nav>
                </div>

                <button
                    type="button"
                    className="ad-logout"
                    onClick={() => router.post('/logout')}
                >
                    <LogOut size={16} aria-hidden />
                    Logout
                </button>
            </aside>

            <div className="ad-col">
                <ThemeToggle className="ad-toggle" />

                <header className="ad-head">
                    <div>
                        <h1 className="ad-title">{title}</h1>
                        {subtitle && <p className="ad-sub">{subtitle}</p>}
                    </div>
                    {actions && (
                        <div className="ad-head-actions">{actions}</div>
                    )}
                </header>

                <main className="ad-body">{children}</main>
            </div>
        </div>
    );
}
