<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class SelectionChoice extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'selection_choices';

    protected $fillable = [
        'selection_id', 'selection', 'selectionGroup', 'area', 'cadRef', 'clientNote', 'adminNote',
        'item', 'itemId', 'itemThumbnail', 'manufacturer', 'productCode',
        'relativePrice', 'schedulingRef', 'totalCost', 'totalQuantity'
    ];

    public function selection()
    {
        return $this->belongsTo(Selection::class, 'selection_id', '_id');
    }
}
