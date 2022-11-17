<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Skybluesofa\Microblog\Enums\Visibility;

class ChangeDefaultVisibilityOnImageTable extends Migration
{
    public function up()
    {
        if (config('database.default') === 'testbench') {
            return;
        }

        DB::statement('ALTER TABLE '.config('microblog.tables.microblog_images').' CHANGE visibility visibility TINYINT UNSIGNED NOT NULL DEFAULT '.Visibility::PERSONAL.';');
    }

    public function down()
    {
        if (config('database.default') === 'testbench') {
            return;
        }

        DB::statement('ALTER TABLE '.config('microblog.tables.microblog_images')." CHANGE visibility visibility TINYINT UNSIGNED NOT NULL DEFAULT '".Visibility::UNIVERSAL."'");
    }
}
