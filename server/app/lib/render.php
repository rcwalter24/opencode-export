<?php
declare(strict_types=1);

require_once __DIR__ . '/Parsedown.php';

function render_session(array $row, int $page = 1): string {
    $payload   = json_decode($row['payload'], true);
    $pd        = new Parsedown();
    $pd->setSafeMode(true);

    $per_page   = 20;
    $all_msgs   = $payload['messages'] ?? [];
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
    $type = $part['type'] ?? 'unknown';

    switch ($type) {
        case 'text':
            $text = $part['text'] ?? '';
            return '<div class="part-text">' . $pd->text($text) . '</div>';

        case 'reasoning':
            $text = htmlspecialchars($part['text'] ?? '', ENT_QUOTES, 'UTF-8');
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
                <span class="compaction-label">Context compacted (' . htmlspecialchars($auto, ENT_QUOTES, 'UTF-8') . ') — content before this point has been replaced with a summary</span>
            </div>';

        default:
            $json = htmlspecialchars(json_encode($part, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            return '<details class="part-unknown">
                <summary>&#x26A0;&#xFE0F; Unknown part type: ' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</summary>
                <pre class="unknown-json">' . $json . '</pre>
            </details>';
    }
}

function render_tool(array $part): string {
    $tool  = htmlspecialchars($part['tool'] ?? 'unknown', ENT_QUOTES, 'UTF-8');
    $state = $part['state'] ?? [];
    $status = htmlspecialchars($state['status'] ?? 'unknown', ENT_QUOTES, 'UTF-8');

    $statusClass = match ($state['status'] ?? '') {
        'completed' => 'status-ok',
        'error'     => 'status-error',
        default     => 'status-pending',
    };

    $html = '<div class="part-tool ' . $statusClass . '">';
    $html .= '<div class="tool-header"><span class="tool-name">&#x1F527; ' . $tool . '</span> <span class="tool-status">' . $status . '</span></div>';

    if (isset($state['input'])) {
        $inputJson = htmlspecialchars(
            is_string($state['input']) ? $state['input'] : json_encode($state['input'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ENT_QUOTES, 'UTF-8'
        );
        $html .= '<details class="tool-section" open><summary>Input</summary><pre class="tool-input"><code class="language-json">' . $inputJson . '</code></pre></details>';
    }

    if (isset($state['output']) && $state['output'] !== '') {
        $output = htmlspecialchars((string)$state['output'], ENT_QUOTES, 'UTF-8');
        $html .= '<details class="tool-section" open><summary>Output</summary><pre class="tool-output"><code>' . $output . '</code></pre></details>';
    }

    if (isset($state['error']) && $state['error'] !== '') {
        $error = htmlspecialchars((string)$state['error'], ENT_QUOTES, 'UTF-8');
        $html .= '<div class="tool-error"><strong>Error:</strong> ' . $error . '</div>';
    }

    $html .= '</div>';
    return $html;
}

function render_file_part(array $part): string {
    $filename = htmlspecialchars($part['filename'] ?? 'file', ENT_QUOTES, 'UTF-8');
    $mime     = htmlspecialchars($part['mime'] ?? '', ENT_QUOTES, 'UTF-8');
    $url      = $part['url'] ?? '';

    $html = '<div class="part-file">&#x1F4CE; <strong>' . $filename . '</strong>';
    if ($mime) {
        $html .= ' <span class="file-mime">(' . $mime . ')</span>';
    }
    if ($url) {
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $html .= ' <a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer">Open</a>';
    }
    $html .= '</div>';
    return $html;
}

function format_cost(mixed $cost): string {
    if ($cost === null || $cost === 0 || $cost === 0.0) {
        return '$0.00';
    }
    return '$' . number_format((float)$cost, 4);
}

function format_ts(int $ts): string {
    // timestamps may be in ms (>1e12) or s
    if ($ts > 1e12) {
        $ts = intdiv($ts, 1000);
    }
    return date('Y-m-d H:i:s', $ts) . ' UTC';
}
