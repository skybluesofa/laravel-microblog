<?php
namespace Skybluesofa\Microblog\Model\Scope;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class OrderScope implements Scope
{
    private $column;
    private $direction;

    public function __construct($column, $direction = 'asc')
    {
        $this->column = $column;
        $this->direction = $direction;
    }

    public function apply(Builder $builder, Model $model)
    {
        $builder->orderBy($this->column, $this->direction);
    }
}
