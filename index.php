<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <title>救急ダッシュボード</title>
  <link rel="icon" href="photo.png" type="image/png">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.0.1"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root{ --gap:12px; --header-h:60px; }
    *{ box-sizing:border-box; }
    html,body{ height:100%; margin:0; font-family:Arial, system-ui, sans-serif; background:#f6f7fb; }

    .page{ height:100vh; display:grid; grid-template-rows: var(--header-h) 1fr; overflow:hidden; }
  .toolbar{
  position: sticky;
  top: 0;
  z-index: 1000;

  background:#2563eb;
  color:#fff;

  padding: 20px 24px;   /* ← 背景を太く */
  padding-top: 8px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  border-radius: 0 0 12px 12px;
}

/* タイトル */
.toolbar-title h1{
  font-size: 2.2rem;
  margin-bottom: 10px;
  }

/* 操作行 */
.toolbar-controls{
  display: flex;
  align-items: center;
  gap: 15px;
  flex-wrap: wrap;

}
.toolbar-controls #start,
.toolbar-controls #end{
  padding: 6px 10px;
  border-radius: 6px;
  border: none;
  font-size: 14px;
}

/* ボタン */
.toolbar button{
  background:#1e40af;
  color:#fff;
  border:none;
  border-radius:8px;
  padding:8px 16px;
  cursor:pointer;
}

.toolbar button:hover{
  background:#1d4ed8;
}


