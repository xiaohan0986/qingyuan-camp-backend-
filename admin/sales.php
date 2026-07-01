<?php
$currentPage = 'sales';
$pageTitle = '销售管理';
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* 页面容器 */
.sales-container {
    padding: 24px;
}

/* 统计卡片 */
.sales-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.sales-stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
}

.sales-stat-card .label {
    font-size: 14px;
    color: #8c8c8c;
    margin-bottom: 12px;
}

.sales-stat-card .value {
    font-size: 28px;
    font-weight: 700;
    color: #262626;
}

.sales-stat-card .trend {
    font-size: 12px;
    margin-top: 8px;
}

.sales-stat-card .trend.up {
    color: #52c41a;
}

.sales-stat-card .trend.down {
    color: #f5222d;
}

/* 筛选和操作栏 */
.sales-toolbar {
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

.sales-filters {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.sales-filters input,
.sales-filters select {
    padding: 8px 12px;
    border: 2px solid #f0f0f0;
    border-radius: 6px;
    font-size: 14px;
}

.sales-filters input:focus,
.sales-filters select:focus {
    border-color: #1890ff;
    outline: none;
}

.sales-actions {
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
    background: linear-gradient(135deg, #1890ff, #096dd9);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #40a9ff, #1890ff);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(24,144,255,0.3);
}

.btn-default {
    background: #f5f5f5;
    color: #595959;
}

.btn-default:hover {
    background: #e8e8e8;
}

/* 销售列表表格 */
.sales-table-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow-x: auto;
}

.sales-table {
    width: 100%;
    border-collapse: collapse;
}

.sales-table thead {
    background: #fafafa;
}

.sales-table th,
.sales-table td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

.sales-table th {
    font-weight: 600;
    color: #262626;
    font-size: 14px;
}

.sales-table td {
    font-size: 14px;
    color: #595959;
}

.sales-table tbody tr:hover {
    background: #fafafa;
}

/* 状态标签 */
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.success {
    background: #f6ffed;
    color: #52c41a;
    border: 1px solid #b7eb8f;
}

.status-badge.pending {
    background: #fff7e6;
    color: #fa8c16;
    border: 1px solid #ffd591;
}

.status-badge.failed {
    background: #fff2f0;
    color: #f5222d;
    border: 1px solid #ffccc7;
}

.status-badge.refunded {
    background: #f0f5ff;
    color: #1890ff;
    border: 1px solid #adc6ff;
}

/* 操作按钮 */
.action-btns {
    display: flex;
    gap: 8px;
}

.action-btn {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    border: none;
    background: none;
    transition: all 0.3s;
}

.action-btn.view {
    color: #1890ff;
    background: #f0f5ff;
}

.action-btn.view:hover {
    background: #1890ff;
    color: white;
}

.action-btn.edit {
    color: #52c41a;
    background: #f6ffed;
}

.action-btn.edit:hover {
    background: #52c41a;
    color: white;
}

/* 分页 */
.sales-pagination {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    margin-top: 20px;
}

.sales-pagination button {
    padding: 8px 16px;
    border: 1px solid #d9d9d9;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.sales-pagination button:hover:not(:disabled) {
    border-color: #1890ff;
    color: #1890ff;
}

.sales-pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.sales-pagination .page-info {
    font-size: 14px;
    color: #595959;
}

/* 模态框 */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.45);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal {
    background: white;
    border-radius: 12px;
    width: 600px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 24px rgba(0,0,0,0.15);
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
    color: #262626;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #8c8c8c;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.3s;
}

.modal-close:hover {
    background: #f5f5f5;
    color: #262626;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #fafafa;
    border-radius: 0 0 12px 12px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #262626;
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #f0f0f0;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #1890ff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(24,144,255,0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
</style>

<div class="sales-container">
    <!-- 统计卡片 -->
    <div class="sales-stats" id="salesStats">
        <div class="loading">加载中...</div>
    </div>

    <!-- 筛选和操作栏 -->
    <div class="sales-toolbar">
        <div class="sales-filters">
            <input type="text" id="searchKeyword" placeholder="搜索客户姓名/手机号">
            <select id="filterStatus">
                <option value="">全部状态</option>
                <option value="已成交">已成交</option>
                <option value="跟进中">跟进中</option>
                <option value="已流失">已流失</option>
                <option value="已退款">已退款</option>
            </select>
            <select id="filterSalesman">
                <option value="">全部销售</option>
            </select>
            <input type="date" id="filterStartDate">
            <span>至</span>
            <input type="date" id="filterEndDate">
            <button class="btn btn-default" onclick="loadSalesList()">筛选</button>
            <button class="btn btn-default" onclick="resetFilters()">重置</button>
        </div>
        <div class="sales-actions">
            <button class="btn btn-primary" onclick="exportSales()">导出</button>
            <button class="btn btn-primary" onclick="window.location.href='sales_edit.php'">➕ 新增销售</button>
        </div>
    </div>

    <!-- 销售列表 -->
    <div class="sales-table-container">
        <table class="sales-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>客户姓名</th>
                    <th>客户手机号</th>
                    <th>销售顾问</th>
                    <th>销售金额</th>
                    <th>产品类型</th>
                    <th>成交日期</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="salesListBody">
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px; color: #8c8c8c;">加载中...</td>
                </tr>
            </tbody>
        </table>

        <!-- 分页 -->
        <div class="sales-pagination" id="salesPagination">
            <button onclick="changePage(-1)" id="prevBtn" disabled>上一页</button>
            <span class="page-info" id="pageInfo">第 1 页 / 共 1 页</span>
            <button onclick="changePage(1)" id="nextBtn" disabled>下一页</button>
        </div>
    </div>
</div>

<!-- 新增/编辑销售模态框 -->
<div id="salesDrawer" class="page-drawer">
    <div class="drawer-mask" onclick="closeSalesDrawer()"></div>
    <div class="drawer-content">
        <div class="drawer-header">
            <h3 id="drawerTitle">销售记录</h3>
            <button class="drawer-close" onclick="closeSalesDrawer()">×</button>
        </div>
        <div class="drawer-body">
            <form id="salesForm">
                <input type="hidden" id="saleId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>客户姓名 *</label>
                        <input type="text" id="customerName" required>
                    </div>
                    <div class="form-group">
                        <label>客户手机号 *</label>
                        <input type="text" id="customerPhone" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>销售顾问 *</label>
                        <select id="salesman" required>
                            <option value="">请选择销售顾问</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>销售金额 *</label>
                        <input type="number" id="amount" step="0.01" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>产品类型 *</label>
                        <select id="productType" required>
                            <option value="">请选择产品类型</option>
                            <option value="移民咨询">移民咨询</option>
                            <option value="留学服务">留学服务</option>
                            <option value="签证代办">签证代办</option>
                            <option value="职业规划">职业规划</option>
                            <option value="其他">其他</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>状态 *</label>
                        <select id="status" required>
                            <option value="跟进中">跟进中</option>
                            <option value="已成交">已成交</option>
                            <option value="已流失">已流失</option>
                            <option value="已退款">已退款</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>成交日期</label>
                    <input type="date" id="closeDate">
                </div>

                <div class="form-group">
                    <label>备注</label>
                    <textarea id="remark" rows="3" placeholder="请输入备注信息"></textarea>
                </div>
            </form>
        </div>
        <div class="drawer-footer">
            <button class="btn btn-default" onclick="closeSalesDrawer()">取消</button>
            <button class="btn btn-primary" onclick="saveSale()">保存</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const pageSize = 20;

// 加载销售统计
function loadSalesStats() {
    fetch('../api/sales.php?action=stats')
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) {
                document.getElementById('salesStats').innerHTML = '<div style="color:#f5222d;">加载失败</div>';
                return;
            }
            
            const data = res.data;
            document.getElementById('salesStats').innerHTML = `
                <div class="sales-stat-card">
                    <div class="label">总销售额</div>
                    <div class="value">¥${(data.total_amount || 0).toLocaleString()}</div>
                    <div class="trend up">📊 累计成交 ${data.total_sales || 0} 单</div>
                </div>
                <div class="sales-stat-card">
                    <div class="label">本月销售额</div>
                    <div class="value">¥${(data.month_amount || 0).toLocaleString()}</div>
                    <div class="trend ${data.month_trend >= 0 ? 'up' : 'down'}">${data.month_trend >= 0 ? '↑' : '↓'} 环比 ${Math.abs(data.month_trend || 0)}%</div>
                </div>
                <div class="sales-stat-card">
                    <div class="label">成交客户数</div>
                    <div class="value">${data.closed_customers || 0}</div>
                    <div class="trend up">💡 转化率 ${(data.conversion_rate || 0).toFixed(1)}%</div>
                </div>
                <div class="sales-stat-card">
                    <div class="label">跟进中客户</div>
                    <div class="value">${data.pending_customers || 0}</div>
                    <div class="trend">📞 待跟进</div>
                </div>
            `;
        })
        .catch(err => {
            console.error('统计加载失败:', err);
            document.getElementById('salesStats').innerHTML = '<div style="color:#f5222d;">网络错误</div>';
        });
}

