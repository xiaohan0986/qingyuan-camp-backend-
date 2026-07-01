/**
 * 全局 Tailwind CSS 配置
 * 青园营地管理系统 - 统一样式配置
 */

tailwind.config = {
    theme: {
        extend: {
            colors: {
                primary: '#165DFF',
                success: '#00B42A',
                warning: '#FF7D00',
                danger: '#F53F3F',
                dark: '#1D2129',
                light: '#F2F3F5'
            },
            fontFamily: {
                inter: ['Inter', 'system-ui', '-apple-system', 'PingFang SC', 'Microsoft YaHei', 'sans-serif']
            },
            boxShadow: {
                'card': '0 1px 3px rgba(0, 0, 0, 0.1)',
                'card-lg': '0 4px 6px rgba(0, 0, 0, 0.1)',
            }
        }
    },
    darkMode: 'class'
};
