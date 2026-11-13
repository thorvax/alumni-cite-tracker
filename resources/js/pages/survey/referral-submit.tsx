import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    Field,
    SectionHeading,
    TextArea,
} from '@/components/survey/form-controls';
import SurveyLayout, { SURVEY_FORM_ID } from '@/layouts/survey-layout';
import { stepUrl, withDefaults } from '@/lib/survey';

type Data = {
    classmates: string;
    certify: boolean;
};

const defaults: Data = {
    classmates: '',
    certify: false,
};

export default function ReferralSubmit({ draft }: { draft: Partial<Data> }) {
    const { data, setData, post, processing, errors } = useForm<Data>(
        withDefaults(defaults, draft),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(stepUrl('referral-submit'));
    };

    return (
        <>
            <Head title="Referral & Submit" />

            <SurveyLayout
                step={7}
                page="done"
                title="Referral & Submit"
                description="Please refer your classmates and review before submitting."
                backHref={stepUrl('first-current-job')}
                processing={processing}
                submitLabel="Submit"
            >
                <form
                    id={SURVEY_FORM_ID}
                    onSubmit={submit}
                    noValidate
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 18,
                    }}
                >
                    <div
                        className="sv-card"
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 12,
                        }}
                    >
                        <SectionHeading>
                            Please refer your classmates
                        </SectionHeading>

                        <p
                            style={{
                                margin: 0,
                                fontSize: 13,
                                lineHeight: '19px',
                            }}
                        >
                            Being one of the alumni of our institution, may we
                            request you to list down the names of your
                            classmates and their contact numbers. Their
                            participation will also be needed to make this study
                            more meaningful and useful.
                        </p>

                        <p className="sv-note" style={{ marginTop: 14 }}>
                            Ex. Juan C. Sipag 0922333444, Juana M. Galang,
                            0933222555
                        </p>

                        <Field
                            id="classmates"
                            label="Classmates' Names and Contact Numbers"
                            strong
                            accent
                            error={errors.classmates}
                        >
                            <TextArea
                                id="classmates"
                                style={{ minHeight: 108 }}
                                placeholder="List names and contact numbers here..."
                                value={data.classmates}
                                onChange={(e) =>
                                    setData('classmates', e.target.value)
                                }
                            />
                        </Field>
                    </div>

                    <div
                        className="sv-card"
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 14,
                        }}
                    >
                        <p
                            style={{
                                margin: 0,
                                fontSize: 13.5,
                                lineHeight: '19px',
                            }}
                        >
                            Survey adapted from the Graduate Tracer Study
                            recommended by CHED. Thank you for your
                            participation. Keep safe and Healthy!
                        </p>

                        <p
                            style={{
                                margin: 0,
                                fontSize: 11.5,
                                color: 'var(--sv-muted)',
                            }}
                        >
                            Updated: August 2025
                        </p>

                        <div>
                            <div className="sv-certify">
                                <label className="sv-option">
                                    <input
                                        type="checkbox"
                                        checked={data.certify}
                                        onChange={(e) =>
                                            setData('certify', e.target.checked)
                                        }
                                    />
                                    I certify that all information I have
                                    provided is true and correct.
                                </label>
                            </div>
                            {errors.certify && (
                                <p className="sv-error" role="alert">
                                    {errors.certify}
                                </p>
                            )}
                        </div>
                    </div>
                </form>
            </SurveyLayout>
        </>
    );
}
