import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import ThemeToggle from '@/components/survey/theme-toggle';

type Props = {
    status?: string;
    canResetPassword?: boolean;
};

export default function Login({ status }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Sign in" />

            <div className="lg">
                <section className="lg-left">
                    <img src="/images/ucu-cite-logo.png" alt="UCU CITE seal" />
                    <div className="lg-brand">UCU | CITE</div>
                    <p>Graduate Tracer System</p>
                    <p>College of Information and Technology Education</p>
                    <p>Urdaneta City University</p>
                </section>

                <section
                    className="lg-right"
                    style={{
                        backgroundImage:
                            "var(--lg-overlay), url('/images/ucu-cite.jpg')",
                    }}
                >
                    <ThemeToggle className="lg-toggle" />

                    <form className="lg-form" onSubmit={submit} noValidate>
                        <h1 className="lg-title">Welcome Back</h1>
                        <p className="lg-sub">Sign in</p>

                        {status && <p className="lg-status">{status}</p>}

                        <label className="lg-label" htmlFor="email">
                            Email Address
                        </label>
                        <input
                            id="email"
                            type="email"
                            autoComplete="email"
                            className="lg-input"
                            placeholder="Enter your email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            autoFocus
                        />
                        {errors.email && (
                            <p className="lg-error" role="alert">
                                {errors.email}
                            </p>
                        )}

                        <label className="lg-label" htmlFor="password">
                            Password
                        </label>
                        <input
                            id="password"
                            type="password"
                            autoComplete="current-password"
                            className="lg-input"
                            placeholder="Enter your password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                        />
                        {errors.password && (
                            <p className="lg-error" role="alert">
                                {errors.password}
                            </p>
                        )}

                        <div className="lg-row">
                            <label className="lg-remember">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) =>
                                        setData('remember', e.target.checked)
                                    }
                                />
                                Remember me
                            </label>
                            <Link href="/forgot-password">
                                Forgot password?
                            </Link>
                        </div>

                        <button
                            type="submit"
                            className="lg-btn"
                            disabled={processing}
                        >
                            Sign In
                        </button>
                    </form>
                </section>
            </div>
        </>
    );
}
