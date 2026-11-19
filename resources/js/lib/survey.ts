export const SURVEY_STEPS = [
    { slug: 'general-information', label: 'A. General Information' },
    { slug: 'educational-background', label: 'B. Educational Background' },
    { slug: 'trainings', label: 'C. Trainings & Advance Studies' },
    { slug: 'employment-data', label: 'D. Employment Data' },
    { slug: 'first-current-job', label: 'D. First / Current Job' },
    { slug: 'need-help', label: 'D. Need Help Landing a Job' },
    { slug: 'referral-submit', label: 'Referral & Submit' },
] as const;

export type StepSlug = (typeof SURVEY_STEPS)[number]['slug'];

/**
 * The steps this person will see. The job form is only for people who are
 * employed; once they answer No or Never Employed it is skipped.
 */
export function stepsFor(employed: string | null | undefined) {
    return SURVEY_STEPS.filter(
        (step) =>
            step.slug !== 'first-current-job' ||
            !employed ||
            employed === 'yes',
    );
}

export const stepUrl = (slug: string) => `/survey/${slug}`;

/** Adds the value to the list if it is missing, removes it if it is there. */
export const toggle = (list: string[], value: string) =>
    list.includes(value)
        ? list.filter((item) => item !== value)
        : [...list, value];

/** Fills a form's starting values from the saved draft, ignoring nulls from the server. */
export function withDefaults<T extends object>(
    defaults: T,
    draft: Partial<T> | undefined,
): T {
    const merged = { ...defaults } as Record<string, unknown>;

    for (const [key, value] of Object.entries(draft ?? {})) {
        if (value !== null && value !== undefined) {
            merged[key] = value;
        }
    }

    return merged as T;
}
