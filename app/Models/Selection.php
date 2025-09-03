<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Selection extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'selections';

    protected $fillable = [
        'jobNo', 'client', 'houseType', 'specification', 'lotAddress', 'elevation', 'choices_count'
    ];

    public function choices()
    {
        return $this->hasMany(SelectionChoice::class, 'selection_id');
    }
}

