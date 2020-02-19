<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Skybluesofa\Microblog\Status;
use Skybluesofa\Microblog\Visibility;

/**
 * Class CreateStatusPostsTable
 */
class CreateMicroblogPostsTable extends Migration
{
    public function up()
    {
        Schema::create(config('microblog.tables.microblog_posts'), function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('journal_id');
            $table->integer('user_id')->nullable();
            $table->string('title')->nullable();
            $table->longText('content');
            $table->integer('status')->default(Status::PUBLISHED);
            $table->integer('visibility')->default(Visibility::PERSONAL);
            $table->timestamp('available_on')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();

            $table->index(['id'], 'microblog_posts_index');
            $table->index(['journal_id'], 'microblog_posts_journal_index');

            $table->foreign('journal_id')
                ->references('id')
                ->on(config('microblog.tables.microblog_journals'))
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('microblog.tables.microblog_posts'));
    }
}
