import { Link, router, usePage } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import { pageItems } from '@/lib/admin';

/** Page links that keep the current search and filters, plus a "Go to page" field. */
export default function Pagination({
    current,
    last,
    param = 'page',
    label = 'Pagination',
}: {
    current: number;
    last: number;
    /** Query string name, when a page has two lists (e.g. "no_email_page"). */
    param?: string;
    /** Accessible name, so two paginations on one page can be told apart. */
    label?: string;
}) {
    const { url } = usePage();

    const hrefFor = (page: number) => {
        const next = new URL(url, 'http://localhost');
        next.searchParams.set(param, String(page));

        return `${next.pathname}${next.search}`;
    };

    return (
        <div className="ad-pager">
            <nav className="ad-pages" aria-label={label}>
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
                id={`ad-jump-${param}`}
                current={current}
                last={last}
                hrefFor={hrefFor}
            />
        </div>
    );
}

function PageJump({
    id,
    current,
    last,
    hrefFor,
}: {
    id: string;
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
            <label htmlFor={id}>Go to page</label>
            <input
                id={id}
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
