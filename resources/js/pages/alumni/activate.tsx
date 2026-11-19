import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Field, TextInput } from '@/components/survey/form-controls';
import AlumniLayout from '@/layouts/alumni-layout';
import { store } from '@/routes/alumni/activate';

type Data = { student_id: string; last_name: string };

/** Activation page 1: prove who you are with your Student ID Number and Last Name. */
export default function Activate() {
    const { data, setData, post, processing, errors } = useForm<Data>({
        student_id: '',
        last_name: '',
    });

    // One message for both fields (the server never says which one was wrong).
    const identityError = (errors as Partial<Record<string, string>>).identity;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(store.url());
    };

    return (
        <>
            <Head title="Activate your account" />

            <AlumniLayout
                title="Activate your account"
                description="Enter your Student ID Number and Last Name. We will email you a 6-digit code."
            >
                <form onSubmit={submit} noValidate className="sv-auth-body">
                    {identityError && (
                        <p className="sv-auth-alert" role="alert">
                            {identityError}
                        </p>
                    )}

                    <Field
                        id="student_id"
                        label="Student ID Number"
                        required
                        error={errors.student_id}
                    >
                        <TextInput
                            id="student_id"
                            inputMode="numeric"
                            autoComplete="username"
                            placeholder="e.g. 20244012"
                            maxLength={20}
                            value={data.student_id}
                            onChange={(e) =>
                                setData('student_id', e.target.value)
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
                            autoComplete="family-name"
                            value={data.last_name}
                            onChange={(e) =>
                                setData('last_name', e.target.value)
                            }
                        />
                    </Field>

                    <button
                        type="submit"
                        disabled={processing}
                        className="sv-btn sv-btn-primary"
                    >
                        Send code
                    </button>
                </form>
            </AlumniLayout>
        </>
    );
}
