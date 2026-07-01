<?php
$currentPage = 'position';
$pageTitle = '岗位管理';

// 检查访问权限
require_once dirname(__DIR__) . '/includes/PermissionChecker.php';
$permissionChecker = new PermissionChecker();
$permissionChecker->requireAccess('position');

require_once __DIR__ . '/includes/header.php';
?>

<style>
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
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

.filter-bar {
    background: linear-gradient(135deg, #fafafa, white);
    padding: 24px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
}

.filter-row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-item {
    flex: 1;
    min-width: 180px;
}

.filter-item label {
    display: block;
    margin-bottom: 10px;
    color: #595959;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.filter-item label::before {
    content: '';
    width: 3px;
    height: 14px;
    background: linear-gradient(180deg, #1890ff, #40a9ff);
    border-radius: 2px;
}

.filter-item input,
.filter-item select {
    width: 100%;
    padding: 11px 14px;
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    font-size: 14px;
    background: white;
    color: #262626;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.filter-item input:focus,
.filter-item select:focus {
    outline: none;
    border-color: #1890ff;
    background: white;
    box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
    transform: translateY(-1px);
}

.filter-item input:hover,
.filter-item select:hover {
    border-color: #b37feb;
}

.filter-item .btn {
    width: 100%;
}

.btn {
    padding: 11px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.12);
}

.btn:active {
    transform: translateY(0);
}

.btn-primary {
    background: linear-gradient(135deg, #1890ff, #40a9ff);
    color: white;
}

.btn-primary:hover {
    box-shadow: 0 8px 24px rgba(24,144,255,0.4);
}

.btn-success {
    background: linear-gradient(135deg, #52c41a, #73d13d);
    color: white;
}

.btn-success:hover {
    box-shadow: 0 8px 24px rgba(82,196,26,0.4);
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

.table-container {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 18px 16px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

.data-table th {
    background: linear-gradient(180deg, #fafafa, #f5f5f5);
    font-weight: 700;
    color: #262626;
    font-size: 14px;
    position: relative;
}

.data-table th::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, #1890ff, #40a9ff);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.data-table th:hover::after {
    opacity: 1;
}

.data-table td {
    font-size: 14px;
    color: #595959;
    transition: all 0.2s ease;
}

.data-table tr {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.data-table tr:hover {
    background: linear-gradient(90deg, rgba(24,144,255,0.03), rgba(64,169,255,0.03));
    transform: scale(1.002);
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.data-table tr:hover td {
    color: #262626;
}

.data-table .actions {
    display: flex;
    flex-direction: row;
    gap: 8px;
    flex-wrap: wrap;
}

.data-table .actions a {
    color: #1890ff;
    cursor: pointer;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s ease;
    padding: 6px 14px;
    border-radius: 6px;
    white-space: nowrap;
}

.data-table .actions a:hover {
    background: #f0f5ff;
    transform: translateY(-1px);
}

.data-table .actions a.danger {
    color: #f5222d;
}

.data-table .actions a.danger:hover {
    background: #fff1f0;
}

.status-tag {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
}

.status-tag.active {
    background: #f6ffed;
    color: #52c41a;
    border: 1px solid #b7eb8f;
}

.status-tag.inactive {
    background: #f5f5f5;
    color: #999;
    border: 1px solid #d9d9d9;
}

/* 推荐拨动开关 */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #f0f0f0;
    transition: all 0.3s ease;
    border-radius: 24px;
    border: 2px solid #d9d9d9;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 2px;
    bottom: 1px;
    background-color: white;
    transition: all 0.3s ease;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.toggle-switch input:checked + .toggle-slider {
    background: linear-gradient(135deg, #1890ff, #40a9ff);
    border-color: #1890ff;
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

.toggle-switch input:focus + .toggle-slider {
    box-shadow: 0 0 0 3px rgba(24,144,255,0.2);
}

/* 标签美化 */
.tag {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    border: 1px solid transparent;
}

.tag-blue {
    background: linear-gradient(135deg, #e6f7ff, #bae7ff);
    color: #0050b3;
    border-color: #91d5ff;
}

.tag-purple {
    background: linear-gradient(135deg, #f9f0ff, #efdbff);
    color: #531dab;
    border-color: #d3adf7;
}

.tag-green {
    background: linear-gradient(135deg, #f6ffed, #d9f7be);
    color: #237804;
    border-color: #b7eb8f;
}

.tag-orange {
    background: linear-gradient(135deg, #fff7e6, #ffe7ba);
    color: #ad6800;
    border-color: #ffd591;
}

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
    color: #1890ff;
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
    box-shadow: 0 2px 8px rgba(24,144,255,0.1);
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
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%231890ff' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 16px;
}

.page-size-selector select:hover {
    background: linear-gradient(135deg, #e6f7ff, #bae7ff);
}

.page-size-selector select:focus {
    background: linear-gradient(135deg, #e6f7ff, #bae7ff);
    box-shadow: 0 0 0 3px rgba(24,144,255,0.2);
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

.pagination-buttons {
    display: inline-flex;
    gap: 8px;
}

.pagination-buttons button {
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

.pagination-buttons button:hover:not(:disabled) {
    border-color: #1890ff;
    color: #1890ff;
    background: #f0f5ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(24,144,255,0.2);
}

.pagination-buttons button:disabled {
    cursor: not-allowed;
    opacity: 0.4;
    background: #f5f5f5;
}

/* 模态框美化 - 优化性能 */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.55);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.modal-overlay.show {
    display: flex;
    opacity: 1;
}

.modal {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 850px;
    max-height: 90vh;
    overflow-y: auto;
    overflow-x: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    transform: translateY(20px);
    transition: transform 0.25s ease;
}

.modal-overlay.show .modal {
    transform: translateY(0);
}

.modal::-webkit-scrollbar {
    width: 6px;
}

.modal::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.modal::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.modal::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    padding: 28px 32px;
    border-bottom: 2px solid #f0f2f5;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #fafafa, white);
    border-radius: 20px 20px 0 0;
    position: sticky;
    top: 0;
    z-index: 10;
}

.modal-title {
    font-size: 24px;
    font-weight: 700;
    color: #262626;
    display: flex;
    align-items: center;
    gap: 12px;
}

.modal-title::before {
    content: '';
    width: 6px;
    height: 28px;
    background: linear-gradient(180deg, #1890ff, #40a9ff);
    border-radius: 3px;
}

.modal-close {
    width: 40px;
    height: 40px;
    border: none;
    background: #f5f5f5;
    font-size: 24px;
    cursor: pointer;
    color: #595959;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: linear-gradient(135deg, #ff4d4f, #ff7875);
    color: white;
    transform: rotate(90deg) scale(1.1);
    box-shadow: 0 4px 12px rgba(255,77,79,0.3);
}

.modal-body {
    padding: 32px;
}

.modal-footer {
    padding: 20px 32px;
    border-top: 2px solid #f0f2f5;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: linear-gradient(180deg, #fafafa, white);
    border-radius: 0 0 20px 20px;
}

.modal-footer .btn {
    min-width: 100px;
    padding: 12px 24px;
    font-size: 15px;
    font-weight: 600;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.modal-footer .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}

/* 表单美化 */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row.full {
    grid-template-columns: 1fr;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    color: #262626;
    font-weight: 600;
    font-size: 14px;
}

.form-group label.required::after {
    content: ' *';
    color: #ff4d4f;
    font-size: 16px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    font-size: 14px;
    color: #262626;
    background: #fafafa;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #1890ff;
    background: white;
    box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 90px;
    line-height: 1.6;
}

.form-group small {
    display: block;
    margin-top: 8px;
    color: #8c8c8c;
    font-size: 12px;
}

/* 标签输入组件 */
.tag-input-container {
    margin-bottom: 10px;
}

.tag-input-wrapper {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}

.tag-input {
    flex: 1;
    padding: 10px 14px;
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    font-size: 14px;
    background: #fafafa;
    transition: all 0.3s ease;
}

.tag-input:focus {
    outline: none;
    border-color: #1890ff;
    background: white;
    box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
}

.btn-add-tag {
    padding: 10px 20px;
    background: linear-gradient(135deg, #1890ff, #40a9ff);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-add-tag:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(24,144,255,0.4);
}

.btn-add-tag:active {
    transform: translateY(0);
}

/* 标签列表 */
.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    min-height: 40px;
    padding: 10px;
    background: #fafafa;
    border-radius: 10px;
    border: 2px dashed #e8e8e8;
    transition: border-color 0.3s ease;
}

.tag-list:has(.tag-item) {
    border-style: solid;
    border-color: #f0f0f0;
}

/* 标签项 */
.tag-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: linear-gradient(135deg, #f0f5ff, #e6f7ff);
    border: 1px solid #bae7ff;
    border-radius: 8px;
    font-size: 13px;
    color: #262626;
    animation: tagPop 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    group: tag;
}

@keyframes tagPop {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.tag-item:hover {
    background: linear-gradient(135deg, #e6f7ff, #bae7ff);
    border-color: #91d5ff;
}

.tag-item .tag-text {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.tag-item .tag-delete {
    width: 20px;
    height: 20px;
    border: none;
    background: #ff4d4f;
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.tag-item:hover .tag-delete {
    opacity: 1;
    transform: scale(1);
}

.tag-item .tag-delete:hover {
    background: #ff7875;
    transform: scale(1.1) rotate(90deg);
}

/* 空状态提示 */
.tag-list:empty::before {
    content: '暂无标签，请在上方输入后添加';
    color: #bfbfbf;
    font-size: 13px;
    display: flex;
    align-items: center;
    padding: 8px 0;
}

/* 标签计数 */
.tag-count {
    display: inline-block;
    margin-left: 8px;
    padding: 2px 8px;
    background: #f0f0f0;
    border-radius: 10px;
    font-size: 12px;
    color: #8c8c8c;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.form-row.full {
    grid-template-columns: 1fr;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #999;
    font-size: 12px;
}

/* 材料输入组件 */
.material-input-container {
    margin-bottom: 10px;
}

.material-input-wrapper {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}

.material-input {
    flex: 1;
    padding: 10px 14px;
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    font-size: 14px;
    background: #fafafa;
    transition: all 0.3s ease;
}

.material-input:focus {
    outline: none;
    border-color: #1890ff;
    background: white;
    box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
}

/* 材料列表 */
.material-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    min-height: 40px;
    padding: 10px;
    background: #fafafa;
    border-radius: 10px;
    border: 2px dashed #e8e8e8;
    transition: border-color 0.3s ease;
}

.material-list:has(.material-item) {
    border-style: solid;
    border-color: #f0f0f0;
}

/* 材料项 */
.material-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: linear-gradient(135deg, #f0f5ff, #e6f7ff);
    border: 1px solid #bae7ff;
    border-radius: 8px;
    font-size: 13px;
    color: #262626;
    animation: tagPop 0.3s cubic-bezier(0.4, 0, 0, 1);
    position: relative;
}

.material-item:hover {
    background: linear-gradient(135deg, #e6f7ff, #bae7ff);
    border-color: #91d5ff;
}

.material-item .material-text {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.material-item .material-delete {
    width: 20px;
    height: 20px;
    border: none;
    background: #ff4d4f;
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.material-item:hover .material-delete {
    opacity: 1;
    transform: scale(1);
}

.material-item .material-delete:hover {
    background: #ff7875;
    transform: scale(1.1) rotate(90deg);
}

/* 空状态提示 */
.material-list:empty::before {
    content: '暂无材料，请在上方输入后添加';
    color: #bfbfbf;
    font-size: 13px;
    display: flex;
    align-items: center;
    padding: 8px 0;
}

/* 上传区域 */
.upload-area {
    border: 2px dashed #d9d9d9;
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fafafa;
}

.upload-area:hover {
    border-color: #1890ff;
    background: #f0f5ff;
}

.upload-area.dragover {
    border-color: #1890ff;
    background: #e6f7ff;
    transform: scale(1.02);
}

.upload-icon {
    font-size: 48px;
    margin-bottom: 10px;
}

.upload-text {
    font-size: 14px;
    color: #262626;
    font-weight: 600;
    margin-bottom: 8px;
}

.upload-hint {
    font-size: 12px;
    color: #8c8c8c;
}

/* 文件列表 */
.file-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 10px;
}

.file-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: white;
    border: 1px solid #f0f0f0;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.file-item:hover {
    border-color: #1890ff;
    box-shadow: 0 2px 8px rgba(24,144,255,0.1);
}

.file-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f5ff;
    border-radius: 6px;
    font-size: 20px;
    flex-shrink: 0;
}

.file-info {
    flex: 1;
    min-width: 0;
}

.file-name {
    font-size: 14px;
    color: #262626;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-size {
    font-size: 12px;
    color: #8c8c8c;
    margin-top: 2px;
}

.file-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.file-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.2s ease;
}

.file-btn-preview {
    background: #e6f7ff;
    color: #1890ff;
}

.file-btn-preview:hover {
    background: #1890ff;
    color: white;
}

.file-btn-delete {
    background: #fff1f0;
    color: #f5222d;
}

.file-btn-delete:hover {
    background: #f5222d;
    color: white;
}

/* 文件预览模态框 */
.preview-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 3000;
}

.preview-modal.show {
    display: flex;
}

.preview-content {
    background: white;
    border-radius: 16px;
    max-width: 90%;
    max-height: 90%;
    overflow: auto;
    position: relative;
}

.preview-content img {
    max-width: 100%;
    height: auto;
    display: block;
}

.preview-close {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 40px;
    height: 40px;
    border: none;
    background: rgba(0,0,0,0.5);
    color: white;
    font-size: 24px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-close:hover {
    background: rgba(0,0,0,0.8);
}
</style>

<!-- 批量操作栏 -->
<div id="batchActionBar" class="batch-action-bar" style="display: none;">
    <div class="batch-action-content">
        <div class="batch-info">
            <input type="checkbox" id="batchSelectAll" onchange="toggleSelectAll()" checked style="width: 18px; height: 18px; cursor: pointer;">
            <span>已选择 <strong id="selectedCount" style="color: #1890ff; font-size: 16px;">0</strong> 个岗位</span>
        </div>
        <div class="batch-actions">
            <button class="btn btn-warning btn-sm" onclick="batchToggleStatus(1)">
                ⬆️ 批量上架
            </button>
            <button class="btn btn-warning btn-sm" onclick="batchToggleStatus(0)">
                ⬇️ 批量下架
            </button>
            <button class="btn btn-danger btn-sm" onclick="batchDelete()">
                批量删除
            </button>
            <button class="btn btn-sm" onclick="clearSelection()" style="background: #f0f0f0; color: #666;">
                ✕ 取消选择
            </button>
        </div>
    </div>
</div>

<!-- 筛选栏 -->
<div class="filter-bar">
    <div class="filter-row">
        <div class="filter-item">
            <label>关键词</label>
            <input type="text" id="keyword" placeholder="搜索岗位标题、描述">
        </div>
        <div class="filter-item">
            <label>国家</label>
            <select id="country">
                <option value="">全部</option>
            </select>
        </div>
        <div class="filter-item">
            <label>状态</label>
            <select id="status">
                <option value="">全部</option>
                <option value="1">上架</option>
                <option value="0">下架</option>
            </select>
        </div>
        <div class="filter-item" style="flex: 0; min-width: auto;">
            <div style="display: flex; gap: 12px;">
                <button class="btn btn-primary" onclick="loadPositions()" style="min-width: 100px;">
                    🔍 搜索
                </button>
                <a href="position_edit.php" class="btn btn-success" style="min-width: 120px; text-decoration: none;">
                    ➕ 新增岗位
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 表格 -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 50px;">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="width: 18px; height: 18px; cursor: pointer;">
                </th>
                <th style="width: 60px;">ID</th>
                <th>岗位名称</th>
                <th>岗位分类</th>
                <th>所属区域</th>
                <th style="width: 140px;">薪资范围</th>
                <th style="width: 90px;">查看量</th>
                <th style="width: 100px;">状态</th>
                <th style="width: 150px;">创建/更新时间</th>
                <th style="width: 100px;">推荐</th>
                <th style="width: 220px;">操作</th>
            </tr>
        </thead>
        <tbody id="positionList">
            <tr>
                <td colspan="11" style="text-align: center; padding: 40px;">加载中...</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- 底部分页栏 -->
<div class="pagination" id="pagination"></div>

<!-- 编辑模态框 -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">新增岗位</h3>
            <button class="modal-close" onclick="hideModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="positionForm">
                <input type="hidden" id="positionId">
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">岗位名称 *</label>
                        <input type="text" id="title" required placeholder="例如：新加坡中文教师">
                    </div>
                    <div class="form-group">
                        <label class="required">岗位分类 *</label>
                        <select id="industry" required>
                            <option value="">请选择分类</option>
                            <option value="教育/培训">📚 教育/培训</option>
                            <option value="IT/互联网">💻 IT/互联网</option>
                            <option value="金融/银行">💰 金融/银行</option>
                            <option value="医疗/护理">🏥 医疗/护理</option>
                            <option value="工程/制造">🏭 工程/制造</option>
                            <option value="销售/市场">📈 销售/市场</option>
                            <option value="行政/人事">👔 行政/人事</option>
                            <option value="酒店/旅游">🏨 酒店/旅游</option>
                            <option value="其他">📦 其他</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">所属区域 - 国家 *</label>
                        <input type="text" id="country2" required placeholder="例如：新加坡">
                    </div>
                    <div class="form-group">
                        <label>所属区域 - 城市</label>
                        <input type="text" id="city" placeholder="例如：新加坡市">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>签证类型</label>
                        <input type="text" id="visaType">
                    </div>
                    <div class="form-group">
                        <label>学历要求</label>
                        <input type="text" id="educationRequired">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>最低年龄</label>
                        <input type="number" id="ageMin">
                    </div>
                    <div class="form-group">
                        <label>最高年龄</label>
                        <input type="number" id="ageMax">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>薪资范围（文本）</label>
                        <input type="text" id="salaryRange" placeholder="例如：¥2000-¥3000 或 面议">
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>岗位描述</label>
                        <textarea id="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>岗位要求</label>
                        <textarea id="requirements" rows="3" placeholder="例如：\n1. 学前教育或相关专业大专及以上学历\n2. 有教师资格证优先\n3. 有耐心、爱心，喜欢孩子"></textarea>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>福利待遇</label>
                        <textarea id="benefits" rows="3" placeholder="例如：提供住宿、机票补贴、年终奖金等"></textarea>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>岗位标签</label>
                        <div class="tag-input-container">
                            <div class="tag-input-wrapper">
                                <input type="text" id="tagsInput" placeholder="输入标签后按回车或点击添加，如：急聘、包吃住、年假" class="tag-input">
                                <button type="button" onclick="addTag('tags')" class="btn-add-tag">+ 添加</button>
                            </div>
                            <div id="tagsList" class="tag-list"></div>
                        </div>
                        <textarea id="tags" rows="2" style="display: none;"></textarea>
                        <small>标签将显示在岗位列表中，方便筛选和识别</small>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>所需材料</label>
                        <div class="material-input-container">
                            <div class="material-input-wrapper">
                                <input type="text" id="materialsInput" placeholder="输入所需材料后按回车或点击添加，如：护照、简历、学历证书" class="material-input">
                                <button type="button" onclick="addMaterial()" class="btn-add-tag">+ 添加</button>
                            </div>
                            <div id="materialsList" class="material-list"></div>
                        </div>
                        <textarea id="required_materials" rows="2" style="display: none;"></textarea>
                        <small>列出申请该岗位需要提供的材料清单</small>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>附件文件</label>
                        <div class="upload-area" id="uploadArea" onclick="document.getElementById('fileInput').click()">
                            <div class="upload-icon">📁</div>
                            <div class="upload-text">点击或拖拽文件到此处上传</div>
                            <div class="upload-hint">支持 JPG、PNG、PDF、DOC、DOCX、XLS、XLSX 格式，最大 10MB</div>
                            <input type="file" id="fileInput" style="display: none;" onchange="handleFileSelect(event)" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt" multiple>
                        </div>
                        <div id="uploadProgress" style="display: none; margin-top: 10px;">
                            <div style="background: #f0f0f0; border-radius: 4px; height: 8px; overflow: hidden;">
                                <div id="progressBar" style="background: linear-gradient(135deg, #1890ff, #40a9ff); height: 100%; width: 0%; transition: width 0.3s;"></div>
                            </div>
                            <div id="progressText" style="text-align: center; margin-top: 4px; font-size: 12px; color: #8c8c8c;">上传中...</div>
                        </div>
                        <div id="fileList" class="file-list"></div>
                        <textarea id="attachment_files" rows="2" style="display: none;"></textarea>
                        <small>上传岗位相关的附件文件，如职位描述文档、公司介绍等</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>状态</label>
                        <select id="status2">
                            <option value="1">上架</option>
                            <option value="0">下架</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="hideModal()">取消</button>
            <button class="btn btn-primary" onclick="savePosition()">保存</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let totalPages = 1;
let pageSize = 50; // 默认每页显示 50 条

// 加载岗位列表
function loadPositions(page = 1) {
    // 清除选择
    clearSelection();
    
    const keyword = document.getElementById('keyword').value;
    const country = document.getElementById('country').value;
    const status = document.getElementById('status').value;
    
    let url = `../api/position.php?action=list&page=${page}&page_size=${pageSize}`;
    if (keyword) url += `&keyword=${encodeURIComponent(keyword)}`;
    if (country) url += `&country=${encodeURIComponent(country)}`;
    if (status !== '') url += `&status=${status}`;
    
    fetch(url)
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) {
                document.getElementById('positionList').innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px; color: #f5222d;">加载失败</td></tr>';
                return;
            }
            
            const { list, pagination } = res.data;
            currentPage = pagination.page;
            totalPages = pagination.total_pages;
            
            if (list.length === 0) {
                document.getElementById('positionList').innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 60px; color: #999; font-size: 14px;">暂无岗位数据，点击上方"新增岗位"添加</td></tr>';
            } else {
                document.getElementById('positionList').innerHTML = list.map(item => `
                    <tr>
                        <td style="text-align: center; vertical-align: middle;">
                            <input type="checkbox" class="select-item" data-id="${item.id}" onchange="updateSelectCount()" style="width: 18px; height: 18px; cursor: pointer;">
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <span style="font-weight: 600; color: #bfbfbf;">#${item.id}</span>
                        </td>
                        <td style="vertical-align: middle;">
                            <div style="font-weight: 600; color: #262626; font-size: 15px; margin-bottom: 4px;">${item.title}</div>
                            <div style="font-size: 12px; color: #8c8c8c;">${item.education_required || '学历不限'} · ${item.age_min ? item.age_min + '-' + item.age_max + '岁' : '年龄不限'}</div>
                        </td>
                        <td style="vertical-align: middle;">
                            <span class="tag tag-purple">${item.category || '未分类'}</span>
                        </td>
                        <td style="vertical-align: middle;">
                            <div style="font-weight: 500; color: #262626;">${item.country || '-'}</div>
                            ${item.city ? `<div style="font-size: 12px; color: #8c8c8c;">${item.city}</div>` : ''}
                        </td>
                        <td style="vertical-align: middle;">
                            <div style="font-weight: 700; color: #52c41a; font-size: 14px;">
                                ${item.salary_range || '面议'}
                            </div>
                            ${item.visa_type ? `<div style="font-size: 11px; color: #8c8c8c;">${item.visa_type}</div>` : ''}
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <div style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #f0f5ff; border-radius: 12px;">
                                <span style="font-weight: 600; color: #1890ff;">${item.view_count || 0}</span>
                            </div>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <span class="status-tag ${item.status == 1 ? 'active' : 'inactive'}">
                                ${item.status == 1 ? '上架' : '下架'}
                            </span>
                        </td>
                        <td style="vertical-align: middle;">
                            <div style="font-size: 12px; color: #262626; font-weight: 500; margin-bottom: 2px;">
                                创建：${item.created_at ? item.created_at.slice(0, 10) : '-'}
                            </div>
                            <div style="font-size: 11px; color: #8c8c8c;">
                                更新：${item.updated_at ? item.updated_at.slice(0, 10) : '-'}
                            </div>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <label class="toggle-switch">
                                <input type="checkbox" 
                                       onchange="toggleRecommend(${item.id}, this.checked ? 1 : 0)" 
                                       ${item.is_recommend == 1 ? 'checked' : ''}
                                       style="width: 18px; height: 18px; cursor: pointer;">
                                <span class="toggle-slider"></span>
                            </label>
                        </td>
                        <td style="vertical-align: middle;">
                            <div class="actions">
                                <a href="position_edit2.php?id=${item.id}" style="color: #1890ff;">编辑</a>
                                <a onclick="toggleStatus(${item.id}, ${item.status})" style="color: ${item.status == 1 ? '#faad14' : '#52c41a'};">
                                    ${item.status == 1 ? '下架' : '上架'}
                                </a>
                                <a class="danger" onclick="deletePosition(${item.id})" style="color: #f5222d;">删除</a>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
            
            // 更新分页
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
                    <button class="pagination-btn" onclick="loadPositions(1)" ${page === 1 ? 'disabled' : ''}>⏮ 首页</button>
                    <button class="pagination-btn" onclick="loadPositions(${page - 1})" ${page === 1 ? 'disabled' : ''}>⏯ 上一页</button>
                    <button class="pagination-btn" onclick="loadPositions(${page + 1})" ${page === totalPages ? 'disabled' : ''}>下一页 ⏭</button>
                    <button class="pagination-btn" onclick="loadPositions(${totalPages})" ${page === totalPages ? 'disabled' : ''}>末页 ⏭</button>
                </div>
            `;
        });
}

// 切换每页显示数量
function changePageSize(size) {
    pageSize = parseInt(size);
    loadPositions(1); // 重新从第一页加载
}

// 加载国家列表
function loadCountries() {
    fetch('../api/position.php?action=countries')
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) {
                document.getElementById('country').innerHTML = '<option value="">全部</option>' + 
                    res.data.map(c => `<option value="${c}">${c}</option>`).join('');
            }
        });
}

// 显示新增模态框 - 优化性能
function showCreateModal() {
    document.getElementById('modalTitle').textContent = '新增岗位';
    document.getElementById('positionForm').reset();
    document.getElementById('positionId').value = '';
    
    // 重置标签
    tagData.tags = [];
    const tagsList = document.getElementById('tagsList');
    if (tagsList) {
        tagsList.innerHTML = '';
    }
    const tagsTextarea = document.getElementById('tags');
    if (tagsTextarea) {
        tagsTextarea.value = '';
    }
    
    // 重置材料
    materialData.materials = [];
    const materialsList = document.getElementById('materialsList');
    if (materialsList) {
        materialsList.innerHTML = '';
    }
    const materialsTextarea = document.getElementById('required_materials');
    if (materialsTextarea) {
        materialsTextarea.value = '';
    }
    
    // 重置文件
    fileData.files = [];
    const fileList = document.getElementById('fileList');
    if (fileList) {
        fileList.innerHTML = '';
    }
    const filesTextarea = document.getElementById('attachment_files');
    if (filesTextarea) {
        filesTextarea.value = '';
    }
    
    document.getElementById('modalOverlay').classList.add('show');
    
    // 聚焦到岗位名称输入框
    setTimeout(function() {
        document.getElementById('title').focus();
    }, 100);
}

// 编辑岗位
function editPosition(id) {
    console.log('编辑岗位 ID:', id);
    
    // 重置数据
    tagData.tags = [];
    materialData.materials = [];
    fileData.files = [];
    
    fetch(`../api/position.php?action=detail&id=${id}`)
        .then(res => res.json())
        .then(res => {
            console.log('API 响应:', res);
            
            if (res.code === 200) {
                const data = res.data;
                document.getElementById('modalTitle').textContent = '编辑岗位';
                document.getElementById('positionId').value = data.id;
                document.getElementById('title').value = data.title || '';
                document.getElementById('country2').value = data.country || '';
                document.getElementById('city').value = data.city || '';
                document.getElementById('industry').value = data.industry || '';
                document.getElementById('visaType').value = data.visa_type || '';
                document.getElementById('educationRequired').value = data.education_required || '';
                document.getElementById('ageMin').value = data.age_min || '';
                document.getElementById('ageMax').value = data.age_max || '';
                document.getElementById('salaryRange').value = data.salary_range || '';
                document.getElementById('description').value = data.description || '';
                document.getElementById('requirements').value = data.requirements || '';
                document.getElementById('benefits').value = data.benefits || '';
                document.getElementById('tags').value = data.tags || '';
                document.getElementById('required_materials').value = data.required_materials || '';
                document.getElementById('attachment_files').value = data.attachment_files || '';
                document.getElementById('status2').value = data.status || '1';
                
                // 加载标签
                loadTagsFromTextarea('tags');
                
                // 加载材料
                loadMaterialsFromTextarea();
                
                // 加载文件
                loadFilesFromTextarea();
                
                document.getElementById('modalOverlay').classList.add('show');
                console.log('模态框已打开');
            } else {
                alert('获取岗位信息失败：' + res.message);
                console.error('错误:', res);
            }
        })
        .catch(err => {
            console.error('网络错误:', err);
            alert('网络错误，请检查控制台');
        });
}

// 标签管理
const tagData = {
    tags: []
};

// 材料管理
const materialData = {
    materials: []
};

// 文件管理
const fileData = {
    files: []
};

// 初始化标签
function initTags() {
    // 岗位标签
    const tagsInput = document.getElementById('tagsInput');
    if (tagsInput) {
        tagsInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                addTag('tags');
            }
        });
    }
    
    // 材料输入
    const materialsInput = document.getElementById('materialsInput');
    if (materialsInput) {
        materialsInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                addMaterial();
            }
        });
    }
    
    // 文件上传区域拖拽事件
    const uploadArea = document.getElementById('uploadArea');
    if (uploadArea) {
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            handleFiles(files);
        });
    }
}

// 添加标签
function addTag(type) {
    const input = document.getElementById(`${type}Input`);
    const value = input.value.trim();
    
    if (!value) {
        input.focus();
        return;
    }
    
    // 检查是否重复
    if (tagData[type].includes(value)) {
        input.value = '';
        return;
    }
    
    tagData[type].push(value);
    renderTags(type);
    input.value = '';
    input.focus();
    
    // 同步到隐藏的 textarea
    syncTagsToTextarea(type);
}

// 删除标签
function deleteTag(type, index) {
    tagData[type].splice(index, 1);
    renderTags(type);
    syncTagsToTextarea(type);
}

// 渲染标签
function renderTags(type) {
    const container = document.getElementById(`${type}List`);
    if (!container) return;
    
    if (tagData[type].length === 0) {
        container.innerHTML = '';
        return;
    }
    
    container.innerHTML = tagData[type].map((tag, index) => `
        <div class="tag-item">
            <span class="tag-text">${escapeHtml(tag)}</span>
            <button type="button" class="tag-delete" onclick="deleteTag('${type}', ${index})" title="删除">✕</button>
        </div>
    `).join('');
}

// 同步标签到 textarea
function syncTagsToTextarea(type) {
    const textarea = document.getElementById(type);
    if (textarea) {
        textarea.value = tagData[type].join(',');
    }
}

// HTML 转义
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 从 textarea 加载标签
function loadTagsFromTextarea(type) {
    const textarea = document.getElementById(type);
    if (!textarea) return;
    
    const items = textarea.value.split(',').filter(item => item.trim());
    tagData[type] = [...new Set(items)]; // 去重
    renderTags(type);
}

// ========== 材料管理函数 ==========

// 添加材料
function addMaterial() {
    const input = document.getElementById('materialsInput');
    const value = input.value.trim();
    
    if (!value) {
        input.focus();
        return;
    }
    
    // 检查是否重复
    if (materialData.materials.includes(value)) {
        input.value = '';
        return;
    }
    
    materialData.materials.push(value);
    renderMaterials();
    input.value = '';
    input.focus();
    
    // 同步到隐藏的 textarea
    syncMaterialsToTextarea();
}

// 删除材料
function deleteMaterial(index) {
    materialData.materials.splice(index, 1);
    renderMaterials();
    syncMaterialsToTextarea();
}

// 渲染材料列表
function renderMaterials() {
    const container = document.getElementById('materialsList');
    if (!container) return;
    
    if (materialData.materials.length === 0) {
        container.innerHTML = '';
        return;
    }
    
    container.innerHTML = materialData.materials.map((material, index) => `
        <div class="material-item">
            <span class="material-text">${escapeHtml(material)}</span>
            <button type="button" class="material-delete" onclick="deleteMaterial(${index})" title="删除">✕</button>
        </div>
    `).join('');
}

// 同步材料到 textarea
function syncMaterialsToTextarea() {
    const textarea = document.getElementById('required_materials');
    if (textarea) {
        textarea.value = materialData.materials.join(',');
    }
}

// 从 textarea 加载材料
function loadMaterialsFromTextarea() {
    const textarea = document.getElementById('required_materials');
    if (!textarea) return;
    
    const items = textarea.value.split(',').filter(item => item.trim());
    materialData.materials = [...new Set(items)]; // 去重
    renderMaterials();
}

// ========== 文件上传函数 ==========

// 处理文件选择
function handleFileSelect(event) {
    const files = event.target.files;
    handleFiles(files);
    // 清空 input，允许重复上传同一文件
    event.target.value = '';
}

// 处理文件
function handleFiles(files) {
    Array.from(files).forEach(file => {
        uploadFile(file);
    });
}

// 上传单个文件
function uploadFile(file) {
    // 检查文件大小
    if (file.size > 10 * 1024 * 1024) {
        alert('文件大小不能超过 10MB: ' + file.name);
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    
    // 显示进度
    document.getElementById('uploadProgress').style.display = 'block';
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressText').textContent = '上传中：' + file.name;
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            document.getElementById('progressBar').style.width = percentComplete + '%';
        }
    });
    
    xhr.addEventListener('load', () => {
        document.getElementById('uploadProgress').style.display = 'none';
        
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.code === 200) {
                    // 添加到文件列表
                    fileData.files.push(response.data);
                    renderFileList();
                    syncFilesToTextarea();
                } else {
                    alert('上传失败：' + response.message);
                }
            } catch (e) {
                alert('上传失败：解析响应失败');
            }
        } else {
            alert('上传失败：HTTP ' + xhr.status);
        }
    });
    
    xhr.addEventListener('error', () => {
        document.getElementById('uploadProgress').style.display = 'none';
        alert('上传失败：网络错误');
    });
    
    xhr.open('POST', '../api/upload.php');
    xhr.send(formData);
}

// 渲染文件列表
function renderFileList() {
    const container = document.getElementById('fileList');
    if (!container) return;
    
    if (fileData.files.length === 0) {
        container.innerHTML = '';
        return;
    }
    
    container.innerHTML = fileData.files.map((file, index) => {
        const icon = getFileIcon(file.mime_type);
        const size = formatFileSize(file.file_size);
        const isImage = file.mime_type.startsWith('image/');
        
        return `
            <div class="file-item">
                <div class="file-icon">${icon}</div>
                <div class="file-info">
                    <div class="file-name" title="${escapeHtml(file.file_name)}">${escapeHtml(file.file_name)}</div>
                    <div class="file-size">${size} · ${file.upload_time}</div>
                </div>
                <div class="file-actions">
                    ${isImage ? `<button class="file-btn file-btn-preview" onclick="previewFile(${index})" title="预览">👁️</button>` : ''}
                    <a href="../${file.file_path}" target="_blank" class="file-btn file-btn-preview" title="下载">⬇️</a>
                    <button class="file-btn file-btn-delete" onclick="deleteFile(${index})" title="删除">🗑️</button>
                </div>
            </div>
        `;
    }).join('');
}

// 获取文件图标
function getFileIcon(mimeType) {
    if (mimeType.startsWith('image/')) return '🖼️';
    if (mimeType === 'application/pdf') return '📄';
    if (mimeType.includes('word')) return '📝';
    if (mimeType.includes('excel')) return '📊';
    return '📁';
}

// 格式化文件大小
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// 预览文件
function previewFile(index) {
    const file = fileData.files[index];
    if (!file || !file.mime_type.startsWith('image/')) return;
    
    const modal = document.getElementById('previewModal');
    if (!modal) {
        // 创建预览模态框
        const modalHtml = `
            <div class="preview-modal" id="previewModal" onclick="closePreview()">
                <div class="preview-content">
                    <button class="preview-close" onclick="closePreview()">×</button>
                    <img id="previewImage" src="" alt="预览">
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    document.getElementById('previewImage').src = '../' + file.file_path;
    document.getElementById('previewModal').classList.add('show');
}

// 关闭预览
function closePreview() {
    const modal = document.getElementById('previewModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

// 删除文件
function deleteFile(index) {
    if (!confirm('确定要删除这个文件吗？')) return;
    
    fileData.files.splice(index, 1);
    renderFileList();
    syncFilesToTextarea();
}

// 同步文件到 textarea
function syncFilesToTextarea() {
    const textarea = document.getElementById('attachment_files');
    if (textarea) {
        textarea.value = JSON.stringify(fileData.files);
    }
}

// 从 textarea 加载文件
function loadFilesFromTextarea() {
    const textarea = document.getElementById('attachment_files');
    if (!textarea || !textarea.value) return;
    
    try {
        fileData.files = JSON.parse(textarea.value);
        renderFileList();
    } catch (e) {
        console.error('解析文件列表失败:', e);
        fileData.files = [];
    }
}

// 保存岗位
function savePosition() {
    console.log('=== 开始保存岗位 ===');
    
    // 同步所有数据到 textarea
    syncTagsToTextarea('tags');
    syncMaterialsToTextarea();
    syncFilesToTextarea();
    
    // 验证必填字段
    const title = document.getElementById('title').value.trim();
    const country = document.getElementById('country2').value.trim();
    const industry = document.getElementById('industry').value;
    
    console.log('表单数据:', { title, country, industry });
    
    if (!title) {
        alert('请输入岗位名称');
        return;
    }
    if (!country) {
        alert('请输入所属区域 - 国家');
        return;
    }
    if (!industry) {
        alert('请选择岗位分类');
        return;
    }
    
    const formData = new FormData();
    formData.append('id', document.getElementById('positionId').value);
    formData.append('title', title);
    formData.append('country', country);
    formData.append('city', document.getElementById('city').value.trim());
    formData.append('industry', industry);
    formData.append('visa_type', document.getElementById('visaType').value.trim());
    formData.append('education_required', document.getElementById('educationRequired').value.trim());
    formData.append('age_min', document.getElementById('ageMin').value);
    formData.append('age_max', document.getElementById('ageMax').value);
    formData.append('salary_range', document.getElementById('salaryRange').value);
    formData.append('description', document.getElementById('description').value.trim());
    formData.append('requirements', document.getElementById('requirements').value.trim());
    formData.append('benefits', document.getElementById('benefits').value.trim());
    formData.append('tags', document.getElementById('tags').value.trim());
    formData.append('required_materials', document.getElementById('required_materials').value.trim());
    formData.append('attachment_files', document.getElementById('attachment_files').value.trim());
    formData.append('status', document.getElementById('status2').value);
    
    const isUpdate = document.getElementById('positionId').value;
    const action = isUpdate ? 'update' : 'create';
    const positionTitle = document.getElementById('title').value.trim();
    
    console.log('提交到 API:', action);
    
    // 记录操作步骤
    const stepLog = {
        action: isUpdate ? '保存岗位 - 提交数据' : '创建岗位 - 提交数据',
        detail: '岗位名称：' + positionTitle + '，操作：数据验证通过，提交到服务器'
    };
    fetch('../api/log_operation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(stepLog)
    }).catch(err => console.error('步骤记录失败:', err));
    
    fetch(`../api/position.php?action=${action}`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        console.log('API 响应:', res);
        
        if (res.code === 200) {
            // 记录成功步骤
            fetch('../api/log_operation.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: isUpdate ? '保存岗位 - 成功' : '创建岗位 - 成功',
                    detail: '岗位名称：' + document.getElementById('title').value + '，ID：' + (res.data?.id || 'N/A')
                })
            }).catch(err => console.error('步骤记录失败:', err));
            
            alert(isUpdate ? '✅ 更新成功' : '✅ 创建成功');
            hideModal();
            loadPositions(currentPage);
        } else {
            // 记录失败步骤
            fetch('../api/log_operation.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: '保存岗位 - 失败',
                    detail: '岗位名称：' + document.getElementById('title').value + '，原因：' + res.message
                })
            }).catch(err => console.error('步骤记录失败:', err));
            
            alert('❌ 操作失败：' + res.message);
        }
    })
    .catch(err => {
        console.error('保存错误:', err);
        alert('❌ 网络错误：' + err.message);
    });
}

// 切换状态
function toggleStatus(id, status) {
    if (!confirm('确定要' + (status == 1 ? '下架' : '上架') + '该岗位吗？')) return;
    
    // 记录确认步骤
    fetch('../api/log_operation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: '切换状态 - 确认',
            detail: '岗位 ID:' + id + '，目标状态：' + (status == 1 ? '下架' : '上架')
        })
    }).catch(err => console.error('步骤记录失败:', err));
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('../api/position.php?action=toggle_status', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            // 记录成功步骤
            fetch('../api/log_operation.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: '切换状态 - 成功',
                    detail: '岗位 ID:' + id + '，新状态：' + (status == 1 ? '已下架' : '已上架')
                })
            });
            
            loadPositions(currentPage);
        } else {
            alert('操作失败：' + res.message);
        }
    });
}

// 切换推荐状态
function toggleRecommend(id, isRecommend) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('is_recommend', isRecommend);
    
    fetch('../api/position.php?action=toggle_recommend', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            // 记录成功步骤
            fetch('../api/log_operation.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: '切换推荐 - 成功',
                    detail: '岗位 ID:' + id + '，新推荐状态：' + (isRecommend == 1 ? '已推荐' : '已取消')
                })
            });
            
            // 重新加载列表
            loadPositions(currentPage);
            
            // 显示提示
            showToast(isRecommend == 1 ? '已设为推荐' : '已取消推荐');
        } else {
            alert('操作失败：' + res.message);
        }
    });
}

// 删除岗位
function deletePosition(id) {
    if (!confirm('确定要删除该岗位吗？此操作不可恢复！')) return;
    
    // 记录确认步骤
    fetch('../api/log_operation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: '删除岗位 - 确认',
            detail: '岗位 ID:' + id
        })
    }).catch(err => console.error('步骤记录失败:', err));
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('../api/position.php?action=delete', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            // 记录成功步骤
            fetch('../api/log_operation.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: '删除岗位 - 成功',
                    detail: '岗位 ID:' + id
                })
            });
            
            loadPositions(currentPage);
        } else {
            alert('删除失败：' + res.message);
        }
    });
}

// 隐藏模态框
function hideModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}

// 显示提示消息
function showToast(message) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 80px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #1890ff, #40a9ff);
        color: white;
        padding: 12px 32px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 8px 24px rgba(24,144,255,0.4);
        z-index: 10000;
        animation: slideDownFade 0.3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideUpFade 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

// 添加动画
if (!document.getElementById('toastAnimation')) {
    const style = document.createElement('style');
    style.id = 'toastAnimation';
    style.textContent = `
        @keyframes slideDownFade {
            from { opacity: 0; transform: translate(-50%, -20px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }
        @keyframes slideUpFade {
            from { opacity: 1; transform: translate(-50%, 0); }
            to { opacity: 0; transform: translate(-50%, -20px); }
        }
    `;
    document.head.appendChild(style);
}

// 批量操作
let selectedIds = [];

// 全选/取消全选
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const batchSelectAll = document.getElementById('batchSelectAll');
    const checkboxes = document.querySelectorAll('.select-item');
    
    // 同步两个全选框
    if (selectAll.checked !== batchSelectAll.checked) {
        batchSelectAll.checked = selectAll.checked;
    }
    
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    
    updateSelectCount();
}

// 更新选中数量
function updateSelectCount() {
    const checkboxes = document.querySelectorAll('.select-item:checked');
    selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.dataset.id));
    
    const count = selectedIds.length;
    document.getElementById('selectedCount').textContent = count;
    
    // 显示/隐藏批量操作栏
    const batchBar = document.getElementById('batchActionBar');
    if (count > 0) {
        batchBar.style.display = 'block';
    } else {
        batchBar.style.display = 'none';
    }
    
    // 同步全选框状态
    const selectAll = document.getElementById('selectAll');
    const batchSelectAll = document.getElementById('batchSelectAll');
    const allCheckboxes = document.querySelectorAll('.select-item');
    const allChecked = allCheckboxes.length > 0 && allCheckboxes.length === checkboxes.length;
    
    selectAll.checked = allChecked;
    batchSelectAll.checked = allChecked;
}

// 清除选择
function clearSelection() {
    const selectAll = document.getElementById('selectAll');
    const batchSelectAll = document.getElementById('batchSelectAll');
    const checkboxes = document.querySelectorAll('.select-item');
    
    checkboxes.forEach(cb => {
        cb.checked = false;
    });
    
    selectAll.checked = false;
    batchSelectAll.checked = false;
    selectedIds = [];
    
    document.getElementById('batchActionBar').style.display = 'none';
}

// 批量上架/下架
function batchToggleStatus(status) {
    if (selectedIds.length === 0) {
        alert('请选择要操作的岗位');
        return;
    }
    
    const action = status == 1 ? '上架' : '下架';
    if (!confirm(`确定要将选中的 ${selectedIds.length} 个岗位${action}吗？`)) {
        return;
    }
    
    // 批量更新状态
    const promises = selectedIds.map(id => {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', status);
        return fetch('../api/position.php?action=set_status', {
            method: 'POST',
            body: formData
        });
    });
    
    Promise.all(promises)
        .then(() => {
            alert(`✅ 成功${action} ${selectedIds.length} 个岗位`);
            clearSelection();
            loadPositions(currentPage);
        })
        .catch(err => {
            console.error('批量操作失败:', err);
            alert('❌ 批量操作失败，请重试');
        });
}

// 批量删除
function batchDelete() {
    if (selectedIds.length === 0) {
        alert('请选择要删除的岗位');
        return;
    }
    
    if (!confirm(`⚠️ 确定要删除选中的 ${selectedIds.length} 个岗位吗？此操作不可恢复！`)) {
        return;
    }
    
    // 批量删除
    const promises = selectedIds.map(id => {
        const formData = new FormData();
        formData.append('id', id);
        return fetch('../api/position.php?action=delete', {
            method: 'POST',
            body: formData
        });
    });
    
    Promise.all(promises)
        .then(() => {
            alert(`✅ 成功删除 ${selectedIds.length} 个岗位`);
            clearSelection();
            loadPositions(currentPage);
        })
        .catch(err => {
            console.error('批量删除失败:', err);
            alert('❌ 批量删除失败，请重试');
        });
}

// 初始化 - 优化性能，只初始化必要的事件监听
document.addEventListener('DOMContentLoaded', function() {
    // 延迟加载，避免阻塞页面渲染
    setTimeout(function() {
        initTags();
        loadPositions(); // 只加载岗位列表
        // loadCountries() 已移除，国家字段改为手动输入，不需要预加载
    }, 100);
});
</script>

<?php include __DIR__ . '/../includes/error_handler_include.php'; ?>
</body>
</html>

