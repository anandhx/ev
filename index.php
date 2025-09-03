<?php
$imgDir = __DIR__ . '/assets/img';
$imgBase = 'assets/img';
$allImages = [];
if (is_dir($imgDir)) {
    foreach (scandir($imgDir) as $f) {
        if (preg_match('/\.(jpe?g|png|webp)$/i', $f)) {
            $allImages[] = $imgBase . '/' . $f;
        }
    }
}
if (empty($allImages)) {
    $allImages = [$imgBase . '/hyundai-motor-group-a3vDd8hzuYs-unsplash.jpg'];
}
$heroImage = $allImages[array_rand($allImages)];
// Randomize order for gallery use
$galleryImages = $allImages;
shuffle($galleryImages);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EV Mobile Power & Service Station - Smart Support for Stranded EVs</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary: #8b5cf6;
            --accent: #06d6a0;
            --accent-glow: rgba(6, 214, 160, 0.4);
            --bg: #0a0b0d;
            --bg-secondary: #0f1114;
            --card: rgba(255, 255, 255, 0.08);
            --card-hover: rgba(255, 255, 255, 0.12);
            --glass: rgba(255, 255, 255, 0.05);
            --border: rgba(255, 255, 255, 0.15);
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            --glow: 0 0 20px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: var(--bg);
            overflow-x: hidden;
        }

        /* Advanced Background Effects */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 15%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 30%, rgba(139, 92, 246, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 40% 70%, rgba(6, 214, 160, 0.1) 0%, transparent 50%);
            animation: etherealMove 30s ease-in-out infinite alternate;
            z-index: -2;
        }

        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background: 
                linear-gradient(45deg, transparent 30%, rgba(59, 130, 246, 0.03) 50%, transparent 70%),
                linear-gradient(-45deg, transparent 30%, rgba(139, 92, 246, 0.03) 50%, transparent 70%);
            animation: meshGradient 20s ease-in-out infinite alternate;
            z-index: -1;
        }

        @keyframes etherealMove {
            0% { transform: translate(-5%, -5%) rotate(0deg); }
            100% { transform: translate(5%, 5%) rotate(360deg); }
        }

        @keyframes meshGradient {
            0% { opacity: 0.3; transform: scale(1); }
            100% { opacity: 0.6; transform: scale(1.1); }
        }

        /* Glass Morphism Header */
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            padding: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nav-container {
            background: rgba(10, 11, 13, 0.4);
            backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid var(--border);
            border-radius: 0 0 24px 24px;
            margin: 0 2rem;
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .header.scrolled .nav-container {
            background: rgba(10, 11, 13, 0.8);
            box-shadow: 0 32px 64px rgba(0, 0, 0, 0.4);
            transform: translateY(0.5rem);
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }

        .logo i {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.75rem;
            filter: drop-shadow(0 0 10px var(--accent-glow));
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 0.5rem 1rem;
            border-radius: 12px;
        }

        .nav-links a::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .nav-links a:hover::before {
            opacity: 0.1;
        }

        .nav-links a:hover {
            color: var(--accent);
            transform: translateY(-2px);
            text-shadow: var(--glow) var(--accent-glow);
        }

        /* Hero Section with 3D Elements */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            margin-top: 0;
        }

        .hero-3d-bg {
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(circle at 30% 20%, rgba(59, 130, 246, 0.2), transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(139, 92, 246, 0.15), transparent 50%),
                linear-gradient(135deg, transparent, rgba(6, 214, 160, 0.1));
            animation: heroGlow 8s ease-in-out infinite alternate;
        }

        @keyframes heroGlow {
            0% { opacity: 0.6; filter: blur(40px); }
            100% { opacity: 0.9; filter: blur(60px); }
        }

        .floating-elements {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .floating-icon {
            position: absolute;
            font-size: 2rem;
            opacity: 0.1;
            animation: float 15s linear infinite;
            color: var(--accent);
        }

        .floating-icon:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; }
        .floating-icon:nth-child(2) { top: 60%; left: 85%; animation-delay: -5s; }
        .floating-icon:nth-child(3) { top: 80%; left: 15%; animation-delay: -10s; }
        .floating-icon:nth-child(4) { top: 30%; left: 70%; animation-delay: -3s; }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); opacity: 0; }
            10% { opacity: 0.1; }
            50% { transform: translateY(-20px) rotate(180deg); opacity: 0.2; }
            90% { opacity: 0.1; }
            100% { transform: translateY(-40px) rotate(360deg); opacity: 0; }
        }

        .hero-content {
            text-align: center;
            z-index: 2;
            max-width: 900px;
            padding: 2rem;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--glass);
            border: 1px solid var(--border);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            margin-bottom: 2rem;
            backdrop-filter: blur(20px);
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--accent);
            animation: badgeGlow 3s ease-in-out infinite alternate;
        }

        @keyframes badgeGlow {
            0% { box-shadow: 0 0 20px rgba(6, 214, 160, 0.2); }
            100% { box-shadow: 0 0 40px rgba(6, 214, 160, 0.4); }
        }

        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 5rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 4px 20px rgba(255, 255, 255, 0.1);
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }

        /* Advanced Button Styles */
        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            transform-style: preserve-3d;
        }

        .btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: inherit;
            transition: all 0.4s ease;
            z-index: -1;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #00b894);
            color: #0a0b0d;
            box-shadow: 
                0 20px 40px rgba(6, 214, 160, 0.3),
                0 0 0 1px rgba(6, 214, 160, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 
                0 32px 64px rgba(6, 214, 160, 0.4),
                0 0 40px rgba(6, 214, 160, 0.6);
        }

        .btn-secondary {
            background: var(--glass);
            color: var(--text);
            border: 1px solid var(--border);
            backdrop-filter: blur(20px);
        }

        .btn-secondary:hover {
            background: var(--card-hover);
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 20px 40px rgba(255, 255, 255, 0.1);
            color: var(--text);
        }

        /* Modern Card Styles */
        .section {
            padding: 8rem 2rem;
            position: relative;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--text), var(--text-muted));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.25rem;
            color: var(--text-muted);
            margin-bottom: 4rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal.in {
            opacity: 1;
            transform: translateY(0);
        }

        /* Advanced Feature Cards */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem;
            text-align: center;
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            transition: all 0.5s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px) rotateX(5deg);
            background: var(--card-hover);
            box-shadow: 
                0 40px 80px rgba(0, 0, 0, 0.3),
                0 0 40px rgba(6, 214, 160, 0.2);
        }

        .feature-card:hover::before {
            height: 3px;
            box-shadow: 0 0 20px var(--accent-glow);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }

        .feature-icon::after {
            content: '';
            position: absolute;
            inset: -20px;
            background: radial-gradient(circle, var(--accent-glow), transparent 70%);
            opacity: 0;
            transition: opacity 0.5s ease;
            z-index: -1;
        }

        .feature-card:hover .feature-icon::after {
            opacity: 1;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .feature-card p {
            color: var(--text-muted);
            line-height: 1.7;
        }

        /* Service Cards with Enhanced Design */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 2.5rem;
        }

        .service-card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 32px;
            overflow: hidden;
            backdrop-filter: blur(20px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .service-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 
                0 50px 100px rgba(0, 0, 0, 0.4),
                0 0 60px rgba(6, 214, 160, 0.3);
        }

        .service-image {
            height: 240px;
            background: linear-gradient(135deg, var(--primary), var(--secondary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            position: relative;
            overflow: hidden;
        }

        .service-image::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) skewX(-15deg); }
            100% { transform: translateX(200%) skewX(-15deg); }
        }

        .service-content {
            padding: 2.5rem;
        }

        .service-content h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .service-content ul {
            margin-top: 1.5rem;
            padding-left: 0;
            list-style: none;
        }

        .service-content li {
            padding: 0.5rem 0;
            position: relative;
            padding-left: 1.5rem;
            color: var(--text-muted);
        }

        .service-content li::before {
            content: '✦';
            position: absolute;
            left: 0;
            color: var(--accent);
            font-size: 0.8rem;
        }

        /* Brand Strip with Modern Design */
        .brands {
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.02);
        }

        .brand-slider {
            overflow: hidden;
            position: relative;
        }

        .brand-track {
            display: flex;
            gap: 3rem;
            animation: brandScroll 25s linear infinite;
        }

        @keyframes brandScroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }

        .brand {
            min-width: 200px;
            height: 80px;
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-muted);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            letter-spacing: 1px;
        }

        .brand:hover {
            color: var(--accent);
            border-color: var(--accent);
            box-shadow: 0 0 30px rgba(6, 214, 160, 0.3);
        }

        /* Testimonials with Modern Cards */
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
        }

        .testimonial {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2rem;
            backdrop-filter: blur(20px);
            position: relative;
            transition: all 0.4s ease;
        }

        .testimonial:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            border-color: var(--accent);
        }

        .testimonial::before {
            content: '"';
            position: absolute;
            top: -10px;
            left: 20px;
            font-size: 4rem;
            color: var(--accent);
            opacity: 0.3;
            font-family: serif;
        }

        .testimonial p {
            margin-bottom: 1.5rem;
            font-style: italic;
            color: var(--text);
            line-height: 1.7;
        }

        .author {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        .author i {
            color: var(--accent);
            font-size: 1.5rem;
        }

        /* Back to Top Button */
        #toTop {
            position: fixed;
            right: 2rem;
            bottom: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #00b894);
            color: #0a0b0d;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 
                0 20px 40px rgba(6, 214, 160, 0.3),
                0 0 0 1px rgba(6, 214, 160, 0.2);
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 1.2rem;
        }

        #toTop.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(-8px);
        }

        #toTop:hover {
            transform: translateY(-12px) scale(1.1);
            box-shadow: 
                0 30px 60px rgba(6, 214, 160, 0.4),
                0 0 40px rgba(6, 214, 160, 0.6);
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #0a0b0d, #0f1114);
            color: var(--text-muted);
            padding: 4rem 2rem 2rem;
            text-align: center;
            border-top: 1px solid var(--border);
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
        }

        .footer-content h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .social-links a {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--glass);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 1.25rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .social-links a:hover {
            color: var(--accent);
            border-color: var(--accent);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(6, 214, 160, 0.3);
        }

        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .nav-container {
                margin: 0 1rem;
                padding: 1rem 1.5rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .hero-content {
                padding: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 280px;
                justify-content: center;
            }
            
            .section {
                padding: 4rem 1rem;
            }
            
            .features-grid,
            .services-grid {
                grid-template-columns: 1fr;
            }
            
            .brand-track {
                gap: 2rem;
            }
            
            #toTop {
                right: 1rem;
                bottom: 1rem;
                width: 48px;
                height: 48px;
            }
        }

        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 3px solid var(--border);
            border-top: 3px solid var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <style>
        /* Image Gallery */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
        }
        .gallery-item {
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--border);
            background: var(--glass);
            box-shadow: var(--shadow);
            aspect-ratio: 4 / 3;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        /* Hero background photo layer */
        .hero-photo { position: absolute; inset: 0; z-index: 0; background-size: cover; background-position: center; filter: saturate(1.05) contrast(1.05); opacity: 0.35; }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Advanced Header -->
    <header class="header" id="header">
        <div class="nav-container">
            <nav class="nav">
                <div class="logo">
                    <i class="fas fa-bolt"></i>
                    EV Mobile Station
                </div>
                <ul class="nav-links">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#testimonials">Reviews</a></li>
                    <li><a href="login.php" class="btn btn-secondary" style="padding: 0.75rem 1.5rem; margin: 0;">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Revolutionary Hero Section -->
    <section id="home" class="hero">
        <div class="hero-photo" style="background-image:url('<?php echo htmlspecialchars($heroImage, ENT_QUOTES); ?>');"></div>
        <div class="hero-3d-bg"></div>
        <div class="floating-elements">
            <div class="floating-icon"><i class="fas fa-bolt"></i></div>
            <div class="floating-icon"><i class="fas fa-charging-station"></i></div>
            <div class="floating-icon"><i class="fas fa-car-battery"></i></div>
            <div class="floating-icon"><i class="fas fa-route"></i></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-sparkles"></i>
                Revolutionary EV Support Platform
            </div>
            
            <h1>Power Up Your Journey<br>Anywhere, Anytime</h1>
            
            <p>Experience the future of electric vehicle support with our AI-powered mobile charging stations and emergency services. Get instant help when you need it most.</p>
            
            <div class="cta-buttons">
                <a href="signup.php" class="btn btn-primary">
                    <i class="fas fa-rocket"></i>
                    Start Your Journey
                </a>
                <a href="#services" class="btn btn-secondary">
                    <i class="fas fa-play"></i>
                    Watch Demo
                </a>
            </div>
        </div>
    </section>

    <!-- Advanced Features Section -->
    <section id="features" class="section">
        <div class="container">
            <h2 class="section-title reveal">Next-Gen EV Support</h2>
            <p class="section-subtitle reveal">Cutting-edge technology meets unparalleled service quality</p>
            
            <div class="features-grid">
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <h3>AI-Powered Charging</h3>
                    <p>Smart mobile charging units with AI optimization arrive at your location with ultra-fast charging capabilities and predictive battery analysis.</p>
                </div>
                
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-robot"></i></div>
                    <h3>Autonomous Dispatch</h3>
                    <p>Advanced routing algorithms and real-time traffic analysis ensure the fastest possible response times with automated service deployment.</p>
                </div>
                
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                    <h3>Immersive AR Interface</h3>
                    <p>Next-generation mobile app with augmented reality features for service visualization and interactive troubleshooting guides.</p>
                </div>
                
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-satellite-dish"></i></div>
                    <h3>IoT Integration</h3>
                    <p>Seamless integration with your vehicle's IoT systems for proactive monitoring and predictive maintenance alerts.</p>
                </div>
                
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-shield-virus"></i></div>
                    <h3>Quantum Security</h3>
                    <p>Military-grade encryption and blockchain-secured payments with zero-trust security architecture for complete peace of mind.</p>
                </div>
                
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-brain"></i></div>
                    <h3>Predictive Analytics</h3>
                    <p>Machine learning algorithms predict potential issues before they occur, offering preventive solutions and optimal charging schedules.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Revolutionary Services Section -->
    <section id="services" class="section" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));">
        <div class="container">
            <h2 class="section-title reveal">Premium Service Suite</h2>
            <p class="section-subtitle reveal">Comprehensive solutions for every EV owner</p>
            
            <div class="services-grid">
                <div class="service-card reveal">
                    <div class="service-image">
                        <i class="fas fa-charging-station"></i>
                    </div>
                    <div class="service-content">
                        <h3>Quantum Charging Network</h3>
                        <p>Revolutionary mobile charging technology with quantum-enhanced energy transfer and ultra-rapid charging capabilities for all EV models.</p>
                        <ul>
                            <li>300kW+ ultra-fast charging</li>
                            <li>Universal compatibility system</li>
                            <li>Solar-powered mobile units</li>
                            <li>Wireless charging capability</li>
                        </ul>
                    </div>
                </div>
                
                <div class="service-card reveal">
                    <div class="service-image">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="service-content">
                        <h3>Holographic Diagnostics</h3>
                        <p>Advanced holographic diagnostic systems with AR-powered repair guidance and real-time component analysis for precise troubleshooting.</p>
                        <ul>
                            <li>3D holographic vehicle scanning</li>
                            <li>AI-powered fault detection</li>
                            <li>Remote diagnostic capabilities</li>
                            <li>Predictive maintenance alerts</li>
                        </ul>
                    </div>
                </div>
                
                <div class="service-card reveal">
                    <div class="service-image">
                        <i class="fas fa-satellite"></i>
                    </div>
                    <div class="service-content">
                        <h3>Satellite Emergency Network</h3>
                        <p>Global satellite-connected emergency response system ensuring help reaches you anywhere on Earth with precision timing and location accuracy.</p>
                        <ul>
                            <li>Global satellite coverage</li>
                            <li>Sub-meter GPS accuracy</li>
                            <li>Emergency beacon activation</li>
                            <li>Multi-language support</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dynamic Brand Carousel -->
    <section class="brands">
        <div class="container">
            <h3 class="section-title" style="font-size: 1.5rem; margin-bottom: 2rem; opacity: 0.8;">Trusted by Leading EV Manufacturers</h3>
            <div class="brand-slider">
                <div class="brand-track">
                    <div class="brand">TESLA</div>
                    <div class="brand">RIVIAN</div>
                    <div class="brand">LUCID</div>
                    <div class="brand">MERCEDES EQS</div>
                    <div class="brand">BMW iX</div>
                    <div class="brand">AUDI e-tron</div>
                    <div class="brand">PORSCHE TAYCAN</div>
                    <div class="brand">TESLA</div>
                    <div class="brand">RIVIAN</div>
                    <div class="brand">LUCID</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Inspiration Gallery -->
    <section id="gallery" class="section" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.04), rgba(139, 92, 246, 0.04));">
        <div class="container">
            <h2 class="section-title reveal">Inspiration Gallery</h2>
            <p class="section-subtitle reveal">Real shots that celebrate EV design, charging and the open road</p>
            <div class="gallery-grid">
                <?php foreach ($galleryImages as $img): ?>
                <div class="gallery-item reveal"><img loading="lazy" src="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>" alt="Gallery image"></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Enhanced Testimonials -->
    <section id="testimonials" class="section">
        <div class="container">
            <h2 class="section-title reveal">Success Stories</h2>
            <p class="section-subtitle reveal">Real experiences from satisfied customers</p>
            
            <div class="testimonials-grid">
                <div class="testimonial reveal">
                    <p>The AI-powered charging system arrived in just 12 minutes and had my Tesla at 80% charge in under 15 minutes. Absolutely revolutionary technology!</p>
                    <div class="author">
                        <i class="fas fa-user-astronaut"></i>
                        <span>Alex Chen • Tech Entrepreneur • San Francisco</span>
                    </div>
                </div>
                
                <div class="testimonial reveal">
                    <p>Stranded at midnight on a remote highway, their satellite emergency system located me instantly and dispatched help. The holographic diagnostics were mind-blowing!</p>
                    <div class="author">
                        <i class="fas fa-user-tie"></i>
                        <span>Sarah Johnson • Business Executive • Austin</span>
                    </div>
                </div>
                
                <div class="testimonial reveal">
                    <p>The AR interface showed me exactly what was wrong with my vehicle. The technician fixed the issue remotely while I watched through the app. Future is here!</p>
                    <div class="author">
                        <i class="fas fa-user-graduate"></i>
                        <span>Dr. Michael Rodriguez • Research Scientist • Boston</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Floating Action Button -->
    <div id="toTop">
        <i class="fas fa-rocket"></i>
    </div>

    <!-- Futuristic Footer -->
    <footer class="footer">
        <div class="footer-content">
            <h3>EV Mobile Power & Service Station</h3>
            <p>Pioneering the future of electric vehicle support with cutting-edge technology and unmatched service excellence.</p>
            
            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                <a href="#" aria-label="Discord"><i class="fab fa-discord"></i></a>
            </div>
            
            <p style="margin-top: 2rem; opacity: 0.6;">&copy; 2024 EV Mobile Station. Revolutionizing mobility, one charge at a time.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced Loading Animation
        window.addEventListener('load', function() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            setTimeout(() => {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 500);
            }, 1000);
        });

        // Advanced Smooth Scrolling with Easing
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href.length > 1) {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        const headerHeight = document.querySelector('.header').offsetHeight;
                        const targetPosition = target.offsetTop - headerHeight - 20;
                        
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });

        // Advanced Header Scroll Effect
        const header = document.getElementById('header');
        let lastScrollTop = 0;
        
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            
            // Hide header on scroll down, show on scroll up
            if (scrollTop > lastScrollTop && scrollTop > 200) {
                header.style.transform = 'translateY(-100%)';
            } else {
                header.style.transform = 'translateY(0)';
            }
            lastScrollTop = scrollTop;
        });

        // Enhanced Intersection Observer with Stagger
        const reveals = document.querySelectorAll('.reveal');
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('in');
                    }, index * 100);
                    revealObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);

        reveals.forEach(el => revealObserver.observe(el));

        // Advanced 3D Tilt Effect for Cards
        function handle3DTilt(e) {
            const card = e.currentTarget;
            const rect = card.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            const deltaX = (e.clientX - centerX) / (rect.width / 2);
            const deltaY = (e.clientY - centerY) / (rect.height / 2);
            
            const rotateX = deltaY * -10;
            const rotateY = deltaX * 10;
            
            card.style.transform = `
                perspective(1000px) 
                rotateX(${rotateX}deg) 
                rotateY(${rotateY}deg) 
                translateZ(20px)
                scale3d(1.05, 1.05, 1.05)
            `;
            
            card.style.boxShadow = `
                ${-deltaX * 25}px ${-deltaY * 25}px 50px rgba(0,0,0,0.3),
                0 0 80px rgba(6, 214, 160, 0.2)
            `;
        }

        function reset3DTilt(e) {
            const card = e.currentTarget;
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0) scale3d(1, 1, 1)';
            card.style.boxShadow = '0 20px 40px rgba(0,0,0,0.2)';
        }

        // Apply 3D tilt to cards
        document.querySelectorAll('.feature-card, .service-card').forEach(card => {
            card.addEventListener('mousemove', handle3DTilt);
            card.addEventListener('mouseleave', reset3DTilt);
        });

        // Enhanced Back to Top with Rocket Animation
        const toTop = document.getElementById('toTop');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 500) {
                toTop.classList.add('show');
            } else {
                toTop.classList.remove('show');
            }
        });

        toTop.addEventListener('click', () => {
            // Add rocket launch animation
            toTop.style.transform = 'translateY(-200px) scale(0.5)';
            toTop.style.opacity = '0';
            
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
            
            setTimeout(() => {
                toTop.style.transform = 'translateY(-8px) scale(1)';
                toTop.style.opacity = '1';
            }, 1000);
        });

        // Dynamic Particle System (optional enhancement)
        function createParticle() {
            const particle = document.createElement('div');
            particle.style.cssText = `
                position: fixed;
                width: 2px;
                height: 2px;
                background: #06d6a0;
                border-radius: 50%;
                pointer-events: none;
                z-index: -1;
                opacity: 0.7;
            `;
            
            const x = Math.random() * window.innerWidth;
            const y = Math.random() * window.innerHeight;
            
            particle.style.left = x + 'px';
            particle.style.top = y + 'px';
            
            document.body.appendChild(particle);
            
            const duration = Math.random() * 3000 + 2000;
            const dx = (Math.random() - 0.5) * 100;
            const dy = -Math.random() * 100 - 50;
            
            particle.animate([
                { transform: 'translate(0, 0)', opacity: 0.7 },
                { transform: `translate(${dx}px, ${dy}px)`, opacity: 0 }
            ], {
                duration: duration,
                easing: 'ease-out'
            }).addEventListener('finish', () => {
                particle.remove();
            });
        }

        // Create particles periodically
        setInterval(createParticle, 500);

        // Advanced Brand Slider Pause on Hover
        const brandTrack = document.querySelector('.brand-track');
        if (brandTrack) {
            brandTrack.addEventListener('mouseenter', () => {
                brandTrack.style.animationPlayState = 'paused';
            });
            
            brandTrack.addEventListener('mouseleave', () => {
                brandTrack.style.animationPlayState = 'running';
            });
        }

        // Parallax Effect for Background Elements
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.hero-3d-bg, .floating-elements');
            
            parallaxElements.forEach(element => {
                const speed = element.classList.contains('hero-3d-bg') ? 0.5 : 0.3;
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });

        // Add CSS classes for enhanced interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to interactive elements
            const interactiveElements = document.querySelectorAll('.btn, .feature-card, .service-card, .testimonial');
            interactiveElements.forEach(element => {
                element.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            });
        });

        // Keyboard navigation enhancement
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Close any open modals or overlays
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });

        console.log('🚀 EV Mobile Station - Advanced UI Loaded Successfully!');
    </script>
</body>
</html>