/* 今日の日付バッジ */
.today-badge{
  background:#f6f7fb;
  color:#111; padding:4px 10px; border-radius:999px ;
  font-size:12px; font-weight:600; text-align:center; 
      
}

    .grid{
      display:grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr;
      gap: var(--gap); padding: var(--gap); height:100%;
    }
    .panel{
      background:#fff; border-radius:12px; box-shadow:0 4px 16px rgba(0,0,0,.06);
      position:relative; padding:12px; overflow:hidden; display:flex; flex-direction:column;
    }
    .panel h3{ margin:0 0 8px; font-size:14px; color:#111; font-weight:600; line-height:1; }

    .canvas-wrap{ flex:1; min-height:0; position:relative; }
    .canvas-wrap canvas{ position:absolute; inset:0; width:100% !important; height:100% !important; }

    /* 病気／事故のカード（box-shadow） */
    .chart-pair {
      display:flex; gap:12px; align-items:stretch; height:100%;
    }
    .chart-box {
          background: linear-gradient(180deg, #ffffff, #f8fafc);
          border-radius: 16px;
          padding: 14px;
          flex: 1;
          min-height: 240px;
          display: flex;
          flex-direction: column;
          box-shadow: 0 10px 28px rgba(0,0,0,0.12);
}

    .chart-box:hover{ transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,0,0,0.12); }
    .chart-box h4{ margin:0 0 8px; font-size:13px; text-align:center; }

    .chart-box .canvas-inner{ position:relative; flex:1; }
    .chart-box canvas{ position:absolute; inset:0; width:100% !important; height:100% !important; }

    @media (max-width:1100px){
      .grid{ grid-template-columns:1fr; grid-template-rows:repeat(4,1fr); }
      .chart-pair{ flex-direction:row; }
      .chart-box{ min-height:200px; }
    }
    /* 凡例のスタイルを調整 */
.chartjs-legend {
  width: 100%;
  margin-top: 10px;
}

.chartjs-legend ul {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
  padding: 0;
  margin: 0;
  list-style: none;
}

.chartjs-legend li {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  white-space: nowrap;
}

.chartjs-legend .legend-marker {
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

    /* ===== グラフ4用追加スタイル ===== */
    /* 予測グラフコンテナ */
    .prediction-container {
      position: relative;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    
    /* 日付選択コントロール */
    .prediction-controls {
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 16px;
      border: 1px solid #e2e8f0;
    }
    
    .date-selector {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }
    
    .date-selector label {
      font-size: 13px;
      font-weight: 600;
      color: #475569;
      white-space: nowrap;
    }
    
    .date-selector input {
      padding: 8px 12px;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      font-size: 13px;
      width: 140px;
    }
    
    .date-selector button {
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 16px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    
    .date-selector button:hover {
      background: linear-gradient(135deg, #1d4ed8, #1e40af);
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }
    
    .date-selector button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    
    /* 統計情報カード */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 16px;
    }
    
    .stat-card {
      background: white;
      border-radius: 10px;
      padding: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      border: 1px solid #e2e8f0;
      text-align: center;
      transition: transform 0.2s;
    }
    
    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .stat-card .stat-title {
      font-size: 12px;
      color: #64748b;
      margin-bottom: 8px;
      font-weight: 600;
    }
    
    .stat-card .stat-value {
      font-size: 24px;
      font-weight: 700;
      color: #1e293b;
      line-height: 1;
    }
    
    .stat-card.total { border-top: 4px solid #2563eb; }
    .stat-card.avg { border-top: 4px solid #22c55e; }
    .stat-card.max { border-top: 4px solid #f97316; }
    
    /* 日別サマリー */
    .daily-summary {
      margin-top: 16px;
      background: #f8fafc;
      border-radius: 10px;
      padding: 16px;
      border: 1px solid #e2e8f0;
      max-height: 200px;
      overflow-y: auto;
    }
    
    .daily-summary h6 {
      font-size: 13px;
      color: #475569;
      margin: 0 0 12px 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .summary-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
    }
    
    .summary-table th {
      background: #e2e8f0;
      color: #475569;
      font-weight: 600;
      padding: 8px 12px;
      text-align: left;
    }
    
    .summary-table td {
      padding: 8px 12px;
      border-bottom: 1px solid #e2e8f0;
    }
    
    .summary-table tr:hover {
      background: #f1f5f9;
    }
    
    /* ローディング */
    .loading {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: #64748b;
    }
    
    .spinner {
      width: 40px;
      height: 40px;
      border: 4px solid #e2e8f0;
      border-top: 4px solid #2563eb;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-bottom: 12px;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* エラー表示 */
    .error-message {
      background: #fee2e2;
      color: #dc2626;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #fecaca;
      margin-bottom: 16px;
      font-size: 13px;
    }
  </style>
</head>
<body>
<div class="toolbar">
  <!-- 上段：タイトル -->
  <div class="toolbar-title">
    <h1>救急データ分析ダッシュボード</h1>
    <p>過去データに基づく救急出場件数</p>
  </div>

  <!-- 下段：操作 -->
  <div class="toolbar-controls">
    <label for="start">開始日:</label>
    <input id="start" type="date" value="2003-03-27" />

    <label for="end">終了日:</label>
    <input id="end" type="date" value="2003-06-23" />

    <button id="refresh">更新</button>

    <span style="margin-left:auto"></span>
    <span id="todayBadge" class="today-badge"></span>
  </div>
</div>


  <!-- 下段：2×2 グリッド -->
  <div class="grid">
    <!-- ① 時間帯 -->
    <div class="panel">
      <h3>① 時間帯別 出場件数（0–23時）</h3>
      <div class="canvas-wrap"><canvas id="chartLine"></canvas></div>
    </div>

    <!-- ② 病気と事故（左右にカードでbox-shadow） -->
    <div class="panel">
      <h3>② 病気と事故の傷病程度の内訳</h3>

      <div class="chart-pair">
        <div class="chart-box">
          <h4>病気（急病・転院）</h4>
          <div class="canvas-inner"><canvas id="chartDisease"></canvas></div>
        </div>

        <div class="chart-box">
          <h4>事故（交通・一般負傷）</h4>
          <div class="canvas-inner"><canvas id="chartAccident"></canvas></div>
        </div>
      </div>
    </div>

    <!-- ③ 傷病名 上位5件 × 年齢区分 -->
    <div class="panel">
      <h3>③ 傷病名 上位5件 × 年齢区分</h3>
      <div class="canvas-wrap"><canvas id="chartBar"></canvas></div>
    </div>

    <!-- ④ AI予測グラフ -->
    <div class="panel">
      <div class="prediction-container">
        <!-- ヘッダー -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
          <h3>④ 救急需要予測（AI予測）</h3>
          <span style="font-size: 11px; color: #64748b; background: #f1f5f9; padding: 4px 8px; border-radius: 12px;">
            <i class="fas fa-brain"></i> AI予測モデル
          </span>
        </div>
        
        <!-- 日付選択コントロール -->
        <div class="prediction-controls">
          <div class="date-selector">
            <label for="predict-start"><i class="fas fa-calendar-day"></i> 開始日</label>
            <input type="date" id="predict-start" value="">
            
            <label for="predict-end"><i class="fas fa-calendar-day"></i> 終了日</label>
            <input type="date" id="predict-end" value="">
            
            <button id="update-prediction">
              <i class="fas fa-sync-alt"></i> 予測を更新
            </button>
          </div>
        </div>
        
        <!-- 統計情報 -->
        <div class="stats-grid">
          <div class="stat-card total">
            <div class="stat-title">総予測件数</div>
            <div class="stat-value" id="total-prediction">-</div>
          </div>
          <div class="stat-card avg">
            <div class="stat-title">時間当たり平均</div>
            <div class="stat-value" id="avg-per-hour">-</div>
          </div>
          <div class="stat-card max">
            <div class="stat-title">最大（時間別）</div>
            <div class="stat-value" id="max-hour">-</div>
          </div>
        </div>
        
        <!-- グラフエリア -->
        <div class="canvas-wrap">
          <div id="prediction-loading" class="loading" style="display: none;">
            <div class="spinner"></div>
            <p>AIが予測を計算中...</p>
          </div>
          <div id="prediction-error" class="error-message" style="display: none;"></div>
          <canvas id="chartPrediction"></canvas>
        </div>
        
        <!-- 日別サマリー -->
        <div class="daily-summary">
          <h6><i class="fas fa-list"></i> 日別予測サマリー</h6>
          <div id="daily-summary-content">
            <p style="color: #94a3b8; text-align: center; margin: 20px 0;">データを読み込んでいます...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
/* register plugin */
Chart.register(ChartDataLabels);

/* helpers */
const qs = id => document.getElementById(id);
const palette = i => ["#2563eb","#f97316","#22c55e","#e11d48","#a855f7","#06b6d4","#f59e0b"][i%7];

function todayYMD(){
  const now = new Date();
  const tzfix = now.getTimezoneOffset() * 60000;
  return new Date(Date.now()-tzfix).toISOString().slice(0,10);
}
function youbi(d){ return ["日","月","火","水","木","金","土"][new Date(d).getDay()]; }
function setTodayBadge(){ const t=todayYMD(); const b=qs("todayBadge"); if(b) b.textContent=`今日: ${t}（${youbi(t)}）`; }

async function fetchJSON(url){ 
  const r=await fetch(url); 
  if(!r.ok) throw new Error(`${url} → HTTP ${r.status}`); 
  return r.json(); 
}

async function loadLine(){ const s=qs("start").value,e=qs("end").value; return fetchJSON(`data_line.php?start=${s}&end=${e}`); }
async function loadPieDual(){ const s=qs("start").value,e=qs("end").value; return fetchJSON(`data_pie_dual.php?start=${s}&end=${e}`); }
async function loadBar(){ const s=qs("start").value,e=qs("end").value; return fetchJSON(`data_bar.php?start=${s}&end=${e}`); }

let lineChart, barChart, diseaseChart, accidentChart, predictionChart;
const commonOptions={responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true}},plugins:{legend:{display:false}}};

// ==================== AI予測関連の関数 ====================

// 予測データを取得
async function loadPrediction(startDate, endDate) {
    try {
        // ローディング表示
        qs('prediction-loading').style.display = 'flex';
        qs('prediction-error').style.display = 'none';
        
        // 修正：predict_api_final.php を使用
        const response = await fetch(`api/predict_api_final.php?start=${startDate}&end=${endDate}`);
        
        if (!response.ok) {
            throw new Error(`HTTPエラー: ${response.status}`);
        }
        
        const data = await response.json();
        
        // ローディング非表示
        qs('prediction-loading').style.display = 'none';
        
        if (data.status === 'error') {
            throw new Error(data.message);
        }
        
        return data;
        
    } catch (error) {
        qs('prediction-loading').style.display = 'none';
        qs('prediction-error').style.display = 'block';
        qs('prediction-error').textContent = `エラー: ${error.message}`;
        console.error('予測データ取得エラー:', error);
        throw error;
    }
}

// 予測グラフを描画
function drawPredictionChart(predictionData) {
    // 既存のグラフがあれば破棄
    if (predictionChart) {
        predictionChart.destroy();
    }
    
    // データ整形
    const labels = predictionData.data.map(item => {
        const date = new Date(item.datetime);
        return `${date.getMonth()+1}/${date.getDate()} ${date.getHours()}時`;
    });
    
    const data = predictionData.data.map(item => item.prediction);
    
    // 休日データ（アノテーション用）
    const holidayAnnotations = [];
    predictionData.data.forEach((item, index) => {
        if (item.is_holiday === 1) {
            holidayAnnotations.push({
                type: 'point',
                xValue: index,
                yValue: item.prediction,
                backgroundColor: 'rgba(239, 68, 68, 0.3)',
                radius: 6
            });
        }
    });
    
    // グラフ作成
    const ctx = qs('chartPrediction').getContext('2d');
    predictionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '予測需要件数',
                data: data,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                pointBackgroundColor: '#2563eb',
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: { size: 12 },
                        padding: 10
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return `予測: ${context.parsed.y.toFixed(1)}件`;
                        },
                        title: function(tooltipItems) {
                            const date = new Date(predictionData.data[tooltipItems[0].dataIndex].datetime);
                            return `${date.getMonth()+1}/${date.getDate()} ${date.getHours()}時`;
                        }
                    }
                },
                annotation: {
                    annotations: holidayAnnotations
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: '日時',
                        font: { size: 12 }
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 10 }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: '予測件数',
                        font: { size: 12 }
                    },
                    beginAtZero: true,
                    suggestedMax: Math.max(...data) * 1.2,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(0) + '件';
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'nearest'
            }
        }
    });
    
    // 統計情報を更新
    updatePredictionStats(predictionData);
    
    // 日別サマリーを更新
    updateDailySummary(predictionData);
}

