import { Head, Link } from '@inertiajs/react';
import { ArrowRight, House, Info, MessageSquare } from 'lucide-react';
import { CiteLogo, CollegeCredit } from '@/components/cite-logo';
import ThemeToggle from '@/components/survey/theme-toggle';
import { stepUrl } from '@/lib/survey';

// About and Contact pages don't exist yet; point these at real routes once they do.
const navItems = [
    { label: 'Home', href: '/', icon: House, active: true },
    { label: 'About', href: '#about', icon: Info, active: false },
    { label: 'Contact', href: '#contact', icon: MessageSquare, active: false },
];

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

                            <Link
                                href={stepUrl('general-information')}
                                className="sv-cta"
                            >
                                Get Started
                                <ArrowRight size={16} aria-hidden />
                            </Link>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
