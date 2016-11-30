<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateStatusPostsTable
 */
class CreateMicroblogJournalsTable extends Migration
{

    public function up() {

        Schema::create(config('microblog.tables.microblog_journals'), function (Blueprint $table) {

            $table->uuid('id');
            $table->integer('user_id');
            $table->integer('visibility')->default(2);
            $table->timestamps();
        });

    }

    public function down() {
        Schema::dropIfExists(config('microblog.tables.microblog_journals'));
    }

}
