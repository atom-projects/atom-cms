@php
    /** @var \Illuminate\Support\Collection $children */
    $children = $this->pagesByParent->get($parentId, collect());
    $iconService = app(\App\Services\Catalog\FurniIconService::class);
@endphp

@if ($children->isNotEmpty())
    <ul
        @class([
            'catalog-flashui__tree-list',
            'catalog-flashui__tree-list--nested' => $depth > 0,
        ])
        data-parent-id="{{ $parentId }}"
    >
        @foreach ($children as $page)
            @php
                $hasChildren = $this->pagesByParent->has($page->id);
                $isOpen = $this->isInitiallyOpen($page->id);
                $isActive = $this->selectedPageId === $page->id;
            @endphp

            <li
                wire:key="catalog-page-{{ $page->id }}"
                data-id="{{ $page->id }}"
                data-page-id="{{ $page->id }}"
                @class([
                    'catalog-tree-item',
                    'has-children' => $hasChildren,
                    'is-open' => $hasChildren && $isOpen,
                    'is-active' => $isActive,
                ])
            >
                <div class="catalog-flashui__tree-row" data-tree-row>
                    <span
                        class="catalog-drag-handle"
                        title="Drag to reorder or move to another parent"
                        aria-label="Drag to reorder"
                    >⋮⋮</span>

                    @if ($hasChildren)
                        <button
                            type="button"
                            class="catalog-tree-toggle"
                            aria-label="Toggle {{ $page->caption }}"
                            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        >
                            <span class="catalog-tree-chevron">▶</span>
                        </button>
                    @else
                        <span class="catalog-flashui__tree-spacer"></span>
                    @endif

                    <img
                        src="{{ $iconService->pageIcon($page->icon_image) }}"
                        alt=""
                        loading="lazy"
                        class="catalog-flashui__tree-icon"
                        style="image-rendering: pixelated"
                        onerror="this.style.visibility='hidden'"
                    >

                    <button
                        type="button"
                        wire:click="selectPage({{ $page->id }})"
                        class="catalog-flashui__tree-label"
                        title="{{ $page->caption }}"
                    >
                        <span>{{ $page->caption }}</span>
                        <small>#{{ $page->id }}</small>
                    </button>
                </div>

                @if ($hasChildren)
                    @include('filament.resources.hotel.catalog-editors.pages.partials.catalog-tree', [
                        'parentId' => $page->id,
                        'depth' => $depth + 1,
                    ])
                @endif
            </li>
        @endforeach
    </ul>
@endif
