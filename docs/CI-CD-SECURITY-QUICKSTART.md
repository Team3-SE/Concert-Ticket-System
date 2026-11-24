# CI/CD Security Pipeline - Quickstart Guide

This quick reference guide covers the essential steps for configuring, running, and troubleshooting the automated security pipeline.

---

## 1. Required GitHub Secrets

Configure these under **Repository Settings** $\to$ **Secrets and variables** $\to$ **Actions**:

| Secret Name | Required? | Source / Description |
|---|---|---|
| `SONAR_TOKEN` | **Yes** | User Token generated from [SonarQube Cloud](https://sonarcloud.io) under **My Account** $\to$ **Security**. |
| `APP_ENV_FILE` | Optional | Custom `.env` environment variables if runner overrides are needed. |

---

## 2. Required GitHub Repository Settings

1. **Actions Permissions**:
   - Go to **Settings** $\to$ **Actions** $\to$ **General**.
   - Set Workflow permissions to **Read repository contents and packages permissions**.
2. **Branch Protection**:
   - Go to **Settings** $\to$ **Branches** $\to$ Edit rule for `development` / `main`.
   - Enable **Require a pull request before merging** (with at least 1 approval).
   - Enable **Require status checks to pass before merging**:
     - Check: `4. Security Gate / Overall Status` *(Primary unified status check)*
     - Check: `1. Validate & Test`
     - Check: `2. SonarQube Cloud SAST`
     - Check: `3. OWASP ZAP DAST Analysis`

---

## 3. Git Quick Commands

```bash
# 1. Update your local development branch
git checkout development
git pull origin development

# 2. Create a new feature branch
git checkout -b feature/your-feature-name

# 3. Commit your changes
git add .
git commit -m "feat: implement your feature securely"

# 4. Push your branch to GitHub
git push -u origin feature/your-feature-name

# 5. Open a Pull Request on GitHub against 'development'
```

---

## 4. What CI Checks Will Run

When you open or push to a Pull Request, the pipeline runs 4 automated stages:

| Job Name | What It Evaluates | Merge Blocking? |
|---|---|---|
| `1. Validate & Test` | Code styling (Pint), linting, and automated unit/feature tests (PHPUnit). | **Yes** |
| `2. SonarQube Cloud SAST` | Static source code scanning for bugs, vulnerabilities, code smells, and Sonar Quality Gate. | **Yes** |
| `3. OWASP ZAP DAST Analysis` | Starts local app, runs OWASP ZAP baseline web security scan, and evaluates findings against policy. | **Yes** (if findings $\ge$ High) |
| `4. Security Gate / Overall Status` | Aggregates all results and sets the PR merge readiness status. | **Yes** |

---

## 5. How to Resolve a Failed Security Check

### If `1. Validate & Test` Fails:
- Run Pint locally: `cd paymock && vendor/bin/pint`
- Run PHPUnit tests: `cd paymock && php artisan test`
- Commit and push the fixes.

### If `2. SonarQube Cloud SAST` Fails:
- Click the **SonarQube Code Analysis** link on the PR or open [SonarCloud](https://sonarcloud.io).
- Look at the failed Quality Gate criteria (e.g. security hotspot, hardcoded secret, new bugs).
- Fix the flagged code lines, commit, and push.

### If `3. OWASP ZAP DAST Analysis` Fails:
- Review the summary table in the GitHub Actions **Step Summary**.
- If High/Critical alerts are present (e.g. missing security headers or information disclosure):
  - Add missing HTTP headers (e.g., CSP, X-Content-Type-Options) in Laravel middleware.
  - Or tune the rule in `.zap/rules.tsv` if it is a documented false positive.
- Commit and push the fix.

---

## 6. How to Download Scan Reports

1. Navigate to the **Actions** tab on GitHub.
2. Click on the workflow run for your commit or PR.
3. Scroll to the bottom to the **Artifacts** section.
4. Click **`owasp-zap-dast-reports`** to download `report_html.html` and `report_json.json`.
5. Open `report_html.html` in your web browser for the full vulnerability breakdown with remediation advice.
