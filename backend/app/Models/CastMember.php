<?php

namespace App\Models;

use App\ModelFilters\CastMemberFilter;
use App\Models\Traits\SerializeDateToISO8601;
use App\Models\Traits\Uuid;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CastMember extends Model
{
    use SoftDeletes, Uuid, SerializeDateToISO8601, Filterable;

    const TYPE_DIRECTOR = 1;
    const TYPE_ACTOR = 2;

    public static $types = [
        CastMember::TYPE_DIRECTOR,
        CastMember::TYPE_ACTOR,
    ];

    protected $fillable = ['name', 'type'];
    protected   $dates = ['deleted_at'];
    public      $incrementing = false;
    protected   $keyType = 'string';
    protected   $casts = [
        'id'=>'string',
        'name'=>'string',
        'type'=>'integer'
    ];

    public function modelFilter()
    {
        return $this->provideFilter(CastMemberFilter::class);
    }
}
