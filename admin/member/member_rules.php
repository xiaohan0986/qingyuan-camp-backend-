<?php
/**
 * 会员规则管理页面
 */
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL); ini_set("display_errors", 0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Auth::check();

try {
    $db = Database::getInstance();
    $admin = Auth::user();
} catch (Exception $e) {
    echo "<pre>错误：" . $e->getMessage() . "</pre>";
    die();
}

$pageTitle = '用户规则';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

// 搜索参数
$level = $_GET['level'] ?? '';
$status = $_GET['status'] ?? '';

// 构建查询条件
$where = ['1=1'];
$params = [];

if ($level !== '') {
    $where[] = 'level_id = ?';
    $params[] = $level;
}

if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}

$whereStr = implode(' AND ', $where);

try {
    $totalResult = $db->fetchOne("SELECT COUNT(*) as count FROM member_rules WHERE {$whereStr}", $params);
    $total = $totalResult['count'] ?? 0;
    $totalPages = ceil($total / $pageSize);
    $rules = $db->fetchAll("SELECT * FROM member_rules WHERE {$whereStr} ORDER BY level_id ASC LIMIT {$pageSize} OFFSET {$offset}", $params);
} catch (Exception $e) {
    echo "<pre>查询失败：" . $e->getMessage() . "</pre>";
    die();
}

