<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteShopCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteShopCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteShopCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteShopCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteShopCategory whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteShopCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteShopCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteShopCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteShopCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class WebsiteShopCategory extends Model
{
    protected $guarded = [];

    /** @return HasMany<WebsiteShopPackage, $this> */
    public function packages(): HasMany
    {
        return $this->hasMany(WebsiteShopPackage::class);
    }
}
