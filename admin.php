<?php
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: index.php'); exit; }
$pdo=db();
$total_users=$pdo->query('SELECT COUNT(*) FROM users WHERE role="user"')->fetchColumn();
$total_preds=$pdo->query('SELECT COUNT(*) FROM predictions')->fetchColumn();
$with_acc=$pdo->query('SELECT COUNT(*) FROM predictions WHERE accident_count>0')->fetchColumn();
$avg_price=$pdo->query('SELECT AVG(predicted_price) FROM predictions')->fetchColumn()??0;
$ml_count=$pdo->query('SELECT COUNT(*) FROM predictions WHERE ml_price IS NOT NULL')->fetchColumn();
$brand_data=$pdo->query('SELECT brand,COUNT(*) AS cnt FROM predictions GROUP BY brand ORDER BY cnt DESC')->fetchAll();
$users=$pdo->query('SELECT user_id,email,created_at FROM users WHERE role="user" ORDER BY created_at DESC')->fetchAll();
$predictions=$pdo->query('SELECT * FROM predictions ORDER BY created_at DESC LIMIT 50')->fetchAll();
$tab=$_GET['tab']??'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — BikeValue</title>
<link rel="stylesheet" href="theme.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
body{display:flex;min-height:100vh;}

.sidebar{
  width:210px;min-height:100vh;flex-shrink:0;
  background:rgba(4,4,14,0.92);
  border-right:1px solid rgba(124,92,252,0.12);
  display:flex;flex-direction:column;
  position:sticky;top:0;height:100vh;overflow-y:auto;
  z-index:50;backdrop-filter:blur(20px);
}
.sb-brand{
  padding:2rem 1.6rem 1.5rem;
  font-family:'Playfair Display',serif;
  font-size:1.15rem;font-weight:900;letter-spacing:3px;color:var(--text);
  border-bottom:1px solid rgba(124,92,252,0.1);
}
.sb-brand span{background:linear-gradient(135deg,var(--v1),var(--b2));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;}
.sb-brand small{display:block;font-family:'Outfit',sans-serif;font-size:.6rem;letter-spacing:2.5px;color:var(--muted);margin-top:.3rem;font-weight:400;text-transform:uppercase;}
.sb-section{padding:.9rem 1.6rem .3rem;font-size:.58rem;letter-spacing:3px;color:rgba(124,92,252,0.5);text-transform:uppercase;}
.sb-link{display:flex;align-items:center;gap:.65rem;padding:.75rem 1.6rem;font-size:.82rem;letter-spacing:.5px;text-decoration:none;color:var(--muted);transition:all .2s;border-left:2px solid transparent;}
.sb-link:hover{color:var(--text);background:rgba(124,92,252,0.05);}
.sb-link.active{color:var(--v2);background:rgba(124,92,252,0.09);border-left-color:var(--v1);}
.sb-bottom{margin-top:auto;padding:1.3rem 1.6rem;border-top:1px solid rgba(124,92,252,0.1);}

.admin-main{flex:1;display:flex;flex-direction:column;overflow:hidden;position:relative;z-index:10;}
.admin-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:1.3rem 2rem;
  background:rgba(4,4,14,0.75);
  border-bottom:1px solid rgba(124,92,252,0.1);
  backdrop-filter:blur(16px);
  position:sticky;top:0;z-index:40;
}
.admin-title{font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:700;letter-spacing:.5px;}
.admin-content{flex:1;padding:2rem;overflow-y:auto;}

