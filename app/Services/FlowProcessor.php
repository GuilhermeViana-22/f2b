<?php

// FlowProcessor.php - Flow Expression Processing Library
// Migrado do expression_lib.php original

$flowLog = '';
$compactLog = false;
$inForeach = false;

if (!function_exists('logMessage')) {
    function logMessage($msg) {
        global $flowLog;
        $flowLog .= $msg . "\n";
    }
}

function formatForLog($val) 
{
    if (is_numeric($val)) {
        $num = round((float)$val, 4);
        return rtrim(rtrim(sprintf('%.4f', $num), '0'), '.');
    }
    return json_encode($val);
}

function processActionLine($line, $selections, &$locals, &$globals, $quantities = [], &$xmlCtx = []) 
{
    $actions = [];
    if (preg_match('/^([@$][a-zA-Z_][\w]*)\\s*=\\s*(.+)$/', $line, $ma)) {
        $varName = $ma[1];
        $valExpr = $ma[2];
        $val = evaluateExpression($valExpr, $selections, $locals, $globals, $quantities, $xmlCtx);
        if ($varName[0] === '@') {
            $globals[substr($varName, 1)] = $val;
        } else {
            $locals[substr($varName, 1)] = $val;
        }
        logMessage('Variable: ' . $varName . ' = ' . formatForLog($val));
    } elseif (preg_match('/^([A-Z]+)\\s*\\((.*)\\)$/i', $line, $ma)) {
        $type = strtoupper($ma[1]);
        $rawParams = $ma[2];
        $parts = splitActionParams($rawParams);
        $params = [];
        foreach ($parts as $p) {
            $p = trim($p);
            $params[] = evaluateExpression($p, $selections, $locals, $globals, $quantities, $xmlCtx);
        }
        if ($type === 'EXPORT') {
            $rec = [
                'ItemCode' => $params[0] ?? '',
                'Quantity' => $params[1] ?? '',
                'CostCentre' => $params[2] ?? '',
                'Bload' => $params[3] ?? '1',
                'PriceLevel' => $params[4] ?? '1',
                'XDescription' => $params[5] ?? ''
            ];
            $actions[] = $rec;
            logMessage('Exported: ' . json_encode($rec['ItemCode']));
        }
    }
    return $actions;
}

function normalizeSwitchSyntax($content) 
{
    $content = str_replace(["\r", "\n"], ',', $content);
    $content = preg_replace('/\\bCASE\\b\\s*/i', '', $content);
    $content = preg_replace('/\\s*,\\s*/', ',', $content);
    $content = preg_replace('/,+/', ',', $content);
    return trim($content, ', ');
}

function splitSwitchParts($content) 
{
    $parts = [];
    $buffer = '';
    $depth = 0;
    $inQuote = false;
    $len = strlen($content);
    for ($i = 0; $i < $len; $i++) {
        $ch = $content[$i];
        if ($ch === '"' && ($i === 0 || $content[$i-1] !== '\\')) {
            $inQuote = !$inQuote;
        }
        if (!$inQuote) {
            if ($ch === '(') $depth++;
            elseif ($ch === ')') $depth--;
            elseif ($ch === ',' && $depth === 0) {
                $parts[] = trim($buffer);
                $buffer = '';
                continue;
            }
        }
        $buffer .= $ch;
    }
    if (trim($buffer) !== '') {
        $parts[] = trim($buffer);
    }
    return $parts;
}

function splitActionParams($content) 
{
    $parts = [];
    $buffer = '';
    $depth = 0;
    $inQuote = false;
    $len = strlen($content);
    for ($i = 0; $i < $len; $i++) {
        $ch = $content[$i];
        if ($ch === '"' && ($i === 0 || $content[$i-1] !== '\\')) {
            $inQuote = !$inQuote;
        }
        if (!$inQuote) {
            if ($ch === '(') $depth++;
            elseif ($ch === ')') $depth--;
            elseif ($ch === ',' && $depth === 0) {
                $parts[] = trim($buffer);
                $buffer = '';
                continue;
            }
        }
        $buffer .= $ch;
    }
    if (trim($buffer) !== '') {
        $parts[] = trim($buffer);
    }
    return $parts;
}

