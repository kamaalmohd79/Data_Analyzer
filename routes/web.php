<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalysisController;



Route::get('/', [AnalysisController::class, 'form'])->name('form');
Route::post('/analyze', [AnalysisController::class, 'analyze'])->name('analyze');

Route::post('/export-pdf', [App\Http\Controllers\AnalysisController::class, 'exportPdf'])
    ->name('export.pdf');



