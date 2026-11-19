import { Link } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdField, AdInput, AdSelect } from '@/components/admin/controls';
import LocationFields from '@/components/admin/location-fields';
import { courses, graduationYears } from '@/lib/survey-options';

export type GraduateData = {
    full_name: string;
    birth_date: string;
    gender: string;
    civil_status: string;
    email: string;
    contact_number: string;
    current_address: string;
    region: string;
    province: string;
    city: string;
    // School records
    student_id: string;
    last_name: string;
    course: string;
    year_graduated: string;
    employment_status: string;
    /** Add Graduate only: email the personal survey link after saving. */
    send_invite: boolean;
};

export const emptyGraduate: GraduateData = {
    full_name: '',
    birth_date: '',
    gender: '',
    civil_status: '',
    email: '',
    contact_number: '',
    current_address: '',
    region: '',
    province: '',
    city: '',
    student_id: '',
    last_name: '',
    course: '',
    year_graduated: '',
    employment_status: '',
    send_invite: true,
};

const civilStatuses = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'];

const employmentStatuses = [
    'Employed',
    'Self-employed',
    'Unemployed',
    'Further Studies',
];

/** Must match Graduate::COURSE_CODES. */
const courseCodes: Record<string, string> = {
    'Associate in Computer Technology': 'ACT',
    'Bachelor of Library in Information Science': 'BLIS',
    'Bachelor of Science in Information and Technology': 'BSIT',
};

