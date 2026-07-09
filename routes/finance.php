<?php

use App\Http\Controllers\Finance\BalanceController as FinanceBalanceController;
use App\Http\Controllers\Finance\BillController as FinanceBillController;
use App\Http\Controllers\Finance\DonationController as FinanceDonationController;
use App\Http\Controllers\Finance\PaymentController as FinancePaymentController;
use App\Http\Controllers\Finance\PayrollController as FinancePayrollController;
use App\Http\Controllers\Finance\ReportController as FinanceReportController;
use App\Http\Controllers\Finance\SavingsController as FinanceSavingsController;
use App\Http\Controllers\Finance\TransactionController as FinanceTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('balance')->group(function () {
    Route::get('/', [FinanceBalanceController::class, 'index'])->name('finance.balance.index')->middleware('role:orang-tua');
    Route::get('history', [FinanceBalanceController::class, 'history'])->name('finance.balance.history')->middleware('role:orang-tua');
    Route::get('waiting/{transaction}', [FinanceBalanceController::class, 'waiting'])->name('finance.balance.waiting')->middleware('role:orang-tua');
    Route::get('/{history}', [FinanceBalanceController::class, 'show'])->name('finance.balance.show')->middleware('role:orang-tua');

    Route::post('/', [FinanceBalanceController::class, 'store'])->name('finance.balance.store')->middleware('role:orang-tua');
    Route::post('get', [FinanceBalanceController::class, 'get'])->name('finance.balance.get')->middleware('role:orang-tua');
    Route::post('get/history', [FinanceBalanceController::class, 'getHistory'])->name('finance.balance.get.history')->middleware('role:orang-tua');
});

Route::prefix('bill')->group(function () {
    Route::get('/', [FinanceBillController::class, 'index'])->name('finance.bill.index')->middleware('role:kasir');
    Route::post('datatable/student', [FinanceBillController::class, 'datatableStudent'])->name('finance.bill.datatable.student')->middleware('role:kasir');
    Route::post('datatable/list', [FinanceBillController::class, 'datatableList'])->name('finance.bill.datatable.list')->middleware('role:kasir');
    Route::post('get/option', [FinanceBillController::class, 'getOption'])->name('finance.bill.get.option')->middleware('role:kasir');
    Route::post('get/class', [FinanceBillController::class, 'getClass'])->name('finance.bill.get.class')->middleware('role:kasir');
    Route::post('get', [FinanceBillController::class, 'get'])->name('finance.bill.get')->middleware('role:kasir');
    Route::post('generate', [FinanceBillController::class, 'generate'])->name('finance.bill.generate')->middleware('role:kasir');
    Route::post('delete', [FinanceBillController::class, 'destroy'])->name('finance.bill.destroy')->middleware('role:kasir');

    Route::prefix('type')->group(function () {
        Route::get('/', [FinanceBillController::class, 'type'])->name('finance.bill.type.index')->middleware('role:kasir');
        Route::get('create', [FinanceBillController::class, 'createType'])->name('finance.bill.type.create')->middleware('role:kasir');
        Route::get('{type}/edit', [FinanceBillController::class, 'editType'])->name('finance.bill.type.edit')->middleware('role:kasir');
        Route::post('datatable', [FinanceBillController::class, 'datatableType'])->name('finance.bill.type.datatable')->middleware('role:kasir');
        Route::post('/', [FinanceBillController::class, 'storeType'])->name('finance.bill.type.store')->middleware('role:kasir');
        Route::put('/{type}', [FinanceBillController::class, 'updateType'])->name('finance.bill.type.update')->middleware('role:kasir');
        Route::delete('/{type}', [FinanceBillController::class, 'destroyType'])->name('finance.bill.type.destroy')->middleware('role:kasir');
    });

    Route::prefix('setup')->group(function () {
        Route::get('/', [FinanceBillController::class, 'setup'])->name('finance.bill.setup.index')->middleware('role:kasir');
        Route::get('setting', [FinanceBillController::class, 'setting'])->name('finance.bill.setup.setting')->middleware('role:kasir');
        Route::get('create', [FinanceBillController::class, 'createSetup'])->name('finance.bill.setup.create')->middleware('role:kasir');
        Route::get('{bill}/edit', [FinanceBillController::class, 'editSetup'])->name('finance.bill.setup.edit')->middleware('role:kasir');
        Route::post('datatable', [FinanceBillController::class, 'datatableSetup'])->name('finance.bill.setup.datatable')->middleware('role:kasir');
        Route::post('/', [FinanceBillController::class, 'storeSetup'])->name('finance.bill.setup.store')->middleware('role:kasir');
        Route::put('/{bill}', [FinanceBillController::class, 'updateSetup'])->name('finance.bill.setup.update')->middleware('role:kasir');
        Route::delete('/{bill}', [FinanceBillController::class, 'destroySetup'])->name('finance.bill.setup.destroy')->middleware('role:kasir');
    });

    Route::prefix('discount')->group(function () {
        Route::get('/', [FinanceBillController::class, 'discount'])->name('finance.bill.discount.index')->middleware('role:kasir');
        Route::get('create', [FinanceBillController::class, 'createDiscount'])->name('finance.bill.discount.create')->middleware('role:kasir');
        Route::get('{discount}/edit', [FinanceBillController::class, 'editDiscount'])->name('finance.bill.discount.edit')->middleware('role:kasir');
        Route::post('datatable', [FinanceBillController::class, 'datatableDiscount'])->name('finance.bill.discount.datatable')->middleware('role:kasir');
        Route::post('/', [FinanceBillController::class, 'storeDiscount'])->name('finance.bill.discount.store')->middleware('role:kasir');
        Route::put('/{discount}', [FinanceBillController::class, 'updateDiscount'])->name('finance.bill.discount.update')->middleware('role:kasir');
        Route::delete('/{discount}', [FinanceBillController::class, 'destroyDiscount'])->name('finance.bill.discount.destroy')->middleware('role:kasir');
    });
});

