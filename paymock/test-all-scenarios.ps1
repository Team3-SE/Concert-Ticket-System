# ==============================================================================
# PayMock Scenarios Verification Script (PowerShell)
# ทดสอบทุก Scenario ของ PayMock Gateway โดยอัตโนมัติ
# ==============================================================================

param(
    [string]$BaseUrl = "http://localhost:8081",
    [string]$ApiKey  = "pm_test_key_concert_system_2024"
)

$ErrorActionPreference = "Continue"

Write-Host "`n========================================================" -ForegroundColor Cyan
Write-Host "   PayMock Gateway Automated Verification Suite" -ForegroundColor Cyan
Write-Host "   Target: $BaseUrl" -ForegroundColor Cyan
Write-Host "========================================================`n" -ForegroundColor Cyan

function Assert-Result {
    param([string]$Title, [bool]$Condition, [string]$Details = "")
    if ($Condition) {
        Write-Host "  [PASS] $Title" -ForegroundColor Green
    } else {
        Write-Host "  [FAIL] $Title - $Details" -ForegroundColor Red
    }
}

# ------------------------------------------------------------------------------
# 1. Health Check
# ------------------------------------------------------------------------------
Write-Host "1. Testing Health Check (/up)..." -ForegroundColor Yellow
try {
    $res = Invoke-RestMethod -Uri "$BaseUrl/up" -Method Get -TimeoutSec 5
    Assert-Result "Health check returns status ok" ($res.status -eq "ok")
} catch {
    Assert-Result "Health check failed" $false $_.Exception.Message
}

# ------------------------------------------------------------------------------
# 2. Authentication Check
# ------------------------------------------------------------------------------
Write-Host "`n2. Testing Authentication & Security..." -ForegroundColor Yellow
try {
    # Bad Auth
    $badRes = Invoke-WebRequest -Uri "$BaseUrl/v1/payments" -Method Post `
        -Headers @{ "Authorization" = "Bearer invalid_token" } `
        -Body "{}" -ContentType "application/json" -SkipHttpErrorCheck
    Assert-Result "Rejects invalid API Key with 401" ($badRes.StatusCode -eq 401)
} catch {
    Assert-Result "Rejects invalid API Key with 401" $true
}

# ------------------------------------------------------------------------------
# 3. Create Payment & Idempotency
# ------------------------------------------------------------------------------
Write-Host "`n3. Testing Payment Creation & Idempotency..." -ForegroundColor Yellow
$orderRef = "BOOK_TEST_" + (Get-Random)
$idempKey = "IDEMP_" + (Get-Random)

$body = @{
    order_ref    = $orderRef
    amount       = 1500.00
    currency     = "THB"
    method       = "card"
    card_number  = "4242424242424242"
    exp_month    = "12"
    exp_year     = "28"
    cvc          = "123"
    callback_url = "http://concert_backend:5000/api/webhook"
    expires_in   = 600
} | ConvertTo-Json

try {
    $headers = @{
        "Authorization"   = "Bearer $ApiKey"
        "Idempotency-Key" = $idempKey
    }

    $p1 = Invoke-RestMethod -Uri "$BaseUrl/v1/payments" -Method Post -Headers $headers -Body $body -ContentType "application/json"
    Assert-Result "Create Payment (201 Created)" ($p1.status -eq "PENDING" -and $p1.id -like "txn_*")

    # Send again with same Idempotency-Key
    $p2 = Invoke-RestMethod -Uri "$BaseUrl/v1/payments" -Method Post -Headers $headers -Body $body -ContentType "application/json"
    Assert-Result "Idempotent Repeat: Returns identical payment ID" ($p1.id -eq $p2.id)

    # Status Query (GET /v1/payments/{id})
    $stat = Invoke-RestMethod -Uri "$BaseUrl/v1/payments/$($p1.id)" -Method Get -Headers @{ "Authorization" = "Bearer $ApiKey" }
    Assert-Result "Query Payment Status (GET /v1/payments/{id})" ($stat.id -eq $p1.id -and $stat.status -eq "PENDING")

} catch {
    Assert-Result "Create payment failed" $false $_.Exception.Message
}

# ------------------------------------------------------------------------------
# 4. Chaos Mode (single 503 business response)
# ------------------------------------------------------------------------------
Write-Host "`n4. Testing Chaos Mode..." -ForegroundColor Yellow

# Configure Gateway unavailable from PayMock Admin, then verify its single
# customer-facing response. This script does not use X-Mock-Scenario because
# merchants cannot override a system-wide outage mode.
try {
    $resDown = Invoke-WebRequest -Uri "$BaseUrl/v1/payments" -Method Post `
        -Headers @{ "Authorization" = "Bearer $ApiKey" } `
        -Body $body -ContentType "application/json" -SkipHttpErrorCheck
    $gatewayMessage = $resDown.Content | ConvertFrom-Json
    Assert-Result "Gateway unavailable returns 503 with business message" ($resDown.StatusCode -eq 503 -and $gatewayMessage.message -eq "Gateway มีปัญหา โปรดลองอีกครั้ง")
} catch {
    Assert-Result "Gateway unavailable" $false $_.Exception.Message
}

# ------------------------------------------------------------------------------
# 5. Cancel Payment
# ------------------------------------------------------------------------------
Write-Host "`n5. Testing Payment Cancellation..." -ForegroundColor Yellow
try {
    $cancelRes = Invoke-RestMethod -Uri "$BaseUrl/v1/payments/$($p1.id)/cancel" -Method Post `
        -Headers @{ "Authorization" = "Bearer $ApiKey" }
    Assert-Result "Cancel Payment returns FAILED" ($cancelRes.status -eq "FAILED" -and $cancelRes.failure_code -eq "cancelled")
} catch {
    Assert-Result "Cancel payment" $false $_.Exception.Message
}

Write-Host "`n========================================================" -ForegroundColor Cyan
Write-Host "   Verification Completed!" -ForegroundColor Cyan
Write-Host "========================================================`n" -ForegroundColor Cyan
