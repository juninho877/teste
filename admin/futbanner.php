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

<!-- Modal de Progresso -->
<div id="loadingModal" class="loading-modal">
    <div class="loading-modal-content">
        <div class="loading-header">
            <h3>Gerando Banners</h3>
            <p>Por favor, aguarde enquanto os banners são criados...</p>
        </div>
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="progress-text">
                <span id="progressText">0%</span>
                <span id="progressStatus">Iniciando...</span>
            </div>
        </div>
        <div class="loading-animation">
            <div class="spinner"></div>
        </div>
    </div>
</div>

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
    <!-- Grid com 2 colunas para banners -->
    <div class="banners-grid">
        <?php foreach ($gruposDeJogos as $index => $grupo): ?>
            <div class="banner-card-wrapper">
                <div class="card banner-card">
                    <div class="card-header">
                        <h3 class="card-title">Banner Parte <?php echo $index + 1; ?></h3>
                        <p class="card-subtitle"><?php echo count($grupo); ?> jogos neste banner</p>
                    </div>
                    <div class="card-body">
                        <div class="banner-preview-container">
                            <img src="<?php echo $geradorScript; ?>?grupo=<?php echo $index; ?>&t=<?php echo time(); ?>" 
                                 alt="Banner Parte <?php echo $index + 1; ?>" 
                                 class="banner-preview-image"
                                 data-index="<?php echo $index; ?>"
                                 onload="handleImageLoad(this)"
                                 onerror="handleImageError(this)">
                            <div class="banner-loading" id="loading-<?php echo $index; ?>">
                                <div class="loading-spinner"></div>
                                <span>Carregando...</span>
                            </div>
                            <div class="banner-error" id="error-<?php echo $index; ?>" style="display: none;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Erro ao carregar</span>
                            </div>
                        </div>
                        <a href="<?php echo $geradorScript; ?>?grupo=<?php echo $index; ?>&download=1" 
                           class="btn btn-primary w-full mt-4" target="_blank">
                            <i class="fas fa-download"></i>
                            Baixar Banner
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
} else {
    // Tela de seleção de modelo
?>

<!-- Modal de Progresso para Modelos -->
<div id="loadingModal" class="loading-modal">
    <div class="loading-modal-content">
        <div class="loading-header">
            <h3>Carregando Modelos</h3>
            <p>Por favor, aguarde enquanto os modelos são carregados...</p>
        </div>
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="progress-text">
                <span id="progressText">0%</span>
                <span id="progressStatus">Iniciando...</span>
            </div>
        </div>
        <div class="loading-animation">
            <div class="spinner"></div>
        </div>
    </div>
</div>

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
    <!-- Grid com 3 colunas para seleção de modelos -->
    <div class="models-grid">
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="model-card-wrapper">
                <div class="card model-card group hover:shadow-xl transition-all duration-300">
                    <div class="card-header">
                        <h3 class="card-title">Banner Modelo <?php echo $i; ?></h3>
                        <p class="card-subtitle">Estilo profissional e moderno</p>
                    </div>
                    <div class="card-body">
                        <div class="banner-preview-container model-preview">
                            <img src="gerar_fut<?php echo $i > 1 ? '_' . $i : ''; ?>.php?grupo=0&t=<?php echo time(); ?>" 
                                 alt="Prévia do Banner <?php echo $i; ?>" 
                                 class="banner-preview-image model-image"
                                 data-model="<?php echo $i; ?>"
                                 onload="handleModelLoad(this)"
                                 onerror="handleModelError(this)">
                            <div class="banner-loading" id="model-loading-<?php echo $i; ?>">
                                <div class="loading-spinner"></div>
                                <span>Carregando...</span>
                            </div>
                            <div class="banner-error" id="model-error-<?php echo $i; ?>" style="display: none;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Erro ao carregar</span>
                            </div>
                        </div>
                        <a href="?banner=<?php echo $i; ?>" class="btn btn-primary w-full mt-4 group-hover:bg-primary-600">
                            <i class="fas fa-check"></i>
                            Usar este Modelo
                        </a>
                    </div>
                </div>
            </div>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<style>
    /* Grid para banners - 2 colunas fixas */
    .banners-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
        margin-bottom: 2rem;
    }

    /* Grid para modelos - 3 colunas fixas */
    .models-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    /* Wrappers dos cards */
    .banner-card-wrapper,
    .model-card-wrapper {
        width: 100%;
        min-width: 0; /* Permite que o conteúdo seja comprimido */
    }

    /* Responsividade para tablets */
    @media (max-width: 1024px) {
        .banners-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        .models-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
    }

    /* Responsividade para mobile */
    @media (max-width: 768px) {
        .banners-grid,
        .models-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        /* Ajustes específicos para mobile */
        .banner-preview-container {
            height: auto;
            min-height: 200px;
        }
        
        .model-preview {
            height: auto;
            min-height: 150px;
        }
    }

    /* Modal de Loading */
    .loading-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .loading-modal.show {
        opacity: 1;
        visibility: visible;
    }

    .loading-modal-content {
        background: var(--bg-primary);
        border-radius: 16px;
        padding: 2rem;
        max-width: 400px;
        width: 90%;
        text-align: center;
        box-shadow: var(--shadow-xl);
        border: 1px solid var(--border-color);
    }

    .loading-header h3 {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .loading-header p {
        color: var(--text-secondary);
        margin-bottom: 2rem;
    }

    .progress-container {
        margin-bottom: 2rem;
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: var(--bg-tertiary);
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 1rem;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-500), var(--primary-600));
        width: 0%;
        transition: width 0.3s ease;
        border-radius: 4px;
    }

    .progress-text {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.875rem;
    }

    #progressText {
        font-weight: 600;
        color: var(--primary-500);
    }

    #progressStatus {
        color: var(--text-secondary);
    }

    .loading-animation {
        margin-top: 1rem;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid var(--border-color);
        border-top: 3px solid var(--primary-500);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }

    /* Container dos banners */
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

    .model-preview {
        aspect-ratio: 3/2;
    }

    .banner-preview-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: opacity 0.3s ease;
        display: none;
    }

    .banner-preview-image.loaded {
        display: block;
    }

    .banner-loading {
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
    }

    .banner-error {
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
        gap: 0.5rem;
        color: var(--text-muted);
        z-index: 2;
    }

    .loading-spinner {
        width: 32px;
        height: 32px;
        border: 2px solid var(--border-color);
        border-top: 2px solid var(--primary-500);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Utilities */
    .flex-wrap { flex-wrap: wrap; }
    .py-12 { padding-top: 3rem; padding-bottom: 3rem; }
    .text-6xl { font-size: 3.75rem; line-height: 1; }
    .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
    .font-semibold { font-weight: 600; }
    .mb-2 { margin-bottom: 0.5rem; }
    .mb-4 { margin-bottom: 1rem; }
    .mb-6 { margin-bottom: 1.5rem; }
    .mt-4 { margin-top: 1rem; }
    .w-full { width: 100%; }

    /* Dark theme adjustments */
    [data-theme="dark"] .text-gray-300 {
        color: var(--text-muted);
    }

    [data-theme="dark"] .text-warning-500 {
        color: #f59e0b;
    }

    /* Hover effects */
    .group:hover {
        transform: translateY(-2px);
    }

    .transition-all {
        transition: all 0.3s ease;
    }

    .duration-300 {
        transition-duration: 300ms;
    }

    .hover\:shadow-xl:hover {
        box-shadow: var(--shadow-xl);
    }

    .group-hover\:bg-primary-600:hover {
        background-color: var(--primary-600);
    }

    /* Ajustes específicos para mobile */
    @media (max-width: 480px) {
        .loading-modal-content {
            padding: 1.5rem;
            margin: 1rem;
        }
        
        .card-header,
        .card-body {
            padding: 1rem;
        }
        
        .banner-preview-container {
            min-height: 150px;
        }
    }
</style>

<script>
// Variáveis globais para controle
let totalImages = 0;
let loadedImages = 0;
let modal = null;
let progressFill = null;
let progressText = null;
let progressStatus = null;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar elementos do modal
    modal = document.getElementById('loadingModal');
    progressFill = document.getElementById('progressFill');
    progressText = document.getElementById('progressText');
    progressStatus = document.getElementById('progressStatus');
    
    // Verificar qual página estamos
    const bannerImages = document.querySelectorAll('.banner-preview-image:not(.model-image)');
    const modelImages = document.querySelectorAll('.model-image');
    
    if (bannerImages.length > 0) {
        // Página de visualização de banners
        totalImages = bannerImages.length;
        showModal();
        updateProgress(0, 'Carregando banners...');
    } else if (modelImages.length > 0) {
        // Página de seleção de modelos
        totalImages = modelImages.length;
        showModal();
        updateProgress(0, 'Carregando modelos...');
    }
    
    // Timeout geral para fechar modal se demorar muito
    setTimeout(() => {
        if (modal && modal.classList.contains('show')) {
            hideModal();
        }
    }, 30000); // 30 segundos
});

