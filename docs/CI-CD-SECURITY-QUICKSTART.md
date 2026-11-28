# CI/CD Security Pipeline - Quickstart Guide

This quick reference guide covers the essential steps for configuring, running, and troubleshooting the automated security pipeline.

---

## 1. Required GitHub Secrets

Configure these under **Repository Settings** $\to$ **Secrets and variables** $\to$ **Actions**:

| Secret Name | Required? | Source / Description |
|---|---|---|
| `SONAR_TOKEN` | **Yes** | User Token generated from [SonarQube Cloud](https://sonarcloud.io) under **My Account** $\to$ **Security**. |

---

## 2. Required GitHub Repository Settings

1. **Actions Permissions**:
   - Go to **Settings** $\to$ **Actions** $\to$ **General**.
   - Set Workflow permissions to **Read repository contents and packages permissions**.
2. **Branch Protection**:
   - Go to **Settings** $\to$ **Branches** $\to$ Edit rule for `development-security`.
   - Enable **Require a pull request before merging** (with at least 1 approval).
   - Enable **Require status checks to pass before merging**:
     - Select the SonarQube SAST, OWASP ZAP DAST, and Security Gate checks after the workflow has run once.

---

## 3. Git Quick Commands

```bash
# 1. Update the branch used for Pull Requests
git checkout development-security
git pull origin development-security

# 2. Create a new feature branch
git checkout -b feature/your-feature-name

# 3. Commit your changes
git add .
git commit -m "feat: implement your feature securely"

# 4. Push your branch to GitHub
git push -u origin feature/your-feature-name

# 5. Open a Pull Request on GitHub against 'development-security'
```

The current workflow also runs on pushes to `test-workflow` and can be started manually with `workflow_dispatch`.

---

## 4. What CI Checks Will Run

When you open or update a Pull Request targeting `development-security`, the pipeline scans the Laravel application at the repository root:

| Job Name | What It Evaluates | Merge Blocking? |
|---|---|---|
| `1. SonarQube Cloud SAST` | Static source code scanning for bugs, vulnerabilities, code smells, and Sonar Quality Gate. | **Yes** |
| `2. OWASP ZAP DAST Analysis` | Builds the root app, initializes its MySQL schema, starts the app, and runs an OWASP ZAP baseline scan. | **Yes** (if findings $\ge$ High) |
| `3. Security Gate & AI Failure Report` | Aggregates scan results and publishes the overall PR status and reports. | **Yes** |

---

## 5. How to Resolve a Failed Security Check

### If SonarQube Cloud SAST Fails:
- Open the **1. SonarQube Cloud SAST** job summary and use its SonarCloud project or issues links.
- Review the failed Quality Gate conditions shown in the summary (e.g. security rating, coverage, or duplication).
- Fix the flagged code lines, commit, and push.

### If OWASP ZAP DAST Analysis Fails:
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
4. Download the `step2-owasp-zap-dast-reports-*` artifact for raw scan results, or `step3-ai-full-report-*` for the combined HTML and JSON report.
5. Open `pipeline-report.html` from the combined report artifact, or `report_html.html` from the raw ZAP artifact.
