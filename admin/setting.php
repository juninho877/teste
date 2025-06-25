<?php
// Sua lógica PHP original, mantida integralmente.
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$pdo = new PDO("sqlite:api/.fzstoredev.db");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_atual = $_SESSION["usuario"];
    $novo_usuario = trim($_POST["novo_usuario"]);
    $senha_atual = trim($_POST["senha_atual"]);
    $nova_senha = trim($_POST["nova_senha"]);
    $confirmar_senha = trim($_POST["confirmar_senha"]);

    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE username = ?");
    $stmt->execute([$usuario_atual]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($senha_atual, $user["password"])) {
        $mensagem = '<div class="alert alert-danger">❌ Senha atual incorreta!</div>';
    } elseif ($nova_senha !== $confirmar_senha) {
        $mensagem = '<div class="alert alert-danger">❌ As novas senhas não coincidem!</div>';
    } elseif (!empty($nova_senha) && strlen($nova_senha) < 6) {
        $mensagem = '<div class="alert alert-danger">❌ A nova senha deve ter pelo menos 6 caracteres!</div>';
    } elseif (empty($novo_usuario)) {
        $mensagem = '<div class="alert alert-danger">❌ O nome de usuário não pode estar vazio!</div>';
    } else {
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, password = ? WHERE username = ?");
        $stmt->execute([$novo_usuario, $nova_senha_hash, $usuario_atual]);

        $_SESSION["usuario"] = $novo_usuario;
        $mensagem = '<div class="alert alert-success">✅ Usuário e senha alterados com sucesso!</div>';
    }
}

$pageTitle = "Configurações da Conta";
include "includes/header.php";
?>

<style>
    /* Estilos dos formulários importados do nosso design */
    .form-group { position: relative; margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-muted); }
    .input-field {
        width: 100%; padding: 12px 15px; background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px;
        color: #fff; font-size: 1em; transition: all 0.3s ease;
    }
    .input-field:focus { outline: none; border-color: var(--accent-color); }
    
    /* Ícone para mostrar/ocultar senha */
    .password-toggle {
        position: absolute;
        top: 50%;
        right: 15px;
        transform: translateY(-50%);
        color: var(--text-muted);
        cursor: pointer;
        padding-top: 15px; /* Alinhamento vertical com o campo */
    }

    .submit-btn {
        width: 100%; padding: 12px 30px; border: none; border-radius: 8px;
        background-color: var(--success-color); color: #fff; font-size: 1.1em;
        font-weight: bold; cursor: pointer; transition: all 0.3s ease;
        margin-top: 10px; display: flex; align-items: center; justify-content: center;
    }
    .submit-btn i { margin-right: 8px; }
    .submit-btn:hover { background-color: #218838; transform: translateY(-2px); }

    /* Estilos para as mensagens de alerta */
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; }
    .alert-success { background-color: rgba(40, 167, 69, 0.2); border: 1px solid rgba(40, 167, 69, 0.5); color: #28a745; }
    .alert-danger { background-color: rgba(231, 76, 60, 0.2); border: 1px solid rgba(231, 76, 60, 0.5); color: #e74c3c; }

    hr { border-color: rgba(255, 255, 255, 0.1); margin: 30px 0; }
</style>

<div class="page-header">
    <h1><i class="fas fa-user-cog" style="color: var(--accent-color);"></i> Configurações da Conta</h1>
</div>

<div class="content-card">
    <?php if ($mensagem) echo $mensagem; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="novo_usuario">Nome de Usuário</label>
            <input type="text" id="novo_usuario" name="novo_usuario" class="input-field" value="<?php echo htmlspecialchars($_SESSION['usuario']); ?>" required>
        </div>

        <hr>

        <h3 style="margin-bottom: 20px; font-weight: 600;">Alterar Senha</h3>
        
        <div class="form-group">
            <label for="senha_atual">Senha Atual</label>
            <input type="password" id="senha_atual" name="senha_atual" class="input-field" placeholder="Digite sua senha atual para confirmar" required>
            <i class="fas fa-eye password-toggle"></i>
        </div>

        <div class="form-group">
            <label for="nova_senha">Nova Senha</label>
            <input type="password" id="nova_senha" name="nova_senha" class="input-field" placeholder="Mínimo de 6 caracteres" required>
            <i class="fas fa-eye password-toggle"></i>
        </div>

        <div class="form-group">
            <label for="confirmar_senha">Confirmar Nova Senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" class="input-field" placeholder="Repita a nova senha" required>
            <i class="fas fa-eye password-toggle"></i>
        </div>
        
        <button type="submit" class="submit-btn">
            <i class="fas fa-save"></i>Salvar Alterações
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lógica para mostrar/ocultar senha
    const togglePasswordIcons = document.querySelectorAll('.password-toggle');

    togglePasswordIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            // Alterna o ícone
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });
});
</script>

<?php 
include "includes/footer.php"; 
?>