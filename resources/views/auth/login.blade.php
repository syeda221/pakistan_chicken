<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Bin Sultan Sweets & Bakers</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #800000;
            --primary-hover: #a52a2a;
            --gold: #D4AF37;
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
            overflow: hidden;
        }

        .bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('{{ asset("assets/images/hero_bakery.png") }}');
            background-size: cover;
            background-position: center;
            z-index: -1;
            transform: scale(1.1);
            animation: slowZoom 20s infinite alternate;
        }

        @keyframes slowZoom {
            from { transform: scale(1); }
            to { transform: scale(1.1); }
        }

        .login-wrapper {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 3rem 2.5rem;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInUp 0.8s ease forwards;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .brand-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: var(--gold);
            margin-bottom: 0.5rem;
        }

        .brand-header p {
            color: rgba(255, 255, 255, 0.6);
            font-weight: 300;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 0.8rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gold);
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 15px 12px 45px;
            border-radius: 10px;
            color: white;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
            outline: none;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--gold);
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.2);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
            box-shadow: 0 10px 20px rgba(128, 0, 0, 0.3);
        }

        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(128, 0, 0, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .back-to-site {
            display: block;
            text-align: center;
            margin-top: 2rem;
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .back-to-site:hover {
            color: var(--gold);
        }

        .alert-error {
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #fca5a5;
            padding: 12px;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            list-style: none;
        }

        /* Autofill removal */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: white;
            -webkit-box-shadow: 0 0 0px 1000px #2a2a2a inset;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</head>
<body>

    <div class="bg-overlay"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand-header">
                <h2>Bin Sultan<span>.</span></h2>
                <p>Bakery Management System</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                @if ($errors->any())
                    <ul class="alert-error">
                        @foreach ($errors->all() as $error)
                            <li><i class="fas fa-exclamation-circle mr-2"></i> {{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="form-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control" placeholder="Email Address" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>

                <button type="submit" class="btn-login">
                    Sign In
                </button>
            </form>

            <a href="/" class="back-to-site">
                <i class="fas fa-arrow-left mr-2"></i> Back to Homepage
            </a>
        </div>
    </div>

</body>
</html>