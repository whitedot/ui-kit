<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<?php
function readme_inline(string $text): string
{
    $images = [];
    $links = [];
    $i = 0;
    $imageIndex = 0;

    $text = preg_replace_callback('/!\[(.*?)\]\((.*?)\)/', function ($m) use (&$images, &$imageIndex) {
        $key = "%%IMAGE_{$imageIndex}%%";
        $alt = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
        $src = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
        $images[$key] = "<img src=\"{$src}\" alt=\"{$alt}\" style=\"max-width:100%;height:auto;border-radius:12px;display:block;margin:0 0 1rem;\">";
        $imageIndex++;
        return $key;
    }, $text);

    $text = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function ($m) use (&$links, &$i) {
        $key = "%%LINK_{$i}%%";
        $label = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
        $links[$key] = "<a href=\"{$url}\">{$label}</a>";
        $i++;
        return $key;
    }, $text);

    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

    if (!empty($links)) {
        $text = strtr($text, $links);
    }

    if (!empty($images)) {
        $text = strtr($text, $images);
    }

    return $text;
}

function render_readme_markdown(string $markdown): string
{
    $lines = preg_split('/\R/', trim($markdown));
    $html = [];
    $inList = false;
    $inCode = false;
    $codeLines = [];

    foreach ($lines as $line) {
        if (preg_match('/^\s*```/', $line)) {
            if ($inCode) {
                $html[] = '<pre><code>' . htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES, 'UTF-8') . '</code></pre>';
                $codeLines = [];
                $inCode = false;
            } else {
                $inCode = true;
            }
            continue;
        }

        if ($inCode) {
            $codeLines[] = $line;
            continue;
        }

        if (preg_match('/^\s*[-*]\s+(.+)$/', $line, $m)) {
            if (!$inList) {
                $html[] = '<ul>';
                $inList = true;
            }
            $html[] = '<li>' . readme_inline($m[1]) . '</li>';
            continue;
        }

        if ($inList) {
            $html[] = '</ul>';
            $inList = false;
        }

        if (trim($line) === '') {
            continue;
        }

        if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
            $level = strlen($m[1]);
            $html[] = "<h{$level}>" . readme_inline($m[2]) . "</h{$level}>";
            continue;
        }

        $html[] = '<p>' . readme_inline($line) . '</p>';
    }

    if ($inList) {
        $html[] = '</ul>';
    }

    if ($inCode) {
        $html[] = '<pre><code>' . htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES, 'UTF-8') . '</code></pre>';
    }

    return implode("\n", $html);
}

$readmePath = __DIR__ . '/README.md';
$readmeContent = is_file($readmePath) ? file_get_contents($readmePath) : '';
$readmeHtml = $readmeContent !== false && $readmeContent !== ''
    ? render_readme_markdown($readmeContent)
    : '<p>README.md 파일을 찾을 수 없습니다.</p>';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">README</h4>
        </div>
        <div class="card-body" id="readme-content">
            <?= $readmeHtml ?>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