$levelNames = ['', '普通会员', '银卡会员', '金卡会员', '钻石会员'];
$levelColors = ['', '#8c8c8c', '#d9d9d9', '#faad14', '#722ed1'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> - 青园营地管理后台</title>
    <link rel="stylesheet" href="../assets/css/admin.min.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.min.css?v=<?= time() ?>">
    <style>
        .content-wrapper { padding: 24px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .toolbar-left { display: flex; gap: 12px; align-items: center; }
        .toolbar-title { font-size: 16px; font-weight: 600; color: #262626; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #1890ff; color: white; }
        .btn-primary:hover { background: #40a9ff; }
        .btn-default { background: #f5f5f5; color: #666; border: 1px solid #d9d9d9; }
        .btn-default:hover { background: #d9d9d9; }
        .btn-success { background: #52c41a; color: white; }
        .btn-success:hover { background: #73d13d; }
        .search-bar { background: white; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .search-form { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .form-item { display: flex; flex-direction: column; gap: 6px; }
        .form-item label { font-size: 13px; color: #666; }
        .form-item input, .form-item select { padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 13px; }
        .form-actions { grid-column: span 4; display: flex; gap: 8px; margin-top: 8px; }
        .rule-list { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .table-container { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1100px; }
        th, td { padding: 16px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; }
        tr:hover { background: #fafafa; }
        .level-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; color: white; }
        .condition-text { font-size: 13px; color: #595959; text-align: left; }
        .benefits-list { font-size: 12px; color: #8c8c8c; text-align: left; }
        .benefits-list div { margin: 4px 0; }
        .status-switch { position: relative; display: inline-block; width: 44px; height: 22px; }
        .status-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .3s; border-radius: 22px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 2px; bottom: 2px; background-color: white; transition: .3s; border-radius: 50%; }
        input:checked + .slider { background-color: #52c41a; }
        input:checked + .slider:before { transform: translateX(22px); }
        .action-btns { display: flex; gap: 8px; justify-content: center; }
        .action-btn { padding: 4px 10px; border-radius: 4px; font-size: 13px; cursor: pointer; text-decoration: none; }
        .action-btn.primary { background: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; }
        .action-btn.primary:hover { background: #1890ff; color: white; }
        .action-btn.default { background: #f5f5f5; color: #666; border: 1px solid #d9d9d9; }
        .action-btn.default:hover { background: #666; color: white; }
        .pagination { display: flex; justify-content: center; align-items: center; padding: 20px; gap: 8px; }
        .pagination a, .pagination span { padding: 8px 14px; border: 1px solid #d9d9d9; border-radius: 6px; text-decoration: none; color: #262626; font-size: 14px; }
        .pagination a:hover, .pagination .active { background: #1890ff; color: white; border-color: #1890ff; }
        .pagination .active { font-weight: 600; }
        
        /* 弹窗样式 */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.45); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.show { display: flex; }
        .modal { background: white; border-radius: 12px; width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-size: 16px; font-weight: 600; color: #262626; }
        .modal-close { cursor: pointer; font-size: 20px; color: #8c8c8c; }
        .modal-close:hover { color: #262626; }
        .modal-body { padding: 24px; }
        .modal-footer { padding: 16px 24px; border-top: 1px solid #f0f0f0; display: flex; justify-content: flex-end; gap: 12px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; color: #262626; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 13px; box-sizing: border-box; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-tip { font-size: 12px; color: #8c8c8c; margin-top: 6px; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        <div class="content-wrapper">
            <!-- 工具栏（隐藏，按钮已移到搜索栏） -->
            
            <!-- 搜索组件 -->
            <div class="search-bar">
                <form class="search-form" method="get">
                    <div class="form-item">
                        <label>会员等级</label>
                        <select name="level">
                            <option value="">全部</option>
                            <option value="1" <?= $level === '1' ? 'selected' : '' ?>>普通会员</option>
                            <option value="2" <?= $level === '2' ? 'selected' : '' ?>>银卡会员</option>
                            <option value="3" <?= $level === '3' ? 'selected' : '' ?>>金卡会员</option>
                            <option value="4" <?= $level === '4' ? 'selected' : '' ?>>钻石会员</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>状态</label>
                        <select name="status">
                            <option value="">全部</option>
                            <option value="1" <?= $status === '1' ? 'selected' : '' ?>>启用</option>
                            <option value="0" <?= $status === '0' ? 'selected' : '' ?>>禁用</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">🔍 搜索</button>
                        <button type="button" class="btn btn-default" onclick="resetSearch()">🔄 重置</button>
                        <button class="btn btn-default" onclick="showRuleDeclaration()">📋 规则声明</button>
                        <button class="btn btn-default" onclick="showContract()">📄 合约条款</button>
                        <button class="btn btn-primary" onclick="showAddModal()">➕ 新增规则</button>
                    </div>
                </form>
            </div>
            
            <!-- 规则列表 -->
            <div class="rule-list">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="80">等级 ID</th>
                                <th width="150">等级名称</th>
                                <th width="280">升级条件</th>
                                <th width="300">等级权益</th>
                                <th width="100">状态</th>
                                <th width="120">添加时间</th>
                                <th width="180">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rules)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #8c8c8c;">
                                    <div style="font-size: 64px; margin-bottom: 16px;">📋</div>
                                    暂无规则数据
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($rules as $rule): ?>
                                <tr>
                                    <td><strong><?= $rule['level_id'] ?></strong></td>
                                    <td>
                                        <span class="level-badge" style="background: <?= $levelColors[$rule['level_id']] ?>;"><?= $rule['level_name'] ?></span>
                                    </td>
                                    <td>
                                        <div class="condition-text">
                                            <?php
                                            $conditions = json_decode($rule['upgrade_condition'], true);
                                            if ($conditions):
                                            ?>
                                                <div>💰 消费金额：¥<?= number_format($conditions['amount'] ?? 0) ?></div>
                                                <div>🎁 积分：<?= number_format($conditions['points'] ?? 0) ?></div>
                                            <?php else: ?>
                                                无
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="benefits-list">
                                            <?php
                                            $benefits = json_decode($rule['benefits'], true);
                                            if ($benefits):
                                            ?>
                                                <div>🏷️ 折扣：<?= $benefits['discount'] ?? '-' ?></div>
                                                <div>📦 包邮：<?= $benefits['shipping'] ?? '-' ?></div>
                                                <div>💁 服务：<?= $benefits['service'] ?? '-' ?></div>
                                            <?php else: ?>
                                                无
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <label class="status-switch">
                                            <input type="checkbox" <?= $rule['status'] === 1 ? 'checked' : '' ?> onchange="toggleStatus(<?= $rule['id'] ?>, this.checked ? 1 : 0)">
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <span style="font-size: 13px; color: #8c8c8c;"><?= date('Y-m-d', strtotime($rule['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="action-btn.primary" onclick="editRule(<?= $rule['id'] ?>)">编辑</button>
                                            <button class="action-btn.default" onclick="deleteRule(<?= $rule['id'] ?>)">删除</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">首页</a>
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">上一页</a>
                        <?php endif; ?>
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">下一页</a>
                        <?php endif; ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">末页</a>
                        <span style="margin-left: 12px; color: #8c8c8c;">共 <?= $total ?> 条</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 新增/编辑规则弹窗 -->
    <div class="modal-overlay" id="ruleModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">新增会员规则</div>
                <div class="modal-close" onclick="closeModal()">×</div>
            </div>
            <div class="modal-body">
                <form id="ruleForm">
                    <input type="hidden" id="ruleId" value="">
                    <div class="form-group">
                        <label>等级 ID</label>
                        <select id="levelId" required>
                            <option value="1">普通会员</option>
                            <option value="2">银卡会员</option>
                            <option value="3">金卡会员</option>
                            <option value="4">钻石会员</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>等级名称</label>
                        <input type="text" id="levelName" required placeholder="如：普通会员">
                    </div>
                    <div class="form-group">
                        <label>升级条件 - 消费金额（元）</label>
                        <input type="number" id="upgradeAmount" value="0" min="0">
                        <div class="form-tip">累计消费金额达到此数值可升级</div>
                    </div>
                    <div class="form-group">
                        <label>升级条件 - 积分</label>
                        <input type="number" id="upgradePoints" value="0" min="0">
                        <div class="form-tip">累计积分达到此数值可升级</div>
                    </div>
                    <div class="form-group">
                        <label>等级权益 - 折扣</label>
                        <input type="text" id="benefitDiscount" placeholder="如：9.5 折" value="9.8 折">
                    </div>
                    <div class="form-group">
                        <label>等级权益 - 包邮政策</label>
                        <input type="text" id="benefitShipping" placeholder="如：满 99 包邮" value="满 99 包邮">
                    </div>
                    <div class="form-group">
                        <label>等级权益 - 服务</label>
                        <textarea id="benefitService" placeholder="如：7 天无理由退换货">7 天无理由退换货</textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeModal()">取消</button>
                <button class="btn btn-primary" onclick="saveRule()">💾 保存</button>
            </div>
        </div>
    </div>
    
    <!-- 规则声明弹窗 -->
    <div class="modal-overlay" id="declarationModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">📋 会员规则声明</div>
                <div class="modal-close" onclick="closeDeclarationModal()">×</div>
            </div>
            <div class="modal-body">
                <div style="line-height: 1.8; color: #595959;">
                    <h3 style="color: #262626; margin-bottom: 16px;">会员体系规则声明</h3>
                    <p><strong>1. 会员等级说明</strong></p>
                    <p>本平台会员分为四个等级：普通会员、银卡会员、金卡会员、钻石会员。会员等级根据累计消费金额和积分自动评定。</p>
                    
                    <p style="margin-top: 16px;"><strong>2. 升级条件</strong></p>
                    <p>会员等级提升需同时满足累计消费金额和积分要求，系统将在每日凌晨自动评定并更新会员等级。</p>
                    
                    <p style="margin-top: 16px;"><strong>3. 等级权益</strong></p>
                    <p>不同等级会员享受不同折扣、包邮政策及专属服务，具体权益以各等级规则配置为准。</p>
                    
                    <p style="margin-top: 16px;"><strong>4. 有效期</strong></p>
                    <p>会员等级有效期为一年，到期后根据上一年度消费情况重新评定。</p>
                    
                    <p style="margin-top: 16px;"><strong>5. 其他说明</strong></p>
                    <p>本平台保留对会员规则进行调整的权利，调整前将通过站内信等方式通知会员。</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeDeclarationModal()">我知道了</button>
            </div>
        </div>
    </div>
    
    <!-- 合约条款弹窗 -->
    <div class="modal-overlay" id="contractModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">📄 会员服务合约条款</div>
                <div class="modal-close" onclick="closeContractModal()">×</div>
            </div>
            <div class="modal-body">
                <div style="line-height: 1.8; color: #595959; max-height: 400px; overflow-y: auto;">
                    <h3 style="color: #262626; margin-bottom: 16px;">会员服务合约</h3>
                    <p><strong>甲方：</strong>青园营地平台</p>
                    <p><strong>乙方：</strong>会员用户</p>
                    
                    <p style="margin-top: 16px;"><strong>第一条 服务内容</strong></p>
                    <p>甲方为乙方提供会员等级体系服务，包括但不限于购物折扣、包邮服务、专属客服等权益。</p>
                    
                    <p style="margin-top: 16px;"><strong>第二条 会员义务</strong></p>
                    <p>乙方应遵守国家法律法规及平台规则，不得滥用会员权益进行违法违规活动。</p>
                    
                    <p style="margin-top: 16px;"><strong>第三条 隐私保护</strong></p>
                    <p>甲方承诺对乙方的个人信息严格保密，未经乙方同意不得向第三方泄露。</p>
                    
                    <p style="margin-top: 16px;"><strong>第四条 合约变更</strong></p>
                    <p>甲方有权根据业务发展需要调整会员规则，调整前将提前 7 日通知乙方。</p>
                    
                    <p style="margin-top: 16px;"><strong>第五条 合约终止</strong></p>
                    <p>如乙方严重违反平台规则，甲方有权终止其会员资格并取消相关权益。</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeContractModal()">关闭</button>
                <button class="btn btn-success" onclick="acceptContract()">✅ 同意并签署</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
    // 显示新增弹窗
    function showAddModal() {
        document.getElementById('modalTitle').textContent = '新增会员规则';
        document.getElementById('ruleId').value = '';
        document.getElementById('ruleForm').reset();
        document.getElementById('ruleModal').classList.add('show');
    }
    
    // 编辑规则
    function editRule(id) {
        // TODO: 调用 API 获取规则详情
        alert('编辑功能开发中，规则 ID: ' + id);
    }
    
    // 删除规则
    function deleteRule(id) {
        if (confirm('确定要删除该规则吗？删除后不可恢复！')) {
            alert('删除功能开发中，规则 ID: ' + id);
        }
    }
    
    // 切换状态
    function toggleStatus(id, status) {
        const action = status === 1 ? '启用' : '禁用';
        if (confirm('确定要' + action + '该规则吗？')) {
            alert('状态切换功能开发中，规则 ID: ' + id);
        }
    }
    
    // 保存规则
    function saveRule() {
        const data = {
            id: document.getElementById('ruleId').value,
            level_id: document.getElementById('levelId').value,
            level_name: document.getElementById('levelName').value,
            upgrade_amount: document.getElementById('upgradeAmount').value,
            upgrade_points: document.getElementById('upgradePoints').value,
            benefit_discount: document.getElementById('benefitDiscount').value,
            benefit_shipping: document.getElementById('benefitShipping').value,
            benefit_service: document.getElementById('benefitService').value
        };
        
        if (!data.level_name) {
            alert('请填写等级名称');
            return;
        }
        
        // TODO: 调用 API 保存
        console.log('保存数据:', data);
        alert('保存功能开发中');
        closeModal();
    }
    
    // 关闭弹窗
    function closeModal() {
        document.getElementById('ruleModal').classList.remove('show');
    }
    
    // 显示规则声明
    function showRuleDeclaration() {
        document.getElementById('declarationModal').classList.add('show');
    }
    
    // 关闭规则声明
    function closeDeclarationModal() {
        document.getElementById('declarationModal').classList.remove('show');
    }
    
    // 显示合约
    function showContract() {
        document.getElementById('contractModal').classList.add('show');
    }
    
    // 关闭合约
    function closeContractModal() {
        document.getElementById('contractModal').classList.remove('show');
    }
    
    // 同意合约
    function acceptContract() {
        alert('✅ 您已同意并签署会员服务合约');
        closeContractModal();
    }
    
    function resetSearch() {
        window.location.href = '?';
    }
    
    // 点击遮罩关闭弹窗
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    });
    </script>
</body>
</html>
