<?php

use App\Models\Articles\WebsiteArticle;
use App\Models\Community\Teams\WebsiteTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

it('renders Plus staff cards with their rank details', function (string $theme) {
    seedPlusRoles();
    $staff = User::factory()->count(2)->create(['rank' => 7]);
    setSetting('theme', $theme);

    $response = $this->actingAs($staff->firstOrFail())
        ->get(route('staff.index'))
        ->assertOk()
        ->assertSee([$staff[0]->username, $staff[1]->username, 'Owner']);

    expect(substr_count($response->getContent(), 'assets/images/staff-bg.png'))->toBe(2);
})->with(['atom', 'dusk']);

it('loads the article author role and default background for Plus', function (string $theme) {
    seedPlusRoles();
    $author = User::factory()->create(['rank' => 7]);
    $article = WebsiteArticle::create([
        'user_id' => $author->id,
        'title' => 'Plus hotel opening',
        'short_story' => 'The hotel is open.',
        'full_story' => '<p>Welcome to the hotel.</p>',
        'image' => 'articles/opening.png',
    ]);
    setSetting('theme', $theme);

    $this->actingAs($author)
        ->get(route('article.show', $article->slug))
        ->assertOk()
        ->assertViewHas('article', fn (WebsiteArticle $displayed): bool => $displayed->user?->permission?->rank_name === 'Owner')
        ->assertSee('assets/images/staff-bg.png');
})->with(['atom', 'dusk']);

it('loads the rank of Plus team members', function (string $theme) {
    seedPlusRoles();
    setSetting('theme', $theme);
    setSetting('enable_caching', '0');
    $team = WebsiteTeam::create([
        'rank_name' => 'Events Crew',
        'badge' => 'EVT',
        'hidden_rank' => false,
        'staff_color' => '#123456',
        'staff_background' => '',
        'job_description' => 'Hosts community events.',
    ]);
    $member = User::factory()->create(['rank' => 7, 'team_id' => $team->id]);

    $this->actingAs($member)
        ->get(route('teams.index'))
        ->assertOk()
        ->assertSee($member->username)
        ->assertViewHas('employees', fn (Collection $teams): bool => $teams->firstOrFail()->users->firstOrFail()->permission?->rank_name === 'Owner');
})->with(['atom', 'dusk']);
