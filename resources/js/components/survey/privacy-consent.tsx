import { privacy } from '@/routes';

/** Required "I have read and agree to the Privacy Policy" checkbox. */
export default function PrivacyConsent({
    checked,
    onChange,
    error,
    className,
}: {
    checked: boolean;
    onChange: (checked: boolean) => void;
    error?: string;
    className?: string;
}) {
    return (
        <div className={className}>
            <div className="sv-certify">
                <label className="sv-option">
                    <input
                        type="checkbox"
                        checked={checked}
                        onChange={(e) => onChange(e.target.checked)}
                    />
                    <span>
                        I have read and agree to the{' '}
                        <a
                            href={privacy.url()}
                            target="_blank"
                            rel="noreferrer"
                        >
                            Data Privacy Notice
                        </a>
                        . *
                    </span>
                </label>
            </div>
            {error && (
                <p className="sv-error" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
}
