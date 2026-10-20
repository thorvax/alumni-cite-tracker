import SectionHeading from '@/components/survey/section-heading';
import Field, { inputClass } from '@/components/survey/input-field';
import { Head, useForm } from '@inertiajs/react';
import SurveyLayout from '@/layouts/survey-layout';
// import { Check } from 'lucide-react';

type EducationalBackgroundData = {
    course: string;
    year_graduated: string;
    exams: string[];
    date_taken: string;
    rating: string;
    reasons_ug: string[];
    reasons_grad: string[];
};

const courses = [
    'Bachelor of Science in Information Technology',
    'Bachelor of Science in Computer Science',
];

const currentYear = new Date().getFullYear();

const years = Array.from(
    { length: currentYear - 1966 + 1 },
    (_, i) => currentYear - i,
);

const exams = [
    { value: 'let', label: 'Licensure Examination for Teachers' },
    { value: 'csc_professional', label: 'CSC Professional' },
    { value: 'csc_sub_professional', label: 'CSC Sub-Professional' },
];

const reasons = [
    {
        key: 'high_grades_course',
        label: 'High grades in the course or subject area(s) related to the course',
    },
    { key: 'good_grades_hs', label: 'Good grades in high school' },
    { key: 'parents_relatives', label: 'Influence of parents or relatives' },
    { key: 'peer_influence', label: 'Peer Influence' },
    { key: 'role_model', label: 'Inspired by a role model' },
    { key: 'passion', label: 'Strong passion for the profession' },
    { key: 'immediate_employment', label: 'Prospect for immediate employment' },
    { key: 'prestige', label: 'Status or prestige of the profession' },
    {
        key: 'course_availability',
        label: 'Availability of course offering in chosen institution',
    },
    { key: 'career_advancement', label: 'Prospect of career advancement' },
    { key: 'affordable', label: 'Affordable for the family' },
    { key: 'compensation', label: 'Prospect of attractive compensation' },
    { key: 'abroad', label: 'Opportunity for employment abroad' },
    { key: 'no_choice', label: 'No particular choice or no better idea' },
];

const toggle = (list: string[], value: string) => {
    if (list.includes(value)) {
        return list.filter((item) => item !== value);
    }

    return [...list, value];
};

const colsGrid2 = 'mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-5';

