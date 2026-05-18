<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MonthlySalesReportController;


Route::post('/user/register', [UserController::class, 'register']);
Route::post('/user/login', [UserController::class, 'login'])->middleware('throttle:againstBruteForce');

Route::prefix('user')->middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [UserController::class, 'logout']);

    Route::get('/showCategories', [CategoryController::class, 'showCategories']);
    Route::get('/showCategoryProducts/{id}', [CategoryController::class, 'showCategoryProducts']);

    Route::post('/createCart', [CartController::class, 'createCart']);
    Route::post('/deleteCart/{id}', [CartController::class, 'deleteCart']);
    Route::get('/showUserCarts', [CartController::class, 'showUserCarts']);

    Route::post('/createOrder1/{id}', [OrderController::class, 'createOrder1']);//قبل تنفيذ أفكار الطلب الأول والثالث
    Route::post('/createOrder2/{id}', [OrderController::class, 'createOrder2']);//بعد تنفيذأفكار الطلب الأول والثالث

    Route::get('/showUserOrders', [OrderController::class, 'showUserOrders']);

    Route::get('/showUserNotificationsNumber', [NotificationController::class, 'showUserNotificationsNumber']);
    Route::get('/showUserNotifications', [NotificationController::class, 'showUserNotifications']);
});



Route::post('admin/register', [AdminController::class, 'register']);
Route::post('admin/login', [AdminController::class, 'login']);

Route::prefix('admin')->middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AdminController::class, 'logout']);

    Route::post('/createCategory', [CategoryController::class, 'createCategory']);
    Route::post('/updateCategory/{id}', [CategoryController::class, 'updateCategory']);
    Route::post('/deleteCategory/{id}', [CategoryController::class, 'deleteCategory']);
    Route::get('/showCategories', [CategoryController::class, 'showCategories']);
    Route::get('/showCategoryProducts/{id}', [CategoryController::class, 'showCategoryProducts']);

    Route::post('/createProduct/{id}', [ProductController::class, 'createProduct']);
    Route::post('/updateProduct/{id}', [ProductController::class, 'updateProduct']);
    Route::post('/deleteProduct/{id}', [ProductController::class, 'deleteProduct']);

    Route::post('/sendNotificationToAll', [NotificationController::class, 'sendNotificationToAll']);

    Route::post('/runMonthlyInventory1', [MonthlySalesReportController::class, 'runMonthlyInventory1']);//(الجردالشهري)قبل تنفيذ افكار
    Route::post('/runMonthlyInventory2', [MonthlySalesReportController::class, 'runMonthlyInventory2']);//(الجردالشهري)بعد تنفيذ أفكار
    });