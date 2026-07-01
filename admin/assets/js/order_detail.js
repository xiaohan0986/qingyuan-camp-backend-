// 订单详情 TAB 切换脚本
(function() {
    console.log('订单详情 TAB 脚本开始执行');
    
    function switchTab(tabName, btn) {
        console.log('切换 TAB:', tabName);
        
        // 隐藏所有 TAB 内容
        var contents = document.querySelectorAll('.tab-content');
        for (var i = 0; i < contents.length; i++) {
            contents[i].classList.remove('active');
        }
        
        // 移除所有 TAB 按钮的激活状态
        var buttons = document.querySelectorAll('.tab-btn');
        for (var i = 0; i < buttons.length; i++) {
            buttons[i].classList.remove('active');
        }
        
        // 显示当前 TAB 内容
        var tabElement = document.getElementById('tab-' + tabName);
        if (tabElement) {
            tabElement.classList.add('active');
            console.log('显示 TAB:', tabName);
        }
        
        // 激活当前 TAB 按钮
        if (btn) {
            btn.classList.add('active');
        }
    }
    
    function initTabs() {
        console.log('初始化 TAB');
        var buttons = document.querySelectorAll('.tab-btn');
        console.log('找到按钮数量:', buttons.length);
        
        for (var i = 0; i < buttons.length; i++) {
            (function(btn) {
                btn.addEventListener('click', function(e) {
                    console.log('按钮点击:', btn.getAttribute('data-tab'));
                    e.preventDefault();
                    e.stopPropagation();
                    var tabName = btn.getAttribute('data-tab');
                    switchTab(tabName, btn);
                });
                console.log('已绑定按钮:', btn.textContent);
            })(buttons[i]);
        }
    }
    
    // 等待 DOM 加载完成
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTabs);
    } else {
        // DOM 已加载，延迟执行确保元素存在
        setTimeout(initTabs, 100);
    }
})();
