<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateMicroblogPostsTable
 */
class CreateMicroblogPostImagesTable extends Migration
{
    public function up()
    {
        Schema::create(config('microblog.tables.microblog_post_images'), function (Blueprint $table) {
            $table->uuid('image_id');
            $table->uuid('post_id');
            $table->timestamps();

            $table->index(['image_id'], 'microblog_image_index');
            $table->index(['post_id'], 'microblog_image_post_index');

            $table->foreign('image_id')
                ->references('id')
                ->on(config('microblog.tables.microblog_images'))
                ->onDelete('cascade');

            $table->foreign('post_id')
                ->references('id')
                ->on(config('microblog.tables.microblog_posts'))
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('microblog.tables.microblog_post_images'));
    }
}
