import { Head, Link } from '@inertiajs/react';
import { ArrowRight, House, Info, MessageSquare } from 'lucide-react';
import { CiteLogo, CollegeCredit } from '@/components/cite-logo';
import { cn } from '@/lib/utils';
import { NavMain } from '@/components/nav-main';

// About and Contact pages don't exist yet; point these at real routes once they do.
const navItems = [
    { title: 'Home', label: 'Home', href: '/', icon: House, active: true },
    {
        title: 'About',
        label: 'About',
        href: '#about',
        icon: Info,
        active: false,
    },
    {
        title: 'Contact',
        label: 'Contact',
        href: '#contact',
        icon: MessageSquare,
        active: false,
    },
];

export default function Welcome() {
    return (
        <>
            <Head title="CITE Tracker" />
            <div className="flex min-h-screen bg-[#2b2b2b] text-white">
                {/* Sidebar (desktop) */}
                <aside className="hidden w-[230px] shrink-0 flex-col justify-between py-7 md:flex">
                    <div>
                        <div className="px-8">
                            <CiteLogo />
                        </div>

                        <nav className="mt-8 flex flex-col gap-1 px-6">
                            {navItems.map(
                                ({ label, href, icon: Icon, active }) => (
                                    <a
                                        key={label}
                                        href={href}
                                        aria-current={
                                            active ? 'page' : undefined
                                        }
                                        className={cn(
                                            'flex items-center gap-3 rounded-md px-3 py-2.5 text-sm',
                                            active
                                                ? 'bg-neutral-800 font-medium text-white'
                                                : 'text-gray-400 hover:text-gray-200',
                                        )}
                                    >
                                        <Icon className="size-4" />
                                        {label}
                                    </a>
                                ),
                            )}
                        </nav>
                    </div>

                    <div className="mx-3.5 rounded-sm border border-white/10 px-5 py-4">
                        <CollegeCredit />
                    </div>
                </aside>

                {/* Hero */}
                <main
                    className="relative flex flex-1 flex-col bg-cover bg-center"
                    style={{
                        backgroundImage:
                            "linear-gradient(rgba(24,24,24,0.78), rgba(24,24,24,0.78)), url('/images/ucu-cite.png')",
                    }}
                >
                    {/* Top bar (mobile) */}
                    <div className="flex items-center justify-between p-5 md:hidden">
                        <CiteLogo />
                    </div>

                    <div className="flex flex-1 items-center px-8 md:px-[77px]">
                        <div>
                            <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-black/20 px-3.5 py-1.5 text-xs tracking-wider text-white/60">
                                <span className="size-1.5 rounded-full bg-white/50" />
                                UCU · CITE
                            </span>

                            <h1 className="mt-6 text-5xl leading-[1.1] font-bold tracking-tight md:text-[52px]">
                                Track Today,
                                <br />
                                <span className="text-white/45">
                                    Empower Tomorrow
                                </span>
                            </h1>

                            <Link
                                href="/survey/general-information"
                                className="mt-6 inline-flex items-center gap-3 rounded-md bg-[#f0f0f0] px-6 py-3.5 text-sm font-semibold text-black transition-colors hover:bg-white focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-black focus-visible:outline-none"
                            >
                                Get Started
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </div>

                    {/* College credit (mobile) */}
                    <div className="p-5 md:hidden">
                        <CollegeCredit />
                    </div>
                </main>
            </div>
        </>
    );
}
