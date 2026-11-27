@props(['placement' => 'page'])

@php
    $hotel = setting('hotel_name', config('app.name')) ?: __('the hotel');
    $position = $placement === 'game'
        ? 'top-[calc(0.5rem+env(safe-area-inset-top))] in-data-website-open:top-auto in-data-website-open:bottom-[calc(5.5rem+env(safe-area-inset-bottom))]'
        : 'bottom-[calc(1rem+env(safe-area-inset-bottom))]';
    $closeIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>';
    $shareIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-label="' . e(__('Share')) . '" role="img"><path d="M12 3v12M8 7l4-4 4 4" /><path d="M8.5 10H7a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-1.5" /></svg>';
    $menuIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-label="' . e(__('Menu')) . '" role="img"><path d="M4 7h16M4 12h16M4 17h16" /></svg>';
    $iconButton = 'grid size-11 shrink-0 cursor-pointer place-items-center rounded-md border-0 bg-transparent p-0 text-[var(--muted)] hover:text-[var(--text)] [&_svg]:size-5';
    $stepNumber = 'grid size-7 shrink-0 place-items-center rounded-full bg-[#eeb425] text-sm font-bold text-white';
@endphp

<div data-install-app="{{ $placement }}" hidden>
    <div data-install-banner role="region" aria-label="{{ __('Install the app') }}"
        class="fixed inset-x-3 z-[90] mx-auto grid max-w-md grid-cols-[1fr_auto] items-center gap-x-2 rounded-lg border border-[var(--border)] bg-[var(--panel)] p-2.5 text-[var(--text)] shadow-[0_8px_24px_rgb(0_0_0/0.35)] {{ $position }}">
        <div class="flex min-w-0 items-center gap-2">
            <img src="/pwa/apple-touch-icon.png" alt="" class="size-8 shrink-0 rounded-[7px]" width="32" height="32">
            <p class="min-w-0 text-sm leading-tight font-bold [overflow-wrap:anywhere]">
                {{ __('Play :hotel as an app', ['hotel' => $hotel]) }}
            </p>
        </div>

        <button type="button" data-install-dismiss class="{{ $iconButton }} justify-self-end"
            aria-label="{{ __('Not now') }}" title="{{ __('Not now') }}">
            {!! $closeIcon !!}
        </button>

        <div class="col-span-2 flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
            <p class="text-xs leading-tight text-[var(--muted)]">{{ __('Play in full screen.') }}</p>

            <button type="button" data-install-action
                class="min-h-11 max-w-full cursor-pointer rounded-md border-2 border-[var(--accent-border)] bg-[var(--accent)] px-3 py-1 text-sm font-bold text-white hover:brightness-110">
                <span data-install-label="install" hidden>{{ __('Install') }}</span>
                <span data-install-label="guide" hidden>{{ __('Show me how') }}</span>
            </button>
        </div>
    </div>

    <div data-install-guide hidden class="fixed inset-0 z-[95] bg-black/60">
        <section role="dialog" aria-modal="true" aria-labelledby="install-guide-title"
            class="absolute flex max-h-[calc(100dvh-6rem)] flex-col overflow-y-auto rounded-2xl border border-[var(--border)] bg-[var(--panel)] pb-4 text-[var(--text)] shadow-[0_12px_32px_rgb(0_0_0/0.45)]">
            <header class="flex items-center gap-3 bg-[var(--header)] px-4 py-3">
                <img src="/pwa/apple-touch-icon.png" alt="" class="size-10 shrink-0 rounded-[9px]" width="40" height="40">

                <h2 id="install-guide-title" class="flex-1 text-base font-bold">
                    {{ __('Add :hotel to your Home Screen', ['hotel' => $hotel]) }}
                </h2>

                <button type="button" data-install-close class="{{ $iconButton }}" aria-label="{{ __('Close') }}">
                    {!! $closeIcon !!}
                </button>
            </header>

            <div data-install-in-app hidden class="grid gap-3 px-4 pt-4 text-sm">
                <p>{{ __('This in-app browser cannot add apps to your Home Screen. Open this page in Safari, then tap Show me how again.') }}</p>

                <p class="text-[var(--muted)]">{{ __('Tip: tap the menu of this app and choose Open in Safari, or copy the link and paste it in Safari.') }}</p>

                <button type="button" data-install-copy
                    class="min-h-11 cursor-pointer rounded-md border-2 border-[#cf9d15] bg-[#eeb425] px-4 font-bold text-white hover:bg-[#e3aa1e]">
                    <span data-install-copy-label="copy">{{ __('Copy link') }}</span>
                    <span data-install-copy-label="copied" hidden>{{ __('Link copied') }}</span>
                </button>
            </div>

            <ol data-install-steps hidden
                class="grid gap-3 px-4 pt-4 text-sm [&_li]:flex [&_li]:items-center [&_li]:gap-3 [&_svg]:inline [&_svg]:size-6 [&_svg]:align-middle [&_svg]:text-[#0a84ff]">
                <li>
                    <span class="{{ $stepNumber }}">1</span>

                    <span data-install-share="browser" hidden>
                        {{ __('Tap the Share button') }} {!! $shareIcon !!} {{ __('in the address bar.') }}
                    </span>

                    <span data-install-share="menu" hidden>
                        {{ __('Tap the menu button') }} {!! $menuIcon !!}
                        {{ __('on the left of the address bar, then tap Share') }} {!! $shareIcon !!}
                    </span>

                    <span data-install-share="top" hidden>
                        {{ __('Tap the Share button') }} {!! $shareIcon !!} {{ __('at the top of the screen.') }}
                    </span>

                    <span data-install-share="bottom" hidden>
                        {{ __('Tap the Share button') }} {!! $shareIcon !!} {{ __('in the toolbar at the bottom of the screen.') }}
                    </span>
                </li>

                <li>
                    <span class="{{ $stepNumber }}">2</span>

                    <span>
                        {{ __('Scroll down and choose') }}
                        <strong class="whitespace-nowrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                                <rect x="4" y="4" width="16" height="16" rx="4" />
                                <path d="M12 8.5v7M8.5 12h7" />
                            </svg>
                            {{ __('Add to Home Screen') }}</strong>.
                    </span>
                </li>

                <li>
                    <span class="{{ $stepNumber }}">3</span>

                    <span>
                        {!! __('Tap <strong>Add</strong>.') !!}
                        {{ __(':hotel now opens full screen from your Home Screen.', ['hotel' => $hotel]) }}
                    </span>
                </li>

                <li data-install-browser-hint hidden class="text-[var(--muted)]">
                    {{ __("Don't see Add to Home Screen? Update iOS to 16.4 or newer, or open this page in Safari.") }}
                </li>
            </ol>

            <div class="px-4 pt-4">
                <button type="button" data-install-finish
                    class="min-h-11 w-full cursor-pointer rounded-md border-2 border-[var(--accent-border)] bg-[var(--accent)] font-bold text-white">
                    {{ __('Got it') }}
                </button>
            </div>
        </section>

        <div data-install-arrow hidden aria-hidden="true"
            class="pointer-events-none absolute animate-bounce text-[#eeb425] drop-shadow-[0_2px_0_rgb(0_0_0/0.6)] [&_svg]:size-10">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M10 3h4v11h4.5L12 21l-6.5-7H10z" />
            </svg>
        </div>
    </div>
</div>
