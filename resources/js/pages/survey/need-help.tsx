import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Field, TextArea } from '@/components/survey/form-controls';
import SurveyLayout, { SURVEY_FORM_ID } from '@/layouts/survey-layout';
import { stepUrl, withDefaults } from '@/lib/survey';

type Data = { help_needed: string };

const defaults: Data = { help_needed: '' };

export default function NeedHelp({ draft }: { draft: Partial<Data> }) {
    const { data, setData, post, processing, errors } = useForm<Data>(
        withDefaults(defaults, draft),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(stepUrl('need-help'));
    };

    return (
        <>
            <Head title="Need Help in Landing a Job?" />

            <SurveyLayout
                slug="need-help"
                page="first-job"
                title="Need Help in Landing a Job?"
                description="Please provide us in details how can we help you land a job."
                processing={processing}
            >
                <form id={SURVEY_FORM_ID} onSubmit={submit} noValidate>
                    <Field
                        id="help_needed"
                        label="How can we help you?"
                        required
                        strong
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
                </form>
            </SurveyLayout>
        </>
    );
}
