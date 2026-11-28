<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\BrandController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\GlobalVariantController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\ProductMediaController;
use App\Http\Controllers\Api\Admin\ProductImportController;
use App\Http\Controllers\Api\Admin\ProductExportController;
use App\Http\Controllers\Api\Admin\SliderController;
use App\Http\Controllers\Api\StorefrontCategoryController;
use App\Http\Controllers\Api\Admin\PopularProductController;
use App\Http\Controllers\Api\StorefrontPopularProductController;
use App\Http\Controllers\Api\Admin\NewArrivalProductController;

use App\Http\Controllers\Api\Admin\AdvertisementController;
use App\Http\Controllers\Api\StorefrontAdvertisementController;

use App\Http\Controllers\Api\StorefrontNewArrivalProductController;

use App\Http\Controllers\Api\HomepageCategorySectionController;

use App\Http\Controllers\Api\Admin\CollectionController;
use App\Http\Controllers\Api\StorefrontCollectionController;

use App\Http\Controllers\Api\Admin\StoreOutletController;
use App\Http\Controllers\Api\StorefrontOutletController;

use App\Http\Controllers\Api\Admin\SiteFooterController;
use App\Http\Controllers\Api\StorefrontFooterController;
use App\Http\Controllers\Api\StorefrontProductDetailController;
use App\Http\Controllers\Api\StorefrontBrandMenuController;
use App\Http\Controllers\Api\StorefrontBrandController;

use App\Http\Controllers\Api\StorefrontProductSearchController;

use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\Admin\OrderController;

use App\Http\Controllers\Api\Admin\OrderWorkflowController;
use App\Http\Controllers\Api\AbandonedCheckoutController;



Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});




Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {

    Route::prefix('brands')->group(function () {

        Route::get('/', [BrandController::class, 'index']);
        Route::get('/statistics', [BrandController::class, 'statistics']);
        Route::get('/{brand}', [BrandController::class, 'show'])->whereNumber('brand');
        Route::post('/', [BrandController::class, 'store']);
        Route::put('/{brand}', [BrandController::class, 'update'])->whereNumber('brand');
        Route::patch('/{brand}/featured', [BrandController::class, 'toggleFeatured'])->whereNumber('brand');
        Route::patch('/{brand}/archive', [BrandController::class, 'archive'])->whereNumber('brand');
        Route::patch('/{brand}/unarchive', [BrandController::class, 'restoreArchive'])->whereNumber('brand');
        Route::post('/{brand}/logo', [BrandController::class, 'uploadLogo'])->whereNumber('brand');
        Route::delete('/{brand}', [BrandController::class, 'destroy'])->whereNumber('brand');
        Route::patch('/{brand}/restore', [BrandController::class, 'restoreDeleted'])->whereNumber('brand');
    });
});


Route::middleware(['auth:api', 'admin'])->prefix('admin/categories')->group(function () {

    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/statistics', [CategoryController::class, 'statistics']);
    Route::get('/tree', [CategoryController::class, 'tree']);
    Route::get('/{category}', [CategoryController::class, 'show'])->whereNumber('category');
    Route::post('/', [CategoryController::class, 'store']);
    Route::put('/{category}', [CategoryController::class, 'update'])->whereNumber('category');
    Route::patch('/{category}', [CategoryController::class, 'update'])->whereNumber('category');
    Route::patch('/{category}/featured', [CategoryController::class, 'toggleFeatured'])->whereNumber('category');
    Route::post('/{category}/cover', [CategoryController::class, 'uploadCover'])->whereNumber('category');
    Route::post('/{category}/icon', [CategoryController::class, 'uploadIcon'])->whereNumber('category');
    Route::delete('/{category}', [CategoryController::class, 'destroy'])->whereNumber('category');
    Route::patch('/{category}/restore', [CategoryController::class, 'restore'])->whereNumber('category');
});


Route::middleware(['auth:api', 'admin'])->prefix('admin/global-variants')->group(function () {

    Route::get('/', [GlobalVariantController::class, 'index']);
    Route::post('/', [GlobalVariantController::class, 'store']);
    Route::get('/{variant}', [GlobalVariantController::class, 'show'])->whereNumber('variant');
    Route::put('/{variant}', [GlobalVariantController::class, 'update'])->whereNumber('variant');
    Route::patch('/{variant}/reorder', [GlobalVariantController::class, 'reorder'])->whereNumber('variant');
    Route::delete('/{variant}', [GlobalVariantController::class, 'destroy'])->whereNumber('variant');
});

