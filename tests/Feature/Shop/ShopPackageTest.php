<?php

use App\Actions\Shop\PurchaseShopPackage;
use App\Data\RconResponse;
use App\Emulator\Contracts\CurrencyRepository;
use App\Enums\CurrencyTypes;
use App\Exceptions\ShopPurchaseException;
use App\Models\Shop\WebsiteShopCategory;
use App\Models\Shop\WebsiteShopItem;
use App\Models\Shop\WebsiteShopPackage;
use App\Models\Shop\WebsiteShopPurchase;
use App\Models\User;
use App\Support\PermissionRanks;
use Database\Seeders\WebsiteShopSeeder;
use Illuminate\Support\Collection;

test('the shop lists packages alongside articles', function () {
    installHotel();

    $user = User::factory()->create();
    $package = makePackage();

    $this->actingAs($user)
        ->get(route('shop.index'))
        ->assertOk()
        ->assertViewHas('shopPackages', fn ($packages) => $packages->contains($package))
        ->assertSee($package->name);
});

test('the seeded shop categories have local icons and render once in the Atom theme', function () {
    installHotel();
    $this->seed(WebsiteShopSeeder::class);

    $user = User::factory()->create();
    $categories = WebsiteShopCategory::query()
        ->where('is_active', true)
        ->whereHas('packages')
        ->get();

    expect($categories)->not->toBeEmpty()
        ->and($categories->every(fn (WebsiteShopCategory $category): bool => is_string($category->icon)
            && str_starts_with($category->icon, '/assets/images/')))->toBeTrue();

    $response = $this->actingAs($user)->get(route('shop.index'))->assertOk();

    foreach ($categories as $category) {
        expect(substr_count($response->getContent(), route('shop.index', $category)))->toBe(1);
    }
});

test('the seeded shop sells no rank and no seeded package needs a player rank above the default', function () {
    installHotel();
    // The lowest staff rank on a stock Arcturus database.
    setSetting('min_staff_rank', '3');
    $this->seed(WebsiteShopSeeder::class);

    $playerRanks = array_values(array_diff(array_keys(PermissionRanks::grantableRanks()), [1]));

    expect($playerRanks)->toContain(2)
        ->and(WebsiteShopItem::query()->where('type', 'rank')->pluck('name')->all())->toBe([])
        ->and(WebsiteShopPackage::query()->whereIn('min_rank', $playerRanks)->pluck('name')->all())->toBe([]);
});

test('a package purchase charges the buyer and delivers its items', function () {
    installHotel();

    $user = User::factory()->create(['website_balance' => 2000]);
    $package = makePackage();
    $startCredits = (int) $user->credits;

    $this->actingAs($user)
        ->post(route('shop.buy-package', $package), [])
        ->assertSessionHas('success');

    $user->refresh();

    expect((int) $user->website_balance)->toBe(1500)
        ->and((int) $user->credits)->toBe($startCredits + 200)
        ->and(WebsiteShopPurchase::where('user_id', $user->id)->count())->toBe(1);
});

test('a package price is charged exactly in minor units', function () {
    installHotel();

    $user = User::factory()->create(['website_balance' => 300]);
    $package = makePackage(['price' => 299]);

    $this->actingAs($user)
        ->post(route('shop.buy-package', $package), [])
        ->assertSessionHas('success');

    expect((int) $user->refresh()->website_balance)->toBe(1)
        ->and($package->formattedPrice())->toBe('USD 2.99');
});

test('a buyer without enough balance is rejected and nothing is delivered', function () {
    installHotel();

    $user = User::factory()->create(['website_balance' => 100]);
    $package = makePackage();

    $this->actingAs($user)
        ->post(route('shop.buy-package', $package), [])
        ->assertSessionHasErrors('message');

    expect((int) $user->refresh()->website_balance)->toBe(100)
        ->and(WebsiteShopPurchase::count())->toBe(0);
});

