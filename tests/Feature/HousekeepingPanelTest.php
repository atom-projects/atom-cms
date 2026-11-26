<?php

use App\Filament\Resources\Atom\Articles\Pages\CreateArticle;
use App\Filament\Resources\User\Users\Pages\CreateUser;
use App\Models\Articles\WebsiteArticle;
use App\Models\Community\Teams\WebsiteTeam;
use App\Models\User;
use App\Models\WebsiteHousekeepingPermission;
use App\Services\HousekeepingPermissionsService;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

function grantPanelPermission(string $permission, int $minRank): void
{
    WebsiteHousekeepingPermission::query()->updateOrCreate(
        ['permission' => $permission],
        ['min_rank' => $minRank, 'description' => "Testing {$permission}"],
    );

    app(HousekeepingPermissionsService::class)->refresh();
}

beforeEach(function () {
    installHotel();
    setSetting('force_staff_2fa', '0');
});

test('eligible staff can open the housekeeping dashboard', function () {
    grantPanelPermission('can_access_housekeeping', 6);

    $staff = User::factory()->create(['rank' => 6]);

    $this->actingAs($staff)
        ->get('/housekeeping')
        ->assertOk();
});

test('the badge page is accessible with the manage badges permission', function () {
    grantPanelPermission('can_access_housekeeping', 6);
    grantPanelPermission('manage_badges', 6);

    $staff = User::factory()->create(['rank' => 6]);

    $this->actingAs($staff)
        ->get('/housekeeping/badge-page')
        ->assertOk();
});

test('the badge page is denied without the manage badges permission', function () {
    grantPanelPermission('can_access_housekeeping', 6);
    grantPanelPermission('manage_badges', 7);

    $staff = User::factory()->create(['rank' => 6]);

    $this->actingAs($staff)
        ->get('/housekeeping/badge-page')
        ->assertForbidden();
});

test('created articles are attributed to the authenticated staff member', function () {
    grantPanelPermission('can_access_housekeeping', 6);
    grantPanelPermission('write_article', 6);

    $staff = User::factory()->create(['rank' => 6]);
    $other = User::factory()->create();

    $this->actingAs($staff);

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Server-side authorship',
            'short_story' => 'The author is never client-controlled',
            'full_story' => '<p>Authored by the signed-in staff member.</p>',
        ])
        // Simulate a tampered Livewire payload trying to attribute the
        // article to another user.
        ->set('data.user_id', $other->id)
        ->call('create')
        ->assertHasNoFormErrors();

    $article = WebsiteArticle::query()->latest('id')->first();

    expect($article)->not->toBeNull()
        ->and($article->user_id)->toBe($staff->id);
});

test('staff create an account with a username and password of their choosing', function () {
    grantPanelPermission('can_access_housekeeping', 6);
    grantPanelPermission('edit_user', 6);

    $staff = User::factory()->create(['rank' => 6]);
    $team = WebsiteTeam::query()->create(['rank_name' => 'Moderators']);

    $this->actingAs($staff);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'username' => 'Housekept',
            'mail' => 'housekept@example.test',
            'motto' => 'Made in housekeeping',
            'gender' => 'F',
            'password' => 'Sup3r-Secret-Pass!',
            'password_confirmation' => 'Sup3r-Secret-Pass!',
            'rank' => 5,
            'hidden_staff' => true,
            'team_id' => $team->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::query()->where('username', 'Housekept')->first();

    expect($created)->not->toBeNull()
        ->and(Hash::check('Sup3r-Secret-Pass!', $created->password))->toBeTrue()
        ->and($created->rank)->toBe(5)
        ->and($created->motto)->toBe('Made in housekeeping')
        ->and($created->gender)->toBe('F')
        ->and($created->hidden_staff)->toBeTrue()
        ->and($created->team_id)->toBe($team->id)
        // The registration defaults the hotel gives every new account.
        ->and($created->look)->not->toBeEmpty()
        ->and($created->referral_code)->not->toBeEmpty();
});

test('the generated password is accepted as the new account password', function () {
    grantPanelPermission('can_access_housekeeping', 6);
    grantPanelPermission('edit_user', 6);

    $this->actingAs(User::factory()->create(['rank' => 6]));

    $page = Livewire::test(CreateUser::class)
        ->fillForm([
            'username' => 'Generated',
            'mail' => 'generated@example.test',
            'motto' => 'Generated password',
            'gender' => 'M',
            'rank' => 5,
        ])
        ->callFormComponentAction('password', 'generatePassword');

    $password = $page->get('data')['password'];

    expect($password)->toBeString()->not->toBeEmpty()
        ->and($page->get('data')['password_confirmation'])->toBe($password);

    $page->call('create')->assertHasNoFormErrors();

    $created = User::query()->where('username', 'Generated')->first();

    expect($created)->not->toBeNull()
        ->and(Hash::check($password, $created->password))->toBeTrue();
});

test('staff cannot create an account at or above their own rank', function () {
    grantPanelPermission('can_access_housekeeping', 6);
    grantPanelPermission('edit_user', 6);

    $this->actingAs(User::factory()->create(['rank' => 6]));

    Livewire::test(CreateUser::class)
        ->fillForm([
            'username' => 'Overreach',
            'mail' => 'overreach@example.test',
            'motto' => 'Too senior',
            'gender' => 'M',
            'password' => 'Sup3r-Secret-Pass!',
            'password_confirmation' => 'Sup3r-Secret-Pass!',
            'rank' => 6,
        ])
        ->call('create');

    expect(User::query()->where('username', 'Overreach')->exists())->toBeFalse();
});

test('a duplicate email is reported on the field', function () {
    grantPanelPermission('can_access_housekeeping', 6);
    grantPanelPermission('edit_user', 6);

    User::factory()->create(['mail' => 'taken@example.test']);

    $this->actingAs(User::factory()->create(['rank' => 6]));

    Livewire::test(CreateUser::class)
        ->fillForm([
            'username' => 'Duplicate',
            'mail' => 'taken@example.test',
            'motto' => 'Same address',
            'gender' => 'M',
            'password' => 'Sup3r-Secret-Pass!',
            'password_confirmation' => 'Sup3r-Secret-Pass!',
            'rank' => 5,
        ])
        ->call('create')
        ->assertHasFormErrors(['mail']);

    expect(User::query()->where('username', 'Duplicate')->exists())->toBeFalse();
});
