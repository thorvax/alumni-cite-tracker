import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { Field, TextInput } from '@/components/survey/form-controls';
import AlumniLayout from '@/layouts/alumni-layout';
import { resend, store } from '@/routes/alumni/code';

type Props = {
    purpose: 'activate' | 'reset';
    /** Masked, e.g. ju******@gmail.com. Null on a password reset (never revealed). */
    email: string | null;
    resendIn: number; // seconds until a new code may be sent
    status?: string | null;
};

/** Activation / password reset page 2: the 6-digit code from the email. */
export default function VerifyCode({
    purpose,
    email,
    resendIn,
    status,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({ code: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(store.url());
    };

    return (
        <>
            <Head title="Enter your code" />

            <AlumniLayout
                title="Enter your code"
                description={
                    email
                        ? `We sent a 6-digit code to ${email}. It expires in 10 minutes.`
                        : 'If the details you entered match an activated account, we sent a 6-digit code to the email address we have on file. It expires in 10 minutes.'
                }
            >
                <form onSubmit={submit} noValidate className="sv-auth-body">
                    {status && (
                        <p className="sv-auth-status" role="status">
                            {status}
                        </p>
                    )}

                    <Field
                        id="code"
                        label={
                            purpose === 'reset'
                                ? 'Password reset code'
                                : 'Activation code'
                        }
                        required
                        error={errors.code}
                    >
                        <TextInput
                            id="code"
                            className="sv-code-input"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            maxLength={6}
                            placeholder="000000"
                            value={data.code}
                            onChange={(e) =>
                                setData(
                                    'code',
                                    e.target.value.replace(/\D/g, ''),
                                )
                            }
                        />
                    </Field>

                    <button
                        type="submit"
                        disabled={processing}
                        className="sv-btn sv-btn-primary"
                    >
                        Verify code
                    </button>

                    {/* key: restart the countdown after a new code is sent */}
                    <ResendButton
                        key={`${resendIn}-${status}`}
                        wait={resendIn}
                    />
                </form>
            </AlumniLayout>
        </>
    );
}

function ResendButton({ wait }: { wait: number }) {
    const [seconds, setSeconds] = useState(wait);
    const [sending, setSending] = useState(false);

    useEffect(() => {
        if (seconds <= 0) {
            return;
        }

        const timer = window.setTimeout(() => setSeconds(seconds - 1), 1000);

        return () => window.clearTimeout(timer);
    }, [seconds]);

    const send = () =>
        router.post(
            resend.url(),
            {},
            {
                preserveScroll: true,
                onStart: () => setSending(true),
                onFinish: () => setSending(false),
            },
        );

    return (
        <p className="sv-auth-note">
            Didn&apos;t get the code? Check your spam folder, or{' '}
            <button
                type="button"
                className="sv-link-btn"
                disabled={seconds > 0 || sending}
                onClick={send}
            >
                {seconds > 0
                    ? `send a new code in ${seconds}s`
                    : 'send a new code'}
            </button>
        </p>
    );
}
