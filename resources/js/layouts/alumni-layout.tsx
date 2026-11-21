import { Link, usePage } from '@inertiajs/react';
import { ClipboardList, Megaphone } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { CiteLogo, CollegeCredit } from '@/components/cite-logo';
import LogoutButton from '@/components/survey/logout-button';
import ThemeToggle from '@/components/survey/theme-toggle';
import { cn } from '@/lib/utils';
import { privacy } from '@/routes';

type Props = PropsWithChildren<{
    title: string;
    description?: string;
    /** Wider card, for pages that show more than a small form. */
    wide?: boolean;
}>;

/** My Survey and Announcements (with the unread count), for a logged-in graduate. */
function AlumniNav() {
    const { props, url } = usePage();
    const { alumni } = props;

    if (!alumni) {
        return null;
    }

    const unread = alumni.unread_announcements;
    const items = [
        { href: '/alumni/my-survey', label: 'My Survey', icon: ClipboardList },
        {
            href: '/alumni/announcements',
            label: 'Announcements',
            icon: Megaphone,
        },
    ];

    return (
        <nav className="sv-alumni-nav" aria-label="Alumni">
            {items.map(({ href, label, icon: Icon }) => (
                <Link
                    key={href}
                    href={href}
                    aria-current={url.startsWith(href) ? 'page' : undefined}
                >
                    <Icon size={15} aria-hidden />
                    {label}
                    {href === '/alumni/announcements' && unread > 0 && (
                        <span className="sv-alumni-count">
                            {unread}
                            <span className="sr-only"> unread</span>
                        </span>
                    )}
                </Link>
            ))}
        </nav>
    );
}

/**
 * Layout for the alumni account pages (activate, code, password, login,
 * My Survey). Same sidebar, colors and theme toggle as the survey.
 */
export default function AlumniLayout({
    title,
    description,
    wide = false,
    children,
}: Props) {
    return (
        <div className="sv" data-page="alumni">
            <aside className="sv-side">
                <div>
                    <div className="sv-side-top">
                        <CiteLogo />
                    </div>
                    <AlumniNav />
                    <LogoutButton />
                </div>

                <div className="sv-credit">
                    <CollegeCredit />
                </div>
            </aside>

            <div className="sv-col">
                <ThemeToggle />

                <main className="sv-main sv-auth">
                    <div className={cn('sv-auth-card', wide && 'sv-auth-wide')}>
                        <h1 className="sv-title">{title}</h1>
                        {description && (
                            <p className="sv-desc">{description}</p>
                        )}
                        {children}
                    </div>
                </main>

                <footer className="sv-auth-footer">
                    <Link href={privacy()}>Data Privacy Notice</Link>
                </footer>
            </div>
        </div>
    );
}