test('the per-user purchase limit is enforced', function () {
    installHotel();

    $user = User::factory()->create(['website_balance' => 10000]);
    $package = makePackage(['limit_per_user' => 1]);

    $this->actingAs($user)->post(route('shop.buy-package', $package), [])->assertSessionHas('success');
    $this->actingAs($user)->post(route('shop.buy-package', $package), [])->assertSessionHasErrors('message');

    expect(WebsiteShopPurchase::where('user_id', $user->id)->count())->toBe(1)
        ->and((int) $user->refresh()->website_balance)->toBe(9500);
});

test('an out of stock package cannot be purchased', function () {
    installHotel();

    $user = User::factory()->create(['website_balance' => 10000]);
    $package = makePackage(['stock' => 0]);

    $this->actingAs($user)
        ->post(route('shop.buy-package', $package), [])
        ->assertSessionHasErrors('message');

    expect(WebsiteShopPurchase::count())->toBe(0);
});

test('a non-giftable package cannot be gifted', function () {
    installHotel();

    $user = User::factory()->create(['website_balance' => 10000]);
    $recipient = User::factory()->create();
    $package = makePackage(['is_giftable' => false]);

    $this->actingAs($user)
        ->post(route('shop.buy-package', $package), ['receiver' => $recipient->username])
        ->assertSessionHasErrors('message');

    expect(WebsiteShopPurchase::count())->toBe(0);
});

test('a gifted package delivers to the recipient and records the gift', function () {
    installHotel();

    $buyer = User::factory()->create(['website_balance' => 10000]);
    $recipient = User::factory()->create();
    $package = makePackage(['is_giftable' => true]);
    $recipientCredits = (int) $recipient->credits;

    $this->actingAs($buyer)
        ->post(route('shop.buy-package', $package), ['receiver' => $recipient->username])
        ->assertSessionHas('success');

    expect((int) $buyer->refresh()->website_balance)->toBe(9500)
        ->and((int) $recipient->refresh()->credits)->toBe($recipientCredits + 200)
        ->and(WebsiteShopPurchase::where('user_id', $buyer->id)->where('gifted_to', $recipient->id)->count())->toBe(1);
});

test('a package that stops being giftable cannot be delivered from a stale model', function () {
    installHotel();

    $buyer = User::factory()->create(['website_balance' => 10000]);
    $recipient = User::factory()->create();
    $package = makePackage(['is_giftable' => true, 'stock' => 1]);
    $recipientCredits = (int) $recipient->credits;

    WebsiteShopPackage::whereKey($package->id)->update(['is_giftable' => false]);

    expect(fn () => app(PurchaseShopPackage::class)->execute($buyer, $package, $recipient->username))
        ->toThrow(ShopPurchaseException::class, 'This package is not giftable');

    expect((int) $buyer->refresh()->website_balance)->toBe(10000)
        ->and((int) $recipient->refresh()->credits)->toBe($recipientCredits)
        ->and($package->refresh()->stock)->toBe(1)
        ->and(WebsiteShopPurchase::count())->toBe(0);
});

test('invalid item numbers reject the purchase and roll back earlier rewards', function (string $type, string $value, int $quantity) {
    installHotel();

    $buyer = User::factory()->create(['website_balance' => 10000, 'rank' => 1]);
    $package = makePackage(['stock' => 1]);
    $credits = (int) $buyer->credits;
    $rank = (int) $buyer->rank;
    $item = WebsiteShopItem::create([
        'name' => 'Misconfigured reward',
        'type' => $type,
        'type_value' => $value,
        'is_active' => true,
    ]);
    $package->items()->attach($item->id, ['quantity' => $quantity]);

    $this->actingAs($buyer)
        ->post(route('shop.buy-package', $package))
        ->assertSessionHasErrors('message');

    expect((int) $buyer->refresh()->website_balance)->toBe(10000)
        ->and((int) $buyer->credits)->toBe($credits)
        ->and((int) $buyer->rank)->toBe($rank)
        ->and($package->refresh()->stock)->toBe(1)
        ->and(WebsiteShopPurchase::count())->toBe(0);
})->with([
    'currency suffix' => ['currency', 'credits:50bad', 1],
    'fractional currency' => ['currency', 'credits:50.5', 1],
    'furniture suffix' => ['furniture', '230bad', 1],
    'rank suffix' => ['rank', '5bad', 1],
    'currency multiplication overflow' => ['currency', 'credits:' . PHP_INT_MAX, 2],
]);