Route::post('donation/datatable', [FinanceDonationController::class, 'datatable'])->name('finance.donation.datatable')->middleware('role:kasir');
Route::resource('donation', FinanceDonationController::class, ['as' => 'finance'])->except(['show'])->middleware('role:kasir');

Route::prefix('payment')->group(function () {
    Route::get('/', [FinancePaymentController::class, 'index'])->name('finance.payment.index')->middleware('role:orang-tua');
    Route::get('waiting/{transaction}', [FinancePaymentController::class, 'waiting'])->name('finance.payment.waiting')->middleware('role:orang-tua');
    Route::get('history', [FinancePaymentController::class, 'history'])->name('finance.payment.history')->middleware('role:orang-tua');
    Route::get('/{transaction}', [FinancePaymentController::class, 'show'])->name('finance.payment.show')->middleware('role:orang-tua');
    Route::post('/', [FinancePaymentController::class, 'store'])->name('finance.payment.store')->middleware('role:orang-tua');
    Route::post('check/{transaction}', [FinancePaymentController::class, 'check'])->name('finance.payment.check')->middleware('role:orang-tua');
    Route::post('get/bills', [FinancePaymentController::class, 'getBills'])->name('finance.payment.get.bills')->middleware('role:orang-tua');
    Route::post('get/history', [FinancePaymentController::class, 'getHistory'])->name('finance.payment.get.history')->middleware('role:orang-tua');
    Route::delete('/{transaction}', [FinancePaymentController::class, 'destroy'])->name('finance.payment.destroy')->middleware('role:orang-tua');
});

