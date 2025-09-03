<?php
session_start();
require_once 'includes/functions.php';

// Simple session check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

$message = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contact'])) {
    $name = UtilityFunctions::sanitizeInput($_POST['name'] ?? '');
    $email = UtilityFunctions::sanitizeInput($_POST['email'] ?? '');
    $subject = UtilityFunctions::sanitizeInput($_POST['subject'] ?? '');
    $message_text = UtilityFunctions::sanitizeInput($_POST['message'] ?? '');

    try {
        $db = Database::getInstance();
        $sql = "INSERT INTO support_tickets (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)";
        $db->executeQuery($sql, [$_SESSION['user_id'], $name, $email, $subject, $message_text]);
        $message = 'Your support ticket has been submitted successfully! We will get back to you within 24 hours.';
    } catch (Exception $e) {
        $message = 'Failed to submit your ticket. Please try again later.';
        error_log('Support ticket insert failed: ' . $e->getMessage());
    }
}

// FAQ data
$faqs = [
    [
        'category' => 'General',
        'question' => 'How does the EV Mobile Power & Service Station work?',
        'answer' => 'Our service connects stranded electric vehicle owners with mobile technicians who can provide roadside assistance, battery charging, and emergency repairs. Simply request a service through our platform and a qualified technician will be dispatched to your location.'
    ],
    [
        'category' => 'General',
        'question' => 'What areas do you serve?',
        'answer' => 'We currently serve major metropolitan areas and surrounding suburbs. Our service coverage includes downtown areas, residential neighborhoods, and major highways. Check our service area map for specific coverage details.'
    ],
    [
        'category' => 'Services',
        'question' => 'What types of services do you offer?',
        'answer' => 'We offer emergency battery charging, roadside assistance, tire changes, minor repairs, diagnostic services, and emergency towing. Our technicians are certified to work with all major electric vehicle brands.'
    ],
    [
        'category' => 'Services',
        'question' => 'How quickly can you respond to an emergency?',
        'answer' => 'Our average response time is 15-30 minutes for emergency situations. Response times may vary based on location, traffic conditions, and technician availability. We prioritize emergency calls and strive for the fastest possible response.'
    ],
    [
        'category' => 'Payment',
        'question' => 'What payment methods do you accept?',
        'answer' => 'We accept all major credit cards, debit cards, and digital payment methods including PayPal, Apple Pay, and Google Pay. Payment is processed securely through our platform after service completion.'
    ],
    [
        'category' => 'Payment',
        'question' => 'Do you offer any discounts or membership programs?',
        'answer' => 'Yes! We offer various membership tiers with benefits including priority service, discounted rates, and free emergency calls. We also provide special rates for fleet vehicles and corporate accounts.'
    ],
    [
        'category' => 'Technical',
        'question' => 'Are your technicians certified to work on electric vehicles?',
        'answer' => 'Absolutely. All our technicians are certified electric vehicle specialists with extensive training on EV systems, high-voltage safety, and manufacturer-specific protocols. They carry proper certifications and insurance.'
    ],
    [
        'category' => 'Technical',
        'question' => 'What if my vehicle needs more extensive repairs?',
        'answer' => 'If your vehicle requires repairs beyond our mobile capabilities, we can arrange towing to the nearest authorized service center. We work with a network of trusted repair facilities and can coordinate the entire process.'
    ],
    [
        'category' => 'Account',
        'question' => 'How do I update my vehicle information?',
        'answer' => 'You can update your vehicle information in your profile settings. Navigate to your profile page and click on "Update Profile Information" to modify your vehicle details, contact information, and preferences.'
    ],
    [
        'category' => 'Account',
        'question' => 'Can I cancel or modify a service request?',
        'answer' => 'Yes, you can cancel or modify service requests through your dashboard as long as a technician hasn\'t been assigned yet. Once assigned, please contact our support team for assistance with modifications.'
    ]
];

// Support categories
$support_categories = [
    'technical' => 'Technical Issues',
    'billing' => 'Billing & Payments',
    'service' => 'Service Requests',
    'account' => 'Account Management',
    'general' => 'General Inquiry'
];

