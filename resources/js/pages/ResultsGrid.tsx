import { Head } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import api from '@/lib/api';
import type { BreadcrumbItem } from '@/types';

const breadcrumbsBase: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Report Cards', href: '/report-cards' },
];

interface Subject {
    id: number;
    name: string;
    display_order: number;
}

interface GridRow {
    student_id: number;
    student_name: string;
    exam_id: number;
    exam_label: string;
    scores: Record<number, number | null>;
}

interface GridResponse {
    class: { id: number; name: string };
    subjects: Subject[];
    students: GridRow[];
}

interface Props {
    classId: number;
    examId: number;
}

export default function ResultsGrid({ classId, examId }: Props) {
    const [data, setData] = useState<GridResponse | null>(null);
    const [rows, setRows] = useState<GridRow[]>([]);
    const [saving, setSaving] = useState(false);
    const [savedAt, setSavedAt] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = useMemo(() => {
        if (!data) {
            return [...breadcrumbsBase, { title: 'Edit Marks', href: '#' }];
        }

        return [
            ...breadcrumbsBase,
            { title: data.class.name, href: '#' },
            { title: 'Edit Marks', href: '#' },
        ];
    }, [data]);

    useEffect(() => {
        api.get('/results/grid', { params: { class_id: classId, exam_id: examId } }).then((r) => {
            setData(r.data);
            setRows(r.data.students);
        });
    }, [classId, examId]);

    const handleChange = (rowIndex: number, subjectId: number, value: string) => {
        setRows((prev) =>
            prev.map((row, i) =>
                i === rowIndex
                    ? {
                          ...row,
                          scores: {
                              ...row.scores,
                              [subjectId]: value === '' ? null : Number(value),
                          },
                      }
                    : row,
            ),
        );
    };

    const handleSave = async () => {
        if (!data) {
return;
}

        setSaving(true);

        try {
            await api.put('/results/grid', {
                class_id: data.class.id,
                exam_id: examId,
                rows: rows.map((row) => ({
                    student_id: row.student_id,
                    exam_id: row.exam_id,
                    scores: row.scores,
                })),
            });
            setSavedAt(new Date().toLocaleTimeString());
        } finally {
            setSaving(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Marks" />
            <div className="flex h-full flex-col gap-4 p-6">
                <div className="flex items-center justify-between">
                    <button
                        onClick={() => history.back()}
                        className="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        <ArrowLeft className="h-3.5 w-3.5" /> Back
                    </button>
                    <div className="flex items-center gap-3">
                        {savedAt && <span className="text-xs text-gray-400">Last saved at {savedAt}</span>}
                        <button
                            onClick={handleSave}
                            disabled={saving}
                            className="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                        >
                            <Save className="h-3.5 w-3.5" />
                            {saving ? 'Saving…' : 'Save Changes'}
                        </button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-[2fr,minmax(0,1.2fr)]">
                    {/* Marks grid */}
                    <div className="overflow-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        {data ? (
                            <table className="min-w-full text-xs">
                                <thead className="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    <tr>
                                        <th className="sticky left-0 z-10 bg-gray-50 px-3 py-2 text-left dark:bg-gray-800">
                                            Student
                                        </th>
                                        <th className="px-3 py-2 text-left">Exam</th>
                                        {data.subjects.map((subject) => (
                                            <th key={subject.id} className="px-3 py-2 text-center">
                                                {subject.name}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row, rowIndex) => (
                                        <tr
                                            key={row.student_id}
                                            className={rowIndex % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800'}
                                        >
                                            <td className="sticky left-0 z-10 bg-inherit px-3 py-2 text-left font-medium">
                                                {row.student_name}
                                            </td>
                                            <td className="px-3 py-2 text-left text-[11px] text-gray-500 dark:text-gray-300">
                                                {row.exam_label}
                                            </td>
                                            {data.subjects.map((subject) => (
                                                <td key={subject.id} className="px-2 py-1.5 text-center">
                                                    <input
                                                        type="number"
                                                        min={0}
                                                        max={100}
                                                        value={row.scores[subject.id] ?? ''}
                                                        onChange={(e) => handleChange(rowIndex, subject.id, e.target.value)}
                                                        className="w-16 rounded border border-gray-300 bg-white px-1 py-0.5 text-center text-xs dark:border-gray-600 dark:bg-gray-900"
                                                    />
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : (
                            <div className="flex items-center justify-center p-8 text-sm text-gray-500">
                                Loading grid…
                            </div>
                        )}
                    </div>

                    {/* Live report card-style preview for first selected student */}
                    <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h2 className="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Report Card Preview (First Student)
                        </h2>
                        {data && rows.length > 0 ? (
                            <div className="text-xs text-gray-700 dark:text-gray-200">
                                <div className="mb-2 text-sm font-bold">{rows[0].student_name}</div>
                                <div className="mb-1 text-[11px] text-gray-500">
                                    Class: {data.class.name}
                                </div>
                                <table className="mt-3 w-full border-collapse text-[11px]">
                                    <thead>
                                        <tr className="bg-emerald-700 text-white">
                                            <th className="px-2 py-1 text-left">Subject</th>
                                            <th className="px-2 py-1 text-center">Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.subjects.map((subject) => (
                                            <tr key={subject.id} className="odd:bg-gray-50 dark:odd:bg-gray-800">
                                                <td className="px-2 py-1">{subject.name}</td>
                                                <td className="px-2 py-1 text-center">
                                                    {rows[0].scores[subject.id] ?? '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-xs text-gray-500">No students in this class yet.</p>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

