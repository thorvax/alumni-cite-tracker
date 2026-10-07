import { Head, useForm } from '@inertiajs/react';
import type { FormEvent, PropsWithChildren } from 'react';
import SurveyLayout, { SURVEY_FORM_ID } from '@/layouts/survey-layout';

type GeneralInformationData = {
    first_name: string;
    middle_name: string;
    last_name: string;
    civil_status: string;
    sex: '' | 'male' | 'female';
    birth_date: string;
    region: string;
    province: string;
    city: string;
    barangay: string;
    house_no: string;
    mobile_primary: string;
    mobile_secondary: string;
};

const civilStatuses = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'];

const regions = [
    'NCR - National Capital Region',
    'CAR - Cordillera Administrative Region',
    'Region I - Ilocos Region',
    'Region II - Cagayan Valley',
    'Region III - Central Luzon',
    'Region IV-A - CALABARZON',
    'MIMAROPA Region',
    'Region V - Bicol Region',
    'Region VI - Western Visayas',
    'Region VII - Central Visayas',
    'Region VIII - Eastern Visayas',
    'Region IX - Zamboanga Peninsula',
    'Region X - Northern Mindanao',
    'Region XI - Davao Region',
    'Region XII - SOCCSKSARGEN',
    'Region XIII - Caraga',
    'BARMM - Bangsamoro Autonomous Region',
];

const inputClass =
    'h-[38px] w-full rounded-md border border-transparent bg-[#1a1a1a] px-3.5 text-sm text-white placeholder:text-white/35 focus:border-white/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/25 [color-scheme:dark]';

function Field({
    id,
    label,
    required,
    error,
    children,
}: PropsWithChildren<{
    id: string;
    label: string;
    required?: boolean;
    error?: string;
}>) {
    return (
        <div>
            <label
                htmlFor={id}
                className="mb-2 block text-[13px] font-medium text-white"
            >
                {label}
                {required && ' *'}
            </label>
            {children}
            {error && <p className="mt-1.5 text-xs text-red-400">{error}</p>}
        </div>
    );
}

function SectionHeading({ children }: PropsWithChildren) {
    return (
        <h2 className="col-span-full mt-4 text-[13px] font-semibold tracking-wide text-white uppercase">
            {children}
        </h2>
    );
}

