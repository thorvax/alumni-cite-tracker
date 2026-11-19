import { Link, router, usePage } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import { pageItems } from '@/lib/admin';

/** Page links that keep the current search and filters, plus a "Go to page" field. */
export default function Pagination({
    current,
    last,
}: {
    current: number;
    last: number;
}) {
    const { url } = usePage();

    const hrefFor = (page: number) => {
        const next = new URL(url, 'http://localhost');
        next.searchParams.set('page', String(page));

        return `${next.pathname}${next.search}`;
    };

    return (
        <div className="ad-pager">
            <nav className="ad-pages" aria-label="Pagination">
                {pageItems(current, last).map((item, i) =>
                    item === '…' ? (
                        <span key={`gap-${i}`}>…</span>
                    ) : (
                        <Link
                            key={item}
                            href={hrefFor(item)}
                            preserveScroll
                            aria-current={item === current ? 'page' : undefined}
                        >
                            {item}
                        </Link>
                    ),
                )}
            </nav>

            {/* key: the field shows the new page number after every page change */}
            <PageJump
                key={current}
                current={current}
                last={last}
                hrefFor={hrefFor}
            />
        </div>
    );
}

function PageJump({
    current,
    last,
    hrefFor,
}: {
    current: number;
    last: number;
    hrefFor: (page: number) => string;
}) {
    const [value, setValue] = useState(String(current));

    const go = (e: FormEvent) => {
        e.preventDefault();

        // Keep the number between 1 and the last page (e.g. 99 -> last page).
        const typed = Math.trunc(Number(value));
        const page = Math.min(
            Math.max(Number.isFinite(typed) ? typed : 1, 1),
            last,
        );

        setValue(String(page));

        if (page !== current) {
            router.get(hrefFor(page), {}, { preserveScroll: true });
        }
    };

    return (
        <form className="ad-jump" onSubmit={go}>
            <label htmlFor="ad-jump-page">Go to page</label>
            <input
                id="ad-jump-page"
                className="ad-input"
                type="number"
                inputMode="numeric"
                min={1}
                max={last}
                value={value}
                onChange={(e) => setValue(e.target.value)}
            />
            <span className="ad-faint">of {last}</span>
            <button type="submit" className="ad-btn ad-btn-sm">
                Go
            </button>
        </form>
    );
}
