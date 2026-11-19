import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    CheckGroup,
    Field,
    Question,
    RadioGroup,
    SelectInput,
    TextInput,
} from '@/components/survey/form-controls';
import SurveyLayout, { SURVEY_FORM_ID } from '@/layouts/survey-layout';
import { stepUrl, toggle, withDefaults } from '@/lib/survey';
import {
    businessLines,
    employedOptions,
    employmentStatuses,
    notEmployedReasons,
    placeOfWork,
    sectors,
    workLines,
    yesNo,
} from '@/lib/survey-options';

type Data = {
    employed: string;
    // not employed
    not_employed_reasons: string[];
    not_employed_other: string;
    // employed
    employment_status: string;
    employment_sector: string;
    employment_sector_other: string;
    self_employed_skills: string;
    present_occupation: string;
    business_line: string;
    work_line: string;
    work_line_other: string;
    place_of_work: string;
    first_job: string;
};

const defaults: Data = {
    employed: '',
    not_employed_reasons: [],
    not_employed_other: '',
    employment_status: '',
    employment_sector: '',
    employment_sector_other: '',
    self_employed_skills: '',
    present_occupation: '',
    business_line: '',
    work_line: '',
    work_line_other: '',
    place_of_work: '',
    first_job: '',
};

const cardStyle = {
    display: 'flex',
    flexDirection: 'column',
    gap: 18,
} as const;

export default function EmploymentData({ draft }: { draft: Partial<Data> }) {
    const { data, setData, post, processing, errors } = useForm<Data>(
        withDefaults(defaults, draft),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(stepUrl('employment-data'));
    };

    const isEmployed = data.employed === 'yes';
    const notEmployed = data.employed === 'no' || data.employed === 'never';

    return (
        <>
            <Head title="Employment Data" />

            <SurveyLayout
                slug="employment-data"
                page="employment"
                title="D. Employment Data"
                description="Tell us about your current employment status."
                employed={data.employed || null}
                processing={processing}
            >
                <form
                    id={SURVEY_FORM_ID}
                    onSubmit={submit}
                    noValidate
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 20,
                    }}
                >
                    <div style={{ marginBottom: 6 }}>
                        <Question
                            label="Are you presently employed?"
                            required
                            strong
                            error={errors.employed}
                        >
                            <RadioGroup
                                name="employed"
                                options={employedOptions}
                                value={data.employed}
                                onChange={(value) => setData('employed', value)}
                            />
                        </Question>
                    </div>

                    {isEmployed && (
                        <div className="sv-card" style={cardStyle}>
                            <h2 className="sv-card-title">Employed</h2>

                            <div className="sv-grid">
                                <Question
                                    label="1. Present Employment Status"
                                    required
                                    strong
                                    error={errors.employment_status}
                                >
                                    <RadioGroup
                                        name="employment_status"
                                        options={employmentStatuses}
                                        value={data.employment_status}
                                        onChange={(value) =>
                                            setData('employment_status', value)
                                        }
                                    />
                                </Question>

                                <Question
                                    label="2. Employment Sector"
                                    required
                                    strong
                                    error={
                                        errors.employment_sector ??
                                        errors.employment_sector_other
                                    }
                                >
                                    <RadioGroup
                                        name="employment_sector"
                                        options={sectors}
                                        value={data.employment_sector}
                                        onChange={(value) =>
                                            setData('employment_sector', value)
                                        }
                                        otherText={data.employment_sector_other}
                                        onOtherText={(value) =>
                                            setData(
                                                'employment_sector_other',
                                                value,
                                            )
                                        }
                                    />
                                </Question>
                            </div>

                            <Field
                                id="self_employed_skills"
                                label="3. For self-employed / Entrepreneurial / Freelance Work: What skills acquired in college were you able to apply in your work? Write N/A if you're not self-employed."
                                required
                                strong
                                error={errors.self_employed_skills}
                            >
                                <TextInput
                                    id="self_employed_skills"
                                    placeholder="e.g. Web development, database management..."
                                    value={data.self_employed_skills}
                                    onChange={(e) =>
                                        setData(
                                            'self_employed_skills',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>

                            <Field
                                id="present_occupation"
                                label="4. Present Occupation (Ex. Grade School Teacher, Electrical Engineer, Free-Lancer)"
                                required
                                strong
                                error={errors.present_occupation}
                            >
                                <TextInput
                                    id="present_occupation"
                                    placeholder="e.g. Software Developer, IT Specialist"
                                    value={data.present_occupation}
                                    onChange={(e) =>
                                        setData(
                                            'present_occupation',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>

                            <Field
                                id="business_line"
                                label="5. Major line of business of the company you are presently employed in. Check one only."
                                required
                                strong
                                error={errors.business_line}
                            >
                                <SelectInput
                                    id="business_line"
                                    options={businessLines}
                                    value={data.business_line}
                                    onChange={(e) =>
                                        setData('business_line', e.target.value)
                                    }
                                />
                            </Field>

                            <Field
                                id="work_line"
                                label="6. Select Your Specific Line Of Work."
                                required
                                strong
                                error={
                                    errors.work_line ?? errors.work_line_other
                                }
                            >
                                <SelectInput
                                    id="work_line"
                                    options={workLines}
                                    value={data.work_line}
                                    onChange={(e) =>
                                        setData('work_line', e.target.value)
                                    }
                                />
                                {data.work_line === 'Other' && (
                                    <TextInput
                                        aria-label="Other line of work"
                                        placeholder="Other: please specify"
                                        style={{ marginTop: 10 }}
                                        value={data.work_line_other}
                                        onChange={(e) =>
                                            setData(
                                                'work_line_other',
                                                e.target.value,
                                            )
                                        }
                                    />
                                )}
                            </Field>

                            <Question
                                label="7. Place of work"
                                required
                                strong
                                error={errors.place_of_work}
                            >
                                <RadioGroup
                                    name="place_of_work"
                                    options={placeOfWork}
                                    value={data.place_of_work}
                                    onChange={(value) =>
                                        setData('place_of_work', value)
                                    }
                                />
                            </Question>

                            <Question
                                label="8. Is this your FIRST JOB after college?"
                                required
                                strong
                                error={errors.first_job}
                            >
                                <RadioGroup
                                    name="first_job"
                                    options={yesNo}
                                    value={data.first_job}
                                    onChange={(value) =>
                                        setData('first_job', value)
                                    }
                                />
                            </Question>
                        </div>
                    )}

                    {notEmployed && (
                        <div className="sv-card" style={cardStyle}>
                            <h2 className="sv-card-title">Not Employed</h2>

                            <Question
                                label="1. Please state reason(s) why you are not yet employed. You may select/check more than one answer."
                                required
                                strong
                                error={
                                    errors.not_employed_reasons ??
                                    errors.not_employed_other
                                }
                            >
                                <CheckGroup
                                    stack
                                    options={notEmployedReasons}
                                    values={data.not_employed_reasons}
                                    onToggle={(value) =>
                                        setData(
                                            'not_employed_reasons',
                                            toggle(
                                                data.not_employed_reasons,
                                                value,
                                            ),
                                        )
                                    }
                                    otherText={data.not_employed_other}
                                    onOtherText={(value) =>
                                        setData('not_employed_other', value)
                                    }
                                    otherPlaceholder="please specify"
                                />
                            </Question>
                        </div>
                    )}
                </form>
            </SurveyLayout>
        </>
    );
}
