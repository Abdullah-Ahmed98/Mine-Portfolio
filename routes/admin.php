<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EducationEntryController;
use App\Http\Controllers\Admin\ExperienceController;
use App\Http\Controllers\Admin\LatestWorkCategoryController;
use App\Http\Controllers\Admin\LatestWorkImageController;
use App\Http\Controllers\Admin\LatestWorkItemController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProfileHighlightController;
use App\Http\Controllers\Admin\ProjectCategoryController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectImageController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SkillCategoryController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\SocialLinkController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [AuthController::class, 'create'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->middleware('throttle:5,1');
    });

    Route::post('logout', [AuthController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware('auth')->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Reordering is shared by every sortable list.
        Route::post('reorder/{type}/{id}', [DashboardController::class, 'reorder'])
            ->whereIn('type', [
                'profile-highlights', 'social-links', 'skill-categories', 'skills',
                'experiences', 'project-categories', 'projects', 'project-images',
                'education', 'latest-work', 'latest-work-categories', 'latest-work-images',
            ])
            ->name('reorder');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::resource('social-links', SocialLinkController::class)->except(['show']);
        Route::resource('highlights', ProfileHighlightController::class)->except(['show']);
        Route::resource('skill-categories', SkillCategoryController::class)->except(['show']);
        Route::resource('skills', SkillController::class)->except(['show']);
        Route::resource('experiences', ExperienceController::class)->except(['show']);
        Route::resource('project-categories', ProjectCategoryController::class)->except(['show']);
        Route::resource('projects', ProjectController::class)->except(['show']);
        Route::resource('education', EducationEntryController::class)->except(['show']);

        Route::resource('latest-work', LatestWorkItemController::class)
            ->except(['show'])
            // The singular of "latest-work" is "latest_work", which would not
            // match the controller's typed parameter, and the model binding
            // would be skipped rather than failing loudly.
            ->parameters(['latest-work' => 'latestWorkItem']);
        Route::resource('latest-work-categories', LatestWorkCategoryController::class)->except(['show']);

        Route::post('latest-work/{latestWorkItem}/images', [LatestWorkImageController::class, 'store'])
            ->name('latest-work.images.store');
        Route::delete('latest-work/images/{image}', [LatestWorkImageController::class, 'destroy'])
            ->name('latest-work.images.destroy');
        Route::put('latest-work/images/{image}', [LatestWorkImageController::class, 'update'])
            ->name('latest-work.images.update');

        Route::post('projects/{project}/images', [ProjectImageController::class, 'store'])
            ->name('projects.images.store');
        Route::delete('projects/images/{image}', [ProjectImageController::class, 'destroy'])
            ->name('projects.images.destroy');
        Route::put('projects/images/{image}', [ProjectImageController::class, 'update'])
            ->name('projects.images.update');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('messages', [ContactMessageController::class, 'index'])->name('messages.index');
        Route::get('messages/{message}', [ContactMessageController::class, 'show'])->name('messages.show');
        Route::patch('messages/{message}/read', [ContactMessageController::class, 'update'])->name('messages.read');
        Route::delete('messages/{message}', [ContactMessageController::class, 'destroy'])->name('messages.destroy');
    });
});
