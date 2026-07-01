-- 销售记录表
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID',
    customer_name VARCHAR(100) NOT NULL COMMENT '客户姓名',
    customer_phone VARCHAR(20) NOT NULL COMMENT '客户手机号',
    salesman_id INT NOT NULL COMMENT '销售顾问 ID',
    amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '销售金额',
    product_type VARCHAR(50) DEFAULT '' COMMENT '产品类型',
    status VARCHAR(20) DEFAULT '跟进中' COMMENT '状态：跟进中/已成交/已流失/已退款',
    close_date DATE DEFAULT NULL COMMENT '成交日期',
    remark TEXT DEFAULT NULL COMMENT '备注',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_salesman (salesman_id),
    INDEX idx_status (status),
    INDEX idx_close_date (close_date),
    INDEX idx_phone (customer_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='销售记录表';
