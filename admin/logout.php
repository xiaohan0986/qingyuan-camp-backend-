<?php
/**
 * 退出登录
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

Auth::logout();
