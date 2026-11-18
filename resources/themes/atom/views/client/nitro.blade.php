<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ setting('hotel_name') }} - Nitro</title>

    <link href="https://fonts.googleapis.com/css2?family=Ubuntu+Condensed&display=swap" rel="stylesheet">

    @livewireStyles
    @livewireScriptConfig
    @vite(['resources/themes/' .  setting('theme') . '/css/app.css', 'resources/themes/' .  setting('theme') . '/js/app.js'], 'build')
</head>

<body class="overflow-hidden" id="nitro-client" data-client-shell
    data-client-url="{{ route('nitro-client') }}"
    data-exit-urls="{{ route('logout') }} {{ route('flash-client') }}">
    @if (setting('enable_client_buttons') === '1')
        <div class="absolute top-4 left-4 z-10 flex gap-x-2" data-client-buttons>
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

    <iframe id="nitro" src="{{ sprintf('%s/index.html?sso=%s', setting('nitro_path'), $sso) }}"
        class="absolute top-0 left-0 m-0 h-full w-full overflow-hidden border-none p-0"></iframe>

    <iframe data-website-frame hidden title="{{ setting('hotel_name') }}"
        class="fixed inset-0 z-20 m-0 h-full w-full border-none p-0"></iframe>

    <a data-return-to-hotel hidden href="{{ route('nitro-client') }}"
        class="fixed right-6 bottom-6 z-30 flex items-center rounded-full bg-white/95 px-6 py-3 text-lg font-semibold text-black shadow-lg ring-1 ring-black/10 transition duration-300 ease-in-out hover:scale-105 hover:bg-white dark:bg-gray-900 dark:text-white dark:ring-white/10">
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
</body>

</html>
