#!/usr/bin/env python3
"""
CI/CD Pipeline Failure & Security Report Generator (HTML + AI-Ready JSON)

Collects pipeline failure data, SonarQube Cloud quality gate results, OWASP ZAP DAST findings,
and test suite failures. Generates a normalized machine-readable JSON report and an interactive,
standalone HTML report for developers and future AI Code Fix Agents.

Standard library only. No external dependencies required.
"""

import os
import sys
import json
import html
import re
import argparse
import datetime
import urllib.request
import urllib.error
import xml.etree.ElementTree as ET
from typing import Dict, List, Any, Optional, Tuple


class SecretMasker:
    """Masks secrets, tokens, and sensitive credentials in text and structures."""

    PATTERNS = [
        re.compile(r"ghp_[A-Za-z0-9_]{20,}", re.IGNORECASE),
        re.compile(r"github_pat_[A-Za-z0-9_]{20,}", re.IGNORECASE),
        re.compile(r"sqp_[a-f0-9]{40}", re.IGNORECASE),
        re.compile(r"Bearer\s+[A-Za-z0-9\-._~+/]+=*", re.IGNORECASE),
        re.compile(r"(api[_-]?key|secret|token|password|auth|passwd)\s*[:=]\s*['\"]?([^\s'\"]{6,})['\"]?", re.IGNORECASE),
        re.compile(r"(private[_-]?key|client[_-]?secret)\s*[:=]\s*['\"]?([^\s'\"]{6,})['\"]?", re.IGNORECASE),
    ]

    def __init__(self, explicit_secrets: Optional[List[str]] = None):
        self.explicit_secrets = [s.strip() for s in (explicit_secrets or []) if s and len(s.strip()) > 3]

    def mask(self, text: Any) -> Any:
        if not isinstance(text, str):
            return text

        masked = text
        for secret in self.explicit_secrets:
            masked = masked.replace(secret, "[REDACTED_SECRET]")

        for pattern in self.PATTERNS:
            masked = pattern.sub(lambda m: m.group(0).split(':')[0] + ": [REDACTED_SECRET]" if ':' in m.group(0) else "[REDACTED_SECRET]", masked)

        return masked

    def sanitize_obj(self, obj: Any) -> Any:
        if isinstance(obj, str):
            return self.mask(obj)
        elif isinstance(obj, dict):
            return {k: self.sanitize_obj(v) for k, v in obj.items()}
        elif isinstance(obj, list):
            return [self.sanitize_obj(elem) for elem in obj]
        return obj


class SonarCollector:
    """Collects Quality Gate status and issues from SonarQube Cloud API."""

    def __init__(self, project_key: str, organization: str = "", token: str = "",
                 pr_number: str = "", branch: str = "", host_url: str = "https://sonarcloud.io"):
        self.project_key = project_key.strip() if project_key else ""
        self.organization = organization.strip() if organization else ""
        self.token = token.strip() if token else ""
        self.pr_number = pr_number.strip() if pr_number else ""
        self.branch = branch.strip() if branch else ""
        self.host_url = host_url.rstrip("/")

    def _api_get(self, endpoint: str, params: Dict[str, str]) -> Optional[Dict[str, Any]]:
        if not self.project_key:
            return None

        query_str = "&".join(f"{k}={urllib.parse.quote(str(v))}" for k, v in params.items() if v)
        url = f"{self.host_url}{endpoint}?{query_str}"
        headers = {
            "User-Agent": "Antigravity-Pipeline-Reporter/1.0",
            "Accept": "application/json"
        }
        if self.token:
            import base64
            auth_str = f"{self.token}:"
            b64_auth = base64.b64encode(auth_str.encode("utf-8")).decode("ascii")
            headers["Authorization"] = f"Basic {b64_auth}"

        req = urllib.request.Request(url, headers=headers)
        try:
            with urllib.request.urlopen(req, timeout=10) as resp:
                data = json.loads(resp.read().decode("utf-8"))
                return data
        except Exception as e:
            # Network issue or unauthenticated; return None with graceful handling
            sys.stderr.write(f"[WARN] SonarCloud API request failed ({url}): {e}\n")
            return None

    def collect(self) -> Dict[str, Any]:
        result = {
            "status": "UNAVAILABLE",
            "project_key": self.project_key,
            "organization": self.organization,
            "conditions": [],
            "issues": [],
            "hotspots": []
        }

        if not self.project_key:
            result["status"] = "NOT_CONFIGURED"
            return result

        # 1. Fetch Quality Gate Status
        qg_params = {"projectKey": self.project_key}
        if self.pr_number:
            qg_params["pullRequest"] = self.pr_number
        elif self.branch:
            qg_params["branch"] = self.branch

        qg_data = self._api_get("/api/qualitygates/project_status", qg_params)
        if qg_data and "projectStatus" in qg_data:
            ps = qg_data["projectStatus"]
            result["status"] = ps.get("status", "UNKNOWN")
            for c in ps.get("conditions", []):
                metric = c.get("metricKey", "")
                threshold = c.get("errorThreshold", "")
                actual = c.get("actualValue", "")
                status = c.get("status", "")
                comparator = c.get("comparator", "")

                explanation = self._explain_condition(metric, actual, threshold, comparator)
                result["conditions"].append({
                    "metric_key": metric,
                    "status": status,
                    "comparator": comparator,
                    "threshold": threshold,
                    "actual_value": actual,
                    "explanation": explanation
                })

        # 2. Fetch Issues
        issue_params = {
            "projects": self.project_key,
            "ps": "100",
            "statuses": "OPEN,REOPENED,CONFIRMED"
        }
        if self.pr_number:
            issue_params["pullRequest"] = self.pr_number
        elif self.branch:
            issue_params["branch"] = self.branch

        issues_data = self._api_get("/api/issues/search", issue_params)
        if issues_data and "issues" in issues_data:
            for issue in issues_data.get("issues", []):
                component = issue.get("component", "")
                # Format component to local repo relative path
                file_path = component.split(":", 1)[-1] if ":" in component else component
                result["issues"].append({
                    "key": issue.get("key"),
                    "rule": issue.get("rule"),
                    "severity": issue.get("severity", "MAJOR"),
                    "component": file_path,
                    "line": issue.get("line"),
                    "message": issue.get("message"),
                    "type": issue.get("type", "CODE_SMELL"),
                    "effort": issue.get("effort"),
                    "debt": issue.get("debt"),
                    "tags": issue.get("tags", [])
                })

        # 3. Fetch Hotspots
        hotspot_params = {"projectKey": self.project_key, "ps": "100"}
        if self.pr_number:
            hotspot_params["pullRequest"] = self.pr_number
        elif self.branch:
            hotspot_params["branch"] = self.branch

        hotspot_data = self._api_get("/api/hotspots/search", hotspot_params)
        if hotspot_data and "hotspots" in hotspot_data:
            for hs in hotspot_data.get("hotspots", []):
                component = hs.get("component", "")
                file_path = component.split(":", 1)[-1] if ":" in component else component
                result["hotspots"].append({
                    "key": hs.get("key"),
                    "rule": hs.get("ruleKey"),
                    "vulnerability_probability": hs.get("vulnerabilityProbability", "MEDIUM"),
                    "component": file_path,
                    "line": hs.get("line"),
                    "message": hs.get("message"),
                    "status": hs.get("status")
                })

        return result

    def _explain_condition(self, metric: str, actual: str, threshold: str, comparator: str) -> str:
        rating_map = {"1": "A", "2": "B", "3": "C", "4": "D", "5": "E"}
        if "security_rating" in metric:
            actual_g = rating_map.get(actual, actual)
            thresh_g = rating_map.get(threshold, threshold)
            return f"Security Rating is {actual_g} (required: grade {thresh_g}). Critical or high vulnerabilities detected."
        elif "reliability_rating" in metric:
            actual_g = rating_map.get(actual, actual)
            thresh_g = rating_map.get(threshold, threshold)
            return f"Reliability Rating is {actual_g} (required: grade {thresh_g}). Major bugs detected."
        elif "coverage" in metric:
            return f"Test coverage on new code is {actual}% (required: at least {threshold}%)."
        elif "duplicated_lines" in metric:
            return f"Duplicated lines density is {actual}% (required: less than {threshold}%)."
        elif "security_hotspots" in metric:
            return f"Security hotspots reviewed: {actual}% (required: {threshold}%)."
        return f"Metric {metric} value is {actual} (threshold: {comparator} {threshold})."


