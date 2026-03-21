import { Head } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="p-8">
                <h1 className="text-2xl font-bold">Dashboard</h1>
                <p className="mt-2 text-zinc-500">Welcome to Inte.Team CMS.</p>
            </div>
        </>
    );
}
