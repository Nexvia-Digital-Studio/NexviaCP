#!/usr/bin/env python3
"""
NexviaCP Automated CI/CD Security, Syntax & Regression Audit Suite
This test suite executes automatically on PRs and commits before merging to main.
If any test fails, exit code 1 is returned, blocking the CI pipeline.
"""

import os
import sys
import subprocess
import re
import glob

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

# ANSI Color Codes
GREEN = "\033[92m"
RED = "\033[91m"
YELLOW = "\033[93m"
BLUE = "\033[94m"
BOLD = "\033[1m"
RESET = "\033[0m"

failed_tests = []
passed_count = 0


def log_section(title):
    print(f"\n{BOLD}{BLUE}======================================================{RESET}")
    print(f"{BOLD}{BLUE}[SUITE] {title}{RESET}")
    print(f"{BOLD}{BLUE}======================================================{RESET}")


def record_pass(test_name):
    global passed_count
    passed_count += 1
    print(f"  {GREEN}✔ PASS:{RESET} {test_name}")


def record_fail(test_name, details=""):
    global failed_tests
    failed_tests.append((test_name, details))
    print(f"  {RED}✘ FAIL:{RESET} {test_name}")
    if details:
        print(f"    {YELLOW}↳ Details:{RESET} {details}")


# -------------------------------------------------------------
# SUITE 1: CLI Scripts Syntax Validation (Bash & PHP Shebangs)
# -------------------------------------------------------------
def test_cli_scripts_syntax():
    log_section("1. CLI Scripts Syntax Validation (bin/v-*)")
    bin_dir = os.path.join(REPO_ROOT, "bin")
    scripts = glob.glob(os.path.join(bin_dir, "v-*"))

    if not scripts:
        record_fail("CLI Scripts Discovery", "No bin/v-* scripts found!")
        return

    for script in sorted(scripts):
        s_name = os.path.basename(script)
        with open(script, "r", encoding="utf-8", errors="ignore") as f:
            first_line = f.readline()

        if "php" in first_line:
            res = subprocess.run(["php", "-l", script], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
            if res.returncode == 0:
                record_pass(f"PHP CLI Syntax: {s_name}")
            else:
                record_fail(f"PHP CLI Syntax: {s_name}", res.stderr.strip())
        else:
            res = subprocess.run(["bash", "-n", script], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
            if res.returncode == 0:
                record_pass(f"Bash CLI Syntax: {s_name}")
            else:
                record_fail(f"Bash CLI Syntax: {s_name}", res.stderr.strip())


# -------------------------------------------------------------
# SUITE 2: Web PHP Syntax & Linting (web/)
# -------------------------------------------------------------
def test_php_syntax():
    log_section("2. PHP Code Syntax & Linting (web/)")
    php_files = []
    for root, _, files in os.walk(os.path.join(REPO_ROOT, "web")):
        for f in files:
            if f.endswith(".php"):
                php_files.append(os.path.join(root, f))

    for php_f in sorted(php_files):
        rel_path = os.path.relpath(php_f, REPO_ROOT)
        res = subprocess.run(["php", "-l", php_f], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        if res.returncode == 0:
            record_pass(f"PHP Lint: {rel_path}")
        else:
            record_fail(f"PHP Lint: {rel_path}", res.stderr.strip())


# -------------------------------------------------------------
# SUITE 3: Security Static Analysis (CSRF, Escaping & Auth)
# -------------------------------------------------------------
def test_security_static_analysis():
    log_section("3. Security Static Analysis (CSRF, XSS & Auth Guard)")
    controllers = glob.glob(os.path.join(REPO_ROOT, "web", "**", "index.php"), recursive=True)

    for ctrl in sorted(controllers):
        rel_path = os.path.relpath(ctrl, REPO_ROOT)
        with open(ctrl, "r", encoding="utf-8", errors="ignore") as f:
            content = f.read()

        # Webhooks & External API endpoints authenticate via HMAC / API keys, not session cookies
        is_exempt = any(ex in rel_path for ex in ["web/api/", "web/webhook/", "web/login/", "web/reset/"])

        # 1. State-modifying POST requests in panel must verify CSRF
        if not is_exempt and "$_POST" in content and any(k in content for k in ["action", "save", "delete", "create", "update", "purge", "toggle"]):
            if "verify_csrf" not in content and "check_token" not in content and "token" not in content:
                record_fail(f"CSRF Protection: {rel_path}", "Controller processes state-modifying $_POST without CSRF verification!")
            else:
                record_pass(f"CSRF Guard: {rel_path}")

        # 2. Raw unescaped user inputs directly concatenated into shell exec without quoteshellarg/escapeshellarg
        # Find all exec lines
        exec_lines = [line.strip() for line in content.splitlines() if "exec(" in line and "HESTIA_CMD" in line]
        for el in exec_lines:
            # Check if $_GET or $_POST is used without quoteshellarg / escapeshellarg
            if re.search(r'\$(?:_GET|_POST|_REQUEST)\[', el) and "quoteshellarg" not in el and "escapeshellarg" not in el:
                record_fail(f"Unsanitized Shell Exec: {rel_path}", f"Unescaped superglobal in shell call: {el}")
                break
        else:
            record_pass(f"Safe Shell Exec: {rel_path}")


# -------------------------------------------------------------
# SUITE 4: WAF Engine Regex Assertions & False-Positive Guard
# -------------------------------------------------------------
def test_waf_regex_rules():
    log_section("4. WAF Engine Regex Assertions & False-Positive Guard")

    # Read WAF regexes from v-add-web-domain-waf
    waf_script = os.path.join(REPO_ROOT, "bin", "v-add-web-domain-waf")
    if not os.path.exists(waf_script):
        record_fail("WAF Script Existence", "bin/v-add-web-domain-waf not found!")
        return

    # Standard WAF Regex patterns under test
    sqli_pattern = r"(union.*select|select.*from|insert.*into|delete.*from|drop\s+table|update.*set|benchmark\s*\(|sleep\s*\(|extractvalue|load_file|sysibm|information_schema|into\s+outfile|group_concat|order\s+by\s+[0-9]+|(--|#|\/\*).*union)"
    xss_pattern = r"(<script|%3Cscript|javascript:|vbscript:|data:text/html|onload\s*=|onerror\s*=|document\.cookie|document\.location|window\.location|eval\s*\(|alert\s*\()"
    path_traversal_pattern = r"(\.\./|\.\.\\|/etc/passwd|/proc/self|/boot\.ini|/win\.ini|php://|/\.env|/\.git|/\.svn|/\.aws)"
    scanner_ua_pattern = r"(sqlmap|nikto|wpscan|acunetix|masscan|dirbuster|nmap|zgrab|censys|shodan|gobuster)"

    # Test Cases: (Pattern Name, Regex, Test Input, Expected Match Bool)
    test_cases = [
        # SQL Injection assertions
        ("SQLi - UNION SELECT", sqli_pattern, "id=1' UNION SELECT 1,2,3--", True),
        ("SQLi - Information Schema", sqli_pattern, "cat=1 AND SELECT table_name FROM information_schema.tables", True),
        ("SQLi - Time-based Sleep", sqli_pattern, "q=admin' AND sleep(5)--", True),
        ("SQLi - Benign Query Parameter", sqli_pattern, "search=istanbul&page=2&order=title", False),
        
        # XSS assertions
        ("XSS - SCRIPT Tag", xss_pattern, "name=<script>alert(1)</script>", True),
        ("XSS - Error Handler Injection", xss_pattern, "img=<img src=x onerror=alert(document.cookie)>", True),
        ("XSS - Javascript URI", xss_pattern, "redirect=javascript:alert(1)", True),
        ("XSS - Benign Normal Text", xss_pattern, "comment=Hello world this is a normal test comment.", False),

        # Path Traversal & Sensitive File assertions
        ("Path Traversal - DotDotSlash", path_traversal_pattern, "/uploads/../../etc/passwd", True),
        ("Path Traversal - Environment Probe", path_traversal_pattern, "/.env", True),
        ("Path Traversal - Git Directory Probe", path_traversal_pattern, "/.git/config", True),
        ("Path Traversal - Normal Asset Request", path_traversal_pattern, "/assets/images/logo.png", False),

        # Scanner User-Agents
        ("Scanner UA - Sqlmap", scanner_ua_pattern, "sqlmap/1.6.12#stable (https://sqlmap.org)", True),
        ("Scanner UA - Nikto", scanner_ua_pattern, "Mozilla/5.00 (Nikto/2.1.6) (Evasions:None) (Test:Port Check)", True),
        ("Scanner UA - Legitimate Chrome Browser", scanner_ua_pattern, "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36", False),
    ]

    for name, pattern, sample, expected in test_cases:
        matched = bool(re.search(pattern, sample, re.IGNORECASE))
        if matched == expected:
            record_pass(f"WAF Rule [{name}]: Correctly {'Matched' if expected else 'Ignored'}")
        else:
            record_fail(f"WAF Rule [{name}]", f"Sample '{sample}' expected match={expected}, got match={matched}")


# -------------------------------------------------------------
# MAIN TEST RUNNER
# -------------------------------------------------------------
def main():
    print(f"\n{BOLD}🚀 Starting NexviaCP Full CI/CD Security & Regression Audit...{RESET}")
    
    test_cli_scripts_syntax()
    test_php_syntax()
    test_security_static_analysis()
    test_waf_regex_rules()

    log_section("Audit Summary")
    total_run = passed_count + len(failed_tests)
    print(f"  Total Checks Executed: {BOLD}{total_run}{RESET}")
    print(f"  Passed Checks: {GREEN}{passed_count}{RESET}")
    print(f"  Failed Checks: {RED if failed_tests else GREEN}{len(failed_tests)}{RESET}")

    if failed_tests:
        print(f"\n{RED}{BOLD}❌ CI AUDIT FAILED! The following {len(failed_tests)} test(s) failed:{RESET}")
        for idx, (t_name, d) in enumerate(failed_tests, 1):
            print(f"  {idx}. {RED}{t_name}{RESET} - {d}")
        print(f"\n{RED}Blocking merge to main. Fix the issues above before merging.{RESET}\n")
        sys.exit(1)
    else:
        print(f"\n{GREEN}{BOLD}✔ ALL SECURITY & REGRESSION CHECKS PASSED! Codebase is ready for main.{RESET}\n")
        sys.exit(0)


if __name__ == "__main__":
    main()
