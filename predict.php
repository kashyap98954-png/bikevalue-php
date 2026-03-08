<?php
// ═══════════════════════════════════════════════
//  BikeValue — Predict Page
// ═══════════════════════════════════════════════
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: index.php'); exit;
}
require_once 'config.php';

$user_id = $_SESSION['user_id'];
$prediction = null;
$pv = [];  // previous values for form refill

// ── Bike data ──────────────────────────────────
$brands = ['Royal Enfield','Yamaha','Honda','Bajaj','TVS','KTM','Suzuki','Kawasaki','Hero','Triumph'];
$cities = ['Mumbai','Delhi','Bangalore','Chennai','Hyderabad','Pune','Kolkata','Ahmedabad','Jaipur','Lucknow','Chandigarh','Kochi'];
$acc_types = ['none','minor','major','severe'];

$bike_names_by_brand = [
    'Royal Enfield' => ['Classic 350','Classic 500','Bullet 350','Bullet 500','Thunderbird 350','Thunderbird 500','Himalayan','Meteor 350','Hunter 350'],
    'Yamaha'        => ['FZ-S V3','FZ25','R15 V4','MT-15','R3','FZS-FI','Fazer 25','YZF R15','Ray ZR','Fascino','Alpha','SZ-RR'],
    'Honda'         => ['CB Shine','CB Hornet 160R','CB350','CB500F','CBR650R','Activa 6G','Unicorn','Livo','SP 125','Shine SP','CB200X','NX200'],
    'Bajaj'         => ['Pulsar 150','Pulsar 180','Pulsar 220F','Pulsar NS200','Pulsar RS200','Dominar 400','Avenger 220','CT100','Platina','Pulsar N250','Pulsar F250','Dominar 250'],
    'TVS'           => ['Apache RTR 160','Apache RTR 200','Apache RR 310','Jupiter','NTorq 125','Raider 125','Ronin','iQube Electric','Star City+','Sport','HLX 125','Radeon'],
    'KTM'           => ['Duke 200','Duke 250','Duke 390','RC 200','RC 390','Adventure 250','Adventure 390','Duke 125','RC 125'],
    'Suzuki'        => ['Gixxer SF','Gixxer 250','V-Strom 650','Access 125','Burgman Street','Intruder 150','Avenis 125'],
    'Kawasaki'      => ['Ninja 300','Ninja 400','Ninja 650','Z650','Z900','Versys 650','W175','Vulcan S','Z H2','Ninja ZX-10R'],
    'Hero'          => ['Splendor Plus','Passion Pro','HF Deluxe','Glamour','Xtreme 160R','Xpulse 200','Maestro Edge','Destini 125','Super Splendor','Vida V1'],
    'Triumph'       => ['Tiger 660','Trident 660'],
];

