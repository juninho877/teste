<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/banner_functions.php';

$pageTitle = isset($_GET['banner']) ? "Gerador de Banner" : "Selecionar Modelo de Banner";
include "includes/header.php";

// Obter dados dos jogos
$jogos = obterJogosDeHoje();

$jogosPorBanner = 5;
$gruposDeJogos = array_chunk(array_keys($jogos), $jogosPorBanner);

if (isset($_GET['banner'])) {
    // Tela de visualização dos banners
    $tipo_banner = $_GET['banner'];
    $geradorScript = '';

    switch ($tipo_banner) {
        case '1': $geradorScript = 'gerar_fut.php'; break;
        case '2': $geradorScript = 'gerar_fut_2.php'; break;
        case '3': $geradorScript = 'gerar_fut_3.php'; break;
        default:
            echo "<div class='card'><div class='card-body text-center'><p class='text-danger'>Tipo de banner inválido!</p></div></div>";
            include "includes/footer.php";
            exit();
    }
?>

<div class="page-header">
    <h1 class="page-title">Banners de Jogos de Hoje</h1>
    <p class="page-subtitle">Modelo <?php echo $tipo_banner; ?> - <?php echo count($jogos); ?> jogos disponíveis</p>
</div>

<div class="mb-6 flex flex-wrap gap-4">
    <a href="<?php echo basename(__FILE__); ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i>
        Voltar para Seleção
    </a>
    <?php if (!empty($jogos)): ?>
        <a href="<?php echo $geradorScript; ?>?download_all=1" class="btn btn-success" target="_blank">
            <i class="fas fa-download"></i>
            Baixar Todos (ZIP)
        </a>
    <?php endif; ?>
</div>

<?php if (empty($jogos)): ?>
    <div class="card">
        <div class="card-body text-center py-12">
            <div class="mb-4">
                <i class="fas fa-futbol text-6xl text-gray-300"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Nenhum jogo disponível</h3>
            <p class="text-muted">Não há jogos programados para hoje no momento.</p>
        </div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($gruposDeJogos as $index => $grupo): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Banner Parte <?php echo $index + 1; ?></h3>
                    <p class="card-subtitle"><?php echo count($grupo); ?> jogos neste banner</p>
                </div>
                <div class="card-body">
                    <div class="banner-preview-container">
                        <div class="loading-overlay">
                            <div class="loading-spinner"></div>
                            <span>Carregando...</span>
                        </div>
                        <img src="<?php echo $geradorScript; ?>?grupo=<?php echo $index; ?>" 
                             alt="Banner Parte <?php echo $index + 1; ?>" 
                             class="banner-preview-image"
                             onload="hideLoading(this)"
                             onerror="showError(this)">
                    </div>
                    <a href="<?php echo $geradorScript; ?>?grupo=<?php echo $index; ?>&download=1" 
                       class="btn btn-primary w-full mt-4" target="_blank">
                        <i class="fas fa-download"></i>
                        Baixar Banner
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
} else {
    // Tela de seleção de modelo
?>

<div class="page-header">
    <h1 class="page-title">Escolha o Modelo de Banner</h1>
    <p class="page-subtitle">Selecione o estilo que melhor se adequa às suas necessidades</p>
</div>

<?php if (empty($jogos)): ?>
    <div class="card">
        <div class="card-body text-center py-12">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle text-6xl text-warning-500"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Nenhum jogo disponível</h3>
            <p class="text-muted">Não há jogos programados para hoje para gerar as prévias dos banners.</p>
        </div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="card group hover:shadow-xl transition-all duration-300">
                <div class="card-header">
                    <h3 class="card-title">Banner Modelo <?php echo $i; ?></h3>
                    <p class="card-subtitle">Estilo profissional e moderno</p>
                </div>
                <div class="card-body">
                    <div class="banner-preview-container">
                        <div class="loading-overlay">
                            <div class="loading-spinner"></div>
                            <span>Carregando...</span>
                        </div>
                        <img src="gerar_fut<?php echo $i > 1 ? '_' . $i : ''; ?>.php?grupo=0" 
                             alt="Prévia do Banner <?php echo $i; ?>" 
                             class="banner-preview-image"
                             onload="hideLoading(this)"
                             onerror="showError(this)">
                    </div>
                    <a href="?banner=<?php echo $i; ?>" class="btn btn-primary w-full mt-4 group-hover:bg-primary-600">
                        <i class="fas fa-check"></i>
                        Usar este Modelo
                    </a>
                </div>
            </div>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<style>
    .banner-preview-container {
        position: relative;
        width: 100%;
        aspect-ratio: 16/9;
        background: var(--bg-secondary);
        border-radius: var(--border-radius);
        overflow: hidden;
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .banner-preview-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .banner-preview-image.loaded {
        opacity: 1;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--bg-secondary);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        z-index: 2;
        transition: opacity 0.3s ease;
    }

    .loading-overlay.hidden {
        opacity: 0;
        pointer-events: none;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid var(--border-color);
        border-top: 3px solid var(--primary-500);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .error-state {
        color: var(--text-muted);
        text-align: center;
    }

    .flex-wrap {
        flex-wrap: wrap;
    }

    .py-12 {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }

    .text-6xl {
        font-size: 3.75rem;
        line-height: 1;
    }

    .text-xl {
        font-size: 1.25rem;
        line-height: 1.75rem;
    }

    .font-semibold {
        font-weight: 600;
    }

    .mb-2 {
        margin-bottom: 0.5rem;
    }

    .mb-4 {
        margin-bottom: 1rem;
    }

    .mb-6 {
        margin-bottom: 1.5rem;
    }

    .mt-4 {
        margin-top: 1rem;
    }

    .w-full {
        width: 100%;
    }

    /* Dark theme adjustments */
    [data-theme="dark"] .text-gray-300 {
        color: var(--text-muted);
    }

    [data-theme="dark"] .text-warning-500 {
        color: #f59e0b;
    }
</style>

<script>
function hideLoading(img) {
    const container = img.closest('.banner-preview-container');
    const overlay = container.querySelector('.loading-overlay');
    if (overlay) {
        overlay.classList.add('hidden');
    }
    img.classList.add('loaded');
}

function showError(img) {
    const container = img.closest('.banner-preview-container');
    const overlay = container.querySelector('.loading-overlay');
    if (overlay) {
        overlay.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
                <p>Erro ao carregar banner</p>
            </div>
        `;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Timeout para mostrar erro se demorar muito
    const images = document.querySelectorAll('.banner-preview-image');
    
    images.forEach(function(img) {
        const timeout = setTimeout(function() {
            if (!img.classList.contains('loaded')) {
                showError(img);
            }
        }, 15000); // 15 segundos
        
        img.addEventListener('load', function() {
            clearTimeout(timeout);
        });
        
        img.addEventListener('error', function() {
            clearTimeout(timeout);
        });
    });
});
</script>

<?php
} // Fim do if/else principal

include "includes/footer.php";
?>