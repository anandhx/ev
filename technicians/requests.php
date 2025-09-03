<?php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['technician_id']) || ($_SESSION['user_type'] ?? '') !== 'technician') {
    header('Location: ../login.php');
    exit;
}

$technicianId = (int)$_SESSION['technician_id'];
$db = Database::getInstance();
$flash = $_SESSION['tech_flash'] ?? '';
unset($_SESSION['tech_flash']);

$filter = $_GET['status'] ?? 'all';
$valid = ['all','assigned','in_progress','completed','cancelled'];
if (!in_array($filter,$valid)) $filter = 'all';

$where = " WHERE sr.assigned_technician_id = ?";
$params = [$technicianId];
if ($filter !== 'all') {
    $where .= " AND sr.status = ?";
    $params[] = $filter;
}

$requests = [];
try {
    $stmt = $db->executeQuery(
        "SELECT sr.id, sr.request_type, sr.urgency_level, sr.status, sr.description, sr.total_cost, sr.created_at,
                sr.vehicle_location_lat AS lat, sr.vehicle_location_lng AS lng,
                u.full_name AS user_name, u.phone AS user_phone
         FROM service_requests sr
         JOIN users u ON u.id = sr.user_id
         $where
         ORDER BY FIELD(sr.status,'assigned','in_progress','completed','cancelled'), sr.updated_at DESC",
        $params
    );
    $requests = $stmt->fetchAll();
} catch (Exception $e) {
    $requests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - Technician</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;background:#f5f7fa}
        .topbar{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:1rem 1.5rem;display:flex;justify-content:space-between;align-items:center}
        .nav a{color:#fff;text-decoration:none;margin-left:1rem;background:rgba(255,255,255,0.15);padding:0.5rem .75rem;border-radius:6px}
        .container{padding:1.5rem;max-width:1200px;margin:0 auto}
        .flash{background:#e9f7ef;color:#155724;border:1px solid #c3e6cb;padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;display:flex;justify-content:space-between;align-items:center}
        .filters{display:flex;gap:.5rem;margin-bottom:1rem}
        .filters a{padding:.5rem .75rem;background:#fff;border-radius:6px;text-decoration:none;color:#333;border:1px solid #e5e8ee}
        .card{background:#fff;border-radius:12px;box-shadow:0 5px 15px rgba(0,0,0,.08);padding:1rem;margin-bottom:.75rem}
        .row{display:flex;justify-content:space-between;gap:1rem}
        .badge{padding:.25rem .5rem;border-radius:12px;font-size:.8rem}
        .b-assigned{background:#fff3cd;color:#856404}
        .b-in_progress{background:#d1ecf1;color:#0c5460}
        .b-completed{background:#d4edda;color:#155724}
        .b-cancelled{background:#f8d7da;color:#721c24}
        .actions a, .actions button{color:#fff;text-decoration:none;padding:.35rem .6rem;border-radius:6px;margin-left:.35rem;border:none;cursor:pointer}
        .btn-start{background:#ffc107;color:#212529}
        .btn-complete{background:#28a745}
        .btn-cancel{background:#dc3545}
        .btn-track{background:#667eea}
        .btn-map{background:#17a2b8}
        /* Modal */
        .modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.5)}
        .modal-content{background:#fff;margin:5% auto;padding:1rem;border-radius:12px;max-width:700px;width:92%;position:relative}
        .close{position:absolute;right:12px;top:8px;font-size:1.5rem;cursor:pointer;color:#666}
        #map{width:100%;height:420px;border-radius:10px}
    </style>
</head>
<body>
    <div class="topbar">
        <div><i class="fas fa-clipboard-list"></i> My Requests</div>
        <div class="nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="update-availability.php?toggle=1&redirect=requests.php"><i class="fas fa-toggle-on"></i> Toggle Availability</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="../logout.php" style="background:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($flash): ?>
            <div class="flash" id="flashMsg">
                <span><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($flash); ?></span>
                <a href="#" onclick="document.getElementById('flashMsg').remove();return false;" style="color:#155724;">&times;</a>
            </div>
        <?php endif; ?>

        <div class="filters">
            <?php foreach ($valid as $v): $active = $v===$filter ? 'style="background:#667eea;color:#fff;border-color:#667eea"' : '';?>
                <a href="?status=<?php echo $v; ?>" <?php echo $active; ?>><?php echo ucfirst(str_replace('_',' ',$v)); ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($requests)): ?>
            <div class="card" style="text-align:center;color:#666">No requests found.</div>
        <?php else: foreach ($requests as $r): $lat = $r['lat'] ?? null; $lng = $r['lng'] ?? null; ?>
            <div class="card" id="req-<?php echo (int)$r['id']; ?>">
                <div class="row">
                    <div>
                        <div><strong>#<?php echo (int)$r['id']; ?></strong> • <?php echo htmlspecialchars($r['user_name']); ?> • <?php echo htmlspecialchars($r['user_phone']); ?></div>
                        <div><?php echo ucfirst(htmlspecialchars($r['request_type'])); ?> • Urgency: <?php echo ucfirst(htmlspecialchars($r['urgency_level'])); ?></div>
                        <?php if (!empty($r['description'])): ?><div style="color:#666;margin-top:.25rem;">"<?php echo htmlspecialchars($r['description']); ?>"</div><?php endif; ?>
                        <?php if ($lat && $lng): ?>
                            <div style="margin-top:.25rem;color:#333;">Location: <span title="Latitude, Longitude"><?php echo htmlspecialchars($lat); ?>, <?php echo htmlspecialchars($lng); ?></span></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="badge b-<?php echo $r['status']; ?>"><?php echo ucfirst(str_replace('_',' ',$r['status'])); ?></span>
                        <div class="actions" style="margin-top:.5rem;text-align:right;">
                            <?php if ($lat && $lng): ?>
                                <button class="btn-track" onclick="openDirections(<?php echo (float)$lat; ?>, <?php echo (float)$lng; ?>)"><i class="fas fa-location-arrow"></i> Track</button>
                                <button class="btn-map" onclick="openMapModal(<?php echo (float)$lat; ?>, <?php echo (float)$lng; ?>, 'Request #<?php echo (int)$r['id']; ?>')"><i class="fas fa-map-marked-alt"></i> View Map</button>
                            <?php endif; ?>
                            <?php if ($r['status']==='assigned'): ?>
                                <a class="btn-start" href="update-status.php?id=<?php echo (int)$r['id']; ?>&status=in_progress">Start</a>
                            <?php endif; ?>
                            <?php if ($r['status']==='in_progress'): ?>
                                <a class="btn-complete" href="update-status.php?id=<?php echo (int)$r['id']; ?>&status=completed">Complete</a>
                                <a class="btn-cancel" href="update-status.php?id=<?php echo (int)$r['id']; ?>&status=cancelled">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Map Modal -->
    <div id="mapModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeMapModal()">&times;</span>
            <h3 id="mapTitle" style="margin:0 0 .75rem 0;">Location</h3>
            <div id="map"></div>
        </div>
    </div>

    <script>
        function openDirections(lat, lng){
            const url = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(lat + ',' + lng);
            window.open(url, '_blank');
        }
        let mapInstance = null;
        function openMapModal(lat, lng, title){
            document.getElementById('mapTitle').textContent = title + ' - (' + lat + ', ' + lng + ')';
            document.getElementById('mapModal').style.display = 'block';
            setTimeout(() => initMap(lat, lng), 50);
        }
        function closeMapModal(){ document.getElementById('mapModal').style.display = 'none'; }
        function initMap(lat, lng){
            if (!window.google || !window.google.maps) return;
            const center = { lat: parseFloat(lat), lng: parseFloat(lng) };
            mapInstance = new google.maps.Map(document.getElementById('map'), {
                center: center,
                zoom: 14,
                mapTypeControl: false,
                streetViewControl: false,
            });
            new google.maps.Marker({ position: center, map: mapInstance, title: 'Destination' });
        }
        // API callback placeholder
        function myFunction(){ /* Google Maps API ready */ }
        setTimeout(function(){ var f=document.getElementById('flashMsg'); if(f){ f.style.opacity='0'; setTimeout(()=>f.remove(), 500);}}, 4000);
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD3MPnSnyWwNmpnVEFkaddVvy_GWtxSejs&callback=myFunction"></script>
</body>
</html>
