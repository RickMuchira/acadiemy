import { Head } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import api from '@/lib/api';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
];

interface AcademicYear {
    id: number;
    year: string;
}

interface Exam {
    id: number;
    name: string;
    academic_year_id: number;
    academic_year: AcademicYear;
}

export default function Exams() {
    const [academicYears, setAcademicYears] = useState<AcademicYear[]>([]);
    const [exams, setExams] = useState<Exam[]>([]);
    const [newYear, setNewYear] = useState('');
    const [newExam, setNewExam] = useState({ name: '', academic_year_id: '' });
    const [yearError, setYearError] = useState('');
    const [examError, setExamError] = useState('');
    const [saving, setSaving] = useState(false);

    const load = () => {
        api.get('/academic-years').then((r) => setAcademicYears(r.data));
        api.get('/exams').then((r) => setExams(r.data));
    };

    useEffect(() => {
        load();
    }, []);

    const addYear = async () => {
        if (!newYear.trim()) {
return;
}

        setSaving(true);

        try {
            await api.post('/academic-years', { year: newYear.trim() });
            setNewYear('');
            setYearError('');
            load();
        } catch (e: unknown) {
            if (axios_like(e)) {
setYearError(e.response?.data?.errors?.year?.[0] ?? 'Error');
}
        } finally {
            setSaving(false);
        }
    };

    const addExam = async () => {
        if (!newExam.name.trim() || !newExam.academic_year_id) {
return;
}

        setSaving(true);

        try {
            await api.post('/exams', { name: newExam.name.trim(), academic_year_id: Number(newExam.academic_year_id) });
            setNewExam({ name: '', academic_year_id: '' });
            setExamError('');
            load();
        } catch (e: unknown) {
            if (axios_like(e)) {
setExamError(e.response?.data?.message ?? 'Error');
}
        } finally {
            setSaving(false);
        }
    };

    const deleteExam = async (id: number) => {
        if (!confirm('Delete this exam? All related results will be removed.')) {
return;
}

        await api.delete(`/exams/${id}`);
        load();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Exams" />
            <div className="flex flex-col gap-6 p-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Exams</h1>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Academic Years */}
                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 className="mb-4 text-lg font-semibold">Academic Years</h2>
                        <div className="mb-3 flex gap-2">
                            <input
                                className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="e.g. 2025"
                                value={newYear}
                                onChange={(e) => setNewYear(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && addYear()}
                            />
                            <button
                                onClick={addYear}
                                disabled={saving}
                                className="flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                <Plus className="h-4 w-4" /> Add
                            </button>
                        </div>
                        {yearError && <p className="mb-2 text-sm text-red-500">{yearError}</p>}
                        <ul className="space-y-1">
                            {academicYears.map((y) => (
                                <li key={y.id} className="rounded-lg border border-gray-100 px-3 py-2 text-sm dark:border-gray-700">
                                    {y.year}
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Exams */}
                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 className="mb-4 text-lg font-semibold">Exams</h2>
                        <div className="mb-3 flex flex-col gap-2">
                            <select
                                className="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                value={newExam.academic_year_id}
                                onChange={(e) => setNewExam((p) => ({ ...p, academic_year_id: e.target.value }))}
                            >
                                <option value="">Select Academic Year</option>
                                {academicYears.map((y) => (
                                    <option key={y.id} value={y.id}>
                                        {y.year}
                                    </option>
                                ))}
                            </select>
                            <div className="flex gap-2">
                                <input
                                    className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="e.g. Opening Exam"
                                    value={newExam.name}
                                    onChange={(e) => setNewExam((p) => ({ ...p, name: e.target.value }))}
                                    onKeyDown={(e) => e.key === 'Enter' && addExam()}
                                />
                                <button
                                    onClick={addExam}
                                    disabled={saving}
                                    className="flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                                >
                                    <Plus className="h-4 w-4" /> Add
                                </button>
                            </div>
                            {examError && <p className="text-sm text-red-500">{examError}</p>}
                        </div>
                        <ul className="space-y-1">
                            {exams.map((exam) => (
                                <li key={exam.id} className="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2 text-sm dark:border-gray-700">
                                    <span>
                                        <span className="font-medium">{exam.name}</span>
                                        <span className="ml-2 text-gray-400">({exam.academic_year?.year})</span>
                                    </span>
                                    <button onClick={() => deleteExam(exam.id)} className="text-red-400 hover:text-red-600">
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

 
function axios_like(e: unknown): e is { response?: { data?: { errors?: Record<string, string[]>; message?: string } } } {
    return typeof e === 'object' && e !== null && 'response' in e;
}
