<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
header("Content-Type: application/json; charset=UTF-8");

$mysqli = new mysqli("localhost","root","root","accidents");
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(["error" => "DB接続失敗: " . $mysqli->connect_error], JSON_UNESCAPED_UNICODE);
  exit;
}

$start = $_GET["start"] ?? "2003-03-27";
$end   = $_GET["end"]   ?? "2003-06-23";

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
  http_response_code(400);
  echo json_encode(["error" => "日付形式が不正です"], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ▼ 病気グループ（急病・転院） */
$sql1 = "
  SELECT COALESCE(傷病程度,'不明') AS severity, COUNT(*) AS cnt
  FROM shobo
  WHERE (
    (覚知年月日 BETWEEN ? AND ?)
    OR (STR_TO_DATE(覚知年月日, '%Y/%m/%d') BETWEEN ? AND ?)
  )
  AND (事故種別 LIKE '%急病%' OR 事故種別 LIKE '%転院%')
  AND COALESCE(搬送有無, '') <> '不取扱'
  GROUP BY severity
  ORDER BY cnt DESC
";
$stmt1 = $mysqli->prepare($sql1);
$stmt1->bind_param("ssss", $start, $end, $start, $end);
$stmt1->execute();
$res1 = $stmt1->get_result();

$labels_b = [];
$data_b = [];
while ($row = $res1->fetch_assoc()) {
  $labels_b[] = $row["severity"];
  $data_b[] = (int)$row["cnt"];
}

/* ▼ 事故グループ（交通・一般負傷） */
$sql2 = "
  SELECT COALESCE(傷病程度,'不明') AS severity, COUNT(*) AS cnt
  FROM shobo
  WHERE (
    (覚知年月日 BETWEEN ? AND ?)
    OR (STR_TO_DATE(覚知年月日, '%Y/%m/%d') BETWEEN ? AND ?)
  )
  AND (事故種別 LIKE '%交通%' OR 事故種別 LIKE '%一般負傷%')
  AND COALESCE(搬送有無, '') <> '不取扱'
  GROUP BY severity
  ORDER BY cnt DESC
";
$stmt2 = $mysqli->prepare($sql2);
$stmt2->bind_param("ssss", $start, $end, $start, $end);
$stmt2->execute();
$res2 = $stmt2->get_result();

$labels_a = [];
$data_a = [];
while ($row = $res2->fetch_assoc()) {
  $labels_a[] = $row["severity"];
  $data_a[] = (int)$row["cnt"];
}

echo json_encode([
  "byouki" => [
    "labels" => $labels_b,
    "counts" => $data_b
  ],
  "jiko" => [
    "labels" => $labels_a,
    "counts" => $data_a
  ]
], JSON_UNESCAPED_UNICODE);
