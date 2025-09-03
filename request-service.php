<?php
session_start();
require_once 'includes/functions.php';

// Simple session check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Load services from DB
$services = [];
// Load user's saved vehicles
$userVehicles = [];
try {
    $db = Database::getInstance();
    $stmt = $db->executeQuery("SELECT id, name, description, base_price FROM services WHERE is_active = 1 ORDER BY name ASC");
    $services = $stmt->fetchAll();
    // vehicles
    $vstmt = $db->executeQuery("SELECT id, make, model, plate, is_primary FROM user_vehicles WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC", [$_SESSION['user_id']]);
    $userVehicles = $vstmt->fetchAll();
} catch (Exception $e) {
    error_log('Failed to load services: ' . $e->getMessage());
}

// Dummy data for the form fallbacks
$urgency_levels = [
    'low' => 'Low Priority',
    'medium' => 'Medium Priority', 
    'high' => 'High Priority',
    'emergency' => 'Emergency'
];

$payment_methods = [
    'credit_card' => 'Credit Card',
    'debit_card' => 'Debit Card',
    'cash' => 'Cash on Payment'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_type = UtilityFunctions::sanitizeInput($_POST['request_type'] ?? '');
    $urgency_level = UtilityFunctions::sanitizeInput($_POST['urgency_level'] ?? 'medium');
    $description = UtilityFunctions::sanitizeInput($_POST['description'] ?? '');
    $location_lat = UtilityFunctions::sanitizeInput($_POST['location_lat'] ?? '');
    $location_lng = UtilityFunctions::sanitizeInput($_POST['location_lng'] ?? '');
    $payment_method = UtilityFunctions::sanitizeInput($_POST['payment_method'] ?? '');
    $user_vehicle_id = isset($_POST['user_vehicle_id']) ? (int)$_POST['user_vehicle_id'] : null;

    // Map arbitrary service label to enum: charging | mechanical | both
    $lt = strtolower($raw_type);
    if (strpos($lt, 'charging') !== false && strpos($lt, 'mechanical') !== false) {
        $request_type = 'both';
    } elseif (strpos($lt, 'mechanical') !== false) {
        $request_type = 'mechanical';
    } else {
        $request_type = 'charging';
    }

    // Basic validation
    $errors_local = [];
    if (!in_array($request_type, ['charging','mechanical','both'], true)) $errors_local[] = 'Invalid service type';
    if (!in_array($urgency_level, ['low','medium','high','emergency'], true)) $errors_local[] = 'Invalid urgency level';
    if ($location_lat === '' || $location_lng === '') $errors_local[] = 'Location is required';
    if (!empty($userVehicles) && !$user_vehicle_id) $errors_local[] = 'Please select a vehicle';

    if (empty($errors_local)) {
        try {
            $serviceManager = new ServiceRequestManager();
            $request_id = $serviceManager->createRequest([
                'user_id' => $_SESSION['user_id'],
                'user_vehicle_id' => $user_vehicle_id,
                'request_type' => $request_type,
                'vehicle_location_lat' => (float)$location_lat,
                'vehicle_location_lng' => (float)$location_lng,
                'description' => $description,
                'urgency_level' => $urgency_level
            ]);
            if ($request_id) {
                // If user selected a card payment, record the payment immediately
                $amount = (float)($_POST['calculated_amount'] ?? 0);
                $method = $payment_method;
                if ($amount > 0 && in_array($method, ['credit_card','debit_card','cash'], true)) {
                    $paymentManager = new PaymentManager();
                    $paymentManager->createPayment([
                        'service_request_id' => $request_id,
                        'amount' => $amount,
                        'payment_method' => $method
                    ]);
                }
                $success = true;
            } else {
                $errorMsg = 'Failed to create service request.';
            }
        } catch (Exception $e) {
            $errorMsg = 'Error creating service request: ' . $e->getMessage();
        }
    } else {
        $errorMsg = implode("\n", $errors_local);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Service - EV Mobile Power & Service Station</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #333; }

        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }

        .nav { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 0 1rem; }

        .logo { font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 0.5rem; }

        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-menu a { color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 5px; transition: background 0.3s; }
        .user-menu a:hover { background: rgba(255,255,255,0.1); }

        .container { width: 100%; max-width: none; margin: 0; padding: 1rem; }

        .page-header { text-align: left; margin: 1rem 0 1.5rem; }
        .page-header h1 { color: #333; margin-bottom: 0.25rem; }

        .request-form { background: white; border-radius: 15px; padding: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        .form-section { margin-bottom: 2rem; }
        .form-section h3 { color: #333; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 1rem; border: 2px solid #e1e5e9; border-radius: 10px; font-size: 1rem; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; }
        .form-group textarea { resize: vertical; min-height: 100px; }

        .submit-btn { width: 100%; padding: 1rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: transform 0.3s; }
        .submit-btn:hover { transform: translateY(-2px); }

        .success-message { background: #d4edda; color: #155724; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid #c3e6cb; }

        .location-section { background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; }
        .map-placeholder { background: #e9ecef; height: 200px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #6c757d; font-size: 1.1rem; margin-bottom: 1rem; }

        .estimated-cost { background: #fff3cd; color: #856404; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid #ffeaa7; }

        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .container { padding: 1rem; }
            .request-form { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav">
            <div class="logo">
                <i class="fas fa-bolt"></i>
                EV Mobile Station
            </div>
            <div class="user-menu">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="vehicles.php"><i class="fas fa-car"></i> My Vehicles</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-plus-circle"></i> Request Service</h1>
            <p>Tell us about your emergency and we'll dispatch help immediately</p>
        </div>

        <?php if (isset($errorMsg) && $errorMsg): ?>
            <div class="success-message" style="background:#fde8ea;color:#8a2530;border-color:#f8c7cd;">
                <h3><i class="fas fa-exclamation-triangle"></i> Request Failed</h3>
                <p><?php echo htmlspecialchars($errorMsg); ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($success) && $success): ?>
            <div class="success-message">
                <h3><i class="fas fa-check-circle"></i> Service Request Created Successfully!</h3>
                <p><strong>Request ID:</strong> #<?php echo $request_id; ?></p>
                <p>We've received your request and are dispatching the nearest available service vehicle. You'll receive updates on your dashboard.</p>
                <a href="dashboard.php" class="submit-btn" style="margin-top: 1rem; text-decoration: none; display: inline-block;">
                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                </a>
            </div>
        <?php else: ?>
            <form method="POST" class="request-form">
                <!-- Service Type Section -->
                <div class="form-section">
                    <h3><i class="fas fa-tools"></i> Service Type</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="request_type">Service Required *</label>
                            <select id="request_type" name="request_type" required>
                                <option value="">Select service type</option>
                                <?php if (!empty($services)): ?>
                                    <?php foreach ($services as $svc): ?>
                                        <option value="<?php echo htmlspecialchars(strtolower($svc['name'])); ?>" data-price="<?php echo htmlspecialchars($svc['base_price']); ?>">
                                            <?php echo htmlspecialchars($svc['name']); ?> (₹<?php echo number_format($svc['base_price'], 2); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="charging" data-price="75">Mobile EV Charging (₹75.00)</option>
                                    <option value="mechanical" data-price="80">Mechanical Support (₹80.00)</option>
                                    <option value="both" data-price="120">Charging + Mechanical (₹120.00)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="user_vehicle_id">Select Your Vehicle *</label>
                            <select id="user_vehicle_id" name="user_vehicle_id" <?php echo empty($userVehicles)?'disabled':''; ?> required>
                                <?php if (empty($userVehicles)): ?>
                                    <option value="">No saved vehicles. Please add one in profile.</option>
                                <?php else: ?>
                                    <option value="">Choose vehicle</option>
                                    <?php foreach ($userVehicles as $uv): ?>
                                        <option value="<?php echo (int)$uv['id']; ?>">
                                            <?php echo htmlspecialchars(($uv['is_primary']? '[Primary] ':'') . $uv['make'] . ' ' . $uv['model'] . ($uv['plate']? (' - ' . $uv['plate']) : '')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="urgency_level">Urgency Level *</label>
                            <select id="urgency_level" name="urgency_level" required>
                                <option value="">Select urgency</option>
                                <?php foreach ($urgency_levels as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Location Section -->
                <div class="form-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Location</h3>
                    <div class="location-section">
                        <div id="map" class="map-placeholder"></div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="location_lat">Latitude</label>
                                <input type="number" id="location_lat" name="location_lat" step="any" 
                                       value="9.591600" placeholder="Enter latitude">
                            </div>
                            <div class="form-group">
                                <label for="location_lng">Longitude</label>
                                <input type="number" id="location_lng" name="location_lng" step="any" 
                                       value="76.522200" placeholder="Enter longitude">
                            </div>
                        </div>
                        <button type="button" style="background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-crosshairs"></i> Use Current Location
                        </button>
                    </div>
                </div>

                <!-- Description Section -->
                <div class="form-section">
                    <h3><i class="fas fa-comment"></i> Description</h3>
                    <div class="form-group">
                        <label for="description">Describe your issue *</label>
                        <textarea id="description" name="description" required 
                                  placeholder="Please describe your vehicle issue in detail..."></textarea>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="form-section">
                    <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                    <div class="form-group">
                        <label>Preferred Payment Method *</label>
                        <div class="form-row" style="margin-bottom: 0;">
                            <label style="display:block;border:2px solid #e1e5e9;border-radius:10px;padding:1rem;cursor:pointer;">
                                <input type="radio" name="payment_method" value="credit_card" required style="margin-right:8px;"> Credit Card
                            </label>
                            <label style="display:block;border:2px solid #e1e5e9;border-radius:10px;padding:1rem;cursor:pointer;">
                                <input type="radio" name="payment_method" value="debit_card" required style="margin-right:8px;"> Debit Card
                            </label>
                        </div>
                        <div class="form-row">
                            <label style="display:block;border:2px solid #e1e5e9;border-radius:10px;padding:1rem;cursor:pointer;grid-column:1 / -1;">
                                <input type="radio" name="payment_method" value="cash" required style="margin-right:8px;"> Cash on Payment
                            </label>
                        </div>
                    </div>
                    
                    <div class="estimated-cost">
                        <h4><i class="fas fa-info-circle"></i> Estimated Cost</h4>
                        <p id="serviceFee"><strong>Service Fee:</strong> —</p>
                        <p><strong>Distance Fee:</strong> ₹0.50 per km</p>
                        <p id="totalEstimate"><strong>Total Estimate:</strong> —</p>
                        <small>* Final cost will be calculated based on actual service provided</small>
                    </div>
                </div>

                <input type="hidden" name="calculated_amount" id="calculated_amount" value="">
                <button type="submit" class="submit-btn" id="submitRequest">
                    <i class="fas fa-paper-plane"></i> Submit Service Request
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Google Maps init
        let map, marker;
        function myFunction() {
            const latInput = document.getElementById('location_lat');
            const lngInput = document.getElementById('location_lng');
            const initial = {
                lat: parseFloat(latInput.value) || 9.5916, // Kerala default
                lng: parseFloat(lngInput.value) || 76.5222 // Kottayam vicinity
            };
            map = new google.maps.Map(document.getElementById('map'), {
                center: initial,
                zoom: 13,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false
            });
            marker = new google.maps.Marker({
                position: initial,
                map,
                draggable: true
            });
            marker.addListener('dragend', () => {
                const pos = marker.getPosition();
                latInput.value = pos.lat().toFixed(6);
                lngInput.value = pos.lng().toFixed(6);
            });
            map.addListener('click', (e) => {
                marker.setPosition(e.latLng);
                latInput.value = e.latLng.lat().toFixed(6);
                lngInput.value = e.latLng.lng().toFixed(6);
            });
        }

        // Use Current Location button
        document.querySelector('button[type="button"]').addEventListener('click', function() {
            if (!navigator.geolocation) {
                alert('Geolocation not supported');
                return;
            }
            navigator.geolocation.getCurrentPosition((pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                document.getElementById('location_lat').value = lat.toFixed(6);
                document.getElementById('location_lng').value = lng.toFixed(6);
                if (map && marker) {
                    const ll = { lat, lng };
                    map.setCenter(ll);
                    marker.setPosition(ll);
                }
            }, (err) => {
                alert('Unable to get current location');
            });
        });

        // Intercept submit to show payment modal for card payments
        const formEl = document.querySelector('form');
        const submitBtn = document.getElementById('submitRequest');
        formEl.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            requiredFields.forEach(field => {
                if (!field.value.trim()) { isValid = false; field.style.borderColor = '#dc3545'; }
                else { field.style.borderColor = '#e1e5e9'; }
            });
            if (!isValid) { e.preventDefault(); alert('Please fill in all required fields'); return false; }

            const method = (document.querySelector('input[name="payment_method"]:checked')||{}).value;
            if (method === 'credit_card' || method === 'debit_card') {
                e.preventDefault();
                openPaymentModal(method);
                return false;
            }
        });

        // Dynamic cost estimation (based on selected service base_price and urgency)
        function updateEstimate(){
            const sel = document.getElementById('request_type');
            const option = sel.options[sel.selectedIndex];
            const urgency = document.getElementById('urgency_level').value;
            const base = parseFloat(option?.dataset?.price || '0');
            if (!base){
                document.getElementById('serviceFee').innerHTML = '<strong>Service Fee:</strong> —';
                document.getElementById('totalEstimate').innerHTML = '<strong>Total Estimate:</strong> —';
                document.getElementById('calculated_amount').value = '';
                return;
            }
            let multiplier = 1;
            if (urgency === 'high') multiplier = 1.3;
            if (urgency === 'emergency') multiplier = 1.5;
            const fee = (base * multiplier);
            const totalLow = Math.round(fee);
            const totalHigh = totalLow + 50; // rough distance buffer
            document.getElementById('serviceFee').innerHTML = `<strong>Service Fee:</strong> ₹${fee.toFixed(2)} (${urgency||'medium'})`;
            document.getElementById('totalEstimate').innerHTML = `<strong>Total Estimate:</strong> ₹${totalLow} - ₹${totalHigh}`;
            document.getElementById('calculated_amount').value = fee.toFixed(2);
        }
        document.getElementById('request_type').addEventListener('change', updateEstimate);
        document.getElementById('urgency_level').addEventListener('change', updateEstimate);
        // initialize if defaults are present
        updateEstimate();
    </script>
    <!-- Payment Modal -->
    <div id="paymentModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:9999;">
        <div style="background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.2);width:90%;max-width:420px;padding:1.25rem;">
            <h3 style="margin:0 0 0.5rem 0;"><i class="fas fa-credit-card"></i> Payment Details</h3>
            <p id="paymentTypeLabel" style="margin:0 0 1rem 0;color:#555;font-size:0.95rem;">Enter your card details to proceed.</p>
            <div class="form-group">
                <label>Card Holder Name</label>
                <input type="text" id="cardHolder" placeholder="e.g., John Doe" style="width:100%;padding:0.75rem;border:2px solid #e1e5e9;border-radius:10px;">
            </div>
            <div class="form-group">
                <label>Card Number</label>
                <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" style="width:100%;padding:0.75rem;border:2px solid #e1e5e9;border-radius:10px;">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Expiry (MM/YY)</label>
                    <input type="text" id="cardExpiry" placeholder="MM/YY" maxlength="5" style="width:100%;padding:0.75rem;border:2px solid #e1e5e9;border-radius:10px;">
                </div>
                <div class="form-group">
                    <label>CVV</label>
                    <input type="password" id="cardCVV" placeholder="123" maxlength="4" style="width:100%;padding:0.75rem;border:2px solid #e1e5e9;border-radius:10px;">
                </div>
            </div>
            <div style="display:flex;gap:0.5rem;justify-content:flex-end;margin-top:0.5rem;">
                <button id="cancelPayment" style="background:#6c757d;color:#fff;border:none;border-radius:8px;padding:0.6rem 1rem;cursor:pointer;">Cancel</button>
                <button id="confirmPayment" style="background:#28a745;color:#fff;border:none;border-radius:8px;padding:0.6rem 1rem;cursor:pointer;">Pay</button>
            </div>
        </div>
    </div>
    <script>
        function openPaymentModal(type){
            document.getElementById('paymentTypeLabel').innerText = (type==='debit_card'?'Debit':'Credit') + ' card payment';
            document.getElementById('paymentModal').style.display = 'flex';
        }
        document.getElementById('cancelPayment').addEventListener('click', ()=>{
            document.getElementById('paymentModal').style.display = 'none';
        });
        document.getElementById('cardNumber').addEventListener('input', function(){
            this.value = this.value.replace(/\D/g,'').slice(0,16).replace(/(.{4})/g,'$1 ').trim();
        });
        document.getElementById('cardExpiry').addEventListener('input', function(){
            this.value = this.value.replace(/\D/g,'').slice(0,4).replace(/^(\d{2})(\d{0,2}).*/, (m,mm,yy)=> yy? mm+'/'+yy : mm);
        });
        document.getElementById('cardCVV').addEventListener('input', function(){
            this.value = this.value.replace(/\D/g,'').slice(0,4);
        });
        document.getElementById('confirmPayment').addEventListener('click', function(){
            const name = document.getElementById('cardHolder').value.trim();
            const number = document.getElementById('cardNumber').value.replace(/\s+/g,'');
            const exp = document.getElementById('cardExpiry').value.trim();
            const cvv = document.getElementById('cardCVV').value.trim();
            const errors = [];
            if (!/^[A-Za-z ]{2,}$/.test(name)) errors.push('Valid card holder name required');
            if (!/^\d{16}$/.test(number)) errors.push('Card number must be 16 digits');
            if (!/^(0[1-9]|1[0-2])\/(\d{2})$/.test(exp)) errors.push('Expiry must be MM/YY');
            if (!/^\d{3,4}$/.test(cvv)) errors.push('CVV must be 3-4 digits');
            if (errors.length){ alert(errors.join('\n')); return; }
            // Close modal and submit original form
            document.getElementById('paymentModal').style.display = 'none';
            formEl.submit();
        });
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD3MPnSnyWwNmpnVEFkaddVvy_GWtxSejs&callback=myFunction"></script>
</body>
</html> 