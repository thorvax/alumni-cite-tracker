import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    CheckGroup,
    Field,
    Question,
    RadioGroup,
    TextInput,
} from '@/components/survey/form-controls';
import SurveyLayout, { SURVEY_FORM_ID } from '@/layouts/survey-layout';
import { stepUrl, toggle, withDefaults } from '@/lib/survey';
import {
    employedOptions,
    employmentStatuses,
    notEmployedReasons,
    sectors,
} from '@/lib/survey-options';

type Data = {
    employed: string;
    not_employed_reasons: string[];
    not_employed_other: string;
    employment_status: string;
    employment_sector: string;
    employment_sector_other: string;
    self_employed_skills: string;
    present_occupation: string;
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
};

export default function EmploymentData({ draft }: { draft: Partial<Data> }) {
    const { data, setData, post, processing, errors } = useForm<Data>(
        withDefaults(defaults, draft),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(stepUrl('employment-data'));
    };

    return (
        <>
            <Head title="Employment Data" />

            <SurveyLayout
                step={4}
                page="employment"
                title="D. Employment Data"
                description="Tell us about your current employment status."
                backHref={stepUrl('trainings')}
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
                    <div style={{ marginBottom: 22 }}>
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

                    <div
                        className="sv-card"
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 14,
                        }}
                    >
                        <p className="sv-note">
                            If No or Never Employed — please answer below:
                        </p>

                        <Question
                            label="1. Please state reason(s) why you are not yet employed. (Select all that apply)"
                            strong
                            error={errors.not_employed_reasons}
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

                    <div
                        className="sv-card"
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 16,
                        }}
                    >
                        <p className="sv-note">
                            If Employed — please answer below:
                        </p>

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
                                error={errors.employment_sector}
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
                            label="3. For self-employed / Entrepreneurial / Freelance Work: What skills acquired in college were you able to apply in your work? (Write N/A if not self-employed)"
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
                            label="4. Present Occupation"
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
                    </div>
                </form>
            </SurveyLayout>
        </>
    );
}
