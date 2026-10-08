import React from 'react';
import type { FormEvent, PropsWithChildren } from 'react';

export default function SectionHeading({ children }: PropsWithChildren) {
    return (
        <h2 className="col-span-full mt-4 text-[13px] font-semibold tracking-wide text-white uppercase">
            {children}
        </h2>
    );
}