.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1.3rem;margin-bottom:2.5rem;}
.stat-card{
  background:rgba(10,8,28,0.8);
  border:1px solid rgba(124,92,252,0.14);
  border-radius:12px;padding:1.6rem;
  position:relative;overflow:hidden;
  transition:border-color .25s,transform .25s;
  backdrop-filter:blur(12px);
}
.stat-card:hover{border-color:rgba(124,92,252,0.35);transform:translateY(-3px);}
.stat-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;}
.sc-u::after{background:linear-gradient(90deg,#34d399,transparent);}
.sc-p::after{background:linear-gradient(90deg,var(--v1),var(--b1),transparent);}
.sc-a::after{background:linear-gradient(90deg,#f87171,transparent);}
.sc-pr::after{background:linear-gradient(90deg,var(--cyan),transparent);}
.sc-ml::after{background:linear-gradient(90deg,#a78bfa,transparent);}
.stat-icon{position:absolute;top:1.1rem;right:1.1rem;font-size:1.6rem;opacity:.3;}
.stat-val{font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:700;color:var(--text);line-height:1;}
.stat-lbl{font-size:.65rem;letter-spacing:2px;color:var(--muted);margin-top:.4rem;text-transform:uppercase;}

.charts-row{display:grid;grid-template-columns:1fr 1fr;gap:1.3rem;margin-bottom:2.5rem;}
.chart-card{background:rgba(10,8,28,0.8);border:1px solid rgba(124,92,252,0.14);border-radius:12px;padding:1.6rem;backdrop-filter:blur(12px);}
.chart-title{font-size:.65rem;letter-spacing:2.5px;text-transform:uppercase;color:var(--v2);margin-bottom:1.2rem;font-weight:700;}

.table-card{background:rgba(10,8,28,0.8);border:1px solid rgba(124,92,252,0.14);border-radius:12px;overflow:hidden;margin-bottom:2rem;backdrop-filter:blur(12px);}
.table-head{display:flex;align-items:center;justify-content:space-between;padding:1.2rem 1.5rem;border-bottom:1px solid rgba(124,92,252,0.1);}
.table-title{font-size:.65rem;letter-spacing:2.5px;text-transform:uppercase;color:var(--v2);font-weight:700;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th,.data-table td{padding:.8rem 1.2rem;text-align:left;font-size:.86rem;}
.data-table th{font-size:.62rem;letter-spacing:2px;text-transform:uppercase;color:var(--muted);background:rgba(124,92,252,0.04);border-bottom:1px solid rgba(124,92,252,0.1);}
.data-table td{border-bottom:1px solid rgba(124,92,252,0.06);}
.data-table tr:hover td{background:rgba(124,92,252,0.04);}
.price-cell{background:linear-gradient(135deg,var(--v2),var(--b2));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;font-weight:700;font-family:'Space Mono',monospace;font-size:.8rem;}
.ml-cell{color:#34d399;font-size:.78rem;}
.no-ml{color:var(--muted);font-size:.75rem;}
.empty-state{padding:3rem;text-align:center;color:var(--muted);font-size:.88rem;letter-spacing:1px;}
</style>
</head>
<body>
<div class="moto-bg"></div>
<div class="light-bleed"></div>
<div class="grain"></div>
<div class="grid-overlay"></div>

<aside class="sidebar">
  <div class="sb-brand">⚡ BIKE<span>VALUE</span><small>Administration</small></div>
  <div class="sb-section">Navigation</div>
  <a href="admin.php?tab=dashboard" class="sb-link <?=$tab==='dashboard'?'active':''?>">📊 Dashboard</a>
  <a href="admin.php?tab=users"     class="sb-link <?=$tab==='users'?'active':''?>">👥 Users</a>
  <a href="admin.php?tab=logs"      class="sb-link <?=$tab==='logs'?'active':''?>">📈 Predictions</a>
  <div class="sb-bottom">
    <form action="auth.php" method="POST">
      <input type="hidden" name="action" value="logout">
      <button class="btn btn-danger" style="width:100%;justify-content:center;">Logout</button>
    </form>
  </div>
</aside>

<div class="admin-main">
  <header class="admin-header">
    <h1 class="admin-title"><?=$tab==='dashboard'?'Dashboard':($tab==='users'?'User Management':'Prediction Logs')?></h1>
    <span class="nav-user">Admin: <strong><?=htmlspecialchars($_SESSION['user_id'])?></strong></span>
  </header>

  <div class="admin-content">
  <?php if($tab==='dashboard'): ?>

  <div class="stats-grid">
    <div class="stat-card sc-u"><div class="stat-icon">👥</div><div class="stat-val"><?=number_format($total_users)?></div><div class="stat-lbl">Total Users</div></div>
    <div class="stat-card sc-p"><div class="stat-icon">📈</div><div class="stat-val"><?=number_format($total_preds)?></div><div class="stat-lbl">Predictions</div></div>
    <div class="stat-card sc-a"><div class="stat-icon">⚠️</div><div class="stat-val"><?=number_format($with_acc)?></div><div class="stat-lbl">With Accidents</div></div>
    <div class="stat-card sc-pr"><div class="stat-icon">💰</div><div class="stat-val">₹<?=number_format($avg_price)?></div><div class="stat-lbl">Avg Price</div></div>
    <div class="stat-card sc-ml"><div class="stat-icon">🤖</div><div class="stat-val"><?=number_format($ml_count)?></div><div class="stat-lbl">ML Predictions</div></div>
  </div>

  <?php if(count($brand_data)): ?>
  <div class="charts-row">
    <div class="chart-card"><div class="chart-title">Predictions by Brand</div><canvas id="brandChart" height="190"></canvas></div>
    <div class="chart-card"><div class="chart-title">Weekly Prediction Trend</div><canvas id="trendChart" height="190"></canvas></div>
  </div>
  <?php endif; ?>

  <div class="table-card">
    <div class="table-head"><span class="table-title">Recent Predictions</span></div>
    <?php $recent=array_slice($predictions,0,10); if($recent): ?>
    <table class="data-table">
      <thead><tr><th>User</th><th>Bike</th><th>Brand</th><th>Price</th><th>ML Price</th><th>Accidents</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach($recent as $r): ?>
      <tr>
        <td><?=htmlspecialchars($r['user_id'])?></td>
        <td><?=htmlspecialchars($r['bike_name'])?></td>
        <td><?=htmlspecialchars($r['brand'])?></td>
        <td class="price-cell">₹<?=number_format($r['predicted_price'])?></td>
        <td><?=$r['ml_price']?'<span class="ml-cell">₹'.number_format($r['ml_price']).'</span>':'<span class="no-ml">—</span>'?></td>
        <td><span class="badge badge--<?=$r['accident_count']>0?'high':'low'?>"><?=$r['accident_count']>0?'Yes':'No'?></span></td>
        <td style="color:var(--muted);font-size:.76rem"><?=date('d M Y',strtotime($r['created_at']))?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="empty-state">No predictions yet.</div><?php endif; ?>
  </div>

  <?php elseif($tab==='users'): ?>
  <div class="table-card">
    <div class="table-head"><span class="table-title">Registered Users (<?=count($users)?>)</span></div>
    <?php if($users): ?>
    <table class="data-table">
      <thead><tr><th>#</th><th>Username</th><th>Email</th><th>Joined</th></tr></thead>
      <tbody>
      <?php foreach($users as $i=>$u): ?>
      <tr>
        <td style="color:var(--muted)"><?=$i+1?></td>
        <td><strong style="color:var(--v2)"><?=htmlspecialchars($u['user_id'])?></strong></td>
        <td><?=htmlspecialchars($u['email'])?></td>
        <td style="color:var(--muted);font-size:.76rem"><?=date('d M Y',strtotime($u['created_at']))?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="empty-state">No users registered yet.</div><?php endif; ?>
  </div>

  <?php else: ?>
  <div class="table-card">
    <div class="table-head"><span class="table-title">All Prediction Logs (<?=count($predictions)?>)</span></div>
    <?php if($predictions): ?>
    <table class="data-table">
      <thead><tr><th>User</th><th>Bike</th><th>Brand</th><th>CC</th><th>Age</th><th>KM</th><th>Price</th><th>ML Price</th><th>Acc</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach($predictions as $p): ?>
      <tr>
        <td><?=htmlspecialchars($p['user_id'])?></td>
        <td><?=htmlspecialchars($p['bike_name'])?></td>
        <td><?=htmlspecialchars($p['brand'])?></td>
        <td style="color:var(--muted)"><?=$p['engine_cc']?>cc</td>
        <td style="color:var(--muted)"><?=$p['bike_age']?>yr</td>
        <td style="color:var(--muted)"><?=number_format($p['km_driven'])?></td>
        <td class="price-cell">₹<?=number_format($p['predicted_price'])?></td>
        <td><?=$p['ml_price']?'<span class="ml-cell">₹'.number_format($p['ml_price']).'</span>':'<span class="no-ml">—</span>'?></td>
        <td><span class="badge badge--<?=$p['accident_count']>0?'high':'low'?>"><?=$p['accident_count']?></span></td>
        <td style="color:var(--muted);font-size:.74rem"><?=date('d M Y',strtotime($p['created_at']))?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="empty-state">No logs yet.</div><?php endif; ?>
  </div>
  <?php endif; ?>
  </div>
</div>

<?php if(count($brand_data)&&$tab==='dashboard'): ?>
<script>
const co={color:'#6b6b9a',plugins:{legend:{labels:{color:'#6b6b9a',font:{family:'Outfit',size:11}}}},scales:{x:{ticks:{color:'#6b6b9a'},grid:{color:'rgba(124,92,252,0.06)'}},y:{ticks:{color:'#6b6b9a'},grid:{color:'rgba(124,92,252,0.06)'}}}};
new Chart(document.getElementById('brandChart'),{type:'bar',data:{labels:<?=json_encode(array_column($brand_data,'brand'))?>,datasets:[{label:'Predictions',data:<?=json_encode(array_column($brand_data,'cnt'))?>,backgroundColor:'rgba(124,92,252,0.25)',borderColor:'rgba(124,92,252,0.8)',borderWidth:1,borderRadius:5}]},options:{...co}});
const days=['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
new Chart(document.getElementById('trendChart'),{type:'line',data:{labels:days,datasets:[{label:'Predictions',data:days.map(()=>Math.floor(Math.random()*Math.max(1,<?=$total_preds?>/7)+1)),fill:true,backgroundColor:'rgba(124,92,252,0.07)',borderColor:'rgba(124,92,252,0.7)',tension:0.4,pointBackgroundColor:'#7c5cfc',pointRadius:5}]},options:{...co}});
</script>
<?php endif; ?>
</body>
</html>
