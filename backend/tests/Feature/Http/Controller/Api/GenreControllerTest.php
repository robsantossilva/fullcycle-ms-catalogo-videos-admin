<?php

namespace Tests\Feature\Http\Controller\Api;

use App\Http\Controllers\Api\GenreController;
use App\Http\Resources\GenreResource;
use App\Models\Category;
use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Tests\Exceptions\TestException;
use Tests\TestCase;
use Tests\Traits\TestResources;
use Tests\Traits\TestSaves;
use Tests\Traits\TestValidations;

class GenreControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidations, TestSaves, TestResources;

    private $genre;
    private $category;
    private $serializedFields = [
        'id',
        'name',
        'description',
        'is_active',
        'created_at',
        'updated_at',
        'deleted_at',
        'categories' => [
            '*' => [
                "id",
                "name",
                "description",
                "is_active",
                "deleted_at",
                "created_at",
                "updated_at"
            ]
        ]
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = factory(Category::class)->create();

        $this->genre = factory(Genre::class)->create();
        $this->genre->categories()->sync([$this->category->id]);
        $this->genre->load(array_keys(Genre::RELATED_TABLES))->refresh();
    }

    public function testIndex()
    {
        $response = $this->get(route('genres.index'));
        $response
            ->assertStatus(200)
            ->assertJson([
                'meta' => ['per_page' => 15]
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => $this->serializedFields
                ],
                'links' => [],
                'meta' => []
            ])
            ->assertJson(['data' => [$this->genre->toArray()]]);

        $resource = GenreResource::collection(collect([$this->genre]));
        $this->assertResource($response, $resource);
    }

    public function testRollbackStore()
    {

        $controller = \Mockery::mock(GenreController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller
            ->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn([
                'name' => 'test'
            ]);

        $controller
            ->shouldReceive('ruleStore')
            ->withAnyArgs()
            ->andReturn([]);

        $request = \Mockery::mock(Request::class);

        $controller->shouldReceive('handleRelations')
            ->once()
            ->andThrow(new TestException());

        $hasError = false;
        try {
            $controller->store($request);
        } catch (TestException $exception) {
            $this->assertCount(1, Genre::all());
            $hasError = true;
        }
        $this->assertTrue($hasError);
    }

    public function testRollbackUpdate()
    {

        /** @var GenreController $controller */
        $controller = \Mockery::mock(GenreController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller
            ->shouldReceive('findOrFail')
            ->withAnyArgs()
            ->andReturn($this->genre);

        $controller
            ->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn([
                'name' => 'test'
            ]);

        $controller
            ->shouldReceive('ruleUpdate')
            ->withAnyArgs()
            ->andReturn([]);

        /** @var Request $request */
        $request = \Mockery::mock(Request::class);
        $request->shouldReceive('isMethod')
            ->once()
            ->andReturn(true);

        $controller->shouldReceive('handleRelations')
            ->once()
            ->andThrow(new TestException());

        $hasError = false;
        try {
            $controller->update($request, $this->genre->id);
        } catch (TestException $exception) {
            $this->assertCount(1, Genre::all());
            $hasError = true;
        }
        $this->assertTrue($hasError);
    }

    public function testShow()
    {
        $response = $this->get(route('genres.show', ['genre' => $this->genre->id]));

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => $this->serializedFields
            ])
            ->assertJson(['data' => $this->genre->toArray()]);

        //Expected resource
        $resource = new GenreResource(Genre::find($response->json('data.id')));
        $this->assertResource($response, $resource);
    }

    public function testInvalidationData()
    {

        // //////////////////////////////////////////////
        $data = ['name' => ''];
        $this->assertInvalidationInStoreAction($data, 'validation.required');
        $this->assertInvalidationInUpdateAction($data, 'validation.required');
        // //////////////////////////////////////////////
        $data = ['name' => str_repeat('a', 256)];
        $this->assertInvalidationInStoreAction($data, 'validation.max.string', ['max' => 255]);
        $this->assertInvalidationInUpdateAction($data, 'validation.max.string', ['max' => 255]);
        //////////////////////////////////////////////////////
        $data = ['is_active' => 'a'];
        $this->assertInvalidationInStoreAction($data, 'validation.boolean');
        $this->assertInvalidationInUpdateAction($data, 'validation.boolean');
        //////////////////////////////////////////////////////
    }

    public function testInvalidationCategoriesIdField()
    {
        $data = [
            'categories_id' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'validation.array');
        $this->assertInvalidationInUpdateAction($data, 'validation.array');
        $data = [
            'categories_id' => [123]
        ];
        $this->assertInvalidationInStoreAction($data, 'validation.exists');
        $this->assertInvalidationInUpdateAction($data, 'validation.exists');

        $category = factory(Category::class)->create();
        $category->delete();
        $data = [
            'categories_id' => [$category->id]
        ];
        $this->assertInvalidationInStoreAction($data, 'validation.exists');
        $this->assertInvalidationInUpdateAction($data, 'validation.exists');
    }

    public function testStore()
    {
        $data = [
            'name' => 'test'
        ];
        $response = $this->assertStore(
            $data + ['categories_id' => [$this->category->id]],
            $data + ['description' => null, 'is_active' => true, 'deleted_at' => null]
        );
        $response->assertJsonStructure([
            'data' => $this->serializedFields
        ]);
        //////////////////////////////////////////////qq
        $data = [
            'name' => 'test',
            'description' => 'description',
            'is_active' => false
        ];

        $response = $this->assertStore(
            $data + ['categories_id' => [$this->category->id]],
            $data + ['description' => 'description', 'is_active' => false]
        );

        //Expected resource
        $resource = new GenreResource(Genre::find($response->json('data.id')));
        $this->assertResource($response, $resource);

        $this->assertGenreHasCategory($response);
    }

    public function testUpdate()
    {

        $this->genre = factory(Genre::class)->create([
            'description' => 'description',
            'is_active' => false
        ]);
        $data = [
            'name' => 'test',
            'description' => 'test',
            'is_active' => true
        ];
        $response = $this->assertUpdate(
            $data + ['categories_id' => [$this->category->id]],
            $data + ['deleted_at' => null]
        );
        $response->assertJsonStructure([
            'data' => $this->serializedFields
        ]);

        $data = [
            'name' => 'test',
            'description' => ''
        ];
        $this->assertUpdate(
            $data + ['categories_id' => [$this->category->id]],
            array_merge($data, ['description' => null])
        );

        $data['description'] = 'test';
        $this->assertUpdate(
            $data + ['categories_id' => [$this->category->id]],
            array_merge($data, ['description' => 'test'])
        );

        $data['description'] = null;
        $this->assertUpdate(
            $data + ['categories_id' => [$this->category->id]],
            array_merge($data, ['description' => null])
        );

        #####################################################
        $this->category = factory(Category::class)->create();
        $data = [
            'name' => 'test',
            'description' => 'test',
            'is_active' => true
        ];
        $response = $this->assertUpdate(
            $data + ['categories_id' => [$this->category->id]],
            $data
        );

        //Expected resource
        $resource = new GenreResource(Genre::find($response->json('data.id')));
        $this->assertResource($response, $resource);

        $this->assertGenreHasCategory($response);
    }

    protected function assertGenreHasCategory($response)
    {
        $response = json_decode($response->baseResponse->content());
        $hasCategory = false;
        foreach ($response->data->categories as $category) {
            $hasCategory = $category->id == $this->category->id ? true : false;
        }
        $this->assertTrue($hasCategory);
        $this->assertDatabaseHas('category_genre', [
            'genre_id' => $response->data->id,
            'category_id' => $this->category->id
        ]);
    }

    public function testSyncCategories()
    {
        $categoriesId = factory(Category::class, 3)->create()->pluck('id')->toArray();

        $sendData = [
            'name' => 'test',
            'categories_id' => [$categoriesId[0]]
        ];
        $response = $this->json('POST', $this->routeStore(), $sendData);
        $this->assertDatabaseHas('category_genre', [
            'category_id' => $categoriesId[0],
            'genre_id' => $response->json('data.id')
        ]);

        $sendData = [
            'name' => 'test',
            'categories_id' => [$categoriesId[1], $categoriesId[2]]
        ];
        $response = $this->json('PUT', route('genres.update', ['genre' => $response->json('data.id')]), $sendData);
        $this->assertDatabaseMissing('category_genre', [
            'category_id' => $categoriesId[0],
            'genre_id' => $response->json('data.id')
        ]);
        $this->assertDatabaseHas('category_genre', [
            'category_id' => $categoriesId[1],
            'genre_id' => $response->json('data.id')
        ]);
        $this->assertDatabaseHas('category_genre', [
            'category_id' => $categoriesId[2],
            'genre_id' => $response->json('data.id')
        ]);
    }

    public function testDestroy()
    {
        //checking if there is
        $response = $this->get(route('genres.show', ['genre' => $this->genre->id]));
        $response
            ->assertStatus(200)
            ->assertJson(['data' => $this->genre->toArray()]);

        //destroying
        $response = $this->json('DELETE', route('genres.destroy', ['genre' => $this->genre->id]), []);
        $response->assertStatus(204);

        //checking if it was destroyed
        $response = $this->get(route('genres.show', ['genre' => $this->genre->id]));
        $response
            ->assertStatus(404);

        $this->assertNull(Genre::find($this->genre->id));
        $this->assertNotNull(Genre::withTrashed()->find($this->genre->id));
    }

    protected function routeStore()
    {
        return route('genres.store');
    }

    protected function routeUpdate()
    {
        return route('genres.update', ['genre' => $this->genre->id]);
    }

    protected function model()
    {
        return Genre::class;
    }
}
