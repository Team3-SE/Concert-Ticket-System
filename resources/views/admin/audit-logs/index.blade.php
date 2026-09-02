{{-- 
    admin/audit-logs/index.blade.php
    แสดงรายการ audit log ทั้งหมด พร้อม filter bar และ pagination สำหรับ Admin
    Author : AI Agent
    Created : 02/09/2026
--}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .filter-bar { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .table-wrapper { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .badge-action { font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">📋 Audit Logs</h2>
                <p class="text-muted mb-0">บันทึกการดำเนินการทั้งหมดในระบบ เพื่อใช้ตรวจสอบย้อนหลัง</p>
            </div>
            <div>
                <a href="/admin/audit-logs/export?{{ http_build_query($filters ?? []) }}" class="btn btn-outline-success">
                    📥 Export CSV
                </a>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="filter-bar">
            <form method="GET" action="/admin/audit-logs" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Action</label>
                    <input type="text" name="action" class="form-control" placeholder="เช่น auth.login"
                           value="{{ $filters['action'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">User ID</label>
                    <input type="text" name="user_id" class="form-control" placeholder="ULID ของ user"
                           value="{{ $filters['user_id'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">IP Address</label>
                    <input type="text" name="ip_address" class="form-control" placeholder="เช่น 192.168.1.1"
                           value="{{ $filters['ip_address'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">ตั้งแต่วันที่</label>
                    <input type="date" name="start_date" class="form-control"
                           value="{{ $filters['start_date'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">ถึงวันที่</label>
                    <input type="date" name="end_date" class="form-control"
                           value="{{ $filters['end_date'] ?? '' }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">🔍 ค้นหา</button>
                    <a href="/admin/audit-logs" class="btn btn-outline-secondary">ล้าง</a>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="table-wrapper">
            @if($logs->isEmpty())
                <div class="text-center py-5 text-muted">
                    <h5>ไม่พบ Audit Log</h5>
                    <p>ยังไม่มีบันทึกในระบบ หรือไม่ตรงกับเงื่อนไขที่ค้นหา</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 22%">Action</th>
                                <th style="width: 18%">ผู้ดำเนินการ</th>
                                <th style="width: 18%">เวลา</th>
                                <th style="width: 15%">IP Address</th>
                                <th style="width: 10%">รายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $index => $log)
                                <tr>
                                    <td class="text-muted">{{ $logs->firstItem() + $index }}</td>
                                    <td>
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
                                        <span class="badge {{ $badgeColor }} badge-action">{{ $actionCategory }}</span>
                                        <span class="ms-1">{{ $log->action }}</span>
                                    </td>
                                    <td>
                                        @if($log->user)
                                            <span class="badge bg-light text-dark">{{ $log->user->role }}</span>
                                            <br>
                                            <small class="text-muted" title="{{ $log->user_id }}">
                                                {{ Str::limit($log->user_id, 12) }}
                                            </small>
                                        @else
                                            <span class="text-muted">System / Guest</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $log->timestamp ? $log->timestamp->format('d/m/Y H:i:s') : 'N/A' }}
                                    </td>
                                    <td>
                                        <code>{{ $log->ip_address ?? 'N/A' }}</code>
                                    </td>
                                    <td>
                                        <a href="/admin/audit-logs/{{ $log->log_id }}" class="btn btn-sm btn-outline-primary">
                                            ดู
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">
                        แสดง {{ $logs->firstItem() }}–{{ $logs->lastItem() }} จาก {{ $logs->total() }} รายการ
                    </small>
                    {{ $logs->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

