<?php

use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\DrNetworkAdminController;
use App\Http\Controllers\Admin\DrNetworkFinanceController;
use App\Http\Controllers\Admin\SubscriptionAdminController;
use App\Http\Controllers\Admin\WebhookAdminController;
use App\Http\Controllers\AdminCouponController;
use App\Http\Controllers\AdminMediaController;
use App\Http\Controllers\API\Auth\EmailVerificationController;
use App\Http\Controllers\API\Auth\PasswordResetController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProfileProgressController;
use App\Http\Controllers\API\TwoFactorController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CmsAdminController;
use App\Http\Controllers\CmsPublicController;
use App\Http\Controllers\CmsUploadController;
use App\Http\Controllers\ContentAdminController;
use App\Http\Controllers\ContentPublicController;
use App\Http\Controllers\DrNetwork\DrNetworkFlowController;
use App\Http\Controllers\DrNetwork\NetworkWebhookController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LayoutController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\OrderJourneyController;
use App\Http\Controllers\PatientAppointmentController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientIntakeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductStepController;
use App\Http\Controllers\ReportsManageController;
use App\Http\Controllers\SalesMetricsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TodayVisitController;
use App\Http\Controllers\UploadController;
use App\Http\Middleware\CaptureStripeRawBody;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DocumentConversionController;

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

Route::post('stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->middleware([
        CaptureStripeRawBody::class,
        'throttle:stripe-webhooks',
    ])
    ->withoutMiddleware([
        VerifyCsrfToken::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ])
    ->name('stripe.webhook');