Route::prefix('payroll')->group(function () {
    Route::get('/', [FinancePayrollController::class, 'index'])->name('finance.payroll.index')->middleware('role:pegawai');
    Route::get('setup', [FinancePayrollController::class, 'setup'])->name('finance.payroll.setup')->middleware('role:bendahara');
    Route::get('slip', [FinancePayrollController::class, 'slip'])->name('finance.payroll.slip')->middleware('role:bendahara');
    Route::get('slip/{payroll}', [FinancePayrollController::class, 'showSlip'])->name('finance.payroll.show.slip')->middleware('role:bendahara,pegawai');
    Route::get('edit/setup/{employee}', [FinancePayrollController::class, 'editSetup'])->name('finance.payroll.edit.setup')->middleware('role:bendahara');
    Route::get('download/slip/{payroll}', [FinancePayrollController::class, 'downloadSlip'])->name('finance.payroll.download.slip')->middleware('role:bendahara,pegawai');

    Route::post('datatable/setup', [FinancePayrollController::class, 'datatableSetup'])->name('finance.payroll.datatable.setup')->middleware('role:bendahara');
    Route::post('datatable/slip', [FinancePayrollController::class, 'datatableSlip'])->name('finance.payroll.datatable.slip')->middleware('role:bendahara');

    Route::put('setup/{employee}', [FinancePayrollController::class, 'updateSetup'])->name('finance.payroll.update.setup')->middleware('role:bendahara');
});

Route::prefix('report')->group(function () {
    Route::get('bill-not-paid', [FinanceReportController::class, 'billNotPaid'])->name('finance.report.bill-not-paid');
    Route::get('bill-student', [FinanceReportController::class, 'billStudent'])->name('finance.report.bill-student')->middleware('role:kasir,bendahara');
    Route::get('bill-progress', [FinanceReportController::class, 'billProgress'])->name('finance.report.bill-progress')->middleware('role:kasir');
    Route::get('bill-total', [FinanceReportController::class, 'billTotal'])->name('finance.report.bill-total')->middleware('role:kasir,bendahara');
    Route::get('outstanding-arrears', [FinanceReportController::class, 'outstandingArrears'])->name('finance.report.outstanding-arrears')->middleware('role:kasir');
    Route::get('payment-method', [FinanceReportController::class, 'paymentMethod'])->name('finance.report.payment-method')->middleware('role:kasir');
    Route::get('donation', [FinanceReportController::class, 'donation'])->name('finance.report.donation');
    Route::get('ongoing-collection-spp', [FinanceReportController::class, 'ongoingCollectionSpp'])->name('finance.report.ongoing-collection-spp')->middleware('role:kasir');

    Route::get('download/excel/bill-student', [FinanceReportController::class, 'downloadExcelBillStudent'])->name('finance.report.download.excel.bill-student')->middleware('role:kasir,bendahara');
    Route::get('download/excel/bill-not-paid', [FinanceReportController::class, 'downloadExcelBillNotPaid'])->name('finance.report.download.excel.bill-not-paid')->middleware('role:kasir');
    Route::get('download/excel/bill-progress', [FinanceReportController::class, 'downloadExcelBillProgress'])->name('finance.report.download.excel.bill-progress')->middleware('role:kasir');
    Route::get('download/excel/bill-total', [FinanceReportController::class, 'downloadExcelBillTotal'])->name('finance.report.download.excel.bill-total')->middleware('role:kasir,bendahara');
    Route::get('download/excel/outstanding-arrears', [FinanceReportController::class, 'downloadExcelOutstandingArrears'])->name('finance.report.download.excel.outstanding-arrears')->middleware('role:kasir');
    Route::get('download/excel/payment-method', [FinanceReportController::class, 'downloadExcelPaymentMethod'])->name('finance.report.download.excel.payment-method')->middleware('role:kasir');
    Route::get('download/excel/donation', [FinanceReportController::class, 'downloadExcelDonation'])->name('finance.report.download.excel.donation')->middleware('role:kasir');
    Route::get('download.excel/ongoing-collection-spp', [FinanceReportController::class, 'downloadExcelOngoingCollectionSpp'])->name('finance.report.download.excel.ongoing-collection-spp')->middleware('role:kasir');

    Route::get('download/pdf/bill-student', [FinanceReportController::class, 'downloadPdfBillStudent'])->name('finance.report.download.pdf.bill-student')->middleware('role:kasir,bendahara');
    Route::get('download/pdf/bill-not-paid', [FinanceReportController::class, 'downloadPdfBillNotPaid'])->name('finance.report.download.pdf.bill-not-paid')->middleware('role:kasir');
    Route::get('download/pdf/bill-progress', [FinanceReportController::class, 'downloadPdfBillProgress'])->name('finance.report.download.pdf.bill-progress')->middleware('role:kasir');
    Route::get('download/pdf/bill-total', [FinanceReportController::class, 'downloadPdfBillTotal'])->name('finance.report.download.pdf.bill-total')->middleware('role:kasir,bendahara');
    Route::get('download/pdf/outstanding-arrears', [FinanceReportController::class, 'downloadPdfOutstandingArrears'])->name('finance.report.download.pdf.outstanding-arrears')->middleware('role:kasir');
    Route::get('download/pdf/payment-method', [FinanceReportController::class, 'downloadPdfPaymentMethod'])->name('finance.report.download.pdf.payment-method')->middleware('role:kasir');
    Route::get('download/pdf/donation', [FinanceReportController::class, 'downloadPdfDonation'])->name('finance.report.download.pdf.donation')->middleware('role:kasir');
    Route::get('download/pdf/ongoing-collection-spp', [FinanceReportController::class, 'downloadPdfOngoingCollectionSpp'])->name('finance.report.download.pdf.ongoing-collection-spp')->middleware('role:kasir');

    Route::post('datatable/bill-student', [FinanceReportController::class, 'datatableBillStudent'])->name('finance.report.datatable.bill-student')->middleware('role:kasir');
    Route::post('datatable/bill-not-paid', [FinanceReportController::class, 'datatableBillNotPaid'])->name('finance.report.datatable.bill-not-paid')->middleware('role:kasir');
    Route::post('datatable/bill-total', [FinanceReportController::class, 'datatableBillTotal'])->name('finance.report.datatable.bill-total')->middleware('role:kasir');
    Route::post('datatable/donation', [FinanceReportController::class, 'datatableDonation'])->name('finance.report.datatable.donation')->middleware('role:kasir');

    Route::post('get/total-bill', [FinanceReportController::class, 'getTotalBill'])->name('finance.report.get.total-bill')->middleware('role:kasir,bendahara');
    Route::post('get/total-bill-not-paid', [FinanceReportController::class, 'getTotalBillNotPaid'])->name('finance.report.get.total-bill-not-paid')->middleware('role:kasir');
    Route::post('get/outstanding-arrears', [FinanceReportController::class, 'getOutstandingArrears'])->name('finance.report.get.outstanding-arrears')->middleware('role:kasir');
    Route::post('get/ongoing-collection-spp', [FinanceReportController::class, 'getOngoingCollectionSpp'])->name('finance.report.get.ongoing-collection-spp')->middleware('role:kasir');
    Route::post('get/option/bill', [FinanceReportController::class, 'getOptionBill'])->name('finance.report.get.option.bill')->middleware('role:kasir');
});

