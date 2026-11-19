import { NCR_CODE, type PsgcItem, usePsgcList } from '@/hooks/use-psgc';
import { AdField, AdSelect } from '@/components/admin/controls';

type Location = { region: string; province: string; city: string };

// Same place, different spelling ("Region I - Ilocos Region" vs "Region I (Ilocos Region)").
const normalize = (name: string) =>
    (name.toLowerCase().match(/[a-z0-9]+/g) ?? []).sort().join(' ');

const find = (items: PsgcItem[], name: string) =>
    name ? items.find((i) => normalize(i.name) === normalize(name)) : undefined;

/** Option names, plus the saved value so it still shows when it isn't in the list. */
function names(items: PsgcItem[], current: string) {
    const list = items.map((i) => i.name);

    return current && !list.includes(current) ? [current, ...list] : list;
}

/**
 * Region, province and city/municipality dropdowns backed by the PSGC API.
 * The saved values are place names, so older records still show their values.
 */
export default function LocationFields({
    value,
    onChange,
    errors,
}: {
    value: Location;
    onChange: (patch: Partial<Location>) => void;
    errors: Partial<Record<keyof Location, string>>;
}) {
    const regions = usePsgcList('/regions/');
    const region = find(regions.items, value.region);
    const isNcr = region?.code === NCR_CODE;

    const provinces = usePsgcList(
        region && !isNcr ? `/regions/${region.code}/provinces/` : null,
    );
    const province = isNcr ? undefined : find(provinces.items, value.province);

    const cities = usePsgcList(
        isNcr
            ? `/regions/${NCR_CODE}/cities-municipalities/`
            : province
              ? `/provinces/${province.code}/cities-municipalities/`
              : null,
    );

    const label = (
        list: { loading: boolean; failed: boolean },
        text: string,
    ) => (list.loading ? 'Loading…' : list.failed ? 'Could not load' : text);

    return (
        <div className="ad-grid-3 ad-span">
            <AdField id="region" label="Region" required error={errors.region}>
                <AdSelect
                    id="region"
                    placeholder={label(regions, 'Select region')}
                    options={names(regions.items, value.region)}
                    value={value.region}
                    onChange={(e) => {
                        const picked = find(regions.items, e.target.value);

                        onChange({
                            region: e.target.value,
                            province:
                                picked?.code === NCR_CODE ? 'Metro Manila' : '',
                            city: '',
                        });
                    }}
                />
            </AdField>

            <AdField
                id="province"
                label="Province"
                required
                error={errors.province}
            >
                <AdSelect
                    id="province"
                    placeholder={label(provinces, 'Select province')}
                    options={
                        isNcr
                            ? ['Metro Manila']
                            : names(provinces.items, value.province)
                    }
                    value={value.province}
                    disabled={isNcr}
                    onChange={(e) =>
                        onChange({ province: e.target.value, city: '' })
                    }
                />
            </AdField>

            <AdField
                id="city"
                label="City/Municipality"
                required
                error={errors.city}
            >
                <AdSelect
                    id="city"
                    placeholder={label(cities, 'Select city/municipality')}
                    options={names(cities.items, value.city)}
                    value={value.city}
                    onChange={(e) => onChange({ city: e.target.value })}
                />
            </AdField>
        </div>
    );
}
