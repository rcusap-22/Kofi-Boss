<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="theme-color"
        content="#1c1917"
    >

    <title>Sign In — KOFI BOSS</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body class="min-h-screen bg-stone-100">

    <div class="min-h-screen grid grid-cols-2">

        {{-- LEFT BRAND PANEL --}}
        <section
            class="
                flex
                bg-stone-900
                text-white
                p-8
                md:p-10
                lg:p-12
                xl:p-16
                flex-col
                justify-between
            "
        >

            {{-- LOGO --}}
            <div>
                <div
                    class="
                        text-4xl
                        lg:text-5xl
                        xl:text-6xl
                        font-extrabold
                        tracking-tight
                    "
                >
                    KOFI BOSS
                </div>
            </div>

            {{-- DESCRIPTION --}}
            <div class="max-w-lg">

                <p
                    class="
                        text-amber-400
                        font-bold
                        uppercase
                        tracking-[.18em]
                        text-xs
                        mb-3
                    "
                >
                    Coffee Shop Operations
                </p>

                <h1
                    class="
                        text-3xl
                        lg:text-4xl
                        xl:text-5xl
                        font-extrabold
                        leading-tight
                    "
                >
                    Manage your coffee shop efficiently.
                </h1>

                <p
                    class="
                        mt-5
                        text-stone-400
                        text-base
                        lg:text-lg
                        leading-relaxed
                    "
                >
                    Designed for quick, touch-friendly use
                    behind the counter.
                </p>

            </div>

            {{-- FOOTER --}}
            <div class="text-sm text-stone-500">
                KOFI BOSS Inventory Management System
            </div>

        </section>

        {{-- LOGIN SIDE --}}
        <main
            class="
                flex
                items-center
                justify-center
                p-6
                md:p-8
                lg:p-10
            "
        >

            <div class="w-full max-w-md">

                {{-- LOGIN CARD --}}
                <div class="ui-card p-6 md:p-8">

                    <div class="mb-7">

                        <h2
                            class="
                                text-2xl
                                font-bold
                                text-stone-900
                            "
                        >
                            Welcome back
                        </h2>

                        <p class="text-stone-500 mt-1">
                            Sign in to continue.
                        </p>

                    </div>

                    {{-- ERROR --}}
                    @if($errors->any())

                        <div
                            class="
                                mb-5
                                rounded-xl
                                border
                                border-red-200
                                bg-red-50
                                text-red-800
                                px-4
                                py-3
                                text-sm
                                font-medium
                            "
                        >
                            {{ $errors->first() }}
                        </div>

                    @endif

                    {{-- LOGIN FORM --}}
                    <form
                        method="POST"
                        action="{{ route('login') }}"
                        class="space-y-5"
                    >

                        @csrf

                        {{-- USERNAME --}}
                        <div>

                            <label class="ui-label">
                                Username
                            </label>

                            <input
                                type="text"
                                name="username"
                                value="{{ old('username') }}"
                                required
                                autofocus
                                autocomplete="username"
                                class="ui-input text-lg"
                                placeholder="Enter your username"
                            >

                        </div>

                        {{-- PASSWORD --}}
                        <div>

                            <label class="ui-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="ui-input text-lg"
                                placeholder="Enter your password"
                            >

                        </div>

                        {{-- SIGN IN --}}
                        <button
                            type="submit"
                            class="
                                ui-btn
                                ui-btn-primary
                                tap
                                w-full
                                min-h-14
                                text-lg
                            "
                        >
                            Sign In
                        </button>

                    </form>

                </div>

            </div>

        </main>

    </div>

</body>

</html>