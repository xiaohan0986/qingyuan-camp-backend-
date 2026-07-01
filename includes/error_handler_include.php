<?php
/**
 * 全局错误处理（页面底部引入）
 * 放在 body 结束前，确保页面已渲染完毕才输出脚本错误
 */
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    echo '<!-- 调试模式已启用 -->';
}
