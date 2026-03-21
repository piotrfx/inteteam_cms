import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function SettingsIndex() {
    return (
        <AdminLayout title="Settings">
            <Head title="Settings" />
            <p className="text-sm text-zinc-500">Site settings — coming soon.</p>
        </AdminLayout>
    );
}
