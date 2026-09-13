// ============================================================
// Concert Ticketing System - Class Design Specification v2.1.0
// For Visual Paradigm Instant Reverse (Java)
// ============================================================

package concertticketing;

import java.util.Date;
import java.util.List;

// ============================================================
// 4. ENUMERATIONS
// ============================================================

enum Role { CUSTOMER, ORGANIZER, STAFF, ADMIN }

enum AccountStatus { ACTIVE, SUSPENDED }

enum ConcertStatus { DRAFT, SCHEDULED, ON_SALE, SALE_CLOSED, SALE_SUSPENDED, COMPLETED }

enum ContactType { PHONE, LINE, FACEBOOK, EMAIL }

enum ZoneType { SEATED, STANDING }

enum SeatStatus { AVAILABLE, LOCKED, BOOKED }

enum BookingStatus { PENDING_PAYMENT, CONFIRMED, CANCELLED, EXPIRED }

enum PaymentMethod { PROMPTPAY, CREDIT_CARD }

enum PaymentStatus { PENDING, COMPLETED, FAILED, EXPIRED }

enum RiskLevel { LOW, MEDIUM, HIGH }

enum SeatLockStatus { LOCKED, EXPIRED, RELEASED, CONVERTED_TO_BOOKING }

enum AnomalyStatus { UNRESOLVED, INVESTIGATING, RESOLVED }

enum AnomalyType { PAYMENT_FRAUD_CARD_TESTING, SEAT_HOARDING_ABUSE, CHARGEBACK_ABUSE }

// ============================================================
// 3. ENTITIES
// ============================================================

class Company {
    private String companyID;
    private String name;
    private String initials;
    private String description;
    private Date createdAt;

    public String getCompanyInfo() { return null; }
    public List<Concert> listEvents() { return null; }
}

abstract class AppUser {
    protected String userID;
    protected String passwordHash;
    protected Role role;
    protected AccountStatus accountStatus;
    protected Date createdAt;

    public boolean login() { return false; }
    public void logout() { }
}

class Customer extends AppUser {
    private String customerID;
    private String email;
    private String phone;
    private String firstName;
    private String lastName;

    public void register() { }
    public List<Concert> searchConcert(String keyword) { return null; }
    public Booking createBooking(Concert concert, List<Seat> seats, List<BookingStandingItem> standingItems) { return null; }
    public List<Booking> viewHistory() { return null; }
    public List<Ticket> viewTickets() { return null; }
}

class EventOrganizer extends AppUser {
    private String organizerID;
    private String companyID;
    private String email;
    private String username;

    public Concert createConcert(Object details) { return null; }
    public void defineSeatMap(Concert concert, Object mapDetails) { }
    public Report viewSalesRealTime(Concert concert) { return null; }
}

class Staff extends AppUser {
    private String staffID;
    private String companyID;
    private String createdByOrganizerID;
    private String username;

    public boolean scanTicketQR(String qrCode) { return false; }
}

class Admin extends AppUser {
    private String adminID;
    private String email;
    private String username;

    public void manageUser(String userID, String action) { }
    public List<SystemAnomaly> viewAnomalies(Object filters) { return null; }
    public void acknowledgeAnomaly(String anomalyID) { }
    public void resolveAnomaly(String anomalyID, String reason, List<AnomalyAttachment> files) { }
}

class Concert {
    private String concertID;
    private String companyID;
    private String organizerID;
    private String name;
    private String artist;
    private String venue;
    private Date eventDatetime;
    private String description;
    private String posterPath;
    private String seatMapImagePath;
    private Date saleStartDatetime;
    private Date saleEndDatetime;
    private int maxTicketPerOrder;
    private ConcertStatus status;
    private String concertCode;

    public Object getDetails() { return null; }
}

class ConcertContact {
    private String contactID;
    private String concertID;
    private ContactType contactType;
    private String value;
}

class Zone {
    private String zoneID;
    private String concertID;
    private String name;
    private ZoneType zoneType;
    private double price;
    private int capacity;
    private String color;

    public List<Seat> getAvailableSeats() { return null; }
    public int getAvailableCapacity() { return 0; }
}

class ZoneSeatPattern {
    private String patternID;
    private String zoneID;
    private String patternType;
    private int startRow;
    private int startSeatNumber;
    private int rowCount;
    private int seatsPerRow;

    public List<Seat> generateSeats() { return null; }
}

class Seat {
    private String seatID;
    private String zoneID;
    private String row;
    private int number;
    private SeatStatus status;

    public void lockSeat() { }
    public void unlockSeat() { }
    public void confirmSeat() { }
}

class Booking {
    private String bookingID;
    private String customerID;
    private String concertID;
    private Date bookingDate;
    private double totalAmount;
    private BookingStatus status;
    private Date expiryTime;
    private String bookingCode;