Route::prefix('savings')->group(function () {
    Route::get('/', [FinanceSavingsController::class, 'index'])->name('finance.savings.index')->middleware('role:orang-tua');
    Route::get('history', [FinanceSavingsController::class, 'history'])->name('finance.savings.history')->middleware('role:orang-tua');
    Route::get('history/withdrawal', [FinanceSavingsController::class, 'historyWithdrawal'])->name('finance.savings.history.withdrawal')->middleware('role:penanggung-jawab-tabungan');
    Route::get('deposit', [FinanceSavingsController::class, 'deposit'])->name('finance.savings.deposit')->middleware('role:kasir');
    Route::get('list', [FinanceSavingsController::class, 'list'])->name('finance.savings.list')->middleware('role:kasir');
    Route::get('waiting/{transaction}', [FinanceSavingsController::class, 'waiting'])->name('finance.savings.waiting')->middleware('role:orang-tua');
    Route::get('withdrawal', [FinanceSavingsController::class, 'withdrawal'])->name('finance.savings.withdrawal')->middleware('role:kasir');
    Route::get('mutation', [FinanceSavingsController::class, 'mutation'])->name('finance.savings.mutation')->middleware('role:kasir,penanggung-jawab-tabungan');
    Route::get('create/withdrawal', [FinanceSavingsController::class, 'createWithdrawal'])->name('finance.savings.create.withdrawal')->middleware('role:penanggung-jawab-tabungan');
    Route::get('edit/withdrawal/{withdrawal}', [FinanceSavingsController::class, 'editWithdrawal'])->name('finance.savings.edit.withdrawal')->middleware('role:penanggung-jawab-tabungan');
    Route::get('download/excel/withdrawal/{transaction}', [FinanceSavingsController::class, 'downloadExcelWithdrawal'])->name('finance.savings.download.excel.withdrawal')->middleware('role:kasir');
    Route::get('{transaction}', [FinanceSavingsController::class, 'show'])->name('finance.savings.show')->middleware('role:orang-tua');
    Route::get('withdrawal/{withdrawal}', [FinanceSavingsController::class, 'showWithdrawal'])->name('finance.savings.show-withdrawal')->middleware('role:orang-tua');

    Route::post('datatable/history', [FinanceSavingsController::class, 'datatableHistory'])->name('finance.savings.datatable.history')->middleware('role:kasir');
    Route::post('datatable/history-withdrawal', [FinanceSavingsController::class, 'datatableHistoryWithdrawal'])->name('finance.savings.datatable.history-withdrawal')->middleware('role:penanggung-jawab-tabungan');
    Route::post('datatable/mutation', [FinanceSavingsController::class, 'datatableMutation'])->name('finance.savings.datatable.mutation')->middleware('role:kasir,penanggung-jawab-tabungan');
    Route::post('get', [FinanceSavingsController::class, 'get'])->name('finance.savings.get')->middleware('role:orang-tua');
    Route::post('get/student', [FinanceSavingsController::class, 'getStudent'])->name('finance.savings.get.student')->middleware('role:kasir,penanggung-jawab-tabungan');
    Route::post('get/history', [FinanceSavingsController::class, 'getHistory'])->name('finance.savings.get.history')->middleware('role:orang-tua');
    Route::post('get/withdrawal', [FinanceSavingsController::class, 'getWithdrawal'])->name('finance.savings.get.withdrawal')->middleware('role:kasir');
    Route::post('/', [FinanceSavingsController::class, 'store'])->name('finance.savings.store')->middleware('role:kasir,orang-tua');
    Route::post('withdrawal', [FinanceSavingsController::class, 'storeWithdrawal'])->name('finance.savings.store.withdrawal')->middleware('role:penanggung-jawab-tabungan');
    Route::post('process/withdrawal', [FinanceSavingsController::class, 'processWithdrawal'])->name('finance.savings.process.withdrawal')->middleware('role:kasir');

    Route::put('withdrawal/{withdrawal}', [FinanceSavingsController::class, 'updateWithdrawal'])->name('finance.savings.update.withdrawal')->middleware('role:penanggung-jawab-tabungan');

    Route::delete('withdrawal/{withdrawal}', [FinanceSavingsController::class, 'destroyWithdrawal'])->name('finance.savings.destroy.withdrawal')->middleware('role:penanggung-jawab-tabungan');
});

