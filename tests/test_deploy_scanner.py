#!/usr/bin/env python3
import subprocess
import tempfile
import os
import json
import shutil
import sys

def run_tests():
    test_dir = tempfile.mkdtemp(prefix="nxv_test_")
    try:
        # 1. Test pnpm project
        pnpm_dir = os.path.join(test_dir, "pnpm_app")
        os.makedirs(pnpm_dir)
        with open(os.path.join(pnpm_dir, "package.json"), "w") as f:
            json.dump({"name": "pnpm-test", "dependencies": {"express": "^4.18.2"}}, f)
        with open(os.path.join(pnpm_dir, "pnpm-lock.yaml"), "w") as f:
            f.write("lockfileVersion: 5.4\n")
        
        out = subprocess.check_output([sys.executable, "lib/nexvia-repo-scan.py", pnpm_dir], text=True, encoding="utf-8")
        res = json.loads(out)
        assert res["ok"] == True
        assert res["package_manager"] == "pnpm"
        print("[PASS] PNPM detection")

        # 2. Test bun project
        bun_dir = os.path.join(test_dir, "bun_app")
        os.makedirs(bun_dir)
        with open(os.path.join(bun_dir, "package.json"), "w") as f:
            json.dump({"name": "bun-test", "dependencies": {"hono": "^3.0.0"}}, f)
        with open(os.path.join(bun_dir, "bun.lockb"), "w") as f:
            f.write("lock")
        
        out = subprocess.check_output([sys.executable, "lib/nexvia-repo-scan.py", bun_dir], text=True, encoding="utf-8")
        res = json.loads(out)
        assert res["ok"] == True
        assert res["package_manager"] == "bun"
        print("[PASS] Bun detection")

        # 3. Test yarn project
        yarn_dir = os.path.join(test_dir, "yarn_app")
        os.makedirs(yarn_dir)
        with open(os.path.join(yarn_dir, "package.json"), "w") as f:
            json.dump({"name": "yarn-test", "dependencies": {"express": "^4.18.2"}}, f)
        with open(os.path.join(yarn_dir, "yarn.lock"), "w") as f:
            f.write("# yarn lockfile v1\n")
        
        out = subprocess.check_output([sys.executable, "lib/nexvia-repo-scan.py", yarn_dir], text=True, encoding="utf-8")
        res = json.loads(out)
        assert res["ok"] == True
        assert res["package_manager"] == "yarn"
        print("[PASS] Yarn detection")

        # 4. Test .NET project with TargetFramework
        dotnet_dir = os.path.join(test_dir, "dotnet_app")
        os.makedirs(dotnet_dir)
        with open(os.path.join(dotnet_dir, "MyApp.csproj"), "w") as f:
            f.write("<Project Sdk=\"Microsoft.NET.Sdk.Web\"><PropertyGroup><TargetFramework>net8.0</TargetFramework></PropertyGroup></Project>")
        
        out = subprocess.check_output([sys.executable, "lib/nexvia-repo-scan.py", dotnet_dir], text=True, encoding="utf-8")
        res = json.loads(out)
        assert res["ok"] == True
        assert res["platform"]["mode"] == "dotnet"
        assert res["target_framework"] == "net8.0"
        assert res["output_directory"] == "publish"
        print("[PASS] .NET detection with net8.0 & publish output_directory")

        # 5. Test Prisma ORM project
        prisma_dir = os.path.join(test_dir, "prisma_app")
        os.makedirs(os.path.join(prisma_dir, "prisma"))
        with open(os.path.join(prisma_dir, "package.json"), "w") as f:
            json.dump({"name": "prisma-test", "dependencies": {"@prisma/client": "^5.0.0"}}, f)
        with open(os.path.join(prisma_dir, "prisma", "schema.prisma"), "w") as f:
            f.write("datasource db {\n  provider = \"mysql\"\n  url = env(\"DATABASE_URL\")\n}\n")
        
        out = subprocess.check_output([sys.executable, "lib/nexvia-repo-scan.py", prisma_dir], text=True, encoding="utf-8")
        res = json.loads(out)
        assert res["ok"] == True
        assert res["database"]["orm"] == "prisma"
        assert res["database"]["engine"] == "mysql"
        assert res["database"]["auto"] == True
        print("[PASS] Prisma detection with MySQL auto-provisioning plan")

        # 6. Test React / Vite output directory
        vite_dir = os.path.join(test_dir, "vite_app")
        os.makedirs(vite_dir)
        with open(os.path.join(vite_dir, "package.json"), "w") as f:
            json.dump({"name": "vite-test", "dependencies": {"react": "^18.0.0", "vite": "^5.0.0"}}, f)
        
        out = subprocess.check_output([sys.executable, "lib/nexvia-repo-scan.py", vite_dir], text=True, encoding="utf-8")
        res = json.loads(out)
        assert res["ok"] == True
        assert res["output_directory"] == "dist"
        print("[PASS] Vite output_directory detection (dist)")

        # 7. Test Next.js static export
        next_dir = os.path.join(test_dir, "next_app")
        os.makedirs(next_dir)
        with open(os.path.join(next_dir, "package.json"), "w") as f:
            json.dump({"name": "next-test", "dependencies": {"next": "^14.0.0", "react": "^18.0.0"}, "scripts": {"build": "next build && next export"}}, f)
        
        out = subprocess.check_output([sys.executable, "lib/nexvia-repo-scan.py", next_dir], text=True, encoding="utf-8")
        res = json.loads(out)
        assert res["ok"] == True
        assert res["output_directory"] == "out"
        print("[PASS] Next.js export output_directory detection (out)")

        # 8. Test --summary CLI option
        out_summary = subprocess.check_output([sys.executable, "lib/nexvia-repo-scan.py", "--summary", vite_dir], text=True, encoding="utf-8").strip()
        assert "React / Vite" in out_summary
        print("[PASS] --summary flag output:", out_summary)

        # 9. Test missing directory error JSON
        out_err = subprocess.check_output([sys.executable, "lib/nexvia-repo-scan.py", os.path.join(test_dir, "nonexistent")], text=True, encoding="utf-8")
        res_err = json.loads(out_err)
        assert res_err["ok"] == False
        assert "error" in res_err
        print("[PASS] Missing directory error JSON payload:", res_err)

        print("\nALL SCANNER TESTS PASSED SUCCESSFULLY!")
    finally:
        shutil.rmtree(test_dir, ignore_errors=True)

if __name__ == "__main__":
    run_tests()
