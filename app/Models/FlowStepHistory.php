<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class FlowStepHistory extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'flow_step_histories';

    protected $fillable = [
        'flow_step_id',
        'flow_id', 
        'version',
        'name',
        'expression',
        'description',
        'order',
        'active',
        'user_id',
        'tag_ids',
        'change_type',
        'change_description',
        'is_current',
        'previous_version_id',
        'snapshot_data'
    ];

    protected $casts = [
        'active' => 'boolean',
        'order' => 'integer',
        'flow_step_id' => 'string',
        'flow_id' => 'string',
        'user_id' => 'integer',
        'version' => 'integer',
        'tag_ids' => 'array',
        'is_current' => 'boolean',
        'snapshot_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relacionamento com o FlowStep atual
     */
    public function flowStep()
    {
        return $this->belongsTo(FlowStep::class, 'flow_step_id', '_id');
    }

    /**
     * Relacionamento com o Flow
     */
    public function flow()
    {
        return $this->belongsTo(Flow::class, 'flow_id', '_id');
    }

    /**
     * Método para obter tags como Collection
     */
    public function getTagsAttribute()
    {
        if (!$this->tag_ids || !is_array($this->tag_ids)) {
            return collect([]);
        }
        return FlowTag::whereIn('_id', $this->tag_ids)->get();
    }

    /**
     * Scope para buscar por FlowStep
     */
    public function scopeByFlowStep($query, $flowStepId)
    {
        return $query->where('flow_step_id', $flowStepId);
    }

    /**
     * Scope para buscar por Flow
     */
    public function scopeByFlow($query, $flowId)
    {
        return $query->where('flow_id', $flowId);
    }

    /**
     * Scope para buscar versões ordenadas
     */
    public function scopeOrderedVersions($query)
    {
        return $query->orderBy('version', 'desc');
    }

    /**
     * Scope para buscar apenas a versão atual
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Método estático para criar um snapshot
     */
    public static function createSnapshot(FlowStep $flowStep, $changeType = 'update', $changeDescription = null, $userId = null)
    {
        // Obter a última versão para este FlowStep
        $lastVersion = self::where('flow_step_id', $flowStep->_id)
            ->orderBy('version', 'desc')
            ->first();

        $newVersion = $lastVersion ? $lastVersion->version + 1 : 1;

        // Marcar versões anteriores como não atuais
        self::where('flow_step_id', $flowStep->_id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // Criar snapshot completo dos dados atuais
        $snapshotData = [
            'name' => $flowStep->name,
            'expression' => $flowStep->expression,
            'description' => $flowStep->description,
            'order' => $flowStep->order,
            'active' => $flowStep->active,
            'tag_ids' => $flowStep->tag_ids,
            'flow_id' => $flowStep->flow_id,
            'user_id' => $flowStep->user_id,
            'original_created_at' => $flowStep->created_at,
            'original_updated_at' => $flowStep->updated_at
        ];

        return self::create([
            'flow_step_id' => $flowStep->_id,
            'flow_id' => $flowStep->flow_id,
            'version' => $newVersion,
            'name' => $flowStep->name,
            'expression' => $flowStep->expression,
            'description' => $flowStep->description,
            'order' => $flowStep->order,
            'active' => $flowStep->active,
            'user_id' => $userId ?? $flowStep->user_id,
            'tag_ids' => $flowStep->tag_ids,
            'change_type' => $changeType,
            'change_description' => $changeDescription,
            'is_current' => true,
            'previous_version_id' => $lastVersion?->_id,
            'snapshot_data' => $snapshotData
        ]);
    }

    /**
     * Método para restaurar uma versão específica
     */
    public function restore()
    {
        $flowStep = $this->flowStep;
        
        if (!$flowStep) {
            throw new \Exception('FlowStep not found');
        }

        // Atualizar o FlowStep com os dados desta versão
        $flowStep->update([
            'name' => $this->name,
            'expression' => $this->expression,
            'description' => $this->description,
            'order' => $this->order,
            'active' => $this->active,
            'tag_ids' => $this->tag_ids
        ]);

        // Criar um novo snapshot marcando como restore
        self::createSnapshot(
            $flowStep->fresh(),
            'restore',
            "Restored to version {$this->version}",
            $this->user_id
        );

        return $flowStep;
    }

    /**
     * Comparar mudanças entre duas versões
     */
    public function compareWith($otherVersion)
    {
        $changes = [];
        $fields = ['name', 'expression', 'description', 'order', 'active', 'tag_ids'];

        foreach ($fields as $field) {
            $currentValue = $this->{$field};
            $otherValue = $otherVersion->{$field};
            
            // Comparação especial para arrays (tag_ids)
            if (is_array($currentValue) && is_array($otherValue)) {
                if (count($currentValue) !== count($otherValue) || 
                    array_diff($currentValue, $otherValue) || 
                    array_diff($otherValue, $currentValue)) {
                    $changes[$field] = [
                        'from' => $otherValue,
                        'to' => $currentValue,
                        'type' => 'array',
                        'from_display' => implode(', ', $otherValue ?? []),
                        'to_display' => implode(', ', $currentValue ?? [])
                    ];
                }
            }
            // Comparação para strings (incluindo expressões)
            elseif ($field === 'expression') {
                if (trim($currentValue ?? '') !== trim($otherValue ?? '')) {
                    $diff = $this->generateLineDiff($otherValue ?? '', $currentValue ?? '');
                    $changes[$field] = [
                        'from' => $otherValue,
                        'to' => $currentValue,
                        'type' => 'expression',
                        'from_lines' => $this->countLines($otherValue ?? ''),
                        'to_lines' => $this->countLines($currentValue ?? ''),
                        'from_chars' => strlen($otherValue ?? ''),
                        'to_chars' => strlen($currentValue ?? ''),
                        'diff' => $diff
                    ];
                }
            }
            // Comparação para outros campos
            else {
                if ($currentValue !== $otherValue) {
                    $changes[$field] = [
                        'from' => $otherValue,
                        'to' => $currentValue,
                        'type' => gettype($currentValue)
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Contar linhas em uma string
     */
    private function countLines($text)
    {
        if (empty($text)) return 0;
        return substr_count($text, "\n") + 1;
    }

    /**
     * Gerar diff linha por linha entre duas strings
     */
    private function generateLineDiff($oldText, $newText)
    {
        $oldLines = explode("\n", $oldText);
        $newLines = explode("\n", $newText);
        
        // Usar algoritmo LCS melhorado para diff mais preciso
        $lcs = $this->computeLCS($oldLines, $newLines);
        $diffLines = $this->buildDiffFromLCS($oldLines, $newLines, $lcs);
        
        return [
            'lines' => $diffLines,
            'stats' => [
                'added' => count(array_filter($diffLines, fn($line) => $line['type'] === 'added')),
                'removed' => count(array_filter($diffLines, fn($line) => $line['type'] === 'removed')),
                'unchanged' => count(array_filter($diffLines, fn($line) => $line['type'] === 'unchanged'))
            ]
        ];
    }
    
    /**
     * Computar Longest Common Subsequence (LCS)
     */
    private function computeLCS($oldLines, $newLines)
    {
        $oldCount = count($oldLines);
        $newCount = count($newLines);
        
        // Matriz para armazenar os comprimentos do LCS
        $lengths = array_fill(0, $oldCount + 1, array_fill(0, $newCount + 1, 0));
        
        // Construir a tabela LCS
        for ($i = 1; $i <= $oldCount; $i++) {
            for ($j = 1; $j <= $newCount; $j++) {
                if ($oldLines[$i - 1] === $newLines[$j - 1]) {
                    $lengths[$i][$j] = $lengths[$i - 1][$j - 1] + 1;
                } else {
                    $lengths[$i][$j] = max($lengths[$i - 1][$j], $lengths[$i][$j - 1]);
                }
            }
        }
        
        // Reconstruir o LCS
        $lcs = [];
        $i = $oldCount;
        $j = $newCount;
        
        while ($i > 0 && $j > 0) {
            if ($oldLines[$i - 1] === $newLines[$j - 1]) {
                $lcs[] = ['old' => $i - 1, 'new' => $j - 1];
                $i--;
                $j--;
            } elseif ($lengths[$i - 1][$j] > $lengths[$i][$j - 1]) {
                $i--;
            } else {
                $j--;
            }
        }
        
        return array_reverse($lcs);
    }
    
    /**
     * Construir diff a partir do LCS
     */
    private function buildDiffFromLCS($oldLines, $newLines, $lcs)
    {
        $diffLines = [];
        $oldIndex = 0;
        $newIndex = 0;
        $oldLineNum = 1;
        $newLineNum = 1;
        
        foreach ($lcs as $common) {
            // Adicionar linhas removidas antes da linha comum
            while ($oldIndex < $common['old']) {
                $diffLines[] = [
                    'type' => 'removed',
                    'line' => $oldLines[$oldIndex],
                    'oldLineNumber' => $oldLineNum,
                    'newLineNumber' => null
                ];
                $oldIndex++;
                $oldLineNum++;
            }
            
            // Adicionar linhas adicionadas antes da linha comum
            while ($newIndex < $common['new']) {
                $diffLines[] = [
                    'type' => 'added',
                    'line' => $newLines[$newIndex],
                    'oldLineNumber' => null,
                    'newLineNumber' => $newLineNum
                ];
                $newIndex++;
                $newLineNum++;
            }
            
            // Adicionar a linha comum
            $diffLines[] = [
                'type' => 'unchanged',
                'line' => $oldLines[$oldIndex],
                'oldLineNumber' => $oldLineNum,
                'newLineNumber' => $newLineNum
            ];
            
            $oldIndex++;
            $newIndex++;
            $oldLineNum++;
            $newLineNum++;
        }
        
        // Adicionar linhas removidas restantes
        while ($oldIndex < count($oldLines)) {
            $diffLines[] = [
                'type' => 'removed',
                'line' => $oldLines[$oldIndex],
                'oldLineNumber' => $oldLineNum,
                'newLineNumber' => null
            ];
            $oldIndex++;
            $oldLineNum++;
        }
        
        // Adicionar linhas adicionadas restantes
        while ($newIndex < count($newLines)) {
            $diffLines[] = [
                'type' => 'added',
                'line' => $newLines[$newIndex],
                'oldLineNumber' => null,
                'newLineNumber' => $newLineNum
            ];
            $newIndex++;
            $newLineNum++;
        }
        
        return $diffLines;
    }

    /**
     * Obter estatísticas de mudanças
     */
    public static function getChangeStats($flowStepId, $days = 30)
    {
        $startDate = now()->subDays($days);
        
        return self::where('flow_step_id', $flowStepId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('change_type, COUNT(*) as count')
            ->groupBy('change_type')
            ->get()
            ->pluck('count', 'change_type')
            ->toArray();
    }
}
