<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index the `hls_video_id` foreign keys.
 *
 * PostgreSQL does not create an index for a foreign key constraint, so every
 * `$video->qualities` load and every cascading delete was a sequential scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hls_video_qualities', function (Blueprint $table): void {
            $table->index('hls_video_id', 'hls_video_qualities_hls_video_id_index');
        });

        Schema::table('hls_videoables', function (Blueprint $table): void {
            $table->index('hls_video_id', 'hls_videoables_hls_video_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('hls_video_qualities', function (Blueprint $table): void {
            $table->dropIndex('hls_video_qualities_hls_video_id_index');
        });

        Schema::table('hls_videoables', function (Blueprint $table): void {
            $table->dropIndex('hls_videoables_hls_video_id_index');
        });
    }
};
