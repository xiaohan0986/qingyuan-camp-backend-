<?php
$currentPage = 'finance';
$pageTitle = '财务管理';

// 检查访问权限
require_once dirname(__DIR__) . '/includes/PermissionChecker.php';
$permissionChecker = new PermissionChecker();
$permissionChecker->requireAccess('finance');

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* 页面容器 */
.finance-container {
    padding: 24px;
}

/* 统计卡片 */
.finance-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.finance-stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
    position: relative;
    overflow: hidden;
}

.finance-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}

.finance-stat-card.income::before { background: #52c41a; }
.finance-stat-card.expense::before { background: #ff4d4f; }
.finance-stat-card.balance::before { background: #1890ff; }
.finance-stat-card.receivable::before { background: #faad14; }

.finance-stat-card .label {
    font-size: 14px;
    color: #8c8c8c;
    margin-bottom: 12px;
}

.finance-stat-card .value {
    font-size: 28px;
    font-weight: 700;
    color: #262626;
}

.finance-stat-card .sub-value {
    font-size: 12px;
    color: #8c8c8c;
    margin-top: 8px;
}

/* 工具栏 */
.finance-toolbar {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.finance-filters {
    display: flex;
    gap: 12px;
    flex-wrap: nowrap;
}

.finance-filters input,
.finance-filters select {
    padding: 8px 12px;
    border: 1px solid #d9d9d9;
    border-radius: 6px;
    font-size: 14px;
}

.finance-filters input {
    width: 200px;
}

.finance-actions {
    display: flex;
    gap: 12px;
}

/* 表格 */
.finance-table {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.finance-table table {
    width: 100%;
    border-collapse: collapse;
}

.finance-table th,
.finance-table td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

.finance-table th {
    background: #fafafa;
    font-weight: 600;
    color: #262626;
    font-size: 14px;
}

.finance-table td {
    font-size: 14px;
    color: #595959;
}

.finance-table tr:hover {
    background: #fafafa;
}

/* 状态标签 */
.type-tag {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.type-income {
    background: #f6ffed;
    color: #52c41a;
    border: 1px solid #b7eb8f;
}

.type-expense {
    background: #fff2f0;
    color: #ff4d4f;
    border: 1px solid #ffccc7;
}

.status-tag {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.status-paid {
    background: #f6ffed;
    color: #52c41a;
    border: 1px solid #b7eb8f;
}

.status-unpaid {
    background: #fff7e6;
    color: #fa8c16;
    border: 1px solid #ffd591;
}

.status-pending {
    background: #f5f5f5;
    color: #8c8c8c;
    border: 1px solid #d9d9d9;
}

/* 操作按钮 */
.action-btns {
    display: flex;
    gap: 8px;
}

.action-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.3s;
}

.action-btn.edit {
    background: #e6f7ff;
    color: #1890ff;
}

.action-btn.edit:hover {
    background: #1890ff;
    color: white;
}

.action-btn.delete {
    background: #fff2f0;
    color: #ff4d4f;
}

.action-btn.delete:hover {
    background: #ff4d4f;
    color: white;
}

/* 分页 */
.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 24px;
    padding: 16px 0;
}

.pagination button {
    padding: 8px 16px;
    border: 1px solid #d9d9d9;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.pagination button:hover:not(:disabled) {
    border-color: #1890ff;
    color: #1890ff;
}

.pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination button.active {
    background: #1890ff;
    color: white;
    border-color: #1890ff;
}

.page-size-selector,
.page-info,
.page-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #595959;
    font-size: 14px;
}

.page-info {
    flex: 1;
    justify-content: center;
}

.page-buttons {
    gap: 12px;
}

/* 弹窗 */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    padding: 32px;
    width: 600px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #262626;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #8c8c8c;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #262626;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d9d9d9;
    border-radius: 6px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
}

.btn {
    padding: 10px 24px;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    border: none;
    transition: all 0.3s;
}

.btn-primary {
    background: #1890ff;
    color: white;
}

.btn-primary:hover {
    background: #40a9ff;
}

.btn-default {
    background: #f5f5f5;
    color: #595959;
    border: 1px solid #d9d9d9;
}

.btn-default:hover {
    color: #1890ff;
    border-color: #1890ff;
}

.btn-danger {
    background: #ff4d4f;
    color: white;
}

.btn-danger:hover {
    background: #ff7875;
}
</style>

<div class="finance-container">
    <!-- 统计卡片 -->
    <div class="finance-stats">
        <div class="finance-stat-card income">
            <div class="label">💰 总收入</div>
            <div class="value" id="stat-income">¥0.00</div>
            <div class="sub-value">本月：<span id="stat-income-month">¥0.00</span></div>
        </div>
        <div class="finance-stat-card expense">
            <div class="label">💸 总支出</div>
            <div class="value" id="stat-expense">¥0.00</div>
            <div class="sub-value">本月：<span id="stat-expense-month">¥0.00</span></div>
        </div>
        <div class="finance-stat-card balance">
            <div class="label">💳 结余</div>
            <div class="value" id="stat-balance">¥0.00</div>
            <div class="sub-value">本月：<span id="stat-balance-month">¥0.00</span></div>
        </div>
        <div class="finance-stat-card receivable">
            <div class="label">📋 待收款</div>
            <div class="value" id="stat-receivable">¥0.00</div>
            <div class="sub-value">待付款：<span id="stat-payable">¥0.00</span></div>
        </div>
    </div>

    <!-- 工具栏 -->
    <div class="finance-toolbar">
        <div class="finance-filters">
            <input type="text" id="filter-keyword" placeholder="搜索摘要/备注">
            <select id="filter-type">
                <option value="">全部类型</option>
                <option value="income">收入</option>
                <option value="expense">支出</option>
            </select>
            <select id="filter-status">
                <option value="">全部状态</option>
                <option value="paid">已收/已付</option>
                <option value="unpaid">未收/未付</option>
                <option value="pending">待处理</option>
            </select>
            <input type="date" id="filter-date-start">
            <input type="date" id="filter-date-end">
            <button class="btn btn-default" onclick="loadData()">筛选</button>
            <button class="btn btn-default" onclick="resetFilters()">重置</button>
        </div>
        <div class="finance-actions">
            <button class="btn btn-primary" onclick="showCreateModal()">➕ 新增记录</button>
        </div>
    </div>

    <!-- 数据表格 -->
    <div class="finance-table">
        <div class="table-header">
            <h3 style="margin: 0;">财务记录</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>类型</th>
                    <th>金额</th>
                    <th>摘要</th>
                    <th>关联单号</th>
                    <th>日期</th>
                    <th>状态</th>
                    <th>备注</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="finance-list">
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px;">加载中...</td>
                </tr>
            </tbody>
        </table>

        <!-- 分页 -->
        <div class="pagination" id="pagination">
            <div class="page-size-selector">
                <span>每页显示</span>
                <select id="page-size" onchange="changePageSize()" style="padding: 6px 12px; border: 1px solid #d9d9d9; border-radius: 6px; margin: 0 8px;">
                    <option value="10">10 条</option>
                    <option value="20" selected>20 条</option>
                    <option value="50">50 条</option>
                    <option value="100">100 条</option>
                </select>
            </div>
            <div class="page-info">
                <span>共 <strong id="total-count">0</strong> 条</span>
                <span style="margin: 0 16px;">|</span>
                <span>第 <strong id="current-page-num">1</strong> 页</span>
                <span style="margin: 0 8px;">/</span>
                <span>共 <strong id="total-pages">1</strong> 页</span>
            </div>
            <div class="page-buttons">
                <button onclick="changePage(-1)" id="prev-btn" disabled>上一页</button>
                <button onclick="changePage(1)" id="next-btn" disabled>下一页</button>
            </div>
        </div>
    </div>
</div>

<!-- 新增/编辑弹窗 -->
<div class="modal" id="finance-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">新增财务记录</h2>
            <button class="close-btn" onclick="closeModal()">×</button>
        </div>
        <form id="finance-form" onsubmit="saveData(event)">
            <input type="hidden" id="form-id">
            <div class="form-row">
                <div class="form-group">
                    <label>类型 *</label>
                    <select id="form-type" required onchange="toggleStatusOptions()">
                        <option value="income">收入</option>
                        <option value="expense">支出</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>金额 *</label>
                    <input type="number" id="form-amount" step="0.01" required min="0">
                </div>
            </div>
            <div class="form-group">
                <label>摘要 *</label>
                <input type="text" id="form-title" required placeholder="例如：销售收款、办公采购">
            </div>
            <div class="form-group">
                <label>关联单号</label>
                <input type="text" id="form-ref-no" placeholder="例如：SO20260411001">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>日期 *</label>
                    <input type="date" id="form-date" required>
                </div>
                <div class="form-group">
                    <label>状态 *</label>
                    <select id="form-status" required>
                        <option value="paid">已收/已付</option>
                        <option value="unpaid">未收/未付</option>
                        <option value="pending">待处理</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>备注</label>
                <textarea id="form-remark" placeholder="补充说明..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" onclick="closeModal()">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentPage = 1;
let totalPages = 1;
let pageSize = 20;
let totalRecords = 0;

// 加载统计数据
async function loadStats() {
    try {
        const response = await fetch('../api/finance.php?action=stats');
        const result = await response.json();
        
        if (result.success) {
            const stats = result.data;
            document.getElementById('stat-income').textContent = '¥' + parseFloat(stats.total_income || 0).toLocaleString('zh-CN', {minimumFractionDigits: 2});
            document.getElementById('stat-expense').textContent = '¥' + parseFloat(stats.total_expense || 0).toLocaleString('zh-CN', {minimumFractionDigits: 2});
            document.getElementById('stat-balance').textContent = '¥' + parseFloat(stats.balance || 0).toLocaleString('zh-CN', {minimumFractionDigits: 2});
            document.getElementById('stat-receivable').textContent = '¥' + parseFloat(stats.receivable || 0).toLocaleString('zh-CN', {minimumFractionDigits: 2});
            document.getElementById('stat-payable').textContent = '¥' + parseFloat(stats.payable || 0).toLocaleString('zh-CN', {minimumFractionDigits: 2});
            
            document.getElementById('stat-income-month').textContent = '¥' + parseFloat(stats.month_income || 0).toLocaleString('zh-CN', {minimumFractionDigits: 2});
            document.getElementById('stat-expense-month').textContent = '¥' + parseFloat(stats.month_expense || 0).toLocaleString('zh-CN', {minimumFractionDigits: 2});
            document.getElementById('stat-balance-month').textContent = '¥' + parseFloat(stats.month_balance || 0).toLocaleString('zh-CN', {minimumFractionDigits: 2});
        }
    } catch (error) {
        console.error('加载统计数据失败:', error);
    }
}

// 加载数据列表
async function loadData() {
    const keyword = document.getElementById('filter-keyword').value;
    const type = document.getElementById('filter-type').value;
    const status = document.getElementById('filter-status').value;
    const dateStart = document.getElementById('filter-date-start').value;
    const dateEnd = document.getElementById('filter-date-end').value;
    
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        page_size: pageSize,
        keyword: keyword,
        type: type,
        status: status,
        date_start: dateStart,
        date_end: dateEnd
    });
    
    try {
        const response = await fetch('../api/finance.php?' + params.toString());
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('finance-list');
            
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px;">暂无数据</td></tr>';
            } else {
                tbody.innerHTML = result.data.map(item => `
                    <tr>
                        <td>${item.id}</td>
                        <td><span class="type-tag type-${item.type}">${item.type === 'income' ? '💰 收入' : '💸 支出'}</span></td>
                        <td style="font-weight: 600; color: ${item.type === 'income' ? '#52c41a' : '#ff4d4f'}">
                            ${item.type === 'income' ? '+' : '-'}¥${parseFloat(item.amount).toLocaleString('zh-CN', {minimumFractionDigits: 2})}
                        </td>
                        <td>${item.title}</td>
                        <td>${item.ref_no || '-'}</td>
                        <td>${item.date}</td>
                        <td><span class="status-tag status-${item.status}">${getStatusText(item.status)}</span></td>
                        <td>${item.remark || '-'}</td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn edit" onclick="editData(${item.id})">编辑</button>
                                <button class="action-btn delete" onclick="deleteData(${item.id})">删除</button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
            
            totalPages = result.total_pages;
            totalRecords = result.total;
            
            // 更新分页信息
            document.getElementById('total-count').textContent = totalRecords;
            document.getElementById('current-page-num').textContent = result.page;
            document.getElementById('total-pages').textContent = totalPages;
            document.getElementById('prev-btn').disabled = result.page <= 1;
            document.getElementById('next-btn').disabled = result.page >= totalPages;
        }
    } catch (error) {
        console.error('加载数据失败:', error);
        document.getElementById('finance-list').innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px; color: red;">加载失败</td></tr>';
    }
}

// 获取状态文本
function getStatusText(status) {
    const map = {
        'paid': '已收/已付',
        'unpaid': '未收/未付',
        'pending': '待处理'
    };
    return map[status] || status;
}

// 切换状态选项文本
function toggleStatusOptions() {
    const type = document.getElementById('form-type').value;
    const statusSelect = document.getElementById('form-status');
    const options = statusSelect.options;
    
    if (type === 'income') {
        options[0].text = '已收款';
        options[1].text = '未收款';
    } else {
        options[0].text = '已付款';
        options[1].text = '未付款';
    }
}

// 改变页码
function changePage(delta) {
    currentPage += delta;
    if (currentPage < 1) currentPage = 1;
    if (currentPage > totalPages) currentPage = totalPages;
    loadData();
}

// 改变每页显示条数
function changePageSize() {
    pageSize = parseInt(document.getElementById('page-size').value);
    currentPage = 1; // 重置到第一页
    loadData();
}

// 重置筛选
function resetFilters() {
    document.getElementById('filter-keyword').value = '';
    document.getElementById('filter-type').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-date-start').value = '';
    document.getElementById('filter-date-end').value = '';
    document.getElementById('page-size').value = pageSize;
    currentPage = 1;
    loadData();
}

// 显示创建弹窗
function showCreateModal() {
    document.getElementById('modal-title').textContent = '新增财务记录';
    document.getElementById('finance-form').reset();
    document.getElementById('form-id').value = '';
    document.getElementById('form-date').valueAsDate = new Date();
    toggleStatusOptions();
    document.getElementById('finance-modal').classList.add('active');
}

// 编辑数据
async function editData(id) {
    try {
        const response = await fetch(`../api/finance.php?action=get&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            document.getElementById('modal-title').textContent = '编辑财务记录';
            document.getElementById('form-id').value = data.id;
            document.getElementById('form-type').value = data.type;
            document.getElementById('form-amount').value = data.amount;
            document.getElementById('form-title').value = data.title;
            document.getElementById('form-ref-no').value = data.ref_no || '';
            document.getElementById('form-date').value = data.date;
            document.getElementById('form-status').value = data.status;
            document.getElementById('form-remark').value = data.remark || '';
            toggleStatusOptions();
            document.getElementById('finance-modal').classList.add('active');
        }
    } catch (error) {
        alert('加载数据失败：' + error.message);
    }
}

// 关闭弹窗
function closeModal() {
    document.getElementById('finance-modal').classList.remove('active');
}

// 保存数据
async function saveData(event) {
    event.preventDefault();
    
    const id = document.getElementById('form-id').value;
    const data = {
        type: document.getElementById('form-type').value,
        amount: parseFloat(document.getElementById('form-amount').value),
        title: document.getElementById('form-title').value,
        ref_no: document.getElementById('form-ref-no').value,
        date: document.getElementById('form-date').value,
        status: document.getElementById('form-status').value,
        remark: document.getElementById('form-remark').value
    };
    
    if (id) {
        data.id = id;
    }
    
    try {
        const response = await fetch('../api/finance.php?action=' + (id ? 'update' : 'create'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message || '保存成功');
            closeModal();
            loadData();
            loadStats();
        } else {
            alert('保存失败：' + result.message);
        }
    } catch (error) {
        alert('保存失败：' + error.message);
    }
}

// 删除数据
async function deleteData(id) {
    if (!confirm('确定要删除该财务记录吗？')) {
        return;
    }
    
    try {
        const response = await fetch('../api/finance.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('删除成功');
            loadData();
            loadStats();
        } else {
            alert('删除失败：' + result.message);
        }
    } catch (error) {
        alert('删除失败：' + error.message);
    }
}

// 页面加载时初始化
document.addEventListener('DOMContentLoaded', function() {
    // 设置初始每页条数
    document.getElementById('page-size').value = pageSize;
    loadStats();
    loadData();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php';