function convertSwitchSyntax($expr) 
{
    $offset = 0;
    while (preg_match('/\\bswitch\\s*\\(/i', $expr, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $start = $m[0][1];
        $pos = $start + strlen($m[0][0]);
        $depth = 1;
        while ($depth > 0 && $pos < strlen($expr)) {
            $ch = $expr[$pos];
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
            }
            $pos++;
        }
        if ($depth !== 0) {
            break;
        }
        $inner = substr($expr, $start + strlen($m[0][0]), $pos - $start - strlen($m[0][0]) - 1);
        $inner = convertSwitchSyntax($inner);
        $inner = normalizeSwitchSyntax($inner);
        $expr = substr($expr, 0, $start) . 'switch_case(' . $inner . ')' . substr($expr, $pos);
        $offset = $start + strlen('switch_case(' . $inner . ')');
    }
    return $expr;
}

function convertCharSyntax($expr) 
{
    $expr = preg_replace('/\\bvbcrlf\\b/i', "\n", $expr);
    $expr = preg_replace_callback('/\\bchar\\s*\\(\\s*(\\d+)\\s*\\)/i', function($m){
        return 'chr(' . intval($m[1]) . ')';
    }, $expr);
    return $expr;
}

function convertRoundSyntax($expr) 
{
    $replacements = [
        '/\\bROUND\\s*\\(/i' => 'round_log(',
        '/\\bROUNDDOWN\\s*\\(/i' => 'rounddown_log(',
        '/\\bROUNDUP\\s*\\(/i' => 'roundup_log(',
        '/\\bCEILING\\s*\\(/i' => 'ceiling_log(',
        '/\\bFLOOR\\s*\\(/i' => 'floor_log(',
        '/\\bREMAINDER\\s*\\(/i' => 'remainder_log(',
    ];
    foreach ($replacements as $pat => $rep) {
        $expr = preg_replace($pat, $rep, $expr);
    }
    return $expr;
}

function convertLogicalOperators($expr) 
{
    return preg_replace_callback('/"(?:\\\\.|[^"\\\\])*"|\\b(?:and|or)\\b/i', function ($m) {
        if ($m[0][0] === '"') {
            return $m[0];
        }
        return strtolower($m[0]) === 'and' ? '&&' : '||';
    }, $expr);
}

function processSwitchStatement($content, $selections, &$locals, &$globals, $quantities = [], &$xmlCtx = []) 
{
    $content = normalizeSwitchSyntax($content);
    $parts = splitSwitchParts($content);
    if (count($parts) < 3) return [];
    $valueExpr = trim(array_shift($parts));
    $value = evaluateExpression($valueExpr, $selections, $locals, $globals, $quantities, $xmlCtx);
    $defaultAction = trim(array_pop($parts));
    for ($i = 0; $i < count($parts); $i += 2) {
        if (!isset($parts[$i + 1])) break;
        $caseVal = evaluateExpression(trim($parts[$i]), $selections, $locals, $globals, $quantities, $xmlCtx);
        if ($value == $caseVal) {
            logMessage('Switch Case: ' . json_encode($caseVal));
            return processExpressionFlow(trim($parts[$i + 1]), $selections, $globals, $locals, $quantities, $xmlCtx);
        }
    }
    logMessage('Switch Case: ' . json_encode($value) . ' default');
    return processExpressionFlow($defaultAction, $selections, $globals, $locals, $quantities, $xmlCtx);
}

