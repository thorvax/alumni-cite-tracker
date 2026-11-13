import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    CheckGroup,
    Field,
    Question,
    SelectInput,
    SubHeading,
    TextInput,
} from '@/components/survey/form-controls';
import SurveyLayout, { SURVEY_FORM_ID } from '@/layouts/survey-layout';
import { stepUrl, toggle, withDefaults } from '@/lib/survey';
import {
    courseReasons,
    courses,
    exams,
    graduationYears,
} from '@/lib/survey-options';

type Data = {
    course: string;
    year_graduated: string;
    exams: string[];
    exam_date: string;
    exam_rating: string;
    reasons_ug: string[];
    reasons_grad: string[];
};

const defaults: Data = {
    course: '',
    year_graduated: '',
    exams: [],
    exam_date: '',
    exam_rating: '',
    reasons_ug: [],
    reasons_grad: [],
};

export default function EducationalBackground({
    draft,
}: {
    draft: Partial<Data>;
}) {
    const { data, setData, post, processing, errors } = useForm<Data>(
        withDefaults(defaults, draft),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(stepUrl('educational-background'));
    };

    return (
        <>
            <Head title="Educational Background" />

            <SurveyLayout
                step={2}
                page="education"
                title="B. Educational Background"
                backHref={stepUrl('general-information')}
                processing={processing}
            >
                <form id={SURVEY_FORM_ID} onSubmit={submit} noValidate>
                    <section className="sv-section">
                        <SubHeading>1. Educational Attainment</SubHeading>

                        <div className="sv-grid">
                            <Field
                                id="course"
                                label="1.1 Course"
                                required
                                error={errors.course}
                            >
                                <SelectInput
                                    id="course"
                                    options={courses}
                                    value={data.course}
                                    onChange={(e) =>
                                        setData('course', e.target.value)
                                    }
                                />
                            </Field>

                            <Field
                                id="year_graduated"
                                label="1.2 Year Graduated"
                                required
                                error={errors.year_graduated}
                            >
                                <SelectInput
                                    id="year_graduated"
                                    options={graduationYears}
                                    value={data.year_graduated}
                                    onChange={(e) =>
                                        setData(
                                            'year_graduated',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                    </section>

                    <section className="sv-section">
                        <SubHeading>
                            2. Professional Examination Passed
                        </SubHeading>

                        <Question
                            label="2.1 Highest Professional Examination / Eligibility Passed"
                            error={errors.exams}
                        >
                            <CheckGroup
                                stack
                                options={exams}
                                values={data.exams}
                                onToggle={(value) =>
                                    setData('exams', toggle(data.exams, value))
                                }
                            />
                        </Question>

                        <div className="sv-grid" style={{ marginTop: 18 }}>
                            <Field
                                id="exam_date"
                                label="2.2 Date Taken"
                                error={errors.exam_date}
                            >
                                <TextInput
                                    id="exam_date"
                                    type="date"
                                    value={data.exam_date}
                                    onChange={(e) =>
                                        setData('exam_date', e.target.value)
                                    }
                                />
                            </Field>

                            <Field
                                id="exam_rating"
                                label="2.3 Rating"
                                error={errors.exam_rating}
                            >
                                <TextInput
                                    id="exam_rating"
                                    inputMode="decimal"
                                    placeholder="e.g. 82.00%"
                                    value={data.exam_rating}
                                    onChange={(e) =>
                                        setData('exam_rating', e.target.value)
                                    }
                                />
                            </Field>
                        </div>
                    </section>

                    <section className="sv-section">
                        <SubHeading>
                            3. Reason(s) for taking the course(s) or pursuing
                            degree(s)
                        </SubHeading>

                        <p className="sv-hint">
                            You may select more than one answer. Check all that
                            apply for each level.
                        </p>

                        <div className="sv-reasons">
                            {courseReasons.map((reason) => (
                                <div key={reason.key} className="sv-reason">
                                    <span>{reason.label}</span>

                                    <div className="sv-reason-checks">
                                        <label>
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
                                        <label>
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
                    </section>
                </form>
            </SurveyLayout>
        </>
    );
}
