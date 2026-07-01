<?php
$currentPage = 'customer';
$pageTitle = '客户管理';

// 检查访问权限
require_once dirname(__DIR__) . '/includes/PermissionChecker.php';
$permissionChecker = new PermissionChecker();
$permissionChecker->requireAccess('customer');

require_once __DIR__ . '/includes/header.php';
?>

<style>
.filter-bar {
    background: linear-gradient(135deg, #fafafa, white);
    padding: 24px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
}
.filter-row { display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end; }
.filter-item { flex: 1; min-width: 180px; }
.filter-item label { display: block; margin-bottom: 10px; color: #595959; font-size: 14px; font-weight: 600; }
.filter-item input, .filter-item select {
    width: 100%; padding: 11px 14px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; background: white; transition: all 0.3s;
}
.filter-item input:focus, .filter-item select:focus { outline: none; border-color: #52c41a; box-shadow: 0 0 0 4px rgba(82,196,26,0.1); }
.btn { padding: 11px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.12); }
.btn-primary { background: linear-gradient(135deg, #52c41a, #389e0d); color: white; }

/* 批量操作栏 */
.batch-action-bar {
    background: linear-gradient(135deg, #fff1f0, #ffe7e7);
    padding: 16px 24px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 2px solid #ffccc7;
    box-shadow: 0 4px 12px rgba(245,34,45,0.1);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.batch-action-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.batch-info {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #595959;
}

.batch-info input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.batch-actions {
    display: flex;
    gap: 10px;
}

.btn-warning {
    background: linear-gradient(135deg, #faad14, #ffc53d);
    color: white;
}

.btn-warning:hover {
    box-shadow: 0 8px 24px rgba(250,173,20,0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #f5222d, #ff4d4f);
    color: white;
}

.btn-danger:hover {
    box-shadow: 0 8px 24px rgba(245,34,45,0.4);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

.data-table-container { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #f0f0f0; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 16px; text-align: left; font-size: 14px; font-weight: 600; color: #262626; border-bottom: 2px solid #f0f0f0; background: linear-gradient(135deg, #fafafa, #f5f5f5); }
.data-table td { padding: 16px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #595959; }
.data-table tbody tr:hover { background: #f6ffed; }
.status-tag { display: inline-block; padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; }
.status-0 { background: #e6f7ff; color: #0050b3; }
.status-1 { background: #f6ffed; color: #237804; }
.status-2 { background: #f0f5ff; color: #1d39c4; }
.status-3 { background: #fff7e6; color: #d46b08; }
.action-btns { display: flex; gap: 6px; }
.action-btn { padding: 5px 12px; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; }
.action-btn-view { background: #e6f7ff; color: #0050b3; }
.action-btn-delete { background: #fff2f0; color: #cf1322; }
.action-btn:hover { transform: translateY(-1px); opacity: 0.9; }
.pagination {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 20px;
    padding: 20px 32px;
    background: linear-gradient(135deg, #fafafa, white);
    border-radius: 16px;
    margin-top: 24px;
    border: 2px solid #f0f0f0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.pagination-info {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #595959;
    font-size: 14px;
}

.pagination-info strong {
    color: #52c41a;
    font-weight: 700;
}

.page-size-selector {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: white;
    border-radius: 10px;
    border: 2px solid #f0f0f0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-size-selector:hover {
    border-color: #d6e4ff;
    box-shadow: 0 2px 8px rgba(82,196,26,0.1);
}

.page-size-selector label {
    font-weight: 600;
    color: #595959;
    font-size: 13px;
    white-space: nowrap;
}

.page-size-selector select {
    padding: 8px 28px 8px 14px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #262626;
    background: linear-gradient(135deg, #f0f5ff, #e6f7ff);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
    min-width: 70px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2352c41a' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 16px;
}

.page-size-selector select:hover {
    background: linear-gradient(135deg, #e6f7ff, #bae7ff);
}

.page-size-selector select:focus {
    background: linear-gradient(135deg, #e6f7ff, #bae7ff);
    box-shadow: 0 0 0 3px rgba(82,196,26,0.2);
}

.page-size-selector select option {
    padding: 10px 14px;
    font-weight: 600;
    background: white;
    color: #262626;
}

.pagination-info-divider {
    color: #d9d9d9;
    font-weight: 300;
    font-size: 14px;
    margin: 0 4px;
}

.pagination-btn {
    padding: 8px 16px;
    border: 2px solid #e8e8e8;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: #595959;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}

.pagination-btn:hover:not(:disabled) {
    border-color: #52c41a;
    color: #52c41a;
    background: #f6ffed;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(82,196,26,0.2);
}

.pagination-btn:disabled {
    cursor: not-allowed;
    opacity: 0.4;
    background: #f5f5f5;
}

.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.45); display: none; align-items: center; justify-content: center; z-index: 2000; }
.modal-overlay.show { display: flex; }
.modal { 
    background: white; 
    border-radius: 16px; 
    width: 90%; 
    max-width: 600px; 
    max-height: 90vh; 
    overflow-y: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.modal::-webkit-scrollbar {
    display: none;
}
.modal-header { padding: 24px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; font-size: 18px; }
.modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #8c8c8c; }
.modal-body { padding: 24px; }
.modal-footer { padding: 20px 24px; border-top: 1px solid #f0f0f0; display: flex; justify-content: flex-end; gap: 12px; background: #fafafa; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #262626; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 11px 14px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #52c41a; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
</style>

<!-- 批量操作栏 -->
<div id="batchActionBar" class="batch-action-bar" style="display: none;">
    <div class="batch-action-content">
        <div class="batch-info">
            <input type="checkbox" id="batchSelectAll" onchange="toggleSelectAll()" checked style="width: 18px; height: 18px; cursor: pointer;">
            <span>已选择 <strong id="selectedCount" style="color: #52c41a; font-size: 16px;">0</strong> 个客户</span>
        </div>
        <div class="batch-actions">
            <button class="btn btn-warning btn-sm" onclick="batchChangeStatus(1)">
                📋 批量设为办理中
            </button>
            <button class="btn btn-success btn-sm" onclick="batchChangeStatus(2)">
                ✅ 批量设为已完结
            </button>
            <button class="btn btn-danger btn-sm" onclick="batchDelete()">
                批量删除
            </button>
            <button class="btn btn-sm" onclick="clearSelection()" style="background: #f0f0f0; color: #666;">
                ❌ 取消选择
            </button>
        </div>
    </div>
</div>

<!-- 筛选栏 -->
<div class="filter-bar">
    <div class="filter-row">
        <div class="filter-item"><label>关键词</label><input type="text" id="keyword" placeholder="姓名/手机/微信"></div>
        <div class="filter-item"><label>国家</label><input type="text" id="country" placeholder="意向国家"></div>
        <div class="filter-item"><label>状态</label>
            <select id="status"><option value="">全部</option><option value="0">待办理</option><option value="1">办理中</option><option value="2">已完结</option><option value="3">已跑单</option></select>
        </div>
        <div class="filter-item" style="flex: 0 0 auto;"><label>&nbsp;</label><button class="btn btn-primary" onclick="loadCustomers(1)">🔍 搜索</button></div>
        <div class="filter-item" style="flex: 0 0 auto;"><label>&nbsp;</label><button class="btn btn-primary" onclick="window.location.href='reports.php'">➕ 新增客户</button></div>
    </div>
</div>

<!-- 数据表格 -->
<div class="data-table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 50px;">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="width: 18px; height: 18px; cursor: pointer;">
                </th>
                <th style="width: 60px;">ID</th><th>姓名</th><th>手机</th><th>微信</th><th>国家</th><th>签证类型</th><th>学历</th><th>所属销售</th><th>状态</th><th>创建时间</th><th style="width: 180px;">操作</th>
            </tr>
        </thead>
        <tbody id="customerList"><tr><td colspan="11" style="text-align: center; padding: 60px; color: #8c8c8c;">加载中...</td></tr></tbody>
    </table>
</div>

<!-- 分页 -->
<div class="pagination" id="pagination"></div>

<!-- 新增/编辑弹窗 -->
<div class="modal-overlay" id="customerModal">
    <div class="modal" style="max-width: 900px; max-height: 85vh;">
        <div class="modal-header">
            <h3 id="modalTitle">新增客户</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body" style="max-height: calc(85vh - 140px); overflow-y: auto;">
            <form id="customerForm">
                <input type="hidden" id="customerId">
                
                <!-- 第一部分：基础信息（必填） -->
                <div style="margin-bottom: 24px;">
                    <h4 style="margin: 0 0 16px 0; padding: 12px; background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%); color: white; border-radius: 8px; font-size: 15px;">📋 一、基础信息 <span style="font-size: 12px; opacity: 0.9;">（必填）</span></h4>
                    
                    <div class="form-group">
                        <label>客户名称 <span style="color: #f5222d;">*</span></label>
                        <div style="display: flex; gap: 8px; align-items: flex-start;">
                            <div style="flex: 1;">
                                <input type="text" id="customerName" required placeholder="请输入手机号/姓名/ID 查询用户" readonly style="background: #fafafa;">
                                <input type="hidden" id="userId">
                            </div>
                            <button type="button" class="btn btn-primary" onclick="showUserSelector()" style="padding: 11px 20px;">🔍 选择用户</button>
                        </div>
                        <div style="margin-top: 8px; font-size: 12px; color: #8c8c8c;">💡 必须从用户管理中选择已有用户，不能自行录入</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label>年龄 <span style="color: #f5222d;">*</span></label><input type="number" id="age" required placeholder="请输入年龄" min="18" max="100"></div>
                        <div class="form-group"><label>电话 <span style="color: #f5222d;">*</span></label><input type="text" id="phone" required placeholder="请输入联系电话"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label>学历 <span style="color: #f5222d;">*</span></label>
                            <select id="education" required>
                                <option value="">请选择</option>
                                <option value="初中">初中</option>
                                <option value="高中">高中</option>
                                <option value="中专">中专</option>
                                <option value="大专">大专</option>
                                <option value="本科">本科</option>
                                <option value="硕士">硕士</option>
                                <option value="博士">博士</option>
                            </select>
                        </div>
                        <div class="form-group"><label>邮箱 <span style="color: #f5222d;">*</span></label><input type="email" id="email" required placeholder="请输入邮箱地址"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label>微信 <span style="color: #f5222d;">*</span></label><input type="text" id="wechat" required placeholder="请输入微信号"></div>
                        <div class="form-group"><label>国家 <span style="color: #f5222d;">*</span></label><select id="country_select" required onchange="loadPositionsByCountry()"><option value="">加载中...</option></select></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label>岗位 <span style="color: #f5222d;">*</span></label><select id="position_id" required onchange="checkCountryBeforePosition()"><option value="0">请先选择国家</option></select></div>
                        <div class="form-group"><label>签证类型 <span style="color: #f5222d;">*</span></label>
                            <select id="visa_type" required>
                                <option value="">请选择</option>
                                <option value="旅游签证">旅游签证</option>
                                <option value="工作签证">工作签证</option>
                                <option value="留学签证">留学签证</option>
                                <option value="商务签证">商务签证</option>
                                <option value="探亲签证">探亲签证</option>
                                <option value="永住签证">永住签证</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label>来源 <span style="color: #f5222d;">*</span></label><select id="source" required><option value="">请选择</option><option value="线上咨询">线上咨询</option><option value="线下活动">线下活动</option><option value="客户推荐">客户推荐</option><option value="社交媒体">社交媒体</option><option value="其他">其他</option></select></div>
                        <div class="form-group"><label>销售顾问 <span style="color: #f5222d;">*</span></label><select id="sales_user_id" required><option value="0">请选择</option></select></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label>客户状态 <span style="color: #f5222d;">*</span></label>
                            <select id="status_select" required>
                                <option value="0">待办理</option>
                                <option value="1">办理中</option>
                                <option value="2">已完结</option>
                                <option value="3">已跑单</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>负责人 <span style="color: #8c8c8c; font-weight: normal;">（可选）</span></label>
                            <select id="owner_id">
                                <option value="">未分配</option>
                                <!-- 动态加载后台用户 -->
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- 第二部分：基本情况（非必填） -->
                <div style="margin-bottom: 24px;">
                    <h4 style="margin: 0 0 16px 0; padding: 12px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 8px; font-size: 15px;">二、基本情况 <span style="font-size: 12px; opacity: 0.9;">（非必填）</span></h4>
                    
                    <div class="form-row">
                        <div class="form-group"><label>婚姻状况</label>
                            <select id="marital_status">
                                <option value="">请选择</option>
                                <option value="未婚">未婚</option>
                                <option value="已婚">已婚</option>
                                <option value="离异">离异</option>
                                <option value="丧偶">丧偶</option>
                            </select>
                        </div>
                        <div class="form-group"><label>子女状况</label>
                            <select id="children_status">
                                <option value="">请选择</option>
                                <option value="无子女">无子女</option>
                                <option value="有子女">有子女</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label>工作状况</label>
                            <select id="work_status">
                                <option value="">请选择</option>
                                <option value="在职">在职</option>
                                <option value="离职">离职</option>
                                <option value="自由职业">自由职业</option>
                                <option value="学生">学生</option>
                                <option value="退休">退休</option>
                                <option value="其他">其他</option>
                            </select>
                        </div>
                        <div class="form-group"><label>籍贯</label><input type="text" id="hometown" placeholder="例如：北京朝阳"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label>流水状况</label><input type="text" id="flow_status" placeholder="例如：月流水 2 万"></div>
                        <div class="form-group"><label>社保状况</label><input type="text" id="social_security_status" placeholder="例如：已交 5 年"></div>
                    </div>
                    
                    <div class="form-group"><label>技能</label><input type="text" id="skills" placeholder="例如：日语 N1、IT 技能、厨师证等"></div>
                    
                    <div class="form-row">
                        <div class="form-group"><label>车辆</label>
                            <select id="vehicle">
                                <option value="">请选择</option>
                                <option value="无">无</option>
                                <option value="有">有</option>
                            </select>
                        </div>
                        <div class="form-group"><label>房产</label>
                            <select id="property">
                                <option value="">请选择</option>
                                <option value="无">无</option>
                                <option value="有">有</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group"><label>财务状况</label><input type="text" id="financial_status" placeholder="例如：存款 50 万，年收入 30 万"></div>
                </div>
                
                <!-- 第三部分：所需资料 -->
                <div style="margin-bottom: 24px;">
                    <h4 style="margin: 0 0 16px 0; padding: 12px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border-radius: 8px; font-size: 15px;">📎 三、所需资料 <span style="font-size: 12px; opacity: 0.9;">（点击按钮添加）</span></h4>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px;">
                        <button type="button" class="doc-btn" onclick="addDocField('无犯罪证明')">📄 无犯罪证明</button>
                        <button type="button" class="doc-btn" onclick="addDocField('工资流水')">💰 工资流水</button>
                        <button type="button" class="doc-btn" onclick="addDocField('社保证明')">🏥 社保证明</button>
                        <button type="button" class="doc-btn" onclick="addDocField('工作证明')">💼 工作证明</button>
                        <button type="button" class="doc-btn" onclick="addDocField('签证申请表')">签证申请表</button>
                        <button type="button" class="doc-btn" onclick="addDocField('身份证')">🆔 身份证</button>
                        <button type="button" class="doc-btn" onclick="addDocField('结婚证')">💍 结婚证</button>
                        <button type="button" class="doc-btn" onclick="addDocField('户口本')">📕 户口本</button>
                        <button type="button" class="doc-btn" onclick="addDocField('机动车证')">🚗 机动车证</button>
                        <button type="button" class="doc-btn" onclick="addDocField('房产证')">🏠 房产证</button>
                        <button type="button" class="doc-btn" onclick="addDocField('护照')">🛂 护照</button>
                        <button type="button" class="doc-btn" onclick="addDocField('2 寸白底照片')">📷 2 寸白底照片</button>
                        <button type="button" class="doc-btn" onclick="addDocField('其他')">📦 其他</button>
                    </div>
                    
                    <div id="docFieldsContainer" style="display: flex; flex-direction: column; gap: 12px;">
                        <!-- 动态生成的文件上传框将放在这里 -->
                    </div>
                </div>
                
                <!-- 办理进度 -->
                <div style="margin-bottom: 24px;">
                    <h4 style="margin: 0 0 16px 0; padding: 12px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; border-radius: 8px; font-size: 15px;">📈 办理进度</h4>
                    
                    <div id="progressHistory" style="margin-bottom: 16px; padding: 12px; background: #fafafa; border-radius: 8px; max-height: 150px; overflow-y: auto;">
                        <div style="color: #8c8c8c; text-align: center;">暂无进度记录</div>
                    </div>
                    
                    <div style="display: flex; gap: 12px;">
                        <input type="text" id="progressInput" placeholder="输入正在办理的项目，如：提交移民局审核资料" style="flex: 1; padding: 11px 14px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px;">
                        <button type="button" class="btn btn-primary" onclick="addProgress()" style="padding: 11px 24px;">⏱️ 更新进度</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal()">取消</button>
            <button class="btn btn-primary" onclick="saveCustomer()">保存</button>
        </div>
    </div>
</div>

<style>
.doc-btn {
    padding: 8px 16px;
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
}
.doc-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(24,144,255,0.4);
}
.doc-field {
    padding: 16px;
    background: #fafafa;
    border-radius: 10px;
    border: 2px solid #f0f0f0;
}
.doc-field-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.doc-field-title {
    font-weight: 600;
    color: #262626;
    font-size: 14px;
}
.doc-field-remove {
    background: #fff2f0;
    color: #cf1322;
    border: none;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
}
.doc-field-remove:hover {
    background: #ffccc7;
}
.doc-upload-area {
    border: 2px dashed #d9d9d9;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}
.doc-upload-area:hover {
    border-color: #1890ff;
    background: #f6ffed;
}
.doc-upload-area input[type="file"] {
    display: none;
}
.doc-preview {
    margin-top: 12px;
    padding: 8px 12px;
    background: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}
.progress-item {
    padding: 8px 12px;
    background: white;
    border-radius: 6px;
    margin-bottom: 8px;
    font-size: 13px;
    border-left: 3px solid #1890ff;
}
.progress-time {
    color: #8c8c8c;
    font-size: 12px;
    margin-right: 8px;
}
</style>

<!-- 用户选择器弹窗 -->
<!-- 用户选择器弹窗 -->
<div class="modal-overlay" id="userSelectorModal">
    <div class="modal" style="max-width: 700px;">
        <div class="modal-header">
            <h3>👤 选择用户</h3>
            <button class="modal-close" onclick="closeUserSelector()">×</button>
        </div>
        <div class="modal-body">
            <div class="filter-bar" style="margin-bottom: 16px;">
                <div class="filter-row">
                    <div class="filter-item" style="flex: 1;">
                        <label>搜索</label>
                        <input type="text" id="userSearchInput" placeholder="输入手机号、姓名或 ID 查询" onkeyup="if(event.keyCode===13) searchUsers()">
                    </div>
                    <div class="filter-item" style="flex: 0 0 auto;">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary" onclick="searchUsers()">🔍 搜索</button>
                    </div>
                </div>
            </div>
            <div class="data-table-container" style="max-height: 400px; overflow-y: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>姓名</th>
                            <th>手机号</th>
                            <th>邮箱</th>
                            <th>角色</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="userList">
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #8c8c8c;">请输入搜索条件后查询</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let pagination = {};
let pageSize = 20;
let selectedIds = [];

function loadCustomers(page = 1) {
    return new Promise((resolve, reject) => {
        clearSelection();
        
        const keyword = document.getElementById('keyword').value;
        const country = document.getElementById('country').value;
        const status = document.getElementById('status').value;
        
        let url = `../api/customer.php?action=list&page=${page}&page_size=${pageSize}`;
        if (keyword) url += `&keyword=${encodeURIComponent(keyword)}`;
        if (country) url += `&country=${encodeURIComponent(country)}`;
        if (status !== '') url += `&status=${status}`;
        
        fetch(url).then(res => res.json()).then(res => {
            if (res.code !== 200) {
                document.getElementById('customerList').innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 60px; color: #ff4d4f;">❌ 加载失败：' + res.message + '</td></tr>';
                resolve();
                return;
            }
            
            const { list, pagination: pag } = res.data;
            currentPage = pag.page;
            pagination = { 
                page: pag.page, 
                total: pag.total, 
                total_pages: pag.total_pages,
                has_prev: pag.page > 1,
                has_next: pag.page < pag.total_pages
            };
            
            if (list.length === 0) {
                document.getElementById('customerList').innerHTML = '<tr><td colspan="12" style="text-align: center; padding: 60px; color: #8c8c8c;">暂无数据</td></tr>';
            } else {
                document.getElementById('customerList').innerHTML = list.map(item => `
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" class="select-item" data-id="${item.id}" onchange="updateSelectCount()" style="width: 18px; height: 18px; cursor: pointer;">
                        </td>
                        <td><span style="font-weight: 600; color: #bfbfbf;">#${item.id}</span></td>
                        <td style="font-weight: 600; color: #262626;">${escapeHtml(item.name)}</td>
                        <td>${escapeHtml(item.phone) || '-'}</td>
                        <td>${escapeHtml(item.wechat) || '-'}</td>
                        <td>${escapeHtml(item.country) || '-'}</td>
                        <td>${escapeHtml(item.visa_type) || '-'}</td>
                        <td>${escapeHtml(item.education) || '-'}</td>
                        <td>${item.owner_name ? escapeHtml(item.owner_name) : '<span style="color: #bfbfbf;">未分配</span>'}</td>
                        <td><span class="status-tag status-${item.status || 0}">${getStatusText(item.status || 0)}</span></td>
                        <td>${item.created_at || '-'}</td>
                        <td><div class="action-btns">
                            <button class="action-btn action-btn-view" onclick="editCustomer(${item.id})">📝</button>
                            <button class="action-btn action-btn-view" onclick="viewCustomer(${item.id})">👁️</button>
                            <button class="action-btn action-btn-delete" onclick="deleteCustomer(${item.id}, '${escapeHtml(item.name)}')">🗑️</button>
                        </div></td>
                    </tr>
                `).join('');
            }
            updatePagination();
            resolve();
        }).catch(err => {
            console.error(err);
            document.getElementById('customerList').innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 60px; color: #ff4d4f;">加载失败，请刷新重试</td></tr>';
            resolve();
        });
    });
}

function updatePagination() {
    document.getElementById('pagination').innerHTML = `
        <div class="pagination-info">
            <span>共 <strong>${pagination.total}</strong> 条</span>
            <span class="pagination-info-divider">|</span>
            <span>第 <strong>${pagination.page}</strong> 页</span>
            <span class="pagination-info-divider">|</span>
            <span>共 <strong>${pagination.total_pages}</strong> 页</span>
        </div>
        <div class="page-size-selector">
            <label>每页显示</label>
            <select onchange="changePageSize(this.value)">
                <option value="20" ${pageSize === 20 ? 'selected' : ''}>20</option>
                <option value="50" ${pageSize === 50 ? 'selected' : ''}>50</option>
                <option value="100" ${pageSize === 100 ? 'selected' : ''}>100</option>
            </select>
            <label>条</label>
        </div>
        <div style="display: flex; gap: 8px;">
            <button class="pagination-btn" onclick="loadCustomers(1)" ${pagination.page === 1 ? 'disabled' : ''}>⏮ 首页</button>
            <button class="pagination-btn" onclick="loadCustomers(${pagination.page - 1})" ${!pagination.has_prev ? 'disabled' : ''}>⏯ 上一页</button>
            <button class="pagination-btn" onclick="loadCustomers(${pagination.page + 1})" ${!pagination.has_next ? 'disabled' : ''}>下一页 ⏭</button>
            <button class="pagination-btn" onclick="loadCustomers(${pagination.total_pages})" ${pagination.page === pagination.total_pages ? 'disabled' : ''}>末页 ⏭</button>
        </div>
    `;
}

function changePageSize(size) {
    pageSize = parseInt(size);
    loadCustomers(1);
}

function escapeHtml(text) {
    if (!text) return '-';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getStatusText(status) {
    const map = {
        '0': '待办理',
        '1': '办理中',
        '2': '已完结',
        '3': '已跑单'
    };
    return map[status] || '未知';
}

function showCreateModal() {
    document.getElementById('modalTitle').textContent = '新增客户';
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';
    document.getElementById('docFieldsContainer').innerHTML = '';
    document.getElementById('progressHistory').innerHTML = '<div style="color: #8c8c8c; text-align: center;">暂无进度记录</div>';
    // 加载岗位列表和销售顾问列表
    loadCountries();
    loadPositions();
    loadSalesUsers();
    document.getElementById('customerModal').classList.add('show');
}

function closeModal() {
    document.getElementById('customerModal').classList.remove('show');
}

// 用户选择器功能
function showUserSelector() {
    document.getElementById('userSelectorModal').classList.add('show');
    document.getElementById('userSearchInput').value = '';
    document.getElementById('userList').innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #8c8c8c;">请输入搜索条件后查询</td></tr>';
    document.getElementById('userSearchInput').focus();
}

function closeUserSelector() {
    document.getElementById('userSelectorModal').classList.remove('show');
}

function searchUsers() {
    const keyword = document.getElementById('userSearchInput').value.trim();
    if (!keyword) {
        alert('请输入搜索条件');
        return;
    }
    
    fetch(`../api/user.php?action=search&keyword=${encodeURIComponent(keyword)}`)
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) {
                document.getElementById('userList').innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #f5222d;">❌ ' + res.message + '</td></tr>';
                return;
            }
            
            const users = res.data;
            if (users.length === 0) {
                document.getElementById('userList').innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #8c8c8c;">未找到匹配的用户</td></tr>';
                return;
            }
            
            let html = '';
            users.forEach(user => {
                const roleName = user.role === 2 ? '管理员' : '普通用户';
                html += `
                    <tr>
                        <td>${user.id}</td>
                        <td>${escapeHtml(user.username)}</td>
                        <td>${escapeHtml(user.phone || '-')}</td>
                        <td>${escapeHtml(user.email || '-')}</td>
                        <td>${roleName}</td>
                        <td>
                            <button class="action-btn action-btn-view" onclick="selectUser(${user.id}, '${escapeHtml(user.username)}', '${escapeHtml(user.phone || '')}')">
                                ✅ 选择
                            </button>
                        </td>
                    </tr>
                `;
            });
            document.getElementById('userList').innerHTML = html;
        })
        .catch(err => {
            console.error('搜索用户失败:', err);
            document.getElementById('userList').innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #f5222d;">❌ 搜索失败</td></tr>';
        });
}

function selectUser(userId, username, phone) {
    document.getElementById('customerName').value = username;
    document.getElementById('userId').value = userId;
    document.getElementById('phone').value = phone;
    closeUserSelector();
}

function viewCustomer(id) {
    fetch(`../api/customer.php?action=detail&id=${id}`).then(res => res.json()).then(res => {
        if (res.code !== 200) {
            alert('获取失败：' + res.message);
            return;
        }
        const data = res.data;
        alert(`客户详情：\n姓名：${data.name}\n手机：${data.phone || '-'}\n微信：${data.wechat || '-'}\n国家：${data.country || '-'}\n状态：${getStatusText(data.status)}`);
    });
}

function deleteCustomer(id, name) {
    if (!confirm(`确定要删除客户 "${name}" 吗？此操作不可恢复！`)) return;
    
    const data = new FormData();
    data.append('id', id);
    
    fetch('../api/customer.php?action=delete', { method: 'POST', body: data })
    .then(res => res.json()).then(res => {
        if (res.code !== 200) {
            alert('删除失败：' + res.message);
            return;
        }
        
        // 记录操作日志
        fetch('../api/log_operation.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: '删除客户',
                detail: '客户姓名：' + name
            })
        }).catch(err => console.error('日志记录失败:', err));
        
        alert('✅ 客户已删除');
        loadCustomers(currentPage);
    }).catch(err => {
        console.error(err);
        alert('❌ 删除失败，请重试');
    });
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const batchSelectAll = document.getElementById('batchSelectAll');
    const checkboxes = document.querySelectorAll('.select-item');
    
    if (selectAll.checked !== batchSelectAll.checked) {
        batchSelectAll.checked = selectAll.checked;
    }
    
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateSelectCount();
}

function updateSelectCount() {
    const checkboxes = document.querySelectorAll('.select-item:checked');
    selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.dataset.id));
    
    const count = selectedIds.length;
    document.getElementById('selectedCount').textContent = count;
    
    const batchBar = document.getElementById('batchActionBar');
    batchBar.style.display = count > 0 ? 'block' : 'none';
    
    const selectAll = document.getElementById('selectAll');
    const batchSelectAll = document.getElementById('batchSelectAll');
    const allCheckboxes = document.querySelectorAll('.select-item');
    const allChecked = allCheckboxes.length > 0 && allCheckboxes.length === checkboxes.length;
    
    selectAll.checked = allChecked;
    batchSelectAll.checked = allChecked;
}

function clearSelection() {
    const selectAll = document.getElementById('selectAll');
    const batchSelectAll = document.getElementById('batchSelectAll');
    const checkboxes = document.querySelectorAll('.select-item');
    
    checkboxes.forEach(cb => cb.checked = false);
    selectAll.checked = false;
    batchSelectAll.checked = false;
    selectedIds = [];
    
    document.getElementById('batchActionBar').style.display = 'none';
}

function batchChangeStatus(status) {
    if (selectedIds.length === 0) {
        alert('请选择要操作的客户');
        return;
    }
    
    const statusMap = {'1': '办理中', '2': '已完结'};
    const action = statusMap[status];
    
    if (!confirm(`确定要将选中的 ${selectedIds.length} 个客户设为"${action}"吗？`)) return;
    
    const promises = selectedIds.map(id => {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', status);
        return fetch('../api/customer.php?action=update_status', {
            method: 'POST',
            body: formData
        });
    });
    
    Promise.all(promises)
        .then(() => {
            alert(`✅ 成功修改 ${selectedIds.length} 个客户的状态`);
            clearSelection();
            loadCustomers(currentPage);
        })
        .catch(err => {
            console.error('批量操作失败:', err);
            alert('❌ 批量操作失败，请重试');
        });
}

function batchDelete() {
    if (selectedIds.length === 0) {
        alert('请选择要删除的客户');
        return;
    }
    
    if (!confirm(`⚠️ 确定要删除选中的 ${selectedIds.length} 个客户吗？此操作不可恢复！`)) return;
    
    const promises = selectedIds.map(id => {
        const formData = new FormData();
        formData.append('id', id);
        return fetch('../api/customer.php?action=delete', {
            method: 'POST',
            body: formData
        });
    });
    
    Promise.all(promises)
        .then(() => {
            alert(`✅ 成功删除 ${selectedIds.length} 个客户`);
            clearSelection();
            loadCustomers(currentPage);
        })
        .catch(err => {
            console.error('批量删除失败:', err);
            alert('❌ 批量删除失败，请重试');
        });
}


  // 加载国家列表（从岗位管理动态读取）
  function loadCountries() {
      const countrySelect = document.getElementById('country_select');
      if (!countrySelect) return;
      
      fetch('../api/position.php?action=countries')
          .then(res => res.json())
          .then(res => {
              if (res.code === 200 && res.data && res.data.length > 0) {
                  countrySelect.innerHTML = '<option value="">请选择</option>' + 
                      res.data.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
              } else {
                  countrySelect.innerHTML = '<option value="">暂无国家</option>';
              }
          })
          .catch(err => {
              console.error('加载国家列表失败:', err);
              countrySelect.innerHTML = '<option value="">加载失败</option>';
          });
  }
  // 加载岗位列表（全部）
function loadPositions() {
    fetch('../api/customer.php?action=positions').then(res => res.json()).then(res => {
        if (res.code === 200) {
            const select = document.getElementById('position_id');
            select.innerHTML = '<option value="0">请选择</option>' + 
                res.data.map(p => `<option value="${p.id}">${escapeHtml(p.title)}</option>`).join('');
        }
    }).catch(err => console.error('加载岗位失败:', err));
}

// 根据国家加载对应岗位
function loadPositionsByCountry() {
    const country = document.getElementById('country_select').value;
    const positionSelect = document.getElementById('position_id');
    
    if (!country) {
        positionSelect.innerHTML = '<option value="0">请先选择国家</option>';
        return;
    }
    
    fetch('../api/customer.php?action=positions&country=' + encodeURIComponent(country)).then(res => res.json()).then(res => {
        if (res.code === 200) {
            if (res.data.length === 0) {
                positionSelect.innerHTML = '<option value="0">该国暂无岗位</option>';
            } else {
                positionSelect.innerHTML = '<option value="0">请选择</option>' + 
                    res.data.map(p => `<option value="${p.id}">${escapeHtml(p.title)}</option>`).join('');
            }
        } else {
            positionSelect.innerHTML = '<option value="0">加载失败</option>';
        }
    }).catch(err => {
        console.error('加载岗位失败:', err);
        positionSelect.innerHTML = '<option value="0">加载失败</option>';
    });
}

// 检查国家选择
function checkCountryBeforePosition() {
    const country = document.getElementById('country_select').value;
    const positionId = document.getElementById('position_id').value;
    
    if (!country && positionId != 0) {
        alert('⚠️ 请先选择国家，再选择岗位！');
        document.getElementById('position_id').value = 0;
    }
}

// 加载销售用户列表
function loadSalesUsers() {
    fetch('../api/customer.php?action=sales_users').then(res => res.json()).then(res => {
        if (res.code === 200) {
            const select = document.getElementById('sales_user_id');
            select.innerHTML = '<option value="0">请选择</option>' + 
                res.data.map(u => `<option value="${u.id}">${escapeHtml(u.nickname || u.username)}</option>`).join('');
        }
    }).catch(err => console.error('加载销售用户失败:', err));
}

// 加载销售人员列表（用于负责人选择）
function loadSalesOwners() {
    fetch('../api/customer.php?action=salesmen_list').then(res => res.json()).then(res => {
        if (res.code === 200) {
            const select = document.getElementById('owner_id');
            select.innerHTML = '<option value="">未分配</option>' + 
                res.data.map(s => `<option value="${s.id}">${escapeHtml(s.name)} - ${escapeHtml(s.phone)}</option>`).join('');
        }
    }).catch(err => console.error('加载销售人员失败:', err));
}


// ========== 文件上传和进度管理功能 ==========

// 添加资料上传框
function addDocField(docType) {
    const container = document.getElementById('docFieldsContainer');
    
    // 检查是否已存在该类型的上传框（"其他"除外）
    const existing = document.querySelector(`.doc-field[data-doc-type="${docType}"]`);
    if (existing && docType !== '其他') {
        alert('该资料类型已添加，每个类型只能上传一个文件');
        return;
    }
    
    const fieldId = 'doc_' + Date.now();
    const html = `
        <div class="doc-field" data-doc-type="${docType}" id="${fieldId}">
            <div class="doc-field-header">
                <span class="doc-field-title">${docType}</span>
                <button type="button" class="doc-field-remove" onclick="removeDocField('${fieldId}')">− 移除</button>
            </div>
            <div class="doc-upload-area" onclick="document.getElementById('${fieldId}_file').click()">
                <input type="file" id="${fieldId}_file" accept="image/*,.pdf" onchange="handleFileSelect(this, '${fieldId}')">
                <div style="font-size: 24px; margin-bottom: 8px;">📁</div>
                <div style="font-weight: 600; color: #262626;">点击上传文件</div>
                <div style="font-size: 12px; color: #8c8c8c; margin-top: 4px;">支持 JPG、PNG、GIF、PDF 格式，最大 10MB</div>
            </div>
            <div class="doc-preview-container" id="${fieldId}_preview" style="display: none;"></div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
}

// 移除资料上传框
function removeDocField(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.remove();
    }
}

// 处理文件选择
function handleFileSelect(input, fieldId) {
    const file = input.files[0];
    if (!file) return;
    
    // 验证文件大小
    if (file.size > 10 * 1024 * 1024) {
        alert('文件大小不能超过 10MB');
        input.value = '';
        return;
    }
    
    // 验证文件类型
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!validTypes.includes(file.type)) {
        alert('只支持 JPG、PNG、GIF 和 PDF 格式');
        input.value = '';
        return;
    }
    
    // 显示文件预览
    const previewContainer = document.getElementById(fieldId + '_preview');
    const fileName = file.name.length > 30 ? file.name.substring(0, 30) + '...' : file.name;
    const fileSize = (file.size / 1024 / 1024).toFixed(2) + 'MB';
    
    // 如果是图片，显示图片预览
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewContainer.innerHTML = `
                <div class="doc-preview" style="flex-direction: column; align-items: flex-start;">
                    <img src="${e.target.result}" style="max-width: 200px; max-height: 200px; border-radius: 8px; margin-bottom: 8px; object-fit: contain;">
                    <div style="display: flex; align-items: center; width: 100%;">
                        <span style="font-size: 13px; color: #262626;">📄 ${fileName} (${fileSize})</span>
                        <span style="color: #52c41a; margin-left: auto; font-size: 12px;">✅ 已选择</span>
                    </div>
                </div>
            `;
            previewContainer.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        // PDF 文件显示图标预览
        previewContainer.innerHTML = `
            <div class="doc-preview">
                <span style="font-size: 24px; margin-right: 8px;">📕</span>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #262626;">${fileName}</div>
                    <div style="font-size: 12px; color: #8c8c8c;">${fileSize} · PDF 文档</div>
                </div>
                <span style="color: #52c41a; margin-left: auto; font-size: 12px;">✅ 已选择</span>
            </div>
        `;
        previewContainer.style.display = 'block';
    }
}

// 添加进度记录
function addProgress() {
    const progressInput = document.getElementById('progressInput');
    const progressText = progressInput.value.trim();
    
    if (!progressText) {
        alert('请输入进度内容');
        return;
    }
    
    const customerId = document.getElementById('customerId').value;
    if (!customerId) {
        alert('请先保存客户信息后再添加进度');
        return;
    }
    
    // 添加到进度历史（前端显示）
    const progressHistory = document.getElementById('progressHistory');
    const now = new Date();
    const timeStr = now.getFullYear() + '-' + 
        String(now.getMonth() + 1).padStart(2, '0') + '-' + 
        String(now.getDate()).padStart(2, '0') + ' ' + 
        String(now.getHours()).padStart(2, '0') + ':' + 
        String(now.getMinutes()).padStart(2, '0') + ':' + 
        String(now.getSeconds()).padStart(2, '0');
    
    const html = `
        <div class="progress-item">
            <span class="progress-time">${timeStr}</span>
            <span>${progressText}</span>
        </div>
    `;
    
    if (progressHistory.querySelector('.progress-item')) {
        progressHistory.insertAdjacentHTML('afterbegin', html);
    } else {
        progressHistory.innerHTML = html;
    }
    
    // 保存到数据库
    const formData = new FormData();
    formData.append('customer_id', customerId);
    formData.append('progress_text', progressText);
    
    fetch('../api/customer.php?action=add_progress', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) {
                console.error('保存进度失败:', res.message);
            }
        })
        .catch(err => console.error('保存进度失败:', err));
    
    progressInput.value = '';
}

// 加载进度历史
function loadProgressHistory(customerId) {
    fetch(`../api/customer.php?action=get_progress&customer_id=${customerId}`)
        .then(res => res.json())
        .then(res => {
            if (res.code === 200 && res.data && res.data.length > 0) {
                const progressHistory = document.getElementById('progressHistory');
                progressHistory.innerHTML = res.data.map(item => `
                    <div class="progress-item">
                        <span class="progress-time">${item.created_at}</span>
                        <span>${item.progress_text}</span>
                    </div>
                `).join('');
            }
        })
        .catch(err => console.error('加载进度历史失败:', err));
}

// 加载资料列表
function loadDocuments(customerId) {
    fetch(`../api/customer.php?action=get_documents&customer_id=${customerId}`)
        .then(res => res.json())
        .then(res => {
            if (res.code === 200 && res.data && res.data.length > 0) {
                res.data.forEach(doc => {
                    addDocField(doc.doc_type);
                    // 显示已上传的文件
                    setTimeout(() => {
                        const fieldId = document.querySelector('.doc-field[data-doc-type="' + doc.doc_type + '"]')?.id;
                        if (fieldId) {
                            const previewContainer = document.getElementById(fieldId + '_preview');
                            previewContainer.innerHTML = `
                                <div class="doc-preview">
                                    <span>📄 ${doc.file_name}</span>
                                    <span style="color: #52c41a; margin-left: auto;">✅ 已上传</span>
                                </div>
                            `;
                            previewContainer.style.display = 'block';
                        }
                    }, 100);
                });
            }
        })
        .catch(err => console.error('加载资料失败:', err));
}

// 编辑客户 - 跳转到 reports.php 页面并带上 ID 参数
function editCustomer(id) {
    window.location.href = `reports2.php?id=${id}`;
}

// 更新 saveCustomer 函数以支持新字段
function saveCustomer() {
    const id = document.getElementById('customerId').value;
    const name = document.getElementById('customerName').value.trim();
    const userId = document.getElementById('userId').value.trim();
    
    if (!name) {
        alert('请选择客户（从用户管理中选择）');
        return;
    }
    
    if (!userId) {
        alert('必须从用户管理中选择已有用户，不能自行录入');
        return;
    }
    
    // 验证必填字段
    const requiredFields = [
        {id: 'age', name: '年龄'},
        {id: 'phone', name: '电话'},
        {id: 'education', name: '学历'},
        {id: 'email', name: '邮箱'},
        {id: 'wechat', name: '微信'},
        {id: 'country_select', name: '国家'},
        {id: 'position_id', name: '岗位'},
        {id: 'visa_type', name: '签证类型'},
        {id: 'source', name: '来源'},
        {id: 'sales_user_id', name: '销售顾问'}
    ];
    
    for (const field of requiredFields) {
        const el = document.getElementById(field.id);
        if (!el.value || el.value === '0') {
            alert('请填写' + field.name);
            el.focus();
            return;
        }
    }
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('name', name);
    formData.append('user_id', userId);
    formData.append('age', document.getElementById('age').value);
    formData.append('phone', document.getElementById('phone').value);
    formData.append('education', document.getElementById('education').value);
    formData.append('email', document.getElementById('email').value);
    formData.append('wechat', document.getElementById('wechat').value);
    formData.append('country', document.getElementById('country_select').value);
    formData.append('position_id', document.getElementById('position_id').value);
    formData.append('visa_type', document.getElementById('visa_type').value);
    formData.append('source', document.getElementById('source').value);
    formData.append('sales_user_id', document.getElementById('sales_user_id').value);
    formData.append('owner_id', document.getElementById('owner_id').value);
    formData.append('status', document.getElementById('status_select').value);
    
    // 基本情况
    formData.append('marital_status', document.getElementById('marital_status').value);
    formData.append('children_status', document.getElementById('children_status').value);
    formData.append('work_status', document.getElementById('work_status').value);
    formData.append('flow_status', document.getElementById('flow_status').value);
    formData.append('social_security_status', document.getElementById('social_security_status').value);
    formData.append('skills', document.getElementById('skills').value);
    formData.append('hometown', document.getElementById('hometown').value);
    formData.append('vehicle', document.getElementById('vehicle').value);
    formData.append('property', document.getElementById('property').value);
    formData.append('financial_status', document.getElementById('financial_status').value);
    
    const action = id ? 'update' : 'create';
    
    fetch(`../api/customer.php?action=${action}`, { method: 'POST', body: formData })
    .then(res => res.json()).then(res => {
        if (res.code !== 200) {
            alert('操作失败：' + res.message);
            return;
        }
        
        const customerId = id || res.data.id;
        
        // 记录操作日志
        fetch('../api/log_operation.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: id ? '更新客户' : '创建客户',
                detail: '客户姓名：' + name
            })
        }).catch(err => console.error('日志记录失败:', err));
        
        alert(id ? '✅ 更新成功' : '✅ 创建成功');
        closeModal();
        loadCustomers(currentPage);
    }).catch(err => {
        console.error(err);
        alert('❌ 操作失败，请重试');
    });
}

// 当前登录用户信息
let currentUserId = null;
let currentLoginType = null;

// 获取当前登录用户信息
function loadCurrentUserInfo() {
    return new Promise((resolve, reject) => {
        fetch('../api/auth.php?action=userinfo')
            .then(res => res.json())
            .then(res => {
                if (res.code === 200) {
                    currentUserId = res.data.id;
                    currentLoginType = res.data.login_type;
                    
                    // 如果是销售人员，自动设置 owner_id
                    if (currentLoginType === 'salesman' && currentUserId) {
                        const ownerSelect = document.getElementById('owner_id');
                        if (ownerSelect) {
                            ownerSelect.value = currentUserId;
                            ownerSelect.disabled = true;
                        }
                    }
                }
                resolve();
            })
            .catch(err => {
                console.error('获取用户信息失败:', err);
                resolve(); // 即使失败也继续
            });
    });
}

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    // 加载当前用户信息
    loadCurrentUserInfo().then(() => {
        // 加载客户列表
        return loadCustomers(1);
    }).then(() => {
        // 加载下拉选项
        return Promise.all([
            loadCountries(),
            loadPositions(),
            loadSalesUsers(),
            loadSalesOwners()
        ]);
    }).catch(err => {
        console.error('加载失败:', err);
    });
});

// 标记步骤完成
function markStepComplete(stepId) {
    const step = document.getElementById(stepId);
    if (step) {
        step.classList.add('completed');
        step.querySelector('.icon').textContent = '✅';
    }
}

// 标记步骤加载中
function markStepLoading(stepId) {
    const step = document.getElementById(stepId);
    if (step) {
        step.classList.remove('completed');
        step.querySelector('.icon').textContent = '⏳';
    }
}
</script>

<?php include __DIR__ . '/../includes/error_handler_include.php'; ?>
</body>
</html>