class ZapCollector:
    """Parses OWASP ZAP Baseline JSON reports."""

    def __init__(self, report_path: str, rules_path: str = ""):
        self.report_path = report_path
        self.rules_path = rules_path

    def collect(self) -> Dict[str, Any]:
        result = {
            "status": "NOT_AVAILABLE",
            "scanner": "OWASP ZAP Baseline",
            "target": "",
            "alerts": [],
            "counts": {"critical": 0, "high": 0, "medium": 0, "low": 0, "informational": 0}
        }

        if not self.report_path or not os.path.exists(self.report_path):
            return result

        try:
            with open(self.report_path, "r", encoding="utf-8") as f:
                data = json.load(f)
        except Exception as e:
            result["status"] = f"ERROR_PARSING: {e}"
            return result

        result["status"] = "OK"

        # Load ignored rules from ZAP rules file if available
        ignored_rules = set()
        rules_file = self.rules_path or ".zap/rules.tsv"
        if rules_file and os.path.exists(rules_file):
            try:
                with open(rules_file, "r", encoding="utf-8") as rf:
                    for line in rf:
                        line = line.strip()
                        if not line or line.startswith("#"):
                            continue
                        parts = line.split("\t")
                        if len(parts) >= 2 and parts[1].strip().upper() == "IGNORE":
                            ignored_rules.add(parts[0].strip())
            except Exception as e:
                print(f"Warning reading ZAP rules file: {e}")

        sites = data.get("site", [])
        if isinstance(sites, dict):
            sites = [sites]

        risk_map = {
            "3": ("high", "High"),
            "2": ("medium", "Medium"),
            "1": ("low", "Low"),
            "0": ("informational", "Informational")
        }
        conf_map = {
            "3": "High",
            "2": "Medium",
            "1": "Low",
            "0": "False Positive"
        }

        for site in sites:
            site_name = site.get("@name", "")
            if not result["target"] and site_name:
                result["target"] = site_name

            for alert in site.get("alerts", []):
                plugin_id = str(alert.get("pluginid", "")).strip()
                if plugin_id in ignored_rules:
                    continue

                risk_code = str(alert.get("riskcode", "0"))
                severity_key, severity_label = risk_map.get(risk_code, ("informational", "Informational"))
                confidence_code = str(alert.get("confidence", "2"))
                confidence_label = conf_map.get(confidence_code, confidence_code)

                count = int(alert.get("count", 1))
                result["counts"][severity_key] += count

                instances = []
                for inst in alert.get("instances", []):
                    instances.append({
                        "uri": inst.get("uri", ""),
                        "method": inst.get("method", ""),
                        "param": inst.get("param", ""),
                        "attack": inst.get("attack", ""),
                        "evidence": inst.get("evidence", "")
                    })

                refs = []
                raw_ref = alert.get("reference", "")
                if raw_ref:
                    refs = [r.strip() for r in re.split(r"[\r\n\s]+", raw_ref) if r.startswith("http")]

                cwe_id = alert.get("cweid")
                cwe_str = f"CWE-{cwe_id}" if cwe_id and str(cwe_id) != "0" else None
                wasc_id = alert.get("wascid")
                wasc_str = f"WASC-{wasc_id}" if wasc_id and str(wasc_id) != "0" else None

                result["alerts"].append({
                    "plugin_id": alert.get("pluginid"),
                    "name": alert.get("name") or alert.get("alert", "Security Alert"),
                    "risk_code": risk_code,
                    "severity": severity_label.upper(),
                    "confidence": confidence_label,
                    "description": alert.get("desc", ""),
                    "solution": alert.get("solution", ""),
                    "count": count,
                    "cwe_id": cwe_str,
                    "wasc_id": wasc_str,
                    "reference_urls": refs,
                    "instances": instances
                })

        return result


class TestCollector:
    """Parses JUnit XML test execution reports (PHPUnit, Pest, Pytest, Jest)."""

    def __init__(self, xml_path: str):
        self.xml_path = xml_path

    def collect(self) -> Dict[str, Any]:
        result = {
            "status": "NOT_AVAILABLE",
            "total_tests": 0,
            "failed_tests": 0,
            "errors": 0,
            "skipped": 0,
            "time": 0.0,
            "failures": []
        }

        if not self.xml_path or not os.path.exists(self.xml_path):
            return result

        try:
            tree = ET.parse(self.xml_path)
            root = tree.getroot()
        except Exception as e:
            result["status"] = f"ERROR_PARSING: {e}"
            return result

        result["status"] = "OK"

        # Handle root <testsuites> or single <testsuite>
        testsuites = [root] if root.tag == "testsuite" else root.findall(".//testsuite")

        total_cnt = 0
        fail_cnt = 0
        err_cnt = 0

        for ts in testsuites:
            total_cnt += int(ts.attrib.get("tests", 0))
            fail_cnt += int(ts.attrib.get("failures", 0))
            err_cnt += int(ts.attrib.get("errors", 0))

            suite_name = ts.attrib.get("name", "Test Suite")
            for tc in ts.findall("testcase"):
                test_name = tc.attrib.get("name", "Unknown Test")
                classname = tc.attrib.get("classname", suite_name)
                file_ref = tc.attrib.get("file")
                line_ref = tc.attrib.get("line")
                duration = float(tc.attrib.get("time", 0.0))

                failure_elem = tc.find("failure")
                error_elem = tc.find("error")
                target_elem = failure_elem if failure_elem is not None else error_elem

                if target_elem is not None:
                    msg = target_elem.attrib.get("message", "Test assertion failed")
                    stack_trace = target_elem.text or ""

                    result["failures"].append({
                        "suite": classname,
                        "name": test_name,
                        "file": file_ref,
                        "line": int(line_ref) if line_ref and line_ref.isdigit() else None,
                        "message": msg.strip(),
                        "stack_trace": stack_trace.strip(),
                        "duration": duration,
                        "type": "error" if error_elem is not None else "failure"
                    })

        result["total_tests"] = total_cnt
        result["failed_tests"] = len(result["failures"])
        result["errors"] = err_cnt

        return result


