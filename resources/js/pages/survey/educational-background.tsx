
import SectionHeading from '@/components/survey/section-heading';
import Field, { inputClass } from '@/components/survey/input-field';
import { Head, useForm } from '@inertiajs/react';
import SurveyLayout from '@/layouts/survey-layout';
import { Check } from 'lucide-react';

type EducationalBackgroundData = {
    course: string;
    year_graduated: string;
    exams: string[];
    date_taken: string;
    rating: string;

};

const courses = [
    'Bachelor of Science in Information Technology',
    'Bachelor of Science in Computer Science',
];

const currentYear = new Date().getFullYear();

const years = Array.from({ length: currentYear - 1966 + 1 }, (_, i) => currentYear - i);

const exams = [
    { value: 'let', label: 'Licensure Examination for Teachers' },
    { value: 'csc_professional', label: 'CSC Professional' },
    { value: 'csc_sub_professional', label: 'CSC Sub-Professional' },
];

const toggle = (list: string[], value: string) => {
    if (list.includes(value)) {
        return list.filter((item) => item !== value);
    }

    return [...list, value];
};






const colsGrid2 = 'mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-5';




export default function EducationalBackground({ draft, }: { draft: Partial<EducationalBackgroundData> }) {

    const { data, setData, errors } = useForm<EducationalBackgroundData>({
        course: draft.course ?? "",
        year_graduated: draft.year_graduated ?? "",
        exams: draft.exams ?? [],
        date_taken: draft.date_taken ?? "null",
        rating: draft.rating ?? "0.0%",
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

                    <Field id={'course'} label={'1.1 Course'} required error={errors.course}>
                        <select
                            id='course'
                            className={inputClass}
                            value={data.course}
                            onChange={(e) => { setData("course", e.target.value); }}
                        >
                            <option value={""}>-- Select --</option>
                            {courses.map((c) => (
                                <option key={c} value={c}>{c}</option>
                            ))}

                        </select>
                    </Field>

                    <Field id='year_graduated' label='1.2 Year Graduated' required error={errors.year_graduated}>
                        <select
                            id='year_graduated'
                            className={inputClass}
                            value={data.year_graduated}
                            onChange={(e) => { setData("year_graduated", e.target.value) }} >
                            <option value="">-- Select --</option>
                            {
                                years.map((c) => (
                                    <option value={c} key={c}>{c}</option>
                                ))
                            }

                        </select>

                    </Field>
                </div>
                <SectionHeading>2. PROFESSIONAL EXAMINATION PASSED</SectionHeading>
                <fieldset className='mt-4'>
                    <legend className="mb-2 text-[13px] font-medium text-white">
                        2.1 Highest Professional Examination / Eligibility Passed
                    </legend>
                    <div className='space-y-2.5'>
                        {exams.map((exam) => (
                            <label key={exam.value} className="flex cursor-pointer items-center gap-2.5 text-sm text-white/80">
                                <input value={exam.value}
                                    type='checkbox'
                                    checked={data.exams.includes(exam.value)}
                                    onChange={() => { setData("exams", toggle(data.exams, exam.value)) }} ></input>
                                {exam.label}
                            </label>
                        ))}
                    </div>
                </fieldset>

                <div className={colsGrid2}>
                    <Field
                        id="date_taken"
                        label="2.2 Date Taken"
                        required
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
                        id='rating'
                        label='Rating'
                        required
                        error={errors.rating}>
                        <input
                            type="text"
                            id='rating'
                            className={inputClass}
                            value={data.rating}
                            onChange={(e) => setData('rating', e.target.value)}
                            placeholder='e.g 82.00%'
                        />
                    </Field>
                </div>
                <p>{JSON.stringify(data)}</p>
                <div className=' my-5'>
                    <SectionHeading>3. REASON(S) FOR TAKING THE COURSE(S) OR PURSUING DEGREE(S)</SectionHeading>
                </div>

            </SurveyLayout>
        </>
    );

}
