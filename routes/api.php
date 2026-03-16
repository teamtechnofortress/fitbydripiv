<?php

use Illuminate\Http\Request;
use App\Models\ChiefComplaint;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TwoFactorController;
use App\Http\Controllers\API\Auth\EmailVerificationController;
use App\Http\Controllers\API\Auth\PasswordResetController;
use App\Http\Controllers\API\ProfileProgressController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\TodayVisitController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\SalesMetricsController;
use App\Http\Controllers\ReportsManageController;
use App\Http\Controllers\PatientAppointmentController;
use App\Http\Controllers\CmsPublicController;
use App\Http\Controllers\CmsAdminController;
use App\Http\Controllers\CmsUploadController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group( function(){

    Route::post('auth/login',    [AuthController::class, 'signin'])->name('login');
    Route::post('auth/logout',   [AuthController::class, 'signout'])->name('logout');
    Route::post('auth/register', [AuthController::class, 'signup']);
    Route::post('auth/simple-register', [AuthController::class, 'simpleSignup']);
    Route::post('auth/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')
        ->name('auth.forgot-password');
    Route::post('auth/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:5,1')
        ->name('auth.reset-password');
    Route::post('auth/verify-2fa', [TwoFactorController::class, 'verify'])->name('auth.verify-2fa');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/2fa-status', [TwoFactorController::class, 'status'])->name('auth.2fa-status');
        Route::post('auth/enable-2fa', [TwoFactorController::class, 'enable'])->name('auth.enable-2fa');
        Route::post('auth/confirm-2fa', [TwoFactorController::class, 'confirm'])->name('auth.confirm-2fa');
        Route::post('auth/disable-2fa', [TwoFactorController::class, 'disable'])->name('auth.disable-2fa');
        Route::post('auth/regenerate-2fa', [TwoFactorController::class, 'regenerate'])->name('auth.regenerate-2fa');

        Route::get('auth/email/status', [EmailVerificationController::class, 'status'])->name('auth.email-status');
        Route::post('auth/email/send-verification', [EmailVerificationController::class, 'send'])
            ->middleware('throttle:email-verification')
            ->name('auth.email-send');
    });

    Route::get('auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('auth.email-verify');

    Route::post('checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');
    Route::post('subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    //Patient
    Route::get('get/patient-by-name', [PatientController::class, 'getPatientByName'])->name('getPatientByName');
    Route::get('get/patient-history-by-name', [PatientController::class, 'getPatientAndHistoryByName'])->name('getPatientHistoryByName');
    Route::get('get/patient-history-by-id',   [PatientController::class, 'getPatientAndHistoryById'])->name('getPatientHistoryById');
    Route::get('get/patient-encounter-by-id',   [PatientController::class, 'getPatientAndEncounterById'])->name('getPatientEncounterById');
    Route::post('update/patient/{id}', [PatientController::class, 'updatePatient'])->name('updatePatient');
    Route::get('get/patient-by-phone', [PatientController::class, 'getPatientByPhone'])->name('getPatientByPhone');

    Route::post('save/intake1', [PatientController::class, 'saveIntake1'])->name('saveIntake1');
    Route::post('save/intake2', [PatientController::class, 'saveIntake2'])->name('saveIntake2');
    Route::post('save/intake3', [PatientController::class, 'saveIntake3'])->name('saveIntake3');

    Route::post('save/encounter', [PatientController::class, 'saveEncounter'])->name('saveEncounter');
    Route::get('delete/encounter/{id}', [PatientController::class, 'deleteEncounter'])->name('deleteEncounter');
    Route::post('save/invoice', [PatientController::class, 'saveInvoice'])->name('saveInvoice');   
    Route::get('send/invoice',  [PatientController::class, 'sendInvoiceAgain'])->name('sendInvoice');   
    

    //Upload
    Route::post('/upload-endpoint', [UploadController::class, 'doUpload'])->name('uploadEndpoint');
    Route::post('/logo-upload',     [UploadController::class, 'logoUpload'])->name('logoUpload');
    Route::get('/get-logo',         [UploadController::class, 'getLogo'])->name('getLogo');
    Route::post('/instruction-upload', [UploadController::class, 'instructionUpload'])->name('instructionUpload');
    Route::get('/get-instruction',     [UploadController::class, 'getInstruction'])->name('getInstruction');

    //Staff
    Route::group(['middleware' => ['auth:sanctum', 'check.deleted']], function() {
        Route::get('profile-progress', [ProfileProgressController::class, 'show'])->name('profile-progress.show');
        Route::get('profile-progress/step/{step}', [ProfileProgressController::class, 'showStep'])->name('profile-progress.step.show');
        Route::post('profile-progress/step-2', [ProfileProgressController::class, 'saveStep2'])->name('profile-progress.step2');
        Route::post('profile-progress/step-3', [ProfileProgressController::class, 'saveStep3'])->name('profile-progress.step3');
        Route::post('profile-progress/step-4', [ProfileProgressController::class, 'saveStep4'])->name('profile-progress.step4');
        Route::post('profile-progress/step-5', [ProfileProgressController::class, 'saveStep5'])->name('profile-progress.step5');
        Route::post('profile-progress/skip', [ProfileProgressController::class, 'skip'])->name('profile-progress.skip');

        Route::get("patients",             [PatientController::class, 'getPatients'])->name('getPatients');

        Route::post("auth/logout",         [AuthController::class, 'signout'])->name('logout');
        Route::get("get/profile",          [AuthController::class, 'getProfile'])->name('getProfile');
        Route::post("save/profile",        [AuthController::class, 'saveProfile'])->name('saveProfile');
        Route::post("auth/change-password", [AuthController::class, 'resetPassword'])->name('resetPassword');
        Route::post("auth/confirm-password", [AuthController::class, 'confirmPassword'])->name('confirmPassword');
        Route::post("remove-account",      [AuthController::class, 'removeAccount'])->name('removeAccount');
        Route::post("user/delete",         [AuthController::class, 'userRemove'])->name('userRemove');
        Route::post("user/add",            [AuthController::class, 'userAddNew'])->name('userAddNew');
        Route::get("users",                [AuthController::class, 'getUsers'])->name('getUsers');
        Route::post("user/edit-role",      [AuthController::class, 'changeUserRole']);  
        
        //Security 
        Route::post("auth/security-save",  [AuthController::class, 'saveSecurity'])->name('saveSecurity');

        //Appointment
        Route::get("get/appointments", [AppointmentController::class, 'getAppointments'])->name('getAppointments');
        Route::get("get/appointment/{id}", [AppointmentController::class, 'getAppointment'])->name('getAppointment');
        Route::post('add/appointment', [AppointmentController::class, 'addAppointment'])->name('addAppointment');
        Route::post('update/appointment/{id}', [AppointmentController::class, 'updateAppointment'])->name('updateAppointment');
        Route::post('delete/appointment/{id}', [AppointmentController::class, 'removeAppointment'])->name('removeAppointment');

        //Patient
        Route::get('get/patient-que', [PatientController::class, 'getPatientQue'])->name('getPatientQue');
        Route::post('delete/patient/{id}', [PatientController::class, 'removePatient'])->name('removePatient');

        //Sales Metrics
        Route::get('get/sales-metrics', [SalesMetricsController::class, 'getSalesMetrics'])->name('getSalesMetrics');

        //Staff #########################
        Route::get('get/staffs', [StaffController::class, 'getAllStaffs']);
        Route::get('get/members', [StaffController::class, 'getAllMembers']);
        Route::post('add/staff', [StaffController::class, 'addStaff']);
        Route::post('update/staff/{id}', [StaffController::class, 'updateStaff']);
        Route::post('delete/staff/{id}', [StaffController::class, 'deleteStaff']);
        Route::get('get/staff-by-name', [StaffController::class, 'getStaffByName'])->name('getStaffByName');

        Route::post('save/staff-schedule', [StaffController::class, 'saveStaffSchedule'])->name('saveStaffSchedule');
        Route::get('get/staff-schedule', [StaffController::class, 'getStaffSchedule'])->name('getStaffSchedule');

        Route::post('save/staff-payroll', [StaffController::class, 'saveStaffPayroll'])->name('saveStaffPayroll');
        Route::get('get/staff-payroll', [StaffController::class, 'getStaffPayroll'])->name('getStaffPayroll');

        //Inventory #########################
        Route::post('add/inventory', [InventoryController::class, 'addInventory']);
        Route::post('update/inventory/{id}', [InventoryController::class, 'updateInventory']);
        Route::get('get/inventories', [InventoryController::class, 'getAllInventory']);
        Route::post('delete/inventory/{id}', [InventoryController::class, 'deleteInventory']);

        //Chief complaint #########################
        Route::post('add/admin-subject-notes', [TodayVisitController::class, 'addAdminSubjectNotes'])->name('addAdminSubjectNotes');
        Route::post('add/admin-object-notes', [TodayVisitController::class, 'addAdminObjectNotes'])->name('addAdminObjectNotes');
        Route::post('add/admin-assessment-notes', [TodayVisitController::class, 'addAdminAssessmentNotes'])->name('addAdminAssessmentNotes');
        Route::post('add/admin-plan-notes', [TodayVisitController::class, 'addAdminPlanNotes'])->name('addAdminPlanNotes');
        Route::post('add/admin-risk-benefit-reward', [TodayVisitController::class, 'addAdminRiskBenefitReward'])->name('addAdminRiskBenefitReward');

        Route::get('get/admin-notes', [TodayVisitController::class, 'getAdminNotes'])->name('getAdminNotes');
        Route::post('add/procedure-plan-notes', [TodayVisitController::class, 'addProcedurePlanNotes'])->name('addProcedurePlanNotes');
        Route::get('get/procedure-plan-notes', [TodayVisitController::class, 'getProcedurePlanNotes'])->name('getProcedurePlanNotes');          
        Route::post('add/physical-exam', [TodayVisitController::class, 'addPhysicalExam'])->name('addPhysicalExam');
        Route::get('get/physical-exam', [TodayVisitController::class, 'getPhysicalExamByDate'])->name('getPhysicalExamByDate');
        Route::post('add/chief-complaint', [TodayVisitController::class, 'addChiefComplaint'])->name('addChiefComplaint');
        Route::get('get/chief-complaint', [TodayVisitController::class, 'getChiefComplaintByDate'])->name('getChiefComplaintByDate');
        Route::post('update/physical-exam/{id}', [TodayVisitController::class, 'updatePatientPhysicalExamp'])->name('updatePatientPhysicalExamp');
        Route::post('delete/chief-complaint/{id}', [TodayVisitController::class, 'deleteChiefComplaint'])->name('deleteChiefComplaint');
        Route::post('add/assessment', [TodayVisitController::class, 'addAssessment'])->name('addAssessment');
        Route::get('get/assessment-by-date', [TodayVisitController::class, 'getAssessmentByDate'])->name('getAssessmentByDate');
        Route::post('add/patient-plan', [TodayVisitController::class, 'addPatientPlan'])->name('addPatientPlan');
        Route::get('get/patient-plan', [TodayVisitController::class, 'getPatientPlan'])->name('getPatientPlan');
        Route::post('add/patient-procedure', [TodayVisitController::class, 'addPatientProcedure'])->name('addPatientProcedure');
        Route::get('get/patient-procedure', [TodayVisitController::class, 'getPatientProcedure'])->name('getPatientProcedure');

        //Marketing-textCampaign
        Route::post('save/text-campaign', [MarketingController::class, 'saveTextCampaign'])->name('saveTextCampaign');
        Route::post('save/email-campaign', [MarketingController::class, 'saveEmailCampaign'])->name('saveEmailCampaign');
        Route::post('save/special-promo', [MarketingController::class, 'saveSpecialPromo'])->name('saveSpecialPromo');
        Route::get('get/special-promos', [MarketingController::class, 'getSpecialPromos'])->name('getSpecialPromos');
        Route::get('get/text-campaigns', [MarketingController::class, 'getTextCampaigns'])->name('getTextCampaigns');
        Route::get('get/email-campaigns', [MarketingController::class, 'getEmailCampaigns'])->name('getEmailCampaigns');
        Route::post('update/special-promo/{id}', [MarketingController::class, 'updateSpecialPromo'])->name('updateSpecialPromo');
        Route::post('delete/special-promo/{id}', [MarketingController::class, 'removeSpecialPromo'])->name('removeSpecialPromo');
        Route::post('delete/text-campaign/{id}', [MarketingController::class, 'removeTextCampaign'])->name('removeTextCampaign');
        Route::post('delete/email-campaign/{id}', [MarketingController::class, 'removeEmailCampaign'])->name('removeEmailCampaign');

        //Patient Appointment
        Route::get("get/patient-appointments", [PatientAppointmentController::class, 'getAppointments'])->name('getAppointments');
        Route::get("all/patient-appointments", [PatientAppointmentController::class, 'getAllAppointments'])->name('getAllAppointments');
        Route::post('add/patient-appointment', [PatientAppointmentController::class, 'addAppointment'])->name('addAppointment');
        Route::post('update/patient-appointment/{id}', [PatientAppointmentController::class, 'updateAppointment'])->name('updateAppointment');
        Route::post('delete/patient-appointment/{id}', [PatientAppointmentController::class, 'removeAppointment'])->name('removeAppointment');
    
        //Settings 
        Route::get("get/banking", [SettingsController::class, 'getBankingData'])->name('getBankingData');
        Route::post('save/banking', [SettingsController::class, 'saveBankingData'])->name('saveBankingData');
        Route::get("get/business-hours", [SettingsController::class, 'getBusinessHours'])->name('getBusinessHours');
        Route::post('save/business-hours', [SettingsController::class, 'saveBusinessHours'])->name('saveBusinessHours');

        //Reports
        Route::get('get/reports', [PatientController::class, 'getReports'])->name('getReports');

        Route::post('save/chart-history', [ReportsManageController::class, 'saveChartHistory'])->name('saveChartHistory');
        Route::get('get/all-chart-history', [ReportsManageController::class, 'getAllChartHistory'])->name('getAllChartHistory');
        Route::get('delete/chart-history/{id}', [ReportsManageController::class, 'removeChartHistoryById'])->name('removeChartHistoryById');

        Route::post('save/customer-service-report', [ReportsManageController::class, 'saveCustomerServiceReport'])->name('saveCustomerServiceReport');
        Route::get('get/all-customer-service-report', [ReportsManageController::class, 'getAllCustomerServiceReport'])->name('getAllCustomerServiceReport');
        Route::get('delete/customer-service-report/{id}', [ReportsManageController::class, 'removeCustomerServiceReportById'])->name('removeCustomerServiceReportById');

        Route::post('save/patient-metrics', [ReportsManageController::class, 'savePatientMetrics'])->name('savePatientMetrics');
        Route::get('get/all-patient-metrics', [ReportsManageController::class, 'getAllPatientMetrics'])->name('getAllPatientMetrics');
        Route::get('delete/patient-metrics/{id}', [ReportsManageController::class, 'removePatientMetricsById'])->name('removePatientMetricsById');

        Route::post('save/product-metrics', [ReportsManageController::class, 'saveProductMetrics'])->name('saveProductMetrics');
        Route::get('get/all-product-metrics', [ReportsManageController::class, 'getAllProductMetrics'])->name('getAllProductMetrics');
        Route::get('delete/product-metrics/{id}', [ReportsManageController::class, 'removeProductMetricsById'])->name('removeProductMetricsById');

        Route::post('save/appointment-report', [ReportsManageController::class, 'saveAppointmentReport'])->name('saveAppointmentReport');
        Route::get('get/all-appointment-report', [ReportsManageController::class, 'getAllAppointmentReports'])->name('getAllAppointmentReports');
        Route::get('delete/appointment-report/{id}', [ReportsManageController::class, 'removeAppointmentReportById'])->name('removeAppointmentReportById');

        Route::post('save/reward-report', [ReportsManageController::class, 'saveRewardReport'])->name('saveRewardReport');
        Route::get('get/all-reward-report', [ReportsManageController::class, 'getAllRewardReports'])->name('getAllRewardReports');
        Route::get('delete/reward-report/{id}', [ReportsManageController::class, 'removeRewardReportById'])->name('removeRewardReportById');

        Route::post('save/email-text-reward-report', [ReportsManageController::class, 'saveEmailTextRewardReport'])->name('saveEmailTextRewardReport');
        Route::get('get/all-email-text-reward-report', [ReportsManageController::class, 'getAllEmailTextRewardReports'])->name('getAllEmailTextRewardReports');
        Route::get('delete/email-text-reward-report/{id}', [ReportsManageController::class, 'removeEmailTextRewardReportById'])->name('removeEmailTextRewardReportById');
        
        Route::post('save/invoicing-sales-report', [ReportsManageController::class, 'saveInvoicingSalesReport'])->name('saveInvoicingSalesReport');
        Route::get('get/all-invoicing-sales-report', [ReportsManageController::class, 'getAllInvoicingSalesReports'])->name('getAllInvoicingSalesReports');
        Route::get('delete/invoicing-sales-report/{id}', [ReportsManageController::class, 'removeInvoicingSalesReportById'])->name('removeInvoicingSalesReportById');

        Route::post('save/staff-report', [ReportsManageController::class, 'saveStaffReport'])->name('saveStaffReport');
        Route::get('get/all-staff-report', [ReportsManageController::class, 'getAllStaffReports'])->name('getAllStaffReports');
        Route::get('delete/staff-report/{id}', [ReportsManageController::class, 'removeStaffReportById'])->name('removeStaffReportById');

        Route::post('save/payroll-report', [ReportsManageController::class, 'savePayrollReport'])->name('savePayrollReport');
        Route::get('get/all-payroll-report', [ReportsManageController::class, 'getAllPayrollReports'])->name('getAllPayrollReports');
        Route::get('delete/payroll-report/{id}', [ReportsManageController::class, 'removePayrollReportById'])->name('removePayrollReportById');

        Route::post('save/medrx-report', [ReportsManageController::class, 'saveMedrxReport'])->name('saveMedrxReport');
        Route::get('get/all-medrx-report', [ReportsManageController::class, 'getAllMedrxReports'])->name('getAllMedrxReports');
        Route::get('delete/medrx-report/{id}', [ReportsManageController::class, 'removeMedrxReportById'])->name('removeMedrxReportById');

        Route::post('save/signature', [ReportsManageController::class, 'saveSignature'])->name('saveSignature');
        Route::get('get/signature', [ReportsManageController::class, 'getSignature'])->name('getSignature');

        // CMS Admin Routes
        Route::prefix('cms/admin')->group(function () {
            Route::get('categories', [CmsAdminController::class, 'getCategories']);
            Route::post('categories', [CmsAdminController::class, 'saveCategory']);
            Route::delete('categories/{id}', [CmsAdminController::class, 'deleteCategory']);

            Route::get('products', [CmsAdminController::class, 'getProducts']);
            Route::post('products', [CmsAdminController::class, 'saveProduct']);
            Route::delete('products/{id}', [CmsAdminController::class, 'deleteProduct']);

            Route::post('research-links', [CmsAdminController::class, 'saveResearchLink']);
            Route::delete('research-links/{id}', [CmsAdminController::class, 'deleteResearchLink']);

            Route::post('pricing-options', [CmsAdminController::class, 'savePricingOption']);
            Route::delete('pricing-options/{id}', [CmsAdminController::class, 'deletePricingOption']);

            Route::post('faqs', [CmsAdminController::class, 'saveFaq']);
            Route::delete('faqs/{id}', [CmsAdminController::class, 'deleteFaq']);

            Route::post('subscription-discounts', [CmsAdminController::class, 'saveSubscriptionDiscount']);
            Route::delete('subscription-discounts/{id}', [CmsAdminController::class, 'deleteSubscriptionDiscount']);

            Route::post('site-settings', [CmsAdminController::class, 'saveSiteSettings']);

            Route::get('contact-submissions', [CmsAdminController::class, 'getContactSubmissions']);
            Route::post('contact-submissions/{id}/status', [CmsAdminController::class, 'updateContactStatus']);

            Route::post('upload/product-image', [CmsUploadController::class, 'uploadProductImage']);
            Route::post('upload/category-video', [CmsUploadController::class, 'uploadCategoryVideo']);
            Route::post('upload/hero-video', [CmsUploadController::class, 'uploadHeroVideo']);
        });
    });

    // CMS Public Routes (no auth required)
    Route::prefix('cms')->group(function () {
        Route::get('categories', [CmsPublicController::class, 'getCategories']);
        Route::get('categories/{slug}', [CmsPublicController::class, 'getCategoryBySlug']);
        Route::get('categories/{slug}/products', [CmsPublicController::class, 'getProductsByCategory']);
        Route::get('products/featured', [CmsPublicController::class, 'getFeaturedProducts']);
        Route::get('products/selector', [CmsPublicController::class, 'getAllProductsForSelector']);
        Route::get('products/{slug}', [CmsPublicController::class, 'getProductBySlug']);
        Route::get('products/{slug}/pricing', [CmsPublicController::class, 'getProductPricing']);
        Route::get('faqs', [CmsPublicController::class, 'getFaqs']);
        Route::get('faqs/category/{category}', [CmsPublicController::class, 'getFaqsByCategory']);
        Route::get('site-settings', [CmsPublicController::class, 'getSiteSettings']);
        Route::post('contact', [CmsPublicController::class, 'submitContact']);
    });

});
