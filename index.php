<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EV Mobile Power & Service Station - Smart Support for Stranded EVs</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #0e0f12;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: background 0.4s ease, box-shadow 0.3s ease;
        }

        .nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            left: 0; bottom: -6px;
            width: 0; height: 2px;
            background: #ffd700;
            transition: width 0.25s ease;
        }

        .nav-links a:hover::after { width: 100%; }

        .nav-links a:hover {
            color: #ffd700;
        }

        /* Parallax helpers */
        .parallax { will-change: background-position; }

        /* Hero Section */
        .hero {
            background: radial-gradient(circle at 20% 20%, rgba(102,126,234,0.25), transparent 40%),
                        radial-gradient(circle at 80% 10%, rgba(118,75,162,0.25), transparent 40%),
                        linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            margin-top: 70px;
            position: relative;
            overflow: hidden;
        }

        /* Floating shapes */
        .hero::before, .hero::after {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.25;
            animation: float 12s ease-in-out infinite;
        }
        .hero::before { background: #667eea; left: -200px; top: -200px; }
        .hero::after { background: #764ba2; right: -200px; bottom: -200px; animation-delay: 2s; }
        @keyframes float { 0%,100%{ transform: translateY(0) } 50%{ transform: translateY(30px) } }

        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.95;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-block;
            will-change: transform;
        }

        .btn-primary { background: #ffd700; color: #333; box-shadow: 0 12px 24px rgba(255,215,0,0.25); }
        .btn-primary:hover { background: #ffed4e; transform: translateY(-3px) scale(1.02); }

        .btn-secondary { background: transparent; color: white; border: 2px solid white; }
        .btn-secondary:hover { background: white; color: #333; transform: translateY(-3px) scale(1.02); }

        /* Sections */
        .features, .how-it-works, .services {
            padding: 5rem 2rem;
            position: relative;
        }
        .features { background: linear-gradient(rgba(255,255,255,0.96), rgba(255,255,255,0.96)), url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80'); }
        .how-it-works { background: linear-gradient(rgba(255,255,255,0.96), rgba(255,255,255,0.96)), url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80'); }
        .services { background: linear-gradient(rgba(255,255,255,0.92), rgba(255,255,255,0.92)), url('https://images.unsplash.com/photo-1581094794329-c8112a89af12?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80'); }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #222;
        }

        /* Reveal on scroll */
        .reveal { opacity: 0; transform: translateY(24px); transition: opacity 0.6s ease, transform 0.6s ease; }
        .reveal.in { opacity: 1; transform: translateY(0); }

        /* Cards */
        .features-grid, .services-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .feature-card, .service-card { background: white; border-radius: 16px; box-shadow: 0 12px 32px rgba(0,0,0,0.08); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .feature-card { padding: 2rem; text-align: center; }
        .feature-card:hover, .service-card:hover { transform: translateY(-10px); box-shadow: 0 24px 48px rgba(0,0,0,0.12); }
        .tilt-card { will-change: transform; }

        .feature-icon { font-size: 3rem; color: #667eea; margin-bottom: 1rem; }
        .feature-card h3 { font-size: 1.5rem; margin-bottom: 1rem; color: #333; }

        .service-card { border-radius: 16px; overflow: hidden; }
        .service-image { height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; }
        .service-content { padding: 2rem; }

        /* Footer */
        .footer { background: #111318; color: white; padding: 3rem 2rem; text-align: center; }
        .footer-content { max-width: 1200px; margin: 0 auto; }
        .social-links { display: flex; justify-content: center; gap: 1rem; margin: 2rem 0; }
        .social-links a { color: white; font-size: 1.5rem; transition: color 0.3s; }
        .social-links a:hover { color: #ffd700; }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hero-content h1 { font-size: 2.5rem; }
            .cta-buttons { flex-direction: column; align-items: center; }
            .btn { width: 200px; }
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
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php">Sign Up</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero parallax">
        <div class="hero-content reveal">
            <h1>EV Mobile Power & Service Station</h1>
            <p>Smart support for stranded electric vehicles. Get instant help with charging and mechanical support when you need it most.</p>
            <div class="cta-buttons">
                <a href="signup.php" class="btn btn-primary">Get Started</a>
                <a href="#how-it-works" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features parallax">
        <div class="container">
            <h2 class="section-title reveal">Why Choose Our Service?</h2>
            <div class="features-grid">
                <div class="feature-card tilt-card reveal">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>On-Demand Charging</h3>
                    <p>Specialized mobile charging units arrive at your location with portable fast chargers to get you back on the road quickly.</p>
                </div>
                <div class="feature-card tilt-card reveal">
                    <div class="feature-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Emergency Mechanical Support</h3>
                    <p>Professional technicians provide on-site repairs, tire replacement, and diagnostics for all types of issues.</p>
                </div>
                <div class="feature-card tilt-card reveal">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Digital Platform</h3>
                    <p>Easy-to-use website and mobile app for requesting help, sharing location, and tracking service vehicle arrival.</p>
                </div>
                <div class="feature-card tilt-card reveal">
                    <div class="feature-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Real-Time Tracking</h3>
                    <p>Track your service vehicle in real-time and get accurate estimated arrival times for peace of mind.</p>
                </div>
                <div class="feature-card tilt-card reveal">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Round-the-clock emergency support for EV owners stranded in remote areas or on highways.</p>
                </div>
                <div class="feature-card tilt-card reveal">
                    <div class="feature-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Secure Payments</h3>
                    <p>Multiple payment options with secure transaction processing and transparent pricing.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works parallax">
        <div class="container">
            <h2 class="section-title reveal">How It Works</h2>
            <div class="steps">
                <div class="step reveal">
                    <div class="step-number">1</div>
                    <h3>Request Help</h3>
                    <p>Use our website or mobile app to request assistance. Share your location and describe your vehicle issue.</p>
                </div>
                <div class="step reveal">
                    <div class="step-number">2</div>
                    <h3>Get Matched</h3>
                    <p>Our system automatically matches you with the nearest available service vehicle and qualified technician.</p>
                </div>
                <div class="step reveal">
                    <div class="step-number">3</div>
                    <h3>Track Arrival</h3>
                    <p>Monitor your service vehicle's real-time location and estimated arrival time through our tracking system.</p>
                </div>
                <div class="step reveal">
                    <div class="step-number">4</div>
                    <h3>Receive Service</h3>
                    <p>Get professional charging or mechanical support at your location. Pay securely through our platform.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services parallax">
        <div class="container">
            <h2 class="section-title reveal">Our Services</h2>
            <div class="services-grid">
                <div class="service-card tilt-card reveal">
                    <div class="service-image">
                        <i class="fas fa-charging-station"></i>
                    </div>
                    <div class="service-content">
                        <h3>Mobile EV Charging</h3>
                        <p>Fast charging solutions delivered to your location with portable charging units capable of charging most EV models.</p>
                        <ul style="margin-top: 1rem; padding-left: 1.5rem;">
                            <li>50kW+ fast charging capability</li>
                            <li>Compatible with all major EV brands</li>
                            <li>Emergency roadside charging</li>
                        </ul>
                    </div>
                </div>
                <div class="service-card tilt-card reveal">
                    <div class="service-image">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <div class="service-content">
                        <h3>Mechanical Support</h3>
                        <p>Professional technicians provide on-site repairs and maintenance for all types of vehicle issues.</p>
                        <ul style="margin-top: 1rem; padding-left: 1.5rem;">
                            <li>Tire replacement and repair</li>
                            <li>Minor electrical repairs</li>
                            <li>Diagnostic services</li>
                        </ul>
                    </div>
                </div>
                <div class="service-card tilt-card reveal">
                    <div class="service-image">
                        <i class="fas fa-route"></i>
                    </div>
                    <div class="service-content">
                        <h3>Emergency Response</h3>
                        <p>24/7 emergency response for stranded EVs in remote areas, highways, and urban locations.</p>
                        <ul style="margin-top: 1rem; padding-left: 1.5rem;">
                            <li>Priority emergency dispatch</li>
                            <li>GPS location tracking</li>
                            <li>Real-time status updates</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <h3>EV Mobile Power & Service Station</h3>
            <p>Smart support for stranded electric vehicles</p>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
            </div>
            <p>&copy; 2024 EV Mobile Station. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href.length > 1) {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });

        // Header background on scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(102, 126, 234, 0.95)';
                header.style.boxShadow = '0 6px 18px rgba(0,0,0,0.15)';
            } else {
                header.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                header.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            }
        });

        // Reveal on scroll
        const reveals = document.querySelectorAll('.reveal');
        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        reveals.forEach(el => io.observe(el));

        // Parallax backgrounds
        const parallaxes = document.querySelectorAll('.parallax');
        window.addEventListener('scroll', () => {
            const sc = window.pageYOffset;
            parallaxes.forEach(el => {
                // slow parallax shift
                el.style.backgroundPositionY = `${-sc * 0.15}px`;
            });
        });

        // Tilt effect on cards
        function handleTilt(e){
            const card = e.currentTarget;
            const rect = card.getBoundingClientRect();
            const cx = rect.left + rect.width/2;
            const cy = rect.top + rect.height/2;
            const dx = (e.clientX - cx) / (rect.width/2);
            const dy = (e.clientY - cy) / (rect.height/2);
            const max = 8; // degrees
            card.style.transform = `rotateX(${(-dy*max).toFixed(2)}deg) rotateY(${(dx*max).toFixed(2)}deg) translateY(-6px)`;
            card.style.boxShadow = `${-dx*12}px ${dy*12}px 24px rgba(0,0,0,0.15)`;
        }
        function resetTilt(e){
            const card = e.currentTarget;
            card.style.transform = 'translateY(-6px) rotateX(0) rotateY(0)';
            card.style.boxShadow = '0 24px 48px rgba(0,0,0,0.12)';
        }
        document.querySelectorAll('.tilt-card').forEach(card => {
            card.addEventListener('mousemove', handleTilt);
            card.addEventListener('mouseleave', resetTilt);
        });
    </script>
</body>
</html> 