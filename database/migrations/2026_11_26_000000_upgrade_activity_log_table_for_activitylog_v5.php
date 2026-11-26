<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Activitylog v5 keeps tracked model changes in `attribute_changes` and
     * only custom data in `properties`, and it no longer has batches.
     */
    public function up(): void
    {
        if (! columnExists('activity_log', 'attribute_changes')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->json('attribute_changes')->nullable()->after('causer_id');
            });
        }

        DB::table('activity_log')
            ->where(function ($query) {
                $query->whereNotNull('properties->attributes')
                    ->orWhereNotNull('properties->old');
            })
            ->eachById(function (object $row) {
                $properties = json_decode((string) $row->properties, true) ?: [];
                $changes = array_intersect_key($properties, array_flip(['attributes', 'old']));
                $remaining = array_diff_key($properties, array_flip(['attributes', 'old']));

                DB::table('activity_log')->where('id', $row->id)->update([
                    'attribute_changes' => $changes === [] ? null : json_encode($changes),
                    'properties' => $remaining === [] ? null : json_encode($remaining),
                ]);
            });

        if (columnExists('activity_log', 'batch_uuid')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropColumn('batch_uuid');
            });
        }
    }

    public function down(): void
    {
        if (! columnExists('activity_log', 'batch_uuid')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->uuid('batch_uuid')->nullable()->after('properties');
            });
        }

        if (! columnExists('activity_log', 'attribute_changes')) {
            return;
        }

        DB::table('activity_log')
            ->whereNotNull('attribute_changes')
            ->eachById(function (object $row) {
                $properties = json_decode((string) $row->properties, true) ?: [];
                $changes = json_decode((string) $row->attribute_changes, true) ?: [];

                DB::table('activity_log')->where('id', $row->id)->update([
                    'properties' => json_encode(array_merge($properties, $changes)),
                ]);
            });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn('attribute_changes');
        });
    }
};