Route::middleware(['auth:api', 'admin'])->prefix('admin/products')->group(function () {

    // Product Listing
    Route::get('/', [ProductController::class, 'index']);

    // Product Statistics
    Route::get('/statistics', [ProductController::class, 'statistics']);

    // Create Product
    Route::post('/', [ProductController::class, 'store']);

    // Get Single Product
    Route::get('/{product}', [ProductController::class, 'show'])->whereNumber('product');

    // Update Product
    Route::put('/{product}', [ProductController::class, 'update'])->whereNumber('product');

    // Partial Update
    Route::patch('/{product}', [ProductController::class, 'update'])->whereNumber('product');

    // Soft Delete
    Route::delete('/{product}', [ProductController::class, 'destroy'])->whereNumber('product');

    // Restore Deleted Product
    Route::post('/{product}/restore', [ProductController::class, 'restore'])->whereNumber('product');
});


Route::middleware(['auth:api', 'admin'])->prefix('admin/products')->group(function () {

    Route::get('/{product}/media', [ProductMediaController::class, 'index'])->whereNumber('product');
    Route::post('/{product}/media', [ProductMediaController::class, 'store'])->whereNumber('product');
    Route::patch('/{product}/media/{media}/cover', [ProductMediaController::class, 'cover'])->whereNumber('product')->whereNumber('media');
    Route::delete('/{product}/media/{media}', [ProductMediaController::class, 'destroy'])->whereNumber('product')->whereNumber('media');
});


Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/products/imports')
    ->group(function () {
        Route::post('/', [ProductImportController::class, 'store']);
        Route::get('/{batch}', [ProductImportController::class, 'show'])->whereNumber('batch');
    });


Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/products')
    ->group(function () {
        Route::get('/exports/csv', [ProductExportController::class, 'export']);
        Route::get('/exports/sample', [ProductExportController::class, 'sample']);
    });

Route::get('/storefront/categories', function () {
    $categories = \App\Models\Category::query()
        ->orderBy('name')
        ->get()
        ->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'image' => $category->image ?? null,
                'image_url' => $category->image_url ?? null,
            ];
        });

    return response()->json([
        'success' => true,
        'data' => $categories,
    ]);
});






Route::get('/storefront/sliders', [
    SliderController::class,
    'storefront',
]);

Route::middleware('auth:api')->prefix('admin')->group(function () {
    Route::get('/sliders', [SliderController::class, 'index']);
    Route::post('/sliders', [SliderController::class, 'store']);
    Route::get('/sliders/{id}', [SliderController::class, 'show']);
    Route::put('/sliders/{id}', [SliderController::class, 'update']);
    Route::delete('/sliders/{id}', [SliderController::class, 'destroy']);
});



Route::get('/storefront/categories', [StorefrontCategoryController::class, 'index']);
Route::get('/storefront/categories/featured', [StorefrontCategoryController::class, 'featured']);


Route::middleware(['auth:api', 'admin'])
    ->group(function () {
        Route::patch('/admin/products/{product}/popular', [PopularProductController::class, 'update'])->whereNumber('product');
    });

Route::get('/storefront/products/popular', [StorefrontPopularProductController::class, 'index']);

Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/advertisements')
    ->group(function () {
        Route::get('/', [AdvertisementController::class, 'index']);
        Route::post('/', [AdvertisementController::class, 'store']);
        Route::get('/{advertisement}', [AdvertisementController::class, 'show'])->whereNumber('advertisement');
        Route::put('/{advertisement}', [AdvertisementController::class, 'update'])->whereNumber('advertisement');
        Route::delete('/{advertisement}', [AdvertisementController::class, 'destroy'])->whereNumber('advertisement');
    });

Route::get('/storefront/advertisements', [StorefrontAdvertisementController::class, 'index']);

Route::middleware(['auth:api', 'admin'])->patch('/admin/products/{product}/new-arrival', [NewArrivalProductController::class, 'update'])->whereNumber('product');

Route::get(
    '/storefront/products/new-arrivals',
    [StorefrontNewArrivalProductController::class, 'index']
);

Route::get('/storefront/category-sections', [HomepageCategorySectionController::class, 'index']);

Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/homepage-category-sections')
    ->group(function () {
        Route::get('/', [HomepageCategorySectionController::class, 'manage']);
        Route::get('/products', [HomepageCategorySectionController::class, 'availableProducts']);
        Route::post('/', [HomepageCategorySectionController::class, 'store']);
        Route::put('/{section}', [HomepageCategorySectionController::class, 'update'])->whereNumber('section');
        Route::delete('/{section}', [HomepageCategorySectionController::class, 'destroy'])->whereNumber('section');
    });


Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/collections')
    ->group(function () {
        Route::get('/', [CollectionController::class, 'index']);
        Route::get('/statistics', [CollectionController::class, 'statistics']);
        Route::get('/products/search', [CollectionController::class, 'searchProducts']);
        Route::post('/', [CollectionController::class, 'store']);
        Route::get('/{collection}', [CollectionController::class, 'show'])->whereNumber('collection');
        Route::put('/{collection}', [CollectionController::class, 'update'])->whereNumber('collection');
        Route::post('/{collection}/cover', [CollectionController::class, 'uploadCover'])->whereNumber('collection');
        Route::delete('/{collection}/cover', [CollectionController::class, 'removeCover'])->whereNumber('collection');
        Route::delete('/{collection}', [CollectionController::class, 'destroy'])->whereNumber('collection');
    });