class Normalizer:
    """Normalizes findings from all tools into a consistent, AI-ready schema."""

    @staticmethod
    def normalize(
        sonar_data: Dict[str, Any],
        zap_data: Dict[str, Any],
        test_data: Dict[str, Any],
        jobs_data: List[Dict[str, Any]]
    ) -> List[Dict[str, Any]]:
        findings = []

        # 1. Normalize SonarQube Issues
        for idx, issue in enumerate(sonar_data.get("issues", [])):
            sev = issue.get("severity", "MAJOR").upper()
            norm_sev = {
                "BLOCKER": "CRITICAL",
                "CRITICAL": "CRITICAL",
                "MAJOR": "HIGH",
                "MINOR": "LOW",
                "INFO": "INFORMATIONAL"
            }.get(sev, "MEDIUM")

            rule_id = issue.get("rule", "sonar-rule")
            component = issue.get("component", "unknown-file")
            line = issue.get("line")
            msg = issue.get("message", "SonarQube finding")
            issue_type = issue.get("type", "CODE_SMELL").lower()

            priority = "P0" if norm_sev == "CRITICAL" else ("P1" if norm_sev == "HIGH" else "P2")

            findings.append({
                "id": f"sonar-{issue.get('key') or idx}",
                "source": "sonarqube",
                "category": issue_type,
                "severity": norm_sev,
                "rule_id": rule_id,
                "title": f"[{norm_sev}] {rule_id}: {msg[:80]}",
                "message": msg,
                "description": f"SonarQube {issue_type.replace('_', ' ')}: {msg}",
                "file": component,
                "line": line,
                "column": None,
                "code_snippet": None,
                "evidence": None,
                "remediation": f"Inspect rule {rule_id} and resolve the {issue_type.replace('_', ' ')} in {component} line {line}.",
                "test_name": None,
                "expected": None,
                "actual": None,
                "cwe_id": None,
                "wasc_id": None,
                "reference_urls": [f"https://rules.sonarsource.com/rules/{rule_id}"] if ":" in rule_id else [],
                "ai_context": {
                    "actionable": True,
                    "fix_priority": priority,
                    "suggested_file": component,
                    "suggested_action": "modify_file"
                }
            })

        # 2. Normalize OWASP ZAP Alerts
        for idx, alert in enumerate(zap_data.get("alerts", [])):
            norm_sev = alert.get("severity", "MEDIUM").upper()
            if norm_sev not in ["CRITICAL", "HIGH", "MEDIUM", "LOW", "INFORMATIONAL"]:
                norm_sev = "MEDIUM"

            plugin_id = alert.get("plugin_id", f"zap-{idx}")
            name = alert.get("name", "ZAP Alert")
            desc = alert.get("description", "")
            sol = alert.get("solution", "")

            first_instance = (alert.get("instances") or [{}])[0]
            uri = first_instance.get("uri", zap_data.get("target", "localhost"))
            evidence = first_instance.get("evidence", "")

            priority = "P0" if norm_sev in ["CRITICAL", "HIGH"] else ("P1" if norm_sev == "MEDIUM" else "P2")

            findings.append({
                "id": f"zap-{plugin_id}-{idx}",
                "source": "owasp_zap",
                "category": "vulnerability" if norm_sev in ["CRITICAL", "HIGH", "MEDIUM"] else "code_smell",
                "severity": norm_sev,
                "rule_id": f"ZAP-{plugin_id}",
                "title": f"[DAST {norm_sev}] {name}",
                "message": name,
                "description": desc,
                "file": None,
                "line": None,
                "column": None,
                "code_snippet": None,
                "evidence": evidence or None,
                "remediation": sol or None,
                "test_name": None,
                "expected": None,
                "actual": None,
                "cwe_id": alert.get("cwe_id"),
                "wasc_id": alert.get("wasc_id"),
                "reference_urls": alert.get("reference_urls", []),
                "instances": alert.get("instances", []),
                "ai_context": {
                    "actionable": True,
                    "fix_priority": priority,
                    "suggested_file": "application middleware or configuration",
                    "suggested_action": "add_security_headers_or_sanitize"
                }
            })

        # 3. Normalize Failed Unit/Feature Tests
        for idx, test in enumerate(test_data.get("failures", [])):
            findings.append({
                "id": f"test-failure-{idx}",
                "source": "unit_test",
                "category": "test_failure",
                "severity": "HIGH",
                "rule_id": "test:assertion_failure",
                "title": f"[TEST FAIL] {test.get('suite')}::{test.get('name')}",
                "message": test.get("message", "Test assertion failure"),
                "description": test.get("stack_trace", ""),
                "file": test.get("file"),
                "line": test.get("line"),
                "column": None,
                "code_snippet": None,
                "evidence": test.get("stack_trace"),
                "remediation": f"Verify test logic and application behavior in {test.get('file')}.",
                "test_name": f"{test.get('suite')}::{test.get('name')}",
                "expected": None,
                "actual": None,
                "cwe_id": None,
                "wasc_id": None,
                "reference_urls": [],
                "ai_context": {
                    "actionable": True,
                    "fix_priority": "P0",
                    "suggested_file": test.get("file"),
                    "suggested_action": "debug_and_fix_code"
                }
            })

        # 4. Normalize Failed GitHub Actions Jobs
        for idx, job in enumerate(jobs_data):
            if job.get("conclusion") in ["failure", "cancelled"]:
                findings.append({
                    "id": f"job-failure-{idx}",
                    "source": "github_actions",
                    "category": "job_failure",
                    "severity": "HIGH",
                    "rule_id": "ci:job_failure",
                    "title": f"[CI JOB FAILED] {job.get('name')}",
                    "message": job.get("error_message") or f"Job '{job.get('name')}' finished with status '{job.get('conclusion')}'.",
                    "description": job.get("failed_step") or None,
                    "file": ".github/workflows/reusable-security-pipeline.yml",
                    "line": None,
                    "column": None,
                    "code_snippet": None,
                    "evidence": None,
                    "remediation": "Review job step logs in GitHub Actions.",
                    "test_name": None,
                    "expected": "success",
                    "actual": job.get("conclusion"),
                    "cwe_id": None,
                    "wasc_id": None,
                    "reference_urls": [],
                    "ai_context": {
                        "actionable": True,
                        "fix_priority": "P0",
                        "suggested_file": ".github/workflows/reusable-security-pipeline.yml",
                        "suggested_action": "review_workflow_logs"
                    }
                })

        return findings


