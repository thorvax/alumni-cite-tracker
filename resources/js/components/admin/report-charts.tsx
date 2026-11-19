import type { PropsWithChildren } from 'react';
import { cn } from '@/lib/utils';

/*
 * Small SVG/CSS charts for the admin Reports page (no chart library).
 * Every chart shows numbers as text (count and percent) as well as color,
 * and each series also has its own pattern (see .ad-s-* in admin.css).
 */

export type Item = { label: string; count: number; percent: number };
export type Breakdown = { total: number; items: Item[] };
export type StackRow = Breakdown & { label: string };

/** Which color + pattern a label gets. Unknown labels use the plain bar color. */
const SERIES: Record<string, string> = {
    Employed: 'employed',
    'Self-employed': 'self',
    Unemployed: 'unemployed',
    'Further Studies': 'further',
    Yes: 'employed',
    No: 'unemployed',
    Local: 'employed',
    Abroad: 'further',
};

const seriesOf = (label: string) => SERIES[label] ?? 'default';

export const countText = (item: { count: number; percent: number }) =>
    `${item.count} (${item.percent}%)`;

export function ChartCard({
    title,
    note,
    empty = false,
    wide = false,
    children,
}: PropsWithChildren<{
    title: string;
    note?: string;
    empty?: boolean;
    wide?: boolean;
}>) {
    return (
        <section className={cn('ad-card ad-chart', wide && 'ad-chart-wide')}>
            <h2 className="ad-card-title">{title}</h2>
            {note && <p className="ad-chart-note">{note}</p>}
            {empty ? <p className="ad-chart-empty">No data yet</p> : children}
        </section>
    );
}

function Swatch({ label }: { label: string }) {
    return <i className={`ad-swatch ad-s-${seriesOf(label)}`} aria-hidden />;
}

export function Legend({ items }: { items: Item[] }) {
    return (
        <ul className="ad-chart-legend">
            {items.map((item) => (
                <li key={item.label}>
                    <Swatch label={item.label} />
                    <span>{item.label}</span>
                    <span className="ad-faint">{countText(item)}</span>
                </li>
            ))}
        </ul>
    );
}

const RADIUS = 54;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

/** Donut with the total in the middle and a legend with counts. */
export function Donut({
    data,
    centerValue,
    centerLabel,
}: {
    data: Breakdown;
    centerValue: string;
    centerLabel: string;
}) {
    let offset = 0;

    return (
        <div className="ad-donut">
            <div className="ad-donut-wrap">
                <svg
                    viewBox="0 0 142 142"
                    width="142"
                    height="142"
                    role="img"
                    aria-label={data.items
                        .map((i) => `${i.label}: ${countText(i)}`)
                        .join(', ')}
                >
                    <circle
                        cx="71"
                        cy="71"
                        r={RADIUS}
                        fill="none"
                        strokeWidth="24"
                        style={{ stroke: 'var(--ad-bar-track)' }}
                    />
                    {data.items.map((item) => {
                        if (item.count === 0) {
                            return null;
                        }

                        const length =
                            (item.count / data.total) * CIRCUMFERENCE;
                        const segment = (
                            <circle
                                key={item.label}
                                cx="71"
                                cy="71"
                                r={RADIUS}
                                fill="none"
                                strokeWidth="24"
                                strokeDasharray={`${Math.max(length - 2, 0)} ${CIRCUMFERENCE}`}
                                strokeDashoffset={-offset}
                                transform="rotate(-90 71 71)"
                                style={{
                                    stroke: `var(--ad-c-${seriesOf(item.label)}, var(--ad-bar-fill))`,
                                }}
                            />
                        );

                        offset += length;

                        return segment;
                    })}
                </svg>
                <div className="ad-donut-center">
                    <span className="ad-donut-total">{centerValue}</span>
                    <span className="ad-donut-label">{centerLabel}</span>
                </div>
            </div>

            <Legend items={data.items} />
        </div>
    );
}

