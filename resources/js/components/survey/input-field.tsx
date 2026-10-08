import React from 'react';
import type { FormEvent, PropsWithChildren } from 'react';

export const inputClass =
    'h-[38px] w-full rounded-md border border-transparent bg-[#1a1a1a] px-3.5 text-sm text-white placeholder:text-white/35 focus:border-white/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/25 [color-scheme:dark] disabled:cursor-not-allowed disabled:opacity-50';

export default function Field({
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
