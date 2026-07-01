/**
 * 全局错误处理模块
 * 捕获所有页面加载错误和网络超时
 */

(function() {
    'use strict';
    
    // 配置
    const ERROR_PAGE = '/111ceshi/error_page.php';
    const TIMEOUT_PAGE = '/111ceshi/timeout_page.php';
    const TIMEOUT_DURATION = 30000; // 30 秒超时
    const DEBUG_MODE = false; // 生产环境设为 false
    
    // 存储活跃的请求
    const activeRequests = new Map();
    
    /**
     * 显示错误页面
     * @param {string} code - 错误代码
     * @param {string} title - 错误标题
     * @param {string} message - 错误消息
     * @param {string} details - 详细信息
     */
    function showError(code, title, message, details) {
        if (DEBUG_MODE) {
            console.error('[Error Handler]', code, title, message, details);
            return;
        }
        
        const params = new URLSearchParams({
            code: code,
            title: title,
            message: message,
            details: details || ''
        });
        
        window.location.href = ERROR_PAGE + '?' + params.toString();
    }
    
    /**
     * 显示超时页面
     * @param {string} message - 超时消息
     */
    function showTimeout(message) {
        if (DEBUG_MODE) {
            console.warn('[Timeout Handler]', message);
            return;
        }
        
        const params = new URLSearchParams({
            message: message || '请求超时，请检查网络连接'
        });
        
        window.location.href = TIMEOUT_PAGE + '?' + params.toString();
    }
    
    /**
     * 带超时控制的 fetch 封装
     * @param {string} url - 请求 URL
     * @param {object} options - fetch 选项
     * @param {number} timeout - 超时时间（毫秒）
     * @returns {Promise}
     */
    function fetchWithTimeout(url, options = {}, timeout = TIMEOUT_DURATION) {
        const controller = new AbortController();
        const { signal } = controller;
        
        const timeoutId = setTimeout(() => {
            controller.abort();
        }, timeout);
        
        activeRequests.set(url, { controller, timeoutId });
        
        return originalFetch(url, { ...options, signal })
            .then(response => {
                activeRequests.delete(url);
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    if (response.status === 404) {
                        showError('404', '页面未找到', '请求的资源不存在或已被移除');
                    } else if (response.status === 403) {
                        showError('403', '无权访问', '您没有权限访问此资源');
                    } else if (response.status === 401) {
                        showError('401', '未授权', '请先登录');
                    } else if (response.status >= 500) {
                        showError('500', '服务器错误', '服务器开小差了，请稍后再试');
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response;
            })
            .catch(error => {
                activeRequests.delete(url);
                clearTimeout(timeoutId);
                
                if (error.name === 'AbortError') {
                    showTimeout('请求超时，请检查网络连接后重试');
                } else if (error.name === 'TypeError' && error.message.includes('fetch')) {
                    showTimeout('网络连接失败，请检查网络后重试');
                } else {
                    console.error('[Fetch Error]', error);
                    showError('ERROR', '请求失败', error.message || '未知错误');
                }
                throw error;
            });
    }
    
    /**
     * 带超时控制的 XHR 封装（兼容旧代码）
     * @param {string} url - 请求 URL
     * @param {object} options - 选项 {method, headers, body, timeout}
     * @returns {Promise}
     */
    function xhrWithTimeout(url, options = {}) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const timeout = options.timeout || TIMEOUT_DURATION;
            
            let timedOut = false;
            
            const timeoutId = setTimeout(() => {
                timedOut = true;
                xhr.abort();
                showTimeout('请求超时，请检查网络连接后重试');
                reject(new Error('Timeout'));
            }, timeout);
            
            xhr.open(options.method || 'GET', url, true);
            
            // 设置请求头
            if (options.headers) {
                Object.keys(options.headers).forEach(key => {
                    xhr.setRequestHeader(key, options.headers[key]);
                });
            }
            
            xhr.timeout = timeout;
            
            xhr.onload = function() {
                clearTimeout(timeoutId);
                if (timedOut) return;
                
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.code === 404) {
                            showError('404', '资源未找到', data.message || '资源不存在');
                        } else if (data.code === 403) {
                            showError('403', '无权访问', data.message || '没有权限');
                        }
                        resolve(data);
                    } catch (e) {
                        resolve(xhr.responseText);
                    }
                } else {
                    if (xhr.status === 404) {
                        showError('404', '页面未找到', '请求的资源不存在');
                    } else if (xhr.status === 403) {
                        showError('403', '无权访问', '没有访问权限');
                    } else if (xhr.status >= 500) {
                        showError('500', '服务器错误', '服务器内部错误');
                    }
                    reject(new Error(`HTTP ${xhr.status}`));
                }
            };
            
            xhr.onerror = function() {
                clearTimeout(timeoutId);
                if (timedOut) return;
                showTimeout('网络连接失败，请检查网络后重试');
                reject(new Error('Network error'));
            };
            
            xhr.ontimeout = function() {
                clearTimeout(timeoutId);
                showTimeout('请求超时，请检查网络连接后重试');
                reject(new Error('Timeout'));
            };
            
            xhr.send(options.body || null);
        });
    }
    
    /**
     * 全局错误监听
     */
    window.addEventListener('error', function(event) {
        // 忽略扩展脚本错误
        if (event.filename && event.filename.startsWith('chrome-extension://')) {
            return;
        }
        
        if (DEBUG_MODE) {
            console.error('[Global Error]', event.message, event.filename, event.lineno);
            return;
        }
        
        // 资源加载错误（图片、脚本、样式等）
        if (event.target && event.target.tagName) {
            const tagName = event.target.tagName.toLowerCase();
            if (['script', 'link', 'img'].includes(tagName)) {
                console.warn(`[Resource Error] Failed to load ${tagName}:`, event.target.src || event.target.href);
                // 不中断页面，仅记录
            }
        }
    }, true);
    
    /**
     * 未捕获的 Promise 错误监听
     */
    window.addEventListener('unhandledrejection', function(event) {
        if (DEBUG_MODE) {
            console.warn('[Unhandled Rejection]', event.reason);
            return;
        }
        
        // 忽略已处理的错误
        if (event.reason && event.reason.message && event.reason.message.includes('Abort')) {
            return;
        }
        
        // 网络相关错误
        if (event.reason && (
            event.reason.name === 'TypeError' ||
            event.reason.message.includes('fetch') ||
            event.reason.message.includes('network') ||
            event.reason.message.includes('timeout')
        )) {
            showTimeout('网络请求失败，请检查连接后重试');
        }
    });
    
    /**
     * 页面卸载时取消所有活跃请求
     */
    window.addEventListener('beforeunload', function() {
        activeRequests.forEach(({ controller, timeoutId }) => {
            controller.abort();
            clearTimeout(timeoutId);
        });
        activeRequests.clear();
    });
    
    // 保存原始 fetch（在重写之前）
    const originalFetch = window.fetch;
    
    /**
     * 暴露全局方法供页面使用
     */
    window.GlobalErrorHandler = {
        fetch: fetchWithTimeout,
        xhr: xhrWithTimeout,
        showError: showError,
        showTimeout: showTimeout,
        setDebugMode: (mode) => { DEBUG_MODE = mode; },
        originalFetch: originalFetch
    };
    
    // 自动监控所有 fetch 调用
    window.fetch = function(...args) {
        // 如果是错误处理页面本身，使用原始 fetch
        if (window.location.pathname.includes(ERROR_PAGE) || 
            window.location.pathname.includes(TIMEOUT_PAGE)) {
            return originalFetch.apply(this, args);
        }
        
        // 其他页面使用带超时控制的 fetch
        return fetchWithTimeout.apply(this, args);
    };
    
    if (DEBUG_MODE) {
        console.log('[Global Error Handler] Initialized');
    }
})();
