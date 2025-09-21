<!DOCTYPE html>
<html lang="en">
<head>
    <title>Working on something cool, be back soon!</title>
    <link rel="shortcut icon" id="favicon" type="image/x-icon"  href="{{ Utils::getFavicon() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/antd/4.24.10/antd.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
            background: #0a0e27;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        .stars {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            opacity: 0.8;
            animation: twinkle 3s infinite;
        }

        .planet-system {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 800px;
            height: 600px;
            animation: rotate-system 60s infinite linear;
        }

        .orbit {
            position: absolute;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .orbit-1 { width: 200px; height: 200px; }
        .orbit-2 { width: 300px; height: 300px; }
        .orbit-3 { width: 400px; height: 400px; }

        .planet {
            position: absolute;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
        }

        .sun {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80px;
            height: 80px;
            background: radial-gradient(circle, #FFD700, #FF8C00);
            border-radius: 50%;
            animation: pulse-sun 4s infinite ease-in-out;
            box-shadow: 0 0 30px #FFD700, 0 0 60px #FF8C00;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .planet-1 {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #4FC3F7, #29B6F6);
            animation: orbit-1 15s infinite linear;
            top: -20px;
            left: 80px;
        }

        .planet-2 {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #81C784, #66BB6A);
            animation: orbit-2 25s infinite linear;
            top: -25px;
            left: 125px;
        }

        .planet-3 {
            width: 35px;
            height: 35px;
            background: linear-gradient(45deg, #FFB74D, #FF9800);
            animation: orbit-3 35s infinite linear;
            top: -17px;
            left: 182px;
        }

        .maintenance-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 10;
            color: white;
        }

        .maintenance-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
            background: linear-gradient(45deg, #FFD700, #FF8C00, #4FC3F7);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-flow 3s ease-in-out infinite;
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
        }

        .rocket {
            position: absolute;
            font-size: 40px;
            animation: rocket-fly 20s infinite linear;
            transform-origin: center;
        }

        .shooting-star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: linear-gradient(45deg, transparent, #fff, transparent);
            border-radius: 50%;
            animation: shooting 8s infinite linear;
        }

        .comet-tail {
            position: absolute;
            width: 100px;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
            animation: comet-move 12s infinite linear;
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .floating-element {
            position: absolute;
            font-size: 20px;
            opacity: 0.6;
            animation: float-space 6s infinite ease-in-out;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }

        @keyframes rotate-system {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        @keyframes orbit-1 {
            from { transform: rotate(0deg) translateX(100px) rotate(0deg); }
            to { transform: rotate(360deg) translateX(100px) rotate(-360deg); }
        }

        @keyframes orbit-2 {
            from { transform: rotate(0deg) translateX(150px) rotate(0deg); }
            to { transform: rotate(360deg) translateX(150px) rotate(-360deg); }
        }

        @keyframes orbit-3 {
            from { transform: rotate(0deg) translateX(200px) rotate(0deg); }
            to { transform: rotate(360deg) translateX(200px) rotate(-360deg); }
        }

        @keyframes pulse-sun {
            0%, 100% { transform: translate(-50%, -50%) scale(1); box-shadow: 0 0 30px #FFD700; }
            50% { transform: translate(-50%, -50%) scale(1.1); box-shadow: 0 0 50px #FFD700, 0 0 80px #FF8C00; }
        }

        @keyframes gradient-flow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes rocket-fly {
            0% { 
                left: -100px; 
                top: 20%; 
                transform: rotate(45deg); 
            }
            25% { 
                left: 50%; 
                top: 80%; 
                transform: rotate(135deg); 
            }
            50% { 
                left: 120%; 
                top: 30%; 
                transform: rotate(225deg); 
            }
            75% { 
                left: 30%; 
                top: 10%; 
                transform: rotate(315deg); 
            }
            100% { 
                left: -100px; 
                top: 20%; 
                transform: rotate(405deg); 
            }
        }

        @keyframes shooting {
            0% { 
                left: 120%; 
                top: 10%; 
                opacity: 0; 
                transform: rotate(225deg); 
            }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { 
                left: -20%; 
                top: 90%; 
                opacity: 0; 
                transform: rotate(225deg); 
            }
        }

        @keyframes comet-move {
            0% { 
                right: -150px; 
                top: 15%; 
                opacity: 0; 
                transform: rotate(-30deg); 
            }
            20% { opacity: 1; }
            80% { opacity: 1; }
            100% { 
                right: 120%; 
                top: 85%; 
                opacity: 0; 
                transform: rotate(-30deg); 
            }
        }

        @keyframes float-space {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.4; }
            50% { transform: translateY(-20px) rotate(180deg); opacity: 0.8; }
        }

        @media (max-width: 768px) {
            .maintenance-title { font-size: 32px; }
            .planet-system { width: 400px; height: 300px; }
            .orbit-1 { width: 120px; height: 120px; }
            .orbit-2 { width: 180px; height: 180px; }
            .orbit-3 { width: 240px; height: 240px; }
        }
    </style>
</head>
<body>
    <div class="stars"></div>
    
    <div class="planet-system">
        <div class="orbit orbit-1"></div>
        <div class="orbit orbit-2"></div>
        <div class="orbit orbit-3"></div>
        
        <div class="sun">🌟</div>
        <div class="planet planet-1">🌍</div>
        <div class="planet planet-2">🪐</div>
        <div class="planet planet-3">🌕</div>
    </div>

    <div class="maintenance-content">
        <h1 class="maintenance-title">Working on something cool!</h1>
    </div>

    <div class="rocket">🚀</div>
    <div class="shooting-star"></div>
    <div class="comet-tail"></div>

    <div class="floating-elements">
        <div class="floating-element" style="left: 10%; top: 20%; animation-delay: 0s;">✨</div>
        <div class="floating-element" style="right: 15%; top: 30%; animation-delay: 1s;">⭐</div>
        <div class="floating-element" style="left: 20%; bottom: 25%; animation-delay: 2s;">🌙</div>
        <div class="floating-element" style="right: 25%; bottom: 35%; animation-delay: 3s;">☄️</div>
        <div class="floating-element" style="left: 70%; top: 15%; animation-delay: 4s;">🛸</div>
    </div>

    <script>
        // Generate random stars
        function createStars() {
            const starsContainer = document.querySelector('.stars');
            for (let i = 0; i < 100; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.animationDelay = Math.random() * 3 + 's';
                star.style.animationDuration = (Math.random() * 2 + 2) + 's';
                starsContainer.appendChild(star);
            }
        }
        
        createStars();
    </script>
</body>
</html>