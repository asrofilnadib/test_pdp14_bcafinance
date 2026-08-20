import { useEffect, useRef } from 'react';

export default function FilePondField({
    label,
    hint,
    file,
    existingLabel,
    onFile,
}) {
    const inputRef = useRef(null);
    const pondRef = useRef(null);
    const onFileRef = useRef(onFile);
    const initialFileRef = useRef(file);

    useEffect(() => {
        onFileRef.current = onFile;
    }, [onFile]);

    useEffect(() => {
        if (!window.FilePond || !inputRef.current) {
            return undefined;
        }

        const pond = window.FilePond.create(inputRef.current, {
            credits: false,
            allowMultiple: false,
            maxFiles: 1,
            maxFileSize: '5MB',
            instantUpload: false,
            storeAsFile: true,
            acceptedFileTypes: ['image/jpeg', 'image/png', 'application/pdf'],
            labelIdle: 'Seret file ke sini atau <span class="filepond--label-action">Pilih</span>',
            labelFileProcessing: 'Mengunggah',
            labelFileProcessingComplete: 'Selesai',
            labelTapToCancel: 'batal',
            labelTapToUndo: 'undo',
            labelFileTypeNotAllowed: 'Hanya JPG, PNG, atau PDF',
            labelMaxFileSizeExceeded: 'Ukuran file terlalu besar',
            labelMaxFileSize: 'Maksimal 5 MB',
            stylePanelAspectRatio: 0.4,
        });

        pondRef.current = pond;

        if (initialFileRef.current instanceof File) {
            pond.addFile(initialFileRef.current);
        }

        pond.on('addfile', (error, item) => {
            if (error || !item?.file) {
                return;
            }
            onFileRef.current?.(item.file);
        });

        pond.on('removefile', () => {
            onFileRef.current?.(null);
        });

        return () => {
            pond.destroy();
            pondRef.current = null;
        };
    }, []);

    return (
        <div className="rounded-xl border border-dashed border-slate-300 p-3">
            {label && <p className="mb-1 text-sm font-medium text-navy-900">{label}</p>}
            {hint && <p className="mb-2 text-xs text-muted-foreground">{hint}</p>}
            {existingLabel && (
                <p className="mb-2 text-xs text-emerald-700">Sudah ada di sistem: {existingLabel}</p>
            )}
            <input ref={inputRef} type="file" accept=".jpg,.jpeg,.png,.pdf" />
        </div>
    );
}
