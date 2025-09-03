<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EV Mobile Power & Service Station - Smart Support for Stranded EVs</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #ffd700;
            --bg: #0e0f12;
            --card: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.12);
            --text: #e9edf1;
            --muted: #9aa3ae;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: var(--bg);
        }

        body::before {
            content: '';
            position: fixed;
            inset: -20% -20% auto -20%;
            height: 120vh;
            background: radial-gradient(40% 40% at 20% 10%, rgba(102,126,234,0.22) 0%, rgba(102,126,234,0) 70%),
                        radial-gradient(40% 40% at 80% 0%, rgba(118,75,162,0.22) 0%, rgba(118,75,162,0) 70%);
            filter: blur(40px);
            z-index: -1;
            animation: drift 28s ease-in-out infinite alternate;
        }
        @keyframes drift { 0% { transform: translateY(-4%) translateX(0); } 100% { transform: translateY(4%) translateX(-2%); } }

        .header {
            background: rgba(20, 22, 28, 0.5);
            -webkit-backdrop-filter: blur(14px) saturate(140%);
            backdrop-filter: blur(14px) saturate(140%);
            border-bottom: 1px solid var(--border);
            color: var(--text);
            padding: 0.75rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: background 0.4s ease, box-shadow 0.3s ease;
        }
        .header.scrolled { box-shadow: 0 10px 30px rgba(0,0,0,0.25); background: rgba(20,22,28,0.85); }

        .nav { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 2rem; }
        .logo { font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
        .nav-links { display: flex; gap: 2rem; list-style: none; }
        .nav-links a { color: var(--text); text-decoration: none; transition: color 0.3s; position: relative; font-weight: 500; }
        .nav-links a::after { content: ''; position: absolute; left: 0; bottom: -6px; width: 0; height: 2px; background: var(--accent); transition: width 0.25s ease; }
        .nav-links a:hover::after { width: 100%; }
        .nav-links a:hover { color: var(--accent); }

        .parallax { will-change: background-position; }

        .hero { min-height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center; color: white; margin-top: 64px; position: relative; overflow: hidden; }
        .hero-gradient { position: absolute; inset: 0; pointer-events: none; background: radial-gradient(60% 60% at 50% 20%, rgba(102,126,234,0.25), rgba(0,0,0,0) 60%), radial-gradient(50% 50% at 85% 20%, rgba(118,75,162,0.25), rgba(0,0,0,0) 60%); filter: blur(10px); }
        .cta-buttons { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 1rem 2rem; border: none; border-radius: 14px; text-decoration: none; font-weight: 700; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; display: inline-block; will-change: transform; }
        .btn-primary { background: var(--accent); color: #14161c; box-shadow: 0 18px 36px rgba(255,215,0,0.28); }
        .btn-primary:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 26px 54px rgba(255,215,0,0.35); }
        .btn-secondary { background: transparent; color: var(--text); border: 2px solid var(--text); }
        .btn-secondary:hover { background: var(--text); color: #14161c; transform: translateY(-3px) scale(1.02); }

        .features, .how-it-works, .services, .testimonials, .brands { padding: 5rem 2rem; position: relative; }
        .features, .how-it-works { background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02)); }
        .services { background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.04)); }

        .container { max-width: 1200px; margin: 0 auto; }
        .section-title { text-align: center; font-size: 2.3rem; margin-bottom: 3rem; color: var(--text); }
        .reveal { opacity: 0; transform: translateY(24px); transition: opacity 0.6s ease, transform 0.6s ease; }
        .reveal.in { opacity: 1; transform: translateY(0); }

        .features-grid, .services-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; }
        .feature-card, .service-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; backdrop-filter: blur(6px); box-shadow: 0 18px 40px rgba(0,0,0,0.18); transition: transform 0.2s, box-shadow 0.2s; }
        .feature-card { padding: 2rem; text-align: center; }
        .feature-card:hover, .service-card:hover { transform: translateY(-10px); box-shadow: 0 28px 64px rgba(0,0,0,0.25); }
        .tilt-card { will-change: transform; }
        .feature-icon { font-size: 2.6rem; color: var(--primary); margin-bottom: 1rem; }
        .feature-card h3 { font-size: 1.4rem; margin-bottom: 0.75rem; color: var(--text); }
        .feature-card p { color: var(--muted); }
        .service-image { height: 200px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 2.6rem; }
        .service-content { padding: 2rem; }

        .brands { background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.02)); padding: 3rem 2rem; }
        .brand-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 2rem; opacity: 0.8; filter: grayscale(100%); }
        .brand { height: 40px; background: var(--card); border: 1px dashed var(--border); border-radius: 10px; display:flex; align-items:center; justify-content:center; color: var(--muted); font-weight: 600; }

        .testimonials { background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.03)); }
        .testimonials-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; }
        .testimonial { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; position: relative; }
        .testimonial::before { content: '“'; position:absolute; top:-10px; left:12px; font-size: 3rem; color: var(--primary); opacity: 0.3; }
        .author { display:flex; align-items:center; gap:0.75rem; margin-top: 1rem; color: var(--muted); font-size: 0.95rem; }
        .author i { color: var(--primary); }

        .wave { width: 100%; height: 60px; background: radial-gradient(60px 60px at 50% -10px, rgba(255,255,255,0.06) 0, transparent 70%); opacity: 0.6; }

        #toTop { position: fixed; right: 18px; bottom: 18px; width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; display:flex; align-items:center; justify-content:center; cursor: pointer; box-shadow: 0 12px 30px rgba(0,0,0,0.25); opacity: 0; visibility: hidden; transition: opacity 0.25s, transform 0.25s; }
        #toTop.show { opacity: 1; visibility: visible; transform: translateY(-4px); }

        .footer { background: #0b0c10; color: var(--muted); padding: 3rem 2rem; text-align: center; }
        .footer-content { max-width: 1200px; margin: 0 auto; }
        .social-links { display: flex; justify-content: center; gap: 1rem; margin: 1.25rem 0; }
        .social-links a { color: var(--muted); font-size: 1.3rem; transition: color 0.3s; }
        .social-links a:hover { color: var(--accent); }

        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hero-content h1 { font-size: 2.3rem; }
            .btn { width: 200px; }
            .brand-row { grid-template-columns: repeat(3, 1fr); }
        }

        .carousel-caption { bottom: 22%; text-shadow: 0 4px 16px rgba(0,0,0,0.6); }
        .carousel-caption h1 { font-size: 3rem; }
        .carousel-item::after { content:''; position:absolute; inset:0; background: linear-gradient(180deg, rgba(0,0,0,0.35), rgba(0,0,0,0.45)); }
        .carousel-item > img { object-fit: cover; height: calc(100vh - 64px); }
        .carousel .btn { position: relative; z-index: 2; }
    </style>
