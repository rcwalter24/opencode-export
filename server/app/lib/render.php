<?php
declare(strict_types=1);

require_once __DIR__ . '/Parsedown.php';

function h(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function render_session(array $cfg, array $row, int $page = 1): string {
    $payload = json_decode($row['payload'], true);
    if (!is_array($payload)) {
        $payload = [];
    }
    $pd = new Parsedown();
    $pd->setSafeMode(true);
    $nonce = security_nonce();
    $base  = rtrim((string)$cfg['PUBLIC_BASE_URL'], '/');

    $per_page   = 20;
    $all_msgs   = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
    $total_msgs = count($all_msgs);
    $pages      = max(1, (int)ceil($total_msgs / $per_page));
    $page       = max(1, min($pages, $page));
    $offset     = ($page - 1) * $per_page;
    $msg_start  = $offset + 1;
    $msg_end    = min($offset + $per_page, $total_msgs);

    $payload['messages'] = array_slice($all_msgs, $offset, $per_page);

    ob_start();
    include __DIR__ . '/../templates/session.php';
    return ob_get_clean();
}

function render_part(array $part, Parsedown $pd): string {
    $type = is_string($part['type'] ?? null) ? $part['type'] : 'unknown';

    switch ($type) {
        case 'text':
            $text = is_string($part['text'] ?? null) ? $part['text'] : '';
            return '<div class="part-text">' . $pd->text($text) . '</div>';

        case 'reasoning':
            $text = h(is_string($part['text'] ?? null) ? $part['text'] : '');
            return '<details class="part-reasoning">
                <summary>&#x1F4AD; Reasoning</summary>
                <div class="reasoning-body"><pre>' . $text . '</pre></div>
            </details>';

        case 'tool':
            return render_tool($part);

        case 'file':
            return render_file_part($part);

        case 'step-start':
        case 'step-finish':
            return '';

        case 'compaction':
            $auto = ($part['auto'] ?? false) ? 'auto' : 'manual';
            return '<div class="part-compaction">
                <span class="compaction-icon">&#x267B;</span>
                <span class="compaction-label">Context compacted (' . $auto . ') — content before this point has been replaced with a summary</span>
            </div>';

        default:
            $json = h(json_encode($part, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));
            return '<details class="part-unknown">
                <summary>&#x26A0;&#xFE0F; Unknown part type: ' . h($type) . '</summary>
                <pre class="unknown-json">' . $json . '</pre>
            </details>';
    }
}

function render_tool(array $part): string {
    $tool   = h(is_string($part['tool'] ?? null) ? $part['tool'] : 'unknown');
    $state  = is_array($part['state'] ?? null) ? $part['state'] : [];
    $status = h(is_string($state['status'] ?? null) ? $state['status'] : 'unknown');

    $statusClass = match ($state['status'] ?? '') {
        'completed' => 'status-ok',
        'error'     => 'status-error',
        default     => 'status-pending',
    };

    $html = '<div class="part-tool ' . $statusClass . '">';
    $html .= '<div class="tool-header"><span class="tool-name">&#x1F527; ' . $tool . '</span> <span class="tool-status">' . $status . '</span></div>';

    if (isset($state['input'])) {
        $inputJson = h(
            is_string($state['input']) ? $state['input'] : json_encode($state['input'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)
        );
        $html .= '<details class="tool-section" open><summary>Input</summary><pre class="tool-input"><code class="language-json">' . $inputJson . '</code></pre></details>';
    }

    if (isset($state['output']) && $state['output'] !== '') {
        $output = h(is_scalar($state['output']) ? $state['output'] : json_encode($state['output'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $html .= '<details class="tool-section" open><summary>Output</summary><pre class="tool-output"><code>' . $output . '</code></pre></details>';
    }

    if (isset($state['error']) && $state['error'] !== '') {
        $error = h(is_scalar($state['error']) ? $state['error'] : json_encode($state['error'], JSON_UNESCAPED_UNICODE));
        $html .= '<div class="tool-error"><strong>Error:</strong> ' . $error . '</div>';
    }

    $html .= '</div>';
    return $html;
}

function render_file_part(array $part): string {
    $filename = h(is_string($part['filename'] ?? null) ? $part['filename'] : 'file');
    $mime     = h(is_string($part['mime'] ?? null) ? $part['mime'] : '');
    $url      = is_string($part['url'] ?? null) ? $part['url'] : '';

    $html = '<div class="part-file">&#x1F4CE; <strong>' . $filename . '</strong>';
    if ($mime) {
        $html .= ' <span class="file-mime">(' . $mime . ')</span>';
    }
    if ($url) {
        if (preg_match('#^https?://#i', $url)) {
            $html .= ' <a href="' . h($url) . '" target="_blank" rel="noopener noreferrer">Open</a>';
        } else {
            $html .= ' <span class="file-mime">' . h($url) . '</span>';
        }
    }
    $html .= '</div>';
    return $html;
}

/**
 * Model may be a plain string, an object {providerID, id|modelID, variant}
 * or (from older clients) that object serialised as a JSON string.
 */
function format_model(mixed $model): string {
    if (is_string($model)) {
        $trimmed = trim($model);
        if ($trimmed !== '' && $trimmed[0] === '{') {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                $model = $decoded;
            }
        }
    }
    if (is_array($model)) {
        $provider = is_string($model['providerID'] ?? null) ? $model['providerID'] : '';
        $id       = is_string($model['modelID'] ?? null) ? $model['modelID']
                  : (is_string($model['id'] ?? null) ? $model['id'] : '');
        $variant  = is_string($model['variant'] ?? null) ? $model['variant'] : '';
        $out = $provider !== '' && $id !== '' ? "$provider/$id" : ($id ?: $provider);
        return $variant !== '' ? "$out ($variant)" : $out;
    }
    return is_scalar($model) ? (string)$model : '';
}

function format_cost(mixed $cost): string {
    if (!is_numeric($cost) || (float)$cost == 0.0) {
        return '$0.00';
    }
    return '$' . number_format((float)$cost, 4);
}

function format_ts(mixed $ts): string {
    if (!is_numeric($ts)) {
        return '-';
    }
    $ts = (int)$ts;
    // timestamps may be in ms (>1e12) or s
    if ($ts > 1e12) {
        $ts = intdiv($ts, 1000);
    }
    return gmdate('Y-m-d H:i:s', $ts) . ' UTC';
}
