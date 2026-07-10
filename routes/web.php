<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\LockController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\WhatsAppSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/lock', [LockController::class, 'show'])->name('lock.show');
Route::post('/unlock', [LockController::class, 'unlock'])->name('lock.unlock');
Route::post('/lock/relock', [LockController::class, 'relock'])->name('lock.relock');

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

Route::get('/reports', ReportController::class)->name('reports.index');
Route::get('/statistics', StatisticsController::class)->name('statistics.index');

Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
Route::post('/goals', [GoalController::class, 'store'])->name('goals.store');
Route::post('/goals/{goal}/contribute', [GoalController::class, 'contribute'])->name('goals.contribute');
Route::post('/goals/{goal}/withdraw', [GoalController::class, 'withdraw'])->name('goals.withdraw');
Route::delete('/goals/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');

Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
Route::post('/debts', [DebtController::class, 'store'])->name('debts.store');
Route::post('/debts/{debt}/pay', [DebtController::class, 'pay'])->name('debts.pay');
Route::delete('/debts/{debt}', [DebtController::class, 'destroy'])->name('debts.destroy');

Route::post('/recurring', [RecurringTransactionController::class, 'store'])->name('recurring.store');
Route::delete('/recurring/{recurringTransaction}', [RecurringTransactionController::class, 'destroy'])->name('recurring.destroy');

Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings/currency', [SettingsController::class, 'updateCurrency'])->name('settings.currency');
Route::put('/settings/lock', [SettingsController::class, 'updateLock'])->name('settings.lock');
Route::put('/settings/pin', [SettingsController::class, 'updatePin'])->name('settings.pin');
Route::put('/settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme');
Route::get('/settings/backup', [BackupController::class, 'download'])->name('settings.backup');
Route::post('/settings/backup/share', [BackupController::class, 'share'])->name('settings.backup.share');
Route::post('/settings/whatsapp/link', [WhatsAppSettingsController::class, 'link'])->name('settings.whatsapp.link');
Route::post('/settings/whatsapp/refresh', [WhatsAppSettingsController::class, 'refresh'])->name('settings.whatsapp.refresh');
Route::put('/settings/whatsapp/account', [WhatsAppSettingsController::class, 'updateAccount'])->name('settings.whatsapp.account');
Route::delete('/settings/whatsapp/link', [WhatsAppSettingsController::class, 'unlink'])->name('settings.whatsapp.unlink');

Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');

Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
