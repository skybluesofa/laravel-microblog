<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Skybluesofa\Microblog\Visibility;

/**
 * Class CreateMicroblogImagesTable
 */
class CreateMicroblogImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('microblog.tables.microblog_images'), function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('journal_id');
            $table->integer('user_id')->nullable();
            $table->string('image');
            $table->string('area_of_interest')->nullable();
            $table->tinyInteger('visibility')->default(Visibility::UNIVERSAL);
            $table->timestamps();
        
            $table->index(['id'], 'microblog_images_index');
            $table->index(['journal_id'], 'microblog_images_journal_index');

            $table->foreign('journal_id')
                ->references('id')
                ->on(config('microblog.tables.microblog_journals'))
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('microblog.tables.microblog_images'));
    }
}