class HTMLRenderer:
    """Renders standalone, responsive, modern developer dashboard HTML reports with zero external dependencies."""

    @staticmethod
    def render(report: Dict[str, Any]) -> str:
        summary = report.get("summary", {})
        repo = report.get("repository", {})
        wf = report.get("workflow", {})
        pr = report.get("pull_request", {})
        commit = report.get("commit", {})
        qg = report.get("quality_gate", {})
        findings = report.get("findings", [])
        failed_jobs = report.get("failed_jobs", [])
        failed_tests = report.get("failed_tests", [])
        ai_ready = report.get("ai_analysis_ready", {})

        is_passed = summary.get("overall_status") == "PASSED"
        status_color = "#10b981" if is_passed else "#ef4444"
        status_text = "PASSED" if is_passed else "FAILED"
        status_bg = "#ecfdf5" if is_passed else "#fef2f2"
        status_border = "#a7f3d0" if is_passed else "#fecaca"

        # Embedded JSON string for client-side filtering and AI export
        json_dump_safe = html.escape(json.dumps(report, indent=2), quote=True)

        html_content = f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CI/CD Failure & Security Report - {html.escape(repo.get('name', 'Repository'))}</title>
  <style>
    :root {{
      --bg: #0f172a;
      --card-bg: #1e293b;
      --card-border: #334155;
      --text: #f8fafc;
      --text-muted: #94a3b8;
      --accent: #6366f1;
      --accent-hover: #4f46e5;
      --crit: #ef4444;
      --crit-bg: rgba(239, 68, 68, 0.15);
      --high: #f97316;
      --high-bg: rgba(249, 115, 22, 0.15);
      --med: #eab308;
      --med-bg: rgba(234, 179, 8, 0.15);
      --low: #38bdf8;
      --low-bg: rgba(56, 189, 248, 0.15);
      --info: #94a3b8;
      --info-bg: rgba(148, 163, 184, 0.15);
      --success: #10b981;
      --success-bg: rgba(16, 185, 129, 0.15);
    }}

    * {{ box-sizing: border-box; margin: 0; padding: 0; }}
    body {{
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: var(--bg);
      color: var(--text);
      line-height: 1.5;
      padding: 1.5rem;
    }}
    .container {{ max-width: 1400px; margin: 0 auto; }}

    /* Header */
    .header {{
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 12px;
      padding: 1.5rem 2rem;
      margin-bottom: 1.5rem;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
    }}
    .header-title h1 {{
      font-size: 1.5rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }}
    .status-badge {{
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.875rem;
      font-weight: 700;
      text-transform: uppercase;
      background: {status_bg};
      color: {status_color};
      border: 1px solid {status_border};
    }}
    .header-meta {{
      display: flex;
      flex-wrap: wrap;
      gap: 1.25rem;
      font-size: 0.875rem;
      color: var(--text-muted);
      margin-top: 0.5rem;
    }}
    .header-meta a {{
      color: var(--low);
      text-decoration: none;
    }}
    .header-meta a:hover {{ text-decoration: underline; }}

    /* KPI Summary Cards */
    .kpi-grid {{
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }}
    .kpi-card {{
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 10px;
      padding: 1.25rem;
      text-align: center;
      transition: transform 0.15s ease;
    }}
    .kpi-card:hover {{ transform: translateY(-2px); }}
    .kpi-label {{
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
      margin-bottom: 0.5rem;
    }}
    .kpi-val {{
      font-size: 1.75rem;
      font-weight: 800;
    }}
    .val-crit {{ color: var(--crit); }}
    .val-high {{ color: var(--high); }}
    .val-med {{ color: var(--med); }}
    .val-low {{ color: var(--low); }}
    .val-succ {{ color: var(--success); }}

    /* Tabs */
    .tabs-nav {{
      display: flex;
      gap: 0.5rem;
      border-bottom: 1px solid var(--card-border);
      margin-bottom: 1.5rem;
      overflow-x: auto;
    }}
    .tab-btn {{
      background: transparent;
      border: none;
      color: var(--text-muted);
      font-size: 0.95rem;
      font-weight: 600;
      padding: 0.75rem 1.25rem;
      cursor: pointer;
      border-bottom: 2px solid transparent;
      transition: all 0.2s;
      white-space: nowrap;
    }}
    .tab-btn:hover {{ color: var(--text); }}
    .tab-btn.active {{
      color: var(--accent);
      border-bottom-color: var(--accent);
    }}

    .tab-pane {{ display: none; }}
    .tab-pane.active {{ display: block; }}

    /* Tables & Cards */
    .section-card {{
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }}
    .section-title {{
      font-size: 1.15rem;
      font-weight: 700;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }}

    .table-responsive {{ overflow-x: auto; }}
    table {{
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.9rem;
    }}
    th {{
      background: rgba(255, 255, 255, 0.03);
      padding: 0.75rem 1rem;
      border-bottom: 1px solid var(--card-border);
      color: var(--text-muted);
      font-weight: 600;
    }}
    td {{
      padding: 0.75rem 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      vertical-align: top;
    }}
    tr:hover td {{ background: rgba(255, 255, 255, 0.02); }}

    /* Pill Badges */
    .pill {{
      display: inline-block;
      padding: 0.2rem 0.55rem;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
    }}
    .pill-crit {{ background: var(--crit-bg); color: var(--crit); }}
    .pill-high {{ background: var(--high-bg); color: var(--high); }}
    .pill-med {{ background: var(--med-bg); color: var(--med); }}
    .pill-low {{ background: var(--low-bg); color: var(--low); }}
    .pill-info {{ background: var(--info-bg); color: var(--info); }}
    .pill-ok {{ background: var(--success-bg); color: var(--success); }}

    /* Filter Controls */
    .filter-bar {{
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      margin-bottom: 1.25rem;
      align-items: center;
    }}
    .search-input, .filter-select {{
      background: rgba(15, 23, 42, 0.8);
      border: 1px solid var(--card-border);
      color: var(--text);
      padding: 0.5rem 0.75rem;
      border-radius: 6px;
      font-size: 0.875rem;
    }}
    .search-input {{ flex-grow: 1; min-width: 200px; }}

    /* Code Blocks */
    pre, code {{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 0.85rem;
    }}
    pre {{
      background: #090d16;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 6px;
      padding: 0.75rem;
      overflow-x: auto;
      color: #cbd5e1;
      margin-top: 0.5rem;
    }}

    /* Buttons */
    .btn {{
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: var(--accent);
      color: #fff;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.875rem;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.15s;
    }}
    .btn:hover {{ background: var(--accent-hover); }}
    .btn-secondary {{
      background: rgba(255, 255, 255, 0.1);
      color: var(--text);
    }}
    .btn-secondary:hover {{ background: rgba(255, 255, 255, 0.2); }}

    details summary {{
      cursor: pointer;
      color: var(--low);
      font-weight: 500;
    }}
    details summary:hover {{ text-decoration: underline; }}
  </style>
