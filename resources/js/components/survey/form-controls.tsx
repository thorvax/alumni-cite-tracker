import type {
    InputHTMLAttributes,
    PropsWithChildren,
    SelectHTMLAttributes,
    TextareaHTMLAttributes,
} from 'react';
import { cn } from '@/lib/utils';

type LabelProps = {
    label: string;
    required?: boolean;
    error?: string;
    strong?: boolean;
    accent?: boolean;
    className?: string;
};

/** A label above a single input, select or textarea. */
export function Field({
    id,
    label,
    required,
    error,
    strong,
    accent,
    className,
    children,
}: PropsWithChildren<LabelProps & { id: string }>) {
    return (
        <div className={className}>
            <label
                htmlFor={id}
                className={cn(
                    'sv-label',
                    strong && 'sv-label-strong',
                    accent && 'sv-accent',
                )}
            >
                {label}
                {required && ' *'}
            </label>
            {children}
            {error && (
                <p className="sv-error" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
}

/** A question above a group of radios or checkboxes. */
export function Question({
    label,
    required,
    error,
    strong,
    className,
    children,
}: PropsWithChildren<LabelProps>) {
    return (
        <fieldset className={cn('sv-fieldset', className)}>
            <legend className={cn('sv-label', strong && 'sv-label-strong')}>
                {label}
                {required && ' *'}
            </legend>
            {children}
            {error && (
                <p className="sv-error" role="alert">
                    {error}
                </p>
            )}
        </fieldset>
    );
}

/** Big uppercase heading (boxed in light mode), e.g. ADDRESS. */
export function SectionHeading({ children }: PropsWithChildren) {
    return <h2 className="sv-heading">{children}</h2>;
}

/** Small uppercase heading with a rule under it (step B). */
export function SubHeading({ children }: PropsWithChildren) {
    return <h2 className="sv-subhead">{children}</h2>;
}

export function TextInput({
    className,
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return <input {...props} className={cn('sv-input', className)} />;
}

export function TextArea({
    className,
    ...props
}: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return <textarea {...props} className={cn('sv-textarea', className)} />;
}

type Option = string | { value: string; label: string };

export function SelectInput({
    options,
    placeholder = '— Select —',
    className,
    ...props
}: SelectHTMLAttributes<HTMLSelectElement> & {
    options: Option[];
    placeholder?: string;
}) {
    return (
        <select
            {...props}
            className={cn('sv-select', className)}
            data-empty={!props.value}
        >
            <option value="">{placeholder}</option>
            {options.map((option) => {
                const { value, label } =
                    typeof option === 'string'
                        ? { value: option, label: option }
                        : option;

                return (
                    <option key={value} value={value}>
                        {label}
                    </option>
                );
            })}
        </select>
    );
}

export type ChoiceOption = { value: string; label: string; other?: boolean };

type OtherProps = {
    otherText?: string;
    onOtherText?: (value: string) => void;
    otherPlaceholder?: string;
};

function OtherInput({
    otherText = '',
    onOtherText,
    otherPlaceholder = 'specify',
}: OtherProps) {
    return (
        <TextInput
            className="sv-input-sm"
            placeholder={otherPlaceholder}
            aria-label="Other, please specify"
            value={otherText}
            onChange={(e) => onOtherText?.(e.target.value)}
        />
    );
}

export function RadioGroup({
    name,
    options,
    value,
    onChange,
    stack,
    ...other
}: {
    name: string;
    options: ChoiceOption[];
    value: string;
    onChange: (value: string) => void;
    stack?: boolean;
} & OtherProps) {
    return (
        <div className={stack ? 'sv-stack' : 'sv-group'} role="radiogroup">
            {options.map((option) => (
                <div key={option.value} className="sv-option-row">
                    <label className="sv-option">
                        <input
                            type="radio"
                            name={name}
                            value={option.value}
                            checked={value === option.value}
                            onChange={() => onChange(option.value)}
                        />
                        {option.other ? 'Other:' : option.label}
                    </label>
                    {option.other && <OtherInput {...other} />}
                </div>
            ))}
        </div>
    );
}

export function CheckGroup({
    options,
    values,
    onToggle,
    stack,
    ...other
}: {
    options: ChoiceOption[];
    values: string[];
    onToggle: (value: string) => void;
    stack?: boolean;
} & OtherProps) {
    return (
        <div className={stack ? 'sv-stack' : 'sv-group'}>
            {options.map((option) => (
                <div key={option.value} className="sv-option-row">
                    <label className="sv-option">
                        <input
                            type="checkbox"
                            value={option.value}
                            checked={values.includes(option.value)}
                            onChange={() => onToggle(option.value)}
                        />
                        {option.other ? 'Other:' : option.label}
                    </label>
                    {option.other && <OtherInput {...other} />}
                </div>
            ))}
        </div>
    );
}