</head>
<body>
    <header class="header" id="header">
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

    <!-- Hero with Bootstrap Carousel -->
    <section id="home" class="hero">
        <div class="hero-gradient"></div>
        <div id="heroCarousel" class="carousel slide carousel-fade w-100" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active position-relative">
                    <img src="https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=2000&q=80" class="d-block w-100" alt="EV Charging">
                    <div class="carousel-caption">
                        <h1 class="mb-3">On-Demand EV Charging</h1>
                        <p class="mb-4">Fast mobile chargers dispatched to your exact location.</p>
                        <div class="cta-buttons">
                            <a href="signup.php" class="btn btn-primary">Get Started</a>
                            <a href="#services" class="btn btn-secondary">Explore Services</a>
                        </div>
                    </div>
                </div>
                <div class="carousel-item position-relative">
                    <img src="https://images.unsplash.com/photo-1531427186611-ecfd6d936c79?auto=format&fit=crop&w=2000&q=80" class="d-block w-100" alt="Roadside Assistance">
                    <div class="carousel-caption">
                        <h1 class="mb-3">Emergency Roadside Support</h1>
                        <p class="mb-4">Technicians available 24/7 for quick assistance.</p>
                        <div class="cta-buttons">
                            <a href="request-service.php" class="btn btn-primary">Request Service</a>
                            <a href="#how-it-works" class="btn btn-secondary">How It Works</a>
                        </div>
                    </div>
                </div>
                <div class="carousel-item position-relative">
                    <img src="https://images.unsplash.com/photo-1606549502658-9d6a5b31b46a?auto=format&fit=crop&w=2000&q=80" class="d-block w-100" alt="Tracking">
                    <div class="carousel-caption">
                        <h1 class="mb-3">Live Tracking & ETA</h1>
                        <p class="mb-4">Track your service vehicle's real-time arrival.</p>
                        <div class="cta-buttons">
                            <a href="login.php" class="btn btn-primary">Login</a>
                            <a href="#features" class="btn btn-secondary">See Features</a>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </section>

    <div class="wave"></div>

    <!-- Features Section -->
    <section id="features" class="features parallax">
        <div class="container">
            <h2 class="section-title reveal">Why Choose Our Service?</h2>
            <div class="features-grid">
                <div class="feature-card tilt-card reveal">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <h3>On-Demand Charging</h3>
                    <p>Specialized mobile charging units arrive at your location with portable fast chargers to get you back on the road quickly.</p>
                </div>
                <div class="feature-card tilt-card reveal">
                    <div class="feature-icon"><i class="fas fa-tools"></i></div>
                    <h3>Emergency Mechanical Support</h3>
                    <p>Professional technicians provide on-site repairs, tire replacement, and diagnostics for all types of issues.</p>
                </div>
                <div class="feature-card tilt-card reveal">
                    <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                    <h3>Digital Platform</h3>
                    <p>Easy-to-use website and mobile app for requesting help, sharing location, and tracking service vehicle arrival.</p>
                </div>
                <div class="feature-card tilt-card reveal">
                    <div class="feature-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <h3>Real-Time Tracking</h3>
                    <p>Track your service vehicle in real-time and get accurate estimated arrival times for peace of mind.</p>
                </div>
                <div class="feature-card tilt-card reveal">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3>24/7 Support</h3>
                    <p>Round-the-clock emergency support for EV owners stranded in remote areas or on highways.</p>
                </div>
                <div class="feature-card tilt-card reveal">
                    <div class="feature-icon"><i class="fas fa-credit-card"></i></div>
                    <h3>Secure Payments</h3>
                    <p>Multiple payment options with secure transaction processing and transparent pricing.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="wave"></div>

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

    <div class="wave"></div>

    <!-- Services Section -->
    <section id="services" class="services parallax">
        <div class="container">
            <h2 class="section-title reveal">Our Services</h2>
            <div class="services-grid">
                <div class="service-card tilt-card reveal">
                    <div class="service-image"><i class="fas fa-charging-station"></i></div>
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
                    <div class="service-image"><i class="fas fa-wrench"></i></div>
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
                    <div class="service-image"><i class="fas fa-route"></i></div>
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

    <!-- Brands strip -->
    <section class="brands">
        <div class="container">
            <div class="brand-row reveal">
                <div class="brand">TESLA</div>
                <div class="brand">NISSAN</div>
                <div class="brand">KIA</div>
                <div class="brand">TATA</div>
                <div class="brand">HYUNDAI</div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials">
        <div class="container">
            <h2 class="section-title reveal">What Users Say</h2>
            <div class="testimonials-grid" id="testimonials">
                <div class="testimonial reveal">
                    <p>Super fast response. The technician arrived in 25 minutes and got me charged in no time!</p>
                    <div class="author"><i class="fas fa-user-circle"></i> Ananya • Kochi</div>
                </div>
                <div class="testimonial reveal">
                    <p>Professional service and real-time tracking gave me complete peace of mind on the highway.</p>
                    <div class="author"><i class="fas fa-user-circle"></i> Rahul • Trivandrum</div>
                </div>
                <div class="testimonial reveal">
                    <p>Easy to use and reliable. The cost was transparent and payment was smooth.</p>
                    <div class="author"><i class="fas fa-user-circle"></i> Meera • Kottayam</div>
                </div>
            </div>
        </div>
    </section>

    <div id="toTop"><i class="fas fa-arrow-up"></i></div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href.length > 1) {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
        const header = document.getElementById('header');
        window.addEventListener('scroll', function() { if (window.scrollY > 80) header.classList.add('scrolled'); else header.classList.remove('scrolled'); });
        const reveals = document.querySelectorAll('.reveal');
        const io = new IntersectionObserver((entries) => { entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('in'); io.unobserve(entry.target); } }); }, { threshold: 0.12 });
        reveals.forEach(el => io.observe(el));
        const parallaxes = document.querySelectorAll('.parallax');
        window.addEventListener('scroll', () => { const sc = window.pageYOffset; parallaxes.forEach(el => { el.style.backgroundPositionY = `${-sc * 0.15}px`; }); });
        function handleTilt(e){ const card = e.currentTarget; const rect = card.getBoundingClientRect(); const cx = rect.left + rect.width/2; const cy = rect.top + rect.height/2; const dx = (e.clientX - cx) / (rect.width/2); const dy = (e.clientY - cy) / (rect.height/2); const max = 8; card.style.transform = `rotateX(${(-dy*max).toFixed(2)}deg) rotateY(${(dx*max).toFixed(2)}deg) translateY(-6px)`; card.style.boxShadow = `${-dx*12}px ${dy*12}px 24px rgba(0,0,0,0.18)`; }
        function resetTilt(e){ const card = e.currentTarget; card.style.transform = 'translateY(-6px) rotateX(0) rotateY(0)'; card.style.boxShadow = '0 24px 48px rgba(0,0,0,0.18)'; }
        document.querySelectorAll('.tilt-card').forEach(card => { card.addEventListener('mousemove', handleTilt); card.addEventListener('mouseleave', resetTilt); });
        const toTop = document.getElementById('toTop');
        window.addEventListener('scroll', () => { if (window.scrollY > 450) toTop.classList.add('show'); else toTop.classList.remove('show'); });
        toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        const heroCarousel = document.getElementById('heroCarousel');
        if (heroCarousel) { const c = new bootstrap.Carousel(heroCarousel, { interval: 5000, ride: 'carousel', pause: false }); }
    </script>
</body>
</html> 