// 統計情報を更新
function updatePredictionStats(predictionData) {
  qs('total-prediction').textContent = predictionData.statistics.total.toFixed(1) + '件';
  qs('avg-per-hour').textContent = predictionData.statistics.average_per_hour.toFixed(2) + '件';
  qs('max-hour').textContent = predictionData.statistics.max_hour.toFixed(1) + '件';
}

// 日別サマリーを更新
function updateDailySummary(predictionData) {
  const container = qs('daily-summary-content');
  if (!container) return;
  
  if (!predictionData.daily_summary || predictionData.daily_summary.length === 0) {
    container.innerHTML = '<p style="color: #94a3b8; text-align: center; margin: 20px 0;">データがありません</p>';
    return;
  }
  
  let html = '<table class="summary-table">';
  html += '<thead><tr><th>日付</th><th>曜日</th><th>予測合計</th></tr></thead><tbody>';
  
  predictionData.daily_summary.forEach(day => {
    const date = new Date(day.date);
    const dayOfWeek = youbi(day.date);
    const isHoliday = date.getDay() === 0 || date.getDay() === 6;
    
    html += `<tr>
      <td>${day.date}</td>
      <td style="color: ${isHoliday ? '#ef4444' : '#475569'}">${dayOfWeek}</td>
      <td><strong>${day.total.toFixed(1)}件</strong></td>
    </tr>`;
  });
  
  html += '</tbody></table>';
  container.innerHTML = html;
}