export default function GeneralInformation({
    draft,
}: {
    draft: Partial<Record<keyof GeneralInformationData, string | null>>;
}) {
    const { data, setData, post, processing, errors } =
        useForm<GeneralInformationData>({
            first_name: draft.first_name ?? '',
            middle_name: draft.middle_name ?? '',
            last_name: draft.last_name ?? '',
            civil_status: draft.civil_status ?? '',
            sex: (draft.sex as GeneralInformationData['sex']) ?? '',
            birth_date: draft.birth_date ?? '',
            region: draft.region ?? '',
            province: draft.province ?? '',
            city: draft.city ?? '',
            barangay: draft.barangay ?? '',
            house_no: draft.house_no ?? '',
            mobile_primary: draft.mobile_primary ?? '',
            mobile_secondary: draft.mobile_secondary ?? '',
        });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/survey/general-information');
    };

    return (
        <>
            <Head title="General Information" />

            <SurveyLayout
                current={1}
                title="A. General Information"
                description="Please provide your personal details."
                backHref="/"
                processing={processing}
            >
                <form
                    id={SURVEY_FORM_ID}
                    onSubmit={submit}
                    className="grid grid-cols-1 gap-x-5 gap-y-5 md:grid-cols-2"
                >
                    <Field id="first_name" label="First Name" required error={errors.first_name}>
                        <input
                            id="first_name"
                            className={inputClass}
                            placeholder="e.g. Juan"
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                        />
                    </Field>

                    <Field id="middle_name" label="Middle Name" error={errors.middle_name}>
                        <input
                            id="middle_name"
                            className={inputClass}
                            placeholder="Optional"
                            value={data.middle_name}
                            onChange={(e) => setData('middle_name', e.target.value)}
                        />
                    </Field>

                    <Field id="last_name" label="Last Name" required error={errors.last_name}>
                        <input
                            id="last_name"
                            className={inputClass}
                            placeholder="e.g. dela Cruz"
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                        />
                    </Field>

                    <Field id="civil_status" label="Civil Status" required error={errors.civil_status}>
                        <select
                            id="civil_status"
                            className={inputClass}
                            value={data.civil_status}
                            onChange={(e) => setData('civil_status', e.target.value)}
                        >
                            <option value="">— Select —</option>
                            {civilStatuses.map((s) => (
                                <option key={s} value={s}>
                                    {s}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field id="sex-male" label="Sex" required error={errors.sex}>
                        <div className="flex items-center gap-5 pt-1">
                            {(['male', 'female'] as const).map((value) => (
                                <label
                                    key={value}
                                    htmlFor={`sex-${value}`}
                                    className="flex cursor-pointer items-center gap-2 text-sm"
                                >
                                    <input
                                        id={`sex-${value}`}
                                        type="radio"
                                        name="sex"
                                        value={value}
                                        checked={data.sex === value}
                                        onChange={() => setData('sex', value)}
                                        className="size-3.5 accent-white"
                                    />
                                    {value === 'male' ? 'Male' : 'Female'}
                                </label>
                            ))}
                        </div>
                    </Field>

                    <Field id="birth_date" label="Birth Date" required error={errors.birth_date}>
                        <input
                            id="birth_date"
                            type="date"
                            className={inputClass}
                            value={data.birth_date}
                            onChange={(e) => setData('birth_date', e.target.value)}
                        />
                    </Field>

                    <SectionHeading>Address</SectionHeading>

                    <Field id="region" label="Select your region" required error={errors.region}>
                        <select
                            id="region"
                            className={inputClass}
                            value={data.region}
                            onChange={(e) => setData('region', e.target.value)}
                        >
                            <option value="">— Select —</option>
                            {regions.map((r) => (
                                <option key={r} value={r}>
                                    {r}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field id="province" label="Province" required error={errors.province}>
                        <input
                            id="province"
                            className={inputClass}
                            placeholder="e.g. Pangasinan"
                            value={data.province}
                            onChange={(e) => setData('province', e.target.value)}
                        />
                    </Field>

                    <Field id="city" label="City/Town" required error={errors.city}>
                        <input
                            id="city"
                            className={inputClass}
                            placeholder="e.g. Urdaneta City"
                            value={data.city}
                            onChange={(e) => setData('city', e.target.value)}
                        />
                    </Field>

                    <Field id="barangay" label="Barangay" required error={errors.barangay}>
                        <input
                            id="barangay"
                            className={inputClass}
                            placeholder="e.g. Nancamaliran"
                            value={data.barangay}
                            onChange={(e) => setData('barangay', e.target.value)}
                        />
                    </Field>

                    <Field id="house_no" label="House #, Building #" error={errors.house_no}>
                        <input
                            id="house_no"
                            className={inputClass}
                            placeholder="Optional"
                            value={data.house_no}
                            onChange={(e) => setData('house_no', e.target.value)}
                        />
                    </Field>

                    <SectionHeading>Contact</SectionHeading>

                    <Field id="mobile_primary" label="Mobile Number (Primary)" required error={errors.mobile_primary}>
                        <input
                            id="mobile_primary"
                            type="tel"
                            inputMode="tel"
                            className={inputClass}
                            placeholder="e.g. 09xx-xxx-xxxx"
                            value={data.mobile_primary}
                            onChange={(e) => setData('mobile_primary', e.target.value)}
                        />
                    </Field>

                    <Field id="mobile_secondary" label="Mobile Number (Secondary)" error={errors.mobile_secondary}>
                        <input
                            id="mobile_secondary"
                            type="tel"
                            inputMode="tel"
                            className={inputClass}
                            placeholder="Optional"
                            value={data.mobile_secondary}
                            onChange={(e) => setData('mobile_secondary', e.target.value)}
                        />
                    </Field>
                </form>
            </SurveyLayout>
        </>
    );
}
