<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bin Sultan Sweets & Bakers | Premium Artisan Bakery</title>

    <!-- Google Fonts: Playfair Display for Serif, Outfit for Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #800000; /* Deep Maroon */
            --primary-light: #a52a2a;
            --gold: #D4AF37;
            --gold-light: #F4DF4E;
            --cream: #FFFDD0;
            --dark: #1a1a1a;
            --light: #f9f9f9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: 'Outfit', sans-serif;
            background-color: var(--dark);
            color: var(--light);
            scroll-behavior: smooth;
        }

        /* Hero Section */
        .hero {
            position: relative;
            height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: var(--dark);
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('{{ asset("assets/images/hero_bakery.png") }}');
            background-size: cover;
            background-position: center;
            transform: scale(1.1);
            animation: slowZoom 20s infinite alternate;
            z-index: 1;
        }

        @keyframes slowZoom {
            from { transform: scale(1); }
            to { transform: scale(1.1); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 900px;
            padding: 2rem;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1s ease forwards 0.5s;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .brand-top {
            font-size: 1.2rem;
            letter-spacing: 6px;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 1rem;
            display: block;
            font-weight: 500;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(3rem, 8vw, 5.5rem);
            line-height: 1.1;
            margin-bottom: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, var(--gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-desc {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 3rem;
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-container {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 1.2rem 2.8rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 10px 30px rgba(128, 0, 0, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(128, 0, 0, 0.5);
            background-color: var(--primary-light);
        }

        .btn-outline {
            border: 2px solid var(--gold);
            color: var(--gold);
            background: transparent;
        }

        .btn-outline:hover {
            background: var(--gold);
            color: var(--dark);
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.3);
        }

        /* Floating Navigation */
        nav {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 2rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
        }

        .logo span {
            color: var(--gold);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--gold);
        }

        /* Decoration */
        .scroll-down {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .scroll-down i {
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        /* Glass Cards Bottom */
        .features {
            position: absolute;
            bottom: 60px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            z-index: 5;
            display: none; /* Hidden on mobile by default, shown via media query if space allows */
        }

        @media (min-height: 850px) {
            .features {
                display: grid;
            }
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.1);
        }

        .feature-card i {
            font-size: 1.5rem;
            color: var(--gold);
        }

        .feature-card h3 {
            font-size: 1rem;
            margin-bottom: 2px;
        }

        .feature-card p {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
        }

        @media (max-width: 768px) {
            .nav-links { display: none; }
            h1 { font-size: 3rem; }
            .hero-content { padding: 1.5rem; }
            .cta-container { flex-direction: column; width: 100%; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body class="antialiased">

    <nav>
        <a href="/" class="logo">Bin Sultan<span>.</span></a>
        <div class="nav-links">
            @if (Route::has('login'))
                <a href="{{ route('login') }}" style="color: var(--gold); font-weight: 700;">Login</a>
            @endif
        </div>
    </nav>

    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-content">
            <span class="brand-top">Since 1995</span>
            <h1>Bin Sultan Sweets & Bakers</h1>
            <p class="hero-desc">
                Experience the fine art of baking with our handcrafted sweets, gourmet pastries, and traditional delicacies made with the finest ingredients and timeless passion.
            </p>
            
            <div class="cta-container">
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class="fa-solid fa-gauge-high mr-2"></i> Access Panel
                    </a>
                @endif
            </div>
        </div>

        <div class="scroll-down">
            <span>Scroll</span>
            <i class="fa-solid fa-chevron-down"></i>
        </div>

        <div class="features">
            <div class="feature-card">
                <i class="fa-solid fa-wheat-awn"></i>
                <div>
                    <h3>Fine Ingredients</h3>
                    <p>Only the best for you.</p>
                </div>
            </div>
            <div class="feature-card">
                <i class="fa-solid fa-clock"></i>
                <div>
                    <h3>Always Fresh</h3>
                    <p>Baked daily with love.</p>
                </div>
            </div>
            <div class="feature-card">
                <i class="fa-solid fa-medal"></i>
                <div>
                    <h3>Legacy Taste</h3>
                    <p>Over 25 years of excellence.</p>
                </div>
            </div>
        </div>
    </section>

</body>
</html>