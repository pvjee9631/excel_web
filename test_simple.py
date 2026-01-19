# test_simple.py
import json
import sys

def main():
    # 単純なJSONを出力
    result = {
        "status": "success",
        "message": "Hello from Python",
        "data": [
            {"time": "2024-12-01 08:00:00", "value": 1.5},
            {"time": "2024-12-01 09:00:00", "value": 2.0}
        ]
    }
    
    # JSONのみを出力（余分な出力なし）
    print(json.dumps(result))
    
    # エラーはstderrに
    print("これはstderrのメッセージです", file=sys.stderr)

if __name__ == "__main__":
    main()