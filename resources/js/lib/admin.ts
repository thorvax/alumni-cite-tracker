export type GraduateRow = {
    id: number;
    name: string;
    course: string | null;
    year: number | null;
    status: string | null;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

/** Page numbers to show, with '…' where pages are skipped: 1 2 3 … 32 */
export function pageItems(current: number, last: number): (number | '…')[] {
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }

    const wanted = [1, 2, 3, current - 1, current, current + 1, last]
        .filter((n) => n >= 1 && n <= last)
        .sort((a, b) => a - b)
        .filter((n, i, all) => all.indexOf(n) === i);

    const items: (number | '…')[] = [];

    wanted.forEach((n, i) => {
        if (i > 0 && n - wanted[i - 1] > 1) {
            items.push('…');
        }

        items.push(n);
    });

    return items;
}
