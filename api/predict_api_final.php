<?php
// api/predict_api_final.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// パラメータ取得
$start_date = $_GET['start'] ?? date('Y-m-d', strtotime('+1 day'));
$end_date = $_GET['end'] ?? date('Y-m-d', strtotime('+7 days'));

// 日付バリデーション
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

if (!isValidDate($start_date) || !isValidDate($end_date)) {
    echo json_encode([
        'status' => 'error',
        'message' => '無効な日付形式です。YYYY-MM-DD形式を使用してください。'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 開始日と終了日のDateTimeオブジェクトを作成
$start = DateTime::createFromFormat('Y-m-d', $start_date);
$end = DateTime::createFromFormat('Y-m-d', $end_date);

// 日付の順序チェック
if ($start > $end) {
    echo json_encode([
        'status' => 'error', 
        'message' => '開始日は終了日より前である必要があります。'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 予測期間が長すぎないかチェック（最大30日）
$interval = $start->diff($end);
if ($interval->days > 30) {
    echo json_encode([
        'status' => 'error',
        'message' => '予測期間が長すぎます。最大30日間までです。'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 予測データを生成
$data = [];
$daily_summary = [];
$current = clone $start;

while ($current <= $end) {
    $date_str = $current->format('Y-m-d');
    $day_of_week = $current->format('w'); // 0=日曜日, 1=月曜日, ..., 6=土曜日
    $is_weekend = ($day_of_week == 0 || $day_of_week == 6);
    $month = (int)$current->format('n'); // 1-12
    
    $day_total = 0;
    
    // 24時間分の予測を生成
    for ($hour = 0; $hour < 24; $hour++) {
        // 基本値
        $base = 1.0;
        
        // 時間帯による補正
        if ($hour == 8 || $hour == 9) {
            $base = 1.8; // 朝の通勤時間帯
        } elseif ($hour == 17 || $hour == 18) {
            $base = 1.6; // 夕方の通勤時間帯
        } elseif ($hour >= 22 || $hour <= 5) {
            $base = 0.4; // 深夜
        } elseif ($hour >= 10 && $hour <= 16) {
            $base = 1.2; // 昼間
        }
        
        // 休日補正（土日は需要が増加）
        if ($is_weekend) {
            $base *= 1.3;
        }
        
        // 季節補正
        if ($month == 7 || $month == 8) {
            $base *= 1.2; // 夏休み
        } elseif ($month == 12 || $month == 1) {
            $base *= 1.3; // 冬休み・正月
        }
        
        // 予測値を小数点以下2桁に丸める
        $prediction = round($base, 2);
        $day_total += $prediction;
        
        // データに追加
        $data[] = [
            'datetime' => $date_str . ' ' . sprintf('%02d', $hour) . ':00:00',
            'hour' => $hour,
            'prediction' => $prediction,
            'is_holiday' => $is_weekend ? 1 : 0
        ];
    }
    
    // 日別サマリーに追加
    $daily_summary[] = [
        'date' => $date_str,
        'total' => round($day_total, 1)
    ];
    
    // 翌日に進める
    $current->modify('+1 day');
}

// 統計情報の計算
$predictions = array_column($data, 'prediction');
$total = array_sum($predictions);
$count = count($predictions);

$result = [
    'status' => 'success',
    'prediction_period' => [
        'start' => $start_date,
        'end' => $end_date
    ],
    'data' => $data,
    'daily_summary' => $daily_summary,
    'statistics' => [
        'total' => round($total, 1),
        'average_per_hour' => round($total / $count, 2),
        'max_hour' => round(max($predictions), 1),
        'min_hour' => round(min($predictions), 1)
    ],
    'source' => 'php_optimized',
    'generated_at' => date('Y-m-d H:i:s')
];

// 数値を確実に整形するカスタムJSONエンコーダー
function clean_json_encode($data) {
    // 再帰的に浮動小数点数をフォーマット
    array_walk_recursive($data, function(&$item) {
        if (is_float($item)) {
            // 小数点以下2桁にフォーマット
            $item = (float)number_format($item, 2, '.', '');
        }
    });
    
    // JSONエンコード
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    
    // 万が一長い小数が残っていないかチェック
    $json = preg_replace_callback('/\d+\.\d{10,}/', function($matches) {
        return number_format((float)$matches[0], 2, '.', '');
    }, $json);
    
    return $json;
}

// 結果を出力
echo clean_json_encode($result);
?>