Route::prefix('v1')->group(function () {

    Route::post('auth/login', [AuthController::class, 'signin'])->name('login');
    Route::post('auth/logout', [AuthController::class, 'signout'])->name('logout');
    Route::post('auth/register', [AuthController::class, 'signup']);
    Route::post('auth/simple-register', [AuthController::class, 'simpleSignup']);
    Route::post('auth/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')
        ->name('auth.forgot-password');
    Route::post('auth/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:5,1')
        ->name('auth.reset-password');
    Route::post('auth/verify-2fa', [TwoFactorController::class, 'verify'])->name('auth.verify-2fa');
    Route::get('public/media/pdf-download', [AdminMediaController::class, 'downloadPublicPdf'])
        ->name('public.media.pdf-download');

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

    Route::post('checkout/draft', [CheckoutController::class, 'createDraft'])->name('checkout.draft');
    Route::post('checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('checkout/apply-coupon', [CheckoutController::class, 'applyCoupon'])->name('checkout.apply-coupon');
    Route::get('checkout/payment-confirmation', [CheckoutController::class, 'paymentConfirmation'])->name('checkout.payment-confirmation');
    Route::post('checkout/payment-confirmation', [CheckoutController::class, 'paymentConfirmation'])->name('checkout.payment-confirmation.store');
    Route::get('orders/by-session/{session_id}', [CheckoutController::class, 'showBySession'])->name('orders.by-session');
    Route::post('subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    Route::post('webhooks/dr-networks/{endpointToken}', [NetworkWebhookController::class, 'handle'])
        ->middleware('network.webhook.verify')
        ->withoutMiddleware('auth:sanctum')
        ->name('dr-network.webhooks.receive');

    Route::prefix('patients/{patientId}/intakes')->group(function () {
        Route::get('/', [PatientIntakeController::class, 'index']);
        Route::post('/', [PatientIntakeController::class, 'store']);
        Route::get('/{intakeId}', [PatientIntakeController::class, 'show']);
    });

    Route::get('patients/intake-form', [PatientIntakeController::class, 'fetchByEmail'])->name('patients.intake-form.show');
    Route::get('intake/states', [PatientIntakeController::class, 'states'])->name('patients.intake-form.states');
    Route::post('intake/{order_uuid}', [PatientIntakeController::class, 'submitIntakeForm'])->name('patients.intake-form');

    // Patient
    Route::get('get/patient-by-name', [PatientController::class, 'getPatientByName'])->name('getPatientByName');
    Route::get('get/patient-history-by-name', [PatientController::class, 'getPatientAndHistoryByName'])->name('getPatientHistoryByName');
    Route::get('get/patient-history-by-id', [PatientController::class, 'getPatientAndHistoryById'])->name('getPatientHistoryById');
    Route::get('get/patient-encounter-by-id', [PatientController::class, 'getPatientAndEncounterById'])->name('getPatientEncounterById');
    Route::post('update/patient/{id}', [PatientController::class, 'updatePatient'])->name('updatePatient');
    Route::get('get/patient-by-phone', [PatientController::class, 'getPatientByPhone'])->name('getPatientByPhone');

    Route::post('save/encounter', [PatientController::class, 'saveEncounter'])->name('saveEncounter');
    Route::get('delete/encounter/{id}', [PatientController::class, 'deleteEncounter'])->name('deleteEncounter');
    Route::post('save/invoice', [PatientController::class, 'saveInvoice'])->name('saveInvoice');
    Route::get('send/invoice', [PatientController::class, 'sendInvoiceAgain'])->name('sendInvoice');

    // Upload
    Route::post('/upload-endpoint', [UploadController::class, 'doUpload'])->name('uploadEndpoint');
    Route::post('/logo-upload', [UploadController::class, 'logoUpload'])->name('logoUpload');
    Route::get('/get-logo', [UploadController::class, 'getLogo'])->name('getLogo');
    Route::post('/instruction-upload', [UploadController::class, 'instructionUpload'])->name('instructionUpload');
    Route::get('/get-instruction', [UploadController::class, 'getInstruction'])->name('getInstruction');

    Route::prefix('orders/{order:order_uuid}')->group(function () {
        Route::get('journey', [OrderJourneyController::class, 'show'])->name('orders.journey.show');
        Route::get('workflow/current-step', [DrNetworkFlowController::class, 'currentStep'])->name('orders.workflow.current-step');
        Route::post('dr-network/start', [DrNetworkFlowController::class, 'start'])->name('dr-network.start');
        Route::get('dr-network/current-step', [DrNetworkFlowController::class, 'currentStep'])->name('dr-network.current-step');
        Route::get('dr-network/status', [DrNetworkFlowController::class, 'status'])->name('dr-network.status');
        Route::post('dr-network/submit', [DrNetworkFlowController::class, 'submit'])->name('dr-network.submit');
        Route::post('documents', [DrNetworkFlowController::class, 'uploadDocument'])->name('dr-network.documents.store');
        Route::post('documents/complete', [DrNetworkFlowController::class, 'completeDocumentUpload'])->name('dr-network.documents.complete');
        Route::post('intake-answers', [DrNetworkFlowController::class, 'saveIntakeAnswer'])->name('dr-network.intake-answers.store');
        Route::get('provider-slots', [DrNetworkFlowController::class, 'getProviderSlots'])->name('dr-network.provider-slots.index');
        Route::post('provider-slots/{slotId}/book', [DrNetworkFlowController::class, 'bookSlot'])->name('dr-network.provider-slots.book');
    });

    // Staff
    Route::group(['middleware' => ['auth:sanctum', 'check.deleted']], function () {
        Route::get('profile-progress', [ProfileProgressController::class, 'show'])->name('profile-progress.show');
        Route::get('profile-progress/step/{step}', [ProfileProgressController::class, 'showStep'])->name('profile-progress.step.show');
        Route::post('profile-progress/step-2', [ProfileProgressController::class, 'saveStep2'])->name('profile-progress.step2');
        Route::post('profile-progress/step-3', [ProfileProgressController::class, 'saveStep3'])->name('profile-progress.step3');
        Route::post('profile-progress/step-4', [ProfileProgressController::class, 'saveStep4'])->name('profile-progress.step4');
        Route::post('profile-progress/step-5', [ProfileProgressController::class, 'saveStep5'])->name('profile-progress.step5');
        Route::post('profile-progress/skip', [ProfileProgressController::class, 'skip'])->name('profile-progress.skip');

        Route::get('patients', [PatientController::class, 'getPatients'])->name('getPatients');

        Route::post('auth/logout', [AuthController::class, 'signout'])->name('logout');
        Route::get('get/profile', [AuthController::class, 'getProfile'])->name('getProfile');
        Route::post('save/profile', [AuthController::class, 'saveProfile'])->name('saveProfile');
        Route::post('auth/change-password', [AuthController::class, 'resetPassword'])->name('resetPassword');
        Route::post('auth/confirm-password', [AuthController::class, 'confirmPassword'])->name('confirmPassword');
        Route::post('remove-account', [AuthController::class, 'removeAccount'])->name('removeAccount');
        Route::post('user/delete', [AuthController::class, 'userRemove'])->name('userRemove');
        Route::post('user/add', [AuthController::class, 'userAddNew'])->name('userAddNew');
        Route::get('users', [AuthController::class, 'getUsers'])->name('getUsers');
        Route::post('user/edit-role', [AuthController::class, 'changeUserRole']);

        // Security
        Route::post('auth/security-save', [AuthController::class, 'saveSecurity'])->name('saveSecurity');

        // Appointment
        Route::get('get/appointments', [AppointmentController::class, 'getAppointments'])->name('getAppointments');
        Route::get('get/appointment/{id}', [AppointmentController::class, 'getAppointment'])->name('getAppointment');
        Route::post('add/appointment', [AppointmentController::class, 'addAppointment'])->name('addAppointment');
        Route::post('update/appointment/{id}', [AppointmentController::class, 'updateAppointment'])->name('updateAppointment');
        Route::post('delete/appointment/{id}', [AppointmentController::class, 'removeAppointment'])->name('removeAppointment');

        // Patient
        Route::get('get/patient-que', [PatientController::class, 'getPatientQue'])->name('getPatientQue');
        Route::post('delete/patient/{id}', [PatientController::class, 'removePatient'])->name('removePatient');

        // Sales Metrics
        Route::get('get/sales-metrics', [SalesMetricsController::class, 'getSalesMetrics'])->name('getSalesMetrics');

        Route::prefix('admin')->group(function () {
            Route::get('orders', [OrderAdminController::class, 'index'])->name('admin.orders.index');
            Route::get('orders/{order}', [OrderAdminController::class, 'show'])->name('admin.orders.show');
            Route::get('subscriptions', [SubscriptionAdminController::class, 'index'])->name('admin.subscriptions.index');
            Route::get('subscriptions/{subscription}', [SubscriptionAdminController::class, 'show'])->name('admin.subscriptions.show');
            Route::get('webhooks/{webhook}', [WebhookAdminController::class, 'show'])->name('admin.webhooks.show');

            Route::get('states', [DrNetworkAdminController::class, 'listStates'])->name('admin.dr-networks.states.index');
            Route::post('states', [DrNetworkAdminController::class, 'storeState'])->name('admin.dr-networks.states.store');
            Route::get('document-types', [DrNetworkAdminController::class, 'listDocumentTypes'])->name('admin.dr-networks.document-types.index');
            Route::post('document-types', [DrNetworkAdminController::class, 'storeDocumentType'])->name('admin.dr-networks.document-types.store');

            Route::get('dr-networks', [DrNetworkAdminController::class, 'indexNetworks'])->name('admin.dr-networks.index');
            Route::post('dr-networks', [DrNetworkAdminController::class, 'storeNetwork'])->name('admin.dr-networks.store');
            Route::get('dr-networks/state-mappings/coverage-check', [DrNetworkAdminController::class, 'coverageCheck'])->name('admin.dr-networks.state-mappings.coverage-check');
            Route::get('dr-networks/state-mappings', [DrNetworkAdminController::class, 'listStateMappings'])->name('admin.dr-networks.state-mappings.index');
            Route::post('dr-networks/state-mappings', [DrNetworkAdminController::class, 'storeStateMapping'])->name('admin.dr-networks.state-mappings.store');
            Route::patch('dr-networks/state-mappings/{mapping}', [DrNetworkAdminController::class, 'updateStateMapping'])->name('admin.dr-networks.state-mappings.update');
            Route::delete('dr-networks/state-mappings/{mapping}', [DrNetworkAdminController::class, 'destroyStateMapping'])->name('admin.dr-networks.state-mappings.destroy');
            Route::post('dr-networks/state-mappings/{mapping}/toggle', [DrNetworkAdminController::class, 'toggleStateMapping'])->name('admin.dr-networks.state-mappings.toggle');
            Route::patch('dr-networks/product-mappings/{mapping}', [DrNetworkAdminController::class, 'updateProductMapping'])->name('admin.dr-networks.product-mappings.update');
            Route::delete('dr-networks/product-mappings/{mapping}', [DrNetworkAdminController::class, 'destroyProductMapping'])->name('admin.dr-networks.product-mappings.destroy');
            Route::post('dr-networks/product-mappings/{mapping}/toggle', [DrNetworkAdminController::class, 'toggleProductMapping'])->name('admin.dr-networks.product-mappings.toggle');
            Route::patch('document-rules/{rule}', [DrNetworkAdminController::class, 'updateDocumentRule'])->name('admin.dr-networks.document-rules.update');
            Route::delete('document-rules/{rule}', [DrNetworkAdminController::class, 'destroyDocumentRule'])->name('admin.dr-networks.document-rules.destroy');
            Route::post('document-rules/{rule}/preview', [DrNetworkAdminController::class, 'previewDocumentRule'])->name('admin.dr-networks.document-rules.preview');
            Route::get('flows/{flow}', [DrNetworkAdminController::class, 'showFlow'])->name('admin.dr-networks.flows.show');
            Route::patch('flows/{flow}', [DrNetworkAdminController::class, 'updateFlow'])->name('admin.dr-networks.flows.update');
            Route::delete('flows/{flow}', [DrNetworkAdminController::class, 'destroyFlow'])->name('admin.dr-networks.flows.destroy');
            Route::post('flows/{flow}/validate', [DrNetworkAdminController::class, 'validateFlowDefinition'])->name('admin.dr-networks.flows.validate');
            Route::post('flows/{flow}/clone', [DrNetworkAdminController::class, 'cloneFlow'])->name('admin.dr-networks.flows.clone');
            Route::get('question-sets/{set}', [DrNetworkAdminController::class, 'showQuestionSet'])->name('admin.dr-networks.question-sets.show');
            Route::patch('question-sets/{set}', [DrNetworkAdminController::class, 'updateQuestionSet'])->name('admin.dr-networks.question-sets.update');
            Route::post('question-sets/{set}/validate', [DrNetworkAdminController::class, 'validateQuestionSet'])->name('admin.dr-networks.question-sets.validate');
            Route::post('question-sets/{set}/publish', [DrNetworkAdminController::class, 'publishQuestionSet'])->name('admin.dr-networks.question-sets.publish');
            Route::post('question-sets/{set}/archive', [DrNetworkAdminController::class, 'archiveQuestionSet'])->name('admin.dr-networks.question-sets.archive');
            Route::post('question-sets/{set}/clone', [DrNetworkAdminController::class, 'cloneQuestionSet'])->name('admin.dr-networks.question-sets.clone');
            Route::get('question-sets/{set}/questions', [DrNetworkAdminController::class, 'listQuestions'])->name('admin.dr-networks.question-sets.questions.index');
            Route::post('question-sets/{set}/questions', [DrNetworkAdminController::class, 'storeQuestion'])->name('admin.dr-networks.question-sets.questions.store');
            Route::post('question-sets/{set}/preview', [DrNetworkAdminController::class, 'previewQuestionSet'])->name('admin.dr-networks.question-sets.preview');
            Route::post('question-sets/{set}/reorder-bulk', [DrNetworkAdminController::class, 'reorderQuestionsBulk'])->name('admin.dr-networks.question-sets.reorder-bulk');
            Route::patch('questions/{question}', [DrNetworkAdminController::class, 'updateQuestion'])->name('admin.dr-networks.questions.update');
            Route::delete('questions/{question}', [DrNetworkAdminController::class, 'destroyQuestion'])->name('admin.dr-networks.questions.destroy');
            Route::post('questions/{question}/reorder', [DrNetworkAdminController::class, 'reorderQuestion'])->name('admin.dr-networks.questions.reorder');
            Route::post('questions/{question}/test-blocking-rule', [DrNetworkAdminController::class, 'testBlockingRule'])->name('admin.dr-networks.questions.test-blocking-rule');
            Route::get('dr-networks/{network}', [DrNetworkAdminController::class, 'showNetwork'])->name('admin.dr-networks.show');
            Route::patch('dr-networks/{network}', [DrNetworkAdminController::class, 'updateNetwork'])->name('admin.dr-networks.update');
            Route::post('dr-networks/{network}/toggle', [DrNetworkAdminController::class, 'toggleNetwork'])->name('admin.dr-networks.toggle');
            Route::delete('dr-networks/{network}', [DrNetworkAdminController::class, 'destroyNetwork'])->name('admin.dr-networks.destroy');
            Route::get('dr-networks/{network}/credentials', [DrNetworkAdminController::class, 'showCredentials'])->name('admin.dr-networks.credentials.show');
            Route::put('dr-networks/{network}/credentials', [DrNetworkAdminController::class, 'updateCredentials'])->name('admin.dr-networks.credentials.update');
            Route::post('dr-networks/{network}/credentials/test', [DrNetworkAdminController::class, 'testCredentials'])->name('admin.dr-networks.credentials.test');
            Route::get('dr-networks/{network}/flows', [DrNetworkAdminController::class, 'listFlows'])->name('admin.dr-networks.flows.index');
            Route::post('dr-networks/{network}/flows', [DrNetworkAdminController::class, 'storeFlow'])->name('admin.dr-networks.flows.store');
            Route::get('dr-networks/{network}/flows/{flow}/content-coverage', [DrNetworkAdminController::class, 'flowContentCoverage'])->name('admin.dr-networks.flows.content-coverage');
            Route::get('dr-networks/{network}/product-mappings/matrix', [DrNetworkAdminController::class, 'productMappingMatrix'])->name('admin.dr-networks.product-mappings.matrix');
            Route::get('dr-networks/{network}/product-mappings', [DrNetworkAdminController::class, 'listProductMappings'])->name('admin.dr-networks.product-mappings.index');
            Route::post('dr-networks/{network}/product-mappings', [DrNetworkAdminController::class, 'storeProductMapping'])->name('admin.dr-networks.product-mappings.store');
            Route::get('dr-networks/{network}/question-sets', [DrNetworkAdminController::class, 'listQuestionSets'])->name('admin.dr-networks.question-sets.index');
            Route::post('dr-networks/{network}/question-sets', [DrNetworkAdminController::class, 'storeQuestionSet'])->name('admin.dr-networks.question-sets.store');
            Route::get('dr-networks/{network}/document-rules', [DrNetworkAdminController::class, 'listDocumentRules'])->name('admin.dr-networks.document-rules.index');
            Route::post('dr-networks/{network}/document-rules', [DrNetworkAdminController::class, 'storeDocumentRule'])->name('admin.dr-networks.document-rules.store');
            Route::get('dr-networks/{network}/webhook-config', [DrNetworkAdminController::class, 'webhookConfig'])->name('admin.dr-networks.webhook-config.show');
            Route::patch('dr-networks/{network}/webhook-config', [DrNetworkAdminController::class, 'updateWebhookConfig'])->name('admin.dr-networks.webhook-config.update');
            Route::get('dr-networks/{network}/webhook-log', [DrNetworkAdminController::class, 'webhookLog'])->name('admin.dr-networks.webhook-log.index');
            Route::post('dr-networks/{network}/webhook-log/{event}/replay', [DrNetworkAdminController::class, 'replayWebhook'])->name('admin.dr-networks.webhook-log.replay');
            Route::get('dr-networks/{network}/cases', [DrNetworkAdminController::class, 'cases'])->name('admin.dr-networks.cases.index');
            Route::get('dr-networks/{network}/cases/{order}', [DrNetworkAdminController::class, 'showCase'])->name('admin.dr-networks.cases.show');
            Route::get('dr-networks/{network}/cases/{order}/documents/{document}/preview', [DrNetworkAdminController::class, 'previewCaseDocument'])->name('admin.dr-networks.cases.documents.preview');
            Route::get('dr-networks/{network}/cases/{order}/documents/{document}/download', [DrNetworkAdminController::class, 'downloadCaseDocument'])->name('admin.dr-networks.cases.documents.download');
            Route::get('dr-networks/{network}/flow-runs', [DrNetworkAdminController::class, 'flowRuns'])->name('admin.dr-networks.flow-runs.index');
            Route::get('dr-networks/{network}/flow-runs/{run}', [DrNetworkAdminController::class, 'showFlowRun'])->name('admin.dr-networks.flow-runs.show');
            Route::post('dr-networks/{network}/flow-runs/{run}/retry-poll', [DrNetworkAdminController::class, 'retryFlowRunPoll'])->name('admin.dr-networks.flow-runs.retry-poll');
            Route::get('dr-networks/{network}/finance/summary', [DrNetworkFinanceController::class, 'summary'])->name('admin.dr-networks.finance.summary');
            Route::get('dr-networks/{network}/finance/transactions', [DrNetworkFinanceController::class, 'transactions'])->name('admin.dr-networks.finance.transactions.index');
            Route::post('dr-networks/{network}/finance/transactions/{transaction}/void', [DrNetworkFinanceController::class, 'voidTransaction'])->name('admin.dr-networks.finance.transactions.void');
            Route::get('dr-networks/{network}/finance/payouts', [DrNetworkFinanceController::class, 'payouts'])->name('admin.dr-networks.finance.payouts.index');
            Route::post('dr-networks/{network}/finance/payouts', [DrNetworkFinanceController::class, 'storePayout'])->name('admin.dr-networks.finance.payouts.store');

            Route::post('products/step-1', [ProductStepController::class, 'step1'])->name('admin.products.step1');
            Route::post('products/step-2', [ProductStepController::class, 'step2'])->name('admin.products.step2');
            Route::post('products/step-3', [ProductStepController::class, 'step3'])->name('admin.products.step3');
            Route::post('products/step-4', [ProductStepController::class, 'step4'])->name('admin.products.step4');
            Route::post('products/step-5', [ProductStepController::class, 'step5'])->name('admin.products.step5');
            Route::get('products/image-config', [ProductStepController::class, 'productImageConfig'])->name('admin.products.image-config');
            Route::get('products', [ProductController::class, 'index'])->name('admin.products.index');
            Route::get('products/drafts', [ProductController::class, 'drafts'])->name('admin.products.drafts');
            Route::get('products/search-selection', [ProductController::class, 'searchSelection'])->name('admin.products.search-selection');
            Route::delete('products/{productId}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
            Route::get('products/{productId}/publish-status', [ProductController::class, 'publishStatus'])->name('admin.products.publish-status');
            Route::get('products/{productId}/preview', [ProductController::class, 'preview'])->name('admin.products.preview');
            Route::post('products/{productId}/toggle-featured', [ProductController::class, 'toggleFeatured'])->name('admin.products.toggle-featured');
            Route::post('products/{productId}/publish', [ProductController::class, 'publish'])->name('admin.products.publish');
            Route::post('products/{productId}/unpublish', [ProductController::class, 'unpublish'])->name('admin.products.unpublish');
            Route::get('products/{productId}/step-1', [ProductStepController::class, 'getStep1'])->name('admin.products.get-step1');
            Route::get('products/{productId}/step-2', [ProductStepController::class, 'getStep2'])->name('admin.products.get-step2');
            Route::get('products/{productId}/step-3', [ProductStepController::class, 'getStep3'])->name('admin.products.get-step3');
            Route::get('products/{productId}/step-4', [ProductStepController::class, 'getStep4'])->name('admin.products.get-step4');
            Route::get('products/{productId}/step-5', [ProductStepController::class, 'getStep5'])->name('admin.products.get-step5');
            Route::get('products/{productId}/step-status', [ProductStepController::class, 'status'])->name('admin.products.step-status');
            Route::get('coupons', [AdminCouponController::class, 'index'])->name('admin.coupons.index');
            Route::post('coupons', [AdminCouponController::class, 'store'])->name('admin.coupons.store');
            Route::get('coupons/{couponId}', [AdminCouponController::class, 'show'])->name('admin.coupons.show');
            Route::put('coupons/{couponId}', [AdminCouponController::class, 'update'])->name('admin.coupons.update');
            Route::delete('coupons/{couponId}', [AdminCouponController::class, 'destroy'])->name('admin.coupons.destroy');
            Route::post('coupons/{couponId}/toggle-active', [AdminCouponController::class, 'toggleActive'])->name('admin.coupons.toggle-active');
            Route::post('media/upload', [AdminMediaController::class, 'upload'])->name('admin.media.upload');
            Route::get('ingredients', [IngredientController::class, 'index'])->name('admin.ingredients.index');
            Route::post('ingredients', [IngredientController::class, 'store'])->name('admin.ingredients.store');

            Route::get('content/settings', [ContentAdminController::class, 'getSettings']);
            Route::post('content/settings', [ContentAdminController::class, 'saveSetting']);
            Route::delete('content/settings/{id}', [ContentAdminController::class, 'deleteSetting']);
            Route::get('content/layout', [ContentAdminController::class, 'getGlobalSections']);
            Route::post('content/layout', [ContentAdminController::class, 'saveGlobalSection']);

            Route::get('content/pages', [ContentAdminController::class, 'getPages']);
            Route::get('content/pages/{id}', [ContentAdminController::class, 'getPage']);
            Route::post('content/pages', [ContentAdminController::class, 'savePage']);
            Route::delete('content/pages/{id}', [ContentAdminController::class, 'deletePage']);
            Route::get('content/pages/{pageId}/sections', [ContentAdminController::class, 'getPageSections']);
            Route::post('content/pages/{pageId}/sections', [ContentAdminController::class, 'createPageSection']);

            Route::post('content/sections', [ContentAdminController::class, 'saveSection']);
            Route::delete('content/sections/{id}', [ContentAdminController::class, 'deleteSection']);
            Route::post('content/pages/{pageId}/sections/reorder', [ContentAdminController::class, 'reorderSections']);

            Route::post('content/section-items', [ContentAdminController::class, 'saveSectionItem']);
            Route::delete('content/section-items/{id}', [ContentAdminController::class, 'deleteSectionItem']);
            Route::post('content/sections/{sectionId}/items/reorder', [ContentAdminController::class, 'reorderSectionItems']);
            Route::post('content/document-imports', [DocumentConversionController::class, 'store'])->name('admin.content.document-imports.store');
        });

        // Staff #########################
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

        // Inventory #########################
        Route::post('add/inventory', [InventoryController::class, 'addInventory']);
        Route::post('update/inventory/{id}', [InventoryController::class, 'updateInventory']);
        Route::get('get/inventories', [InventoryController::class, 'getAllInventory']);
        Route::post('delete/inventory/{id}', [InventoryController::class, 'deleteInventory']);

        // Chief complaint #########################(From all these apis the intake 1 is replaced iwth the new intake form model )
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

        // Marketing-textCampaign
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

        // Patient Appointment
        Route::get('get/patient-appointments', [PatientAppointmentController::class, 'getAppointments'])->name('getAppointments');
        Route::get('all/patient-appointments', [PatientAppointmentController::class, 'getAllAppointments'])->name('getAllAppointments');
        Route::post('add/patient-appointment', [PatientAppointmentController::class, 'addAppointment'])->name('addAppointment');
        Route::post('update/patient-appointment/{id}', [PatientAppointmentController::class, 'updateAppointment'])->name('updateAppointment');
        Route::post('delete/patient-appointment/{id}', [PatientAppointmentController::class, 'removeAppointment'])->name('removeAppointment');

        // Settings
        Route::get('get/banking', [SettingsController::class, 'getBankingData'])->name('getBankingData');
        Route::post('save/banking', [SettingsController::class, 'saveBankingData'])->name('saveBankingData');
        Route::get('get/business-hours', [SettingsController::class, 'getBusinessHours'])->name('getBusinessHours');
        Route::post('save/business-hours', [SettingsController::class, 'saveBusinessHours'])->name('saveBusinessHours');

        // Reports
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
            Route::get('order-stats', [CmsAdminController::class, 'getOrderStats']);
            Route::get('categories', [CmsAdminController::class, 'getCategories']);
            Route::post('categories', [CmsAdminController::class, 'saveCategory']);
            Route::delete('categories/{id}', [CmsAdminController::class, 'deleteCategory']);

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

    Route::prefix('content')->group(function () {
        Route::get('settings', [ContentPublicController::class, 'getSettings']);
        Route::get('pages', [ContentPublicController::class, 'getPages']);
        Route::get('pages/{slug}', [ContentPublicController::class, 'getPageBySlug'])
            ->where('slug', '.+');
    });

    Route::get('layout', [LayoutController::class, 'show']);
    Route::get('products/{slug}/pricing', [CmsPublicController::class, 'getProductPricing']);
    Route::get('pages', [ContentPublicController::class, 'getPages']);
    Route::get('pages/{slug}', [ContentPublicController::class, 'getPageBySlug'])
        ->where('slug', '.+');

});
