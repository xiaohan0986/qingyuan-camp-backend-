<?php
/**
 * 平台后台 API 路由
 * 前缀: /adminapi/ （由 group 自动添加）
 */

Router::group('/adminapi', function () {

    // ===== 商品管理 =====
    Router::get('/product', 'ProductController@index');
    Router::get('/product/:id', 'ProductController@show');
    Router::post('/product', 'ProductController@store');
    Router::put('/product/:id', 'ProductController@update');
    Router::delete('/product/:id', 'ProductController@destroy');
    Router::put('/product/:id/status', 'ProductController@toggleStatus');

    // ===== 优惠券管理 =====
    Router::get('/coupon', 'CouponController@index');
    Router::get('/coupon/:id', 'CouponController@show');
    Router::post('/coupon', 'CouponController@store');
    Router::put('/coupon/:id', 'CouponController@update');
    Router::delete('/coupon/:id', 'CouponController@destroy');
    Router::put('/coupon/:id/status', 'CouponController@toggleStatus');
    Router::get('/coupon/:id/logs', 'CouponController@receiveLogs');
    Router::post('/coupon/receive', 'CouponController@receive');
    Router::get('/coupon/user/list', 'CouponController@userCoupons');

    // ===== 订单管理 =====
    Router::get('/order', 'OrderController@index');
    Router::get('/order/:id', 'OrderController@show');
    Router::put('/order/:id/pay', 'OrderController@pay');
    Router::put('/order/:id/ship', 'OrderController@ship');
    Router::put('/order/:id/receive', 'OrderController@receive');
    Router::put('/order/:id/complete', 'OrderController@complete');
    Router::put('/order/:id/cancel', 'OrderController@cancel');
    Router::post('/order/auto-cancel', 'OrderController@autoCancel');

    // ===== 秒杀管理 =====
    Router::get('/seckill', 'SeckillController@index');
    Router::get('/seckill/:id', 'SeckillController@show');
    Router::post('/seckill', 'SeckillController@store');
    Router::put('/seckill/:id', 'SeckillController@update');
    Router::delete('/seckill/:id', 'SeckillController@destroy');
    Router::put('/seckill/:id/status', 'SeckillController@toggleStatus');
    Router::get('/seckill/active', 'SeckillController@active');
    Router::post('/seckill/place', 'SeckillController@place');

    // ===== 分销管理 =====
    Router::get('/distributor', 'DistributorController@index');
    Router::post('/distributor/apply', 'DistributorController@apply');
    Router::get('/distributor/:id/commissions', 'DistributorController@commissions');
    Router::post('/distributor/withdraw', 'DistributorController@withdraw');
    Router::get('/distributor/levels', 'DistributorController@levels');

});
