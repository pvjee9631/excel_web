# yosoku.py - 数値フォーマット修正版
import json
import sys
from datetime import datetime, timedelta

def format_value(value):
    """値を適切な型で返す"""
    if isinstance(value, float):
        # 浮動小数点数の場合は小数点以下2桁に丸める
        return round(value, 2)
    return value

def generate_prediction(start_date_str, end_date_str):
    """予測データを生成"""
    try:
        start_date = datetime.strptime(start_date_str, '%Y-%m-%d')
        end_date = datetime.strptime(end_date_str, '%Y-%m-%d')
        
        days_diff = (end_date - start_date).days + 1
        if days_diff > 30:
            return {
                'status': 'error',
                'message': f'予測期間が長すぎます（{days_diff}日）。30日以内にしてください。'
            }
        
        data = []
        daily_summary = []
        current_date = start_date
        
        while current_date <= end_date:
            date_str = current_date.strftime('%Y-%m-%d')
            is_weekend = current_date.weekday() >= 5
            month = current_date.month
            
            day_total = 0.0
            
            for hour in range(24):
                base = 1.0
                
                if 8 <= hour <= 9:
                    base *= 1.8
                elif 17 <= hour <= 18:
                    base *= 1.6
                elif hour >= 22 or hour <= 5:
                    base *= 0.4
                elif 10 <= hour <= 16:
                    base *= 1.2
                
                if is_weekend:
                    base *= 1.3
                
                if month in [7, 8]:
                    base *= 1.2
                elif month in [12, 1]:
                    base *= 1.3
                
                prediction = round(base, 2)
                day_total += prediction
                
                data.append({
                    'datetime': f'{date_str} {hour:02d}:00:00',
                    'hour': hour,  # 整数として保持
                    'prediction': prediction,  # 少数
                    'is_holiday': 1 if is_weekend else 0  # 整数
                })
            
            daily_summary.append({
                'date': date_str,
                'total': round(day_total, 1)
            })
            
            current_date += timedelta(days=1)
        
        # 統計情報
        predictions = [item['prediction'] for item in data]
        total = sum(predictions)
        count = len(predictions)
        
        result = {
            'status': 'success',
            'prediction_period': {
                'start': start_date_str,
                'end': end_date_str
            },
            'data': data,
            'daily_summary': daily_summary,
            'statistics': {
                'total': round(total, 1),
                'average_per_hour': round(total / count, 2),
                'max_hour': round(max(predictions), 1),
                'min_hour': round(min(predictions), 1)
            }
        }
        
        return result
        
    except Exception as e:
        return {
            'status': 'error',
            'message': f'予測生成エラー: {str(e)}'
        }

def main():
    import argparse
    
    parser = argparse.ArgumentParser(description='救急需要予測')
    parser.add_argument('--start', default='2024-12-01')
    parser.add_argument('--end', default='2024-12-03')
    parser.add_argument('--json', action='store_true')
    
    args = parser.parse_args()
    
    # 予測実行
    result = generate_prediction(args.start, args.end)
    
    # 数値の型を確実に設定
    def clean_types(obj):
        if isinstance(obj, dict):
            return {k: clean_types(v) for k, v in obj.items()}
        elif isinstance(obj, list):
            return [clean_types(item) for item in obj]
        elif isinstance(obj, float):
            # 浮動小数点数は確実に少数2桁
            return round(obj, 2)
        elif isinstance(obj, int) and not isinstance(obj, bool):
            # 整数はそのまま
            return obj
        else:
            return obj
    
    result = clean_types(result)
    
    # JSON出力（デフォルトのシリアライザーを使用）
    print(json.dumps(result, ensure_ascii=False))

if __name__ == "__main__":
    main()