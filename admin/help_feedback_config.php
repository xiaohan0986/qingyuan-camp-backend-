<?php
/**
 * 帮助与反馈配置管理
 */
$currentPage = 'help_feedback_config';
$pageTitle = '帮助与反馈配置';

require_once __DIR__ . '/includes/header.php';

$config = require __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('数据库连接失败：' . $e->getMessage());
}

// 处理保存
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_company_info') {
        // 保存公司信息
        $company_phone = trim($_POST['company_phone'] ?? '');
        $company_email = trim($_POST['company_email'] ?? '');
        $company_intro = trim($_POST['company_intro'] ?? '');
        
        // 更新电话
        $stmt = $pdo->prepare("UPDATE help_feedback_config SET config_value = ?, updated_at = NOW() WHERE config_key = 'company_phone'");
        $stmt->execute([$company_phone]);
        
        // 更新邮箱
        $stmt = $pdo->prepare("UPDATE help_feedback_config SET config_value = ?, updated_at = NOW() WHERE config_key = 'company_email'");
        $stmt->execute([$company_email]);
        
        // 更新介绍
        $stmt = $pdo->prepare("UPDATE help_feedback_config SET config_value = ?, updated_at = NOW() WHERE config_key = 'company_intro'");
        $stmt->execute([$company_intro]);
        
        $message = '<div class="alert alert-success">公司信息保存成功！</div>';
    } elseif ($action === 'add_faq') {
        // 添加 FAQ
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        
        if ($question && $answer) {
            // 获取最大排序
            $stmt = $pdo->query("SELECT MAX(sort) as max_sort FROM help_feedback_config WHERE config_key LIKE 'faq_%'");
            $max_sort = $stmt->fetch(PDO::FETCH_ASSOC)['max_sort'] ?? 0;
            
            $config_key = 'faq_' . (time());
            $config_value = json_encode(['question' => $question, 'answer' => $answer], JSON_UNESCAPED_UNICODE);
            
            $stmt = $pdo->prepare("INSERT INTO help_feedback_config (config_key, config_value, sort, status, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->execute([$config_key, $config_value, $max_sort + 1]);
            
            $message = '<div class="alert alert-success">问题添加成功！</div>';
        } else {
            $message = '<div class="alert alert-danger">问题和答案不能为空！</div>';
        }
    } elseif ($action === 'update_faq') {
        // 更新 FAQ
        $id = intval($_POST['id'] ?? 0);
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        
        if ($id && $question && $answer) {
            $config_value = json_encode(['question' => $question, 'answer' => $answer], JSON_UNESCAPED_UNICODE);
            
            $stmt = $pdo->prepare("UPDATE help_feedback_config SET config_value = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$config_value, $id]);
            
            $message = '<div class="alert alert-success">问题更新成功！</div>';
        }
    } elseif ($action === 'delete_faq') {
        // 删除 FAQ
        $id = intval($_POST['id'] ?? 0);
        
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM help_feedback_config WHERE id = ? AND config_key LIKE 'faq_%'");
            $stmt->execute([$id]);
            
            $message = '<div class="alert alert-success">问题删除成功！</div>';
        }
    } elseif ($action === 'sort_faq') {
        // 排序 FAQ
        $ids = $_POST['ids'] ?? [];
        foreach ($ids as $index => $id) {
            $stmt = $pdo->prepare("UPDATE help_feedback_config SET sort = ? WHERE id = ?");
            $stmt->execute([$index + 1, $id]);
        }
    }
}

// 获取公司信息
$company_info = [];
$stmt = $pdo->query("SELECT config_key, config_value FROM help_feedback_config WHERE config_key IN ('company_phone', 'company_email', 'company_intro')");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $company_info[$row['config_key']] = $row['config_value'];
}

