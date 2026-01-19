<?php
// test_python.php
header('Content-Type: text/plain; charset=utf-8');

echo "=== Python実行テスト ===\n\n";

// 現在のディレクトリ
echo "現在のディレクトリ: " . __DIR__ . "\n";
echo "スクリプトパス: " . __DIR__ . '/yosoku.py' . "\n";
echo "ファイル存在: " . (file_exists(__DIR__ . '/yosoku.py') ? 'はい' : 'いいえ') . "\n\n";

// Pythonコマンドテスト
$commands = [
    'python --version' => 'python --version 2>&1',
    'python3 --version' => 'python3 --version 2>&1',
    'where python' => 'where python 2>&1',
    'which python' => 'which python 2>&1'
];

foreach ($commands as $desc => $cmd) {
    echo "{$desc}:\n";
    $result = shell_exec($cmd);
    echo "  " . ($result ? trim($result) : '実行失敗') . "\n\n";
}

// シンプルなPythonテスト
echo "シンプルなPythonテスト:\n";
$test_cmd = 'python -c "print(\'{\"test\": \"success\"}\')" 2>&1';
$test_result = shell_exec($test_cmd);
echo "コマンド: {$test_cmd}\n";
echo "結果: " . ($test_result ? trim($test_result) : 'NULL') . "\n\n";

// yosoku.pyを直接実行
echo "yosoku.pyを実行:\n";
if (file_exists(__DIR__ . '/yosoku.py')) {
    $cmd = 'python "' . __DIR__ . '/yosoku.py" --json --start "2024-12-01" --end "2024-12-03" 2>&1';
    echo "コマンド: {$cmd}\n";
    $result = shell_exec($cmd);
    if ($result) {
        echo "結果長さ: " . strlen($result) . "文字\n";
        echo "結果（先頭500文字）:\n" . substr($result, 0, 500) . "\n";
        
        // JSONとして解析できるか
        $decoded = json_decode(trim($result), true);
        if ($decoded) {
            echo "\nJSON解析成功！\n";
        } else {
            echo "\nJSON解析失敗: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "結果: NULL\n";
    }
} else {
    echo "yosoku.pyが見つかりません\n";
}

echo "\n=== PHP情報 ===\n";
echo "PHPバージョン: " . PHP_VERSION . "\n";
echo "安全モード: " . (ini_get('safe_mode') ? 'オン' : 'オフ') . "\n";
echo "無効関数: " . (ini_get('disable_functions') ?: 'なし') . "\n";
echo "shell_exec利用可能: " . (function_exists('shell_exec') ? 'はい' : 'いいえ') . "\n";
?>