    public double calculateTotal() { return 0.0; }
    public void confirmBooking() { }
    public void cancelBooking() { }
    public boolean checkExpiry() { return false; }
}

class BookingSeat {
    private String bookingID;
    private String seatID;
    private String zoneNameSnapshot;
    private String seatLabelSnapshot;
    private double unitPriceSnapshot;
}

class BookingStandingItem {
    private String bookingID;
    private String zoneID;
    private int quantity;
    private String zoneNameSnapshot;
    private double unitPriceSnapshot;
}

class Payment {
    private String paymentID;
    private String bookingID;
    private double amount;
    private PaymentMethod method;
    private PaymentStatus status;
    private Date paymentDate;
    private String providerTransactionID;
    private String declineCode;
    private RiskLevel riskLevel;
    private String cardFingerprint;

    public boolean processPayment() { return false; }
    public boolean verifyStatus() { return false; }
}

class Chargeback {
    private String chargebackID;
    private String paymentID;
    private String providerChargebackID;
    private String reason;
    private double amount;
    private Date createdAt;
    private Date resolvedAt;
}

class CustomerOrder {
    private String orderID;
    private String bookingID;
    private String paymentID;
    private Date orderDate;
    private String orderCode;
    private String concertNameSnapshot;
    private String artistSnapshot;
    private String venueSnapshot;
    private Date eventDatetimeSnapshot;

    public List<Ticket> generateTickets() { return null; }
}

class Ticket {
    private String ticketID;
    private String orderID;
    private String concertID;
    private String zoneID;
    private String seatID;
    private String qrCode;
    private boolean isUsed;
    private String ticketCode;
    private String zoneNameSnapshot;
    private String seatLabelSnapshot;
    private double unitPriceSnapshot;

    public String generateQR() { return null; }
    public boolean validateTicket() { return false; }
    public void markAsUsed() { }
}

class SeatLockLog {
    private String lockID;
    private String bookingID;
    private String seatID;
    private Date lockedAt;
    private Date expiredAt;
    private Date releasedAt;
    private SeatLockStatus status;
}

class StaffConcert {
    private String staffID;
    private String concertID;
}

class TicketValidationLog {
    private String validationID;
    private String ticketID;
    private String staffID;
    private Date validationTime;
    private String result;
}

class SystemAnomaly {
    private String anomalyID;
    private String bookingID;
    private String reviewedByAdminID;
    private AnomalyType type;
    private String message;
    private Date timestamp;
    private AnomalyStatus status;
    private String resolveReason;
    private String anomalyCode;

    public void updateStatus(AnomalyStatus newStatus) { }
    public void addAttachment(AnomalyAttachment file) { }
}

class AnomalyAttachment {
    private String attachmentID;
    private String anomalyID;
    private String filePath;
}

class AuditLog {
    private String logID;
    private String userID;
    private String action;
    private Date timestamp;
    private String ipAddress;

    public String getDetails() { return null; }
}

class Report {
    private String reportID;
    private String concertID;
    private double totalSales;
    private Date generatedAt;
}

// ============================================================
// 2. CONTROLLERS
// ============================================================

class AuthController {
    public Customer registerCustomer(Object data) { return null; }
    public boolean login(String identifier, String password) { return false; }
    public void logout(String userID) { }
}

class ConcertController {
    public List<Concert> searchConcert(String keyword) { return null; }
    public List<Concert> getConcertList() { return null; }
    public Concert getConcertDetail(String concertID) { return null; }
    public Concert createConcert(Object details) { return null; }
    public void updateConcert(String concertID, Object details) { }
    public ConcertContact upsertConcertContact(String concertID, ConcertContact contact) { return null; }
}

class BookingController {
    public Booking createBooking(String customerID, String concertID, List<Seat> seats, List<BookingStandingItem> standingItems) { return null; }
    public List<Booking> getBookingHistory(String customerID) { return null; }
    public List<Customer> getCustomerContact(String concertID) { return null; }
}

class PaymentController {
    public Payment processPayment(String bookingID, PaymentMethod method) { return null; }
    public boolean verifyPayment(String paymentID) { return false; }
    public CustomerOrder completePayment(String paymentID) { return null; }
}

class TicketController {
    public List<Ticket> generateTickets(String orderID) { return null; }
    public List<Ticket> getTickets(String customerID) { return null; }
}

class UserController {
    public AppUser getProfile(String userID) { return null; }
    public void updateProfile(String userID, Object data) { }
    public void changePassword(String userID, String password) { }
}

class SeatMapController {
    public Zone createZone(String concertID, Object details) { return null; }
    public void updateZone(String zoneID, Object details) { }
    public ZoneSeatPattern defineSeatPattern(String zoneID, Object pattern) { return null; }
    public List<Seat> generateSeats(String zoneID) { return null; }
    public void updateSeat(String seatID, Object details) { }
}

