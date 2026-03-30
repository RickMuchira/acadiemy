import type {
    DragEndEvent} from '@dnd-kit/core';
import {
    DndContext,
    KeyboardSensor,
    PointerSensor,
    closestCenter,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Head } from '@inertiajs/react';
import { Download, GripVertical, GitMerge, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import api from '@/lib/api';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Classes', href: '/classes' },
];

const STREAMED_LEVELS = ['PP1', 'PP2', 'Grade1', 'Grade2', 'Grade3'];
const ALL_LEVELS = ['PG', 'PP1', 'PP2', 'Grade1', 'Grade2', 'Grade3', 'Grade4', 'Grade5', 'Grade6'];
const LEVEL_LABELS: Record<string, string> = {
    PG: 'Playgroup',
    PP1: 'PP1',
    PP2: 'PP2',
    Grade1: 'Grade 1',
    Grade2: 'Grade 2',
    Grade3: 'Grade 3',
    Grade4: 'Grade 4',
    Grade5: 'Grade 5',
    Grade6: 'Grade 6',
};

interface SchoolClass {
    id: number;
    level: string;
    stream: string | null;
    class_teacher_name: string;
    display_name: string;
    students_count: number;
}

interface Subject {
    id: number;
    name: string;
    display_order: number;
    class_id: number;
}

interface AcademicYear { id: number; year: string; }
interface Exam { id: number; name: string; academic_year_id: number; academic_year: AcademicYear; }

interface ClassFormData {
    level: string;
    stream: string;
    class_teacher_name: string;
}