// Contact information
$contact_info = [
    'phone' => '+1 (555) 123-4567',
    'email' => 'support@evmobilepower.com',
    'hours' => '24/7 Emergency Support',
    'address' => '123 EV Service Center, Tech City, TC 12345'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support & Help - EV Mobile Power & Service Station</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .navbar {
            position: sticky;
            top: 0;
            background: linear-gradient(135deg, rgba(102,126,234,0.9) 0%, rgba(118,75,162,0.9) 100%);
            backdrop-filter: blur(8px);
            color: white;
            padding: 1rem 1rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            z-index: 1000;
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin: 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            letter-spacing: 0.3px;
        }

        .navbar-nav {
            display: flex;
            list-style: none;
            gap: 1rem;
            align-items: center;
        }

        .navbar-nav a {
            color: white;
            text-decoration: none;
            transition: all 0.25s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .navbar-nav a:hover {
            background: rgba(255,255,255,0.18);
            transform: translateY(-2px);
        }

        .container {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 1rem;
        }

        .page-title { margin: 1rem 0 1.25rem; }
        .page-title h1 { display:flex; align-items:center; gap:10px; color:#333; }

        .support-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .support-section {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
            border: 1px solid #eef0ff;
        }

        .section-title {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 600;
        }

        .faq-categories {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .category-tab {
            padding: 8px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 20px;
            background: white;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            cursor: pointer;
        }

        .category-tab.active,
        .category-tab:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .faq-item {
            background: white;
            border-radius: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .faq-question {
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #333;
            transition: background-color 0.3s ease;
        }

        .faq-question:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            color: #555;
            line-height: 1.6;
        }

        .faq-item.active .faq-answer {
            padding: 0 20px 20px;
            max-height: 200px;
        }

        .faq-toggle {
            transition: transform 0.3s ease;
        }

        .faq-item.active .faq-toggle {
            transform: rotate(180deg);
        }

        .contact-form {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .contact-info {
            background: white;
            border-radius: 15px;
            padding: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            background: rgba(102, 126, 234, 0.05);
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        .contact-details h4 {
            color: #333;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .contact-details p {
            color: #666;
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid #28a745;
            color: #155724;
        }

        .emergency-section {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .emergency-section h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }

        .emergency-section p {
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .emergency-btn {
            background: white;
            color: #dc3545;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .emergency-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 768px) {
            .support-grid {
                grid-template-columns: 1fr;
            }

            .faq-categories {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-bolt"></i> EV Mobile Station
            </a>

 
      
           
            <ul class="navbar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="request-service.php"><i class="fas fa-plus-circle"></i> Request Service</a></li>
                <li><a href="service-history.php"><i class="fas fa-history"></i> History</a></li>
                <li><a href="vehicles.php"><i class="fas fa-car"></i> My Vehicles</a></li>
                <li><a href="track-service.php"><i class="fas fa-map-marker-alt"></i> Track Service</a></li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-title">
            <h1><i class="fas fa-headset"></i> Support & Help</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="support-grid">
            <!-- Main Support Content -->
            <div class="support-section">
                <div class="section-title">
                    <i class="fas fa-question-circle"></i> Frequently Asked Questions
                </div>

                <div class="faq-categories">
                    <div class="category-tab active" data-category="all">All</div>
                    <div class="category-tab" data-category="General">General</div>
                    <div class="category-tab" data-category="Services">Services</div>
                    <div class="category-tab" data-category="Payment">Payment</div>
                    <div class="category-tab" data-category="Technical">Technical</div>
                    <div class="category-tab" data-category="Account">Account</div>
                </div>

                <div class="faq-list">
                    <?php foreach ($faqs as $faq): ?>
                        <div class="faq-item" data-category="<?php echo $faq['category']; ?>">
                            <div class="faq-question">
                                <?php echo htmlspecialchars($faq['question']); ?>
                                <i class="fas fa-chevron-down faq-toggle"></i>
                            </div>
                            <div class="faq-answer">
                                <?php echo htmlspecialchars($faq['answer']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Contact & Support Sidebar -->
            <div class="support-section">
                <!-- Emergency Contact -->
                <div class="emergency-section">
                    <h3><i class="fas fa-exclamation-triangle"></i> Emergency?</h3>
                    <p>Need immediate assistance?</p>
                    <a href="tel:+15551234567" class="emergency-btn">
                        <i class="fas fa-phone"></i> Call Now
                    </a>
                </div>

                <!-- Contact Form -->
                <div class="contact-form">
                    <div class="section-title">
                        <i class="fas fa-envelope"></i> Contact Support
                    </div>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <select id="subject" name="subject" required>
                                <option value="">Select a topic</option>
                                <?php foreach ($support_categories as $key => $category): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $category; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="4" placeholder="Describe your issue or question..." required></textarea>
                        </div>
                        <button type="submit" name="submit_contact" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>

                <!-- Contact Information -->
                <div class="contact-info">
                    <div class="section-title">
                        <i class="fas fa-address-book"></i> Contact Information
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Phone Support</h4>
                            <p><?php echo $contact_info['phone']; ?></p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Email Support</h4>
                            <p><?php echo $contact_info['email']; ?></p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Support Hours</h4>
                            <p><?php echo $contact_info['hours']; ?></p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Office Address</h4>
                            <p><?php echo $contact_info['address']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // FAQ Accordion functionality
        document.querySelectorAll('.faq-question').forEach(function(question) {
            question.addEventListener('click', function() {
                const faqItem = this.parentElement;
                const isActive = faqItem.classList.contains('active');
                
                // Close all FAQ items
                document.querySelectorAll('.faq-item').forEach(function(item) {
                    item.classList.remove('active');
                });
                
                // Open clicked item if it wasn't active
                if (!isActive) {
                    faqItem.classList.add('active');
                }
            });
        });

        // Category filtering
        document.querySelectorAll('.category-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                const category = this.getAttribute('data-category');
                
                // Update active tab
                document.querySelectorAll('.category-tab').forEach(function(t) {
                    t.classList.remove('active');
                });
                this.classList.add('active');
                
                // Filter FAQ items
                document.querySelectorAll('.faq-item').forEach(function(item) {
                    if (category === 'all' || item.getAttribute('data-category') === category) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            });
        }, 5000);
    </script>
</body>
</html> 