<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");

// ----------------------------
// DB 接続（mysqli）
// ----------------------------
$mysqli = new mysqli("localhost", "root", "root", "accidents");
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB接続失敗"]);
    exit;
}

// ----------------------------
// GET パラメータ
// ----------------------------
$start = $_GET["start"] ?? "2003-01-01";
$end   = $_GET["end"]   ?? "2003-12-31";

// 日付形式チェック
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
    http_response_code(400);
    echo json_encode(["error" => "日付形式が不正"]);
    exit;
}

// ----------------------------
// 祝日判定ファイル
// ----------------------------
require_once "./api/holiday_jp.php";   // isHolidayJP($date) が使える

// ----------------------------
// カウント配列の準備
// ----------------------------
$weekday_counts = array_fill(0, 24, 0);
$holiday_counts = array_fill(0, 24, 0);

// ----------------------------
// SQL：覚知時 と 覚知年月日 の正規化
// ----------------------------
$sql = "
    SELECT 
        CASE 
            WHEN 覚知時 BETWEEN 0 AND 23 THEN 覚知時
            WHEN 覚知時 >= 100 THEN FLOOR(覚知時 / 100)
            ELSE NULL 
        END AS hour_num,

        COUNT(*) AS cnt,

        CASE 
            WHEN 覚知年月日 LIKE '%/%' THEN STR_TO_DATE(覚知年月日, '%Y/%m/%d')
            ELSE 覚知年月日
        END AS ymd
    FROM shobo
    WHERE 
      (
        覚知年月日 BETWEEN ? AND ?
        OR STR_TO_DATE(覚知年月日, '%Y/%m/%d') BETWEEN ? AND ?
      )
    GROUP BY hour_num, ymd
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ssss", $start, $end, $start, $end);
$stmt->execute();
$res = $stmt->get_result();

// ----------------------------
// 集計処理
// ----------------------------
while ($row = $res->fetch_assoc()) {

    if ($row["hour_num"] === null) continue;

    $hour  = (int)$row["hour_num"];
    $count = (int)$row["cnt"];
    $date  = $row["ymd"];

    // 土日
    $w = (int)date("w", strtotime($date)); 
    $isWeekend = ($w === 0 || $w === 6);

    // 祝日
    $isHoliday = $isWeekend || isHolidayJP($date);

    if ($isHoliday) {
        $holiday_counts[$hour] += $count;
    } else {
        $weekday_counts[$hour] += $count;
    }
}

// ----------------------------
// JSON 出力
// ----------------------------
echo json_encode([
    "hours"    => range(0, 23),
    "weekday"  => $weekday_counts,
    "holiday"  => $holiday_counts
], JSON_UNESCAPED_UNICODE);

?>
