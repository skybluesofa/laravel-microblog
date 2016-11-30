<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateStatusPostsTable
 */
class CreateMicroblogPostsTable extends Migration
{

    public function up() {

        Schema::create(config('microblog.tables.microblog_posts'), function (Blueprint $table) {

            $table->uuid('id');
            $table->uuid('journal_id');
            $table->integer('user_id')->nullable();
            $table->string('title')->nullable();
            $table->longText('content');
            $table->integer('status')->default(1);
            $table->integer('visibility')->default(0);
            $table->timestamp('available_on')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();
        });

    }

    public function down() {
        Schema::dropIfExists(config('microblog.tables.microblog_posts'));
    }

}