// 加载销售列表
function loadSalesList() {
    const params = new URLSearchParams({
        page: currentPage,
        page_size: pageSize
    });
    
    const keyword = document.getElementById('searchKeyword').value;
    const status = document.getElementById('filterStatus').value;
    const salesman = document.getElementById('filterSalesman').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;
    
    if (keyword) params.append('keyword', keyword);
    if (status) params.append('status', status);
    if (salesman) params.append('salesman', salesman);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    fetch(`../api/sales.php?action=list&${params}`)
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) {
                document.getElementById('salesListBody').innerHTML = '<tr><td colspan="9" style="text-align: center; color: #f5222d;">加载失败</td></tr>';
                return;
            }
            
            const data = res.data;
            if (data.list.length === 0) {
                document.getElementById('salesListBody').innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px; color: #8c8c8c;">暂无数据</td></tr>';
            } else {
                document.getElementById('salesListBody').innerHTML = data.list.map(item => `
                    <tr>
                        <td>${item.id}</td>
                        <td>${item.customer_name || '-'}</td>
                        <td>${item.customer_phone || '-'}</td>
                        <td>${item.salesman_name || '-'}</td>
                        <td>¥${(item.amount || 0).toLocaleString()}</td>
                        <td>${item.product_type || '-'}</td>
                        <td>${item.close_date || '-'}</td>
                        <td>${getStatusBadge(item.status)}</td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn view" onclick="viewSale(${item.id})">查看</button>
                                <button class="action-btn edit" onclick="window.location.href='sales_edit.php?id=${item.id}'">编辑</button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
            
            // 更新分页
            const totalPages = Math.ceil((data.total || 0) / pageSize);
            document.getElementById('pageInfo').textContent = `第 ${currentPage} 页 / 共 ${totalPages || 1} 页`;
            document.getElementById('prevBtn').disabled = currentPage <= 1;
            document.getElementById('nextBtn').disabled = currentPage >= totalPages;
        })
        .catch(err => {
            console.error('列表加载失败:', err);
            document.getElementById('salesListBody').innerHTML = '<tr><td colspan="9" style="text-align: center; color: #f5222d;">网络错误</td></tr>';
        });
}

// 获取状态标签
function getStatusBadge(status) {
    const statusMap = {
        '已成交': 'success',
        '跟进中': 'pending',
        '已流失': 'failed',
        '已退款': 'refunded'
    };
    const className = statusMap[status] || 'pending';
    return `<span class="status-badge ${className}">${status || '跟进中'}</span>`;
}

// 切换页码
function changePage(delta) {
    currentPage += delta;
    loadSalesList();
}

// 重置筛选
function resetFilters() {
    document.getElementById('searchKeyword').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterSalesman').value = '';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    currentPage = 1;
    loadSalesList();
}

// 导出销售数据
function exportSales() {
    window.open('../api/sales.php?action=export', '_blank');
}

// 打开新增模态框
function openAddModal() {
    document.getElementById('drawerTitle').textContent = '新增销售记录';
    document.getElementById('salesForm').reset();
    document.getElementById('saleId').value = '';
    loadSalesmen();
    document.getElementById('salesDrawer').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// 编辑销售记录
function editSale(id) {
    document.getElementById('drawerTitle').textContent = '编辑销售记录';
    loadSalesmen();
    
    fetch(`../api/sales.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) {
                alert('加载失败');
                return;
            }
            
            const data = res.data;
            document.getElementById('saleId').value = data.id;
            document.getElementById('customerName').value = data.customer_name || '';
            document.getElementById('customerPhone').value = data.customer_phone || '';
            document.getElementById('salesman').value = data.salesman_id || '';
            document.getElementById('amount').value = data.amount || '';
            document.getElementById('productType').value = data.product_type || '';
            document.getElementById('status').value = data.status || '跟进中';
            document.getElementById('closeDate').value = data.close_date || '';
            document.getElementById('remark').value = data.remark || '';
            
            document.getElementById('salesModal').style.display = 'flex';
        })
        .catch(err => {
            console.error('加载失败:', err);
            alert('加载失败');
        });
}

