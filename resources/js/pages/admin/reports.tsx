import { Head, router } from '@inertiajs/react';
import { Download, Printer } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import {
    BarList,
    BigStat,
    ChartCard,
    Donut,
    Funnel,
    RateList,
    ReportSection,
    StackedBars,
    TextList,
    YearColumns,
} from '@/components/admin/report-charts';
import type {
    Breakdown,
    Item,
    RateRow,
    StackRow,
    TextAnswers,
} from '@/components/admin/report-charts';
import AdminLayout from '@/layouts/admin-layout';

type Filters = { course: string; year: string };

type Props = {
    filters: Filters;
    options: { courses: string[]; years: number[] };
    status: Breakdown & { rate: { count: number; percent: number } };
    byCourse: StackRow[];
    byYear: {
        year: number;
        total: number;
        employed: number;
        percent: number;
    }[];
    related: { overall: Breakdown; courses: StackRow[] };
    timeToLand: Breakdown;
    sector: { sector: Breakdown; place: Breakdown };
    earning: Breakdown;
    howFound: Breakdown;
    competencies: Breakdown;
    curriculum: { relevant: Breakdown; suggestions: TextAnswers };
    notEmployed: Breakdown;
    helpNeeded: TextAnswers;
    courseReasons: Breakdown;
    funnel: {
        uploaded: number;
        rate: { count: number; percent: number };
        stages: Item[];
        byCourse: RateRow[];
        byYear: RateRow[];
        noCourse: number;
        noYear: number;
    };
};

/** Only the filters that are set, e.g. { course: 'BSIT' }. */
const query = (filters: Filters) =>
    Object.fromEntries(Object.entries(filters).filter(([, value]) => value));

