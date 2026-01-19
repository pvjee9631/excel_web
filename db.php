<?php
try {
    // MAMP のデフォルト設定（Windowsでも同じ）
    $pdo = new PDO(
        "mysql:host=localhost;dbname=excel_web;charset=utf8",
        "root",
        "root"
    );

    // エラーを例外として投げる設定（非常に重要）
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo "DB Connection Failed: " . $e->getMessage();
    exit;
}
?>
