<x-filament-panels::page class="!max-w-full catalog-flashui-page-shell">
    <div
        class="catalog-flashui"
        x-data="{
            init() {
                this._key = (event) => {
                    if (event.target.matches('input, textarea, [contenteditable]')) return;

                    const search = this.$el.querySelector('input[type=search]');

                    if (event.key === '/') {
                        event.preventDefault();
                        search?.focus();
                    } else if (event.key === 'Escape' && search?.value) {
                        search.value = '';
                        search.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                };

                document.addEventListener('keydown', this._key);
            },
            destroy() { document.removeEventListener('keydown', this._key); },
        }"
    >
        <header class="catalog-flashui__chrome">
            <div class="catalog-flashui__chrome-title">
                <span class="catalog-flashui__logo-mark">A</span>
                <span>CATALOGUE EDITOR</span>
            </div>
            <span class="catalog-flashui__chrome-subtitle">Atom CMS · FlashUI skin</span>
        </header>

        <div class="catalog-flashui__body">
            <div class="catalog-flashui__toolbar">
                <div>
                    <p class="catalog-flashui__eyebrow">HOUSEKEEPING / HOTEL</p>
                    <h1 class="catalog-flashui__title">Catalogus beheren</h1>
                </div>

                <div class="catalog-flashui__search">
                    <span class="catalog-flashui__search-icon" aria-hidden="true">⌕</span>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="searchTerm"
                        placeholder="Zoek pagina of meubel…"
                        aria-label="Search pages or items"
                    >
                    @if ($searchTerm !== '')
                        <button type="button" wire:click="clearSearch" title="Clear search" aria-label="Clear search">×</button>
                    @endif
                    <kbd>/</kbd>
                </div>
            </div>

            <div class="catalog-flashui__workspace">
                <aside class="catalog-flashui__tree-panel">
                    <div class="catalog-flashui__panel-heading">
                        <span>CATALOGUS</span>
                        <span class="catalog-flashui__panel-hint">SLEEP OM TE SORTEREN</span>
                    </div>

                    <nav
                        class="catalog-flashui__tree"
                        aria-label="Catalog tree"
                        x-data="catalogTree"
                        x-init="init()"
                        wire:ignore.self
                    >
                        @include('filament.resources.hotel.catalog-editors.pages.partials.catalog-tree', [
                            'parentId' => -1,
                            'depth'    => 0,
                        ])
                    </nav>
                </aside>

                <section class="catalog-flashui__content-panel">
                    @if ($this->selectedPage)
                        <header class="catalog-flashui__content-header">
                            <nav class="catalog-flashui__breadcrumbs" aria-label="Breadcrumb">
                                @foreach ($this->breadcrumb as $i => $crumb)
                                    @if ($i > 0)
                                        <span aria-hidden="true">›</span>
                                    @endif
                                    <button type="button" wire:click="selectPage({{ $crumb->id }})">
                                        {{ $crumb->caption }}
                                    </button>
                                @endforeach
                            </nav>

                            <div class="catalog-flashui__page-heading">
                                <div class="catalog-flashui__page-title-wrap">
                                    <img
                                        src="{{ app(\App\Services\Catalog\FurniIconService::class)->pageIcon($this->selectedPage->icon_image) }}"
                                        alt=""
                                        class="catalog-flashui__page-icon"
                                        loading="lazy"
                                        onerror="this.style.visibility='hidden'"
                                    >
                                    <div>
                                        <p class="catalog-flashui__eyebrow">CATALOG PAGE #{{ $this->selectedPage->id }}</p>
                                        <h2 class="catalog-flashui__page-title">{{ $this->selectedPage->caption }}</h2>
                                    </div>
                                </div>

                                <div class="catalog-flashui__page-actions">
                                    {{ $this->editPageAction }}
                                    {{ $this->resetSpacingAction }}
                                    {{ $this->sortAlphabeticallyAction }}
                                </div>
                            </div>
                        </header>

                        @if ($this->lockedItems->isNotEmpty())
                            <div class="catalog-flashui__notice" role="status">
                                <div>
                                    <strong>{{ $this->lockedItems->count() }} locked item(s)</strong>
                                    <span>Ordernummer <code>-1</code> wordt niet meegenomen in drag-and-drop.</span>
                                </div>
                                <div class="catalog-flashui__locked-list">
                                    @foreach ($this->lockedItems as $locked)
                                        <button type="button" wire:click="unlockItem({{ $locked->id }})" title="Unlock {{ $locked->catalog_name }}">
                                            {{ $locked->catalog_name }} <span>↗</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="catalog-flashui__table-wrap">
                            {{ $this->table }}
                        </div>
                    @else
                        <div class="catalog-flashui__empty">
                            <span class="catalog-flashui__empty-icon">▦</span>
                            <h2>Selecteer een cataloguspagina</h2>
                            <p>Kies links een pagina om meubelen te bekijken, bewerken en sorteren.</p>
                        </div>
                    @endif
                </section>
            </div>

            <footer class="catalog-flashui__footer">
                <span>FLASHUI CATALOG EDITOR</span>
                <span>Drag pages to move · Drag items to reorder · <strong>/</strong> search</span>
            </footer>
        </div>
    </div>

    @once
        @push('scripts')
            <script>
                // Tree is server-rendered once; expand/collapse and drag remain
                // client-side so Livewire morphs cannot interrupt a drag.
                document.addEventListener('alpine:init', () => {
                    Alpine.data('catalogTree', () => ({
                        sortables: [],
                        hoverTimer: null,
                        hoverLi: null,
                        isDragging: false,

                        init() {
                            this._attachMissingSortables();

                            this._remountScheduled = false;
                            const scheduleRemount = () => {
                                if (this._remountScheduled) return;
                                this._remountScheduled = true;
                                queueMicrotask(() => {
                                    this._remountScheduled = false;
                                    this._attachMissingSortables();
                                });
                            };

                            Livewire.hook('morph.updated', ({ el }) => {
                                if (this.$el.contains(el)) scheduleRemount();
                            });

                            this.$el.addEventListener('click', (event) => {
                                const toggle = event.target.closest('.catalog-tree-toggle');
                                if (!toggle) return;

                                event.preventDefault();
                                event.stopPropagation();

                                const item = toggle.closest('.catalog-tree-item');
                                if (!item) return;

                                const open = item.classList.toggle('is-open');
                                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                            });
                        },

                        destroy() {
                            this.sortables.forEach((sortable) => { try { sortable.destroy(); } catch (e) {} });
                            this.sortables = [];
                            this._cancelHoverExpand();
                        },

                        _attachMissingSortables() {
                            if (typeof Sortable === 'undefined') return;

                            this.sortables = this.sortables.filter((sortable) => {
                                if (sortable.el?.isConnected) return true;
                                try { sortable.destroy(); } catch (e) {}
                                return false;
                            });

                            this.$el.querySelectorAll('ul.catalog-flashui__tree-list').forEach((list) => {
                                if (Sortable.get(list)) return;

                                this.sortables.push(new Sortable(list, {
                                    group: 'catalog-pages',
                                    handle: '.catalog-drag-handle',
                                    animation: 150,
                                    ghostClass: 'catalog-flashui__drag-ghost',
                                    chosenClass: 'catalog-flashui__drag-chosen',
                                    fallbackOnBody: true,
                                    invertSwap: true,
                                    emptyInsertThreshold: 12,
                                    onStart: () => {
                                        this.isDragging = true;
                                        this.$el.classList.add('is-dragging');
                                    },
                                    onMove: (event) => this._onMove(event),
                                    onEnd: (event) => {
                                        this.isDragging = false;
                                        this.$el.classList.remove('is-dragging');
                                        this._cancelHoverExpand();
                                        this._onDrop(event);
                                    },
                                }));
                            });
                        },

                        _onDrop(event) {
                            const movedId = parseInt(event.item.dataset.id, 10);
                            const newParent = parseInt(event.to.dataset.parentId, 10);

                            if (!Number.isFinite(movedId) || !Number.isFinite(newParent)) return;
                            this.$wire.movePage(movedId, newParent, event.newIndex);
                        },

                        _onMove(event) {
                            const item = event.related?.closest?.('.catalog-tree-item');
                            if (!item) {
                                this._cancelHoverExpand();
                                return true;
                            }

                            if (item === this.hoverLi) return true;

                            this._cancelHoverExpand();
                            this.hoverLi = item;
                            item.classList.add('is-hover-target');

                            if (item.classList.contains('has-children') && !item.classList.contains('is-open')) {
                                this.hoverTimer = setTimeout(() => {
                                    item.classList.add('is-open');
                                    this._attachMissingSortables();
                                }, 450);
                            }

                            return true;
                        },

                        _cancelHoverExpand() {
                            if (this.hoverTimer) clearTimeout(this.hoverTimer);
                            this.hoverTimer = null;
                            if (this.hoverLi) this.hoverLi.classList.remove('is-hover-target');
                            this.hoverLi = null;
                        },
                    }));
                });
            </script>
        @endpush
    @endonce
</x-filament-panels::page>
