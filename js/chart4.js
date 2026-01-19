// js/chart4.js
class PredictionChart {
    constructor(containerId) {
        this.containerId = containerId;
        this.chart = null;
        this.startDate = null;
        this.endDate = null;
        
        // デフォルトの予測期間（明日から1週間）
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const nextWeek = new Date();
        nextWeek.setDate(nextWeek.getDate() + 7);
        
        this.startDate = this.formatDate(tomorrow);
        this.endDate = this.formatDate(nextWeek);
    }
    
    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // データ取得
    async fetchPredictionData() {
        const url = `api/predict_api.php?start=${this.startDate}&end=${this.endDate}`;
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.status === 'success') {
                return data;
            } else {
                throw new Error(data.message || '予測データの取得に失敗しました。');
            }
        } catch (error) {
            console.error('Error fetching prediction data:', error);
            throw error;
        }
    }
    
    // グラフ描画
    renderChart(predictionData) {
        const ctx = document.getElementById(this.containerId).getContext('2d');
        
        // 既存のグラフを破棄
        if (this.chart) {
            this.chart.destroy();
        }
        
        // データ整形
        const labels = predictionData.data.map(item => {
            const date = new Date(item.datetime);
            return `${date.getMonth()+1}/${date.getDate()} ${date.getHours()}時`;
        });
        
        const data = predictionData.data.map(item => item.prediction);
        
        // 休日データ
        const holidayPoints = predictionData.data
            .filter(item => item.is_holiday === 1)
            .map((item, index) => ({
                x: labels.indexOf(`${new Date(item.datetime).getMonth()+1}/${new Date(item.datetime).getDate()} ${new Date(item.datetime).getHours()}時`),
                y: item.prediction
            }));
        
        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: '予測需要件数',
                    data: data,
                    borderColor: '#ff6b6b',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointBackgroundColor: '#ff6b6b'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return `予測: ${context.parsed.y.toFixed(1)}件`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: '日時'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: '予測件数'
                        },
                        beginAtZero: true,
                        suggestedMax: 10
                    }
                },
                annotation: {
                    annotations: holidayPoints.map(point => ({
                        type: 'point',
                        xValue: point.x,
                        yValue: point.y,
                        backgroundColor: 'rgba(255, 0, 0, 0.3)',
                        radius: 6
                    }))
                }
            }
        });
        
        // 統計情報を表示
        this.updateStats(predictionData);
    }
    
    // 統計情報更新
    updateStats(data) {
        document.getElementById('total-prediction').textContent = 
            data.statistics.total.toFixed(1) + '件';
        document.getElementById('avg-per-hour').textContent = 
            data.statistics.average_per_hour.toFixed(2) + '件';
        document.getElementById('max-hour').textContent = 
            data.statistics.max_hour.toFixed(1) + '件';
        
        // 日別サマリーを表示
        const summaryContainer = document.getElementById('daily-summary');
        if (summaryContainer) {
            let html = '<table class="table table-sm">';
            html += '<thead><tr><th>日付</th><th>予測合計</th></tr></thead><tbody>';
            
            data.daily_summary.forEach(day => {
                html += `<tr>
                    <td>${day.date}</td>
                    <td><strong>${day.total.toFixed(1)}件</strong></td>
                </tr>`;
            });
            
            html += '</tbody></table>';
            summaryContainer.innerHTML = html;
        }
    }
    
    // 日付変更イベント
    setupDateControls() {
        const startInput = document.getElementById('start-date');
        const endInput = document.getElementById('end-date');
        const updateBtn = document.getElementById('update-prediction');
        
        if (startInput && endInput) {
            startInput.value = this.startDate;
            endInput.value = this.endDate;
            
            updateBtn.addEventListener('click', async () => {
                this.startDate = startInput.value;
                this.endDate = endInput.value;
                
                // ローディング表示
                updateBtn.disabled = true;
                updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 更新中...';
                
                try {
                    const data = await this.fetchPredictionData();
                    this.renderChart(data);
                } catch (error) {
                    alert(error.message);
                } finally {
                    updateBtn.disabled = false;
                    updateBtn.textContent = '予測を更新';
                }
            });
        }
    }
    
    // 初期化
    async init() {
        try {
            // データ取得
            const data = await this.fetchPredictionData();
            
            // グラフ描画
            this.renderChart(data);
            
            // 日付コントロール設定
            this.setupDateControls();
            
        } catch (error) {
            console.error('Failed to initialize prediction chart:', error);
            document.getElementById(this.containerId).innerHTML = 
                `<div class="alert alert-danger">${error.message}</div>`;
        }
    }
}

// ページ読み込み時に実行
document.addEventListener('DOMContentLoaded', function() {
    const predictionChart = new PredictionChart('predictionChart');
    predictionChart.init();
});