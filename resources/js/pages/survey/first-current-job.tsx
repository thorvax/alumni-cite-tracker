import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    CheckGroup,
    Field,
    Question,
    RadioGroup,
    SelectInput,
    TextArea,
} from '@/components/survey/form-controls';
import SurveyLayout, { SURVEY_FORM_ID } from '@/layouts/survey-layout';
import { stepUrl, toggle, withDefaults } from '@/lib/survey';
import {
    competencies,
    earnings,
    howFound,
    jobLevels,
    jobReasons,
    sectors,
    timeStayed,
    timeToLand,
    withOther,
    yesNo,
} from '@/lib/survey-options';

type Data = {
    // first job
    first_related: string;
    first_reasons: string[];
    first_reason_other: string;
    first_time_to_land: string;
    first_time_to_land_other: string;
    first_level: string;
    first_level_other: string;
    first_sector: string;
    first_sector_other: string;
    first_how_found: string;
    first_how_found_other: string;
    first_earning: string;
    curriculum_relevant: string;
    competencies: string[];
    competencies_other: string;
    curriculum_suggestions: string;
    // current / new job
    current_related: string;
    current_reasons: string[];
    current_reason_other: string;
    current_stay: string;
    current_level: string;
    current_sector: string;
    current_sector_other: string;
    current_earning: string;
};

const defaults: Data = {
    first_related: '',
    first_reasons: [],
    first_reason_other: '',
    first_time_to_land: '',
    first_time_to_land_other: '',
    first_level: '',
    first_level_other: '',
    first_sector: '',
    first_sector_other: '',
    first_how_found: '',
    first_how_found_other: '',
    first_earning: '',
    curriculum_relevant: '',
    competencies: [],
    competencies_other: '',
    curriculum_suggestions: '',
    current_related: '',
    current_reasons: [],
    current_reason_other: '',
    current_stay: '',
    current_level: '',
    current_sector: '',
    current_sector_other: '',
    current_earning: '',
};

const PLACEHOLDER = '- Select -';