// 初期化：デフォルトの予測期間を設定
function initPredictionDates() {
  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  const nextWeek = new Date(today);
  nextWeek.setDate(nextWeek.getDate() + 7);
  
  qs('predict-start').value = formatDateForInput(tomorrow);
  qs('predict-end').value = formatDateForInput(nextWeek);
}

function formatDateForInput(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

// 予測を更新する関数
async function updatePrediction() {
  const startDate = qs('predict-start').value;
  const endDate = qs('predict-end').value;
  
  if (!startDate || !endDate) {
    alert('開始日と終了日を選択してください');
    return;
  }
  
  if (new Date(startDate) > new Date(endDate)) {
    alert('開始日は終了日よりも前の日付を選択してください');
    return;
  }
  
  try {
    const predictionData = await loadPrediction(startDate, endDate);
    drawPredictionChart(predictionData);
  } catch (error) {
    // エラーはloadPrediction内で処理済み
  }
}

// ==================== 既存のグラフ描画関数 ====================

async function drawAll(){
  const s=qs("start").value,e=qs("end").value;
  
  // ① 時間帯（平日＋土日祝）
  const line = await loadLine();
  lineChart?.destroy();

  lineChart = new Chart(qs("chartLine"), {
      type: "line",
      data: {
          labels: line.hours.map(h => `${h}時`),
          datasets: [
              {
        label: "平日",
        data: line.weekday,
        borderColor: "#2563eb",
        backgroundColor: "rgba(37, 99, 235, 0.12)",
        borderWidth: 2,
        tension: 0.3,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: "#2563eb",
        pointBorderWidth: 0,
        fill: true
      },
      {
        label: "土日・祝日",
        data: line.holiday,
        borderColor: "#ef4444",
        backgroundColor: "rgba(239, 68, 68, 0.12)",
        borderWidth: 2,
        tension: 0.3,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: "#ef4444",
        pointBorderWidth: 0,
        fill: true
      }
          ]
      },
      options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
              y: { beginAtZero: true }
          },
          plugins: {
              legend: { position: "top" },
              datalabels: { display: false }
          }
      }
  });

  /* 固定の傷病程度ラベル定義 */
  const fixedSeverityLabels = ["軽症", "中等症", "重症", "死亡", "その他"];

  /* 共通の色設定 */
  const severityColors = [
    "#2563eb", // 軽症（青）
    "#22c55e", // 中等症（緑）
    "#f97316", // 重症（オレンジ）
    "#ef4444", // 死亡（赤）
    "#94a3b8"  // その他（グレー）
  ];

  // ② 病気・事故
  const dual = await loadPieDual();
  diseaseChart?.destroy();
  accidentChart?.destroy();

  /* 共通オプション */
  const pieOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: "65%",
    plugins: {
      legend: {
        position: "bottom",
        labels: {
          boxWidth: 14,
          padding: 12,
          font: { size: 12 },
          generateLabels: function(chart) {
            const data = chart.data;
            if (data.labels.length && data.datasets.length) {
              return data.labels.map((label, i) => {
                const value = data.datasets[0].data[i];
                const backgroundColor = data.datasets[0].backgroundColor[i];
                return {
                  text: `${label}: ${value}件`,
                  fillStyle: backgroundColor,
                  strokeStyle: backgroundColor,
                  lineWidth: 1,
                  hidden: false,
                  index: i
                };
              });
            }
            return [];
          }
        }
      },
      tooltip: {
        callbacks: {
          label: ctx => {
            const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
            const value = ctx.raw;
            const pct = ((value / total) * 100).toFixed(1);
            return `${ctx.label}: ${value}件 (${pct}%)`;
          }
        }
      },
      datalabels: {
        color: "#111",
        font: { weight: "bold", size: 11 },
        formatter: (v, ctx) => {
          const sum = ctx.chart.data.datasets[0].data.reduce((a,b)=>a+b,0);
          const p = (v / sum * 100).toFixed(1);
          return p >= 5 ? `${p}%` : "";
        }
      }
    }
  };

  /* 病気 */
  diseaseChart = new Chart(qs("chartDisease"), {
    type: "doughnut",
    data: {
      labels: fixedSeverityLabels,
      datasets: [{
        data: [
          dual.byouki.counts[0] || 0,
          dual.byouki.counts[1] || 0,
          dual.byouki.counts[2] || 0,
          dual.byouki.counts[3] || 0,
          dual.byouki.counts[4] || 0
        ],
        backgroundColor: severityColors,
        borderWidth: 0
      }]
    },
    options: pieOptions
  });

  /* 事故 */
  accidentChart = new Chart(qs("chartAccident"), {
    type: "doughnut",
    data: {
      labels: fixedSeverityLabels,
      datasets: [{
        data: [
          dual.jiko.counts[0] || 0,
          dual.jiko.counts[1] || 0,
          dual.jiko.counts[2] || 0,
          dual.jiko.counts[3] || 0,
          dual.jiko.counts[4] || 0
        ],
        backgroundColor: severityColors,
        borderWidth: 0
      }]
    },
    options: pieOptions
  });

  // ③ 棒グラフ
  const bar = await loadBar();
  barChart?.destroy();
  const datasetsWithTotals = bar.datasets.map(ds => ({
    ...ds,
    label: `${ds.label} ${ds.data.reduce((a,b)=>a+(b||0),0)}件`
  }));
  barChart = new Chart(qs("chartBar"),{
    type:"bar",
    data:{
      labels: bar.labels,
      datasets: datasetsWithTotals.map((ds,i)=>({
        ...ds,
        backgroundColor: palette(i),
        borderRadius: 6
      }))
    },
    options:{
      responsive:true,
      maintainAspectRatio:false,
      scales:{
        x:{
          ticks:{ maxRotation:45, minRotation:45 },
          title:{ display:true, text:"傷病名" }
        },
        y:{
          beginAtZero:true,
          title:{ display:true, text:"件数" },
          grid:{ color:"rgba(200,200,200,0.2)" }
        }
      },
      plugins:{
        legend:{ position:"bottom" },
        tooltip:{
          callbacks:{
            label: c => `${c.dataset.label.replace(/\s\d+件$/,'')}: ${c.formattedValue}件`
          }
        },
        datalabels: { display: false }
      }
    }
  });
}

// ==================== イベントリスナーの設定 ====================

// ページ読み込み時の初期化
document.addEventListener('DOMContentLoaded', function() {
  // 既存のイベントリスナー
  qs("refresh").addEventListener("click", drawAll);
  setTodayBadge();
  drawAll();
  
  // 予測グラフの初期化
  initPredictionDates();
  
  // 予測更新ボタンのイベントリスナー
  qs('update-prediction').addEventListener('click', updatePrediction);
  
  // 初期予測データの読み込み（少し遅延して）
  setTimeout(() => {
    updatePrediction();
  }, 500);
});

// リサイズイベント
window.addEventListener("resize", () => {
  lineChart?.resize(); 
  diseaseChart?.resize(); 
  accidentChart?.resize(); 
  barChart?.resize(); 
  predictionChart?.resize();
});
</script>
</body>
</html>