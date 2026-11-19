import { Head, Link } from '@inertiajs/react';
import AlumniLayout from '@/layouts/alumni-layout';
import { home } from '@/routes';

/*
 * Privacy Policy (public, /privacy).
 *
 * DRAFT written for a Philippine school under the Data Privacy Act of 2012
 * (RA 10173). Text in [square brackets] must be filled in by UCU. Have the
 * UCU Data Protection Officer / legal office review it before going live.
 */
export default function Privacy() {
    return (
        <>
            <Head title="Privacy Policy" />

            <AlumniLayout
                wide
                title="Privacy Policy"
                description="CITE Tracker: Graduate Tracer System of the College of Information and Technology Education, Urdaneta City University"
            >
                <div className="sv-auth-body sv-policy">
                    <p className="sv-auth-alert" role="note">
                        <strong>DRAFT, for UCU review.</strong> This policy has
                        not yet been reviewed or approved by Urdaneta City
                        University. Parts in [square brackets] still need to be
                        completed by the University&apos;s Data Protection
                        Officer.
                    </p>

                    <p>
                        Effective date: [to be set by UCU]. Last updated:
                        [date].
                    </p>

                    <section>
                        <h2>1. Who we are</h2>
                        <p>
                            The College of Information and Technology Education
                            (CITE) of Urdaneta City University (UCU) runs CITE
                            Tracker to keep in touch with its graduates. UCU is
                            the personal information controller of the data
                            collected here. We process your personal data in
                            line with the Data Privacy Act of 2012 (Republic Act
                            No. 10173), its Implementing Rules and Regulations,
                            and the issuances of the National Privacy Commission
                            (NPC).
                        </p>
                    </section>

                    <section>
                        <h2>2. What we collect</h2>
                        <ul>
                            <li>
                                <strong>Identity:</strong> full name, Student ID
                                Number, sex, date of birth and civil status.
                            </li>
                            <li>
                                <strong>Contact details:</strong> email address
                                and mobile numbers.
                            </li>
                            <li>
                                <strong>Address:</strong> region, province, city
                                or municipality, barangay and house number or
                                street.
                            </li>
                            <li>
                                <strong>Education:</strong> course, year
                                graduated, licensure or civil service exams
                                taken and ratings, and your reasons for choosing
                                your course.
                            </li>
                            <li>
                                <strong>Trainings and further studies</strong>{' '}
                                you have taken since graduating.
                            </li>
                            <li>
                                <strong>Employment information:</strong> whether
                                you are employed, your job, position level,
                                employment sector, place of work, how you found
                                your job, how long it took, and your income
                                range.
                            </li>
                            <li>
                                <strong>Your feedback:</strong> how relevant
                                your course was, useful competencies, curriculum
                                suggestions, and the help you would like in
                                finding a job.
                            </li>
                            <li>
                                <strong>Classmates you refer:</strong> names and
                                contact numbers you choose to give us. Please
                                share these only with your classmates&apos;
                                permission.
                            </li>
                            <li>
                                <strong>Account data:</strong> your password
                                (stored only in scrambled, hashed form), the
                                date you activated your account and the date you
                                agreed to this policy.
                            </li>
                            <li>
                                <strong>Technical data:</strong> session cookies
                                that keep you logged in, a cookie that remembers
                                your light or dark theme, and your IP address,
                                used briefly to block repeated failed logins.
                            </li>
                        </ul>
                        <p>
                            Some of this, such as your age, civil status and
                            education, is{' '}
                            <em>sensitive personal information</em> under RA
                            10173. We process it only with your consent and only
                            for the purposes below.
                        </p>
                    </section>

                    <section>
                        <h2>3. Why we collect it</h2>
                        <ul>
                            <li>
                                To conduct the graduate tracer study: finding
                                out where our graduates are and how they are
                                doing.
                            </li>
                            <li>
                                To prepare reports required by the Commission on
                                Higher Education (CHED) and accrediting bodies.
                                These reports use totals and percentages, not
                                your name, unless the law requires otherwise.
                            </li>
                            <li>
                                For school planning: improving the curriculum,
                                career services and job placement support.
                            </li>
                            <li>
                                To contact you about the survey, including
                                reminders to update your answers [every 6 or 12
                                months].
                            </li>
                            <li>
                                To keep your account secure, for example the
                                one-time codes we email you.
                            </li>
                        </ul>
                        <p>
                            We rely on your consent, which you give by ticking
                            the Privacy Policy box, and on the University&apos;s
                            legitimate interest in improving its programs. We do
                            not sell your data or use it for advertising.
                        </p>
                    </section>

                    <section>
                        <h2>4. Who can see it</h2>
                        <ul>
                            <li>
                                Authorized CITE and UCU staff who handle the
                                tracer study, such as the Dean, the tracer study
                                coordinator and system administrators, only as
                                far as their work requires.
                            </li>
                            <li>
                                CHED and accrediting bodies, normally as summary
                                statistics only.
                            </li>
                            <li>
                                Service providers that help us run the system,
                                such as email delivery and hosting [list
                                providers]. They are bound by agreements to
                                protect your data and use it only for us.
                            </li>
                            <li>
                                Government authorities, when required by law or
                                a court order.
                            </li>
                        </ul>
                    </section>

                    <section>
                        <h2>5. How long we keep it</h2>
                        <p>
                            We keep your data while you take part in the tracer
                            program and for [number] years after your last
                            update, or longer if the law or CHED requires it.
                            After that we securely delete it or anonymize it so
                            it can only be used for statistics. You may ask us
                            to delete it earlier (see your rights below).
                        </p>
                    </section>

                    <section>
                        <h2>6. How we protect it</h2>
                        <ul>
                            <li>
                                Only logged-in, authorized staff can open the
                                admin pages.
                            </li>
                            <li>
                                Passwords and one-time codes are stored hashed,
                                never as plain text. Codes expire after 10
                                minutes and work only once.
                            </li>
                            <li>
                                Repeated failed logins are blocked for a short
                                time.
                            </li>
                            <li>
                                Data is sent over an encrypted connection
                                (HTTPS) [confirm on the live server] and backed
                                up regularly.
                            </li>
                            <li>
                                Staff who handle the data are bound to keep it
                                confidential.
                            </li>
                        </ul>
                        <p>
                            If a data breach affects you, we will notify you and
                            the National Privacy Commission as required by NPC
                            rules.
                        </p>
                    </section>

                    <section>
                        <h2>7. Your rights</h2>
                        <p>Under the Data Privacy Act you have the right to:</p>
                        <ul>
                            <li>
                                <strong>Be informed</strong> how your data is
                                used (this policy).
                            </li>
                            <li>
                                <strong>Access</strong> your data. Log in to see
                                your answers on the My Survey page.
                            </li>
                            <li>
                                <strong>Correct</strong> wrong or outdated data.
                                Log in and update your answers, or contact us.
                            </li>
                            <li>
                                <strong>Object</strong> to the processing of
                                your data.
                            </li>
                            <li>
                                <strong>Withdraw your consent</strong> at any
                                time. This does not affect what we did before
                                you withdrew it.
                            </li>
                            <li>
                                <strong>Erasure or blocking</strong> of your
                                data when it is no longer needed or was
                                processed unlawfully.
                            </li>
                            <li>
                                <strong>Data portability:</strong> get a copy of
                                your data in a common electronic format.
                            </li>
                            <li>
                                <strong>Damages</strong>, and to{' '}
                                <strong>file a complaint</strong> with the
                                National Privacy Commission (privacy.gov.ph).
                            </li>
                        </ul>
                    </section>

                    <section>
                        <h2>8. Contact our Data Protection Officer</h2>
                        <p>
                            For questions, requests or complaints about your
                            data:
                        </p>
                        <address>
                            [Name], Data Protection Officer
                            <br />
                            Urdaneta City University, [office and address],
                            Urdaneta City, Pangasinan
                            <br />
                            Email: [dpo email address]
                            <br />
                            Phone: [phone number]
                        </address>
                    </section>

                    <section>
                        <h2>9. Changes to this policy</h2>
                        <p>
                            We may update this policy. The latest version is
                            always on this page with its effective date. If the
                            changes are significant, we will tell you by email
                            or when you log in.
                        </p>
                    </section>

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