// ── Handle POST (prediction) ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['brand'])) {

    $pv = [
        'brand'            => trim($_POST['brand']            ?? ''),
        'bike_name'        => strtolower(trim($_POST['bike_name'] ?? '')),
        'engine_capacity'  => (int)($_POST['engine_capacity'] ?? 0),
        'age'              => (int)($_POST['age']             ?? 0),
        'owner'            => max(1,(int)($_POST['owner']     ?? 1)),
        'kms_driven'       => (int)($_POST['kms_driven']      ?? 0),
        'city'             => strtolower(trim($_POST['city']  ?? '')),
        'accident_count'   => max(0,(int)($_POST['accident_count'] ?? 0)),
        'accident_history' => in_array($_POST['accident_history']??'none', $acc_types)
                                ? $_POST['accident_history'] : 'none',
    ];
    if ($pv['accident_count'] === 0) $pv['accident_history'] = 'none';

    // ── Call ML API ────────────────────────────
    $ml_result = callMLApi($pv, $ml_api_url);

    // ── Fallback formula (if ML unreachable) ───
    $formula_price = calcFormulaPrice($pv);

    // ── Final price ────────────────────────────
    $final_price = $ml_result['success'] ? $ml_result['ml_price'] : $formula_price;
    $ml_adjusted = $ml_result['ml_adjusted'] ?? null;
    $has_accident = $pv['accident_count'] > 0;
    $ml_impact    = ($has_accident && $ml_adjusted) ? ($final_price - $ml_adjusted) : null;

    // ── Save to DB ─────────────────────────────
    try {
        $stmt = $pdo->prepare('INSERT INTO predictions
            (user_id, bike_name, brand, engine_capacity, age, owner, kms_driven, city,
             accident_count, accident_history, ml_price, ml_adjusted, formula_price, final_price, ml_used)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $user_id,
            $pv['bike_name'],
            $pv['brand'],
            $pv['engine_capacity'],
            $pv['age'],
            $pv['owner'],
            $pv['kms_driven'],
            $pv['city'],
            $pv['accident_count'],
            $pv['accident_history'],
            $ml_result['success'] ? $ml_result['ml_price'] : null,
            $ml_adjusted,
            $formula_price,
            $final_price,
            $ml_result['success'] ? 1 : 0,
        ]);
    } catch (Exception $e) { /* non-fatal */ }

    // ── Build breakdown params ──────────────────
    $prediction = [
        'bike_name'    => $pv['bike_name'],
        'brand'        => $pv['brand'],
        'final_price'  => $final_price,
        'ml_price'     => $ml_result['success'] ? $ml_result['ml_price'] : null,
        'ml_adjusted'  => $ml_adjusted,
        'ml_impact'    => $ml_impact,
        'has_accident' => $has_accident,
        'ml_error'     => $ml_result['success'] ? null : ($ml_result['error'] ?? 'ML API offline — formula used'),
        'debug_payload'=> $ml_result['debug_payload'] ?? '',
        'debug_response'=> $ml_result['debug_response'] ?? '',
        'params'       => buildParams($pv, $final_price),
    ];
}

// ── Functions ──────────────────────────────────

function callMLApi(array $pv, string $base_url): array {
    $url = rtrim($base_url, '/') . '/predict';
    $payload = json_encode([
        'brand'            => $pv['brand'],
        'bike_name'        => $pv['bike_name'],
        'engine_capacity'  => $pv['engine_capacity'],
        'age'              => $pv['age'],
        'owner'            => $pv['owner'],
        'kms_driven'       => $pv['kms_driven'],
        'city'             => $pv['city'],
        'accident_count'   => $pv['accident_count'],
        'accident_history' => $pv['accident_history'],
    ]);

    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/json\r\nAccept: application/json\r\n",
        'content'       => $payload,
        'timeout'       => 8,
        'ignore_errors' => true,
    ]]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return ['success' => false, 'error' => "ML API unreachable at {$url}",
                'debug_payload' => $payload, 'debug_response' => '(no response)'];
    }
    $data = json_decode($raw, true);
    if (!$data || empty($data['success'])) {
        return ['success' => false, 'error' => $data['error'] ?? 'ML API returned error',
                'debug_payload' => $payload, 'debug_response' => $raw];
    }
    $data['debug_payload']  = $payload;
    $data['debug_response'] = $raw;
    return $data;
}

function calcFormulaPrice(array $pv): int {
    $mul = ['triumph'=>1.35,'ktm'=>1.12,'kawasaki'=>1.1,'royal enfield'=>1.15,
            'yamaha'=>1.05,'honda'=>1.02,'suzuki'=>1.04,'bajaj'=>0.97,'tvs'=>0.96,'hero'=>0.92];
    $city_bonus = ['mumbai'=>8500,'delhi'=>6500,'bangalore'=>7200,'chennai'=>5800,
                   'hyderabad'=>6200,'pune'=>7100,'kolkata'=>5500,'ahmedabad'=>6800,
                   'jaipur'=>5900,'lucknow'=>5300,'chandigarh'=>7800,'kochi'=>6100];
    $brand = strtolower($pv['brand']);
    $base  = $pv['engine_capacity'] * 120 * ($mul[$brand] ?? 1.0);
    $base  = $base * pow(0.85, $pv['age']);
    $base -= $pv['kms_driven'] / 100;
    $base  = $base * pow(0.95, $pv['owner'] - 1);
    $base  = max($base, 50000);
    $base += $city_bonus[strtolower($pv['city'])] ?? 0;
    return (int)round($base);
}