function splitActionLines($acts) 
{
    $rawLines = preg_split('/\\r?\\n/', $acts);
    $lines = [];
    $buffer = '';
    $parenDepth = 0;
    $inForeach = false;
    $ifDepth = 0;
    foreach ($rawLines as $ln) {
        $trim = trim($ln);
        if ($trim === '') {
            continue;
        }
        if (!$inForeach && stripos($trim, 'FOREACH ') === 0) {
            $inForeach = true;
            $buffer = $trim;
            if (stripos($trim, 'NEXT') !== false) {
                $lines[] = $buffer;
                $buffer = '';
                $inForeach = false;
            }
            continue;
        }
        if ($inForeach) {
            $buffer .= ($buffer === '' ? '' : "\n") . $trim;
            if (stripos($trim, 'NEXT') !== false) {
                $lines[] = $buffer;
                $buffer = '';
                $inForeach = false;
            }
            continue;
        }
        if ($ifDepth === 0 && preg_match('/^IF\\b.*\\bTHEN\\b/i', $trim)) {
            $ifDepth = 1;
            $buffer = $trim;
            if (stripos($trim, 'ENDIF') !== false) {
                $ifDepth--;
                $lines[] = $buffer;
                $buffer = '';
            }
            continue;
        }
        if ($ifDepth > 0) {
            if (preg_match('/^IF\\b.*\\bTHEN\\b/i', $trim)) {
                $ifDepth++;
            }
            $buffer .= ($buffer === '' ? '' : "\n") . $trim;
            if (stripos($trim, 'ENDIF') !== false) {
                $ifDepth--;
                if ($ifDepth === 0) {
                    $lines[] = $buffer;
                    $buffer = '';
                }
            }
            continue;
        }
        $buffer .= ($buffer === '' ? '' : "\n") . $trim;
        $parenDepth += substr_count($ln, '(') - substr_count($ln, ')');
        if ($parenDepth <= 0) {
            $lines[] = $buffer;
            $buffer = '';
        }
    }
    if ($buffer !== '') {
        $lines[] = $buffer;
    }
    return $lines;
}

function splitIfSections($acts) 
{
    $lines = preg_split('/\\r?\\n/', $acts);
    $sections = ['then' => '', 'elseifs' => [], 'else' => ''];
    $buffer = '';
    $type = 'then';
    $cond = '';
    $depth = 0;
    foreach ($lines as $ln) {
        $trim = trim($ln);
        if (preg_match('/^IF\\b.*\\bTHEN\\b/i', $trim)) {
            $depth++;
        }
        if ($depth === 0) {
            if (preg_match('/^ELSEIF\\s+(.*?)\\s+THEN\\b\\s*(.*)$/i', $trim, $m)) {
                if ($type === 'then') {
                    $sections['then'] = trim($buffer);
                } elseif ($type === 'elseif') {
                    $sections['elseifs'][] = [$cond, trim($buffer)];
                } elseif ($type === 'else') {
                    $sections['else'] = trim($buffer);
                }
                $type = 'elseif';
                $cond = $m[1];
                $buffer = trim($m[2]);
                continue;
            } elseif (preg_match('/^ELSE\\b\\s*(.*)$/i', $trim, $m)) {
                if ($type === 'then') {
                    $sections['then'] = trim($buffer);
                } elseif ($type === 'elseif') {
                    $sections['elseifs'][] = [$cond, trim($buffer)];
                }
                $type = 'else';
                $cond = '';
                $buffer = trim($m[1]);
                continue;
            } elseif (preg_match('/^ENDIF\\b/i', $trim)) {
                if ($type === 'then') {
                    $sections['then'] = trim($buffer);
                } elseif ($type === 'elseif') {
                    $sections['elseifs'][] = [$cond, trim($buffer)];
                } elseif ($type === 'else') {
                    $sections['else'] = trim($buffer);
                }
                return $sections;
            }
        }
        $buffer .= ($buffer === '' ? '' : "\n") . $trim;
        if ($depth > 0 && preg_match('/^ENDIF\\b/i', $trim)) {
            $depth--;
        }
    }
    if ($type === 'then') {
        $sections['then'] = trim($buffer);
    } elseif ($type === 'elseif') {
        $sections['elseifs'][] = [$cond, trim($buffer)];
    } elseif ($type === 'else') {
        $sections['else'] = trim($buffer);
    }
    return $sections;
}

function executeActionBlock($acts, $selections, &$globals, &$locals, $quantities = [], &$xmlCtx = []) 
{
    $actions = [];
    $lines = splitActionLines($acts);
    foreach ($lines as $line) {
        if (stripos($line, 'switch(') === 0 && substr_count($line, '(') === substr_count($line, ')')) {
            $inner = trim(substr($line, strpos($line, '(') + 1));
            $inner = substr($inner, 0, strrpos($inner, ')'));
            $actions = array_merge($actions, processSwitchStatement($inner, $selections, $locals, $globals, $quantities, $xmlCtx));
            continue;
        }
        if (stripos($line, 'ELSE') === 0) {
            continue;
        }
        $actions = array_merge($actions, processExpressionFlow($line, $selections, $globals, $locals, $quantities, $xmlCtx));
    }
    return $actions;
}