</head>
<body>
  <div class="container">
    <!-- Header -->
    <header class="header">
      <div class="header-title">
        <div>
          <h1>
            <span>🛡️ Pipeline Security & Failure Report</span>
            <span class="status-badge">{status_text}</span>
          </h1>
          <div class="header-meta">
            <span><strong>Repo:</strong> {html.escape(repo.get('name', 'N/A'))}</span>
            <span><strong>Branch:</strong> <code>{html.escape(pr.get('head_branch') or 'N/A')}</code></span>
            <span><strong>PR:</strong> #{pr.get('number', 'N/A')}</span>
            <span><strong>Commit:</strong> <code>{html.escape(commit.get('short_sha') or 'N/A')}</code></span>
            <span><strong>Generated:</strong> {html.escape(report.get('generated_at', ''))}</span>
            {f'<span><a href="{html.escape(wf.get("run_url", "#"))}" target="_blank">🔗 View GitHub Actions Run</a></span>' if wf.get('run_url') else ''}
          </div>
        </div>
      </div>
      <div>
        <button class="btn btn-secondary" onclick="downloadJSON()">💾 Export JSON</button>
      </div>
    </header>

    <!-- KPI Summary Grid -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-label">Total Findings</div>
        <div class="kpi-val">{summary.get('total_findings', 0)}</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Critical / High</div>
        <div class="kpi-val val-crit">{summary.get('critical', 0) + summary.get('high', 0)}</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Medium</div>
        <div class="kpi-val val-med">{summary.get('medium', 0)}</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Low / Info</div>
        <div class="kpi-val val-low">{summary.get('low', 0) + summary.get('informational', 0)}</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Failed Tests</div>
        <div class="kpi-val {'val-crit' if summary.get('failed_tests', 0) > 0 else 'val-succ'}">{summary.get('failed_tests', 0)}</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Failed Jobs</div>
        <div class="kpi-val {'val-crit' if summary.get('failed_jobs', 0) > 0 else 'val-succ'}">{summary.get('failed_jobs', 0)}</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Quality Gate</div>
        <div class="kpi-val {'val-succ' if qg.get('status') == 'OK' else 'val-crit'}">{html.escape(qg.get('status', 'N/A'))}</div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="tabs-nav">
      <button class="tab-btn active" onclick="switchTab('overview')">📊 Overview & Quality Gate</button>
      <button class="tab-btn" onclick="switchTab('findings')">🔍 All Security & Code Findings ({len(findings)})</button>
      <button class="tab-btn" onclick="switchTab('tests')">🧪 Failed Tests ({len(failed_tests)})</button>
      <button class="tab-btn" onclick="switchTab('jobs')">⚙️ Failed Jobs ({len(failed_jobs)})</button>
      <button class="tab-btn" onclick="switchTab('ai')">🤖 AI Remediation Preparation</button>
    </nav>

    <!-- Tab 1: Overview & Quality Gate -->
    <div id="tab-overview" class="tab-pane active">
      <div class="section-card">
        <h2 class="section-title">SonarQube Cloud Quality Gate Status</h2>
        <p style="margin-bottom: 1rem; color: var(--text-muted);">
          Project Key: <code>{html.escape(qg.get('project_key', 'N/A'))}</code> | Status:
          <span class="pill {'pill-ok' if qg.get('status') == 'OK' else 'pill-crit'}">{html.escape(qg.get('status', 'UNKNOWN'))}</span>
        </p>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Condition Metric</th>
                <th>Status</th>
                <th>Threshold</th>
                <th>Actual Value</th>
                <th>Explanation & Root Cause</th>
              </tr>
            </thead>
            <tbody>
"""

        conditions = qg.get("conditions", [])
        if conditions:
            for c in conditions:
                c_status = c.get("status", "")
                pill_class = "pill-ok" if c_status == "OK" else "pill-crit"
                html_content += f"""
              <tr>
                <td><strong>{html.escape(c.get('metric_key', ''))}</strong></td>
                <td><span class="pill {pill_class}">{html.escape(c_status)}</span></td>
                <td>{html.escape(str(c.get('threshold', '—')))}</td>
                <td><strong>{html.escape(str(c.get('actual_value', '—')))}</strong></td>
                <td>{html.escape(c.get('explanation', ''))}</td>
              </tr>
"""
        else:
            html_content += """
              <tr>
                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                  No specific Quality Gate conditions reported or SonarCloud was not reached.
                </td>
              </tr>
"""

        html_content += """
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Tab 2: Findings (Filterable Table) -->
    <div id="tab-findings" class="tab-pane">
      <div class="section-card">
        <h2 class="section-title">Unified Findings & Vulnerabilities</h2>
        <div class="filter-bar">
          <input type="text" id="findingSearch" class="search-input" placeholder="Search by file, rule, message, or CVE..." oninput="filterFindings()">
          <select id="severityFilter" class="filter-select" onchange="filterFindings()">
            <option value="ALL">All Severities</option>
            <option value="CRITICAL">Critical</option>
            <option value="HIGH">High</option>
            <option value="MEDIUM">Medium</option>
            <option value="LOW">Low</option>
            <option value="INFORMATIONAL">Informational</option>
          </select>
          <select id="sourceFilter" class="filter-select" onchange="filterFindings()">
            <option value="ALL">All Sources</option>
            <option value="sonarqube">SonarQube Cloud (SAST)</option>
            <option value="owasp_zap">OWASP ZAP (DAST)</option>
            <option value="unit_test">Unit Tests</option>
            <option value="github_actions">GitHub Actions</option>
          </select>
        </div>

        <div class="table-responsive">
          <table id="findingsTable">
            <thead>
              <tr>
                <th>Severity</th>
                <th>Source</th>
                <th>Rule / Alert ID</th>
                <th>Location / Target</th>
                <th>Message & Remediation Details</th>
              </tr>
            </thead>
            <tbody>
