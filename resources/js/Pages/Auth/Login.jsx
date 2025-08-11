import { useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route('login'));
    };

    return (
        <>
            <Head title="Log in - Jardin Academic Platform" />

            <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full space-y-8">
                    <div>
                        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                            Jardin Academic Platform
                        </h2>
                        <p className="mt-2 text-center text-sm text-gray-600">
                            Inicia sesión en tu cuenta
                        </p>
                    </div>

                    {status && (
                        <div className="rounded-md bg-green-50 p-4">
                            <div className="text-sm text-green-800">{status}</div>
                        </div>
                    )}

                    <form className="mt-8 space-y-6" onSubmit={submit}>
                        <div className="rounded-md shadow-sm -space-y-px">
                            <div>
                                <label htmlFor="email" className="sr-only">
                                    Email address
                                </label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    required
                                    className="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                    placeholder="Correo electrónico"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                                {errors.email && (
                                    <div className="text-red-600 text-sm mt-1">{errors.email}</div>
                                )}
                            </div>
                            <div>
                                <label htmlFor="password" className="sr-only">
                                    Password
                                </label>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autoComplete="current-password"
                                    required
                                    className="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                    placeholder="Contraseña"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                />
                                {errors.password && (
                                    <div className="text-red-600 text-sm mt-1">{errors.password}</div>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="flex items-center">
                                <input
                                    id="remember"
                                    name="remember"
                                    type="checkbox"
                                    className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                />
                                <label htmlFor="remember" className="ml-2 block text-sm text-gray-900">
                                    Recordarme
                                </label>
                            </div>

                            {canResetPassword && (
                                <div className="text-sm">
                                    <Link
                                        href={route('password.request')}
                                        className="font-medium text-indigo-600 hover:text-indigo-500"
                                    >
                                        ¿Olvidaste tu contraseña?
                                    </Link>
                                </div>
                            )}
                        </div>

                        <div>
                            <button
                                type="submit"
                                disabled={processing}
                                className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                            >
                                {processing ? 'Iniciando sesión...' : 'Iniciar Sesión'}
                            </button>
                        </div>

                        <div className="text-center">
                            <span className="text-sm text-gray-600">
                                ¿No tienes una cuenta?{' '}
                                <Link
                                    href={route('register')}
                                    className="font-medium text-indigo-600 hover:text-indigo-500"
                                >
                                    Regístrate aquí
                                </Link>
                            </span>
                        </div>

                        <div className="mt-8 text-center">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Cuentas de Demo</h3>
                            <div className="space-y-2 text-sm text-gray-600">
                                <div>
                                    <strong>Administrador:</strong> admin@jardin.com / password
                                </div>
                                <div>
                                    <strong>Profesor:</strong> maria.garcia@jardin.com / password
                                </div>
                                <div>
                                    <strong>Estudiante:</strong> sofia.lopez@example.com / password
                                </div>
                                <div>
                                    <strong>Padre:</strong> ana.lopez@example.com / password
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}