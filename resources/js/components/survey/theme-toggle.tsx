import { Moon, Sun } from 'lucide-react';
import { useAppearance } from '@/hooks/use-appearance';

export default function ThemeToggle() {
    const { appearance, updateAppearance } = useAppearance();

    const isDark =
        appearance === 'dark' ||
        (appearance === 'system' &&
            typeof window !== 'undefined' &&
            window.matchMedia('(prefers-color-scheme: dark)').matches);

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
