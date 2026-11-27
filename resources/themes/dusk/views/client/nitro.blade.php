<!DOCTYPE html>
<html lang="en" class="h-full overflow-hidden overscroll-none bg-[#0e151c]">

<head>
    <meta charset="utf-8">
    <x-web-app.head />
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ setting('hotel_name') }} - Nitro</title>

    <link href="https://fonts.googleapis.com/css2?family=Ubuntu+Condensed&display=swap" rel="stylesheet">

    @livewireStyles
    @livewireScriptConfig
    @vite(['resources/themes/' .  setting('theme') . '/css/app.css', 'resources/themes/' .  setting('theme') . '/js/app.js'], 'build')
</head>

<body class="h-full overflow-hidden overscroll-none bg-[#0e151c] p-0" id="nitro-client" data-client-shell
    data-client-url="{{ route('nitro-client') }}"
    data-exit-urls="{{ route('logout') }} {{ route('flash-client') }}">
<main data-client-viewport class="fixed inset-0 touch-manipulation overflow-hidden overscroll-none bg-[#0e151c]">
    <div data-client-safe-area aria-hidden="true"
        class="pointer-events-none invisible fixed inset-0 pt-[env(safe-area-inset-top)] pr-[env(safe-area-inset-right)] pb-[env(safe-area-inset-bottom)] pl-[env(safe-area-inset-left)]"></div>

    @if (setting('enable_client_buttons') === '1')
        <div class="absolute top-[calc(1rem+env(safe-area-inset-top))] left-[calc(1rem+env(safe-area-inset-left))] z-10 flex gap-x-2 pointer-coarse:max-[600px]:hidden pointer-coarse:[@media(max-height:599px)]:hidden" data-client-buttons>
            <a data-turbolinks="false" href="{{ route('me.show') }}" data-client-home>
                <x-client.client-button>
                    <x-icons.home />
                </x-client.client-button>
            </a>

            <div onclick="reloadClient()">
                <x-client.client-button>
                    <x-icons.reload />
                </x-client.client-button>
            </div>

            <div onclick="toggleFullscreen()">
                <x-client.client-button>
                    <x-icons.fullscreen />
                </x-client.client-button>
            </div>

            <x-client.client-button classes="flex items-center justify-center gap-x-1">
                <x-icons.user />

                <span id="online-count"></span>
            </x-client.client-button>
        </div>
    @endif

    <div class="absolute inset-0">
        <iframe id="nitro" src="{{ $nitroUrl }}"
            allow="fullscreen; autoplay; clipboard-read; clipboard-write; screen-wake-lock; web-share"
            class="m-0 block size-full overflow-hidden rounded-none border-0 p-0"></iframe>
    </div>
</main>

<iframe data-website-frame hidden title="{{ setting('hotel_name') }}"
    class="fixed inset-0 z-20 m-0 h-full w-full border-none p-0"></iframe>

<a data-return-to-hotel hidden href="{{ route('nitro-client') }}"
    class="fixed right-6 bottom-6 z-30 flex items-center rounded border-2 border-yellow-300 bg-yellow-500 px-5 py-3 font-semibold text-white shadow-lg transition-transform duration-300 hover:scale-[1.02]">
    {{ __('Return to :hotel', ['hotel' => setting('hotel_name')]) }}
</a>

{{-- Show disconnected message on client if the user has been disconnected --}}
<div id="disconnected" class="h-screen w-full">
    <div class="absolute h-full w-full bg-black/50"></div>

    <div class="relative flex h-full w-full flex-col items-center justify-center gap-4">
        <h2 class="text-2xl text-white">
            {{ __('Whoops! It seems like you have been disconnected...') }}
        </h2>

        <div class="flex gap-x-4">
            <button
                class="py-2 px-4 text-white rounded bg-[#eeb425] hover:bg-[#e3aa1e] border-2 border-[#cf9d15] transition ease-in-out"
                onclick="reloadClient()">
                {{ __('Reload client') }}
            </button>

            <a data-turbolinks="false" href="{{ route('me.show') }}">
                <x-form.secondary-button>
                    {{ __('Back to website') }}
                </x-form.secondary-button>
            </a>
        </div>
    </div>
</div>

<script>
    function toggleFullscreen() {
        if (document.fullscreenElement) {
            document.exitFullscreen();

            return;
        }

        document.documentElement.requestFullscreen();
    }

    function reloadClient() {
        window.location.href = window.location;
    }

    window.addEventListener('DOMContentLoaded', () => {
        const onlineCount = document.getElementById('online-count');

        if (!onlineCount) {
            return;
        }

        function getOnlineUserCount() {
            fetch('{{ route('api.online-count') }}')
                .then(function(response) {
                    return response.json();
                })
                .then(function(response) {
                    onlineCount.innerHTML = response.data.onlineCount;
                });
        }

        getOnlineUserCount();

        setInterval(function() {
            getOnlineUserCount();
        }, 15000);
    });
</script>

<script src="{{ asset('assets/js/atom.js') }}"></script>
<x-web-app.install-prompt placement="game" />
</body>

</html>