export default function EducationalBackground({
    draft,
}: {
    draft: Partial<EducationalBackgroundData>;
}) {
    const { data, setData, errors } = useForm<EducationalBackgroundData>({
        course: draft.course ?? '',
        year_graduated: draft.year_graduated ?? '',
        exams: draft.exams ?? [],
        date_taken: draft.date_taken ?? 'null',
        rating: draft.rating ?? '0.0%',
        reasons_ug: draft.reasons_ug ?? [],
        reasons_grad: draft.reasons_grad ?? [],
    });

    return (
        <>
            <Head title="Educational Background" />

            <SurveyLayout
                current={2}
                title="B. Educational Background"
                backHref="/survey/general-information"
                description={''}
            >
                <SectionHeading>1. EDUCATIONAL ATTAINMENT</SectionHeading>
                <div className={colsGrid2}>
                    <Field
                        id={'course'}
                        label={'1.1 Course'}
                        required
                        error={errors.course}
                    >
                        <select
                            id="course"
                            className={inputClass}
                            value={data.course}
                            onChange={(e) => {
                                setData('course', e.target.value);
                            }}
                        >
                            <option value={''}>-- Select --</option>
                            {courses.map((c) => (
                                <option key={c} value={c}>
                                    {c}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field
                        id="year_graduated"
                        label="1.2 Year Graduated"
                        required
                        error={errors.year_graduated}
                    >
                        <select
                            id="year_graduated"
                            className={inputClass}
                            value={data.year_graduated}
                            onChange={(e) => {
                                setData('year_graduated', e.target.value);
                            }}
                        >
                            <option value="">-- Select --</option>
                            {years.map((c) => (
                                <option value={c} key={c}>
                                    {c}
                                </option>
                            ))}
                        </select>
                    </Field>
                </div>
                <SectionHeading>
                    2. PROFESSIONAL EXAMINATION PASSED
                </SectionHeading>
                <fieldset className="mt-4">
                    <legend className="mb-2 text-[13px] font-medium text-white">
                        2.1 Highest Professional Examination / Eligibility
                        Passed
                    </legend>
                    <div className="space-y-2.5">
                        {exams.map((exam) => (
                            <label
                                key={exam.value}
                                className="flex cursor-pointer items-center gap-2.5 text-sm text-white/80"
                            >
                                <input
                                    value={exam.value}
                                    type="checkbox"
                                    checked={data.exams.includes(exam.value)}
                                    onChange={() => {
                                        setData(
                                            'exams',
                                            toggle(data.exams, exam.value),
                                        );
                                    }}
                                ></input>
                                {exam.label}
                            </label>
                        ))}
                    </div>
                </fieldset>

                <div className={colsGrid2}>
                    <Field
                        id="date_taken"
                        label="2.2 Date Taken"
                        error={errors.date_taken}
                    >
                        <input
                            id="date_taken"
                            type="date"
                            className={inputClass}
                            value={data.date_taken}
                            onChange={(e) =>
                                setData('date_taken', e.target.value)
                            }
                        />
                    </Field>
                    <Field
                        id="rating"
                        label="Rating"
                        required
                        error={errors.rating}
                    >
                        <input
                            type="number"
                            id="rating"
                            className={inputClass}
                            value={data.rating}
                            onChange={(e) => setData('rating', e.target.value)}
                            placeholder="e.g 82.00%"
                        />
                    </Field>
                </div>
                {/* <p>{JSON.stringify(data)}</p> */}

                <div className="my-5">
                    <SectionHeading>
                        3. REASON(S) FOR TAKING THE COURSE(S) OR PURSUING
                        DEGREE(S)
                    </SectionHeading>
                </div>

                <fieldset className="mt-4">
                    <legend className="mb-3 text-[13px] text-white">
                        You may select more than one answer. Check all that
                        apply for each level.
                    </legend>

                    <div
                        className="grid grid-cols-1 gap-px overflow-hidden rounded-sm border border-white/30 bg-white/25 md:grid-cols-2"
                    >
                        {reasons.map((reason) => (
                            <div
                                key={reason.key}
                                className="flex items-center justify-between gap-3 bg-[#2b2b2b] px-3 py-2.5"
                            >
                                <span className="text-[13px] text-white/75">
                                    {reason.label}
                                </span>

                                <div
                                    className="flex shrink-0 gap-3 [&>label]:flex [&>label]:cursor-pointer [&>label]:flex-col [&>label]:items-center [&>label]:gap-1 [&>label]:text-[10px] [&>label]:text-white/70"
                                >
                                    <label className="">
                                        <input
                                            type="checkbox"
                                            aria-label={`${reason.label} (undergraduate)`}
                                            checked={data.reasons_ug.includes(
                                                reason.key,
                                            )}
                                            onChange={() =>
                                                setData(
                                                    'reasons_ug',
                                                    toggle(
                                                        data.reasons_ug,
                                                        reason.key,
                                                    ),
                                                )
                                            }
                                        />
                                        UG
                                    </label>

                                    <label className="">
                                        <input
                                            type="checkbox"
                                            aria-label={`${reason.label} (graduate)`}
                                            checked={data.reasons_grad.includes(
                                                reason.key,
                                            )}
                                            onChange={() =>
                                                setData(
                                                    'reasons_grad',
                                                    toggle(
                                                        data.reasons_grad,
                                                        reason.key,
                                                    ),
                                                )
                                            }
                                        />
                                        Grad
                                    </label>
                                </div>
                            </div>
                        ))}
                    </div>
                    <button
                        type="button"
                        className="mt-2 rounded-md bg-[#f0f0f0] px-3 py-2 text-sm font-semibold text-black transition-colors hover:bg-white focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:outline-none disabled:opacity-60"

                        onClick={() => {
                            setData('reasons_ug', []);
                            setData('reasons_grad', []);
                        }}
                    >
                        Clear selections
                    </button>
                </fieldset>
            </SurveyLayout>
        </>
    );
}
