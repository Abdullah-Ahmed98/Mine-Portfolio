<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
 * The public site is a single page: every section is an anchor on "/", so the
 * only page route is the home page. The download and machine-readable endpoints
 * below are not pages and stay separate.
 */
Route::get('/', HomeController::class)->name('home');

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::get('/resume', ResumeController::class)->name('resume');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

require __DIR__.'/admin.php';
