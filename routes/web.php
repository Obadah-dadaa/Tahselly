<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Developer;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Owner;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\TrialController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->middleware('track')->name('landing');
Route::post('/locale/{locale}', [PreferenceController::class, 'locale'])->whereIn('locale', ['ar', 'en'])->name('locale');
Route::post('/theme/{theme}', [PreferenceController::class, 'theme'])->whereIn('theme', ['light', 'dark'])->name('theme');

// Checkout: guests create an account while subscribing; owners renew or change plan
Route::get('/subscribe/{plan:code}', [SubscribeController::class, 'show'])->middleware('track')->name('subscribe.show');
Route::post('/subscribe/{plan:code}', [SubscribeController::class, 'store'])->middleware('throttle:10,1')->name('subscribe.store');
Route::get('/checkout/{subscription}/pay', [SubscribeController::class, 'sandbox'])->name('subscribe.sandbox');
Route::post('/checkout/{subscription}/pay', [SubscribeController::class, 'sandboxPay'])->middleware('throttle:10,1')->name('subscribe.sandbox.pay');
Route::get('/checkout/{subscription}/return', [SubscribeController::class, 'stripeReturn'])->name('subscribe.return');
Route::get('/checkout/{subscription}/pending', [SubscribeController::class, 'pending'])->name('subscribe.pending');
Route::get('/checkout/{subscription}/status', [SubscribeController::class, 'status'])->middleware('throttle:30,1')->name('subscribe.status');
Route::get('/checkout/{subscription}/continue', [SubscribeController::class, 'continue'])->name('subscribe.continue');
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

// Free trial (no card) and legal pages
Route::middleware('guest')->group(function () {
    Route::get('/start', [TrialController::class, 'create'])->middleware('track')->name('trial.create');
    Route::post('/start', [TrialController::class, 'store'])->middleware('throttle:5,1')->name('trial.store');
});
Route::view('/terms', 'legal.terms')->middleware('track')->name('legal.terms');
Route::view('/privacy', 'legal.privacy')->middleware('track')->name('legal.privacy');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:3,1')->name('verification.send');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/home', [AuthController::class, 'home'])->name('home');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read', [NotificationController::class, 'readAll'])->name('notifications.read');
});

// Operational area: managers and owners of a company
Route::middleware(['auth', 'verified', 'role:manager,owner'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/remind', [InvoiceController::class, 'remind'])->name('invoices.remind');
    Route::get('invoices/{invoice}/whatsapp', [InvoiceController::class, 'whatsapp'])->middleware('feature:whatsapp')->name('invoices.whatsapp');
    Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    Route::get('import', [ImportController::class, 'index'])->name('import.index');
    Route::post('import', [ImportController::class, 'store'])->name('import.store');
    Route::get('import/template', [ImportController::class, 'template'])->name('import.template');
    Route::get('import/{batch}', [ImportController::class, 'show'])->name('import.show');

    Route::get('reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::get('reminders/create', [ReminderController::class, 'create'])->name('reminders.create');
    Route::post('reminders', [ReminderController::class, 'store'])->name('reminders.store');
    Route::post('reminders/{reminder}/cancel', [ReminderController::class, 'cancel'])->name('reminders.cancel');
    Route::post('invoices/{invoice}/pause-reminders', [ReminderController::class, 'togglePause'])->name('invoices.pause');
    Route::post('reminders/run', [ReminderController::class, 'run'])->name('reminders.run');

    Route::get('reports/aging', [ReportController::class, 'aging'])->name('reports.aging');
    Route::get('reports/aging/export', [ReportController::class, 'exportAging'])->middleware('feature:report_exports')->name('reports.aging.export');
});

// Owner area: business overview, team, company settings
Route::middleware(['auth', 'verified', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/', Owner\DashboardController::class)->middleware('feature:owner_analytics')->name('dashboard');
    Route::get('billing', Owner\BillingController::class)->name('billing');
    Route::get('plan', [TrialController::class, 'choosePlan'])->name('plan');
    Route::post('plan', [TrialController::class, 'storePlan'])->name('plan.choose');
    Route::get('team', [Owner\TeamController::class, 'index'])->name('team.index');
    Route::post('team', [Owner\TeamController::class, 'store'])->name('team.store');
    Route::patch('team/{user}', [Owner\TeamController::class, 'update'])->middleware('feature:team_permissions')->name('team.update');
    Route::get('settings', [Owner\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings/company', [Owner\SettingsController::class, 'updateCompany'])->name('settings.company');
    Route::put('settings/reminders', [Owner\SettingsController::class, 'updateReminders'])->name('settings.reminders');
    Route::put('settings/templates', [Owner\SettingsController::class, 'updateTemplates'])->middleware('feature:custom_templates')->name('settings.templates');
    Route::get('activity', [Owner\DashboardController::class, 'activity'])->middleware('feature:activity_log')->name('activity');
});

// Platform area: developer and platform owner see every subscriber
Route::middleware(['staff:developer,platform'])->prefix('developer')->name('developer.')->group(function () {
    Route::get('/', Developer\DashboardController::class)->name('dashboard');
    Route::get('subscriptions', [Developer\SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('subscriptions', [Developer\SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::post('subscriptions/{subscription}/approve', [Developer\SubscriptionController::class, 'approve'])->name('subscriptions.approve');
    Route::patch('subscriptions/{subscription}', [Developer\SubscriptionController::class, 'update'])->name('subscriptions.update');
    Route::get('companies', [Developer\CompanyController::class, 'index'])->name('companies.index');
    Route::post('companies', [Developer\CompanyController::class, 'store'])->name('companies.store');
    Route::get('companies/{company}', [Developer\CompanyController::class, 'show'])->name('companies.show');
    Route::patch('companies/{company}', [Developer\CompanyController::class, 'update'])->name('companies.update');
    Route::get('plans', [Developer\SettingsController::class, 'plans'])->name('plans');
    Route::put('plans', [Developer\SettingsController::class, 'updatePlans'])->name('plans.update');
    Route::get('activity', [Developer\ToolsController::class, 'activity'])->name('activity');
});

// Developer-only: system internals
Route::middleware(['staff:developer'])->prefix('developer')->name('developer.')->group(function () {
    Route::get('settings', [Developer\SettingsController::class, 'edit'])->name('settings');
    Route::put('settings', [Developer\SettingsController::class, 'update'])->name('settings.update');
    Route::get('templates', [Developer\SettingsController::class, 'templates'])->name('templates');
    Route::put('templates', [Developer\SettingsController::class, 'updateTemplates'])->name('templates.update');
    Route::get('tools', [Developer\ToolsController::class, 'index'])->name('tools');
    Route::post('tools/{action}', [Developer\ToolsController::class, 'run'])->name('tools.run');
    Route::get('logs', [Developer\ToolsController::class, 'logs'])->name('logs');
    Route::patch('users/{user}/toggle', [Developer\AccountController::class, 'toggleUser'])->name('users.toggle');
    Route::delete('users/{user}', [Developer\AccountController::class, 'destroyUser'])->name('users.destroy');
    Route::patch('companies/{company}/toggle', [Developer\AccountController::class, 'toggleCompany'])->name('companies.toggle');
    Route::delete('companies/{company}', [Developer\AccountController::class, 'destroyCompany'])->name('companies.destroy');
});
