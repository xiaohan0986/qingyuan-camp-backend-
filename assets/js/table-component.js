/* ============================================================
   表格列表组件 JS（Table Component）
   依赖：table-component.css
   用法：页面引入后，在表格渲染完成后调 TableComponent.init(tableElement)
   或自动扫描：TableComponent.autoInit()
   ============================================================ */
(function(global) {
    'use strict';

    var TableComponent = {
        /** 启用列宽拖动（只调整当前列和右侧相邻列） */
        enableResize: function(table) {
            if (!table || table._resizeEnabled) return;
            table._resizeEnabled = true;

            var ths = table.querySelectorAll('th');

            ths.forEach(function(th, index) {
                var handle = document.createElement('div');
                handle.className = 'col-resize-handle';
                th.appendChild(handle);

                var startX, startWidth, nextWidth;

                function onMouseDown(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    startX = e.clientX;
                    startWidth = th.offsetWidth;

                    // 获取右侧相邻列的初始宽度
                    var nextTh = th.nextElementSibling;
                    nextWidth = nextTh ? nextTh.offsetWidth : 0;

                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                    document.body.style.cursor = 'col-resize';
                    document.body.style.userSelect = 'none';
                }

                function setColWidth(colIdx, width) {
                    var newW = Math.max(30, width);
                    var rows = table.querySelectorAll('tr');
                    rows.forEach(function(row) {
                        var cell = row.children[colIdx];
                        if (cell) {
                            cell.style.width = newW + 'px';
                            cell.style.minWidth = newW + 'px';
                            cell.style.maxWidth = newW + 'px';
                        }
                    });
                }

                function onMouseMove(e) {
                    var diff = e.clientX - startX;
                    var newWidth = Math.max(40, startWidth + diff + 0.5 | 0);
                    var minNext = 30;

                    // 如果右侧有相邻列，调整右侧列宽度来补偿
                    if (ths[index + 1]) {
                        var newNext = nextWidth - diff;
                        if (newNext < minNext) {
                            // 右侧列已到最小宽度，限制当前列继续拖动
                            newWidth = startWidth + (nextWidth - minNext);
                        }
                        setColWidth(index + 1, Math.max(minNext, newNext));
                    }

                    setColWidth(index, newWidth);
                }

                function onMouseUp() {
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                    document.body.style.cursor = '';
                    document.body.style.userSelect = '';
                }

                handle.addEventListener('mousedown', onMouseDown);
            });
        },

        /** 初始化单个表格 */
        init: function(table) {
            if (!table) return;
            this.enableResize(table);
        },

        /** 自动扫描并初始化所有 .data-table 内的表格 */
        autoInit: function() {
            var self = this;
            var containers = document.querySelectorAll('.data-table');
            containers.forEach(function(container) {
                var table = container.querySelector('table');
                if (table) self.init(table);
            });
        }
    };

    // 暴露到全局
    global.TableComponent = TableComponent;

    // DOM ready 自动扫描
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            TableComponent.autoInit();
        });
    } else {
        TableComponent.autoInit();
    }

})(window);
