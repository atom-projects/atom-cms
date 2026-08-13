import "./bootstrap";

import Sortable from "sortablejs";

// The catalog tree uses SortableJS for page drag-and-drop. Expose one shared
// instance for Livewire/Alpine pages instead of loading a second copy per view.
window.Sortable = Sortable;
