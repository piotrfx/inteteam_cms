import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    status?: string;
}

export default function ForgotPassword({ status }: Props) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('admin.password.email'));
    }

    return (
        <>
            <Head title="Forgot password" />
            <div className="min-h-screen flex items-center justify-center bg-zinc-50 dark:bg-zinc-950 px-4">
                <div className="w-full max-w-sm space-y-6">
                    <div className="text-center">
                        <h1 className="text-2xl font-bold tracking-tight">Reset password</h1>
                        <p className="mt-1 text-sm text-zinc-500">
                            Enter your email and we'll send a reset link.
                        </p>
                    </div>

                    {status && (
                        <div className="rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                            {status}
                        </div>
                    )}

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

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                        >
                            {processing ? 'Sending…' : 'Send reset link'}
                        </button>

                        <p className="text-center text-sm text-zinc-500">
                            <Link href={route('admin.login')} className="text-brand-600 hover:text-brand-700">
                                Back to sign in
                            </Link>
                        </p>
                    </form>
                </div>
            </div>
        </>
    );
}
