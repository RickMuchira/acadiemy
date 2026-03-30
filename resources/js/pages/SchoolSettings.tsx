import { Head } from '@inertiajs/react';
import { CheckCircle, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import api from '@/lib/api';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Settings', href: '/school-settings' },
];

interface Settings { id: number; school_name: string; motto: string; location: string; head_teacher_name: string | null; logo_path: string | null; }

export default function SchoolSettings() {
    const [settings, setSettings] = useState<Settings | null>(null);
    const [form, setForm] = useState({ school_name: '', motto: '', location: '', head_teacher_name: '' });
    const [passwordForm, setPasswordForm] = useState({ current_password: '', password: '', password_confirmation: '' });
    const [saving, setSaving] = useState(false);
    const [savingPassword, setSavingPassword] = useState(false);
    const [saved, setSaved] = useState(false);
    const [passwordSaved, setPasswordSaved] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [passwordErrors, setPasswordErrors] = useState<Record<string, string>>({});
    const [logoUrl, setLogoUrl] = useState<string | null>(null);
    const logoRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        api.get('/settings').then((r) => {
            setSettings(r.data);
            setForm({
                school_name: r.data.school_name,
                motto: r.data.motto,
                location: r.data.location,
                head_teacher_name: r.data.head_teacher_name ?? '',
            });

            if (r.data.logo_path) {
setLogoUrl(`/storage/${r.data.logo_path}`);
}
        });
    }, []);

    const saveSettings = async () => {
        setSaving(true);

        try {
            await api.post('/settings', form);
            setSaved(true);
            setErrors({});
            setTimeout(() => setSaved(false), 2000);
        } catch (e: unknown) {
            if (isAxiosError(e)) {
setErrors(flattenErrors(e.response?.data?.errors ?? {}));
}
        } finally {
            setSaving(false);
        }
    };

    const uploadLogo = async (file: File) => {
        const fd = new FormData();
        fd.append('logo', file);
        const r = await api.post('/settings/logo', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
        setLogoUrl(r.data.logo_url);
    };

    const savePassword = async () => {
        setSavingPassword(true);

        try {
            await api.post('/settings/password', passwordForm);
            setPasswordSaved(true);
            setPasswordErrors({});
            setPasswordForm({ current_password: '', password: '', password_confirmation: '' });
            setTimeout(() => setPasswordSaved(false), 2000);
        } catch (e: unknown) {
            if (isAxiosError(e)) {
setPasswordErrors(flattenErrors(e.response?.data?.errors ?? {}));
}
        } finally {
            setSavingPassword(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Settings" />
            <div className="flex flex-col gap-6 p-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">School Settings</h1>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* School info */}
                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 className="mb-4 text-lg font-semibold">School Information</h2>
                        <div className="space-y-4">
                            <div>
                                <label className="mb-1 block text-sm font-medium">School Name</label>
                                <input
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={form.school_name}
                                    onChange={(e) => setForm((p) => ({ ...p, school_name: e.target.value }))}
                                />
                                {errors.school_name && <p className="mt-1 text-xs text-red-500">{errors.school_name}</p>}
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium">Motto</label>
                                <input
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={form.motto}
                                    onChange={(e) => setForm((p) => ({ ...p, motto: e.target.value }))}
                                />
                                {errors.motto && <p className="mt-1 text-xs text-red-500">{errors.motto}</p>}
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium">Location</label>
                                <input
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={form.location}
                                    onChange={(e) => setForm((p) => ({ ...p, location: e.target.value }))}
                                />
                                {errors.location && <p className="mt-1 text-xs text-red-500">{errors.location}</p>}
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium">Head Teacher Name</label>
                                <input
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="e.g. Mr. Jackson Ndirangu"
                                    value={form.head_teacher_name}
                                    onChange={(e) => setForm((p) => ({ ...p, head_teacher_name: e.target.value }))}
                                />
                                {errors.head_teacher_name && <p className="mt-1 text-xs text-red-500">{errors.head_teacher_name}</p>}
                            </div>
                            <button
                                onClick={saveSettings}
                                disabled={saving}
                                className="flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                {saved ? <><CheckCircle className="h-4 w-4" /> Saved!</> : saving ? 'Saving…' : 'Save Changes'}
                            </button>
                        </div>
                    </div>

                    {/* Logo upload */}
                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 className="mb-4 text-lg font-semibold">School Logo</h2>
                        <div
                            className="cursor-pointer rounded-xl border-2 border-dashed border-gray-300 p-8 text-center hover:border-emerald-400 dark:border-gray-600"
                            onClick={() => logoRef.current?.click()}
                        >
                            {logoUrl ? (
                                <img src={logoUrl} alt="School Logo" className="mx-auto h-24 w-24 object-contain" />
                            ) : (
                                <Upload className="mx-auto h-10 w-10 text-gray-400" />
                            )}
                            <p className="mt-2 text-sm text-gray-500">Click to upload logo (PNG, JPG, SVG)</p>
                            <input
                                ref={logoRef}
                                type="file"
                                className="hidden"
                                accept="image/*"
                                onChange={(e) => {
 if (e.target.files?.[0]) {
uploadLogo(e.target.files[0]);
} 
}}
                            />
                        </div>
                    </div>

                    {/* Change password */}
                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 className="mb-4 text-lg font-semibold">Change Password</h2>
                        <div className="space-y-4">
                            <div>
                                <label className="mb-1 block text-sm font-medium">Current Password</label>
                                <input
                                    type="password"
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={passwordForm.current_password}
                                    onChange={(e) => setPasswordForm((p) => ({ ...p, current_password: e.target.value }))}
                                />
                                {passwordErrors.current_password && <p className="mt-1 text-xs text-red-500">{passwordErrors.current_password}</p>}
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium">New Password</label>
                                <input
                                    type="password"
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={passwordForm.password}
                                    onChange={(e) => setPasswordForm((p) => ({ ...p, password: e.target.value }))}
                                />
                                {passwordErrors.password && <p className="mt-1 text-xs text-red-500">{passwordErrors.password}</p>}
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium">Confirm New Password</label>
                                <input
                                    type="password"
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={passwordForm.password_confirmation}
                                    onChange={(e) => setPasswordForm((p) => ({ ...p, password_confirmation: e.target.value }))}
                                />
                            </div>
                            <button
                                onClick={savePassword}
                                disabled={savingPassword}
                                className="flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                {passwordSaved ? <><CheckCircle className="h-4 w-4" /> Updated!</> : savingPassword ? 'Saving…' : 'Update Password'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

 
function isAxiosError(e: unknown): e is { response?: { data?: { errors?: Record<string, string[]> } } } {
    return typeof e === 'object' && e !== null && 'response' in e;
}

function flattenErrors(errors: Record<string, string[]>): Record<string, string> {
    return Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, v[0]]));
}
