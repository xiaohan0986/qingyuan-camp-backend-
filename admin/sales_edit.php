<?php
$currentPage = 'sales';
$pageTitle = '销售编辑';
require_once __DIR__ . '/includes/header.php';

// 获取销售 ID（如果有则是编辑模式）
$saleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditMode = $saleId > 0;
?>

<style>
.edit-container {
    padding: 24px;
}

.edit-header {
    display: none !important; /* 屏蔽顶部按钮区域 */
    background: linear-gradient(135deg, #fafafa, white);
    padding: 24px 32px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.edit-title {
    font-size: 24px;
    font-weight: 700;
    color: #262626;
    display: flex;
    align-items: center;
    gap: 12px;
}

.edit-title .mode-tag {
    font-size: 14px;
    padding: 6px 16px;
    border-radius: 20px;
    font-weight: 600;
}

.mode-tag.new {
    background: linear-gradient(135deg, #52c41a, #73d13d);
    color: white;
}

.mode-tag.edit {
    background: linear-gradient(135deg, #1890ff, #40a9ff);
    color: white;
}

.back-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: white;
    border: 2px solid #f0f0f0;
    border-radius: 8px;
    color: #595959;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.back-btn:hover {
    border-color: #1890ff;
    color: #1890ff;
}

.edit-form {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
}

.form-section {
    margin-bottom: 32px;
}

.form-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #262626;
    margin-bottom: 20px;
    padding-left: 12px;
    border-left: 4px solid #1890ff;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.form-row.full {
    grid-template-columns: 1fr;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 10px;
    color: #262626;
    font-weight: 600;
    font-size: 14px;
}

.form-group label::before {
    content: '';
    width: 3px;
    height: 14px;
    background: #1890ff;
    display: inline-block;
    margin-right: 8px;
    vertical-align: middle;
    border-radius: 2px;
}

.form-group label.required::after {
    content: '*';
    color: #ff4d4f;
    margin-left: 4px;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 11px 14px;
    border: 2px solid #f0f0f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #1890ff;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid #f0f0f0;
    margin-top: 24px;
}

.btn {
    padding: 11px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-default {
    background: white;
    border: 2px solid #d9d9d9;
    color: #595959;
}

.btn-default:hover {
    border-color: #1890ff;
    color: #1890ff;
}

.btn-primary {
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #ff4d4f, #ff7875);
    color: white;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(255, 77, 79, 0.4);
}

/* 加载动画 */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    flex-direction: column;
    gap: 16px;
}

.loading-overlay.show {
    display: flex;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f0f0f0;
    border-top-color: #1890ff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    color: #595959;
    font-size: 14px;
    font-weight: 600;
}

/* 响应式 */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .edit-form {
        padding: 20px;
    }
}
</style>

<div class="edit-container">
    <!-- 页面标题 -->
    <div class="edit-header" style="display: flex !important;">
        <div class="edit-title">
            <?php if ($isEditMode): ?>
                <span>编辑销售记录</span>
                <span class="mode-tag edit">编辑模式</span>
            <?php else: ?>
                <span>新建销售记录</span>
                <span class="mode-tag new">新建模式</span>
            <?php endif; ?>
        </div>
        <button class="back-btn" onclick="window.location.href='sales.php'">
            ← 返回列表
        </button>
    </div>

    <!-- 编辑表单 -->
    <div class="edit-form">
        <form id="saleForm" onsubmit="return saveSale(event)">
            <input type="hidden" id="saleId" value="<?php echo $saleId; ?>">
            
            <div class="form-section">
                <div class="form-section-title">📋 客户信息</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">客户姓名</label>
                        <input type="text" id="customerName" required placeholder="请输入客户姓名">
                    </div>
                    <div class="form-group">
                        <label class="required">客户手机号</label>
                        <input type="text" id="customerPhone" required placeholder="请输入客户手机号">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="form-section-title">💼 销售信息</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">销售顾问</label>
                        <select id="salesman" required>
                            <option value="">请选择销售顾问</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">销售金额</label>
                        <input type="number" id="amount" step="0.01" required placeholder="请输入销售金额">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">产品类型</label>
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
                        <label class="required">状态</label>
                        <select id="status" required>
                            <option value="跟进中">跟进中</option>
                            <option value="已成交">已成交</option>
                            <option value="已流失">已流失</option>
                            <option value="已退款">已退款</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>成交日期</label>
                        <input type="date" id="closeDate">
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>备注</label>
                        <textarea id="remark" rows="3" placeholder="请输入备注信息"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default" onclick="window.location.href='sales.php'">
                    取消
                </button>
                <?php if ($isEditMode): ?>
                <button type="button" class="btn btn-danger" onclick="deleteSale()" style="margin-right: auto;">
                    删除
                </button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">
                    💾 保存
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 加载动画 -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
    <div class="loading-text" id="loadingText">加载中...</div>
</div>

<script>
// 页面加载时加载销售数据
<?php if ($isEditMode): ?>
document.addEventListener('DOMContentLoaded', function() {
    loadSalesmanList();
    loadSaleData(<?php echo $saleId; ?>);
});
<?php else: ?>
document.addEventListener('DOMContentLoaded', function() {
    loadSalesmanList();
});
<?php endif; ?>

// 显示加载动画
function showLoading(text) {
    document.getElementById('loadingText').textContent = text || '加载中...';
    document.getElementById('loadingOverlay').classList.add('show');
}

// 隐藏加载动画
function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('show');
}

