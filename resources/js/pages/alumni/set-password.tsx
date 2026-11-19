import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Field, TextInput } from '@/components/survey/form-controls';
import PrivacyConsent from '@/components/survey/privacy-consent';
import AlumniLayout from '@/layouts/alumni-layout';
import { store } from '@/routes/alumni/password';

type Props = {
    purpose: 'activate' | 'reset';
    /** Show the Privacy Policy checkbox (always when activating). */
    needsConsent: boolean;
};

type Data = {
    password: string;
    password_confirmation: string;
    privacy: boolean;
};

/** Activation / password reset page 3: choose a password. */
export default function SetPassword({ purpose, needsConsent }: Props) {
    const { data, setData, post, processing, errors } = useForm<Data>({
        password: '',
        password_confirmation: '',
        privacy: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(store.url());
    };

    const title =
        purpose === 'reset' ? 'Choose a new password' : 'Create your password';

    return (
        <>
            <Head title={title} />

            <AlumniLayout
                title={title}
                description="Use at least 8 characters with uppercase and lowercase letters and a number. You will log in with your Student ID Number and this password."
            >
                <form onSubmit={submit} noValidate className="sv-auth-body">
                    <Field
                        id="password"
                        label="Password"
                        required
                        error={errors.password}
                    >
                        <TextInput
                            id="password"
                            type="password"
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                        />
                    </Field>

                    <Field
                        id="password_confirmation"
                        label="Confirm password"
                        required
                        error={errors.password_confirmation}
                    >
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                        />
                    </Field>

                    {needsConsent && (
                        <PrivacyConsent
                            checked={data.privacy}
                            onChange={(checked) => setData('privacy', checked)}
                            error={errors.privacy}
                        />
                    )}

                    <button
                        type="submit"
                        disabled={processing}
                        className="sv-btn sv-btn-primary"
                    >
                        {purpose === 'reset'
                            ? 'Save new password'
                            : 'Activate account'}
                    </button>
                </form>
            </AlumniLayout>
        </>
    );
}
