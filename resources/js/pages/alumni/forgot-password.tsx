import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Field, TextInput } from '@/components/survey/form-controls';
import AlumniLayout from '@/layouts/alumni-layout';
import { login } from '@/routes/alumni';
import { store } from '@/routes/alumni/forgot';

type Data = { student_id: string; last_name: string };

/** Forgot password, page 1. Then the same code and password pages as activation. */
export default function ForgotPassword() {
    const { data, setData, post, processing, errors } = useForm<Data>({
        student_id: '',
        last_name: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(store.url());
    };

    return (
        <>
            <Head title="Forgot password" />

            <AlumniLayout
                title="Forgot password"
                description="Enter your Student ID Number and Last Name. If they match an activated account, we will email you a 6-digit code to reset your password."
            >
                <form onSubmit={submit} noValidate className="sv-auth-body">
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

                    <p className="sv-auth-note">
                        Not activated yet? Use the survey link we emailed you.{' '}
                        <Link href={login()} className="sv-auth-link">
                            Back to login
                        </Link>
                    </p>
                </form>
            </AlumniLayout>
        </>
    );
}
