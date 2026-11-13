import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    CheckGroup,
    Field,
    Question,
    RadioGroup,
    SectionHeading,
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
    yesNo,
} from '@/lib/survey-options';

type Data = {
    // first job
    first_related: string;
    first_reasons: string[];
    first_reason_other: string;
    first_time_to_land: string;
    first_level: string;
    first_sector: string;
    first_sector_other: string;
    first_how_found: string;
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
    help_needed: string;
};

const defaults: Data = {
    first_related: '',
    first_reasons: [],
    first_reason_other: '',
    first_time_to_land: '',
    first_level: '',
    first_sector: '',
    first_sector_other: '',
    first_how_found: '',
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
    help_needed: '',
};

const PLACEHOLDER = '- Select -';

export default function FirstCurrentJob({ draft }: { draft: Partial<Data> }) {
    const { data, setData, post, processing, errors } = useForm<Data>(
        withDefaults(defaults, draft),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(stepUrl('first-current-job'));
    };

    return (
        <>
            <Head title="First / Current Job" />

            <SurveyLayout
                step={6}
                page="first-job"
                title="D. First / Current Job"
                description="Answer whichever applies to your situation."
                backHref={stepUrl('job-details')}
                processing={processing}
            >
                <form id={SURVEY_FORM_ID} onSubmit={submit} noValidate>
                    <div className="sv-card sv-grid">
                        {/* ---------- first job ---------- */}
                        <p className="sv-note sv-col-span">
                            If this is your FIRST JOB:
                        </p>

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
                            error={errors.first_reasons}
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

                        <Field
                            id="first_time_to_land"
                            label="3. How long did it take you to land your first job?"
                            required
                            strong
                            error={errors.first_time_to_land}
                        >
                            <SelectInput
                                id="first_time_to_land"
                                placeholder={PLACEHOLDER}
                                options={timeToLand}
                                value={data.first_time_to_land}
                                onChange={(e) =>
                                    setData(
                                        'first_time_to_land',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>

                        <Field
                            id="first_level"
                            label="4. Level Position of Your First Job"
                            required
                            strong
                            error={errors.first_level}
                        >
                            <SelectInput
                                id="first_level"
                                placeholder={PLACEHOLDER}
                                options={jobLevels}
                                value={data.first_level}
                                onChange={(e) =>
                                    setData('first_level', e.target.value)
                                }
                            />
                        </Field>

                        <Question
                            label="5. Employment Sector of Your First Job"
                            required
                            strong
                            error={errors.first_sector}
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

                        <Field
                            id="first_how_found"
                            label="6. How did you find your first job?"
                            required
                            strong
                            error={errors.first_how_found}
                        >
                            <SelectInput
                                id="first_how_found"
                                placeholder={PLACEHOLDER}
                                options={howFound}
                                value={data.first_how_found}
                                onChange={(e) =>
                                    setData('first_how_found', e.target.value)
                                }
                            />
                        </Field>

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

                        <Question
                            label="If YES - What competencies learned in college did you find very useful in your first job?"
                            strong
                            className="sv-col-span"
                            error={errors.competencies}
                        >
                            <CheckGroup
                                stack
                                options={competencies}
                                values={data.competencies}
                                onToggle={(value) =>
                                    setData(
                                        'competencies',
                                        toggle(data.competencies, value),
                                    )
                                }
                                otherText={data.competencies_other}
                                onOtherText={(value) =>
                                    setData('competencies_other', value)
                                }
                                otherPlaceholder="please specify"
                            />
                        </Question>

                        <Field
                            id="curriculum_suggestions"
                            label="If NO - List down suggestions to further improve our course curriculum."
                            strong
                            className="sv-col-span"
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

                        {/* ---------- current / new job ---------- */}
                        <p className="sv-note sv-col-span">
                            If you have a CURRENT / NEW JOB (not your first):
                        </p>

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
                            error={errors.current_reasons}
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
                            error={errors.current_sector}
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
                                    setData('current_earning', e.target.value)
                                }
                            />
                        </Field>

                        {/* ---------- help ---------- */}
                        <hr className="sv-divider sv-col-span" />

                        <SectionHeading>
                            Need help in landing a job?
                        </SectionHeading>

                        <p
                            className="sv-hint sv-col-span"
                            style={{ margin: 0, color: 'var(--sv-muted)' }}
                        >
                            Please provide us in details how can we help you
                            land a job.
                        </p>

                        <Field
                            id="help_needed"
                            label="How can we help you?"
                            strong
                            className="sv-col-span"
                            error={errors.help_needed}
                        >
                            <TextArea
                                id="help_needed"
                                placeholder="Describe how the college can assist you..."
                                value={data.help_needed}
                                onChange={(e) =>
                                    setData('help_needed', e.target.value)
                                }
                            />
                        </Field>
                    </div>
                </form>
            </SurveyLayout>
        </>
    );
}
