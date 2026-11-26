<?php

namespace App\Filament\Resources\User\Users\Pages;

use App\Actions\Fortify\CreateNewUser;
use App\Filament\Resources\User\Users\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * The rank select only offers ranks below the actor's, which the form alone
     * cannot be trusted to enforce.
     *
     * @throws Halt
     */
    protected function beforeCreate(): void
    {
        $data = $this->getSchema('form')?->getState()
            ?? throw new LogicException('The user create form schema is not registered.');
        $actor = auth()->user();

        if (! $actor instanceof User || (int) $data['rank'] >= $actor->rank) {
            Notification::make()
                ->danger()
                ->title(__('You cannot create this user!'))
                ->body(__('You cannot create users with a rank equal to or higher than yours.'))
                ->send();

            $this->halt();
        }
    }

    /**
     * Accounts are created through the registration action, so one a staff
     * member makes starts out like a self-registered one: the hotel's figure,
     * balances, a referral code of its own, and a hashed password. The form's
     * own values and the guarded rank, team and visibility are written on top.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateNewUser::class)->createByStaff($data, [
            'rank' => (int) $data['rank'],
            'motto' => $data['motto'],
            'gender' => $data['gender'],
            'hidden_staff' => (bool) ($data['hidden_staff'] ?? false),
            'team_id' => $data['team_id'] ?? null,
        ]);
    }
}
