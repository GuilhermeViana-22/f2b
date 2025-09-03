<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class FlowStep extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'flow_steps';

    protected $fillable = [
        'name', 'expression', 'description', 'order', 'active', 'flow_id', 'user_id', 'tag_ids'
    ];

    protected $casts = [
        'active' => 'boolean',
        'order' => 'integer',
        'flow_id' => 'string',
        'user_id' => 'integer',
        'tag_ids' => 'array'
    ];

    public function flow()
    {
        return $this->belongsTo(Flow::class, 'flow_id', '_id');
    }

    // Método para obter tags como Collection
    public function getTagsAttribute()
    {
        if (!$this->tag_ids || !is_array($this->tag_ids)) {
            return collect([]);
        }
        return FlowTag::whereIn('_id', $this->tag_ids)->get();
    }
    // Método para adicionar uma tag
    public function addTag($tagId)
    {
        $tagIds = $this->tag_ids ?? [];
        if (!in_array($tagId, $tagIds)) {
            $tagIds[] = $tagId;
            $this->tag_ids = $tagIds;
            $this->save();
        }
    }

    // Método para remover uma tag
    public function removeTag($tagId)
    {
        $tagIds = $this->tag_ids ?? [];
        $tagIds = array_filter($tagIds, function($id) use ($tagId) {
            return $id !== $tagId;
        });
        $this->tag_ids = array_values($tagIds);
        $this->save();
    }

    // Método para sincronizar tags
    public function syncTags(array $tagIds)
    {
        $this->tag_ids = $tagIds;
        $this->save();
    }
}
