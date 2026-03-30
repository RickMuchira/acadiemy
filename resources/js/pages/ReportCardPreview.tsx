import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Download, Printer } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import api from '@/lib/api';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Report Cards', href: '/report-cards' },
    { title: 'Preview', href: '#' },
];

interface ReportRow {
    subject: string;
    score: number;
    sublevel: string;
    grade: string;
    description: string;
}
interface ReportPage {
    exam: { name: string; academic_year: { year: string } };
    examName: string;
    rows: ReportRow[];
    total: number;
    average: number;
    averageSublevel: string | null;
    averageDescription: string | null;
}
interface GradingEntry {
    range: string;
    sublevel: string;
    grade: string;
    description: string;
}
interface ReportData {
    student: { id: number; name: string; school_class: { display_name: string; class_teacher_name: string } };
    settings: { school_name: string; motto: string; location: string; head_teacher_name: string | null; logo_path: string | null };
    pages: ReportPage[];
    gradingScale: GradingEntry[];
    logoUrl: string | null;
}

export default function ReportCardPreview({ studentId }: { studentId: number }) {
    const [data, setData] = useState<ReportData | null>(null);
    const [loading, setLoading] = useState(true);

    const examId = new URLSearchParams(window.location.search).get('exam_id');

    useEffect(() => {
        if (!examId) {
return;
}

        api.get(`/report-card/preview/${studentId}`, { params: { exam_id: examId } })
            .then((r) => {
 setData(r.data); setLoading(false); 
});
    }, [studentId, examId]);

    const downloadPdf = () => {
        window.open(`/api/report-card/pdf?student_id=${studentId}&exam_id=${examId}`, '_blank');
    };

    const print = () => window.print();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={data ? `${data.student.name} — Report Card` : 'Report Card'} />

            {/* Action bar — hidden on print */}
            <div className="flex items-center gap-3 border-b border-gray-200 bg-white px-6 py-3 print:hidden dark:border-gray-700 dark:bg-gray-800">
                <button
                    onClick={() => router.visit('/report-cards')}
                    className="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    <ArrowLeft className="h-4 w-4" /> Back
                </button>
                <div className="flex-1" />
                <button
                    onClick={print}
                    className="flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                >
                    <Printer className="h-4 w-4" /> Print
                </button>
                <button
                    onClick={downloadPdf}
                    className="flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                >
                    <Download className="h-4 w-4" /> Download PDF
                </button>
            </div>

            {loading ? (
                <div className="flex items-center justify-center p-20">
                    <div className="h-8 w-8 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent" />
                </div>
            ) : data ? (
                <div className="p-6 print:p-0">
                    {data.pages.map((page, pageIndex) => (
                        <div
                            key={pageIndex}
                            className={`mx-auto max-w-2xl rounded-lg border border-gray-200 bg-white shadow-md print:border-0 print:shadow-none dark:border-gray-700 dark:bg-gray-900 ${pageIndex > 0 ? 'mt-8 print:mt-0 print:break-before-page' : ''}`}
                        >
                            {/* Header */}
                            <div className="flex items-center justify-between bg-[#1a5c2a] px-6 py-4 text-white">
                                <div className="flex h-14 w-14 items-center justify-center rounded bg-white/10">
                                    {data.logoUrl ? (
                                        <img src={data.logoUrl} alt="Logo" className="h-12 w-12 object-contain" />
                                    ) : (
                                        <span className="text-xs text-white/50">Logo</span>
                                    )}
                                </div>
                                <div className="text-center">
                                    <h1 className="text-xl font-bold">{data.settings.school_name}</h1>
                                    <p className="text-sm italic opacity-90">{data.settings.motto}</p>
                                    <p className="text-xs opacity-75">{data.settings.location}</p>
                                </div>
                                <div className="flex h-14 w-14 items-center justify-center rounded bg-white/10">
                                    {data.logoUrl ? (
                                        <img src={data.logoUrl} alt="Logo" className="h-12 w-12 object-contain" />
                                    ) : (
                                        <span className="text-xs text-white/50">Logo</span>
                                    )}
                                </div>
                            </div>

                            {/* Title bar */}
                            <div className="border-b-2 border-[#1a5c2a] bg-[#e8f5ec] py-2 text-center text-sm font-bold uppercase tracking-wider text-[#1a5c2a]">
                                Report Card &bull; {page.examName}
                            </div>

                            {/* Student info */}
                            <div className="grid grid-cols-2 gap-4 border-b border-gray-200 px-6 py-4 text-sm dark:border-gray-700">
                                <div><span className="font-semibold text-[#1a5c2a]">Name:</span> {data.student.name}</div>
                                <div><span className="font-semibold text-[#1a5c2a]">Class:</span> {data.student.school_class.display_name}</div>
                                <div><span className="font-semibold text-[#1a5c2a]">Exam:</span> {page.examName}</div>
                                <div><span className="font-semibold text-[#1a5c2a]">Year:</span> {page.exam.academic_year.year}</div>
                            </div>

                            {/* Subject table */}
                            <div className="bg-[#1a5c2a] px-4 py-2 text-sm font-bold text-white">Subject Performance</div>
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-[#1a5c2a] text-white">
                                        <th className="px-4 py-2 text-left">Subject</th>
                                        <th className="px-4 py-2 text-center">Score</th>
                                        <th className="px-4 py-2 text-center">Grade</th>
                                        <th className="px-4 py-2 text-center">Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {page.rows.map((row, i) => (
                                        <tr key={i} className={i % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-[#f0f7f1] dark:bg-gray-800'}>
                                            <td className="px-4 py-2">{row.subject}</td>
                                            <td className="px-4 py-2 text-center">{row.score}</td>
                                            <td className="px-4 py-2 text-center">{row.sublevel}</td>
                                            <td className="px-4 py-2 text-center">{row.description}</td>
                                        </tr>
                                    ))}
                                    <tr className="border-t-2 border-[#1a5c2a] bg-gray-100 font-bold dark:bg-gray-700">
                                        <td className="px-4 py-2">TOTAL</td>
                                        <td className="px-4 py-2 text-center">{page.total}</td>
                                        <td className="px-4 py-2" />
                                        <td className="px-4 py-2" />
                                    </tr>
                                    <tr className="bg-[#d8eedc] font-bold dark:bg-emerald-900/30">
                                        <td className="px-4 py-2">AVERAGE</td>
                                        <td className="px-4 py-2 text-center">{page.average}</td>
                                        <td className="px-4 py-2 text-center">{page.averageSublevel ?? '—'}</td>
                                        <td className="px-4 py-2 text-center">{page.averageDescription ?? '—'}</td>
                                    </tr>
                                </tbody>
                            </table>

                            {/* Performance Level Key — once, first page only */}
                            {pageIndex === 0 && (
                                <div className="mt-4 border border-gray-300 bg-gray-50 px-4 py-3 dark:border-gray-600 dark:bg-gray-800/50">
                                    <div className="mb-2 text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        Performance Level Key (Score %)
                                    </div>
                                    <div className="grid grid-cols-4 gap-4 text-[11px]">
                                        {['EE', 'ME', 'AE', 'BE'].map((code) => {
                                            const label = { EE: 'Exceeding Expectation', ME: 'Meeting Expectation', AE: 'Approaching Expectation', BE: 'Below Expectation' }[code];
                                            const items = data.gradingScale.filter((e) => e.grade === code);

                                            return (
                                                <div key={code}>
                                                    <div className="border-b border-gray-400 pb-1 font-semibold text-gray-800 dark:text-gray-200">{code} — {label}</div>
                                                    {items.map((e) => (
                                                        <div key={e.sublevel} className="mt-0.5 whitespace-nowrap">{e.sublevel}: {e.range}%</div>
                                                    ))}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {/* Class Teacher & Head Teacher Reviews */}
                            <div className="grid grid-cols-2 gap-6 border-t border-gray-200 px-6 py-5 text-sm dark:border-gray-700">
                                <div>
                                    <div className="mb-1 text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">Class Teacher Review</div>
                                    <div className="text-gray-800 dark:text-gray-200">{page.averageDescription ?? '—'}</div>
                                    <div className="my-2 border-b border-gray-400" />
                                    <div className="font-semibold">{data.student.school_class.class_teacher_name ?? '—'}</div>
                                </div>
                                <div>
                                    <div className="mb-1 text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">Head Teacher Review</div>
                                    <div className="text-gray-800 dark:text-gray-200">{page.averageDescription ?? '—'}</div>
                                    <div className="my-2 border-b border-gray-400" />
                                    <div className="font-semibold">{data.settings.head_teacher_name ?? '—'}</div>
                                </div>
                            </div>

                            {/* Footer */}
                            <div className="border-t border-gray-100 px-4 py-2 text-center text-xs text-gray-400 dark:border-gray-700" />
                        </div>
                    ))}
                </div>
            ) : (
                <p className="p-6 text-sm text-gray-400">Report card not found.</p>
            )}

            <style>{`
                @media print {
                    .print\\:hidden { display: none !important; }
                    nav, aside, header { display: none !important; }
                }
            `}</style>
        </AppLayout>
    );
}