function attachRankItem(WebsiteShopPackage $package, string $rank): void
{
    $item = WebsiteShopItem::create([
        'name' => "Rank {$rank}",
        'type' => 'rank',
        'type_value' => $rank,
        'is_active' => true,
    ]);

    $package->items()->attach($item->id, ['quantity' => 1]);
}

test('a rank package is refused before payment when the receiver already has that rank or higher', function (int $buyerRank, ?int $receiverRank) {
    installHotel();

    $buyer = User::factory()->create(['website_balance' => 10000, 'rank' => $buyerRank]);
    $receiver = $receiverRank === null ? $buyer : User::factory()->create(['rank' => $receiverRank]);
    $package = makePackage(['is_giftable' => true]);
    attachRankItem($package, '2');
    $credits = (int) $receiver->credits;
    $rank = (int) $receiver->rank;

    $this->actingAs($buyer)
        ->post(route('shop.buy-package', $package), $receiver->is($buyer) ? [] : ['receiver' => $receiver->username])
        ->assertSessionHasErrors(['message' => 'The account that receives this package already has this rank or a higher one']);

    expect((int) $buyer->refresh()->website_balance)->toBe(10000)
        ->and((int) $receiver->refresh()->rank)->toBe($rank)
        ->and((int) $receiver->credits)->toBe($credits)
        ->and(WebsiteShopPurchase::count())->toBe(0);
})->with([
    'a gift to an administrator' => [1, 7],
    'a gift to a player with the same rank' => [1, 2],
    'staff buying for themselves' => [6, null],
]);

test('a rank package with a staff rank is refused before payment', function () {
    installHotel();

    $buyer = User::factory()->create(['website_balance' => 10000, 'rank' => 1]);
    $package = makePackage(['price' => 0]);
    attachRankItem($package, '7');

    $this->actingAs($buyer)
        ->post(route('shop.buy-package', $package))
        ->assertSessionHasErrors(['message' => 'This package is currently unavailable']);

    expect((int) $buyer->refresh()->rank)->toBe(1)
        ->and(WebsiteShopPurchase::count())->toBe(0);
});

test('a refused rank package sends nothing to the emulator', function (int $buyerRank, ?int $receiverRank, string $rank, string $message) {
    installHotel();

    $buyer = User::factory()->create(['website_balance' => 10000, 'rank' => $buyerRank, 'online' => '1'])->refresh();
    $receiver = $receiverRank === null ? $buyer : User::factory()->create(['rank' => $receiverRank, 'online' => '1'])->refresh();
    $package = makePackage(['is_giftable' => true]);
    attachRankItem($package, $rank);
    $this->rcon->connected();

    $this->actingAs($buyer)
        ->post(route('shop.buy-package', $package), $receiver->is($buyer) ? [] : ['receiver' => $receiver->username])
        ->assertSessionHasErrors(['message' => $message]);

    expect($this->rcon->calls)->toBe([])
        ->and($receiver->refresh()->online)->toBeTrue()
        ->and((int) $buyer->refresh()->website_balance)->toBe(10000)
        ->and(WebsiteShopPurchase::count())->toBe(0);
})->with([
    'a gift to an online administrator' => [1, 7, '2', 'The account that receives this package already has this rank or a higher one'],
    'an online player buying a rank they already have' => [2, null, '2', 'The account that receives this package already has this rank or a higher one'],
    'a staff rank gifted to an online player' => [1, 1, '7', 'This package is currently unavailable'],
]);