function buildParams(array $pv, int $price): array {
    $impacts = [
        ['label'=>'Brand',           'val'=>$pv['brand'],             'impact'=>'Medium'],
        ['label'=>'Model',           'val'=>ucwords($pv['bike_name']), 'impact'=>'Medium'],
        ['label'=>'Engine (cc)',     'val'=>$pv['engine_capacity'].' cc','impact'=>'High'],
        ['label'=>'Age',             'val'=>$pv['age'].' years',      'impact'=>$pv['age']<=2?'Low':'High'],
        ['label'=>'Owner No.',       'val'=>'Owner '.$pv['owner'],    'impact'=>$pv['owner']===1?'Low':'High'],
        ['label'=>'Kms Driven',      'val'=>number_format($pv['kms_driven']).' km','impact'=>$pv['kms_driven']<30000?'Low':'High'],
        ['label'=>'City',            'val'=>ucfirst($pv['city']),     'impact'=>'Medium'],
        ['label'=>'Accidents',       'val'=>$pv['accident_count'],    'impact'=>$pv['accident_count']>0?'High':'Low'],
        ['label'=>'Accident Severity','val'=>ucfirst($pv['accident_history']),'impact'=>$pv['accident_history']==='none'?'Low':'High'],
    ];
    return $impacts;
}

// ── Form pre-fill helpers ──────────────────────
$pBrand    = htmlspecialchars($pv['brand']            ?? '');
$pCC       = htmlspecialchars($pv['engine_capacity']  ?? '');
$pAge      = htmlspecialchars($pv['age']              ?? '');
$pOwner    = htmlspecialchars($pv['owner']            ?? 1);
$pKms      = htmlspecialchars($pv['kms_driven']       ?? '');
$pCity     = htmlspecialchars($pv['city']             ?? '');
$pAccCnt   = htmlspecialchars($pv['accident_count']   ?? 0);
$pAccHist  = htmlspecialchars($pv['accident_history'] ?? 'none');
$pBikeName = htmlspecialchars($pv['bike_name']        ?? '');
$isEdit    = !empty($pv);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $prediction ? 'Valuation Result' : 'Predict Value' ?> — BikeValue</title>
<!-- Your existing theme.css goes here -->
<link rel="stylesheet" href="theme.css">
</head>
<body>
<?php include 'nav_partial.php'; ?>