"""

        if findings:
            for f in findings:
                sev = f.get("severity", "MEDIUM")
                pill_class = {
                    "CRITICAL": "pill-crit",
                    "HIGH": "pill-high",
                    "MEDIUM": "pill-med",
                    "LOW": "pill-low",
                    "INFORMATIONAL": "pill-info"
                }.get(sev, "pill-med")

                loc = f.get("file") or f.get("test_name") or "CI Environment"
                if f.get("line"):
                    loc += f":{f.get('line')}"

                desc = f.get("description") or ""
                remed = f.get("remediation") or ""
                evidence = f.get("evidence") or ""

                html_content += f"""
              <tr class="finding-row" data-severity="{html.escape(sev)}" data-source="{html.escape(f.get('source', ''))}">
                <td><span class="pill {pill_class}">{html.escape(sev)}</span></td>
                <td><code>{html.escape(f.get('source', ''))}</code></td>
                <td><strong>{html.escape(f.get('rule_id', 'N/A'))}</strong></td>
                <td><code>{html.escape(loc)}</code></td>
                <td>
                  <div><strong>{html.escape(f.get('message', ''))}</strong></div>
                  {f'<div style="margin-top:0.35rem; font-size:0.8rem; color:var(--text-muted);">{html.escape(desc)}</div>' if desc and desc != f.get('message') else ''}
                  {f'<details style="margin-top:0.5rem;"><summary>💡 Suggested Remediation</summary><p style="margin-top:0.3rem; font-size:0.85rem; color:#a7f3d0;">{html.escape(remed)}</p></details>' if remed else ''}
                  {f'<details style="margin-top:0.3rem;"><summary>🔎 Evidence / Context</summary><pre>{html.escape(evidence)}</pre></details>' if evidence else ''}
                </td>
              </tr>
"""
        else:
            html_content += """
              <tr>
                <td colspan="5" style="text-align: center; color: var(--success); padding: 2rem;">
                  🎉 No security findings or code issues detected!
                </td>
              </tr>
"""

        html_content += """
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Tab 3: Failed Tests -->
    <div id="tab-tests" class="tab-pane">
      <div class="section-card">
        <h2 class="section-title">Failed Unit & Feature Tests</h2>
"""

        if failed_tests:
            for ft in failed_tests:
                html_content += f"""
        <div style="background: rgba(239,68,68,0.06); border: 1px solid var(--card-border); border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
            <div>
              <span class="pill pill-crit">FAIL</span>
              <strong style="margin-left: 0.5rem;">{html.escape(ft.get('suite', ''))}::{html.escape(ft.get('name', ''))}</strong>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
              {f"File: {html.escape(ft.get('file', ''))}:{ft.get('line', '')}" if ft.get('file') else ''} ({ft.get('duration', 0)}s)
            </div>
          </div>
          <div style="color: #fca5a5; font-size: 0.9rem; margin-bottom: 0.5rem;">
            <strong>Error:</strong> {html.escape(ft.get('message', ''))}
          </div>
          {f'<details><summary>View Stack Trace</summary><pre>{html.escape(ft.get("stack_trace", ""))}</pre></details>' if ft.get('stack_trace') else ''}
        </div>
"""
        else:
            html_content += """
        <p style="text-align: center; color: var(--success); padding: 2rem;">
          ✅ All automated tests passed successfully!
        </p>
"""

        html_content += """
      </div>
    </div>

    <!-- Tab 4: Failed Jobs -->
    <div id="tab-jobs" class="tab-pane">
      <div class="section-card">
        <h2 class="section-title">GitHub Actions Job & Step Failures</h2>
"""

        if failed_jobs:
            html_content += """
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Job Name</th>
                <th>Status</th>
                <th>Failed Step</th>
                <th>Exit Code</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
"""
            for fj in failed_jobs:
                html_content += f"""
              <tr>
                <td><strong>{html.escape(fj.get('name', 'Job'))}</strong></td>
                <td><span class="pill pill-crit">{html.escape(fj.get('conclusion', 'failure'))}</span></td>
                <td>{html.escape(fj.get('failed_step') or 'N/A')}</td>
                <td><code>{html.escape(str(fj.get('exit_code', '—')))}</code></td>
                <td>{html.escape(fj.get('error_message') or 'Check run logs.')}</td>
              </tr>
"""
            html_content += """
            </tbody>
          </table>
        </div>
"""
        else:
            html_content += """
        <p style="text-align: center; color: var(--success); padding: 2rem;">
          ✅ No GitHub Actions job failures recorded.
        </p>
"""

        html_content += """
      </div>
    </div>

    <!-- Tab 5: AI Remediation Preparation -->
    <div id="tab-ai" class="tab-pane">
      <div class="section-card">
        <h2 class="section-title">🤖 AI Agent Code-Fix Preparation</h2>
        <p style="margin-bottom: 1rem; color: var(--text-muted);">
          This normalized report is prepared for consumption by automated AI agents. Below is the actionable summary and recommended prompt starter.
        </p>
"""

        primary_targets = ai_ready.get("primary_targets", [])
        if primary_targets:
            html_content += """
        <h3 style="font-size: 1rem; margin-bottom: 0.75rem;">🎯 High-Priority Files Needing Remediation:</h3>
        <div class="table-responsive" style="margin-bottom: 1.5rem;">
          <table>
            <thead>
              <tr>
                <th>Target File</th>
                <th>Highest Severity</th>
                <th>Total Issues</th>
                <th>Action Description</th>
              </tr>
            </thead>
            <tbody>
"""
            for t in primary_targets:
                sev = t.get("highest_severity", "HIGH")
                p_class = "pill-crit" if sev == "CRITICAL" else ("pill-high" if sev == "HIGH" else "pill-med")
                html_content += f"""
              <tr>
                <td><code>{html.escape(t.get('file', ''))}</code></td>
                <td><span class="pill {p_class}">{html.escape(sev)}</span></td>
                <td><strong>{t.get('issue_count', 0)}</strong></td>
                <td>{html.escape(t.get('summary', ''))}</td>
              </tr>
"""
            html_content += """
            </tbody>
          </table>
        </div>
