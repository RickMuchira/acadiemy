import { Head } from '@inertiajs/react';
import { CheckCircle, Upload as UploadIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import api from '@/lib/api';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Upload Marks', href: '/upload' },
];

interface AcademicYear { id: number; year: string; }
interface Exam { id: number; name: string; academic_year_id: number; academic_year: AcademicYear; }
interface SchoolClass { id: number; display_name: string; }
interface PreviewStudent { name: string; scores: Record<string, number>; }

type Step = 'select' | 'file' | 'preview' | 'done';

export default function Upload() {
    const [academicYears, setAcademicYears] = useState<AcademicYear[]>([]);
    const [exams, setExams] = useState<Exam[]>([]);
    const [classes, setClasses] = useState<SchoolClass[]>([]);

    const [selectedYear, setSelectedYear] = useState('');
    const [newYear, setNewYear] = useState('');
    const [selectedExam, setSelectedExam] = useState('');
    const [newExam, setNewExam] = useState('');
    const [selectedClass, setSelectedClass] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const [step, setStep] = useState<Step>('select');

    const [previewStudents, setPreviewStudents] = useState<PreviewStudent[]>([]);
    const [previewSubjects, setPreviewSubjects] = useState<string[]>([]);
    const [previewPath, setPreviewPath] = useState('');

    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const fileRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        api.get('/academic-years').then((r) => setAcademicYears(r.data));
        api.get('/classes').then((r) => setClasses(r.data));
    }, []);

    useEffect(() => {
        if (selectedYear) {
            api.get('/exams', { params: { academic_year_id: selectedYear } }).then((r) => setExams(r.data));
        }
    }, [selectedYear]);

    const createYear = async () => {
        if (!newYear.trim()) {
return;
}

        const r = await api.post('/academic-years', { year: newYear.trim() });
        setAcademicYears((p) => [...p, r.data]);
        setSelectedYear(String(r.data.id));
        setNewYear('');
    };

    const createExam = async () => {
        if (!newExam.trim() || !selectedYear) {
return;
}

        const r = await api.post('/exams', { name: newExam.trim(), academic_year_id: Number(selectedYear) });
        setExams((p) => [...p, r.data]);
        setSelectedExam(String(r.data.id));
        setNewExam('');
    };

    const handleFileUploadAndPreview = async () => {
        if (!file || !selectedExam || !selectedClass) {
            setError('Please fill in all fields and select a file.');

            return;
        }

        setLoading(true);
        setError('');

        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('exam_id', selectedExam);
            formData.append('class_id', selectedClass);
            formData.append('preview', '1');

            const r = await api.post('/upload-marks', formData);

            setPreviewStudents(r.data.students);
            setPreviewSubjects(r.data.subjects);
            setPreviewPath(r.data.path);
            setStep('preview');
        } catch (e: unknown) {
            setError(getErrorMessage(e));
        } finally {
            setLoading(false);
        }
    };

    const confirmImport = async () => {
        setLoading(true);
        setError('');

        try {
            await api.post('/upload-marks/commit', {
                path: previewPath,
                exam_id: Number(selectedExam),
                class_id: Number(selectedClass),
            });
            setStep('done');
        } catch (e: unknown) {
            setError(getErrorMessage(e));
        } finally {
            setLoading(false);
        }
    };

    const reset = () => {
        setSelectedYear('');
        setSelectedExam('');
        setSelectedClass('');
        setFile(null);
        setPreviewStudents([]);
        setPreviewSubjects([]);
        setPreviewPath('');
        setStep('select');
        setError('');

        if (fileRef.current) {
fileRef.current.value = '';
}
    };

    const filteredExams = exams.filter((e) => String(e.academic_year_id) === selectedYear);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Upload Marks" />
            <div className="flex flex-col gap-6 p-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Upload Marks</h1>

                {step === 'done' ? (
                    <div className="flex flex-col items-center gap-4 rounded-xl border border-emerald-200 bg-emerald-50 p-10 dark:border-emerald-800 dark:bg-emerald-900/20">
                        <CheckCircle className="h-16 w-16 text-emerald-500" />
                        <h2 className="text-xl font-semibold text-emerald-700 dark:text-emerald-400">Marks Imported Successfully!</h2>
                        <button onClick={reset} className="rounded-lg bg-emerald-600 px-6 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Upload Another File
                        </button>
                    </div>
                ) : step === 'preview' ? (
                    <div className="flex flex-col gap-4">
                        <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h2 className="mb-1 text-lg font-semibold">Preview</h2>
                            <p className="mb-4 text-sm text-gray-500">{previewStudents.length} students, {previewSubjects.length} subjects detected. Review before saving.</p>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-emerald-700 text-white">
                                        <tr>
                                            <th className="px-3 py-2 text-left">Student Name</th>
                                            {previewSubjects.map((s) => (
                                                <th key={s} className="px-3 py-2 text-center">{s}</th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {previewStudents.map((st, i) => (
                                            <tr key={i} className={i % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700/50'}>
                                                <td className="px-3 py-2 font-medium">{st.name}</td>
                                                {previewSubjects.map((s) => (
                                                    <td key={s} className="px-3 py-2 text-center">{st.scores[s] ?? '—'}</td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {error && <p className="rounded-lg bg-red-50 px-4 py-2 text-sm text-red-600 dark:bg-red-900/20">{error}</p>}

                        <div className="flex gap-3">
                            <button onClick={() => setStep('file')} className="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">
                                Back
                            </button>
                            <button
                                onClick={confirmImport}
                                disabled={loading}
                                className="rounded-lg bg-emerald-600 px-6 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                {loading ? 'Importing…' : 'Confirm & Import'}
                            </button>
                        </div>
                    </div>
                ) : (
                    <div className="max-w-xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="space-y-5">
                            {/* Academic Year */}
                            <div>
                                <label className="mb-1 block text-sm font-medium">Academic Year</label>
                                <div className="flex gap-2">
                                    <select
                                        className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        value={selectedYear}
                                        onChange={(e) => {
 setSelectedYear(e.target.value); setSelectedExam(''); 
}}
                                    >
                                        <option value="">Select year</option>
                                        {academicYears.map((y) => (
                                            <option key={y.id} value={y.id}>{y.year}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="mt-2 flex gap-2">
                                    <input
                                        className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        placeholder="Or create new: 2025"
                                        value={newYear}
                                        onChange={(e) => setNewYear(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && createYear()}
                                    />
                                    <button onClick={createYear} className="rounded-lg bg-gray-100 px-3 py-2 text-sm hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600">
                                        + Add
                                    </button>
                                </div>
                            </div>

                            {/* Exam */}
                            <div>
                                <label className="mb-1 block text-sm font-medium">Exam</label>
                                <select
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={selectedExam}
                                    onChange={(e) => setSelectedExam(e.target.value)}
                                    disabled={!selectedYear}
                                >
                                    <option value="">Select exam</option>
                                    {filteredExams.map((ex) => (
                                        <option key={ex.id} value={ex.id}>{ex.name}</option>
                                    ))}
                                </select>
                                <div className="mt-2 flex gap-2">
                                    <input
                                        className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        placeholder="Or create new: Opening Exam"
                                        value={newExam}
                                        onChange={(e) => setNewExam(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && createExam()}
                                        disabled={!selectedYear}
                                    />
                                    <button onClick={createExam} disabled={!selectedYear} className="rounded-lg bg-gray-100 px-3 py-2 text-sm hover:bg-gray-200 disabled:opacity-40 dark:bg-gray-700 dark:hover:bg-gray-600">
                                        + Add
                                    </button>
                                </div>
                            </div>

                            {/* Class */}
                            <div>
                                <label className="mb-1 block text-sm font-medium">Class</label>
                                <select
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={selectedClass}
                                    onChange={(e) => setSelectedClass(e.target.value)}
                                >
                                    <option value="">Select class</option>
                                    {classes.map((c) => (
                                        <option key={c.id} value={c.id}>{c.display_name}</option>
                                    ))}
                                </select>
                            </div>

                            {/* File */}
                            <div>
                                <label className="mb-1 block text-sm font-medium">Excel File (.xlsx, .csv)</label>
                                <div
                                    className="cursor-pointer rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-emerald-400 dark:border-gray-600 dark:hover:border-emerald-500"
                                    onClick={() => fileRef.current?.click()}
                                >
                                    <UploadIcon className="mx-auto mb-2 h-8 w-8 text-gray-400" />
                                    {file ? (
                                        <p className="text-sm font-medium text-emerald-600">{file.name}</p>
                                    ) : (
                                        <p className="text-sm text-gray-500">Click to select file or drag & drop</p>
                                    )}
                                    <input
                                        ref={fileRef}
                                        type="file"
                                        className="hidden"
                                        accept=".xlsx,.csv,.xls"
                                        onChange={(e) => setFile(e.target.files?.[0] ?? null)}
                                    />
                                </div>
                            </div>

                            {error && <p className="rounded-lg bg-red-50 px-4 py-2 text-sm text-red-600 dark:bg-red-900/20">{error}</p>}

                            <button
                                onClick={handleFileUploadAndPreview}
                                disabled={loading || !file || !selectedExam || !selectedClass}
                                className="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                {loading ? 'Processing…' : 'Preview Marks'}
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function getErrorMessage(e: unknown): string {
    if (typeof e === 'object' && e !== null && 'response' in e) {
        const err = e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };

        if (err.response?.data?.message) {
return err.response.data.message;
}

        const errors = err.response?.data?.errors;

        if (errors) {
return Object.values(errors).flat().join(', ');
}
    }

    return 'An error occurred. Please try again.';
}