export default function Reports({
    filters,
    options,
    status,
    byCourse,
    byYear,
    related,
    timeToLand,
    sector,
    earning,
    howFound,
    competencies,
    curriculum,
    notEmployed,
    helpNeeded,
    courseReasons,
    funnel,
}: Props) {
    const [selected, setSelected] = useState<Filters>(filters);

    const apply = (e: FormEvent) => {
        e.preventDefault();
        router.get('/reports', query(selected), { preserveScroll: true });
    };

    const exportUrl = `/reports/export?${new URLSearchParams(query(filters)).toString()}`;

    const scope = `${filters.course || 'All courses'} · ${filters.year ? `Graduated ${filters.year}` : 'All years'}`;

    return (
        <>
            <Head title="Reports" />

            <AdminLayout
                active="reports"
                page="reports"
                title="Reports"
                subtitle="Graduate tracer statistics. Counts only; no names are shown."
                actions={
                    <div className="ad-report-actions">
                        <form className="ad-report-filters" onSubmit={apply}>
                            <label>
                                Course
                                <select
                                    className="ad-select"
                                    value={selected.course}
                                    onChange={(e) =>
                                        setSelected({
                                            ...selected,
                                            course: e.target.value,
                                        })
                                    }
                                >
                                    <option value="">All</option>
                                    {options.courses.map((code) => (
                                        <option key={code} value={code}>
                                            {code}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label>
                                Year graduated
                                <select
                                    className="ad-select"
                                    value={selected.year}
                                    onChange={(e) =>
                                        setSelected({
                                            ...selected,
                                            year: e.target.value,
                                        })
                                    }
                                >
                                    <option value="">All</option>
                                    {options.years.map((y) => (
                                        <option key={y} value={String(y)}>
                                            {y}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <button
                                type="submit"
                                className="ad-btn ad-btn-solid"
                            >
                                Apply
                            </button>
                        </form>

                        <a href={exportUrl} className="ad-btn">
                            <Download size={14} aria-hidden /> Export CSV
                        </a>
                        <button
                            type="button"
                            className="ad-btn"
                            onClick={() => window.print()}
                        >
                            <Printer size={14} aria-hidden /> Print
                        </button>
                    </div>
                }
            >
                <p className="ad-report-scope">
                    Showing: <strong>{scope}</strong>
                    <span className="ad-print-only">
                        {' '}
                        · Printed {new Date().toLocaleDateString()}
                    </span>
                </p>

                <div className="ad-report-grid">
                    <ReportSection title="Employment" />

                    {/* 1 */}
                    <ChartCard
                        title="Employment Status"
                        note="Everyone with a known status."
                        empty={status.total === 0}
                    >
                        <BigStat
                            value={`${status.rate.percent}%`}
                            caption={`employment rate (${status.rate.count} of ${status.total} employed or self-employed)`}
                        />
                        <Donut
                            data={status}
                            centerValue={String(status.total)}
                            centerLabel="Graduates"
                        />
                    </ChartCard>

                    {/* 2 */}
                    <ChartCard
                        title="Employment by Course"
                        empty={byCourse.length === 0}
                    >
                        <StackedBars rows={byCourse} />
                    </ChartCard>

                    {/* 3 */}
                    <ChartCard
                        title="Employment Rate by Year Graduated"
                        note="Percent employed or self-employed (employed / total)."
                        empty={byYear.length === 0}
                    >
                        <YearColumns points={byYear} />
                    </ChartCard>

                    {/* 4 */}
                    <ChartCard
                        title="Is the Job Related to the Course?"
                        note="Survey respondents with a job (first or current job)."
                        empty={related.overall.total === 0}
                    >
                        <BigStat
                            value={`${related.overall.items[0]?.percent ?? 0}%`}
                            caption={`say their job is related (${related.overall.items[0]?.count ?? 0} of ${related.overall.total})`}
                        />
                        <StackedBars rows={related.courses} />
                    </ChartCard>

                    {/* 5 */}
                    <ChartCard
                        title="Time to Land the First Job"
                        note="Respondents whose current job is their first job."
                        empty={timeToLand.total === 0}
                    >
                        <BarList items={timeToLand.items} />
                    </ChartCard>

                    <ReportSection title="Jobs, Pay and Skills" />

                    {/* 6 */}
                    <ChartCard
                        title="Sector and Place of Work"
                        note="Respondents who are presently employed."
                        empty={sector.sector.total === 0}
                    >
                        <BarList items={sector.sector.items} />
                        <h3 className="ad-chart-sub">Local vs abroad</h3>
                        <StackedBars
                            rows={[{ label: 'Place of work', ...sector.place }]}
                        />
                    </ChartCard>

                    {/* 7 */}
                    <ChartCard
                        title="First Job Earning"
                        note="Monthly earning range of respondents on their first job. Grouped counts only."
                        empty={earning.total === 0}
                    >
                        <BarList items={earning.items} />
                    </ChartCard>

                    {/* 8 */}
                    <ChartCard
                        title="How They Found Their First Job"
                        note="Most common first."
                        empty={howFound.total === 0}
                    >
                        <BarList items={howFound.items} />
                    </ChartCard>

                    {/* 9 */}
                    <ChartCard
                        title="Competencies Used on the Job"
                        note={`Of ${competencies.total} respondent(s); each could pick more than one, so percentages add up to more than 100%.`}
                        empty={competencies.total === 0}
                    >
                        <BarList items={competencies.items} />
                    </ChartCard>

                    {/* 10 */}
                    <ChartCard
                        title="Curriculum Relevance"
                        note="Was the curriculum relevant to their first job?"
                        empty={
                            curriculum.relevant.total === 0 &&
                            curriculum.suggestions.total === 0
                        }
                        wide
                    >
                        <div className="ad-report-split">
                            <div>
                                {curriculum.relevant.total > 0 ? (
                                    <StackedBars
                                        rows={[
                                            {
                                                label: 'Relevant to the first job',
                                                ...curriculum.relevant,
                                            },
                                        ]}
                                    />
                                ) : (
                                    <p className="ad-chart-empty">
                                        No data yet
                                    </p>
                                )}
                            </div>
                            <TextList
                                title="Latest curriculum suggestions"
                                answers={curriculum.suggestions}
                            />
                        </div>
                    </ChartCard>

                    <ReportSection title="Problems and Gaps" />

                    {/* 11 */}
                    <ChartCard
                        title="Reasons for Not Being Employed"
                        note={`Of ${notEmployed.total} respondent(s) not employed; each could pick more than one.`}
                        empty={notEmployed.total === 0}
                    >
                        <BarList items={notEmployed.items} />
                    </ChartCard>

                    {/* 13 */}
                    <ChartCard
                        title="Why They Chose the Course"
                        note={`Of ${courseReasons.total} respondent(s); each could pick more than one.`}
                        empty={courseReasons.total === 0}
                    >
                        <BarList items={courseReasons.items} />
                    </ChartCard>

                    {/* 12 */}
                    <ChartCard
                        title="Help Needed in Landing a Job"
                        note={`${helpNeeded.total} respondent(s) answered. Latest answers, without names.`}
                        empty={helpNeeded.total === 0}
                        wide
                    >
                        <TextList answers={helpNeeded} />
                    </ChartCard>

                    <ReportSection title="Survey Progress" />

                    {/* 14 */}
                    <ChartCard
                        title="Response Funnel"
                        note="Graduates added through Bulk Upload."
                        empty={funnel.uploaded === 0}
                        wide
                    >
                        <BigStat
                            value={`${funnel.rate.percent}%`}
                            caption={`response rate (${funnel.rate.count} of ${funnel.uploaded} answered)`}
                        />
                        <Funnel stages={funnel.stages} />

                        <div className="ad-report-split">
                            <div>
                                <h3 className="ad-chart-sub">
                                    Response rate by course
                                </h3>
                                <RateList rows={funnel.byCourse} />
                                {funnel.noCourse > 0 && (
                                    <p className="ad-chart-note">
                                        {funnel.noCourse} uploaded without a
                                        course (not answered yet).
                                    </p>
                                )}
                            </div>
                            <div>
                                <h3 className="ad-chart-sub">
                                    Response rate by year graduated
                                </h3>
                                <RateList rows={funnel.byYear} />
                                {funnel.noYear > 0 && (
                                    <p className="ad-chart-note">
                                        {funnel.noYear} uploaded without a year
                                        (not answered yet).
                                    </p>
                                )}
                            </div>
                        </div>

                        <p className="ad-chart-note">
                            Tip: fill in the optional Course and Year Graduated
                            columns when uploading, so everyone is counted here
                            even before they answer.
                        </p>
                    </ChartCard>
                </div>
            </AdminLayout>
        </>
    );
}
