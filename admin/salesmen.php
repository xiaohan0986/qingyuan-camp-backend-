<?php
$currentPage = 'salesmen';
$pageTitle = '销售人员管理';

// 检查访问权限
require_once dirname(__DIR__) . '/includes/PermissionChecker.php';
$permissionChecker = new PermissionChecker();
$permissionChecker->requireAccess('salesmen');

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* 页面容器 */
.salesmen-container {
    padding: 24px;
}

/* 统计卡片 */
.salesmen-stats {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.salesmen-stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
}

.salesmen-stat-card .label {
    font-size: 14px;
    color: #8c8c8c;
    margin-bottom: 12px;
}

.salesmen-stat-card .value {
    font-size: 28px;
    font-weight: 700;
    color: #262626;
}

.salesmen-stat-card .sub-value {
    font-size: 12px;
    color: #8c8c8c;
    margin-top: 8px;
}

/* 筛选和操作栏 */
.salesmen-toolbar {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.salesmen-filters {
    display: flex;
    gap: 12px;
    flex-wrap: nowrap;
    align-items: center;
}

.salesmen-filters input,
.salesmen-filters select {
    padding: 8px 12px;
    border: 2px solid #f0f0f0;
    border-radius: 6px;
    font-size: 14px;
    white-space: nowrap;
}

.salesmen-filters input {
    width: 200px;
}

.salesmen-filters select {
    width: 120px;
}

.salesmen-filters input:focus,
.salesmen-filters select:focus {
    border-color: #1890ff;
    outline: none;
}

.salesmen-actions {
    display: flex;
    gap: 12px;
}

.btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
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
    color: #666;
}

.btn-default:hover {
    background: #e8e8e8;
}

/* 数据表格 */
.salesmen-table-wrapper {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow-x: auto;
}

.salesmen-table {
    width: 100%;
    border-collapse: collapse;
}

.salesmen-table th,
.salesmen-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

.salesmen-table th {
    background: #fafafa;
    font-weight: 600;
    color: #262626;
}

.salesmen-table tr:hover {
    background: #fafafa;
}