class ReportController {
    public Report getMonthlySales(String concertID) { return null; }
    public Report getYearlySales(String concertID) { return null; }
}

class StaffController {
    public Staff createStaff(String companyID, String organizerID, Object data) { return null; }
    public void assignStaffToConcert(String staffID, String concertID) { }
    public void unassignStaffFromConcert(String staffID, String concertID) { }
    public List<Concert> getAssignedConcerts(String staffID) { return null; }
}

class TicketValidationController {
    public boolean scanTicketQR(String qrCode) { return false; }
    public boolean validateTicketCode(String ticketCode) { return false; }
    public List<TicketValidationLog> getValidationHistory(String concertID) { return null; }
}

class UserManagementController {
    public List<AppUser> getUsers() { return null; }
    public List<AppUser> searchUser(String keyword) { return null; }
    public void suspendUser(String userID) { }
    public void activateUser(String userID) { }
    public void changeOrganizerCompany(String userID, String companyID) { }
    public EventOrganizer createOrganizer(Object data) { return null; }
    public Admin createAdmin(Object data) { return null; }
}

class BookingManagementController {
    public List<Booking> getBookings() { return null; }
    public List<Booking> searchBooking(String keyword) { return null; }
    public List<SeatLockLog> getSeatLockHistory(String seatID) { return null; }
}

class AuditLogController {
    public List<AuditLog> getLogs() { return null; }
    public List<AuditLog> searchLogs(String keyword) { return null; }
}

class AnomalyController {
    public List<SystemAnomaly> getAnomalies() { return null; }
    public SystemAnomaly getAnomalyDetail(String anomalyCode) { return null; }
    public void updateAnomalyStatus(String anomalyCode, AnomalyStatus status) { }
    public void executeAction(String anomalyCode, String action) { }
}

class ChargebackController {
    public List<Chargeback> getChargebacks(String paymentID) { return null; }
    public Chargeback syncChargebackFromProvider(String providerChargebackID) { return null; }
}

// ============================================================
// 1. INTERFACES (Boundary)
// ============================================================

interface RegisterInterface {
    void submitRegistration(Object data);
}

interface ConcertSearchInterface {
    void searchConcert(String keyword);
}

interface ConcertListInterface {
    void displayConcertList();
}

interface ConcertDetailInterface {
    void displayConcertDetail(String concertID);
}

interface ETicketInterface {
    void displayTickets();
}

interface SeatSelectionInterface {
    void selectZone(String zoneID);
    void selectSeat(String seatID);
    void selectStandingQuantity(String zoneID, int quantity);
    void submitBooking();
}

interface PaymentInterface {
    void selectPaymentMethod(PaymentMethod method);
    void submitPayment();
    void displayPaymentResult();
}

interface ProfileInterface {
    void displayProfile();
    void submitProfileUpdate(Object data);
    void submitPasswordChange(String password);
}

interface BookingHistoryInterface {
    void displayBookingHistory();
}

interface ConcertManagementInterface {
    void submitConcert(Object details);
    void submitConcertUpdate(String concertID, Object details);
    void submitConcertContact(String concertID, ConcertContact contact);
}

interface SeatMapManagementInterface {
    void submitZone(Object details);
    void submitZoneUpdate(String zoneID, Object details);
    void submitSeatPattern(String zoneID, Object pattern);
    void submitSeatMap(String zoneID);
    void submitSeatUpdate(String seatID, Object details);
}

interface SalesReportInterface {
    void displayMonthlySales(String concertID);
    void displayYearlySales(String concertID);
}

interface CustomerContactInterface {
    void displayCustomerContacts(String concertID);
}

interface StaffManagementInterface {
    void submitStaff(Object data);
    void displayAssignedConcerts(String staffID);
    void submitStaffAssignment(String staffID, String concertID);
    void submitStaffUnassignment(String staffID, String concertID);
}

interface TicketScannerInterface {
    void scanQR(String qrCode);
    void checkTicketCode(String ticketCode);
    void displayValidationResult();
    void displayValidationHistory();
}

interface UserManagementInterface {
    void displayUsers();
    void searchUser(String keyword);
    void submitSuspend(String userID);
    void submitActivate(String userID);
    void submitCompanyChange(String userID, String companyID);
}

interface BookingManagementInterface {
    void displayBookings();
    void searchBooking(String keyword);
    void displaySeatLockHistory(String seatID);
}

interface AuditLogInterface {
    void displayLogs();
    void searchLogs(String keyword);
}

interface SystemAnomalyInterface {
    void displayAnomalies();
    void displayAnomalyDetail(String anomalyCode);
    void submitAnomalyStatus(String anomalyCode, AnomalyStatus status);
    void submitAnomalyAction(String anomalyCode, String action);
}

interface ChargebackManagementInterface {
    void displayChargebacks();
}

interface AccountManagementInterface {
    void submitOrganizer(Object data);
    void submitAdmin(Object data);
}