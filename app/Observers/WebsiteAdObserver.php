<?php

namespace App\Observers;

use App\Models\WebsiteAd;
use App\Support\WebsiteSettingRules;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WebsiteAdObserver
{
    /**
     * Removes the ad's image file once the record is gone. Runs on the
     * "deleted" model event, so it also fires for each record removed by a
     * bulk action that deletes models individually.
     */
    public function deleted(WebsiteAd $websiteAd): void
    {
        if (! $websiteAd->image) {
            return;
        }

        $root = WebsiteSettingRules::hotelPath('ads_path_filesystem');

        if ($root === null) {
            logger()->error('Ad image file was not deleted: ads_path_filesystem is empty or outside HOTEL_FILE_ROOTS', ['file' => $websiteAd->image]);

            return;
        }

        try {
            $disk = Storage::build(['driver' => 'local', 'root' => $root]);

            if ($disk->exists($websiteAd->image)) {
                $disk->delete($websiteAd->image);
            } else {
                logger()->warning('Ad image file not found while deleting ad', ['file' => $websiteAd->image]);
            }
        } catch (Throwable $exception) {
            logger()->error('Failed to delete ad image file', [
                'file' => $websiteAd->image,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