<div id="page-predict" class="page active" style="padding-top:62px">
  <div class="predict-wrap">

    <?php if (!$prediction): ?>
    <!-- ══ FORM VIEW ══ -->
    <div class="page-header">
      <h1><?= $isEdit ? 'Refine Your Inputs' : 'Predict Your Bike\'s Value' ?></h1>
      <p><?= $isEdit ? 'Previous inputs restored — adjust and re-run' : 'Fill every field for maximum ML accuracy' ?></p>
    </div>

    <form action="predict.php" method="POST">
      <div class="form-card">
        <div class="card-section-title">Bike Details</div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Brand</label>
            <select name="brand" id="brandSelect" class="form-input form-select" required onchange="updateBikeNames()">
              <option value="" disabled <?= !$pBrand?'selected':'' ?>>Select Brand</option>
              <?php foreach ($brands as $b): ?>
                <option <?= $pBrand===htmlspecialchars($b)?'selected':'' ?>><?= $b ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Model</label>
            <select name="bike_name_select" id="bikeNameSelect" class="form-input form-select" onchange="onModelSelect(); autoCC()">
              <option value="">Select from list or type below</option>
            </select>
            <input type="text" name="bike_name" id="bikeNameInput" class="form-input"
                   placeholder="Or enter custom model" value="<?= $pBikeName ?>" required
                   style="margin-top:.5rem" oninput="autoCC()">
            <div id="model-hint" class="form-hint"></div>
          </div>
          <div class="form-group">
            <label class="form-label">Engine (cc)</label>
            <input type="number" name="engine_capacity" id="engineCC" class="form-input"
                   placeholder="Auto-filled or enter manually" min="50" max="3000"
                   value="<?= $pCC ?>" required>
            <div id="cc-hint" class="form-hint"></div>
          </div>
          <div class="form-group">
            <label class="form-label">Bike Age (Years)</label>
            <input type="number" name="age" class="form-input" placeholder="e.g. 3"
                   min="0" max="30" value="<?= $pAge ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Owner Number</label>
            <input type="number" name="owner" class="form-input" placeholder="1 = first owner"
                   min="1" max="5" value="<?= $pOwner ?: 1 ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Kilometers Driven</label>
            <input type="number" name="kms_driven" class="form-input" placeholder="e.g. 15000"
                   min="0" value="<?= $pKms ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">City</label>
            <select name="city" class="form-input form-select" required>
              <option value="" disabled <?= !$pCity?'selected':'' ?>>Select City</option>
              <?php foreach ($cities as $c): ?>
                <option value="<?= strtolower($c) ?>" <?= $pCity===strtolower($c)?'selected':'' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div></div>
        </div>
      </div>

      <div class="form-card">
        <div class="card-section-title">Accident History</div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Number of Accidents</label>
            <input type="number" name="accident_count" id="accCnt" class="form-input"
                   placeholder="0" min="0" value="<?= $pAccCnt ?>" oninput="toggleAcc()">
          </div>
          <div class="form-group" id="accHistGroup" style="display:<?= (int)$pAccCnt>0?'flex':'none' ?>;flex-direction:column">
            <label class="form-label">Accident Severity</label>
            <select name="accident_history" class="form-input form-select">
              <?php foreach ($acc_types as $a): if($a==='none')continue; ?>
                <option value="<?= $a ?>" <?= $pAccHist===$a?'selected':'' ?>><?= ucfirst($a) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div style="text-align:center;margin-top:1.5rem">
        <button type="submit" class="btn btn-fire btn-lg">⚡ Predict Price Now</button>
      </div>
    </form>

    <?php else: ?>
    <!-- ══ RESULT VIEW ══ -->
    <div class="result-wrap">
      <div class="result-header">
        <div class="badge-result">Valuation Complete · Random Forest</div>
        <h2><?= htmlspecialchars(ucwords($prediction['bike_name'])) ?> by <?= htmlspecialchars($prediction['brand']) ?></h2>
      </div>

      <?php if ($prediction['ml_error']): ?>
        <div class="form-error" style="margin-bottom:1.2rem">⚠ <?= htmlspecialchars($prediction['ml_error']) ?></div>
      <?php endif; ?>

      <div class="price-grid">
        <div class="price-box price-box-main">
          <?php if ($prediction['ml_price']): ?>
            <div class="price-lbl" style="color:#7ec8e3">🤖 ML Model Price</div>
          <?php else: ?>
            <div class="price-lbl">Formula Estimate</div>
          <?php endif; ?>
          <div class="price-val">₹<?= number_format($prediction['final_price']) ?></div>
          <div class="price-note">Base market valuation</div>
        </div>

        <?php if ($prediction['has_accident'] && $prediction['ml_adjusted']): ?>
        <div class="price-box price-box-acc">
          <div class="price-lbl">Post-Accident Price</div>
          <div class="price-val" style="color:#e88080">₹<?= number_format($prediction['ml_adjusted']) ?></div>
          <div class="price-note">↓ ₹<?= number_format($prediction['ml_impact']) ?> value loss</div>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($prediction['has_accident'] && $prediction['ml_impact']): ?>
      <div class="acc-impact-row">
        <span class="acc-impact-label">Accident Deduction</span>
        <span class="acc-impact-val">− ₹<?= number_format($prediction['ml_impact']) ?></span>
      </div>
      <?php endif; ?>

      <div class="breakdown-card">
        <div class="bk-title">Parameter Breakdown</div>
        <table class="bk-table">
          <thead><tr><th>Parameter</th><th>Value</th><th>Impact</th></tr></thead>
          <tbody>
          <?php foreach ($prediction['params'] as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['label']) ?></td>
              <td><?= htmlspecialchars($p['val']) ?></td>
              <td><span class="badge-impact bi-<?= strtolower($p['impact']) ?>"><?= $p['impact'] ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="result-actions">
        <a href="predict.php" class="btn btn-outline">← Refine Inputs</a>
        <a href="predict.php?reset=1" class="btn btn-ghost">New Valuation</a>
        <a href="auth.php?action=logout" class="btn btn-danger btn-sm" style="margin-left:auto">Logout</a>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php echo '<script>const bikeNamesByBrand='.json_encode($bike_names_by_brand).';</script>'; ?>
