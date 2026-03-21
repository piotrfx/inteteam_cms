import { Head, useForm } from '@inertiajs/react';

interface Props {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('admin.password.update'));
    }

    return (
        <>
            <Head title="Reset password" />
            <div className="min-h-screen flex items-center justify-center bg-zinc-50 dark:bg-zinc-950 px-4">
                <div className="w-full max-w-sm space-y-6">
                    <div className="text-center">
                        <h1 className="text-2xl font-bold tracking-tight">Set new password</h1>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <label htmlFor="email" className="block text-sm font-medium mb-1">
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                autoComplete="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                            {errors.email && (
                                <p className="mt-1 text-xs text-red-500">{errors.email}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="block text-sm font-medium mb-1">
                                New password
                            </label>
                            <input
                                id="password"
                                type="password"
                                autoComplete="new-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                            {errors.password && (
                                <p className="mt-1 text-xs text-red-500">{errors.password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password_confirmation" className="block text-sm font-medium mb-1">
                                Confirm password
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                className="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                        >
                            {processing ? 'Saving…' : 'Reset password'}
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}
