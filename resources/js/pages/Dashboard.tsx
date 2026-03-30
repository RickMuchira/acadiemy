import { Head } from '@inertiajs/react';
import { BarChart3, Download, FileText, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import api from '@/lib/api';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const REPORT_SECTIONS: { key: string; label: string }[] = [
    { key: 'summary', label: 'Summary (students, classes, academic years)' },
    { key: 'school_averages', label: 'School-wide Opener / Midterm averages' },
    { key: 'class_performance', label: 'Performance by class table' },
    { key: 'years_exams', label: 'Academic years & exams table' },
    { key: 'footer', label: 'Footer (school name, internal use)' },
];

interface YearWithExams {
    year: string;
    exam_count: number;
    has_opener: boolean;
    has_midterm: boolean;
}

interface ClassSummary {
    class_name: string;
    student_count: number;
    opener_avg: number | null;
    midterm_avg: number | null;
    students_with_opener: number;
    students_with_midterm: number;
    missing_opener: number;
    missing_midterm: number;
}

interface Summary {
    total_students: number;
    total_classes: number;
    total_exams: number;
    total_academic_years: number;
    overall_opener_avg: number | null;
    overall_midterm_avg: number | null;
}

interface Analytics {
    summary: Summary;
    years_with_exams: YearWithExams[];
    class_summaries: ClassSummary[];
    subject_averages: { class_name: string; subject_name: string; opener_avg: number | null; midterm_avg: number | null }[];
}

interface SchoolClass {
    id: number;
    display_name: string;
    sort_order: number | null;
}

const defaultReportSections = Object.fromEntries(REPORT_SECTIONS.map((s) => [s.key, true]));

export default function Dashboard() {
    const [analytics, setAnalytics] = useState<Analytics | null>(null);
    const [loading, setLoading] = useState(true);
    const [reportSections, setReportSections] = useState<Record<string, boolean>>(defaultReportSections);
    const [showDeviation, setShowDeviation] = useState(true);
    const [classes, setClasses] = useState<SchoolClass[]>([]);
    const [savingClassOrder, setSavingClassOrder] = useState(false);

    useEffect(() => {
        Promise.all([api.get('/dashboard/analytics'), api.get('/classes')])
            .then(([analyticsRes, classesRes]) => {
                setAnalytics(analyticsRes.data);
                setClasses(
                    (classesRes.data as any[]).map((c) => ({
                        id: c.id,
                        display_name: c.display_name,
                        sort_order: c.sort_order ?? null,
                    })),
                );
            })
            .finally(() => setLoading(false));
    }, []);

    const setSection = (key: string, checked: boolean) => {
        setReportSections((prev) => ({ ...prev, [key]: checked }));
    };

    const moveClass = (index: number, direction: -1 | 1) => {
        setClasses((prev) => {
            const newIndex = index + direction;

            if (newIndex < 0 || newIndex >= prev.length) {
                return prev;
            }

            const next = [...prev];
            const [item] = next.splice(index, 1);
            next.splice(newIndex, 0, item);

            return next;
        });
    };

    const saveClassOrder = async () => {
        if (classes.length === 0) {
            return;
        }

        setSavingClassOrder(true);

        try {
            await api.put('/classes/reorder', {
                classes: classes.map((c, index) => ({
                    id: c.id,
                    sort_order: index,
                })),
            });
        } finally {
            setSavingClassOrder(false);
        }
    };

    const downloadPdf = () => {
        const included = REPORT_SECTIONS.filter((s) => reportSections[s.key]).map((s) => s.key);
        const params = new URLSearchParams();
        if (included.length < REPORT_SECTIONS.length) {
            params.set('include', included.join(','));
        }
        if (!showDeviation) {
            params.set('show_deviation', '0');
        }
        const query = params.toString();
        const url = `/api/dashboard/analytics-pdf${query ? `?${query}` : ''}`;
        window.open(url, '_blank');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                        <p className="mt-1 text-sm text-gray-500">Academic analytics and quick actions</p>
                    </div>
                    <button
                        onClick={downloadPdf}
                        className="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                    >
                        <Download className="h-4 w-4" /> Download Analytics PDF
                    </button>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 className="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Customize report</h2>
                    <p className="mb-3 text-xs text-gray-500 dark:text-gray-400">
                        Tick only the sections you want in the PDF. Then click Download Analytics PDF above.
                    </p>
                    <div className="flex flex-wrap gap-x-6 gap-y-2">
                        {REPORT_SECTIONS.map((s) => (
                            <label key={s.key} className="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    checked={reportSections[s.key] ?? true}
                                    onChange={(e) => setSection(s.key, e.target.checked)}
                                    className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span>{s.label}</span>
                            </label>
                        ))}
                    </div>
                    <div className="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                        <p className="mb-2 text-xs font-medium text-gray-600 dark:text-gray-400">Column options</p>
                        <label className="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input
                                type="checkbox"
                                checked={showDeviation}
                                onChange={(e) => setShowDeviation(e.target.checked)}
                                className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                            />
                            <span>Show Deviation column / row</span>
                        </label>
                    </div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 className="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Class order</h2>
                    <p className="mb-3 text-xs text-gray-500 dark:text-gray-400">
                        Arrange how classes appear in analytics and downloaded reports.
                    </p>
                    {classes.length === 0 ? (
                        <p className="text-xs text-gray-500 dark:text-gray-400">No classes available yet.</p>
                    ) : (
                        <>
                            <div className="mb-3 overflow-x-auto">
                                <table className="w-full text-xs">
                                    <thead className="bg-gray-50 dark:bg-gray-700/50">
                                        <tr>
                                            <th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Order</th>
                                            <th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Class</th>
                                            <th className="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Move</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {classes.map((c, index) => (
                                            <tr
                                                key={c.id}
                                                className={`border-b border-gray-100 dark:border-gray-700 ${
                                                    index % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800'
                                                }`}
                                            >
                                                <td className="px-3 py-2 text-gray-400">{index + 1}</td>
                                                <td className="px-3 py-2 font-medium text-gray-800 dark:text-gray-100">{c.display_name}</td>
                                                <td className="px-3 py-2 text-right">
                                                    <button
                                                        type="button"
                                                        onClick={() => moveClass(index, -1)}
                                                        className="mr-1 rounded border border-gray-300 px-2 py-0.5 text-xs hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700"
                                                        disabled={index === 0}
                                                    >
                                                        ↑
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => moveClass(index, 1)}
                                                        className="rounded border border-gray-300 px-2 py-0.5 text-xs hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700"
                                                        disabled={index === classes.length - 1}
                                                    >
                                                        ↓
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <button
                                type="button"
                                onClick={saveClassOrder}
                                disabled={savingClassOrder}
                                className="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                {savingClassOrder ? 'Saving…' : 'Save class order'}
                            </button>
                        </>
                    )}
                </div>

                {loading ? (
                    <div className="flex items-center justify-center py-12">
                        <div className="h-8 w-8 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent" />
                    </div>
                ) : analytics ? (
                    <>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <StatCard
                                label="Total Students"
                                value={analytics.summary.total_students}
                                icon={<Users className="h-5 w-5" />}
                                color="bg-emerald-600"
                            />
                            <StatCard
                                label="Classes"
                                value={analytics.summary.total_classes}
                                icon={<BarChart3 className="h-5 w-5" />}
                                color="bg-teal-600"
                            />
                            <StatCard
                                label="Academic Years"
                                value={analytics.summary.total_academic_years}
                                icon={<FileText className="h-5 w-5" />}
                                color="bg-green-700"
                            />
                        </div>

                        {(analytics.summary.overall_opener_avg !== null || analytics.summary.overall_midterm_avg !== null) && (
                            <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                <h2 className="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">School-wide averages</h2>
                                <p className="text-sm text-gray-600 dark:text-gray-300">
                                    Opener: <strong>{analytics.summary.overall_opener_avg ?? '—'}</strong>
                                    <span className="mx-2">|</span>
                                    Midterm: <strong>{analytics.summary.overall_midterm_avg ?? '—'}</strong>
                                </p>
                            </div>
                        )}

                        <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h2 className="border-b border-gray-100 px-4 py-3 text-lg font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                Performance by class
                            </h2>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-gray-50 dark:bg-gray-700/50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Class</th>
                                            <th className="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">Students</th>
                                            <th className="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">Opener avg</th>
                                            <th className="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">Midterm avg</th>
                                            {showDeviation && (
                                                <th className="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">Deviation</th>
                                            )}
                                            <th className="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">With Opener</th>
                                            <th className="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">With Midterm</th>
                                            <th className="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">Missing Opener</th>
                                            <th className="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">Missing Midterm</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {analytics.class_summaries.map((row, i) => (
                                            <tr
                                                key={row.class_name}
                                                className={`border-b border-gray-100 dark:border-gray-700 ${i % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700/30'}`}
                                            >
                                                <td className="px-4 py-3 font-medium">{row.class_name}</td>
                                                <td className="px-4 py-3 text-center">{row.student_count}</td>
                                                <td className="px-4 py-3 text-center">{row.opener_avg ?? '—'}</td>
                                                <td className="px-4 py-3 text-center">{row.midterm_avg ?? '—'}</td>
                                                {showDeviation && (
                                                    <td className="px-4 py-3 text-center">
                                                        {row.opener_avg !== null && row.midterm_avg !== null
                                                            ? `${row.midterm_avg - row.opener_avg > 0 ? '+' : ''}${(row.midterm_avg - row.opener_avg).toFixed(1)}`
                                                            : '—'}
                                                    </td>
                                                )}
                                                <td className="px-4 py-3 text-center">{row.students_with_opener}</td>
                                                <td className="px-4 py-3 text-center">{row.students_with_midterm}</td>
                                                <td className="px-4 py-3 text-center">{row.missing_opener}</td>
                                                <td className="px-4 py-3 text-center">{row.missing_midterm}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h2 className="border-b border-gray-100 px-4 py-3 text-lg font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                Subject averages by class
                            </h2>
                            <div className="space-y-4 p-4">
                                {Object.entries(
                                    analytics.subject_averages.reduce<Record<string, typeof analytics.subject_averages>>((acc, row) => {
                                        acc[row.class_name] = acc[row.class_name] || [];
                                        acc[row.class_name].push(row);

                                        return acc;
                                    }, {}),
                                ).map(([className, rows]) => {
                                    const sorted = [...rows].sort((a, b) => a.subject_name.localeCompare(b.subject_name));
                                    const deviations = sorted.map((r) =>
                                        r.opener_avg !== null && r.midterm_avg !== null
                                            ? Number((r.midterm_avg - r.opener_avg).toFixed(1))
                                            : null,
                                    );

                                    return (
                                        <div key={className}>
                                            <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-100">{className}</h3>
                                            <div className="overflow-x-auto">
                                                <table className="min-w-full border text-xs">
                                                    <thead>
                                                        <tr className="bg-gray-50 dark:bg-gray-700/50">
                                                            <th className="px-2 py-1 text-left font-medium text-gray-600 dark:text-gray-300">Row</th>
                                                            {sorted.map((r) => (
                                                                <th
                                                                    key={r.subject_name}
                                                                    className="px-2 py-1 text-center font-medium text-gray-600 dark:text-gray-300"
                                                                >
                                                                    {r.subject_name}
                                                                </th>
                                                            ))}
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td className="px-2 py-1 font-medium text-gray-700 dark:text-gray-200">Opener</td>
                                                            {sorted.map((r) => (
                                                                <td key={`opener-${r.subject_name}`} className="px-2 py-1 text-center">
                                                                    {r.opener_avg ?? '—'}
                                                                </td>
                                                            ))}
                                                        </tr>
                                                        <tr>
                                                            <td className="px-2 py-1 font-medium text-gray-700 dark:text-gray-200">Midterm</td>
                                                            {sorted.map((r) => (
                                                                <td key={`midterm-${r.subject_name}`} className="px-2 py-1 text-center">
                                                                    {r.midterm_avg ?? '—'}
                                                                </td>
                                                            ))}
                                                        </tr>
                                                        {showDeviation && (
                                                            <tr>
                                                                <td className="px-2 py-1 font-medium text-gray-700 dark:text-gray-200">Deviation</td>
                                                                {deviations.map((d, idx) => {
                                                                    const baseClass = 'px-2 py-1 text-center font-semibold';
                                                                    const colorClass =
                                                                        d === null
                                                                            ? 'text-gray-400'
                                                                            : d > 0
                                                                              ? 'text-emerald-700 dark:text-emerald-400'
                                                                              : d < 0
                                                                                ? 'text-red-700 dark:text-red-400'
                                                                                : 'text-gray-700 dark:text-gray-200';

                                                                    return (
                                                                        <td key={`dev-${sorted[idx].subject_name}`} className={`${baseClass} ${colorClass}`}>
                                                                            {d === null ? '—' : `${d > 0 ? '+' : ''}${d.toFixed(1)}`}
                                                                        </td>
                                                                    );
                                                                })}
                                                            </tr>
                                                        )}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h2 className="border-b border-gray-100 px-4 py-3 text-lg font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                Academic years &amp; exams
                            </h2>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-gray-50 dark:bg-gray-700/50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Year</th>
                                            <th className="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">Exams</th>
                                            <th className="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">Opener</th>
                                            <th className="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">Midterm</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {analytics.years_with_exams.map((y, i) => (
                                            <tr
                                                key={y.year}
                                                className={`border-b border-gray-100 dark:border-gray-700 ${i % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700/30'}`}
                                            >
                                                <td className="px-4 py-3 font-medium">{y.year}</td>
                                                <td className="px-4 py-3 text-center">{y.exam_count}</td>
                                                <td className="px-4 py-3 text-center">{y.has_opener ? 'Yes' : 'No'}</td>
                                                <td className="px-4 py-3 text-center">{y.has_midterm ? 'Yes' : 'No'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h2 className="mb-2 text-lg font-semibold text-gray-800 dark:text-white">Quick start</h2>
                            <ul className="list-inside list-disc space-y-1 text-sm text-gray-600 dark:text-gray-300">
                                <li>Go to <strong>Classes</strong> to manage your class list and subjects.</li>
                                <li>Go to <strong>Exams</strong> to create academic years and exam records.</li>
                                <li>Go to <strong>Upload Marks</strong> to import student scores from Excel.</li>
                                <li>Go to <strong>Report Cards</strong> to preview and print student results.</li>
                                <li>Use <strong>Download Analytics PDF</strong> above for a printable report for the academic department.</li>
                            </ul>
                        </div>
                    </>
                ) : (
                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p className="text-sm text-gray-500">Unable to load analytics.</p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function StatCard({
    label,
    value,
    icon,
    color,
}: {
    label: string;
    value: number;
    icon: React.ReactNode;
    color: string;
}) {
    return (
        <div className={`rounded-xl p-6 text-white shadow-sm ${color}`}>
            <div className="flex items-center justify-between">
                <p className="text-sm font-medium opacity-90">{label}</p>
                {icon}
            </div>
            <p className="mt-2 text-3xl font-bold">{value}</p>
        </div>
    );
}