/* 状态标签 */
.status-tag {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.status-在职 {
    background: #e6f7ff;
    color: #1890ff;
    border: 1px solid #91d5ff;
}

.status-离职 {
    background: #f5f5f5;
    color: #8c8c8c;
    border: 1px solid #d9d9d9;
}

/* 等级标签 */
.level-tag {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.level-小白 {
    background: #f0f0f0;
    color: #666;
}

.level-初级 {
    background: #e6f7ff;
    color: #1890ff;
}

.level-中级 {
    background: #e6f7e6;
    color: #52c41a;
}

.level-高级 {
    background: #fff7e6;
    color: #fa8c16;
}

.level-金牌 {
    background: #fff0f6;
    color: #eb2f96;
}

.level-王牌 {
    background: #f9f0ff;
    color: #722ed1;
}

/* 分页 */
.salesmen-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 16px;
    margin-top: 24px;
}

.salesmen-pagination button {
    padding: 8px 16px;
    border: 1px solid #d9d9d9;
    background: white;
    border-radius: 6px;
    cursor: pointer;
}

.salesmen-pagination button:hover:not(:disabled) {
    border-color: #1890ff;
    color: #1890ff;
}

.salesmen-pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.salesmen-pagination .page-info {
    font-size: 14px;
    color: #666;
}

/* 弹窗 */
.salesmen-modal {
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

.salesmen-modal.active {
    display: flex;
}

.salesmen-modal-content {
    background: white;
    border-radius: 12px;
    padding: 32px;
    width: 600px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.salesmen-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.salesmen-modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #262626;
}

.salesmen-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #8c8c8c;
}

.salesmen-modal-close:hover {
    color: #262626;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #262626;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #f0f0f0;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #1890ff;
    outline: none;
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

/* 操作按钮 */
.action-btns {
    display: flex;
    gap: 8px;
}

.action-btn {
    padding: 4px 12px;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    background: #f0f0f0;
    color: #666;
}

.action-btn.edit {
    background: #e6f7ff;
    color: #1890ff;
}

.action-btn.delete {
    background: #fff1f0;
    color: #f5222d;
}

.action-btn.disable {
    background: #fff7e6;
    color: #fa8c16;
}

.action-btn.enable {
    background: #f6ffed;
    color: #52c41a;
}

.action-btn:hover {
    opacity: 0.8;
}
</style>

<div class="salesmen-container">
    <!-- 统计卡片 -->
    <div class="salesmen-stats">
        <div class="salesmen-stat-card">
            <div class="label">总人数</div>
            <div class="value" id="stat-total">-</div>
            <div class="sub-value">所有销售人员</div>
        </div>
        <div class="salesmen-stat-card">
            <div class="label">在职人数</div>
            <div class="value" id="stat-active">-</div>
            <div class="sub-value">当前在职</div>
        </div>
        <div class="salesmen-stat-card">
            <div class="label">离职人数</div>
            <div class="value" id="stat-inactive">-</div>
            <div class="sub-value">已离职</div>
        </div>
        <div class="salesmen-stat-card">
            <div class="label">总销售额</div>
            <div class="value" id="stat-sales">-</div>
            <div class="sub-value">累计业绩</div>
        </div>
        <div class="salesmen-stat-card">
            <div class="label">总成交量</div>
            <div class="value" id="stat-deals">-</div>
            <div class="sub-value">成交单数</div>
        </div>
    </div>

    <!-- 筛选和操作栏 -->
    <div class="salesmen-toolbar">
        <div class="salesmen-filters">
            <input type="text" id="filter-keyword" placeholder="搜索姓名/手机号/微信号">
            <select id="filter-level">
                <option value="">全部等级</option>
                <option value="小白">小白</option>
                <option value="初级">初级</option>
                <option value="中级">中级</option>
                <option value="高级">高级</option>
                <option value="金牌">金牌</option>
                <option value="王牌">王牌</option>
            </select>
            <select id="filter-status">
                <option value="">全部状态</option>
                <option value="在职">在职</option>
                <option value="离职">离职</option>
            </select>
            <button class="btn btn-default" onclick="loadData()">筛选</button>
            <button class="btn btn-default" onclick="resetFilters()">重置</button>
        </div>
        <div class="salesmen-actions">
            <button class="btn btn-default" id="batch-actions" style="display: none;" onclick="toggleBatchActions()">
                批量操作 📦
            </button>
            <button class="btn btn-primary" onclick="window.location.href='salesmen_edit.php'">➕ 新增销售</button>
        </div>
    </div>

    <!-- 批量操作栏 -->
    <div class="salesmen-toolbar" id="batch-toolbar" style="display: none; background: #fff7e6; border: 2px solid #faad14;">
        <div class="salesmen-filters">
            <span style="font-weight: 600; color: #fa8c16;">已选择 <span id="selected-count">0</span> 项</span>
            <select id="batch-action-type">
                <option value="status">批量修改状态</option>
                <option value="level">批量修改等级</option>
                <option value="delete">批量删除</option>
            </select>
            <select id="batch-status" style="display: none;">
                <option value="在职">在职</option>
                <option value="离职">离职</option>
            </select>
            <select id="batch-level" style="display: none;">
                <option value="小白">小白</option>
                <option value="初级">初级</option>
                <option value="中级">中级</option>
                <option value="高级">高级</option>
                <option value="金牌">金牌</option>
                <option value="王牌">王牌</option>
            </select>
            <button class="btn btn-primary" onclick="executeBatchAction()">执行</button>
            <button class="btn btn-default" onclick="cancelBatchAction()">取消</button>
        </div>
    </div>

    <!-- 数据表格 -->
    <div class="salesmen-table-wrapper">
        <table class="salesmen-table">
            <thead>
                <tr>
                    <th style="width: 50px;"><input type="checkbox" id="select-all" onchange="toggleSelectAll()"></th>
                    <th>头像</th>
                    <th>ID</th>
                    <th>姓名</th>
                    <th>手机号</th>
                    <th>微信号</th>
                    <th>等级</th>
                    <th>所属门店</th>
                    <th>销售额</th>
                    <th>成交量</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="salesmen-list">
                <tr>
                    <td colspan="10" style="text-align: center; padding: 40px;">加载中...</td>
                </tr>
            </tbody>
        </table>

        <!-- 分页 -->
        <div class="salesmen-pagination">
            <button id="prev-btn" onclick="changePage(-1)" disabled>上一页</button>
            <span class="page-info">第 <span id="current-page">1</span> 页</span>
            <button id="next-btn" onclick="changePage(1)">下一页</button>
        </div>
    </div>
</div>

<!-- 编辑/创建弹窗 -->
<div class="salesmen-modal" id="salesmen-modal">
    <div class="salesmen-modal-content">
        <div class="salesmen-modal-header">
            <h2 id="modal-title">新增销售人员</h2>
            <button class="salesmen-modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="salesmen-form" onsubmit="saveData(event)">
            <input type="hidden" id="form-id">
            <div class="form-row">
                <div class="form-group">
                    <label>姓名 *</label>
                    <input type="text" id="form-name" required>
                </div>
                <div class="form-group">
                    <label>手机号 *</label>
                    <input type="text" id="form-phone" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>登录密码 <span id="password-required" style="color: red;">*</span></label>
                    <input type="password" id="form-password" placeholder="请输入密码">
                    <div id="password-status" style="font-size: 12px; color: #999; margin-top: 4px;">
                        💡 创建时必填，编辑时留空表示不修改
                    </div>
                    <div id="password-reset-tip" style="font-size: 12px; color: #1890ff; margin-top: 4px; display: none;">
                        🔑 忘记密码？<a href="javascript:void(0)" onclick="resetPassword()" style="color: #1890ff; text-decoration: underline;">点击重置密码</a>
                    </div>
                </div>
                <div class="form-group">
                    <label>所属角色 <span style="color: red;">*</span></label>
                    <select id="form-role-id" required>
                        <option value="">请选择角色</option>
                        <!-- 动态加载 -->
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>邮箱</label>
                    <input type="email" id="form-email">
                </div>
                <div class="form-group">
                    <label>微信号</label>
                    <input type="text" id="form-wechat">
                </div>
            </div>
            <div class="form-group">
                <label>头像 URL</label>
                <input type="text" id="form-avatar" placeholder="https://example.com/avatar.jpg">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>等级</label>
                    <select id="form-level">
                        <option value="小白">小白</option>
                        <option value="初级">初级</option>
                        <option value="中级">中级</option>
                        <option value="高级">高级</option>
                        <option value="金牌">金牌</option>
                        <option value="王牌">王牌</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>状态</label>
                    <select id="form-status">
                        <option value="在职">在职</option>
                        <option value="离职">离职</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>入职日期</label>
                    <input type="date" id="form-entry-date">
                </div>
                <div class="form-group">
                    <label>所属门店</label>
                    <select id="form-store">
                        <option value="">请选择门店</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>销售额</label>
                    <input type="number" id="form-sales-amount" step="0.01" value="0">
                </div>
                <div class="form-group">
                    <label>成交量</label>
                    <input type="number" id="form-deal-count" value="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>最后成交日期</label>
                    <input type="date" id="form-last-deal-date">
                </div>
                <div class="form-group">
                    <label>排序</label>
                    <input type="number" id="form-sort-order" value="0">
                </div>
            </div>
            <div class="form-group">
                <label>备注</label>
                <textarea id="form-remark"></textarea>
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

// 加载统计数据
async function loadStats() {
    try {
        const response = await fetch('../api/salesmen.php?action=stats');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            document.getElementById('stat-total').textContent = data.total_count || 0;
            document.getElementById('stat-active').textContent = data.active_count || 0;
            document.getElementById('stat-inactive').textContent = data.inactive_count || 0;
            document.getElementById('stat-sales').textContent = '¥' + (data.total_sales || 0).toLocaleString('zh-CN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('stat-deals').textContent = data.total_deals || 0;
        }
    } catch (error) {
        console.error('加载统计数据失败:', error);
    }
}

// 加载门店列表
async function loadStores() {
    try {
        const response = await fetch('../api/salesmen.php?action=stores');
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('form-store');
            select.innerHTML = '<option value="">请选择门店</option>';
            result.data.forEach(store => {
                const option = document.createElement('option');
                option.value = store.id;
                option.textContent = store.name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('加载门店列表失败:', error);
    }
}

// 加载角色列表
function loadRoles() {
    fetch('../api/role.php?action=list')
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) {
                const select = document.getElementById('form-role-id');
                select.innerHTML = '<option value="">请选择角色</option>' + 
                    res.data.list.map(role => 
                        `<option value="${role.id}">${role.role_name}</option>`
                    ).join('');
            }
        })
        .catch(err => console.error('加载角色失败:', err));
}

async function loadData() {
    const keyword = document.getElementById('filter-keyword').value;
    const level = document.getElementById('filter-level').value;
    const status = document.getElementById('filter-status').value;
    
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        page_size: 20,
        keyword: keyword,
        level: level,
        status: status
    });
    
    try {
        const response = await fetch('../api/salesmen.php?' + params.toString());
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('salesmen-list');
            
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="12" style="text-align: center; padding: 40px;">暂无数据</td></tr>';
            } else {
                tbody.innerHTML = result.data.map(item => `
                    <tr>
                        <td><input type="checkbox" class="row-checkbox" value="${item.id}" onchange="updateSelectedCount()"></td>
                        <td>
                            <div style="width:50px;height:50px;border-radius:6px;overflow:hidden;background:#fafafa;border:2px solid #f0f0f0;">
                                ${item.avatar ? 
                                    `<img src="${item.avatar}" alt="${item.name}" style="width:100%;height:100%;object-fit:cover;">` : 
                                    '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:20px;color:#d9d9d9;">👤</div>'
                                }
                            </div>
                        </td>
                        <td>${item.id}</td>
                        <td>${item.name}</td>
                        <td>${item.phone}</td>
                        <td>${item.wechat || '-'}</td>
                        <td><span class="level-tag level-${item.level}">${item.level}</span></td>
                        <td>${item.store_name || '-'}</td>
                        <td>¥${parseFloat(item.sales_amount || 0).toLocaleString('zh-CN', {minimumFractionDigits: 2})}</td>
                        <td>${item.deal_count || 0}</td>
                        <td><span class="status-tag status-${item.status}">${item.status}</span></td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn edit" onclick="window.location.href='salesmen_edit.php?id=${item.id}'">编辑</button>
                                <button class="action-btn ${item.status === '在职' ? 'disable' : 'enable'}" onclick="toggleStatus(${item.id}, '${item.status}')">${item.status === '在职' ? '🚫 禁用' : '✅ 启用'}</button>
                                <button class="action-btn delete" onclick="deleteData(${item.id})">删除</button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
            
            document.getElementById('current-page').textContent = result.page;
            currentPage = result.page;
            totalPages = Math.ceil(result.total / result.page_size);
            
            document.getElementById('prev-btn').disabled = currentPage <= 1;
            document.getElementById('next-btn').disabled = currentPage >= totalPages;
        }
    } catch (error) {
        console.error('加载数据失败:', error);
        document.getElementById('salesmen-list').innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 40px; color: red;">加载失败</td></tr>';
    }
}

// 改变页码
function changePage(delta) {
    currentPage += delta;
    if (currentPage < 1) currentPage = 1;
    if (currentPage > totalPages) currentPage = totalPages;
    loadData();
}

// 重置筛选
function resetFilters() {
    document.getElementById('filter-keyword').value = '';
    document.getElementById('filter-level').value = '';
    document.getElementById('filter-status').value = '';
    currentPage = 1;
    loadData();
}

// 显示创建弹窗
function showCreateModal() {
    document.getElementById('modal-title').textContent = '新增销售人员';
    document.getElementById('salesmen-form').reset();
    document.getElementById('form-id').value = '';
    // 重置密码状态提示
    document.getElementById('password-status').innerHTML = '💡 创建时必填，编辑时留空表示不修改';
    document.getElementById('salesmen-modal').classList.add('active');
}

// 编辑数据
async function editData(id) {
    try {
        const response = await fetch(`../api/salesmen.php?action=get&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            document.getElementById('modal-title').textContent = '编辑销售人员';
            document.getElementById('form-id').value = data.id;
            document.getElementById('form-name').value = data.name;
            document.getElementById('form-phone').value = data.phone;
            document.getElementById('form-email').value = data.email || '';
            document.getElementById('form-avatar').value = data.avatar || '';
            document.getElementById('form-wechat').value = data.wechat || '';
            document.getElementById('form-level').value = data.level;
            document.getElementById('form-status').value = data.status;
            document.getElementById('form-entry-date').value = data.entry_date || '';
            document.getElementById('form-store').value = data.store_id || '';
            document.getElementById('form-sales-amount').value = data.sales_amount || 0;
            document.getElementById('form-deal-count').value = data.deal_count || 0;
            document.getElementById('form-last-deal-date').value = data.last_deal_date || '';
            document.getElementById('form-sort-order').value = data.sort_order || 0;
            document.getElementById('form-remark').value = data.remark || '';
            document.getElementById('form-password').value = ''; // 密码清空
            
            // 显示密码状态
            const passwordStatusEl = document.getElementById('password-status');
            const passwordResetTip = document.getElementById('password-reset-tip');
            
            if (data.password_plain) {
                // 已有密码，显示明文
                passwordStatusEl.innerHTML = `✅ 当前密码：<code style="background: #f5f5f5; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #1890ff; font-size: 14px;">${data.password_plain}</code> <span style="color: #999; margin-left: 8px;">（留空表示不修改）</span>`;
                passwordResetTip.style.display = 'block'; // 显示重置提示
            } else if (data.password) {
                // 旧数据，显示加密的前几位
                const pwdPreview = data.password.substring(0, 10) + '...';
                passwordStatusEl.innerHTML = `✅ 当前密码：<code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">${pwdPreview}</code>（留空表示不修改）`;
                passwordResetTip.style.display = 'block';
            } else {
                passwordStatusEl.innerHTML = `⚠️ <span style="color: #faad14;">暂未设置密码</span>（创建时必须设置）`;
                passwordResetTip.style.display = 'none';
            }
            
            document.getElementById('form-role-id').value = data.role_id || ''; // 设置角色
            document.getElementById('salesmen-modal').classList.add('active');
        }
    } catch (error) {
        alert('加载数据失败：' + error.message);
    }
}