/** One horizontal stacked bar per row (e.g. per course), with a shared legend. */
export function StackedBars({ rows }: { rows: StackRow[] }) {
    return (
        <div className="ad-stack">
            {rows.map((row) => (
                <div key={row.label} className="ad-stack-row">
                    <div className="ad-stack-head">
                        <strong>{row.label}</strong>
                        <span className="ad-faint">{row.total} total</span>
                    </div>
                    <div
                        className="ad-stack-bar"
                        role="img"
                        aria-label={`${row.label}: ${row.items
                            .map((i) => `${i.label} ${countText(i)}`)
                            .join(', ')}`}
                    >
                        {row.items
                            .filter((item) => item.count > 0)
                            .map((item) => (
                                <span
                                    key={item.label}
                                    className={`ad-s-${seriesOf(item.label)}`}
                                    style={{ width: `${item.percent}%` }}
                                    title={`${item.label}: ${countText(item)}`}
                                >
                                    {item.percent >= 12 && (
                                        <em>{item.percent}%</em>
                                    )}
                                </span>
                            ))}
                    </div>
                </div>
            ))}

            {rows[0] && <Legend items={legendTotals(rows)} />}
        </div>
    );
}

/** Legend under stacked bars: totals across all rows. */
function legendTotals(rows: StackRow[]): Item[] {
    const total = rows.reduce((sum, row) => sum + row.total, 0);

    return rows[0].items.map(({ label }) => {
        const count = rows.reduce(
            (sum, row) =>
                sum + (row.items.find((i) => i.label === label)?.count ?? 0),
            0,
        );

        return {
            label,
            count,
            percent: total > 0 ? Math.round((count / total) * 100) : 0,
        };
    });
}

/** Horizontal bars, one per answer, with "count (percent%)". */
export function BarList({ items }: { items: Item[] }) {
    return (
        <ul className="ad-barlist">
            {items.map((item) => (
                <li key={item.label}>
                    <div className="ad-barlist-head">
                        <span>{item.label}</span>
                        <span className="ad-faint">{countText(item)}</span>
                    </div>
                    <div className="ad-bar" aria-hidden>
                        <i style={{ width: `${item.percent}%` }} />
                    </div>
                </li>
            ))}
        </ul>
    );
}

/** Columns per year: height = employment rate, with the numbers written on top. */
export function YearColumns({
    points,
}: {
    points: {
        year: number;
        total: number;
        employed: number;
        percent: number;
    }[];
}) {
    return (
        <div className="ad-columns-scroll">
            <ol className="ad-columns">
                {points.map((point) => (
                    <li
                        key={point.year}
                        aria-label={`${point.year}: ${point.percent}% employed (${point.employed} of ${point.total})`}
                    >
                        <span className="ad-col-value">{point.percent}%</span>
                        <span className="ad-faint ad-col-count">
                            {point.employed}/{point.total}
                        </span>
                        <span className="ad-col-track" aria-hidden>
                            <span
                                className="ad-s-employed"
                                style={{ height: `${point.percent}%` }}
                            />
                        </span>
                        <span className="ad-col-year">{point.year}</span>
                    </li>
                ))}
            </ol>
        </div>
    );
}

/** Funnel: each stage as a bar sized against the first stage. */
export function Funnel({ stages }: { stages: Item[] }) {
    return (
        <ol className="ad-funnel">
            {stages.map((stage) => (
                <li key={stage.label}>
                    <span className="ad-funnel-label">{stage.label}</span>
                    <span className="ad-funnel-track" aria-hidden>
                        <span
                            style={{ width: `${Math.max(stage.percent, 1)}%` }}
                        />
                    </span>
                    <span className="ad-faint">{countText(stage)}</span>
                </li>
            ))}
        </ol>
    );
}

/** A big number with a caption, e.g. "75% employment rate". */
export function BigStat({
    value,
    caption,
}: {
    value: string;
    caption: string;
}) {
    return (
        <p className="ad-bigstat">
            <strong>{value}</strong>
            <span>{caption}</span>
        </p>
    );
}

/** Written answers: how many there are, and the latest ones (text only, no names). */
export type TextAnswers = { total: number; latest: string[] };

/** Full-width heading that groups the cards (Employment, Problems and Gaps...). */
export function ReportSection({ title }: { title: string }) {
    return <h2 className="ad-report-section">{title}</h2>;
}

export function TextList({
    title,
    answers,
}: {
    title?: string;
    answers: TextAnswers;
}) {
    return (
        <div>
            {title && (
                <h3 className="ad-chart-sub">
                    {title} ({answers.total})
                </h3>
            )}
            {answers.latest.length === 0 ? (
                <p className="ad-chart-empty">No data yet</p>
            ) : (
                <ul className="ad-textlist">
                    {answers.latest.map((text, i) => (
                        <li key={i}>{text}</li>
                    ))}
                </ul>
            )}
        </div>
    );
}
