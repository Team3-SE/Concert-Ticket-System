<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * AuditLogService
 * handle business logic for audit log recording and querying — ห้าม log password, credential, session secret
 * Author : AI Agent
 * Created : 02/09/2026
 */
class AuditLogService
{
    /**
     * log(action, userId, ipAddress)
     * บันทึก audit log entry ทั่วไปลงฐานข้อมูล
     * param : action, userId, ipAddress
     * return : AuditLog
     */
    public static function log(string $action, ?string $userId = null, ?string $ipAddress = null): AuditLog
    {
        return AuditLog::create([
            'user_id'    => $userId,
            'action'     => $action,
            'timestamp'  => Carbon::now(),
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * logFromRequest(action, request)
     * บันทึก audit log โดยดึง user_id และ ip_address จาก Request object อัตโนมัติ
     * param : action, request
     * return : AuditLog
     */
    public static function logFromRequest(string $action, Request $request): AuditLog
    {
        $userId = null;

        if ($request->user()) {
            $userId = $request->user()->user_id ?? $request->user()->getAuthIdentifier();
        }

        return self::log($action, $userId, $request->ip());
    }

    /**
     * logAuth(action, userId, request)
     * บันทึก authentication event เช่น login, logout, register
     * param : action, userId, request
     * return : AuditLog
     */
    public static function logAuth(string $action, ?string $userId, Request $request): AuditLog
    {
        $prefixedAction = 'auth.' . $action;

        return self::log($prefixedAction, $userId, $request->ip());
    }

    /**
     * logBooking(action, userId, request)
     * บันทึก booking-related event เช่น create, cancel, confirm
     * param : action, userId, request
     * return : AuditLog
     */
    public static function logBooking(string $action, ?string $userId, Request $request): AuditLog
    {
        $prefixedAction = 'booking.' . $action;

        return self::log($prefixedAction, $userId, $request->ip());
    }

    /**
     * logPayment(action, userId, request)
     * บันทึก payment event เช่น initiate, success, failed — ห้าม log ข้อมูล payment detail
     * param : action, userId, request
     * return : AuditLog
     */
    public static function logPayment(string $action, ?string $userId, Request $request): AuditLog
    {
        $prefixedAction = 'payment.' . $action;

        return self::log($prefixedAction, $userId, $request->ip());
    }

    /**
     * logTicketValidation(action, userId, request)
     * บันทึก ticket validation event เช่น scan, validate_success, validate_failed
     * param : action, userId, request
     * return : AuditLog
     */
    public static function logTicketValidation(string $action, ?string $userId, Request $request): AuditLog
    {
        $prefixedAction = 'ticket.' . $action;

        return self::log($prefixedAction, $userId, $request->ip());
    }

    /**
     * logAdminAction(action, userId, request)
     * บันทึก admin activity เช่น suspend_user, ban_user, review_anomaly
     * param : action, userId, request
     * return : AuditLog
     */
    public static function logAdminAction(string $action, ?string $userId, Request $request): AuditLog
    {
        $prefixedAction = 'admin.' . $action;

        return self::log($prefixedAction, $userId, $request->ip());
    }

    /**
     * logOrganizerAction(action, userId, request)
     * บันทึก organizer activity เช่น create_concert, update_zone, manage_staff
     * param : action, userId, request
     * return : AuditLog
     */
    public static function logOrganizerAction(string $action, ?string $userId, Request $request): AuditLog
    {
        $prefixedAction = 'organizer.' . $action;

        return self::log($prefixedAction, $userId, $request->ip());
    }

    /**
     * logSecurityEvent(action, userId, request)
     * บันทึก security event เช่น brute_force_detected, unauthorized_access, csrf_violation
     * param : action, userId, request
     * return : AuditLog
     */
    public static function logSecurityEvent(string $action, ?string $userId, Request $request): AuditLog
    {
        $prefixedAction = 'security.' . $action;

        return self::log($prefixedAction, $userId, $request->ip());
    }

    /**
     * getFilteredLogs(filters, perPage)
     * ดึง audit log แบบ filter + paginate สำหรับ Admin UI
     * param : filters (array), perPage (int)
     * return : \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getFilteredLogs(array $filters = [], int $perPage = 20)
    {
        $query = AuditLog::with('user')->orderBy('timestamp', 'desc');

        if (!empty($filters['action'])) {
            $query->where('action', 'like', '%' . $filters['action'] . '%');
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('timestamp', [$filters['start_date'], $filters['end_date']]);
        } elseif (!empty($filters['start_date'])) {
            $query->where('timestamp', '>=', $filters['start_date']);
        } elseif (!empty($filters['end_date'])) {
            $query->where('timestamp', '<=', $filters['end_date']);
        }

        return $query->paginate($perPage);
    }

    /**
     * getAllLogsForExport(filters)
     * ดึง audit log ทั้งหมดตาม filter สำหรับ export CSV (ไม่ paginate)
     * param : filters (array)
     * return : \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllLogsForExport(array $filters = [])
    {
        $query = AuditLog::with('user')->orderBy('timestamp', 'desc');

        if (!empty($filters['action'])) {
            $query->where('action', 'like', '%' . $filters['action'] . '%');
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('timestamp', [$filters['start_date'], $filters['end_date']]);
        } elseif (!empty($filters['start_date'])) {
            $query->where('timestamp', '>=', $filters['start_date']);
        } elseif (!empty($filters['end_date'])) {
            $query->where('timestamp', '<=', $filters['end_date']);
        }

        return $query->get();
    }
}