// Função para mostrar o modal
function showModal() {
    if (modal) {
        modal.classList.add('show');
    }
}

// Função para esconder o modal
function hideModal() {
    if (modal) {
        setTimeout(() => {
            modal.classList.remove('show');
        }, 500);
    }
}

// Função para atualizar o progresso
function updateProgress(percentage, status) {
    if (progressFill) progressFill.style.width = percentage + '%';
    if (progressText) progressText.textContent = Math.round(percentage) + '%';
    if (progressStatus) progressStatus.textContent = status;
}

// Função para calcular progresso
function calculateProgress() {
    if (totalImages === 0) return 100;
    return (loadedImages / totalImages) * 100;
}

// Função para verificar se todos carregaram
function checkAllLoaded() {
    if (loadedImages >= totalImages) {
        updateProgress(100, 'Concluído!');
        setTimeout(() => {
            hideModal();
        }, 1000);
    }
}

// Handlers para imagens de banner
function handleImageLoad(img) {
    loadedImages++;
    const index = img.getAttribute('data-index');
    const loadingElement = document.getElementById(`loading-${index}`);
    
    // Esconder loading e mostrar imagem
    if (loadingElement) loadingElement.style.display = 'none';
    img.style.display = 'block';
    img.classList.add('loaded');
    
    const progress = calculateProgress();
    updateProgress(progress, `Carregado ${loadedImages}/${totalImages} banners`);
    
    checkAllLoaded();
}

