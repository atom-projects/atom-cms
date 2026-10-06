<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\FurnitureRepository;
use App\Emulator\Data\FurnitureHolding;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PlusFurnitureRepository implements FurnitureRepository
{
    public function definitionCount(): int
    {
        return DB::table('furniture')->count();
    }

    public function isLimitedEdition(int $baseItemId): bool
    {
        return DB::table('catalog_items')->where('item_id', (string) $baseItemId)->where('limited_stack', '>', 0)->exists();
    }

    public function grant(User $user, int $baseItemId, int $amount): void
    {
        $row = ['user_id' => $user->id, 'room_id' => 0, 'base_item' => $baseItemId, 'extra_data' => ''];
        foreach (array_chunk(array_fill(0, max(0, $amount), $row), 500) as $chunk) {
            DB::table('items')->insert($chunk);
        }
    }

    public function holdings(int $baseItemId, int $limit = 100): Collection
    {
        return DB::table('items')->where('base_item', $baseItemId)->groupBy('user_id')->selectRaw('user_id, COUNT(*) item_count')
            ->orderByDesc('item_count')->limit($limit)->get()
            ->map(fn (object $row) => new FurnitureHolding((int) $row->user_id, (int) $row->item_count));
    }
}
