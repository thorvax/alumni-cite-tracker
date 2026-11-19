import { Link } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdField, AdInput, AdSelect } from '@/components/admin/controls';
import LocationFields from '@/components/admin/location-fields';

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
};

const civilStatuses = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'];

/** The Personal Information form shared by Add Graduate and Edit Graduate. */
export default function GraduateForm({
    data,
    errors,
    processing,
    onChange,
    onSubmit,
    cancelHref,
    submitLabel,
}: {
    data: GraduateData;
    errors: Partial<Record<keyof GraduateData, string>>;
    processing: boolean;
    onChange: (patch: Partial<GraduateData>) => void;
    onSubmit: (e: FormEvent) => void;
    cancelHref: string;
    submitLabel: string;
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