export default function FirstCurrentJob({
    draft,
    firstJob,
}: {
    draft: Partial<Data>;
    /** Answer to "Is this your FIRST JOB after college?" from the previous step. */
    firstJob: string | null;
}) {
    const { data, setData, post, processing, errors } = useForm<Data>(
        withDefaults(defaults, draft),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(stepUrl('first-current-job'));
    };

    const isFirstJob = firstJob === 'yes';

    return (
        <>
            <Head title="First / Current Job" />

            <SurveyLayout
                slug="first-current-job"
                page="first-job"
                title="D. First / Current Job"
                description={
                    isFirstJob
                        ? 'Tell us about your first job after college.'
                        : 'Tell us about your current or new job.'
                }
                processing={processing}
            >
                <form id={SURVEY_FORM_ID} onSubmit={submit} noValidate>
                    {isFirstJob ? (
                        <div className="sv-card sv-grid">
                            <h2 className="sv-card-title sv-col-span">
                                First Job
                            </h2>

                            <Question
                                label="1. Is your first job related to the course you took up in college?"
                                required
                                strong
                                error={errors.first_related}
                            >
                                <RadioGroup
                                    stack
                                    name="first_related"
                                    options={yesNo}
                                    value={data.first_related}
                                    onChange={(value) =>
                                        setData('first_related', value)
                                    }
                                />
                            </Question>

                            <Question
                                label="2. What were your reasons for accepting the job? (Select all that apply)"
                                strong
                                error={
                                    errors.first_reasons ??
                                    errors.first_reason_other
                                }
                            >
                                <CheckGroup
                                    stack
                                    options={jobReasons}
                                    values={data.first_reasons}
                                    onToggle={(value) =>
                                        setData(
                                            'first_reasons',
                                            toggle(data.first_reasons, value),
                                        )
                                    }
                                    otherText={data.first_reason_other}
                                    onOtherText={(value) =>
                                        setData('first_reason_other', value)
                                    }
                                    otherPlaceholder="please specify"
                                />
                            </Question>

                            {data.first_related === 'yes' && (
                                <Question
                                    label="If YES - What competencies learned in college did you find very useful in your first job?"
                                    required
                                    strong
                                    className="sv-col-span"
                                    error={
                                        errors.competencies ??
                                        errors.competencies_other
                                    }
                                >
                                    <CheckGroup
                                        stack
                                        options={competencies}
                                        values={data.competencies}
                                        onToggle={(value) =>
                                            setData(
                                                'competencies',
                                                toggle(
                                                    data.competencies,
                                                    value,
                                                ),
                                            )
                                        }
                                        otherText={data.competencies_other}
                                        onOtherText={(value) =>
                                            setData('competencies_other', value)
                                        }
                                        otherPlaceholder="please specify"
                                    />
                                </Question>
                            )}

                            {data.first_related === 'no' && (
                                <div className="sv-col-span">
                                    <p
                                        className="sv-note"
                                        style={{ marginBottom: 12 }}
                                    >
                                        Curriculum did not help in landing the
                                        first job.
                                    </p>
                                    <Field
                                        id="curriculum_suggestions"
                                        label="List down suggestions to further improve our course curriculum."
                                        required
                                        strong
                                        error={errors.curriculum_suggestions}
                                    >
                                        <TextArea
                                            id="curriculum_suggestions"
                                            placeholder="Your suggestions here..."
                                            value={data.curriculum_suggestions}
                                            onChange={(e) =>
                                                setData(
                                                    'curriculum_suggestions',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>
                            )}

                            <Question
                                label="3. How long did it take you to land your first job?"
                                required
                                strong
                                error={
                                    errors.first_time_to_land ??
                                    errors.first_time_to_land_other
                                }
                            >
                                <RadioGroup
                                    stack
                                    name="first_time_to_land"
                                    options={withOther(timeToLand)}
                                    value={data.first_time_to_land}
                                    onChange={(value) =>
                                        setData('first_time_to_land', value)
                                    }
                                    otherText={data.first_time_to_land_other}
                                    onOtherText={(value) =>
                                        setData(
                                            'first_time_to_land_other',
                                            value,
                                        )
                                    }
                                />
                            </Question>

                            <Question
                                label="4. Level Position of Your First Job"
                                required
                                strong
                                error={
                                    errors.first_level ??
                                    errors.first_level_other
                                }
                            >
                                <RadioGroup
                                    stack
                                    name="first_level"
                                    options={withOther(jobLevels)}
                                    value={data.first_level}
                                    onChange={(value) =>
                                        setData('first_level', value)
                                    }
                                    otherText={data.first_level_other}
                                    onOtherText={(value) =>
                                        setData('first_level_other', value)
                                    }
                                />
                            </Question>

                            <Question
                                label="5. Employment Sector of Your First Job"
                                required
                                strong
                                error={
                                    errors.first_sector ??
                                    errors.first_sector_other
                                }
                            >
                                <RadioGroup
                                    stack
                                    name="first_sector"
                                    options={sectors}
                                    value={data.first_sector}
                                    onChange={(value) =>
                                        setData('first_sector', value)
                                    }
                                    otherText={data.first_sector_other}
                                    onOtherText={(value) =>
                                        setData('first_sector_other', value)
                                    }
                                />
                            </Question>

                            <Question
                                label="6. How did you find your first job?"
                                required
                                strong
                                error={
                                    errors.first_how_found ??
                                    errors.first_how_found_other
                                }
                            >
                                <RadioGroup
                                    stack
                                    name="first_how_found"
                                    options={withOther(howFound)}
                                    value={data.first_how_found}
                                    onChange={(value) =>
                                        setData('first_how_found', value)
                                    }
                                    otherText={data.first_how_found_other}
                                    onOtherText={(value) =>
                                        setData('first_how_found_other', value)
                                    }
                                />
                            </Question>

                            <Field
                                id="first_earning"
                                label="7. What is your initial gross monthly earning in your first job?"
                                required
                                strong
                                error={errors.first_earning}
                            >
                                <SelectInput
                                    id="first_earning"
                                    placeholder={PLACEHOLDER}
                                    options={earnings}
                                    value={data.first_earning}
                                    onChange={(e) =>
                                        setData('first_earning', e.target.value)
                                    }
                                />
                            </Field>

                            <Question
                                label="8. Was the curriculum you had in college relevant to your first job?"
                                required
                                strong
                                error={errors.curriculum_relevant}
                            >
                                <RadioGroup
                                    stack
                                    name="curriculum_relevant"
                                    options={yesNo}
                                    value={data.curriculum_relevant}
                                    onChange={(value) =>
                                        setData('curriculum_relevant', value)
                                    }
                                />
                            </Question>
                        </div>
                    ) : (
                        <div className="sv-card sv-grid">
                            <h2 className="sv-card-title sv-col-span">
                                Current / New Job
                            </h2>

                            <Question
                                label="1. Is your current/new job related to the course you took up in college?"
                                required
                                strong
                                error={errors.current_related}
                            >
                                <RadioGroup
                                    stack
                                    name="current_related"
                                    options={yesNo}
                                    value={data.current_related}
                                    onChange={(value) =>
                                        setData('current_related', value)
                                    }
                                />
                            </Question>

                            <Question
                                label="2. What were your reason(s) for changing job?"
                                strong
                                error={
                                    errors.current_reasons ??
                                    errors.current_reason_other
                                }
                            >
                                <CheckGroup
                                    stack
                                    options={jobReasons}
                                    values={data.current_reasons}
                                    onToggle={(value) =>
                                        setData(
                                            'current_reasons',
                                            toggle(data.current_reasons, value),
                                        )
                                    }
                                    otherText={data.current_reason_other}
                                    onOtherText={(value) =>
                                        setData('current_reason_other', value)
                                    }
                                    otherPlaceholder="please specify"
                                />
                            </Question>

                            <Field
                                id="current_stay"
                                label="3. How long did you stay in your first job?"
                                required
                                strong
                                error={errors.current_stay}
                            >
                                <SelectInput
                                    id="current_stay"
                                    placeholder={PLACEHOLDER}
                                    options={timeStayed}
                                    value={data.current_stay}
                                    onChange={(e) =>
                                        setData('current_stay', e.target.value)
                                    }
                                />
                            </Field>

                            <Field
                                id="current_level"
                                label="4. Current Job Level Position"
                                required
                                strong
                                error={errors.current_level}
                            >
                                <SelectInput
                                    id="current_level"
                                    placeholder={PLACEHOLDER}
                                    options={jobLevels}
                                    value={data.current_level}
                                    onChange={(e) =>
                                        setData('current_level', e.target.value)
                                    }
                                />
                            </Field>

                            <Question
                                label="5. Employment Sector of Your Current Job"
                                required
                                strong
                                error={
                                    errors.current_sector ??
                                    errors.current_sector_other
                                }
                            >
                                <RadioGroup
                                    stack
                                    name="current_sector"
                                    options={sectors}
                                    value={data.current_sector}
                                    onChange={(value) =>
                                        setData('current_sector', value)
                                    }
                                    otherText={data.current_sector_other}
                                    onOtherText={(value) =>
                                        setData('current_sector_other', value)
                                    }
                                />
                            </Question>

                            <Field
                                id="current_earning"
                                label="6. What is your current gross monthly earning?"
                                required
                                strong
                                error={errors.current_earning}
                            >
                                <SelectInput
                                    id="current_earning"
                                    placeholder={PLACEHOLDER}
                                    options={earnings}
                                    value={data.current_earning}
                                    onChange={(e) =>
                                        setData(
                                            'current_earning',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                    )}
                </form>
            </SurveyLayout>
        </>
    );
}
