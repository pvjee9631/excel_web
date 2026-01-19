<?php
// api/predict_api.php
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
        'message' => '無効な日付形式です'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 数値を整形する関数
function formatNumbers($value) {
    if (is_float($value)) {
        return (float) number_format($value, 2, '.', '');
    }
    if (is_array($value)) {
        foreach ($value as &$item) {
            $item = formatNumbers($item);
        }
    }
    return $value;
}

// PHPで予測データを生成（きれいな数値で）
function getPredictionFromPHP($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    $data = [];
    $daily_summary = [];
    $current = clone $start;
    
    while ($current <= $end) {
        $date_str = $current->format('Y-m-d');
        $day_of_week = $current->format('w');
        $is_weekend = ($day_of_week == 0 || $day_of_week == 6);
        $month = $current->format('n');
        
        $day_total = 0;
        
        for ($hour = 0; $hour < 24; $hour++) {
            $base = 1.0;
            
            // 時間帯補正
            if ($hour >= 8 && $hour <= 9) {
                $base *= 1.8; // 朝の通勤時間
            } elseif ($hour >= 17 && $hour <= 18) {
                $base *= 1.6; // 夕方の通勤時間
            } elseif ($hour >= 22 || $hour <= 5) {
                $base *= 0.4; // 深夜
            } elseif ($hour >= 10 && $hour <= 16) {
                $base *= 1.2; // 昼間
            }
            
            // 休日補正
            if ($is_weekend) {
                $base *= 1.3;
            }
            
            // 季節補正（12月は冬なので増加）
            if ($month == 7 || $month == 8) {
                $base *= 1.2; // 夏
            } elseif ($month == 12 || $month == 1) {
                $base *= 1.3; // 冬
            }
            
            $prediction = round($base, 2);
            $day_total += $prediction;
            
            $data[] = [
                'datetime' => $date_str . ' ' . sprintf('%02d', $hour) . ':00:00',
                'hour' => (int) $hour,
                'prediction' => $prediction,
                'is_holiday' => $is_weekend ? 1 : 0
            ];
        }
        
        $daily_summary[] = [
            'date' => $date_str,
            'total' => round($day_total, 1)
        ];
        
        $current->modify('+1 day');
    }
    
    // 統計情報
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
        'source' => 'php'
    ];
    
    return $result;
}

// Pythonからデータを取得して数値を整形
function getPredictionFromPython($start_date, $end_date) {
    $script_path = dirname(__DIR__) . '/yosoku.py';
    
    if (!file_exists($script_path)) {
        return null;
    }
    
    $python_path = 'python'; // パスが通っているのでこれでOK
    
    $command = sprintf(
        '"%s" "%s" --json --start "%s" --end "%s"',
        $python_path,
        $script_path,
        $start_date,
        $end_date
    );
    
    $output = shell_exec($command . ' 2>&1');
    
    if ($output === null || empty(trim($output))) {
        return null;
    }
    
    $output = trim($output);
    $decoded = json_decode($output, true);
    
    if ($decoded === null || !isset($decoded['status']) || $decoded['status'] !== 'success') {
        return null;
    }
    
    // 数値を整形
    $decoded = formatNumbers($decoded);
    $decoded['source'] = 'python';
    
    return $decoded;
}

// メイン処理
try {
    // Pythonを試す
    $result = getPredictionFromPython($start_date, $end_date);
    
    if ($result === null) {
        // Pythonが失敗したらPHPを使用
        $result = getPredictionFromPHP($start_date, $end_date);
    }
    
    // 最終的な数値整形を確実にする
    $result = formatNumbers($result);
    
    // JSON出力（シリアライズ時に数値を確実にフォーマット）
    $json_options = JSON_UNESCAPED_UNICODE;
    
    // 数値を確実にフォーマットするためのカスタムエンコーダー
    function json_encode_clean($data, $options = 0) {
        // 数値をフォーマット
        array_walk_recursive($data, function(&$value, $key) {
            if (is_float($value)) {
                $value = (float) number_format($value, 2, '.', '');
            }
        });
        
        return json_encode($data, $options);
    }
    
    echo json_encode_clean($result, $json_options);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => '予測データの取得に失敗しました',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>