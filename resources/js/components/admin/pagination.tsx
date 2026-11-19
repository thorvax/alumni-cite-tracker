import { Link, usePage } from '@inertiajs/react';
import { pageItems } from '@/lib/admin';

/** Page links that keep the current search and filters. */
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
    );
}