function processExpressionFlow($expr, $selections, &$globals, &$locals = [], $quantities = [], &$xmlCtx = []) 
{
    global $inForeach;
    $actions = [];
    if (preg_match('/^\\s*FOREACH\\s+(.+?)\\s+IN\\s+(.+?)\\s*,\\s*(.*?)\\s*NEXT\\s*$/is', $expr, $fm)) {
        $itemPath = trim($fm[1]);
        $itemPath = trim($itemPath, " \t\n\r%$");
        $listPath = trim($fm[2]);
        $listPath = trim($listPath, " \t\n\r%$");
        $inner = trim($fm[3]);
        $list = getLoopValue($quantities, $selections, [], $listPath);
        if (is_array($list)) {
            $totalCount = count($list);
            logMessage('FOREACH start ' . $itemPath . ' total ' . $totalCount);
            $idx = 0;
            foreach ($list as $record) {
                $idx++;
                $xmlCtx[$itemPath] = $record;
                $inForeach = true;
                logMessage('FOREACH iteration ' . $idx . ' of ' . $totalCount);
                if (preg_match('/^\\s*IF\\s+(.*?)\\s+THEN\\s+(.*)\\bENDIF\\s*$/is', $inner, $im)) {
                    $cond = trim($im[1]);
                    $body = trim($im[2]);
                    $sections = splitIfSections($body);
                    $chosen = '';
                    if (evaluateExpressionCondition($cond, $selections, $locals, $globals, $quantities, $xmlCtx)) {
                        $chosen = $sections['then'];
                    } else {
                        foreach ($sections['elseifs'] as $ei) {
                            if (evaluateExpressionCondition($ei[0], $selections, $locals, $globals, $quantities, $xmlCtx)) {
                                $chosen = $ei[1];
                                break;
                            }
                        }
                        if ($chosen === '') {
                            $chosen = $sections['else'];
                        }
                    }
                    if ($chosen !== '') {
                        logMessage('FOREACH iteration ' . $idx . ' of ' . $totalCount);
                        if (isset($record['ItemCode'])) {
                            logMessage('FOREACH match XML key %%' . $itemPath . '/ItemCode%% resolved to ' . json_encode($record['ItemCode']));
                        } else {
                            logMessage('FOREACH match');
                        }
                        $actions = array_merge($actions, executeActionBlock($chosen, $selections, $globals, $locals, $quantities, $xmlCtx));
                    }
                } else {
                    $innerActs = processExpressionFlow($inner, $selections, $globals, $locals, $quantities, $xmlCtx);
                    if (count($innerActs) > 0) {
                        logMessage('FOREACH iteration ' . $idx . ' of ' . $totalCount);
                        if (isset($record['ItemCode'])) {
                            logMessage('FOREACH match XML key %%' . $itemPath . '/ItemCode%% resolved to ' . json_encode($record['ItemCode']));
                        } else {
                            logMessage('FOREACH match');
                        }
                        $actions = array_merge($actions, $innerActs);
                    }
                }
                unset($xmlCtx[$itemPath]);
                $inForeach = false;
            }
            logMessage('FOREACH end');
        }
    } else if (preg_match('/^\\s*IF\\s+(.*?)\\s+THEN\\s+(.*)\\bENDIF\\s*$/is', $expr, $im)) {
        $cond = trim($im[1]);
        $body = trim($im[2]);
        $sections = splitIfSections($body);
        $chosen = '';
        if (evaluateExpressionCondition($cond, $selections, $locals, $globals, $quantities, $xmlCtx)) {
            $chosen = $sections['then'];
        } else {
            foreach ($sections['elseifs'] as $ei) {
                if (evaluateExpressionCondition($ei[0], $selections, $locals, $globals, $quantities, $xmlCtx)) {
                    $chosen = $ei[1];
                    break;
                }
            }
            if ($chosen === '') {
                $chosen = $sections['else'];
            }
        }
        if ($chosen !== '') {
            $actions = array_merge($actions, executeActionBlock($chosen, $selections, $globals, $locals, $quantities, $xmlCtx));
        }
    } else {
        $actions = array_merge($actions, processActionLine($expr, $selections, $locals, $globals, $quantities, $xmlCtx));
    }
    return $actions;
}