test('an accepted rank package still alerts and disconnects an online receiver', function () {
    installHotel();

    $buyer = User::factory()->create(['website_balance' => 10000, 'rank' => 1, 'online' => '1'])->refresh();
    $package = makePackage();
    attachRankItem($package, '2');
    $this->rcon->connected();

    $this->actingAs($buyer)
        ->post(route('shop.buy-package', $package))
        ->assertSessionHas('success');

    expect((int) $buyer->refresh()->rank)->toBe(2)
        ->and($buyer->online)->toBeFalse()
        ->and(array_map(fn (array $call): string => $call['args']['command'], $this->rcon->calls))->toBe(['alertuser', 'disconnect']);
});

test('an online recipient is disconnected before atomic delivery', function () {
    installHotel();

    $user = User::factory()->create(['website_balance' => 2000, 'online' => '1'])->refresh();
    $package = makePackage();
    $startCredits = (int) $user->credits;
    $this->rcon->connected();

    $this->actingAs($user)
        ->post(route('shop.buy-package', $package), [])
        ->assertSessionHas('success');

    expect($user->refresh()->online)->toBeFalse()
        ->and((int) $user->website_balance)->toBe(1500)
        ->and((int) $user->credits)->toBe($startCredits + 200)
        ->and(array_column($this->rcon->calls, 'method'))->toBe(['sendCommand', 'sendCommand'])
        ->and($this->rcon->calls[0]['args']['command'])->toBe('alertuser')
        ->and($this->rcon->calls[1]['args']['command'])->toBe('disconnect');
});

test('a failed online disconnect leaves balances and goods untouched', function () {
    installHotel();

    $user = User::factory()->create(['website_balance' => 2000, 'online' => '1'])->refresh();
    $package = makePackage();
    $startCredits = (int) $user->credits;
    $this->rcon->connected()->respondWith(
        new RconResponse(2, 'Arcturus alert response'),
        new RconResponse(1, 'Unable to disconnect user'),
    );

    $this->actingAs($user)
        ->post(route('shop.buy-package', $package), [])
        ->assertSessionHasErrors('message');

    expect($user->refresh()->online)->toBeTrue()
        ->and((int) $user->website_balance)->toBe(2000)
        ->and((int) $user->credits)->toBe($startCredits)
        ->and(WebsiteShopPurchase::count())->toBe(0);
});

test('a fulfillment failure rolls back goods stock charge and history', function () {
    installHotel();

    $user = User::factory()->create(['website_balance' => 2000]);
    $package = makePackage(['stock' => 1]);
    $startCredits = (int) $user->credits;
    $currencies = app(CurrencyRepository::class);

    $failingCurrencies = new class($currencies) implements CurrencyRepository
    {
        public function __construct(private readonly CurrencyRepository $inner) {}

        public function balance(User $user, CurrencyTypes $currency): int
        {
            return $this->inner->balance($user, $currency);
        }

        public function give(User $user, CurrencyTypes $currency, int $amount): void
        {
            $this->inner->give($user, $currency, $amount);

            throw new RuntimeException('Simulated fulfillment failure');
        }

        public function deduct(User $user, CurrencyTypes $currency, int $amount): bool
        {
            return $this->inner->deduct($user, $currency, $amount);
        }

        public function topBy(CurrencyTypes $currency, int $limit, array $excludeUserIds = []): Collection
        {
            return $this->inner->topBy($currency, $limit, $excludeUserIds);
        }
    };

    $this->app->instance(CurrencyRepository::class, $failingCurrencies);

    expect(fn () => app(PurchaseShopPackage::class)->execute($user, $package, null))
        ->toThrow(RuntimeException::class, 'Simulated fulfillment failure');

    expect((int) $user->refresh()->website_balance)->toBe(2000)
        ->and((int) $user->credits)->toBe($startCredits)
        ->and($package->refresh()->stock)->toBe(1)
        ->and(WebsiteShopPurchase::count())->toBe(0);
});