"""

        prompt_text = ai_ready.get("prompt_summary", "Review pipeline-report.json and fix reported findings.")
        html_content += f"""
        <h3 style="font-size: 1rem; margin-bottom: 0.5rem;">📋 AI Prompt Starter (Copy & Paste to your AI assistant):</h3>
        <pre id="aiPromptText">{html.escape(prompt_text)}</pre>
        <div style="margin-top: 0.75rem;">
          <button class="btn btn-secondary" onclick="copyPrompt()">📋 Copy Prompt to Clipboard</button>
        </div>
      </div>
    </div>

  </div>

  <!-- Embedded Report Data for Client Interaction -->
  <script>
    const reportData = JSON.parse(decodeURIComponent('{urllib.parse.quote(json.dumps(report))}'));

    function switchTab(tabId) {{
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

      event.target.classList.add('active');
      document.getElementById('tab-' + tabId).classList.add('active');
    }}

    function filterFindings() {{
      const query = document.getElementById('findingSearch').value.toLowerCase();
      const sevFilter = document.getElementById('severityFilter').value;
      const srcFilter = document.getElementById('sourceFilter').value;
      const rows = document.querySelectorAll('.finding-row');

      rows.forEach(row => {{
        const rowText = row.innerText.toLowerCase();
        const rowSev = row.getAttribute('data-severity');
        const rowSrc = row.getAttribute('data-source');

        const matchesQuery = !query || rowText.includes(query);
        const matchesSev = (sevFilter === 'ALL') || (rowSev === sevFilter);
        const matchesSrc = (srcFilter === 'ALL') || (rowSrc === srcFilter);

        if (matchesQuery && matchesSev && matchesSrc) {{
          row.style.display = '';
        }} else {{
          row.style.display = 'none';
        }}
      }});
    }}

    function downloadJSON() {{
      const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(reportData, null, 2));
      const downloadAnchor = document.createElement('a');
      downloadAnchor.setAttribute("href", dataStr);
      downloadAnchor.setAttribute("download", "pipeline-report.json");
      document.body.appendChild(downloadAnchor);
      downloadAnchor.click();
      downloadAnchor.remove();
    }}

    function copyPrompt() {{
      const promptEl = document.getElementById('aiPromptText');
      navigator.clipboard.writeText(promptEl.innerText).then(() => {{
        alert("AI prompt copied to clipboard!");
      }});
    }}
  </script>
