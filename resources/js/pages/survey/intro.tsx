import { Head, useForm } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { FormEvent } from 'react';
import DataPrivacyNotice from '@/components/survey/data-privacy-notice';
import PrivacyConsent from '@/components/survey/privacy-consent';
import AlumniLayout from '@/layouts/alumni-layout';
import { consent } from '@/routes/survey';

/**
 * Before step A: the letter to the graduate, the Data Privacy Notice and the
 * required "I have read and agree" checkbox. The survey opens only after it.
 */
export default function SurveyIntro({ agreed }: { agreed: boolean }) {
    const { data, setData, post, processing, errors } = useForm({
        privacy: agreed,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(consent.url());
    };

    return (
        <>
            <Head title="Graduate Tracer Study" />

            <AlumniLayout wide title="Graduate Tracer Study (GTS)">
                <form
                    onSubmit={submit}
                    noValidate
                    className="sv-auth-body sv-policy"
                >
                    <p>Dear Graduate:</p>

                    <p>
                        Good day! Please complete this Graduate Tracer Study
                        (GTS) questionnaire as accurately &amp; frankly as
                        possible by selecting the options corresponding to your
                        response. Your answer will be used for research purposes
                        in order to assess graduate employability and
                        eventually, improve course offerings of our Department
                        and University. Your answers to this survey will be
                        treated with strictest confidentiality.
                    </p>

                    <p>Thank You</p>

                    <h2 className="sv-intro-heading">Data Privacy Notice</h2>
                    <DataPrivacyNotice />

                    <PrivacyConsent
                        checked={data.privacy}
                        onChange={(checked) => setData('privacy', checked)}
                        error={errors.privacy}
                    />

                    <button
                        type="submit"
                        disabled={processing}
                        className="sv-btn sv-btn-primary sv-intro-start"
                    >
                        Start the survey
                        <ArrowRight size={16} aria-hidden />
                    </button>
                </form>
            </AlumniLayout>
        </>
    );
}
