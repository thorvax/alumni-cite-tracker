import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    Field,
    Question,
    RadioGroup,
    SelectInput,
} from '@/components/survey/form-controls';
import SurveyLayout, { SURVEY_FORM_ID } from '@/layouts/survey-layout';
import { stepUrl, withDefaults } from '@/lib/survey';
import {
    businessLines,
    placeOfWork,
    workLines,
    yesNo,
} from '@/lib/survey-options';

type Data = {
    business_line: string;
    work_line: string;
    place_of_work: string;
    first_job: string;
};

const defaults: Data = {
    business_line: '',
    work_line: '',
    place_of_work: '',
    first_job: '',
};

export default function JobDetails({ draft }: { draft: Partial<Data> }) {
    const { data, setData, post, processing, errors } = useForm<Data>(
        withDefaults(defaults, draft),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(stepUrl('job-details'));
    };

    return (
        <>
            <Head title="Job Details" />

            <SurveyLayout
                step={5}
                page="job-details"
                title="D. Job Details"
                description="Details about your current employment."
                backHref={stepUrl('employment-data')}
                processing={processing}
            >
                <form
                    id={SURVEY_FORM_ID}
                    onSubmit={submit}
                    noValidate
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 24,
                    }}
                >
                    <Field
                        id="business_line"
                        label="5. Major line of business of the company you are presently employed in."
                        required
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
                        error={errors.work_line}
                    >
                        <SelectInput
                            id="work_line"
                            options={workLines}
                            value={data.work_line}
                            onChange={(e) =>
                                setData('work_line', e.target.value)
                            }
                        />
                    </Field>

                    <Question
                        label="7. Place of work"
                        required
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
                        error={errors.first_job}
                    >
                        <RadioGroup
                            name="first_job"
                            options={yesNo}
                            value={data.first_job}
                            onChange={(value) => setData('first_job', value)}
                        />
                    </Question>
                </form>
            </SurveyLayout>
        </>
    );
}
