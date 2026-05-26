<?php
/**
 * Scan PHP files and report duplicate fully-qualified symbols (namespace + class/interface/trait).
 *
 * Usage:
 *   php tools/scan-php-duplicates.php app app/app
 *
 * Notes:
 * - This is intentionally lightweight (regex-based) to avoid adding new dependencies.
 * - It only reports class/interface/trait declarations with a namespace.
 */
declare(strict_types=1);

function usageAndExit(int $code): void
{
    fwrite(STDERR, "Usage: php tools/scan-php-duplicates.php <dir> [dir2 ...]\n");
    exit($code);
}

if ($argc < 2) {
    usageAndExit(1);
}

$roots = array_slice($argv, 1);
$files = [];

foreach ($roots as $root) {
    $root = rtrim($root, "/\\");
    if (!is_dir($root)) {
        fwrite(STDERR, "Skip non-dir: {$root}\n");
        continue;
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if (!$file->isFile()) {
            continue;
        }
        if (strtolower($file->getExtension()) !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        // Skip vendor/runtime caches if someone passes a wide dir.
        if (preg_match('#[/\\\\](vendor|runtime)[/\\\\]#i', $path)) {
            continue;
        }
        $files[] = $path;
    }
}

sort($files);

$symbols = []; // fqn => [file1, file2...]
$parseErrors = [];

foreach ($files as $path) {
    $content = @file_get_contents($path);
    if ($content === false) {
        $parseErrors[] = $path;
        continue;
    }

    // Namespace: namespace app\controller\api;
    if (!preg_match('/^\s*namespace\s+([^;]+)\s*;/m', $content, $nsMatch)) {
        continue;
    }
    $ns = trim($nsMatch[1]);

    // Find first class/interface/trait name (supports final/abstract).
    if (!preg_match('/^\s*(?:final\s+|abstract\s+)?(class|interface|trait)\s+([A-Za-z_][A-Za-z0-9_]*)\b/m', $content, $cMatch)) {
        continue;
    }

    $name = $cMatch[2];
    $fqn = $ns . '\\' . $name;
    if (!isset($symbols[$fqn])) {
        $symbols[$fqn] = [];
    }
    $symbols[$fqn][] = $path;
}

$dups = array_filter($symbols, static function (array $paths): bool {
    return count($paths) > 1;
});
uksort($dups, 'strcasecmp');

echo "# Duplicate PHP symbols report\n\n";
echo "- Scanned files: " . count($files) . "\n";
echo "- Symbols found: " . count($symbols) . "\n";
echo "- Duplicates: " . count($dups) . "\n";
if ($parseErrors) {
    echo "- Read errors: " . count($parseErrors) . "\n";
}
echo "\n";

if (!$dups) {
    echo "No duplicates found.\n";
    exit(0);
}

foreach ($dups as $fqn => $paths) {
    echo "## {$fqn}\n\n";
    foreach ($paths as $p) {
        // Normalize to forward slashes for readability.
        $p = str_replace('\\', '/', $p);
        echo "- `{$p}`\n";
    }
    echo "\n";
}

if ($parseErrors) {
    echo "## Read errors\n\n";
    foreach ($parseErrors as $p) {
        $p = str_replace('\\', '/', $p);
        echo "- `{$p}`\n";
    }
    echo "\n";
}

