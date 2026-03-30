import { Head, Link } from '@inertiajs/react';
import { Download, Eye, FileArchive, GitMerge, Pencil, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import api from '@/lib/api';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Report Cards', href: '/report-cards' },
];

interface AcademicYear { id: number; year: string; }
interface Exam { id: number; name: string; academic_year_id: number; academic_year: AcademicYear; }
interface SchoolClass { id: number; display_name: string; }
interface Student {
    id: number;
    name: string;
    has_results: number;
    opener_results?: number;
    midterm_results?: number;
    status?: string;
}

export default function ReportCards() {
    const [academicYears, setAcademicYears] = useState<AcademicYear[]>([]);
    const [exams, setExams] = useState<Exam[]>([]);
    const [classes, setClasses] = useState<SchoolClass[]>([]);
    const [students, setStudents] = useState<Student[]>([]);

    const [selectedYear, setSelectedYear] = useState('');
    const [selectedExam, setSelectedExam] = useState('');
    const [selectedClass, setSelectedClass] = useState('');
    const [loading, setLoading] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [showMergeModal, setShowMergeModal] = useState(false);
    const [mergeSourceId, setMergeSourceId] = useState('');
    const [mergeTargetId, setMergeTargetId] = useState('');
    const [mergeKeepName, setMergeKeepName] = useState<'source' | 'target'>('target');
    const [merging, setMerging] = useState(false);
    const [showDeviation, setShowDeviation] = useState(true);

    useEffect(() => {
        api.get('/academic-years').then((r) => setAcademicYears(r.data));
        api.get('/classes').then((r) => setClasses(r.data));
    }, []);

    useEffect(() => {
        if (selectedYear) {
            api.get('/exams', { params: { academic_year_id: selectedYear } }).then((r) => setExams(r.data));
        }
    }, [selectedYear]);

    const loadStudents = async () => {
        if (!selectedClass || !selectedExam) {
return;
}

        setLoading(true);
        const r = await api.get('/report-cards', { params: { class_id: selectedClass, exam_id: selectedExam } });
        setStudents(r.data);
        setLoading(false);
    };

    useEffect(() => {
        if (selectedClass && selectedExam) {
loadStudents();
}
    }, [selectedClass, selectedExam]);

    const filteredExams = exams.filter((e) => String(e.academic_year_id) === selectedYear);

    const openerExam = filteredExams.find((e) => /open/i.test(e.name));
    const midtermExam = filteredExams.find((e) => /mid/i.test(e.name));
    const showAllOption = openerExam && midtermExam;

    const downloadPdf = (studentId: number) => {
        const params = new URLSearchParams({
            student_id: String(studentId),
            exam_id: String(selectedExam),
        });
        if (!showDeviation) {
            params.set('show_deviation', '0');
        }
        window.open(`/api/report-card/pdf?${params.toString()}`, '_blank');
    };

    const downloadBulkZip = () => {
        const params = new URLSearchParams({
            class_id: String(selectedClass),
            exam_id: String(selectedExam),
        });
        if (!showDeviation) {
            params.set('show_deviation', '0');
        }
        window.open(`/api/report-cards/bulk-pdf?${params.toString()}`, '_blank');
    };

    const deleteResultsForClass = async () => {
        if (!selectedClass || !selectedExam) {
return;
}

        if (!window.confirm('Delete all results for this exam and class? This cannot be undone.')) {
return;
}

        setDeleting(true);

        try {
            await api.delete('/results', { params: { class_id: selectedClass, exam_id: selectedExam } });
            await loadStudents();
        } finally {
            setDeleting(false);
        }
    };

    const openMergeModal = () => setShowMergeModal(true);
    const closeMergeModal = () => {
        setShowMergeModal(false);
        setMergeSourceId('');
        setMergeTargetId('');
        setMergeKeepName('target');
    };

    const mergeStudents = async () => {
        const src = parseInt(mergeSourceId, 10);
        const tgt = parseInt(mergeTargetId, 10);

        if (!src || !tgt || src === tgt) {
return;
}

        const srcName = students.find((s) => s.id === src)?.name ?? '';
        const tgtName = students.find((s) => s.id === tgt)?.name ?? '';

        if (!window.confirm(`Merge "${srcName}" into "${tgtName}"? All results will be combined under one record.`)) {
return;
}

        setMerging(true);

        try {
            await api.post('/students/merge', { source_id: src, target_id: tgt, keep_name: mergeKeepName });
            closeMergeModal();
            await loadStudents();
        } catch (e: unknown) {
            const err = e as { response?: { data?: { message?: string } } };
            alert(err.response?.data?.message ?? 'Merge failed.');
        } finally {
            setMerging(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Report Cards" />
            <div className="flex flex-col gap-6 p-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Report Cards</h1>

                {/* Filters */}
                <div className="grid grid-cols-1 gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:grid-cols-3">
                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-500 uppercase">Academic Year</label>
                        <select
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            value={selectedYear}
                            onChange={(e) => {
 setSelectedYear(e.target.value); setSelectedExam(''); setStudents([]); 
}}
                        >
                            <option value="">Select year</option>
                            {academicYears.map((y) => (
                                <option key={y.id} value={y.id}>{y.year}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-500 uppercase">Exam</label>
                        <select
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            value={selectedExam}
                            onChange={(e) => setSelectedExam(e.target.value)}
                            disabled={!selectedYear}
                        >
                            <option value="">Select exam</option>
                            {showAllOption && (
                                <option value={openerExam.id}>
                                    All (Opener + Midterm)
                                </option>
                            )}
                            {filteredExams.map((ex) => (
                                <option key={ex.id} value={ex.id}>{ex.name}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-500 uppercase">Class</label>
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
                </div>

                {/* Student list */}
                {students.length > 0 && (
                    <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                            <p className="text-sm font-medium text-gray-600 dark:text-gray-300">{students.length} students</p>
                        <div className="flex items-center gap-4">
                            <label className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    checked={showDeviation}
                                    onChange={(e) => setShowDeviation(e.target.checked)}
                                    className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span>Show Deviation column in PDFs</span>
                            </label>
                                <button
                                    onClick={openMergeModal}
                                    className="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                >
                                    <GitMerge className="h-4 w-4" /> Merge Students
                                </button>
                                <Link
                                    href={`/results-grid?class_id=${selectedClass}&exam_id=${selectedExam}`}
                                    className="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                >
                                    <Pencil className="h-4 w-4" /> Edit Marks
                                </Link>
                                <button
                                    onClick={deleteResultsForClass}
                                    disabled={deleting}
                                    className="inline-flex items-center gap-2 rounded-lg border border-red-200 px-4 py-2 text-sm text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950"
                                >
                                    {deleting ? (
                                        <span className="h-4 w-4 animate-spin rounded-full border-2 border-red-500 border-t-transparent" />
                                    ) : (
                                        <Trash2 className="h-4 w-4" />
                                    )}
                                    Delete Results
                                </button>
                                <button
                                    onClick={downloadBulkZip}
                                    className="flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                                >
                                    <FileArchive className="h-4 w-4" /> Print All (ZIP)
                                </button>
                            </div>
                        </div>
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">#</th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">Student Name</th>
                                    <th className="px-4 py-3 text-center font-medium text-gray-500">Status</th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {students.map((st, i) => (
                                    <tr key={st.id} className={`border-b border-gray-100 dark:border-gray-700 ${i % 2 === 0 ? '' : 'bg-gray-50 dark:bg-gray-700/30'}`}>
                                        <td className="px-4 py-3 text-gray-400">{i + 1}</td>
                                        <td className="px-4 py-3 font-medium">{st.name}</td>
                                        <td className="px-4 py-3 text-center">
                                            {(typeof st.opener_results === 'number' || typeof st.midterm_results === 'number') ? (
                                                (() => {
                                                    const opener = st.opener_results ?? 0;
                                                    const midterm = st.midterm_results ?? 0;
                                                    const status = st.status ?? '';

                                                    if (opener > 0 && midterm > 0) {
                                                        return (
                                                            <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                                Has opener &amp; midterm
                                                            </span>
                                                        );
                                                    }

                                                    if (opener > 0 && midterm === 0) {
                                                        return (
                                                            <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                                Missing all midterm marks
                                                            </span>
                                                        );
                                                    }

                                                    if (opener === 0 && midterm > 0) {
                                                        return (
                                                            <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                                Missing all opener marks
                                                            </span>
                                                        );
                                                    }

                                                    return (
                                                        <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700">
                                                            No results
                                                        </span>
                                                    );
                                                })()
                                            ) : (
                                                <>
                                                    {st.has_results > 0 ? (
                                                        <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                            Has results
                                                        </span>
                                                    ) : (
                                                        <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700">
                                                            No results
                                                        </span>
                                                    )}
                                                </>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={`/report-cards/${st.id}?exam_id=${selectedExam}`}
                                                className="mr-2 inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                                            >
                                                <Eye className="h-3.5 w-3.5" /> View
                                            </Link>
                                            <button
                                                onClick={() => downloadPdf(st.id)}
                                                className="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                                            >
                                                <Download className="h-3.5 w-3.5" /> PDF
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {!loading && selectedClass && selectedExam && students.length === 0 && (
                    <p className="text-sm text-gray-400">No students found for this class/exam combination.</p>
                )}

                {loading && (
                    <div className="flex items-center gap-2 text-sm text-gray-500">
                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent" />
                        Loading students…
                    </div>
                )}
            </div>

            {/* Merge students modal */}
            {showMergeModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h2 className="mb-2 text-lg font-semibold">Merge Duplicate Students</h2>
                        <p className="mb-4 text-sm text-gray-600 dark:text-gray-300">
                            Use when the same person was uploaded with different spellings (e.g. Opener vs Midterm). All results will be combined under one record.
                        </p>
                        <div className="space-y-4">
                            <div>
                                <label className="mb-1 block text-sm font-medium">Merge this record (will be removed)</label>
                                <select
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={mergeSourceId}
                                    onChange={(e) => setMergeSourceId(e.target.value)}
                                >
                                    <option value="">Select student…</option>
                                    {students.map((s) => (
                                        <option key={s.id} value={s.id}>{s.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium">Into this record (keeps results)</label>
                                <select
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={mergeTargetId}
                                    onChange={(e) => setMergeTargetId(e.target.value)}
                                >
                                    <option value="">Select student…</option>
                                    {students.filter((s) => s.id !== parseInt(mergeSourceId, 10)).map((s) => (
                                        <option key={s.id} value={s.id}>{s.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium">Final name to keep</label>
                                <select
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={mergeKeepName}
                                    onChange={(e) => setMergeKeepName(e.target.value as 'source' | 'target')}
                                >
                                    <option value="target">Keep &ldquo;Into&rdquo; record name</option>
                                    <option value="source">Use &ldquo;Merge&rdquo; record name</option>
                                </select>
                            </div>
                        </div>
                        <div className="mt-6 flex justify-end gap-3">
                            <button
                                onClick={closeMergeModal}
                                className="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={mergeStudents}
                                disabled={merging || !mergeSourceId || !mergeTargetId}
                                className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                {merging ? 'Merging…' : 'Merge'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
