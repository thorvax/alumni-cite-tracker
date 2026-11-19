import { Head, Link } from '@inertiajs/react';
import DataPrivacyNotice from '@/components/survey/data-privacy-notice';
import AlumniLayout from '@/layouts/alumni-layout';
import { home } from '@/routes';

/*
 * Data Privacy Notice (public, /privacy). Linked from the footers and the
 * consent checkboxes (letter page before the survey, Create password).
 */
export default function Privacy() {
    return (
        <>
            <Head title="Data Privacy Notice" />

            <AlumniLayout
                wide
                title="Data Privacy Notice"
                description="College of Information and Technology Education, Urdaneta City University"
            >
                <div className="sv-auth-body sv-policy">
                    <DataPrivacyNotice />

                    <p>
                        <Link href={home()} className="sv-auth-link">
                            ← Back to home
                        </Link>
                    </p>
                </div>
            </AlumniLayout>
        </>
    );
}