// Função auxiliar para obter valores do loop
function getLoopValue($quantities, $selections, $globals, $path) 
{
    // Implementação simplificada - você pode expandir conforme necessário
    if (is_array($quantities) && isset($quantities[$path])) {
        return $quantities[$path];
    }
    
    // Se o path contém barras, tentar navegar pela estrutura
    if (strpos($path, '/') !== false) {
        $parts = explode('/', $path);
        $current = $quantities;
        
        foreach ($parts as $part) {
            if (is_array($current) && isset($current[$part])) {
                $current = $current[$part];
            } else {
                return [];
            }
        }
        
        return is_array($current) ? $current : [$current];
    }
    
    return [];
}

// Função para avaliar condições
function evaluateExpressionCondition($condition, $selections, $locals, $globals, $quantities, $xmlCtx) 
{
    // Implementação básica - você pode expandir conforme necessário
    try {
        $result = evaluateExpression($condition, $selections, $locals, $globals, $quantities, $xmlCtx);
        return !empty($result) && $result !== '0' && $result !== 0 && $result !== false;
    } catch (Exception $e) {
        return false;
    }
}

// Função principal para avaliar expressões
function evaluateExpression($expr, $selections, $locals, $globals, $quantities, $xmlCtx) 
{
    // Implementação básica - você precisa expandir isto conforme suas necessidades
    $expr = trim($expr);
    
    // Remover aspas se for uma string literal
    if (preg_match('/^"(.*)"$/', $expr, $match)) {
        return $match[1];
    }
    
    // Se for um número
    if (is_numeric($expr)) {
        return $expr;
    }
    
    // Substituir variáveis
    $expr = preg_replace_callback('/[@$]([a-zA-Z_][\\w]*)/', function($matches) use ($locals, $globals) {
        $varName = $matches[1];
        if ($matches[0][0] === '@' && isset($globals[$varName])) {
            return $globals[$varName];
        } elseif ($matches[0][0] === '$' && isset($locals[$varName])) {
            return $locals[$varName];
        }
        return $matches[0];
    }, $expr);
    
    // Substituir chaves XML/JSON
    $expr = preg_replace_callback('/%%([^%]+)%%/', function($matches) use ($quantities, $xmlCtx) {
        $path = $matches[1];
        // Tentar encontrar o valor no contexto XML ou quantities
        if (isset($xmlCtx[$path])) {
            return $xmlCtx[$path];
        }
        return $matches[0];
    }, $expr);
    
    // Para esta implementação básica, retornar a expressão processada
    return $expr;
}

// Funções matemáticas auxiliares (você pode implementar conforme necessário)
function round_log($number, $digits) {
    $result = round($number, $digits);
    logMessage("round($number, $digits) = $result");
    return $result;
}

function rounddown_log($number, $digits) {
    $multiplier = pow(10, $digits);
    $result = floor($number * $multiplier) / $multiplier;
    logMessage("rounddown($number, $digits) = $result");
    return $result;
}

function roundup_log($number, $digits) {
    $multiplier = pow(10, $digits);
    $result = ceil($number * $multiplier) / $multiplier;
    logMessage("roundup($number, $digits) = $result");
    return $result;
}

function ceiling_log($number, $multiple) {
    $result = ceil($number / $multiple) * $multiple;
    logMessage("ceiling($number, $multiple) = $result");
    return $result;
}

function floor_log($number, $multiple) {
    $result = floor($number / $multiple) * $multiple;
    logMessage("floor($number, $multiple) = $result");
    return $result;
}

function remainder_log($number, $divisor) {
    $result = $number % $divisor;
    logMessage("remainder($number, $divisor) = $result");
    return $result;
}
