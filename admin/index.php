<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$pageTitle = "Página Inicial";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Bem-vindo de volta, <?php echo htmlspecialchars($_SESSION["usuario"]); ?>! Gerencie seus banners e configurações.</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Banners Gerados</p>
                    <p class="text-2xl font-bold text-primary">156</p>
                </div>
                <div class="w-12 h-12 bg-primary-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-image text-primary-500"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Jogos Hoje</p>
                    <p class="text-2xl font-bold text-success-500">12</p>
                </div>
                <div class="w-12 h-12 bg-success-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-futbol text-success-500"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Último Acesso</p>
                    <p class="text-2xl font-bold text-warning-500"><?php echo date('H:i'); ?></p>
                </div>
                <div class="w-12 h-12 bg-warning-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-warning-500"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ações Rápidas</h3>
            <p class="card-subtitle">Acesse rapidamente as funcionalidades principais</p>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 gap-3">
                <a href="painel.php" class="btn btn-primary">
                    <i class="fas fa-film"></i>
                    Gerar Banner Filme/Série
                </a>
                <a href="futbanner.php" class="btn btn-secondary">
                    <i class="fas fa-futbol"></i>
                    Gerar Banner Futebol
                </a>
                <a href="setting.php" class="btn btn-secondary">
                    <i class="fas fa-cog"></i>
                    Configurações
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Personalização</h3>
            <p class="card-subtitle">Configure a aparência dos seus banners</p>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 gap-3">
                <a href="logo.php" class="btn btn-secondary">
                    <i class="fas fa-image"></i>
                    Gerenciar Logos
                </a>
                <a href="background.php" class="btn btn-secondary">
                    <i class="fas fa-photo-video"></i>
                    Gerenciar Fundos
                </a>
                <a href="card.php" class="btn btn-secondary">
                    <i class="fas fa-th-large"></i>
                    Gerenciar Cards
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Atividade Recente</h3>
        <p class="card-subtitle">Últimas ações realizadas no sistema</p>
    </div>
    <div class="card-body">
        <div class="space-y-4">
            <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-image text-primary-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="font-medium">Banner gerado com sucesso</p>
                    <p class="text-sm text-muted">Filme: Interestelar - há 2 horas</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                <div class="w-10 h-10 bg-success-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-futbol text-success-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="font-medium">Banner futebol criado</p>
                    <p class="text-sm text-muted">5 jogos de hoje - há 3 horas</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                <div class="w-10 h-10 bg-warning-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-cog text-warning-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="font-medium">Configurações atualizadas</p>
                    <p class="text-sm text-muted">Logo alterado - há 1 dia</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    [data-theme="dark"] .bg-gray-50 {
        background-color: var(--bg-tertiary);
    }
    
    .space-y-4 > * + * {
        margin-top: 1rem;
    }
</style>

<?php include "includes/footer.php"; ?>