// 查看销售记录
function viewSale(id) {
    fetch(`../api/sales.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) {
                alert('加载失败');
                return;
            }
            
            const data = res.data;
            alert(`销售详情：
ID: ${data.id}
客户：${data.customer_name} (${data.customer_phone})
销售：${data.salesman_name}
金额：¥${data.amount}
产品：${data.product_type}
状态：${data.status}
日期：${data.close_date}
备注：${data.remark || '无'}`);
        })
        .catch(err => {
            console.error('加载失败:', err);
            alert('加载失败');
        });
}

// 关闭模态框
function closeSalesDrawer() {
    var d = document.getElementById('salesDrawer');
    d.classList.add('closing');
    setTimeout(function() { d.classList.remove('show', 'closing'); document.body.style.overflow = ''; }, 300);
}

// 加载销售顾问列表
function loadSalesmen() {
    fetch('../api/sales.php?action=salesmen')
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) return;
            
            const options = res.data.map(item => 
                `<option value="${item.id}">${item.nickname}</option>`
            ).join('');
            
            document.getElementById('salesman').innerHTML = '<option value="">请选择销售顾问</option>' + options;
            document.getElementById('filterSalesman').innerHTML = '<option value="">全部销售</option>' + options;
        })
        .catch(err => console.error('加载销售顾问失败:', err));
}

// 保存销售记录
function saveSale() {
    const saleId = document.getElementById('saleId').value;
    const data = {
        customer_name: document.getElementById('customerName').value,
        customer_phone: document.getElementById('customerPhone').value,
        salesman_id: document.getElementById('salesman').value,
        amount: document.getElementById('amount').value,
        product_type: document.getElementById('productType').value,
        status: document.getElementById('status').value,
        close_date: document.getElementById('closeDate').value,
        remark: document.getElementById('remark').value
    };
    
    if (!data.customer_name || !data.customer_phone || !data.salesman_id || !data.amount) {
        alert('请填写必填项');
        return;
    }
    
    const url = saleId ? '../api/sales.php?action=update' : '../api/sales.php?action=create';
    if (saleId) data.id = saleId;
    
    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            alert(saleId ? '更新成功' : '创建成功');
            closeModal();
            loadSalesList();
            loadSalesStats();
        } else {
            alert(res.message || '操作失败');
        }
    })
    .catch(err => {
        console.error('保存失败:', err);
        alert('网络错误');
    });
}

// 页面加载
loadSalesStats();
loadSalesList();
loadSalesmen();
</script>

<?php require_once __DIR__ . '/includes/footer.php';
