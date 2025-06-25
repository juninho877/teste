<?php
session_start();

// Incluir as classes necessárias
require_once 'config/database.php';
require_once 'classes/User.php';

// Inicializar banco de dados (criar tabelas se não existirem)
try {
    $db = Database::getInstance();
    $db->createTables();
} catch (Exception $e) {
    $erro = "Erro de conexão com o banco de dados. Verifique as configurações.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    try {
        $user = new User();
        $result = $user->authenticate($username, $password);
        
        if ($result['success']) {
            $_SESSION["usuario"] = $result['user']['username'];
            $_SESSION["user_id"] = $result['user']['id'];
            $_SESSION["role"] = $result['user']['role'];
            header("Location: index.php");
            exit();
        } else {
            $erro = $result['message'];
        }
    } catch (Exception $e) {
        $erro = "Erro interno do sistema. Tente novamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FutBanner</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-50: #eff6ff;
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --danger-500: #ef4444;
            --danger-50: #fef2f2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            opacity: 0.9;
            font-size: 0.875rem;
        }

        .login-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-500);
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            margin-top: 0.75rem;
        }

        .submit-btn {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: var(--danger-50);
            color: var(--danger-500);
            padding: 1rem;
            border-radius: 12px;
            margin-top: 1rem;
            font-size: 0.875rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .theme-toggle {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Dark theme */
        [data-theme="dark"] body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }

        [data-theme="dark"] .login-container {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .login-form {
            color: #f1f5f9;
        }

        [data-theme="dark"] .form-label {
            color: #cbd5e1;
        }

        [data-theme="dark"] .form-input {
            background: rgba(15, 23, 42, 0.5);
            border-color: rgba(255, 255, 255, 0.1);
            color: #f1f5f9;
        }

        [data-theme="dark"] .form-input:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: var(--primary-500);
        }

        [data-theme="dark"] .input-icon {
            color: #64748b;
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            animation: slideIn 0.6s ease-out;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .logo {
            animation: pulse 2s infinite;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-header,
            .login-form {
                padding: 1.5rem;
            }
        }

        .db-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: var(--primary-600);
            padding: 1rem;
            border-radius: 12px;
            margin-top: 1rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        [data-theme="dark"] .db-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-400);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <button class="theme-toggle" id="themeToggle">
            <i class="fas fa-moon"></i>
        </button>
        
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-futbol"></i>
            </div>
            <h1 class="login-title">FutBanner</h1>
            <p class="login-subtitle">Faça login para acessar o painel</p>
        </div>

        <div class="login-form">
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username" class="form-label">Usuário</label>
                    <div style="position: relative;">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="form-input" placeholder="Digite seu usuário" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Senha</label>
                    <div style="position: relative;">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Digite sua senha" required>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i>
                    Entrar
                </button>

                <?php if (isset($erro)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $erro; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        // Theme Management
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const themeIcon = themeToggle.querySelector('i');

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        body.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }

        // Form animations
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
