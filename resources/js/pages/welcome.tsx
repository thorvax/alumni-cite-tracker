import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    House,
    Info,
    Megaphone,
    MessageSquare,
} from 'lucide-react';
import { CiteLogo, CollegeCredit } from '@/components/cite-logo';
import ThemeToggle from '@/components/survey/theme-toggle';
import { privacy } from '@/routes';
import { login, logout, mySurvey } from '@/routes/alumni';
import { show as forgotPassword } from '@/routes/alumni/forgot';
import { start as surveyStart } from '@/routes/survey';

// About and Contact pages don't exist yet; point these at real routes once they do.
const navItems = [
    { label: 'Home', href: '/', icon: House, active: true },
    {
        label: 'Announcements',
        href: '/announcements',
        icon: Megaphone,
        active: false,
    },
    { label: 'About', href: '#about', icon: Info, active: false },
    { label: 'Contact', href: '#contact', icon: MessageSquare, active: false },
];

/**
 * One main button that says exactly where it goes:
 *  - not logged in: Log in to the survey (+ how first-time graduates activate)
 *  - logged in, not answered: Start the survey
 *  - logged in, answered: Go to My Survey
 */
function SurveyAction() {
    const { alumni } = usePage().props;

    if (!alumni) {
        return (
            <>
                <Link href={login()} className="sv-cta">
                    Log in to the survey
                    <ArrowRight size={16} aria-hidden />
                </Link>
                <p className="sv-cta-note">
                    First time here? Open the personal link we emailed you to
                    activate your account. ·{' '}
                    <Link href={forgotPassword()}>Forgot password?</Link>
                </p>
            </>
        );
    }

    return (
        <>
            <Link
                href={alumni.answered ? mySurvey() : surveyStart()}
                className="sv-cta"
            >
                {alumni.answered ? 'Go to My Survey' : 'Start the survey'}
                <ArrowRight size={16} aria-hidden />
            </Link>
            <p className="sv-cta-note">
                Logged in{alumni.last_name ? ` as ${alumni.last_name}` : ''} ·{' '}
                <Link href={logout()} as="button" className="sv-cta-link">
                    Log out
                </Link>
            </p>
        </>
    );
}

export default function Welcome() {
    return (
        <>
            <Head title="CITE Tracker" />

            <div className="sv sv-landing" data-page="landing">
                <aside className="sv-side">
                    <div>
                        <div className="sv-side-top">
                            <CiteLogo />
                        </div>

                        <nav className="sv-landing-nav" aria-label="Main">
                            {navItems.map(
                                ({ label, href, icon: Icon, active }) => (
                                    <a
                                        key={label}
                                        href={href}
                                        className="sv-landing-link"
                                        aria-current={
                                            active ? 'page' : undefined
                                        }
                                    >
                                        <Icon size={16} aria-hidden />
                                        {label}
                                    </a>
                                ),
                            )}
                        </nav>
                    </div>

                    <div className="sv-landing-credit">
                        <CollegeCredit />
                    </div>
                </aside>

                <main
                    className="sv-hero"
                    style={{
                        backgroundImage:
                            "var(--sv-overlay), url('/images/ucu-cite.jpg')",
                    }}
                >
                    <ThemeToggle />

                    <div className="sv-hero-content">
                        <div>
                            <span className="sv-pill">
                                <i />
                                UCU · CITE
                            </span>

                            <h1 className="sv-hero-title">
                                Track Today,
                                <br />
                                <span>Empower Tomorrow</span>
                            </h1>

                            <div className="sv-cta-block">
                                <SurveyAction />
                            </div>
                        </div>
                    </div>

                    <footer className="sv-landing-footer">
                        {/* Also here because the sidebar is hidden on phones. */}
                        <Link href="/announcements">Announcements</Link> ·{' '}
                        <Link href={privacy()}>Data Privacy Notice</Link>
                    </footer>
                </main>
            </div>
        </>
    );
}
