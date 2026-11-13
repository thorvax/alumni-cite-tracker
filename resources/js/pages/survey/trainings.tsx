import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    CheckGroup,
    Field,
    Question,
    SectionHeading,
    TextInput,
} from '@/components/survey/form-controls';
import SurveyLayout, { SURVEY_FORM_ID } from '@/layouts/survey-layout';
import { stepUrl, toggle, withDefaults } from '@/lib/survey';
import { pursueReasons } from '@/lib/survey-options';

type Data = {
    training_title: string;
    training_duration: string;
    training_institution: string;
    pursue_reasons: string[];
    pursue_other: string;
};

const defaults: Data = {
    training_title: '',
    training_duration: '',
    training_institution: '',
    pursue_reasons: [],
    pursue_other: '',
};

export default function Trainings({ draft }: { draft: Partial<Data> }) {
    const { data, setData, post, processing, errors } = useForm<Data>(
        withDefaults(defaults, draft),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(stepUrl('trainings'));
    };

    return (
        <>
            <Head title="Trainings & Advance Studies" />

            <SurveyLayout
                step={3}
                page="training"
                title="C. Trainings & Advance Studies Attended After College"
                backHref={stepUrl('educational-background')}
                processing={processing}
            >
                <form
                    id={SURVEY_FORM_ID}
                    onSubmit={submit}
                    className="sv-grid"
                    noValidate
                >
                    <SectionHeading>
                        1. Professional or work-related training program(s)
                        including advance studies you have attended after
                        college.
                    </SectionHeading>

                    <Field
                        id="training_title"
                        label="1.1 Title of Training or Advance Study"
                        className="sv-col-span"
                        error={errors.training_title}
                    >
                        <TextInput
                            id="training_title"
                            placeholder="e.g. AWS Cloud Practitioner Training"
                            value={data.training_title}
                            onChange={(e) =>
                                setData('training_title', e.target.value)
                            }
                        />
                    </Field>

                    <Field
                        id="training_duration"
                        label="1.2 Duration and Credits Earned"
                        error={errors.training_duration}
                    >
                        <TextInput
                            id="training_duration"
                            placeholder="e.g. 40 hours / 3 units"
                            value={data.training_duration}
                            onChange={(e) =>
                                setData('training_duration', e.target.value)
                            }
                        />
                    </Field>

                    <Field
                        id="training_institution"
                        label="1.3 Name of Training Institution / College / University"
                        error={errors.training_institution}
                    >
                        <TextInput
                            id="training_institution"
                            placeholder="e.g. Amazon Web Services"
                            value={data.training_institution}
                            onChange={(e) =>
                                setData('training_institution', e.target.value)
                            }
                        />
                    </Field>

                    <Question
                        label="2. What made you pursue advance studies?"
                        strong
                        className="sv-col-span"
                        error={errors.pursue_reasons}
                    >
                        <CheckGroup
                            stack
                            options={pursueReasons}
                            values={data.pursue_reasons}
                            onToggle={(value) =>
                                setData(
                                    'pursue_reasons',
                                    toggle(data.pursue_reasons, value),
                                )
                            }
                            otherText={data.pursue_other}
                            onOtherText={(value) =>
                                setData('pursue_other', value)
                            }
                            otherPlaceholder="please specify"
                        />
                    </Question>
                </form>
            </SurveyLayout>
        </>
    );
}
