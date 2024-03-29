<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CastMemberResource;
use App\Models\CastMember;

class CastMemberController extends BasicCrudController
{
    private $rules;

    public function __construct()
    {
      $this->rules = [
        'name'=>'required|max:255',
        'type'=>'integer|in:'. implode(',',[CastMember::TYPE_DIRECTOR, CastMember::TYPE_ACTOR])
      ];
    }

    protected function relatedTables() : array
    {
      return [];
    }

    protected function model()
    {
      return CastMember::class;
    }

    protected function ruleStore()
    {
      return $this->rules;
    }

    protected function ruleUpdate()
    {
      return $this->rules;
    }

    protected function resourceCollection()
    {
      return $this->resource();
    }

    protected function resource()
    {
      return CastMemberResource::class;
    }
}
