<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Skybluesofa\Microblog\Visibility;

/**
 * Class CreateStatusPostsTable
 */
class CreateMicroblogJournalsTable extends Migration
{
    public function up()
    {
        Schema::create(config('microblog.tables.microblog_journals'), function (Blueprint $table) {

            $table->uuid('id');
            $table->bigIncrements('user_id');
            $table->tinyInteger('visibility')->default(Visibility::UNIVERSAL);
            $table->timestamps();

            $table->index(['id'], 'microblog_journals_index');
            $table->index(['user_id'], 'microblog_journals_user_index');

            $table->foreign('user_id')
                ->references('id')
                ->on((new App\User)->getTable())
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('microblog.tables.microblog_journals'));
    }
}
