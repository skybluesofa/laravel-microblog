<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => Illuminate\Support\Str::random(10),
    ];
});

$factory->define(Skybluesofa\Microblog\Model\Post::class, function (Faker\Generator $faker) {

    return [
        'title' => $faker->realText(50),
        'content' => $faker->realText(750),
    ];
});

$factory->define(Skybluesofa\Microblog\Model\Image::class, function (Faker\Generator $faker) {

    return [
        'image' => $faker->iban() . '.jpg',
    ];
});