export default function Classes() {
    const [classes, setClasses] = useState<SchoolClass[]>([]);
    const [showForm, setShowForm] = useState(false);
    const [editingClass, setEditingClass] = useState<SchoolClass | null>(null);
    const [form, setForm] = useState<ClassFormData>({ level: '', stream: '', class_teacher_name: '' });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);
    const [selectedClass, setSelectedClass] = useState<SchoolClass | null>(null);
    const [subjects, setSubjects] = useState<Subject[]>([]);
    const [editSubjectId, setEditSubjectId] = useState<number | null>(null);
    const [editSubjectName, setEditSubjectName] = useState('');
    const [mergeSource, setMergeSource] = useState<Subject | null>(null);
    const [mergeTargetId, setMergeTargetId] = useState<string>('');

    // Marks-report download state
    const [reportClass, setReportClass] = useState<SchoolClass | null>(null);
    const [academicYears, setAcademicYears] = useState<AcademicYear[]>([]);
    const [exams, setExams] = useState<Exam[]>([]);
    const [reportYear, setReportYear] = useState('');
    const [reportExam, setReportExam] = useState('');

    const isStreamed = STREAMED_LEVELS.includes(form.level);

    const loadClasses = () => api.get('/classes').then((r) => setClasses(r.data));

    useEffect(() => {
        loadClasses();
        api.get('/academic-years').then((r) => setAcademicYears(r.data));
    }, []);

    useEffect(() => {
        if (reportYear) {
            api.get('/exams', { params: { academic_year_id: reportYear } }).then((r) => setExams(r.data));
        }
    }, [reportYear]);

    const loadSubjects = (classId: number) => {
        api.get(`/classes/${classId}/subjects`).then((r) => setSubjects(r.data));
    };

    const openCreate = () => {
        setEditingClass(null);
        setForm({ level: '', stream: '', class_teacher_name: '' });
        setErrors({});
        setShowForm(true);
    };

    const openEdit = (cls: SchoolClass) => {
        setEditingClass(cls);
        setForm({ level: cls.level, stream: cls.stream ?? '', class_teacher_name: cls.class_teacher_name });
        setErrors({});
        setShowForm(true);
    };

    const saveClass = async () => {
        setSaving(true);

        try {
            if (editingClass) {
                await api.put(`/classes/${editingClass.id}`, form);
            } else {
                await api.post('/classes', form);
            }

            setShowForm(false);
            setErrors({});
            loadClasses();
        } catch (e: unknown) {
            if (isAxiosError(e)) {
setErrors(flattenErrors(e.response?.data?.errors ?? {}));
}
        } finally {
            setSaving(false);
        }
    };

    const deleteClass = async (id: number) => {
        if (!confirm('Delete this class? All students and results will be removed.')) {
return;
}

        await api.delete(`/classes/${id}`);

        if (selectedClass?.id === id) {
setSelectedClass(null);
}

        loadClasses();
    };

    const selectClass = (cls: SchoolClass) => {
        setSelectedClass(cls);
        loadSubjects(cls.id);
    };

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );

    const handleDragEnd = async (event: DragEndEvent) => {
        const { active, over } = event;

        if (!over || active.id === over.id) {
return;
}

        const oldIndex = subjects.findIndex((s) => s.id === active.id);
        const newIndex = subjects.findIndex((s) => s.id === over.id);
        const reordered = arrayMove(subjects, oldIndex, newIndex).map((s, i) => ({ ...s, display_order: i }));
        setSubjects(reordered);

        await api.put('/subjects/reorder', {
            subjects: reordered.map((s) => ({ id: s.id, display_order: s.display_order })),
        });
    };

    const saveSubjectName = async (subject: Subject) => {
        await api.put(`/subjects/${subject.id}`, { name: editSubjectName });
        setEditSubjectId(null);

        if (selectedClass) {
loadSubjects(selectedClass.id);
}
    };

    const deleteSubject = async (id: number) => {
        if (!confirm('Delete this subject?')) {
return;
}

        await api.delete(`/subjects/${id}`);

        if (selectedClass) {
loadSubjects(selectedClass.id);
}
    };

    const openReportModal = (cls: SchoolClass) => {
        setReportClass(cls);
        setReportYear('');
        setReportExam('');
    };

    const downloadMarksReport = () => {
        if (!reportClass || !reportExam) {
return;
}

        let url = `/api/classes/${reportClass.id}/marks-report?`;

        if (reportExam.startsWith('both:')) {
            const examId = reportExam.replace('both:', '');
            url += `exam_id=${examId}&both=1`;
        } else {
            url += `exam_id=${reportExam}`;
        }

        window.open(url, '_blank');
        setReportClass(null);
    };

    const mergeSubjects = async () => {
        if (!mergeSource || !mergeTargetId) {
return;
}

        const targetId = parseInt(mergeTargetId, 10);

        if (mergeSource.id === targetId) {
return;
}

        if (!confirm(`Merge "${mergeSource.name}" into the selected subject? All results will be combined.`)) {
return;
}

        try {
            const r = await api.post('/subjects/merge', { source_id: mergeSource.id, target_id: targetId });
            setMergeSource(null);
            setMergeTargetId('');

            if (selectedClass) {
                setSubjects(r.data.subjects ?? []);
            }
        } catch (e: unknown) {
            if (isAxiosError(e)) {
alert(e.response?.data?.message ?? 'Merge failed.');
}
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Classes" />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Classes</h1>
                    <button
                        onClick={openCreate}
                        className="flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                    >
                        <Plus className="h-4 w-4" /> New Class
                    </button>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Classes table */}
                    <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-emerald-700 text-white">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-semibold">Class</th>
                                        <th className="px-4 py-3 text-left font-semibold">Teacher</th>
                                        <th className="px-4 py-3 text-center font-semibold">Students</th>
                                        <th className="px-4 py-3 text-right font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {classes.map((cls, i) => (
                                        <tr
                                            key={cls.id}
                                            className={`cursor-pointer border-b border-gray-100 dark:border-gray-700 ${
                                                selectedClass?.id === cls.id
                                                    ? 'bg-emerald-50 dark:bg-emerald-900/20'
                                                    : i % 2 === 0
                                                      ? 'bg-white dark:bg-gray-800'
                                                      : 'bg-gray-50 dark:bg-gray-700/50'
                                            }`}
                                            onClick={() => selectClass(cls)}
                                        >
                                            <td className="px-4 py-3 font-medium">{cls.display_name}</td>
                                            <td className="px-4 py-3 text-gray-500">{cls.class_teacher_name || '—'}</td>
                                            <td className="px-4 py-3 text-center">{cls.students_count}</td>
                                            <td className="px-4 py-3 text-right">
                                                <button
                                                    onClick={(e) => {
 e.stopPropagation(); openReportModal(cls); 
}}
                                                    title="Download Marks Report (PDF)"
                                                    className="mr-2 text-gray-400 hover:text-emerald-600"
                                                >
                                                    <Download className="h-4 w-4" />
                                                </button>
                                                <button
                                                    onClick={(e) => {
 e.stopPropagation(); openEdit(cls); 
}}
                                                    className="mr-2 text-gray-400 hover:text-emerald-600"
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </button>
                                                <button
                                                    onClick={(e) => {
 e.stopPropagation(); deleteClass(cls.id); 
}}
                                                    className="text-gray-400 hover:text-red-500"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                    {classes.length === 0 && (
                                        <tr>
                                            <td colSpan={4} className="px-4 py-6 text-center text-gray-400">No classes yet</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Subject order manager */}
                    {selectedClass && (
                        <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h2 className="mb-4 text-lg font-semibold">
                                Subjects — <span className="text-emerald-600">{selectedClass.display_name}</span>
                            </h2>
                            {subjects.length === 0 ? (
                                <p className="text-sm text-gray-400">No subjects yet. Upload marks to auto-detect subjects.</p>
                            ) : (
                                <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                                    <SortableContext items={subjects.map((s) => s.id)} strategy={verticalListSortingStrategy}>
                                        <ul className="space-y-2">
                                            {subjects.map((subject) => (
                                                <SortableSubjectItem
                                                    key={subject.id}
                                                    subject={subject}
                                                    otherSubjects={subjects.filter((s) => s.id !== subject.id)}
                                                    isEditing={editSubjectId === subject.id}
                                                    editName={editSubjectName}
                                                    onEditStart={() => {
 setEditSubjectId(subject.id); setEditSubjectName(subject.name); 
}}
                                                    onEditChange={setEditSubjectName}
                                                    onEditSave={() => saveSubjectName(subject)}
                                                    onEditCancel={() => setEditSubjectId(null)}
                                                    onMerge={() => setMergeSource(subject)}
                                                    onDelete={() => deleteSubject(subject.id)}
                                                />
                                            ))}
                                        </ul>
                                    </SortableContext>
                                </DndContext>
                            )}
                            {subjects.length > 1 && (
                                <p className="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    Use <strong>Merge</strong> to combine duplicate subjects (e.g. Math + Mathematics) from different exams.
                                </p>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Merge subjects modal */}
            {mergeSource && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Merge Subjects</h2>
                            <button onClick={() => {
 setMergeSource(null); setMergeTargetId(''); 
}}><X className="h-5 w-5" /></button>
                        </div>
                        <p className="mb-4 text-sm text-gray-600 dark:text-gray-300">
                            Merge &ldquo;<strong>{mergeSource.name}</strong>&rdquo; into another subject. All results will be combined.
                        </p>
                        <div>
                            <label className="mb-1 block text-sm font-medium">Merge into</label>
                            <select
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                value={mergeTargetId}
                                onChange={(e) => setMergeTargetId(e.target.value)}
                            >
                                <option value="">Select subject…</option>
                                {subjects.filter((s) => s.id !== mergeSource.id).map((s) => (
                                    <option key={s.id} value={s.id}>{s.name}</option>
                                ))}
                            </select>
                        </div>
                        <div className="mt-6 flex justify-end gap-3">
                            <button
                                onClick={() => {
 setMergeSource(null); setMergeTargetId(''); 
}}
                                className="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={mergeSubjects}
                                disabled={!mergeTargetId}
                                className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                Merge
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Marks Report download modal */}
            {reportClass && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Download Marks Report</h2>
                            <button onClick={() => setReportClass(null)}><X className="h-5 w-5" /></button>
                        </div>
                        <p className="mb-4 text-sm text-gray-600 dark:text-gray-300">
                            Download a PDF marks sheet for <strong>{reportClass.display_name}</strong> showing
                            Opener and Midterm scores for every student.
                        </p>
                        <div className="space-y-4">
                            <div>
                                <label className="mb-1 block text-sm font-medium">Academic Year</label>
                                <select
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={reportYear}
                                    onChange={(e) => {
 setReportYear(e.target.value); setReportExam(''); 
}}
                                >
                                    <option value="">Select year…</option>
                                    {academicYears.map((y) => (
                                        <option key={y.id} value={y.id}>{y.year}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium">Exam</label>
                                <select
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={reportExam}
                                    onChange={(e) => setReportExam(e.target.value)}
                                    disabled={!reportYear}
                                >
                                    <option value="">Select exam…</option>
                                    {(() => {
                                        const filtered = exams.filter((e) => String(e.academic_year_id) === reportYear);
                                        const opener = filtered.find((e) => /open/i.test(e.name));
                                        const midterm = filtered.find((e) => /mid/i.test(e.name));

                                        return (
                                            <>
                                                {opener && midterm && (
                                                    <option value={`both:${opener.id}`}>All (Opener + Midterm)</option>
                                                )}
                                                {filtered.map((ex) => (
                                                    <option key={ex.id} value={ex.id}>{ex.name}</option>
                                                ))}
                                            </>
                                        );
                                    })()}
                                </select>
                            </div>
                        </div>
                        <div className="mt-6 flex justify-end gap-3">
                            <button
                                onClick={() => setReportClass(null)}
                                className="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={downloadMarksReport}
                                disabled={!reportExam}
                                className="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                <Download className="h-4 w-4" /> Download PDF
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Class form modal */}
            {showForm && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="text-lg font-semibold">{editingClass ? 'Edit Class' : 'New Class'}</h2>
                            <button onClick={() => setShowForm(false)}><X className="h-5 w-5" /></button>
                        </div>

                        <div className="space-y-4">
                            <div>
                                <label className="mb-1 block text-sm font-medium">Level</label>
                                <select
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={form.level}
                                    onChange={(e) => setForm((p) => ({ ...p, level: e.target.value, stream: '' }))}
                                >
                                    <option value="">Select level</option>
                                    {ALL_LEVELS.map((l) => (
                                        <option key={l} value={l}>{LEVEL_LABELS[l]}</option>
                                    ))}
                                </select>
                                {errors.level && <p className="mt-1 text-xs text-red-500">{errors.level}</p>}
                            </div>

                            {isStreamed && (
                                <div>
                                    <label className="mb-1 block text-sm font-medium">Stream</label>
                                    <select
                                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        value={form.stream}
                                        onChange={(e) => setForm((p) => ({ ...p, stream: e.target.value }))}
                                    >
                                        <option value="">Select stream</option>
                                        <option value="GREEN">GREEN</option>
                                        <option value="RED">RED</option>
                                    </select>
                                    {errors.stream && <p className="mt-1 text-xs text-red-500">{errors.stream}</p>}
                                </div>
                            )}

                            <div>
                                <label className="mb-1 block text-sm font-medium">Class Teacher Name</label>
                                <input
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Teacher name"
                                    value={form.class_teacher_name}
                                    onChange={(e) => setForm((p) => ({ ...p, class_teacher_name: e.target.value }))}
                                />
                                {errors.class_teacher_name && <p className="mt-1 text-xs text-red-500">{errors.class_teacher_name}</p>}
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end gap-3">
                            <button onClick={() => setShowForm(false)} className="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">
                                Cancel
                            </button>
                            <button
                                onClick={saveClass}
                                disabled={saving}
                                className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                {saving ? 'Saving…' : 'Save'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}

function SortableSubjectItem({
    subject,
    otherSubjects,
    isEditing,
    editName,
    onEditStart,
    onEditChange,
    onEditSave,
    onEditCancel,
    onMerge,
    onDelete,
}: {
    subject: Subject;
    otherSubjects: Subject[];
    isEditing: boolean;
    editName: string;
    onEditStart: () => void;
    onEditChange: (v: string) => void;
    onEditSave: () => void;
    onEditCancel: () => void;
    onMerge: () => void;
    onDelete: () => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition } = useSortable({ id: subject.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <li ref={setNodeRef} style={style} className="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
            <span {...attributes} {...listeners} className="cursor-grab text-gray-400 active:cursor-grabbing">
                <GripVertical className="h-4 w-4" />
            </span>
            {isEditing ? (
                <input
                    className="flex-1 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    value={editName}
                    onChange={(e) => onEditChange(e.target.value)}
                    onKeyDown={(e) => {
 if (e.key === 'Enter') {
onEditSave();
}

 if (e.key === 'Escape') {
onEditCancel();
} 
}}
                    autoFocus
                />
            ) : (
                <span className="flex-1 text-sm">{subject.name}</span>
            )}
            {isEditing ? (
                <>
                    <button onClick={onEditSave} className="text-xs text-emerald-600 hover:underline">Save</button>
                    <button onClick={onEditCancel} className="text-xs text-gray-400 hover:underline">Cancel</button>
                </>
            ) : (
                <>
                    <button onClick={onEditStart} className="text-gray-400 hover:text-emerald-600" title="Edit name">
                        <Pencil className="h-3.5 w-3.5" />
                    </button>
                    {otherSubjects.length > 0 && (
                        <button onClick={onMerge} className="text-gray-400 hover:text-blue-600" title="Merge into another subject">
                            <GitMerge className="h-3.5 w-3.5" />
                        </button>
                    )}
                    <button onClick={onDelete} className="text-gray-400 hover:text-red-500" title="Delete">
                        <Trash2 className="h-3.5 w-3.5" />
                    </button>
                </>
            )}
        </li>
    );
}

 
function isAxiosError(e: unknown): e is { response?: { data?: { errors?: Record<string, string[]> } } } {
    return typeof e === 'object' && e !== null && 'response' in e;
}

function flattenErrors(errors: Record<string, string[]>): Record<string, string> {
    return Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, v[0]]));
}