</body>
</html>
"""
        return html_content


def generate_report(
    repo: str,
    workflow_name: str,
    run_id: str,
    run_url: str,
    commit_sha: str,
    branch: str,
    pr_number: str,
    sonar_project_key: str,
    sonar_org: str,
    sonar_token: str,
    zap_report_path: str,
    test_report_path: str,
    validate_status: str,
    sonar_status: str,
    dast_status: str,
    output_dir: str,
    zap_rules_path: str = ""
) -> Tuple[str, str]:
    """Generates normalized pipeline-report.json and pipeline-report.html."""
    os.makedirs(output_dir, exist_ok=True)
    masker = SecretMasker([sonar_token])

    # 1. Collect Data from Sources
    sonar_collector = SonarCollector(
        project_key=sonar_project_key,
        organization=sonar_org,
        token=sonar_token,
        pr_number=pr_number,
        branch=branch
    )
    sonar_data = sonar_collector.collect()

    zap_collector = ZapCollector(zap_report_path, rules_path=zap_rules_path)
    zap_data = zap_collector.collect()

    test_collector = TestCollector(test_report_path)
    test_data = test_collector.collect()

    # 2. Build Failed Jobs Metadata
    jobs_data = []
    if validate_status in ("failure", "cancelled"):
        jobs_data.append({
            "name": "Validation / Tests",
            "status": validate_status,
            "conclusion": validate_status,
            "failed_step": "Dependencies / Tests / Linter",
            "exit_code": 1,
            "error_message": "Automated tests or code linter checks failed."
        })
    if sonar_status in ("failure", "cancelled"):
        jobs_data.append({
            "name": "1. SonarQube Cloud SAST",
            "status": sonar_status,
            "conclusion": sonar_status,
            "failed_step": "SonarQube Quality Gate Check",
            "exit_code": 1,
            "error_message": "SonarQube Cloud Quality Gate conditions were not satisfied."
        })
    if dast_status in ("failure", "cancelled"):
        jobs_data.append({
            "name": "2. OWASP ZAP DAST Analysis",
            "status": dast_status,
            "conclusion": dast_status,
            "failed_step": "OWASP ZAP Scan / Policy Check",
            "exit_code": 1,
            "error_message": "Dynamic security scan detected vulnerabilities breaching severity thresholds."
        })

    # 3. Normalize all findings
    normalized_findings = Normalizer.normalize(sonar_data, zap_data, test_data, jobs_data)

    # 4. Calculate Summary Metrics
    critical_count = sum(1 for f in normalized_findings if f["severity"] == "CRITICAL")
    high_count = sum(1 for f in normalized_findings if f["severity"] == "HIGH")
    med_count = sum(1 for f in normalized_findings if f["severity"] == "MEDIUM")
    low_count = sum(1 for f in normalized_findings if f["severity"] == "LOW")
    info_count = sum(1 for f in normalized_findings if f["severity"] == "INFORMATIONAL")

    qg_status = sonar_data.get("status", "UNKNOWN")
    overall_passed = (len(jobs_data) == 0) and (critical_count == 0) and (high_count == 0) and (qg_status in ["OK", "PASSED", "NOT_CONFIGURED"])

    # 5. Build AI Preparation Payload
    file_issue_counts: Dict[str, Dict[str, Any]] = {}
    for f in normalized_findings:
        f_file = f.get("file")
        if f_file:
            if f_file not in file_issue_counts:
                file_issue_counts[f_file] = {"count": 0, "highest_severity": "LOW", "reasons": set()}
            file_issue_counts[f_file]["count"] += 1
            file_issue_counts[f_file]["reasons"].add(f.get("rule_id", "issue"))
            cur_sev = file_issue_counts[f_file]["highest_severity"]
            if f["severity"] == "CRITICAL" or (f["severity"] == "HIGH" and cur_sev != "CRITICAL"):
                file_issue_counts[f_file]["highest_severity"] = f["severity"]

    primary_targets = []
    for f_path, meta in sorted(file_issue_counts.items(), key=lambda item: (item[1]["highest_severity"] == "CRITICAL", item[1]["count"]), reverse=True):
        primary_targets.append({
            "file": f_path,
            "issue_count": meta["count"],
            "highest_severity": meta["highest_severity"],
            "summary": f"Resolve {meta['count']} issues ({', '.join(list(meta['reasons'])[:3])})"
        })

    prompt_lines = [
        f"You are an expert software engineer and security auditor tasked with fixing CI/CD pipeline failures for repository '{repo}'.",
        f"Current status: {'PASSED' if overall_passed else 'FAILED'} on branch '{branch}' (PR #{pr_number or 'N/A'}).",
        f"Findings Summary: {critical_count} Critical, {high_count} High, {med_count} Medium, {test_data.get('failed_tests', 0)} failed tests.",
        ""
    ]
    if primary_targets:
        prompt_lines.append("Please inspect and remediate the following files in priority order:")
        for pt in primary_targets[:5]:
            prompt_lines.append(f"- `{pt['file']}`: {pt['highest_severity']} priority ({pt['summary']})")
    if test_data.get("failures"):
        prompt_lines.append("\nFailed Tests:")
        for t in test_data["failures"][:3]:
            prompt_lines.append(f"- `{t['suite']}::{t['name']}`: {t['message']}")

    prompt_summary = "\n".join(prompt_lines)

    # 6. Compose Final JSON Report Structure
    report = {
        "$schema": "https://json-schema.org/draft/2020-12/schema",
        "report_version": "1.0.0",
        "generated_at": datetime.datetime.now(datetime.timezone.utc).isoformat(),
        "generator": {
            "name": "CI/CD Pipeline Failure & Security Report Generator",
            "version": "1.0.0"
        },
        "repository": {
            "name": repo or "Repository",
            "url": f"https://github.com/{repo}" if repo else ""
        },
        "workflow": {
            "name": workflow_name or "Security CI Pipeline",
            "run_id": run_id or "",
            "run_url": run_url or "",
            "status": "success" if overall_passed else "failure",
            "conclusion": "success" if overall_passed else "failure"
        },
        "pull_request": {
            "number": int(pr_number) if pr_number and pr_number.isdigit() else None,
            "head_branch": branch or "",
            "base_branch": "main",
            "url": f"https://github/{repo}/pull/{pr_number}" if repo and pr_number else None
        },
        "commit": {
            "sha": commit_sha or "",
            "short_sha": (commit_sha[:7] if commit_sha else "")
        },
        "summary": {
            "overall_status": "PASSED" if overall_passed else "FAILED",
            "total_findings": len(normalized_findings),
            "critical": critical_count,
            "high": high_count,
            "medium": med_count,
            "low": low_count,
            "informational": info_count,
            "failed_tests": test_data.get("failed_tests", 0),
            "failed_jobs": len(jobs_data),
            "quality_gate_status": qg_status
        },
        "quality_gate": {
            "status": qg_status,
            "project_key": sonar_project_key,
            "organization": sonar_org,
            "conditions": sonar_data.get("conditions", [])
        },
        "failed_jobs": jobs_data,
        "failed_tests": test_data.get("failures", []),
        "security_scan": {
            "scanner": zap_data.get("scanner", "OWASP ZAP"),
            "target": zap_data.get("target", ""),
            "status": zap_data.get("status", ""),
            "alert_counts": zap_data.get("counts", {})
        },
        "findings": normalized_findings,
        "ai_analysis_ready": {
            "ready": len(normalized_findings) > 0 or len(test_data.get("failures", [])) > 0,
            "recommended_agent_role": "Security Remediation & Automated Bug Fixing Specialist",
            "primary_targets": primary_targets,
            "prompt_summary": prompt_summary
        },
        "metadata": {
            "environment": "github-actions" if os.environ.get("GITHUB_ACTIONS") else "local",
            "collector_sources": ["sonarqube", "owasp_zap", "junit_xml", "github_actions"]
        }
    }

    # Mask any detected secrets
    clean_report = masker.sanitize_obj(report)

    # 7. Write JSON Report
    json_path = os.path.join(output_dir, "pipeline-report.json")
    with open(json_path, "w", encoding="utf-8") as f:
        json.dump(clean_report, f, indent=2)

    # 8. Render and Write HTML Report
    html_content = HTMLRenderer.render(clean_report)
    html_path = os.path.join(output_dir, "pipeline-report.html")
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(html_content)

    return json_path, html_path


def main():
    parser = argparse.ArgumentParser(description="Generate AI-ready CI/CD Pipeline Failure & Security Report.")
    parser.add_argument("--repo", default=os.environ.get("GITHUB_REPOSITORY", ""))
    parser.add_argument("--workflow-name", default=os.environ.get("GITHUB_WORKFLOW", "Security CI Pipeline"))
    parser.add_argument("--run-id", default=os.environ.get("GITHUB_RUN_ID", ""))
    parser.add_argument("--run-url", default="")
    parser.add_argument("--commit-sha", default=os.environ.get("GITHUB_SHA", ""))
    parser.add_argument("--branch", default=os.environ.get("GITHUB_HEAD_REF") or os.environ.get("GITHUB_REF_NAME", ""))
    parser.add_argument("--pr", default="")
    parser.add_argument("--sonar-project-key", default=os.environ.get("SONAR_PROJECT_KEY", ""))
    parser.add_argument("--sonar-org", default=os.environ.get("SONAR_ORGANIZATION", ""))
    parser.add_argument("--sonar-token", default=os.environ.get("SONAR_TOKEN", ""))
    parser.add_argument("--zap-report", default="report_json.json")
    parser.add_argument("--test-report", default="test-report.xml")
    parser.add_argument("--validate-status", default="success")
    parser.add_argument("--sonar-status", default="success")
    parser.add_argument("--dast-status", default="success")
    parser.add_argument("--zap-rules", default=".zap/rules.tsv")
    parser.add_argument("--output-dir", default="pipeline-reports")

    args = parser.parse_args()

    # Automatically construct run URL if not explicitly provided
    run_url = args.run_url
    if not run_url and args.repo and args.run_id:
        server = os.environ.get("GITHUB_SERVER_URL", "https://github.com")
        run_url = f"{server}/{args.repo}/actions/runs/{args.run_id}"

    pr_num = args.pr
    if not pr_num and os.environ.get("GITHUB_REF", "").startswith("refs/pull/"):
        parts = os.environ.get("GITHUB_REF", "").split("/")
        if len(parts) >= 3 and parts[2].isdigit():
            pr_num = parts[2]

    print("=======================================================")
    print("   Generating AI-Ready CI/CD Pipeline Report          ")
    print("=======================================================")
    print(f"Repository:     {args.repo}")
    print(f"Workflow:       {args.workflow_name} (Run: {args.run_id})")
    print(f"Branch/PR:      {args.branch} (PR #{pr_num or 'N/A'})")
    print(f"Sonar Key:      {args.sonar_project_key}")
    print(f"ZAP Report:     {args.zap_report}")
    print(f"Test Report:    {args.test_report}")
    print(f"Output Dir:     {args.output_dir}")
    print("=======================================================")

    json_path, html_path = generate_report(
        repo=args.repo,
        workflow_name=args.workflow_name,
        run_id=args.run_id,
        run_url=run_url,
        commit_sha=args.commit_sha,
        branch=args.branch,
        pr_number=pr_num,
        sonar_project_key=args.sonar_project_key,
        sonar_org=args.sonar_org,
        sonar_token=args.sonar_token,
        zap_report_path=args.zap_report,
        test_report_path=args.test_report,
        validate_status=args.validate_status,
        sonar_status=args.sonar_status,
        dast_status=args.dast_status,
        output_dir=args.output_dir,
        zap_rules_path=args.zap_rules
    )

    print(f"✅ Generated JSON Report: {json_path}")
    print(f"✅ Generated HTML Report: {html_path}")
    print("Report generation completed successfully.")


if __name__ == "__main__":
    main()
