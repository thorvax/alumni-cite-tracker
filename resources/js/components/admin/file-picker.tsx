import { FileSpreadsheet, Upload, X } from 'lucide-react';
import { type DragEvent, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

/** Same limits as GraduateImportController::preview. */
const EXTENSIONS = ['xlsx', 'csv'];
const MAX_BYTES = 5 * 1024 * 1024;

const sizeText = (bytes: number) =>
    bytes < 1024 * 1024
        ? `${Math.max(1, Math.round(bytes / 1024))} KB`
        : `${(bytes / 1024 / 1024).toFixed(1)} MB`;

/**
 * A clear upload area for one Excel/CSV file: click (or Enter/Space) to
 * browse, or drag the file onto it. Shows the chosen file with a Remove button.
 */
export default function FilePicker({
    file,
    onChange,
    disabled = false,
}: {
    file: File | null;
    onChange: (file: File | null) => void;
    disabled?: boolean;
}) {
    const input = useRef<HTMLInputElement>(null);
    const [dragging, setDragging] = useState(false);
    const [problem, setProblem] = useState<string | null>(null);

    const choose = (picked: File | null | undefined) => {
        if (!picked) {
            return;
        }

        const extension = picked.name.split('.').pop()?.toLowerCase() ?? '';

        if (!EXTENSIONS.includes(extension)) {
            setProblem(`"${picked.name}" is not an Excel (.xlsx) or CSV file.`);

            return;
        }

        if (picked.size > MAX_BYTES) {
            setProblem(`"${picked.name}" is larger than 5 MB.`);

            return;
        }

        setProblem(null);
        onChange(picked);
    };

    const clear = () => {
        setProblem(null);
        onChange(null);

        if (input.current) {
            input.current.value = '';
        }
    };

    const drop = (e: DragEvent<HTMLLabelElement>) => {
        e.preventDefault();
        setDragging(false);

        if (!disabled) {
            choose(e.dataTransfer.files[0]);
        }
    };

    return (
        <div>
            <label
                className={cn(
                    'ad-drop',
                    dragging && 'is-dragging',
                    file && 'has-file',
                    disabled && 'is-disabled',
                )}
                onDragOver={(e) => {
                    e.preventDefault();
                    setDragging(true);
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={drop}
            >
                <input
                    ref={input}
                    className="ad-visually-hidden"
                    type="file"
                    accept=".xlsx,.csv"
                    disabled={disabled}
                    onChange={(e) => choose(e.target.files?.[0])}
                />

                {file ? (
                    <FileSpreadsheet size={28} aria-hidden />
                ) : (
                    <Upload size={28} aria-hidden />
                )}

                <span className="ad-drop-title">
                    {file ? file.name : 'Choose an Excel or CSV file'}
                </span>
                <span className="ad-drop-hint">
                    {file
                        ? `${sizeText(file.size)} · click to choose another file`
                        : 'Click to browse, or drag the file here. .xlsx or .csv, up to 5 MB.'}
                </span>

                {!file && (
                    <span className="ad-btn ad-btn-sm" aria-hidden>
                        Browse files
                    </span>
                )}
            </label>

            {file && (
                <button
                    type="button"
                    className="ad-btn ad-btn-sm ad-drop-remove"
                    onClick={clear}
                    disabled={disabled}
                >
                    <X size={14} aria-hidden /> Remove file
                </button>
            )}

            {problem && (
                <p className="ad-error" role="alert">
                    {problem}
                </p>
            )}
        </div>
    );
}
