import { useEffect, useState } from 'react';

// Official Philippine Standard Geographic Code (PSGC) data, served as static JSON.
const BASE = 'https://psgc.gitlab.io/api';

// The National Capital Region has no provinces; its cities hang directly under the region.
export const NCR_CODE = '130000000';

export type PsgcItem = { code: string; name: string };

async function getList(
    path: string,
    signal: AbortSignal,
): Promise<PsgcItem[]> {
    const res = await fetch(`${BASE}${path}`, { signal });

    if (!res.ok) {
        throw new Error(`PSGC request failed (${res.status})`);
    }

    const items: PsgcItem[] = await res.json();

    return items
        .map(({ code, name }) => ({ code, name }))
        .sort((a, b) => a.name.localeCompare(b.name));
}

type State = { path: string; items: PsgcItem[]; failed: boolean };

/** Loads a PSGC list. Pass null to skip loading (e.g. parent not chosen yet). */
export function usePsgcList(path: string | null) {
    const [state, setState] = useState<State | null>(null);

    useEffect(() => {
        if (!path) {
            return;
        }

        const controller = new AbortController();

        getList(path, controller.signal)
            .then((items) => setState({ path, items, failed: false }))
            .catch((error) => {
                if (error.name !== 'AbortError') {
                    setState({ path, items: [], failed: true });
                }
            });

        return () => controller.abort();
    }, [path]);

    const current = path && state?.path === path ? state : null;

    return {
        items: current?.items ?? [],
        loading: Boolean(path) && !current,
        failed: current?.failed ?? false,
    };
}
