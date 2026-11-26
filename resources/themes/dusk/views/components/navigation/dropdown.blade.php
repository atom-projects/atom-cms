@props(['icon', 'routeGroup' => [], 'classes' => '', 'childClasses' => 'min-w-[150px]', 'uppercase' => false, 'showChevron' => false, 'flexCol' => true, 'responsive' => false, 'href' => null])

<div
    x-data="{
        open: false,
        openedByHover: false,
        openFromKeyboard(event) {
            event.preventDefault()
            this.openedByHover = false
            this.open = true
            this.$nextTick(() => this.$refs.panel.querySelector('a, button')?.focus())
        },
        triggerElement() {
            return this.$refs.toggleButton ?? this.$refs.button
        },
        toggle(event) {
            if (
                event?.pointerType === 'mouse' &&
                event.currentTarget.closest('.nav-header') &&
                window.matchMedia('(min-width: 64rem)').matches
            ) {
                this.openedByHover = true
                this.open = true
                return
            }

            if (this.open && !this.openedByHover) {
                return this.close()
            }

            this.triggerElement().focus()
            this.openedByHover = false
            this.open = true
        },
        openOnHover(event) {
            if (event.pointerType === 'mouse' && !this.open) {
                this.openedByHover = true
                this.open = true
            }
        },
        closeOnLeave(event) {
            if (
                event.pointerType === 'mouse' &&
                this.openedByHover &&
                !this.$refs.panel.contains(this.$refs.button.ownerDocument.activeElement)
            ) {
                this.close()
            }
        },
        close(focusAfter) {
            if (!this.open) {
                return
            }

            this.open = false
            this.openedByHover = false

            if (focusAfter || this.$refs.panel.contains(this.$refs.button.ownerDocument.activeElement)) {
                (focusAfter || this.triggerElement()).focus()
            }
        }
    }"
    x-on:pointerenter="openOnHover($event)"
    x-on:pointerleave="closeOnLeave($event)"
    x-on:keydown.escape="if (open) { $event.preventDefault(); $event.stopPropagation(); close(triggerElement()) }"
    x-on:focusin.window="! $el.contains($event.target) && close()"
    x-on:click.outside="close()"
    x-id="['dropdown-button']"
    :class="open ? 'z-20' : 'z-5'"
    @if($responsive)
        x-init="$watch('menuOpen', value => { if (!value) close() })"
    @endif
    @class([
        'relative h-auto font-semibold transition duration-300 ease-in-out',
        'active' => request()->routeIs(...(array) $routeGroup),
        $classes,
    ])
>
    <div class="flex items-center gap-1">
    @if($href)
    <a
        x-ref="button"
        x-on:keydown.down="openFromKeyboard($event)"
        href="{{ $href }}"
        aria-expanded="false"
        :aria-expanded="open"
        :aria-controls="$id('dropdown-button')"
        @class([
            'flex gap-1 items-center transition ease-in-out hover:text-[#ac93da] dropdown-parent',
            'flex-col' => $flexCol && !$responsive,
            'lg:flex-col' => $flexCol && $responsive,
        ])
    >
    @else
    <button
        x-ref="button"
        x-on:click="toggle($event)"
        x-on:keydown.down="openFromKeyboard($event)"
        aria-expanded="false"
        :aria-expanded="open"
        :aria-controls="$id('dropdown-button')"
        type="button"
        @class([
            'flex gap-1  items-center transition ease-in-out hover:text-[#ac93da] dropdown-parent',
            'flex-col' => $flexCol && !$responsive,
            'lg:flex-col' => $flexCol && $responsive,
        ])
    >
    @endif
        @if(isset($icon))
            <img @class(['icon', 'hidden lg:block' => $responsive]) src="{{ asset(sprintf('/assets/images/dusk/%s', $icon)) }}" alt="">
        @endif

        {{ $slot }}

        @if(($showChevron || $responsive) && !$href)
            <x-navigation.chevron :classes="$responsive && !$showChevron ? 'lg:hidden' : ''" />
        @endif
    @if($href)
    </a>
    <button
        x-ref="toggleButton"
        x-on:click="toggle($event)"
        type="button"
        aria-expanded="false"
        :aria-expanded="open"
        :aria-controls="$id('dropdown-button')"
    >
        <span class="sr-only">{{ $slot }}</span>
        <x-navigation.chevron />
    </button>
    @else
    </button>
    @endif
    </div>

    <!-- Panel -->
    <div
        x-ref="panel"
        x-show="open"
        x-transition:enter.origin.top.left
        :id="$id('dropdown-button')"
        style="display: none;"
        @class([
            'absolute left-0 pt-1 whitespace-nowrap z-[100]',
            $childClasses,
        ])
    >
        <div class="w-full rounded bg-[#ac93da] shadow-sm overflow-hidden flex flex-col py-2 items-center gap-2 dropdown-children">
            {{ $children }}
        </div>
    </div>
</div>
