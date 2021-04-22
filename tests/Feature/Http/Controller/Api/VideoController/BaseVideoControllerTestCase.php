<?php 
namespace Tests\Feature\Http\Controller\Api\VideoController;

use App\Models\Category;
use App\Models\Genre;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

abstract class BaseVideoControllerTestCase extends TestCase
{
    use DatabaseMigrations;

    protected $video;
    protected $sendData;
    protected $testDatabase;
    protected $category;
    protected $genre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = factory(Category::class)->create();
        $this->genre = factory(Genre::class)->create();
        $this->genre->categories()->sync([$this->category->id]);
        $this->genre->load(array_keys(Genre::RELATED_TABLES))->refresh();

        $this->video = factory(Video::class)->create(['opened'=>false]);
        $this->video->categories()->sync([$this->category->id]);
        $this->video->genres()->sync([$this->genre->id]);
        $this->video->load(array_keys(Video::RELATED_TABLES))->refresh();

        $this->sendData = [
            'title'=>'title',
            'description' => 'description',
            'year_launched' => 2013,
            'rating'=>Video::RATING_LIST[0],
            'duration' => 90,
            'categories_id' => [$this->category->id],
            'genres_id' => [$this->genre->id]
        ];

        $this->testDatabase = $this->sendData;
        unset($this->testDatabase['categories_id']);
        unset($this->testDatabase['genres_id']);

    }
}