// 加载销售顾问列表
function loadSalesmanList() {
    fetch('../api/salesman.php?action=list')
        .then(res => res.json())
        .then(res => {
            if (res.code === 200 && res.data) {
                const select = document.getElementById('salesman');
                select.innerHTML = '<option value="">请选择销售顾问</option>';
                res.data.forEach(salesman => {
                    const option = document.createElement('option');
                    option.value = salesman.name;
                    option.textContent = salesman.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(err => console.error('加载销售顾问失败:', err));
}

// 加载销售数据
function loadSaleData(id) {
    showLoading('加载销售数据...');
    
    fetch(`../api/sales.php?action=detail&id=${id}`)
        .then(res => res.json())
        .then(res => {
            hideLoading();
            
            if (res.code !== 200) {
                alert('加载失败：' + res.message);
                return;
            }
            
            const data = res.data;
            
            // 填充表单字段
            document.getElementById('saleId').value = data.id || '';
            document.getElementById('customerName').value = data.customer_name || '';
            document.getElementById('customerPhone').value = data.customer_phone || '';
            document.getElementById('salesman').value = data.salesman || '';
            document.getElementById('amount').value = data.amount || '';
            document.getElementById('productType').value = data.product_type || '';
            document.getElementById('status').value = data.status || '跟进中';
            document.getElementById('closeDate').value = data.close_date || '';
            document.getElementById('remark').value = data.remark || '';
        })
        .catch(err => {
            hideLoading();
            console.error('加载失败:', err);
            alert('网络错误：' + err.message);
        });
}

// 保存销售记录
function saveSale(event) {
    event.preventDefault();
    
    const saleId = document.getElementById('saleId').value;
    const isEditMode = saleId && saleId != '0' && saleId != '';
    
    // 验证必填字段
    const customerName = document.getElementById('customerName').value.trim();
    if (!customerName) {
        alert('请输入客户姓名');
        document.getElementById('customerName').focus();
        return false;
    }
    
    const customerPhone = document.getElementById('customerPhone').value.trim();
    if (!customerPhone) {
        alert('请输入客户手机号');
        document.getElementById('customerPhone').focus();
        return false;
    }
    
    const salesman = document.getElementById('salesman').value;
    if (!salesman) {
        alert('请选择销售顾问');
        document.getElementById('salesman').focus();
        return false;
    }
    
    const amount = document.getElementById('amount').value;
    if (!amount) {
        alert('请输入销售金额');
        document.getElementById('amount').focus();
        return false;
    }
    
    const productType = document.getElementById('productType').value;
    if (!productType) {
        alert('请选择产品类型');
        document.getElementById('productType').focus();
        return false;
    }
    
    // 构建数据对象（使用 JSON 格式）
    const data = {
        customer_name: customerName,
        customer_phone: customerPhone,
        salesman: salesman,
        amount: parseFloat(amount),
        product_type: productType,
        status: document.getElementById('status').value,
        close_date: document.getElementById('closeDate').value,
        remark: document.getElementById('remark').value.trim()
    };
    
    if (isEditMode) {
        data.id = parseInt(saleId);
    }
    
    const action = isEditMode ? 'update' : 'create';
    const actionText = isEditMode ? '更新' : '创建';
    
    showLoading('正在保存...');
    
    fetch(`../api/sales.php?action=${action}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        hideLoading();
        
        if (res.code === 200) {
            alert('✅ 保存成功');
            // 如果是新建，跳转到编辑页面
            if (!isEditMode && res.data && res.data.id) {
                window.location.href = `sales_edit.php?id=${res.data.id}`;
            }
        } else {
            alert('❌ ' + res.message);
        }
    })
    .catch(err => {
        hideLoading();
        console.error('保存失败:', err);
        alert('❌ 网络错误：' + err.message);
    });
    
    return false;
}

// 删除销售记录
function deleteSale() {
    const saleId = document.getElementById('saleId').value;
    
    if (!saleId) {
        alert('无效的销售 ID');
        return;
    }
    
    if (!confirm('⚠️ 确定要删除这条销售记录吗？此操作不可恢复！')) {
        return;
    }
    
    showLoading('正在删除...');
    
    fetch(`../api/sales.php?action=delete`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: parseInt(saleId)})
    })
    .then(res => res.json())
    .then(res => {
        hideLoading();
        
        if (res.code === 200) {
            alert('✅ 删除成功');
            window.location.href = 'sales.php';
        } else {
            alert('❌ ' + res.message);
        }
    })
    .catch(err => {
        hideLoading();
        console.error('删除失败:', err);
        alert('❌ 网络错误：' + err.message);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php';