// 获取 FAQ 列表
$faq_list = [];
$stmt = $pdo->query("SELECT id, config_value, sort, status FROM help_feedback_config WHERE config_key LIKE 'faq_%' ORDER BY sort ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data = json_decode($row['config_value'], true);
    if ($data) {
        $faq_list[] = [
            'id' => $row['id'],
            'question' => $data['question'] ?? '',
            'answer' => $data['answer'] ?? '',
            'sort' => $row['sort'],
            'status' => $row['status']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>帮助与反馈配置 - 后台管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; }
        .card { margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card-header { background-color: #4c7dff; color: white; font-weight: bold; }
        .faq-item { background: #fff; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
        .faq-item:hover { border-color: #4c7dff; }
        .btn-move { cursor: pointer; padding: 5px 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">帮助与反馈配置</h1>
        
        <?= $message ?>
        
        <!-- 公司信息 -->
        <div class="card">
            <div class="card-header">公司信息</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_company_info">
                    
                    <div class="mb-3">
                        <label class="form-label">公司电话</label>
                        <input type="text" class="form-control" name="company_phone" value="<?= htmlspecialchars($company_info['company_phone'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">公司邮箱</label>
                        <input type="email" class="form-control" name="company_email" value="<?= htmlspecialchars($company_info['company_email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">公司介绍</label>
                        <textarea class="form-control" name="company_intro" rows="4" required><?= htmlspecialchars($company_info['company_intro'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">保存</button>
                </form>
            </div>
        </div>
        
        <!-- FAQ 管理 -->
        <div class="card">
            <div class="card-header">常见问题列表</div>
            <div class="card-body">
                <!-- 添加 FAQ -->
                <div class="mb-4">
                    <h5>添加新问题</h5>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add_faq">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="question" placeholder="问题" required>
                        </div>
                        <div class="col-md-5">
                            <textarea class="form-control" name="answer" placeholder="答案" rows="1" required></textarea>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">添加</button>
                        </div>
                    </form>
                </div>
                
                <!-- FAQ 列表 -->
                <h5>问题列表（拖拽排序）</h5>
                <div id="faq-list">
                    <?php foreach ($faq_list as $index => $faq): ?>
                    <div class="faq-item" data-id="<?= $faq['id'] ?>">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="btn-move">☰</span>
                                <strong>#<?= $index + 1 ?></strong>
                            </div>
                            <div class="flex-grow-1">
                                <div class="mb-2">
                                    <strong>Q:</strong> <?= htmlspecialchars($faq['question']) ?>
                                </div>
                                <div class="text-muted">
                                    <strong>A:</strong> <?= htmlspecialchars($faq['answer']) ?>
                                </div>
                            </div>
                            <div class="ms-3">
                                <button class="btn btn-sm btn-primary" onclick="editFaq(<?= $faq['id'] ?>, '<?= htmlspecialchars($faq['question'], ENT_QUOTES) ?>', '<?= htmlspecialchars($faq['answer'], ENT_QUOTES) ?>')">编辑</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('确定删除这个问题吗？')">
                                    <input type="hidden" name="action" value="delete_faq">
                                    <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">删除</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($faq_list)): ?>
                <div class="text-muted text-center py-4">暂无问题</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 编辑 FAQ 模态框 -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update_faq">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">编辑问题</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">问题</label>
                            <input type="text" class="form-control" id="edit_question" name="question" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">答案</label>
                            <textarea class="form-control" id="edit_answer" name="answer" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 编辑 FAQ
        function editFaq(id, question, answer) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_question').value = question;
            document.getElementById('edit_answer').value = answer;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        // 拖拽排序（简单实现）
        document.addEventListener('DOMContentLoaded', function() {
            const faqList = document.getElementById('faq-list');
            let draggedItem = null;
            
            faqList.addEventListener('dragstart', function(e) {
                if (e.target.classList.contains('faq-item')) {
                    draggedItem = e.target;
                    e.target.style.opacity = '0.5';
                }
            });
            
            faqList.addEventListener('dragend', function(e) {
                if (e.target.classList.contains('faq-item')) {
                    e.target.style.opacity = '1';
                    
                    // 保存新顺序
                    const items = faqList.querySelectorAll('.faq-item');
                    const ids = Array.from(items).map(item => item.dataset.id);
                    
                    const formData = new FormData();
                    formData.append('action', 'sort_faq');
                    ids.forEach((id, index) => {
                        formData.append('ids[]', id);
                    });
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                }
            });
            
            faqList.addEventListener('dragover', function(e) {
                e.preventDefault();
                const item = e.target.closest('.faq-item');
                if (item && item !== draggedItem) {
                    const rect = item.getBoundingClientRect();
                    const midpoint = rect.top + rect.height / 2;
                    if (e.clientY < midpoint) {
                        faqList.insertBefore(draggedItem, item);
                    } else {
                        faqList.insertBefore(draggedItem, item.nextSibling);
                    }
                }
            });
        });
    </script>
</body>
</html>
