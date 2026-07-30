<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hls_video_qualities', function (Blueprint $table) {
            $table->longText('playlist_content')->nullable()->after('convert_data');
            $table->unsignedInteger('ts_files_count')->nullable()->after('playlist_content');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hls_video_qualities', function (Blueprint $table) {
            $table->dropColumn(['playlist_content', 'ts_files_count']);
        });
    }
};
