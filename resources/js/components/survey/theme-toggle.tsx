import { Moon, Sun } from 'lucide-react';
import { useSyncExternalStore } from 'react';
import { useAppearance } from '@/hooks/use-appearance';

const subscribe = () => () => {};

export default function ThemeToggle() {
    const { appearance, updateAppearance } = useAppearance();

    // false on the server and during hydration, true afterwards. That keeps the
    // first client render identical to the server-rendered HTML.
    const mounted = useSyncExternalStore(
        subscribe,
        () => true,
        () => false,
    );

    const isDark =
        mounted &&
        (appearance === 'dark' ||
            (appearance === 'system' &&
                window.matchMedia('(prefers-color-scheme: dark)').matches));

    return (
        <div className="sv-toggle" role="group" aria-label="Color theme">
            <button
                type="button"
                aria-label="Light mode"
                aria-pressed={!isDark}
                onClick={() => updateAppearance('light')}
            >
                <Sun size={16} aria-hidden />
            </button>
            <button
                type="button"
                aria-label="Dark mode"
                aria-pressed={isDark}
                onClick={() => updateAppearance('dark')}
            >
                <Moon size={16} aria-hidden />
            </button>
        </div>
    );
}