<script>
function updateBikeNames(){
  const brand=document.getElementById('brandSelect').value;
  const sel=document.getElementById('bikeNameSelect');
  sel.innerHTML='<option value="">Select from list or type below</option>';
  if(bikeNamesByBrand[brand]){
    bikeNamesByBrand[brand].forEach(n=>{const o=document.createElement('option');o.value=n.toLowerCase();o.textContent=n;sel.appendChild(o)});
  }
  // Restore if editing
  <?php if ($pBrand): ?>
  const prevModel='<?= addslashes($pBikeName) ?>';
  if(prevModel)for(let o of sel.options)if(o.value===prevModel){o.selected=true;break;}
  <?php endif; ?>
}
function onModelSelect(){
  const v=document.getElementById('bikeNameSelect').value;
  if(v)document.getElementById('bikeNameInput').value=v;
}
// Auto-fill CC
const ccMap={<?php
  $cc_data = ['classic 350'=>350,'classic 500'=>500,'bullet 350'=>346,'himalayan'=>411,'meteor 350'=>349,'hunter 350'=>349,'fz-s v3'=>149,'fz25'=>249,'r15 v4'=>155,'mt-15'=>155,'r3'=>321,'cb shine'=>124,'cb hornet 160r'=>163,'cb350'=>348,'cb500f'=>471,'cbr650r'=>649,'activa 6g'=>109,'unicorn'=>162,'pulsar 150'=>149,'pulsar 220f'=>220,'pulsar ns200'=>199,'dominar 400'=>373,'apache rtr 160'=>159,'apache rtr 200'=>197,'apache rr 310'=>312,'jupiter'=>109,'ntorq 125'=>124,'duke 200'=>199,'duke 250'=>248,'duke 390'=>373,'rc 390'=>373,'gixxer sf'=>155,'gixxer 250'=>249,'v-strom 650'=>645,'ninja 300'=>296,'ninja 400'=>399,'ninja 650'=>649,'z900'=>948,'splendor plus'=>97,'glamour'=>124,'xtreme 160r'=>163,'xpulse 200'=>199,'tiger 660'=>660,'trident 660'=>660];
  echo implode(',', array_map(fn($k,$v)=>"'{$k}':{$v}", array_keys($cc_data), $cc_data));
?>};
function autoCC(){
  const key=document.getElementById('bikeNameInput').value.toLowerCase().trim();
  const hint=document.getElementById('model-hint');
  const ccF=document.getElementById('engineCC');
  if(ccMap[key]){ccF.value=ccMap[key];hint.innerHTML='✓ Auto-filled: <strong>'+ccMap[key]+' cc</strong>';hint.style.color='#7ec8e3';}
  else if(key){hint.textContent='Enter CC manually';hint.style.color='#9a8f7a';}
}
function toggleAcc(){
  const n=parseInt(document.getElementById('accCnt').value)||0;
  document.getElementById('accHistGroup').style.display=n>0?'flex':'none';
}
// Restore brand on edit
<?php if ($pBrand): ?>window.onload=function(){updateBikeNames()};<?php endif; ?>
</script>
</body>
</html>
