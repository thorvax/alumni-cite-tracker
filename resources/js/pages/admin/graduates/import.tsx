import { Head, Link, router, useForm } from '@inertiajs/react';
import type { RequestPayload } from '@inertiajs/core';
import { Download } from 'lucide-react';
import type { FormEvent } from 'react';
import FilePicker from '@/components/admin/file-picker';
import AdminLayout from '@/layouts/admin-layout';

type SheetRow = {
    row: number;
    student_id: string;
    last_name: string;
    email: string | null;
};

type Preview = {
    file: string;
    total: number;
    errors: { row: number; cell: string; type: string; message: string }[];
    already: SheetRow[];
    new_count: number;
    with_email: number;
    without_email: number;
};

type Props = {
    flash: string | null;
    preview: Preview | null;
};

const SHOWN = 200;

/**
 * Bulk Upload: upload a file, review it, import. The email status of everyone
 * uploaded is on the Invitations page (sidebar).
 */
export default function ImportGraduates({ flash, preview }: Props) {
    const upload = useForm<{ file: File | null }>({ file: null });
    const errors = upload.errors as Record<string, string | undefined>;

    const sendUpload = (e: FormEvent) => {
        e.preventDefault();
        upload.post('/graduates/import/preview', {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const post = (url: string, data: RequestPayload = {}) =>
        router.post(url, data, { preserveScroll: true });

    const hasErrors = (preview?.errors.length ?? 0) > 0;

    return (
        <>
            <Head title="Bulk Upload" />

            <AdminLayout
                active="import"
                page="graduates"
                title="Bulk Upload"
                subtitle="Upload graduates and email each one a personal survey link."
                actions={
                    <>
                        <Link href="/graduates/invitations" className="ad-btn">
                            Invitations
                        </Link>
                        <a
                            href="/graduates/import/template"
                            className="ad-btn ad-btn-solid"
                        >
                            <Download size={14} aria-hidden /> Download Template
                        </a>
                    </>
                }
            >
                <div className="ad-stack">
                    {flash && (
                        <p className="ad-flash" role="status">
                            {flash}
                        </p>
                    )}

                    <section className="ad-card">
                        <h2 className="ad-card-title">1. Upload a file</h2>
                        <p className="ad-note" style={{ margin: '8px 0 16px' }}>
                            Excel (.xlsx) or CSV with Student ID Number (8
                            digits, like 20244012) and Last Name. Optional:
                            Email, Course (ACT, BLIS or BSIT) and Year Graduated
                            (like 2024). Course and Year let Reports show the
                            response rate per course and year. Use the template
                            for the right format.
                        </p>

                        <form className="ad-upload" onSubmit={sendUpload}>
                            <FilePicker
                                file={upload.data.file}
                                onChange={(file) => {
                                    upload.clearErrors('file');
                                    upload.setData('file', file);
                                }}
                                disabled={upload.processing}
                            />
                            <button
                                type="submit"
                                className="ad-btn ad-btn-primary"
                                disabled={
                                    upload.processing || !upload.data.file
                                }
                            >
                                {upload.processing ? 'Checking…' : 'Check File'}
                            </button>
                        </form>
                        {errors.file && (
                            <p className="ad-error" role="alert">
                                {errors.file}
                            </p>
                        )}
                    </section>

                    {preview && (
                        <section className="ad-card">
                            <h2 className="ad-card-title">
                                2. Review {preview.file}
                            </h2>
                            <p
                                className="ad-note"
                                style={{ margin: '8px 0 0' }}
                            >
                                {preview.total} row(s) read.{' '}
                                {hasErrors
                                    ? `${preview.errors.length} problem(s) found. Nothing was saved.`
                                    : `${preview.new_count} new, ${preview.already.length} already uploaded.`}
                            </p>

                            {hasErrors && (
                                <>
                                    <p
                                        className="ad-note"
                                        style={{ margin: '12px 0 8px' }}
                                    >
                                        Fix these cells in your file, then
                                        upload it again.
                                    </p>
                                    <div
                                        className="ad-table"
                                        style={{
                                            ['--cols' as string]: '90px 1fr',
                                        }}
                                    >
                                        <div className="ad-tr ad-th">
                                            <span>Cell</span>
                                            <span>Problem</span>
                                        </div>
                                        {preview.errors
                                            .slice(0, SHOWN)
                                            .map((error, i) => (
                                                <div key={i} className="ad-tr">
                                                    <span className="ad-cell">
                                                        {error.cell}
                                                    </span>
                                                    <span
                                                        style={{
                                                            justifySelf:
                                                                'start',
                                                        }}
                                                    >
                                                        {error.message}
                                                    </span>
                                                </div>
                                            ))}
                                    </div>
                                    {preview.errors.length > SHOWN && (
                                        <p
                                            className="ad-note"
                                            style={{ marginTop: 8 }}
                                        >
                                            Showing the first {SHOWN} of{' '}
                                            {preview.errors.length} problems.
                                        </p>
                                    )}
                                </>
                            )}

                            {!hasErrors && preview.already.length > 0 && (
                                <>
                                    <p
                                        className="ad-note"
                                        style={{ margin: '12px 0 8px' }}
                                    >
                                        These Student ID Numbers are already
                                        uploaded and will be skipped:
                                    </p>
                                    <div
                                        className="ad-table"
                                        style={{
                                            ['--cols' as string]:
                                                '70px 130px 1fr 1.2fr',
                                        }}
                                    >
                                        <div className="ad-tr ad-th">
                                            <span>Row</span>
                                            <span>Student ID</span>
                                            <span>Last Name</span>
                                            <span>Email</span>
                                        </div>
                                        {preview.already
                                            .slice(0, SHOWN)
                                            .map((row) => (
                                                <div
                                                    key={row.row}
                                                    className="ad-tr"
                                                >
                                                    <span className="ad-faint">
                                                        {row.row}
                                                    </span>
                                                    <span>
                                                        {row.student_id}
                                                    </span>
                                                    <span>{row.last_name}</span>
                                                    <span
                                                        style={{
                                                            justifySelf:
                                                                'start',
                                                        }}
                                                    >
                                                        {row.email ?? '—'}
                                                    </span>
                                                </div>
                                            ))}
                                    </div>
                                </>
                            )}

                            {!hasErrors && (
                                <p
                                    className="ad-note"
                                    style={{ marginTop: 14 }}
                                >
                                    Importing {preview.new_count}:{' '}
                                    {preview.with_email} will be emailed,{' '}
                                    {preview.without_email} have no email.
                                </p>
                            )}

                            <div className="ad-actions">
                                <button
                                    type="button"
                                    className="ad-btn"
                                    onClick={() =>
                                        router.delete('/graduates/import', {
                                            preserveScroll: true,
                                        })
                                    }
                                >
                                    {hasErrors ? 'Clear' : 'Cancel'}
                                </button>
                                {!hasErrors && (
                                    <button
                                        type="button"
                                        className="ad-btn ad-btn-primary"
                                        disabled={preview.new_count === 0}
                                        onClick={() =>
                                            post('/graduates/import/confirm')
                                        }
                                    >
                                        {preview.already.length > 0
                                            ? `Skip ${preview.already.length} duplicate(s) and import ${preview.new_count}`
                                            : `Import ${preview.new_count}`}
                                    </button>
                                )}
                            </div>
                        </section>
                    )}
                </div>
            </AdminLayout>
        </>
    );
}
