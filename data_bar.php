<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
header("Content-Type: application/json; charset=UTF-8");

$mysqli = new mysqli("localhost","root","root","accidents");
if($mysqli->connect_error){
  http_response_code(500);
  echo json_encode(["error"=>"DB接続失敗: ".$mysqli->connect_error], JSON_UNESCAPED_UNICODE);
  exit;
}

$start = $_GET["start"] ?? "2003-03-27";
$end   = $_GET["end"]   ?? "2003-06-23";
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$end)){
  http_response_code(400);
  echo json_encode(["error"=>"日付形式が不正です"], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ✅ 年齢区分を DB から全種類取得し、( )内の番号でソート */
$age_buckets = [];
$sql_age = "
  SELECT DISTINCT TRIM(年齢区分_サーベイランス用) AS age
  FROM shobo
  WHERE 年齢区分_サーベイランス用 IS NOT NULL
    AND TRIM(年齢区分_サーベイランス用) <> ''
";
if ($resAge = $mysqli->query($sql_age)) {
  while($row = $resAge->fetch_assoc()){ $age_buckets[] = $row["age"]; }
  // (9)75歳以上 のような先頭の番号で昇順ソート
  usort($age_buckets, function($a,$b){
    preg_match('/\((\d+)\)/u', $a, $ma); $na = isset($ma[1]) ? (int)$ma[1] : 999;
    preg_match('/\((\d+)\)/u', $b, $mb); $nb = isset($mb[1]) ? (int)$mb[1] : 999;
    return $na <=> $nb ?: strcmp($a,$b);
  });
}

/* ▼ 期間で上位5傷病名を取得 */
$sql_top = "
  SELECT 傷病名, COUNT(*) AS cnt
  FROM shobo
  WHERE (
    (覚知年月日 BETWEEN ? AND ?)
    OR (STR_TO_DATE(覚知年月日,'%Y/%m/%d') BETWEEN ? AND ?)
  )
  AND TRIM(COALESCE(傷病名,'')) <> ''
  AND COALESCE(搬送有無, '') <> '不取扱'
  GROUP BY 傷病名
  ORDER BY cnt DESC
  LIMIT 5
";
$st = $mysqli->prepare($sql_top);
$st->bind_param("ssss",$start,$end,$start,$end);
$st->execute();
$r = $st->get_result();

$top_diseases = [];
while($row = $r->fetch_assoc()){ $top_diseases[] = $row["傷病名"]; }
if(empty($top_diseases)){
  echo json_encode(["labels"=>[], "datasets"=>[]], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ▼ 上位5傷病名 × 年齢区分で件数をクロス集計 */
$in = implode(",", array_fill(0, count($top_diseases), "?"));
$types = str_repeat("s", count($top_diseases));
$sql_pivot = "
  SELECT 傷病名, 年齢区分_サーベイランス用 AS age, COUNT(*) AS cnt
  FROM shobo
  WHERE (
    (覚知年月日 BETWEEN ? AND ?)
    OR (STR_TO_DATE(覚知年月日,'%Y/%m/%d') BETWEEN ? AND ?)
  )
  AND 傷病名 IN ($in)
  GROUP BY 傷病名, 年齢区分_サーベイランス用
";
$st2 = $mysqli->prepare($sql_pivot);
$params = array_merge([$start,$end,$start,$end], $top_diseases);
$st2->bind_param("ssss".$types, ...$params);
$st2->execute();
$r2 = $st2->get_result();

/* ▼ マトリクス化（ゼロ埋め：年齢区分は全種類） */
$matrix = [];
foreach($top_diseases as $d){
  foreach($age_buckets as $a){ $matrix[$d][$a] = 0; }
}
while($row = $r2->fetch_assoc()){
  $d = $row["傷病名"];
  $a = trim($row["age"] ?? "");
  if($a !== "" && isset($matrix[$d][$a])){
    $matrix[$d][$a] = (int)$row["cnt"];
  }
}

/* ▼ Chart.js 形式（datasets=年齢区分 / labels=傷病名） */
$datasets = [];
foreach($age_buckets as $a){
  $data = [];
  foreach($top_diseases as $d){ $data[] = $matrix[$d][$a]; }
  $datasets[] = ["label"=>$a, "data"=>$data];
}

echo json_encode([
  "labels"   => $top_diseases,
  "datasets" => $datasets
], JSON_UNESCAPED_UNICODE);