function handleImageError(img) {
    loadedImages++;
    const index = img.getAttribute('data-index');
    const loadingElement = document.getElementById(`loading-${index}`);
    const errorElement = document.getElementById(`error-${index}`);
    
    // Mostrar erro
    if (loadingElement) loadingElement.style.display = 'none';
    if (errorElement) errorElement.style.display = 'flex';
    
    const progress = calculateProgress();
    updateProgress(progress, `Erro no banner ${loadedImages}/${totalImages}`);
    
    checkAllLoaded();
}

// Handlers para imagens de modelo
function handleModelLoad(img) {
    loadedImages++;
    const modelNumber = img.getAttribute('data-model');
    const loadingElement = document.getElementById(`model-loading-${modelNumber}`);
    
    // Esconder loading e mostrar imagem
    if (loadingElement) loadingElement.style.display = 'none';
    img.style.display = 'block';
    img.classList.add('loaded');
    
    const progress = calculateProgress();
    updateProgress(progress, `Carregado ${loadedImages}/${totalImages} modelos`);
    
    checkAllLoaded();
}

function handleModelError(img) {
    loadedImages++;
    const modelNumber = img.getAttribute('data-model');
    const loadingElement = document.getElementById(`model-loading-${modelNumber}`);
    const errorElement = document.getElementById(`model-error-${modelNumber}`);
    
    // Mostrar erro
    if (loadingElement) loadingElement.style.display = 'none';
    if (errorElement) errorElement.style.display = 'flex';
    
    const progress = calculateProgress();
    updateProgress(progress, `Erro no modelo ${loadedImages}/${totalImages}`);
    
    checkAllLoaded();
}

// Permitir fechar modal clicando fora
if (modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            hideModal();
        }
    });
}
</script>

<?php
} // Fim do if/else principal

include "includes/footer.php";
?>