Route::get('/storefront/collections', [StorefrontCollectionController::class, 'index']);
Route::get('/storefront/collections/{slug}', [StorefrontCollectionController::class, 'show']);


Route::get('/storefront/outlets', [StorefrontOutletController::class, 'index']);

Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/store-outlets')
    ->group(function () {
        Route::get('/', [StoreOutletController::class, 'index']);
        Route::post('/', [StoreOutletController::class, 'store']);
        Route::get('/{outlet}', [StoreOutletController::class, 'show'])->whereNumber('outlet');
        Route::put('/{outlet}', [StoreOutletController::class, 'update'])->whereNumber('outlet');
        Route::delete('/{outlet}', [StoreOutletController::class, 'destroy'])->whereNumber('outlet');
        Route::post('/{outlet}/images', [StoreOutletController::class, 'uploadImages'])->whereNumber('outlet');
        Route::delete('/{outlet}/images/{image}', [StoreOutletController::class, 'deleteImage'])->whereNumber('outlet')->whereNumber('image');
    });


Route::get('/storefront/footer', [StorefrontFooterController::class, 'index']);

Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/footer')
    ->group(function () {
        Route::get('/', [SiteFooterController::class, 'show']);
        Route::put('/', [SiteFooterController::class, 'update']);
        Route::post('/logo', [SiteFooterController::class, 'uploadLogo']);
        Route::delete('/logo', [SiteFooterController::class, 'removeLogo']);
    });


    Route::get('/storefront/product-details/{slug}', [StorefrontProductDetailController::class, 'show']);

    Route::get('/storefront/brands/menu', [StorefrontBrandMenuController::class, 'index']);

    Route::get('/storefront/brand/{slug}', [StorefrontBrandController::class, 'show']);

    Route::get('/storefront/search/products', [StorefrontProductSearchController::class, 'index']);


    Route::prefix('storefront')->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth:api')
    ->prefix('user')
    ->group(function () {
        Route::get('/orders', [CheckoutController::class, 'myOrders']);
        Route::get('/orders/{id}', [CheckoutController::class, 'myOrder'])->whereNumber('id');
    });

Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/orders')
    ->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/statistics', [OrderController::class, 'statistics']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show'])->whereNumber('id');
        Route::patch('/{id}/status', [OrderController::class, 'updateStatus'])->whereNumber('id');
        Route::patch('/{id}/payment', [OrderController::class, 'confirmPayment'])->whereNumber('id');
        Route::post('/{id}/notes', [OrderController::class, 'addNote'])->whereNumber('id');
    });



    Route::post('/storefront/checkout-progress', [AbandonedCheckoutController::class, 'store'])->middleware('throttle:5,1');

Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/orders')
    ->group(function () {
        Route::get('/products/search', [OrderWorkflowController::class, 'products']);

        Route::get('/preorders', function (\Illuminate\Http\Request $request, OrderWorkflowController $controller) {
            return $controller->list($request, 'preorders');
        });

        Route::post('/preorders', [OrderWorkflowController::class, 'createPreorder']);
        Route::get('/preorders/{id}', [OrderWorkflowController::class, 'preorder'])->whereNumber('id');
        Route::post('/preorders/{id}/release', [OrderWorkflowController::class, 'releasePreorder'])->whereNumber('id');
        Route::post('/preorders/{id}/cancel', [OrderWorkflowController::class, 'cancelPreorder'])->whereNumber('id');

        Route::get('/returns', function (\Illuminate\Http\Request $request, OrderWorkflowController $controller) {
            return $controller->list($request, 'returns');
        });

        Route::post('/returns', [OrderWorkflowController::class, 'createReturn']);
        Route::patch('/returns/{id}', [OrderWorkflowController::class, 'updateReturn'])->whereNumber('id');

        Route::get('/quotes', function (\Illuminate\Http\Request $request, OrderWorkflowController $controller) {
            return $controller->list($request, 'quotes');
        });

        Route::post('/quotes', [OrderWorkflowController::class, 'createQuote']);
        Route::get('/quotes/{id}', [OrderWorkflowController::class, 'quote'])->whereNumber('id');
        Route::patch('/quotes/{id}', [OrderWorkflowController::class, 'updateQuote'])->whereNumber('id');
        Route::post('/quotes/{id}/convert', [OrderWorkflowController::class, 'convertQuote'])->whereNumber('id');

        Route::get('/abandoned', function (\Illuminate\Http\Request $request, OrderWorkflowController $controller) {
            return $controller->list($request, 'abandoned');
        });

        Route::get('/abandoned/{id}', [OrderWorkflowController::class, 'abandoned'])->whereNumber('id');
        Route::post('/abandoned/{id}/archive', [OrderWorkflowController::class, 'archiveAbandoned'])->whereNumber('id');
    });
