<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Video;
use Faker\Generator as Faker;
use phpDocumentor\Reflection\Types\Boolean;

$factory->define(Video::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(3),
        'description' => $faker->sentence(10),
        'year_launched' => rand(1895, 2021),
        'opened' => rand(0,1),
        'rating' => Video::RATING_LIST[array_rand(Video::RATING_LIST)],
        'duration' => rand(1,30),
        'thumb_file' => 'thumb.png',
        'banner_file' => 'banner.jpg',
        'trailer_file' => 'trailer.mp4',
        'video_file' => 'video.mp4',
        // 'published' => rand(0,1)
    ];
});
