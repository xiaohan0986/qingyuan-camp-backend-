<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL); ini_set('display_errors', 0);
require_once __DIR__.'/../../config/config.php';
require_once __DIR__.'/../../includes/Database.php';
$db = Database::getInstance();

$status = $_GET['status'] ?? '';
$page = max(1,intval($_GET['page']??1)); $pageSize=20;
$where = ['1=1']; $p=[];
if($status!==''){$where[]='status=?';$p[]=$status;}
$wc = implode(' AND ',$where);
$total = $db->fetchOne("SELECT COUNT(*) as c FROM seckill_activities WHERE {$wc}",$p)['c']??0;
$offset = ($page-1)*$pageSize;
$list = $db->fetchAll("SELECT s.*, p.name as product_name FROM seckill_activities s LEFT JOIN products p ON s.product_id=p.id WHERE {$wc} ORDER BY s.sort DESC, s.id DESC LIMIT {$pageSize} OFFSET {$offset}",$p);

if($_SERVER['REQUEST_METHOD']==='POST'){
    $data = [
        'name'=>trim($_POST['name']??''),'product_id'=>intval($_POST['product_id']??0),
        'image'=>trim($_POST['image']??''),'seckill_price'=>floatval($_POST['seckill_price']??0),
        'original_price'=>floatval($_POST['original_price']??0),'stock'=>intval($_POST['stock']??0),
        'limit_buy'=>intval($_POST['limit_buy']??1),'start_time'=>$_POST['start_time']?:null,
        'end_time'=>$_POST['end_time']?:null,'sort'=>intval($_POST['sort']??0),'status'=>isset($_POST['status'])?1:0,
        'description'=>trim($_POST['description']??''),
    ];
    $id = intval($_POST['id']??0);
    if($id>0){ $db->update('seckill_activities',$data,'id=?',[$id]); $msg='更新成功'; }
    else { $data['sold']=0; $data['created_at']=date('Y-m-d H:i:s'); $db->insert('seckill_activities',$data); $msg='创建成功'; }
    header('Location:seckill_manage.php?msg='.urlencode($msg)); exit;
}
if(($a=$_GET['action']??'')&&($cid=intval($_GET['id']??0))){
    if($a==='toggle'){ $c=$db->fetchOne('SELECT status FROM seckill_activities WHERE id=?',[$cid]); $db->execute ? $db->query("UPDATE seckill_activities SET status=? WHERE id=?",[$c['status']?0:1,$cid]) : null; }
    if($a==='delete'){ $db->delete('seckill_activities','id=?',[$cid]); $db->delete('seckill_orders','activity_id=?',[$cid]); }
    header('Location:seckill_manage.php?msg='.urlencode($a==='toggle'?'已切换':'已删除')); exit;
}
$msg = $_GET['msg'] ?? '';
$products = $db->fetchAll("SELECT id,name,price,images FROM products WHERE status=1 ORDER BY id DESC LIMIT 200");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><title>秒杀管理 - 后台</title>
<link rel="stylesheet" href="../assets/css/admin.min.css?v=<?= time() ?>">
<style>
.content-wrapper{padding:24px}.card{background:white;border-radius:12px;padding:20px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.card-title{font-size:18px;font-weight:600}
table{width:100%;border-collapse:collapse}th,td{padding:10px 12px;text-align:left;border-bottom:1px solid #f0f0f0;font-size:14px}th{background:#fafafa;font-weight:600}
.btn{padding:6px 14px;border:none;border-radius:6px;font-size:13px;cursor:pointer}.btn-primary{background:#1890ff;color:white}.btn-danger{background:#ff4d4f;color:white}.btn-default{background:#f5f5f5;color:#666;border:1px solid #d9d9d9}.btn-sm{padding:4px 10px;font-size:12px}
.tag{padding:2px 8px;border-radius:4px;font-size:12px}.tag-on{background:#f6ffed;color:#52c41a}.tag-off{background:#fafafa;color:#8c8c8c}
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1000;justify-content:center;align-items:center}.modal-overlay.show{display:flex}
.modal{background:white;border-radius:12px;padding:24px;width:650px;max-width:95%;max-height:85vh;overflow-y:auto}
.modal h3{margin:0 0 16px}.form-group{margin-bottom:12px}.form-group label{display:block;margin-bottom:4px;font-size:13px;color:#666}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:13px;box-sizing:border-box}
.form-row{display:flex;gap:12px}.form-row .form-group{flex:1}.form-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:18px}
.alert{padding:10px 16px;border-radius:6px;margin-bottom:16px}.alert-success{background:#f6ffed;color:#52c41a;border:1px solid #b7eb8f}
.status-running{background:#fff2e8;color:#fa8c16}.status-done{background:#f0f0f0;color:#8c8c8c}.status-pending{background:#e6f7ff;color:#1890ff}
</style>
</head>
<body class="admin-body">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/header.php'; ?>
<div class="content-wrapper">
<?php if($msg): ?><div class="alert alert-success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<div class="card">
<div class="card-header"><div class="card-title">秒杀活动管理</div><button class="btn btn-primary" onclick="openModal(0)">+ 新增活动</button></div>
<div style="display:flex;gap:10px;margin-bottom:16px">
<a href="seckill_manage.php" class="btn btn-sm <?=$status===''?'btn-primary':'btn-default'?>">全部</a>
<a href="?status=1" class="btn btn-sm <?=$status==='1'?'btn-primary':'btn-default'?>">进行中</a>
<a href="?status=0" class="btn btn-sm <?=$status==='0'?'btn-primary':'btn-default'?>">已关闭</a>
</div>
<table><thead><tr><th>ID</th><th>活动名称</th><th>商品</th><th>秒杀价</th><th>库存</th><th>已售</th><th>时间</th><th>状态</th><th>操作</th></tr></thead>
<tbody>
<?php $now = date('Y-m-d H:i:s'); foreach($list as $r): 
    $timeStatus = $r['status'] ? ($now<$r['start_time']?'pending':($now>$r['end_time']?'done':'running')) : 'off';
?>
<tr>
<td><?=$r['id']?></td>
<td><strong><?=htmlspecialchars($r['name'])?></strong></td>
<td><?=htmlspecialchars($r['product_name']??$r['product_id'])?></td>
<td style="color:#ff4d4f;font-weight:600">¥<?=$r['seckill_price']?></td>
<td><?=$r['stock']?></td>
<td><?=$r['sold']?></td>
<td style="font-size:12px"><?=substr($r['start_time']??'',0,16)?>~<br><?=substr($r['end_time']??'',0,16)?></td>
<td><span class="tag <?=$timeStatus==='running'?'tag-on':($timeStatus==='pending'?'status-pending':($timeStatus==='done'?'status-done':'tag-off'))?>">
<?=['off'=>'关闭','pending'=>'未开始','running'=>'进行中','done'=>'已结束'][$timeStatus]?></span></td>
<td>
<button class="btn btn-default btn-sm" onclick="openModal(<?=$r['id']?>)">编辑</button>
<a href="?action=toggle&id=<?=$r['id']?>" class="btn btn-default btn-sm" onclick="return confirm('确定?')"><?=$r['status']?'关闭':'开启'?></a>
<a href="?action=delete&id=<?=$r['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('确定删除?')">删除</a>
</td></tr>
<?php endforeach; if(empty($list)): ?><tr><td colspan="9" style="text-align:center;padding:40px;color:#8c8c8c">暂无秒杀活动</td></tr><?php endif; ?>
</tbody></table>
</div></div></div>

<div class="modal-overlay" id="modal"><div class="modal"><h3 id="modaltitle">新增秒杀活动</h3>
<form method="post"><input type="hidden" name="id" id="eid" value="0">
<div class="form-group"><label>活动名称 *</label><input name="name" id="ename" required></div>
<div class="form-group"><label>商品 *</label><select name="product_id" id="epid"><option value="">请选择</option><?php foreach($products as $p): ?><option value="<?=$p['id']?>">#<?=$p['id']?> <?=htmlspecialchars($p['name'])?> (¥<?=$p['price']?>)</option><?php endforeach; ?></select></div>
<div class="form-row">
<div class="form-group"><label>秒杀价 *</label><input type="number" name="seckill_price" id="eprice" step="0.01" required></div>
<div class="form-group"><label>原价</label><input type="number" name="original_price" id="eoprice" step="0.01"></div>
</div>
<div class="form-row">
<div class="form-group"><label>秒杀库存 *</label><input type="number" name="stock" id="estock" min="1" required></div>
<div class="form-group"><label>每人限购</label><input type="number" name="limit_buy" id="elimit" value="1" min="1"></div>
</div>
<div class="form-row">
<div class="form-group"><label>开始时间 *</label><input type="datetime-local" name="start_time" id="est" required></div>
<div class="form-group"><label>结束时间 *</label><input type="datetime-local" name="end_time" id="eet" required></div>
</div>
<div class="form-group"><label>活动描述</label><textarea name="description" id="edesc" rows="2"></textarea></div>
<div class="form-group"><label><input type="checkbox" name="status" id="estatus" checked value="1"> 开启活动</label></div>
<div class="form-actions"><button type="button" class="btn btn-default" onclick="document.getElementById('modal').classList.remove('show')">取消</button><button type="submit" class="btn btn-primary">保存</button></div>
</form></div></div>

<script>
var data = <?=json_encode($list,JSON_UNESCAPED_UNICODE)?>;
function openModal(id){
    document.getElementById('eid').value=id;
    if(id>0){
        document.getElementById('modaltitle').textContent='编辑秒杀活动';
        var d=data.find(function(r){return r.id==id});
        if(d){
            document.getElementById('ename').value=d.name||'';document.getElementById('epid').value=d.product_id;
            document.getElementById('eprice').value=d.seckill_price;document.getElementById('eoprice').value=d.original_price||'';
            document.getElementById('estock').value=d.stock;document.getElementById('elimit').value=d.limit_buy||1;
            document.getElementById('est').value=(d.start_time||'').replace(' ','T');
            document.getElementById('eet').value=(d.end_time||'').replace(' ','T');
            document.getElementById('edesc').value=d.description||'';
            document.getElementById('estatus').checked=d.status==1;
        }
    }else{
        document.getElementById('modaltitle').textContent='新增秒杀活动';
        document.querySelector('#modal form').reset();document.getElementById('estatus').checked=true;
    }
    document.getElementById('modal').classList.add('show');
}
document.getElementById('modal').addEventListener('click',function(e){if(e.target===this)this.classList.remove('show');});
</script>
</div><!-- /content-wrapper -->
</div><!-- /main-content -->
</body>
</html>
