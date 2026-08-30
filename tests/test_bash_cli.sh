#!/usr/bin/env bash
set -e

export HESTIA="$(pwd)"
export HESTIA_CMD=""

echo "=== 1. Test non-root JSON error formatting ==="
out1=$(bash bin/v-analyze-repo . main)
echo "Output: $out1"
echo "$out1" | python3 -c "import sys, json; d=json.load(sys.stdin); assert d['ok'] == False; assert 'root' in d['error']; print('JSON error handled correctly with code 0')"

echo "=== 2. Test direct scanner invocation (same core logic) ==="
out2=$(python3 lib/nexvia-repo-scan.py .)
echo "$out2" | python3 -c "import sys, json; d=json.load(sys.stdin); assert d['ok'] == True; print('Scanner JSON output verified')"

echo "=== 3. Test scanner --summary flag ==="
out3=$(python3 lib/nexvia-repo-scan.py --summary .)
echo "Summary: $out3"

echo "=== 4. Test missing directory scanner error JSON ==="
out4=$(python3 lib/nexvia-repo-scan.py /nonexistent/dir/12345)
echo "Error output: $out4"
echo "$out4" | python3 -c "import sys, json; d=json.load(sys.stdin); assert d['ok'] == False; print('Missing dir error JSON verified')"

echo "=== ALL BASH CLI & SCANNER TESTS PASSED! ==="
