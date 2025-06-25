<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$pdo = new PDO("sqlite:api/.fzstoredev.db");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mensagem = "";
$tipoMensagem = "";

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
        $mensagem = "Senha atual incorreta!";
        $tipoMensagem = "error";
    } elseif ($nova_senha !== $confirmar_senha) {
        $mensagem = "As novas senhas não coincidem!";
        $tipoMensagem = "error";
    } elseif (!empty($nova_senha) && strlen($nova_senha) < 6) {
        $mensagem = "A nova senha deve ter pelo menos 6 caracteres!";
        $tipoMensagem = "error";
    } elseif (empty($novo_usuario)) {
        $mensagem = "O nome de usuário não pode estar vazio!";
        $tipoMensagem = "error";
    } else {
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, password = ? WHERE username = ?");
        $stmt->execute([$novo_usuario, $nova_senha_hash, $usuario_atual]);

        $_SESSION["usuario"] = $novo_usuario;
        $mensagem = "Usuário e senha alterados com sucesso!";
        $tipoMensagem = "success";
    }
}

$pageTitle = "Configurações da Conta";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">Configurações da Conta</h1>
    <p class="page-subtitle">Gerencie suas informações de acesso e preferências do sistema</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Settings Form -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informações da Conta</h3>
                <p class="card-subtitle">Atualize seu nome de usuário e senha</p>
            </div>
            <div class="card-body">
                <?php if ($mensagem): ?>
                    <div class="alert alert-<?php echo $tipoMensagem; ?> mb-6">
                        <i class="fas fa-<?php echo $tipoMensagem === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="novo_usuario" class="form-label">
                            <i class="fas fa-user mr-2"></i>
                            Nome de Usuário
                        </label>
                        <input type="text" id="novo_usuario" name="novo_usuario" class="form-input" 
                               value="<?php echo htmlspecialchars($_SESSION['usuario']); ?>" required>
                        <p class="text-xs text-muted mt-1">Este será seu nome de login no sistema</p>
                    </div>

                    <div class="border-t border-gray-200 my-6 pt-6">
                        <h4 class="text-lg font-semibold mb-4">Alterar Senha</h4>
                        
                        <div class="form-group">
                            <label for="senha_atual" class="form-label">
                                <i class="fas fa-lock mr-2"></i>
                                Senha Atual
                            </label>
                            <div class="relative">
                                <input type="password" id="senha_atual" name="senha_atual" class="form-input pr-10" 
                                       placeholder="Digite sua senha atual para confirmar" required>
                                <button type="button" class="password-toggle" data-target="senha_atual">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="nova_senha" class="form-label">
                                    <i class="fas fa-key mr-2"></i>
                                    Nova Senha
                                </label>
                                <div class="relative">
                                    <input type="password" id="nova_senha" name="nova_senha" class="form-input pr-10" 
                                           placeholder="Mínimo de 6 caracteres" required>
                                    <button type="button" class="password-toggle" data-target="nova_senha">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirmar_senha" class="form-label">
                                    <i class="fas fa-check mr-2"></i>
                                    Confirmar Nova Senha
                                </label>
                                <div class="relative">
                                    <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-input pr-10" 
                                           placeholder="Repita a nova senha" required>
                                    <button type="button" class="password-toggle" data-target="confirmar_senha">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar Alterações
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="space-y-6">
        <!-- Account Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informações da Conta</h3>
            </div>
            <div class="card-body">
                <div class="flex items-center gap-3 mb-4">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION["usuario"], 0, 2)); ?>
                    </div>
                    <div>
                        <h4 class="font-semibold"><?php echo htmlspecialchars($_SESSION["usuario"]); ?></h4>
                        <p class="text-sm text-muted">Administrador</p>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted">Último acesso:</span>
                        <span><?php echo date('d/m/Y H:i'); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Status:</span>
                        <span class="text-success-600 font-medium">Ativo</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Tips -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🔒 Dicas de Segurança</h3>
            </div>
            <div class="card-body">
                <div class="space-y-3 text-sm">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-shield-alt text-success-500 mt-0.5"></i>
                        <div>
                            <p class="font-medium">Use senhas fortes</p>
                            <p class="text-muted">Combine letras, números e símbolos</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-clock text-warning-500 mt-0.5"></i>
                        <div>
                            <p class="font-medium">Altere regularmente</p>
                            <p class="text-muted">Recomendamos trocar a cada 3 meses</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-user-secret text-primary-500 mt-0.5"></i>
                        <div>
                            <p class="font-medium">Mantenha em segredo</p>
                            <p class="text-muted">Nunca compartilhe suas credenciais</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ações Rápidas</h3>
            </div>
            <div class="card-body">
                <div class="space-y-2">
                    <a href="index.php" class="btn btn-secondary w-full text-sm">
                        <i class="fas fa-home"></i>
                        Voltar ao Dashboard
                    </a>
                    <a href="logout.php" class="btn btn-danger w-full text-sm">
                        <i class="fas fa-sign-out-alt"></i>
                        Sair da Conta
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .alert {
        padding: 1rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
    }
    
    .alert-success {
        background: var(--success-50);
        color: var(--success-600);
        border: 1px solid rgba(34, 197, 94, 0.2);
    }
    
    .alert-error {
        background: var(--danger-50);
        color: var(--danger-600);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .password-toggle {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 4px;
        transition: var(--transition);
    }
    
    .password-toggle:hover {
        color: var(--text-primary);
        background: var(--bg-tertiary);
    }
    
    [data-theme="dark"] .alert-success {
        background: rgba(34, 197, 94, 0.1);
        color: var(--success-400);
    }
    
    [data-theme="dark"] .alert-error {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger-400);
    }
    
    [data-theme="dark"] .border-gray-200 {
        border-color: var(--border-color);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const toggleButtons = document.querySelectorAll('.password-toggle');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    });

    // Password strength indicator
    const newPasswordInput = document.getElementById('nova_senha');
    const confirmPasswordInput = document.getElementById('confirmar_senha');
    
    function checkPasswordMatch() {
        if (confirmPasswordInput.value && newPasswordInput.value !== confirmPasswordInput.value) {
            confirmPasswordInput.setCustomValidity('As senhas não coincidem');
        } else {
            confirmPasswordInput.setCustomValidity('');
        }
    }
    
    newPasswordInput.addEventListener('input', checkPasswordMatch);
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
});
</script>

<?php include "includes/footer.php"; ?>