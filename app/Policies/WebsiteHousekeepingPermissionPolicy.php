<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebsiteHousekeepingPermission;
use App\Support\WebsiteSettingRules;

class WebsiteHousekeepingPermissionPolicy extends HousekeepingPolicy
{
    protected function permission(): string
    {
        return 'manage_housekeeping_permissions';
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
     * Whether the user may create a permission with this name, or rename one to it.
     */
    public function useName(User $user, string $name): bool
    {
        return $name !== WebsiteSettingRules::SENSITIVE_PERMISSION || $this->allows($user, WebsiteSettingRules::SENSITIVE_PERMISSION);
    }

    private function mayChangeRecord(User $user, mixed $record): bool
    {
        return ! $record instanceof WebsiteHousekeepingPermission || $this->useName($user, $record->permission);
    }
}
