<?php

namespace App\Policies;

use App\Models\Miscellaneous\WebsiteSetting;
use App\Models\User;
use App\Support\WebsiteSettingRules;

class WebsiteSettingPolicy extends HousekeepingPolicy
{
    protected function permission(): string
    {
        return 'manage_website_settings';
    }

    protected function deletePermission(): string
    {
        return 'delete_website_settings';
    }

    public function update(User $user, mixed $record = null): bool
    {
        return parent::update($user, $record) && $this->mayChangeRecord($user, $record);
    }

    public function delete(User $user, mixed $record = null): bool
    {
        return parent::delete($user, $record) && $this->mayChangeRecord($user, $record);
    }

    /**
     * Whether the user may create a setting with this key, or rename one to it.
     */
    public function useKey(User $user, string $key): bool
    {
        return ! WebsiteSettingRules::isSensitive($key) || $this->allows($user, WebsiteSettingRules::SENSITIVE_PERMISSION);
    }

    private function mayChangeRecord(User $user, mixed $record): bool
    {
        return ! $record instanceof WebsiteSetting || $this->useKey($user, $record->key);
    }
}
