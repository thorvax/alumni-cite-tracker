import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import {
    Field,
    Question,
    RadioGroup,
    SectionHeading,
    SelectInput,
    TextInput,
} from '@/components/survey/form-controls';
import PrivacyConsent from '@/components/survey/privacy-consent';
import SurveyLayout, { SURVEY_FORM_ID } from '@/layouts/survey-layout';
import { stepUrl, withDefaults } from '@/lib/survey';
import { civilStatuses, regions } from '@/lib/survey-options';

type Data = {
    first_name: string;
    middle_name: string;
    last_name: string;
    email: string;
    civil_status: string;
    sex: string;
    birth_date: string;
    region: string;
    province: string;
    city: string;
    barangay: string;
    house_no: string;
    mobile_primary: string;
    mobile_secondary: string;
    privacy: boolean;
};

const defaults: Data = {
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    civil_status: '',
    sex: '',
    birth_date: '',
    region: '',
    province: '',
    city: '',
    barangay: '',
    house_no: '',
    mobile_primary: '',
    mobile_secondary: '',
    privacy: false,
};

export default function GeneralInformation({
    draft,
    invite,
}: {
    draft: Partial<Data>;
    invite?: { student_id?: string } | null;
}) {
    const { data, setData, post, processing, errors } = useForm<Data>(
        withDefaults(defaults, draft),
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(stepUrl('general-information'));
    };

    return (
        <>
            <Head title="General Information" />

            <SurveyLayout
                slug="general-information"
                page="general"
                title="A. General Information"
                description="Please provide your personal details."
                processing={processing}
            >
                <form
                    id={SURVEY_FORM_ID}
                    onSubmit={submit}
                    className="sv-grid"
                    noValidate
                >
                    <Field
                        id="first_name"
                        label="First Name"
                        required
                        error={errors.first_name}
                    >
                        <TextInput
                            id="first_name"
                            placeholder="e.g. Juan"
                            value={data.first_name}
                            onChange={(e) =>
                                setData('first_name', e.target.value)
                            }
                        />
                    </Field>

                    <Field
                        id="middle_name"
                        label="Middle Name"
                        error={errors.middle_name}
                    >
                        <TextInput
                            id="middle_name"
                            placeholder="Optional"
                            value={data.middle_name}
                            onChange={(e) =>
                                setData('middle_name', e.target.value)
                            }
                        />
                    </Field>

                    <Field
                        id="last_name"
                        label="Last Name"
                        required
                        error={errors.last_name}
                    >
                        <TextInput
                            id="last_name"
                            placeholder="e.g. dela Cruz"
                            value={data.last_name}
                            onChange={(e) =>
                                setData('last_name', e.target.value)
                            }
                        />
                    </Field>

                    <Field
                        id="email"
                        label="Email Address"
                        required
                        error={errors.email}
                    >
                        <TextInput
                            id="email"
                            type="email"
                            autoComplete="email"
                            placeholder="e.g. juan@gmail.com"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                    </Field>

                    {invite?.student_id && (
                        <Field id="student_id" label="Student ID Number">
                            <TextInput
                                id="student_id"
                                readOnly
                                value={invite.student_id}
                            />
                        </Field>
                    )}

                    <Field
                        id="civil_status"
                        label="Civil Status"
                        required
                        error={errors.civil_status}
                    >
                        <SelectInput
                            id="civil_status"
                            options={civilStatuses}
                            value={data.civil_status}
                            onChange={(e) =>
                                setData('civil_status', e.target.value)
                            }
                        />
                    </Field>

                    <Question label="Sex" required error={errors.sex}>
                        <RadioGroup
                            name="sex"
                            options={[
                                { value: 'male', label: 'Male' },
                                { value: 'female', label: 'Female' },
                            ]}
                            value={data.sex}
                            onChange={(value) => setData('sex', value)}
                        />
                    </Question>

                    <Field
                        id="birth_date"
                        label="Birth Date"
                        required
                        error={errors.birth_date}
                    >
                        <TextInput
                            id="birth_date"
                            type="date"
                            value={data.birth_date}
                            onChange={(e) =>
                                setData('birth_date', e.target.value)
                            }
                        />
                    </Field>

                    <SectionHeading>Address</SectionHeading>

                    <Field
                        id="region"
                        label="Select your region"
                        required
                        error={errors.region}
                    >
                        <SelectInput
                            id="region"
                            options={regions}
                            value={data.region}
                            onChange={(e) => setData('region', e.target.value)}
                        />
                    </Field>

                    <Field
                        id="province"
                        label="Province"
                        required
                        error={errors.province}
                    >
                        <TextInput
                            id="province"
                            placeholder="e.g. Pangasinan"
                            value={data.province}
                            onChange={(e) =>
                                setData('province', e.target.value)
                            }
                        />
                    </Field>

                    <Field
                        id="city"
                        label="City/Town"
                        required
                        error={errors.city}
                    >
                        <TextInput
                            id="city"
                            placeholder="e.g. Urdaneta City"
                            value={data.city}
                            onChange={(e) => setData('city', e.target.value)}
                        />
                    </Field>

                    <Field
                        id="barangay"
                        label="Barangay"
                        required
                        error={errors.barangay}
                    >
                        <TextInput
                            id="barangay"
                            placeholder="e.g. Nancamaliran"
                            value={data.barangay}
                            onChange={(e) =>
                                setData('barangay', e.target.value)
                            }
                        />
                    </Field>

                    <Field
                        id="house_no"
                        label="House #, Building #"
                        error={errors.house_no}
                    >
                        <TextInput
                            id="house_no"
                            placeholder="Optional"
                            value={data.house_no}
                            onChange={(e) =>
                                setData('house_no', e.target.value)
                            }
                        />
                    </Field>
                    <div />

                    <SectionHeading>Contact</SectionHeading>

                    <Field
                        id="mobile_primary"
                        label="Mobile Number (Primary)"
                        required
                        error={errors.mobile_primary}
                    >
                        <TextInput
                            id="mobile_primary"
                            type="tel"
                            inputMode="tel"
                            placeholder="e.g. 09xx-xxx-xxxx"
                            value={data.mobile_primary}
                            onChange={(e) =>
                                setData('mobile_primary', e.target.value)
                            }
                        />
                    </Field>

                    <Field
                        id="mobile_secondary"
                        label="Mobile Number (Secondary)"
                        error={errors.mobile_secondary}
                    >
                        <TextInput
                            id="mobile_secondary"
                            type="tel"
                            inputMode="tel"
                            placeholder="Optional"
                            value={data.mobile_secondary}
                            onChange={(e) =>
                                setData('mobile_secondary', e.target.value)
                            }
                        />
                    </Field>

                    <PrivacyConsent
                        className="sv-col-span"
                        checked={data.privacy}
                        onChange={(checked) => setData('privacy', checked)}
                        error={errors.privacy}
                    />
                </form>
            </SurveyLayout>
        </>
    );
}
