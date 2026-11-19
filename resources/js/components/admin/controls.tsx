import type {
    InputHTMLAttributes,
    PropsWithChildren,
    SelectHTMLAttributes,
} from 'react';
import { cn } from '@/lib/utils';

export function AdField({
    id,
    label,
    required,
    error,
    className,
    children,
}: PropsWithChildren<{
    id: string;
    label: string;
    required?: boolean;
    error?: string;
    className?: string;
}>) {
    return (
        <div className={className}>
            <label htmlFor={id} className="ad-label">
                {label}
                {required && ' *'}
            </label>
            {children}
            {error && (
                <p className="ad-error" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
}

export function AdInput({
    className,
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return <input {...props} className={cn('ad-input', className)} />;
}

type Option = string | { value: string; label: string };

export function AdSelect({
    options,
    placeholder,
    className,
    ...props
}: SelectHTMLAttributes<HTMLSelectElement> & {
    options: Option[];
    placeholder: string;
}) {
    return (
        <select {...props} className={cn('ad-select', className)}>
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
