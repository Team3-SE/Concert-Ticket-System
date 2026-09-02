{{-- 
    admin/audit-logs/show.blade.php
    แสดงรายละเอียด audit log entry — ใช้ getDetails() ตาม class diagram
    Author : AI Agent
    Created : 02/09/2026
--}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log Detail — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .detail-card { background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .detail-row { padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: 600; color: #6c757d; min-width: 160px; }
        .detail-value { color: #212529; }
        .details-string { background: #f1f3f5; border-radius: 6px; padding: 15px 20px; font-size: 0.95rem; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container py-4" style="max-width: 800px;">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="/admin/audit-logs" class="text-decoration-none text-muted mb-2 d-inline-block">
                    ← กลับไปรายการ Audit Logs
                </a>
                <h2 class="mb-0">📋 Audit Log Detail</h2>
            </div>
        </div>

        {{-- getDetails() Output --}}
        <div class="detail-card mb-4">
            <h5 class="mb-3">📝 getDetails()</h5>
            <div class="details-string">
                {{ $details }}
            </div>
        </div>

        {{-- Full Detail --}}
        <div class="detail-card">
            <h5 class="mb-3">🔍 ข้อมูลแบบเต็ม</h5>

            <div class="detail-row d-flex">
                <span class="detail-label">Log ID</span>
                <span class="detail-value"><code>{{ $log->log_id }}</code></span>
            </div>

            <div class="detail-row d-flex">
                <span class="detail-label">Action</span>
                <span class="detail-value">
                    @php
                        $actionParts = explode('.', $log->action);
                        $actionCategory = $actionParts[0] ?? '';
                        $badgeColors = [
                            'auth'      => 'bg-info',
                            'booking'   => 'bg-primary',
                            'payment'   => 'bg-success',
                            'ticket'    => 'bg-warning text-dark',
                            'admin'     => 'bg-danger',
                            'organizer' => 'bg-secondary',
                            'security'  => 'bg-dark',
                        ];
                        $badgeColor = $badgeColors[$actionCategory] ?? 'bg-secondary';
                    @endphp
                    <span class="badge {{ $badgeColor }}">{{ $actionCategory }}</span>
                    <span class="ms-1">{{ $log->action }}</span>
                </span>
            </div>

            <div class="detail-row d-flex">
                <span class="detail-label">ผู้ดำเนินการ</span>
                <span class="detail-value">
                    @if($log->user)
                        <span class="badge bg-light text-dark">{{ $log->user->role }}</span>
                        <code class="ms-1">{{ $log->user_id }}</code>
                    @else
                        <span class="text-muted">System / Guest</span>
                    @endif
                </span>
            </div>

            <div class="detail-row d-flex">
                <span class="detail-label">เวลา (Timestamp)</span>
                <span class="detail-value">
                    {{ $log->timestamp ? $log->timestamp->format('d/m/Y H:i:s') : 'N/A' }}
                </span>
            </div>

            <div class="detail-row d-flex">
                <span class="detail-label">IP Address</span>
                <span class="detail-value">
                    <code>{{ $log->ip_address ?? 'N/A' }}</code>
                </span>
            </div>
        </div>

        {{-- Back Button --}}
        <div class="mt-4">
            <a href="/admin/audit-logs" class="btn btn-outline-secondary">
                ← กลับไปรายการ Audit Logs
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