// 关闭弹窗
function closeModal() {
    document.getElementById('salesmen-modal').classList.remove('active');
}

// 重置密码
function resetPassword() {
    const newPassword = prompt('请输入新密码：');
    if (!newPassword) {
        return;
    }
    
    const confirmPassword = prompt('请再次输入新密码确认：');
    if (newPassword !== confirmPassword) {
        alert('两次输入的密码不一致！');
        return;
    }
    
    if (newPassword.length < 6) {
        alert('密码长度至少 6 位！');
        return;
    }
    
    // 填充到密码框
    document.getElementById('form-password').value = newPassword;
    document.getElementById('password-status').innerHTML = '✅ 已设置新密码（保存后生效）';
    document.getElementById('password-reset-tip').style.display = 'none';
    
    alert('密码已设置，请点击"保存"按钮保存更改');
}

// 保存数据
async function saveData(event) {
    event.preventDefault();
    
    const id = document.getElementById('form-id').value;
    const password = document.getElementById('form-password').value;
    const roleId = document.getElementById('form-role-id').value;
    
    const data = {
        name: document.getElementById('form-name').value,
        avatar: document.getElementById('form-avatar').value,
        phone: document.getElementById('form-phone').value,
        email: document.getElementById('form-email').value,
        wechat: document.getElementById('form-wechat').value,
        level: document.getElementById('form-level').value,
        store_id: document.getElementById('form-store').value || null,
        store_name: document.getElementById('form-store').options[document.getElementById('form-store').selectedIndex]?.text || '',
        entry_date: document.getElementById('form-entry-date').value || null,
        sales_amount: parseFloat(document.getElementById('form-sales-amount').value) || 0,
        deal_count: parseInt(document.getElementById('form-deal-count').value) || 0,
        last_deal_date: document.getElementById('form-last-deal-date').value || null,
        status: document.getElementById('form-status').value,
        sort_order: parseInt(document.getElementById('form-sort-order').value) || 0,
        remark: document.getElementById('form-remark').value,
        role_id: roleId ? parseInt(roleId) : null
    };
    
    // 只有密码不为空时才发送（编辑时不强制修改密码）
    if (password) {
        data.password = password;
    }
    
    if (id) {
        data.id = id;
    }
    
    try {
        const response = await fetch('../api/salesmen.php?action=' + (id ? 'update' : 'create'), {
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
    if (!confirm('确定要删除该销售人员吗？')) {
        return;
    }
    
    try {
        const response = await fetch('../api/salesmen.php?action=delete', {
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

// 切换销售人员状态（禁用/启用）
async function toggleStatus(id, currentStatus) {
    const newStatus = currentStatus === '在职' ? '离职' : '在职';
    const actionText = newStatus === '在职' ? '启用' : '禁用';
    
    if (!confirm(`确定要${actionText}该销售人员吗？\n\n状态：${currentStatus} → ${newStatus}`)) {
        return;
    }
    
    try {
        const response = await fetch('../api/salesmen.php?action=toggle_status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                status: newStatus
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`${actionText}成功`);
            loadData();
            loadStats();
        } else {
            alert(`${actionText}失败：` + result.message);
        }
    } catch (error) {
        alert(`${actionText}失败：` + error.message);
    }
}

// 全选/取消全选
function toggleSelectAll() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateSelectedCount();
}

// 更新选中数量
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selected-count').textContent = count;
    
    const batchActions = document.getElementById('batch-actions');
    if (count > 0) {
        batchActions.style.display = 'inline-block';
    } else {
        batchActions.style.display = 'none';
        cancelBatchAction();
    }
}

// 切换批量操作栏
function toggleBatchActions() {
    const batchToolbar = document.getElementById('batch-toolbar');
    if (batchToolbar.style.display === 'none') {
        batchToolbar.style.display = 'flex';
    } else {
        batchToolbar.style.display = 'none';
    }
}

// 取消批量操作
function cancelBatchAction() {
    document.getElementById('batch-toolbar').style.display = 'none';
    document.getElementById('select-all').checked = false;
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
    updateSelectedCount();
}

// 批量操作类型切换
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('batch-action-type').addEventListener('change', function() {
        const value = this.value;
        document.getElementById('batch-status').style.display = value === 'status' ? 'inline-block' : 'none';
        document.getElementById('batch-level').style.display = value === 'level' ? 'inline-block' : 'none';
    });
});

// 执行批量操作
async function executeBatchAction() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('请至少选择一个销售人员');
        return;
    }
    
    const ids = Array.from(checkboxes).map(cb => parseInt(cb.value));
    const actionType = document.getElementById('batch-action-type').value;
    
    if (actionType === 'delete') {
        if (!confirm(`确定要批量删除选中的 ${ids.length} 个销售人员吗？此操作不可恢复！`)) {
            return;
        }
        
        try {
            const response = await fetch('../api/salesmen.php?action=batch_delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ids: ids })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`成功删除 ${result.deleted_count || ids.length} 条记录`);
                cancelBatchAction();
                loadData();
                loadStats();
            } else {
                alert('批量删除失败：' + result.message);
            }
        } catch (error) {
            alert('批量删除失败：' + error.message);
        }
    } else if (actionType === 'status') {
        const newStatus = document.getElementById('batch-status').value;
        
        try {
            const response = await fetch('../api/salesmen.php?action=batch_update_status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ids: ids, status: newStatus })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`成功更新 ${result.updated_count || ids.length} 条记录的状态为"${newStatus}"`);
                cancelBatchAction();
                loadData();
                loadStats();
            } else {
                alert('批量更新状态失败：' + result.message);
            }
        } catch (error) {
            alert('批量更新状态失败：' + error.message);
        }
    } else if (actionType === 'level') {
        const newLevel = document.getElementById('batch-level').value;
        
        try {
            const response = await fetch('../api/salesmen.php?action=batch_update_level', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ids: ids, level: newLevel })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`成功更新 ${result.updated_count || ids.length} 条记录的等级为"${newLevel}"`);
                cancelBatchAction();
                loadData();
                loadStats();
            } else {
                alert('批量更新等级失败：' + result.message);
            }
        } catch (error) {
            alert('批量更新等级失败：' + error.message);
        }
    }
}

// 页面加载时初始化
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadStores();
    loadData();
    loadRoles();
});

</script>

<?php require_once __DIR__ . '/includes/footer.php';
