<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FirebaseNotificationController;
use App\Http\Controllers\NotificationnController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ExchangeController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\GiftController;
use App\Models\Register;
use App\Models\System;
//  AlaaAlhariri19711972
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('user/register', [UserController::class, 'register']);
Route::post('user/login', [UserController::class, 'login'])->middleware('throttle:forUser');
Route::post('user/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
// Route::post('forgetPassword1',[UserController::class,'forgetPassword1']);
// Route::post('forgetPassword2',[UserController::class,'forgetPassword2']);
Route::post('scan', [BarcodeController::class, 'scan'])->middleware('auth:sanctum'); //security
Route::get('profile1', [UserController::class, 'profile1'])->middleware('auth:sanctum');
Route::post('updateProfile', [UserController::class, 'updateProfile'])->middleware('auth:sanctum');
Route::get('numberOfNotifications', [NotificationnController::class, 'numberOfNotifications'])->middleware('auth:sanctum', 'not_blocked');
Route::get('showNotification', [NotificationnController::class, 'showNotification'])->middleware('auth:sanctum');
Route::get('agents', [FollowController::class, 'agents'])->middleware('auth:sanctum');
Route::post('createFollow/{id}', [FollowController::class, 'createFollow'])->middleware('auth:sanctum');
Route::post('unFollow/{id}', [FollowController::class, 'unFollow'])->middleware('auth:sanctum');
Route::get('followingsHim', [FollowController::class, 'followingsHim'])->middleware('auth:sanctum');























Route::post('admin/login', [UserController::class, 'login2'])->middleware('throttle:forAdmin');
Route::post('admin/logout', [UserController::class, 'logout'])->middleware('auth:sanctum', 'admin');
Route::get('profile2', [UserController::class, 'profile2'])->middleware('auth:sanctum', 'admin');
Route::post('storeAndPrint/{id}', [BarcodeController::class, 'storeAndPrint'])->middleware('auth:sanctum', 'admin'); //security  transaction
Route::get('code_verification/{id}', [BarcodeController::class, 'code_verification'])->middleware('auth:sanctum', 'admin');
Route::post('updatePoints/{id}', [BarcodeController::class, 'updatePoints'])->middleware('auth:sanctum', 'admin'); //security
Route::post('/sendToUser/{id}', [FirebaseNotificationController::class, 'sendToUser'])->middleware('auth:sanctum', 'admin_or_super_admin');
Route::get('countryUsers', [UserController::class, 'countryUsers'])->middleware('auth:sanctum', 'admin');
Route::get('whoFollowers', [FollowController::class, 'whoFollowers'])->middleware('auth:sanctum', 'admin');
Route::post('/sendTofollow', [FirebaseNotificationController::class, 'sendTofollow'])->middleware('auth:sanctum', 'admin');
Route::get('myCategory_admin', [UserController::class, 'myCategory_admin'])->middleware('auth:sanctum', 'admin');
Route::post('increasePointsByAdmin/{idOfUser}/{idOfCategory}', [UserController::class, 'increasePointsByAdmin'])->middleware('auth:sanctum', 'admin'); //security





















Route::get('/categories', [CategoryController::class, 'categories']);
Route::get('/getOffersOfCategory_desc/{categoryId}', [OfferController::class, 'getOffersOfCategory_desc']);
Route::get('/getOffersOfCategory_descPoints/{categoryId}', [OfferController::class, 'getOffersOfCategory_descPoints']);
Route::get('/getOffersOfCategory_ascSyrian_price/{categoryId}', [OfferController::class, 'getOffersOfCategory_ascSyrian_price']);
Route::get('/getGiftsBox_desc', [GiftController::class, 'getGiftsBox_desc']);
Route::get('/getGiftsBox_descPoints', [GiftController::class, 'getGiftsBox_descPoints']);
Route::get('/getGiftsBox_ascPoints', [GiftController::class, 'getGiftsBox_ascPoints']);



Route::post('superAdminRegisterrr', [UserController::class, 'superAdminRegisterrr']);
Route::post('superAdminLoginnn', [UserController::class, 'superAdminLoginnn']);

Route::post('superadmin/registerForAdmin', [UserController::class, 'registerForAdmin'])->middleware('auth:sanctum', 'super_admin');

Route::post('/sendToAll', [FirebaseNotificationController::class, 'sendToAll'])->middleware('auth:sanctum', 'super_admin');
Route::post('/sendToUser/{id}', [FirebaseNotificationController::class, 'sendToUser'])->middleware('auth:sanctum', 'admin_or_super_admin');
Route::post('/sendToCountry', [FirebaseNotificationController::class, 'sendToCountry'])->middleware('auth:sanctum', 'super_admin');
Route::get('whoFollowersThisAgent/{id}', [FollowController::class, 'whoFollowersThisAgent'])->middleware('auth:sanctum', 'super_admin');
Route::post('/snd_Notification_To_The_Customers_Of_This_Agent/{id}', [FirebaseNotificationController::class, 'snd_Notification_To_The_Customers_Of_This_Agent'])->middleware('auth:sanctum', 'super_admin');
Route::post('updatePointsBySuperAdmin/{id}', [BarcodeController::class, 'updatePointsBySuperAdmin'])->middleware('auth:sanctum', 'super_admin');
Route::post('editNumberOfPointsAllowed/{id}', [BarcodeController::class, 'editNumberOfPointsAllowed'])->middleware('auth:sanctum', 'super_admin');


Route::post('/updateCategoriesBySuperAdmin/{id}', [CategoryController::class, 'updateCategoriesBySuperAdmin'])->middleware('auth:sanctum', 'super_admin');

Route::post('/createCategory', [CategoryController::class, 'createCategory'])->middleware('auth:sanctum', 'super_admin');
Route::post('/updateCategory/{id}', [CategoryController::class, 'updateCategory'])->middleware('auth:sanctum', 'super_admin');
Route::post('/deleteCategory/{id}', [CategoryController::class, 'deleteCategory'])->middleware('auth:sanctum', 'super_admin');


Route::get('showCategoriesForAdmin/{id}', [CategoryController::class, 'showCategoriesForAdmin'])->middleware('auth:sanctum', 'super_admin');
Route::post('/updateCategories/{id}', [CategoryController::class, 'updateCategories'])->middleware('auth:sanctum', 'super_admin');



Route::get('superadmin/showAlladmins', [UserController::class, 'showAlladmins'])->middleware('auth:sanctum', 'super_admin');
Route::post('/classificationAdminsByCountry', [UserController::class, 'classificationAdminsByCountry'])->middleware('auth:sanctum', 'super_admin');
Route::get('/showAdminsOfCategory/{id}', [CategoryController::class, 'showAdminsOfCategory'])->middleware('auth:sanctum', 'super_admin');
Route::post('/classificationAdminsByCountryAndCategory/{id}', [UserController::class, 'classificationAdminsByCountryAndCategory'])->middleware('auth:sanctum', 'super_admin');

Route::get('allUsers', [UserController::class, 'allUsers'])->middleware('auth:sanctum', 'super_admin');
Route::post('/classificationUsersByCountry', [UserController::class, 'classificationUsersByCountry'])->middleware('auth:sanctum', 'super_admin');
Route::get('/showUsersOfCategory/{id}', [CategoryController::class, 'showUsersOfCategory'])->middleware('auth:sanctum', 'super_admin');
Route::post('/classificationUsersByCountryAndCategory/{id}', [UserController::class, 'classificationUsersByCountryAndCategory'])->middleware('auth:sanctum', 'super_admin');

Route::post('/increasePointsForAll', [UserController::class, 'increasePointsForAll'])->middleware('auth:sanctum', 'super_admin'); //الوضع تمام من حيث الحماية والثغرات
Route::post('/increasePointsAccordingToCountry', [UserController::class, 'increasePointsAccordingToCountry'])->middleware('auth:sanctum', 'super_admin'); //الوضع تمام من حيث الحماية والثغرات
Route::post('/increasePointsAccordingToCategory/{id}', [UserController::class, 'increasePointsAccordingToCategory'])->middleware('auth:sanctum', 'super_admin'); //الوضع تمام من حيث الحماية والثغرات
Route::post('/increasePointsAccordingToCountryAndCategory/{id}', [UserController::class, 'increasePointsAccordingToCountryAndCategory'])->middleware('auth:sanctum', 'super_admin'); //الوضع تمام من حيث الحماية والثغرات

Route::post('/sendToAllUsers', [FirebaseNotificationController::class, 'sendToAllUsers'])->middleware('auth:sanctum', 'super_admin');
Route::post('/sendToUsersAccordingToCountry', [FirebaseNotificationController::class, 'sendToUsersAccordingToCountry'])->middleware('auth:sanctum', 'super_admin');
Route::post('/sendToUsersAccordingCategory/{id}', [FirebaseNotificationController::class, 'sendToUsersAccordingCategory'])->middleware('auth:sanctum', 'super_admin');
Route::post('/sendToUsersAccordingToCountryAndCategory/{id}', [FirebaseNotificationController::class, 'sendToUsersAccordingToCountryAndCategory'])->middleware('auth:sanctum', 'super_admin');

Route::post('/sendToAllAdmins', [FirebaseNotificationController::class, 'sendToAllAdmins'])->middleware('auth:sanctum', 'super_admin');
Route::post('/sendToAdminsAccordingToCountry', [FirebaseNotificationController::class, 'sendToAdminsAccordingToCountry'])->middleware('auth:sanctum', 'super_admin');
Route::post('/sendToAdminsAccordingCategory/{id}', [FirebaseNotificationController::class, 'sendToAdminsAccordingCategory'])->middleware('auth:sanctum', 'super_admin');
Route::post('/sendToAdminsAccordingToCountryAndCategory/{id}', [FirebaseNotificationController::class, 'sendToAdminsAccordingToCountryAndCategory'])->middleware('auth:sanctum', 'super_admin');

Route::get('showCountriesAccordingToTheTotalPointsOfThereUsers', [UserController::class, 'showCountriesAccordingToTheTotalPointsOfThereUsers'])->middleware('auth:sanctum', 'super_admin');
Route::get('showCategoriesAccordingToTheCategoricalUserPoints', [UserController::class, 'showCategoriesAccordingToTheCategoricalUserPoints'])->middleware('auth:sanctum', 'super_admin');
Route::get('showCountriesAndCategoriesAccordingToTheCategoricalUserPoints', [UserController::class, 'showCountriesAndCategoriesAccordingToTheCategoricalUserPoints'])->middleware('auth:sanctum', 'super_admin');

Route::post('/createOffer/{id}', [OfferController::class, 'createOffer'])->middleware('auth:sanctum', 'super_admin');
Route::post('/deleteOffer/{id}', [OfferController::class, 'deleteOffer'])->middleware('auth:sanctum', 'super_admin');

Route::get('/getOffersOfCategory_desc/{categoryId}', [OfferController::class, 'getOffersOfCategory_desc']);
Route::get('/getOffersOfCategory_descPoints/{categoryId}', [OfferController::class, 'getOffersOfCategory_descPoints']);
Route::get('/getOffersOfCategory_ascSyrian_price/{categoryId}', [OfferController::class, 'getOffersOfCategory_ascSyrian_price']);


Route::post('/set_Dollar_Exchange_Rate', [ExchangeController::class, 'set_Dollar_Exchange_Rate'])->middleware('auth:sanctum', 'super_admin');
Route::get('/get_Dollar_Exchange_Rate', [ExchangeController::class, 'get_Dollar_Exchange_Rate']);



Route::post('/createGiftsBox', [GiftController::class, 'createGiftsBox'])->middleware('auth:sanctum', 'super_admin');
Route::post('/deleteGiftsBox/{id}', [GiftController::class, 'deleteGiftsBox'])->middleware('auth:sanctum', 'super_admin');


Route::get('/allRegisters', [RegisterController::class, 'allRegisters'])->middleware('auth:sanctum', 'super_admin');
Route::get('/printRegisters', [RegisterController::class, 'printRegisters'])->middleware('auth:sanctum', 'super_admin');
Route::get('/scanRegisters', [RegisterController::class, 'scanRegisters'])->middleware('auth:sanctum', 'super_admin');
Route::get('/increaseRegisters', [RegisterController::class, 'increaseRegisters'])->middleware('auth:sanctum', 'super_admin');
Route::get('/reduceRegisters', [RegisterController::class, 'reduceRegisters'])->middleware('auth:sanctum', 'super_admin');
Route::post('/deleteRegisters', [RegisterController::class, 'deleteRegisters'])->middleware('auth:sanctum', 'super_admin');



Route::post('/unBlock/{id}', [UserController::class, 'unBlock'])->middleware('auth:sanctum', 'super_admin');
Route::get('/showBlockedAdmin', [UserController::class, 'showBlockedAdmin'])->middleware('auth:sanctum', 'super_admin');
Route::get('/showBlockedUser', [UserController::class, 'showBlockedUser'])->middleware('auth:sanctum', 'super_admin');

Route::post('/setAdminVersion', [SystemController::class, 'setAdminVersion'])->middleware('auth:sanctum', 'super_admin');
Route::post('/setUserVersion', [SystemController::class, 'setUserVersion'])->middleware('auth:sanctum', 'super_admin');

Route::get('/getAdminVersion', [SystemController::class, 'getAdminVersion'])->middleware('auth:sanctum', 'super_admin');
Route::get('/getUserVersion', [SystemController::class, 'getUserVersion'])->middleware('auth:sanctum', 'super_admin');

Route::get('/checkAdminVersion/{id}', [SystemController::class, 'checkAdminVersion'])->middleware('checkOfAdminVersion');
Route::get('/checkUserVersion/{id}', [SystemController::class, 'checkUserVersion'])->middleware('checkOfUserVersion');