Route::prefix('transaction')->group(function () {
    Route::get('bill', [FinanceTransactionController::class, 'bill'])->name('finance.transaction.bill.index')->middleware('role:kasir');
    Route::get('bill/{transaction}', [FinanceTransactionController::class, 'showBill'])->name('finance.transaction.bill.show')->middleware('role:kasir');
    Route::get('cash/{render}', [FinanceTransactionController::class, 'cash'])->where('render', '[waiting|accepted|rejected]+')->name('finance.transaction.cash')->middleware('role:kasir,bendahara');
    Route::get('unique-code/{render}', [FinanceTransactionController::class, 'uniqueCode'])->where('render', '[waiting|accepted|rejected]+')->name('finance.transaction.unique-code')->middleware('role:kasir,bendahara');
    Route::get('pending', [FinanceTransactionController::class, 'pending'])->name('finance.transaction.pending')->middleware('role:kasir');
    Route::get('history', [FinanceTransactionController::class, 'history'])->name('finance.transaction.history')->middleware('role:kasir');
    Route::get('create/cash', [FinanceTransactionController::class, 'createCash'])->name('finance.transaction.create.cash')->middleware('role:kasir');
    Route::get('create/unique-code', [FinanceTransactionController::class, 'createUniqueCode'])->name('finance.transaction.create.unique-code')->middleware('role:kasir');
    Route::get('edit/cash/{deposit}', [FinanceTransactionController::class, 'editCash'])->name('finance.transaction.edit.cash')->middleware('role:kasir');
    Route::get('edit/unique-code/{deposit}', [FinanceTransactionController::class, 'editUniqueCode'])->name('finance.transaction.edit.unique-code')->middleware('role:kasir');
    Route::get('verify/cash/{deposit}', [FinanceTransactionController::class, 'verifyCash'])->name('finance.transaction.verify.cash')->middleware('role:bendahara');
    Route::get('verify/unique-code/{deposit}', [FinanceTransactionController::class, 'verifyUniqueCode'])->name('finance.transaction.verify.unique-code')->middleware('role:bendahara');
    Route::get('print/{transaction}', [FinanceTransactionController::class, 'print'])->name('finance.transaction.print')->middleware('role:kasir');
    Route::get('print/cash/{deposit}', [FinanceTransactionController::class, 'printCash'])->name('finance.transaction.print.cash')->middleware('role:kasir');

    Route::post('/', [FinanceTransactionController::class, 'store'])->name('finance.transaction.store')->middleware('role:kasir');
    Route::post('cash', [FinanceTransactionController::class, 'storeCash'])->name('finance.transaction.store.cash')->middleware('role:kasir');
    Route::post('unique-code', [FinanceTransactionController::class, 'storeUniqueCode'])->name('finance.transaction.store.unique-code')->middleware('role:kasir');
    Route::post('verify/cash/{deposit}', [FinanceTransactionController::class, 'storeVerifyCash'])->name('finance.transaction.store.verify-cash')->middleware('role:bendahara');
    Route::post('verify/unique-code/{deposit}', [FinanceTransactionController::class, 'storeVerifyUniqueCode'])->name('finance.transaction.store.verify-unique-code')->middleware('role:bendahara');
    Route::post('get/bill', [FinanceTransactionController::class, 'getBill'])->name('finance.transaction.get.bill')->middleware('role:kasir');
    Route::post('datatable/donatur', [FinanceTransactionController::class, 'datatableDonatur'])->name('finance.transaction.datatable.donatur')->middleware('role:kasir');
    Route::post('datatable/pending', [FinanceTransactionController::class, 'datatablePending'])->name('finance.transaction.datatable.pending')->middleware('role:kasir');
    Route::post('datatable/history', [FinanceTransactionController::class, 'datatableHistory'])->name('finance.transaction.datatable.history')->middleware('role:kasir');
    Route::post('datatable/cash', [FinanceTransactionController::class, 'datatableCash'])->name('finance.transaction.datatable.cash')->middleware('role:kasir,bendahara');
    Route::post('datatable/unique-code', [FinanceTransactionController::class, 'datatableUniqueCode'])->name('finance.transaction.datatable.unique-code')->middleware('role:kasir,bendahara');
    Route::post('datatable/paid', [FinanceTransactionController::class, 'datatablePaid'])->name('finance.transaction.datatable.paid')->middleware('role:kasir');
    Route::post('datatable/paid-unique-code', [FinanceTransactionController::class, 'datatablePaidUniqueCode'])->name('finance.transaction.datatable.paid-unique-code')->middleware('role:kasir');
    Route::post('update/status', [FinanceTransactionController::class, 'updateStatus'])->name('finance.transaction.update.status')->middleware('role:kasir');
    Route::post('update/cash/{deposit}', [FinanceTransactionController::class, 'updateCash'])->name('finance.transaction.update.cash')->middleware('role:kasir');
    Route::post('update/unique-code/{deposit}', [FinanceTransactionController::class, 'updateUniqueCode'])->name('finance.transaction.update.unique-code')->middleware('role:kasir');

    Route::delete('cash/{deposit}', [FinanceTransactionController::class, 'destroyCash'])->name('finance.transaction.destroy.cash')->middleware('role:kasir');
    Route::delete('unique-code/{deposit}', [FinanceTransactionController::class, 'destroyUniqueCode'])->name('finance.transaction.destroy.unique-code')->middleware('role:kasir');
});
