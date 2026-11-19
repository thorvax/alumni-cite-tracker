import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Field, TextInput } from '@/components/survey/form-controls';
import AlumniLayout from '@/layouts/alumni-layout';
import forgot from '@/routes/alumni/forgot';
import { store } from '@/routes/alumni/login';

type Data = { student_id: string; password: string; remember: boolean };

/** Alumni login: Student ID Number + password. */
export default function Login({ status }: { status?: string | null }) {
    const { data, setData, post, processing, errors, reset } = useForm<Data>({
        student_id: '',
        password: '',
        remember: false,
    });

    // One message for every failed login (see LoginController).
    const loginError = (errors as Partial<Record<string, string>>).login;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(store.url(), { onFinish: () => reset('password') });
    };

    return (
        <>
            <Head title="Alumni Login" />

            <AlumniLayout
                title="Alumni Login"
                description="Log in with your Student ID Number and password to answer or update your Graduate Tracer Survey."
            >
                <form onSubmit={submit} noValidate className="sv-auth-body">
                    {status && (
                        <p className="sv-auth-status" role="status">
                            {status}
                        </p>
                    )}

                    {loginError && (
                        <p className="sv-auth-alert" role="alert">
                            {loginError}
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
                        id="password"
                        label="Password"
                        required
                        error={errors.password}
                    >
                        <TextInput
                            id="password"
                            type="password"
                            autoComplete="current-password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                        />
                    </Field>

                    <div className="sv-auth-row">
                        <label className="sv-option">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) =>
                                    setData('remember', e.target.checked)
                                }
                            />
                            Remember me
                        </label>

                        <Link href={forgot.show()} className="sv-auth-link">
                            Forgot password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="sv-btn sv-btn-primary"
                    >
                        Log in
                    </button>

                    <p className="sv-auth-note">
                        First time here? Open the survey link we emailed you to
                        activate your account.
                    </p>
                </form>
            </AlumniLayout>
        </>
    );
}