/** The Personal Information form shared by Add Graduate and Edit Graduate. */
export default function GraduateForm({
    data,
    errors,
    processing,
    onChange,
    onSubmit,
    cancelHref,
    submitLabel,
    showInviteOption = false,
    hasLink = false,
}: {
    data: GraduateData;
    errors: Partial<Record<keyof GraduateData, string>>;
    processing: boolean;
    onChange: (patch: Partial<GraduateData>) => void;
    onSubmit: (e: FormEvent) => void;
    cancelHref: string;
    submitLabel: string;
    /** Show "Email the survey link now" (Add Graduate). */
    showInviteOption?: boolean;
    /** The graduate already has a personal survey link (Edit Graduate). */
    hasLink?: boolean;
}) {
    return (
        <form onSubmit={onSubmit} noValidate>
            <h2 className="ad-card-title" style={{ marginBottom: 18 }}>
                Personal Information
            </h2>

            <div className="ad-grid-2">
                <AdField
                    id="full_name"
                    label="Full Name"
                    required
                    error={errors.full_name}
                >
                    <AdInput
                        id="full_name"
                        placeholder="Enter full name"
                        value={data.full_name}
                        onChange={(e) =>
                            onChange({ full_name: e.target.value })
                        }
                    />
                </AdField>

                <AdField
                    id="birth_date"
                    label="Date of Birth"
                    required
                    error={errors.birth_date}
                >
                    <AdInput
                        id="birth_date"
                        type="date"
                        value={data.birth_date}
                        onChange={(e) =>
                            onChange({ birth_date: e.target.value })
                        }
                    />
                </AdField>

                <AdField
                    id="gender"
                    label="Gender"
                    required
                    error={errors.gender}
                >
                    <AdSelect
                        id="gender"
                        placeholder="Select gender"
                        options={['Male', 'Female']}
                        value={data.gender}
                        onChange={(e) => onChange({ gender: e.target.value })}
                    />
                </AdField>

                <AdField
                    id="civil_status"
                    label="Civil Status"
                    required
                    error={errors.civil_status}
                >
                    <AdSelect
                        id="civil_status"
                        placeholder="Select status"
                        options={civilStatuses}
                        value={data.civil_status}
                        onChange={(e) =>
                            onChange({ civil_status: e.target.value })
                        }
                    />
                </AdField>

                <AdField id="email" label="Email Address" error={errors.email}>
                    <AdInput
                        id="email"
                        type="email"
                        placeholder="Enter email address"
                        value={data.email}
                        onChange={(e) => onChange({ email: e.target.value })}
                    />
                </AdField>

                <AdField
                    id="contact_number"
                    label="Contact Number"
                    error={errors.contact_number}
                >
                    <AdInput
                        id="contact_number"
                        type="tel"
                        placeholder="Enter contact number"
                        value={data.contact_number}
                        onChange={(e) =>
                            onChange({ contact_number: e.target.value })
                        }
                    />
                </AdField>

                <AdField
                    id="current_address"
                    label="Current Address"
                    className="ad-span"
                    error={errors.current_address}
                >
                    <AdInput
                        id="current_address"
                        placeholder="Enter complete address"
                        value={data.current_address}
                        onChange={(e) =>
                            onChange({ current_address: e.target.value })
                        }
                    />
                </AdField>

                <LocationFields
                    value={data}
                    onChange={onChange}
                    errors={errors}
                />
            </div>

            <h2 className="ad-card-title" style={{ margin: '28px 0 6px' }}>
                School Records
            </h2>
            <p className="ad-note" style={{ margin: '0 0 18px' }}>
                {hasLink
                    ? 'This graduate already has a personal survey link (see Graduates > Bulk Upload).'
                    : 'With a Student ID Number the graduate gets a personal survey link, so they can create an alumni account and answer the survey.'}
            </p>

            <div className="ad-grid-2">
                <AdField
                    id="student_id"
                    label="Student ID Number"
                    error={errors.student_id}
                >
                    <AdInput
                        id="student_id"
                        inputMode="numeric"
                        maxLength={8}
                        placeholder="e.g. 20244012"
                        value={data.student_id}
                        onChange={(e) =>
                            onChange({ student_id: e.target.value })
                        }
                    />
                </AdField>

                <AdField
                    id="last_name"
                    label="Last Name"
                    required={data.student_id !== ''}
                    error={errors.last_name}
                >
                    <AdInput
                        id="last_name"
                        placeholder="Used with the Student ID to activate"
                        value={data.last_name}
                        onChange={(e) =>
                            onChange({ last_name: e.target.value })
                        }
                    />
                </AdField>

                <AdField id="course" label="Course" error={errors.course}>
                    <AdSelect
                        id="course"
                        placeholder="Select course"
                        options={courses.map((name) => ({
                            value: name,
                            label: `${courseCodes[name]}: ${name}`,
                        }))}
                        value={data.course}
                        onChange={(e) => onChange({ course: e.target.value })}
                    />
                </AdField>

                <AdField
                    id="year_graduated"
                    label="Year Graduated"
                    error={errors.year_graduated}
                >
                    <AdSelect
                        id="year_graduated"
                        placeholder="Select year"
                        options={graduationYears}
                        value={data.year_graduated}
                        onChange={(e) =>
                            onChange({ year_graduated: e.target.value })
                        }
                    />
                </AdField>

                <AdField
                    id="employment_status"
                    label="Employment Status"
                    error={errors.employment_status}
                >
                    <AdSelect
                        id="employment_status"
                        placeholder="Select status"
                        options={employmentStatuses}
                        value={data.employment_status}
                        onChange={(e) =>
                            onChange({ employment_status: e.target.value })
                        }
                    />
                </AdField>
            </div>

            {showInviteOption && (
                <label className="ad-check" style={{ marginTop: 18 }}>
                    <input
                        type="checkbox"
                        checked={data.send_invite}
                        onChange={(e) =>
                            onChange({ send_invite: e.target.checked })
                        }
                    />
                    Email the survey link now (needs a Student ID Number and an
                    email address)
                </label>
            )}

            <div className="ad-actions">
                <Link href={cancelHref} className="ad-btn">
                    Cancel
                </Link>
                <button
                    type="submit"
                    disabled={processing}
                    className="ad-btn ad-btn-primary"
                >
                    {submitLabel}
                </button>
            </div>
        </form>
    );
}
