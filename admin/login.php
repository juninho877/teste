<?php
session_start();
// A sua lógica de backend está perfeita e foi mantida sem alterações.
$pdo = new PDO("sqlite:api/.fzstoredev.db");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["usuario"] = $username;
        header("Location: index.php");
        exit();
    } else {
        $erro = "Usuário ou senha inválidos!";
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
    <style>
        /* Reset básico e fontes */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Estilo do corpo com imagem de fundo e centralização */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            /* IMPORTANTE: Substitua pelo URL da sua imagem de fundo */
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1579952363873-27f3bade9f55');
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
        }

        /* O "cartão" de login com efeito de vidro */
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 40px 30px;
            background: rgba(0, 0, 0, 0.3); /* Fundo semi-transparente */
            backdrop-filter: blur(10px); /* O efeito de vidro fosco */
            -webkit-backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            color: #fff;
            text-align: center;
        }

        .login-card h2 {
            margin-bottom: 30px;
            font-size: 2.2em;
            font-weight: 600;
        }

        /* Container para cada campo de entrada e seu ícone */
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #ccc;
        }

        /* Estilo dos campos de texto */
        .input-field {
            width: 100%;
            padding: 15px 15px 15px 50px; /* Espaço para o ícone */
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: #fff;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .input-field::placeholder { /* Estilo do texto de placeholder */
            color: #ccc;
        }

        .input-field:focus {
            outline: none;
            border-color: #28a745; /* Cor de destaque ao focar */
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
        }

        /* Estilo do botão de login */
        .submit-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            background-color: #28a745;
            color: #fff;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Estilo da mensagem de erro */
        .error-message {
            color: #ff6b6b; /* Vermelho claro para erro */
            background-color: rgba(255, 107, 107, 0.1);
            border: 1px solid rgba(255, 107, 107, 0.3);
            padding: 10px;
            margin-top: 20px;
            border-radius: 8px;
            font-size: 0.9em;
        }

    </style>
</head>
<body>

<div class="login-card">
    <h2>Login</h2>
    <form method="POST" action="login.php" novalidate>
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="username" class="input-field" placeholder="Usuário" required>
        </div>
        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" class="input-field" placeholder="Senha" required>
        </div>
        <button type="submit" class="submit-btn">Entrar</button>
    </form>
    
    <?php if (isset($erro)): ?>
        <div class="error-message">
            <?php echo $erro; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>