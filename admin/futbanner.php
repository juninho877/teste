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
    <!-- Grid FORÇADO para 2 colunas em banners -->
    <div class="banners-grid-container">
        <?php foreach ($gruposDeJogos as $index => $grupo): ?>
            <div class="banner-grid-item">
                <div class="card banner-card">
                    <div class="card-header">
                        <h3 class="card-title">Banner Parte <?php echo $index + 1; ?></h3>
                        <p class="card-subtitle"><?php echo count($grupo); ?> jogos neste banner</p>
                    </div>
                    <div class="card-body">
                        <div class="banner-preview-container">
                            <div class="banner-loading-overlay" id="loading-<?php echo $index; ?>">
                                <div class="loading-spinner"></div>
                                <span>Carregando...</span>
                            </div>
                            <img data-src="<?php echo $geradorScript; ?>?grupo=<?php echo $index; ?>&t=<?php echo time(); ?>" 
                                 alt="Banner Parte <?php echo $index + 1; ?>" 
                                 class="banner-preview-image banner-img lazy-load"
                                 data-index="<?php echo $index; ?>"
                                 style="display: none;">
                            <div class="banner-error-overlay" id="error-<?php echo $index; ?>" style="display: none;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Erro ao carregar</span>
                                <button class="retry-btn" onclick="retryLoadImage(<?php echo $index; ?>)">
                                    <i class="fas fa-redo"></i> Tentar Novamente
                                </button>
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
    <!-- Grid para modelos - 3 colunas -->
    <div class="models-grid-container">
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="model-grid-item">
                <div class="card model-card group hover:shadow-xl transition-all duration-300">
                    <div class="card-header">
                        <h3 class="card-title">Banner Modelo <?php echo $i; ?></h3>
                        <p class="card-subtitle">Estilo profissional e moderno</p>
                    </div>
                    <div class="card-body">
                        <div class="banner-preview-container model-preview">
                            <div class="banner-loading-overlay" id="model-loading-<?php echo $i; ?>">
                                <div class="loading-spinner"></div>
                                <span>Carregando...</span>
                            </div>
                            <img data-src="gerar_fut<?php echo $i > 1 ? '_' . $i : ''; ?>.php?grupo=0&t=<?php echo time(); ?>" 
                                 alt="Prévia do Banner <?php echo $i; ?>" 
                                 class="banner-preview-image model-img lazy-load"
                                 data-model="<?php echo $i; ?>"
                                 style="display: none;">
                            <div class="banner-error-overlay" id="model-error-<?php echo $i; ?>" style="display: none;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Erro ao carregar</span>
                                <button class="retry-btn" onclick="retryLoadModel(<?php echo $i; ?>)">
                                    <i class="fas fa-redo"></i> Tentar Novamente
                                </button>
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
    /* GRID ESPECÍFICO PARA BANNERS - SEMPRE 2 COLUNAS */
    .banners-grid-container {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 2rem;
        margin-bottom: 2rem;
        width: 100%;
    }

    .banner-grid-item {
        width: 100%;
        min-width: 0;
    }

    /* GRID ESPECÍFICO PARA MODELOS - 3 COLUNAS */
    .models-grid-container {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 1.5rem;
        margin-bottom: 2rem;
        width: 100%;
    }

    .model-grid-item {
        width: 100%;
        min-width: 0;
    }

    /* RESPONSIVIDADE ESPECÍFICA */
    @media (max-width: 1024px) {
        .banners-grid-container {
            grid-template-columns: 1fr !important;
        }
        .models-grid-container {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    @media (max-width: 768px) {
        .banners-grid-container,
        .models-grid-container {
            grid-template-columns: 1fr !important;
            gap: 1rem;
        }
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
    }

    .banner-loading-overlay {
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

    .banner-error-overlay {
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
        text-align: center;
        padding: 1rem;
    }

    .retry-btn {
        background: var(--primary-500);
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        font-size: 0.875rem;
        margin-top: 0.5rem;
        transition: var(--transition);
    }

    .retry-btn:hover {
        background: var(--primary-600);
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
let imageRetryCount = {};

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM carregado, inicializando sistema...');
    
    // Verificar qual página estamos
    const bannerImages = document.querySelectorAll('.banner-img');
    const modelImages = document.querySelectorAll('.model-img');
    
    console.log('🖼️ Banners encontrados:', bannerImages.length);
    console.log('🎨 Modelos encontrados:', modelImages.length);
    
    if (bannerImages.length > 0) {
        // Página de visualização de banners
        totalImages = bannerImages.length;
        console.log('📊 Iniciando carregamento de', totalImages, 'banners');
        
        // Configurar lazy loading para banners
        bannerImages.forEach((img, index) => {
            console.log('🔧 Configurando banner', index);
            imageRetryCount[`banner-${index}`] = 0;
            loadImageLazy(img, index, 'banner');
        });
        
    } else if (modelImages.length > 0) {
        // Página de seleção de modelos
        totalImages = modelImages.length;
        console.log('📊 Iniciando carregamento de', totalImages, 'modelos');
        
        // Configurar lazy loading para modelos
        modelImages.forEach((img, index) => {
            console.log('🔧 Configurando modelo', index + 1);
            imageRetryCount[`model-${index + 1}`] = 0;
            loadImageLazy(img, index + 1, 'model');
        });
    } else {
        console.log('ℹ️ Nenhuma imagem encontrada para carregar');
    }
});

// Função para carregar imagem com lazy loading
function loadImageLazy(img, index, type) {
    const dataSrc = img.getAttribute('data-src');
    if (!dataSrc) {
        console.error('❌ data-src não encontrado para', type, index);
        handleImageError(img, index, type);
        return;
    }
    
    // Criar nova imagem para pré-carregamento
    const tempImg = new Image();
    
    // Timeout para cada imagem individual
    const timeout = setTimeout(() => {
        console.log('⏰ Timeout para', type, index);
        handleImageError(img, index, type);
    }, 15000); // 15 segundos por imagem
    
    tempImg.onload = function() {
        clearTimeout(timeout);
        console.log('✅', type, 'carregado:', index);
        img.src = dataSrc;
        handleImageLoad(img, index, type);
    };
    
    tempImg.onerror = function() {
        clearTimeout(timeout);
        console.log('❌ Erro no', type, ':', index);
        handleImageError(img, index, type);
    };
    
    // Adicionar timestamp único para evitar cache
    const separator = dataSrc.includes('?') ? '&' : '?';
    const uniqueUrl = dataSrc + separator + 'cache_bust=' + Date.now() + '&retry=' + (imageRetryCount[`${type}-${index}`] || 0);
    
    console.log('🔄 Carregando', type, index, ':', uniqueUrl);
    tempImg.src = uniqueUrl;
}

// Função para tentar novamente carregar uma imagem
function retryLoadImage(index) {
    const img = document.querySelector(`.banner-img[data-index="${index}"]`);
    if (img) {
        imageRetryCount[`banner-${index}`] = (imageRetryCount[`banner-${index}`] || 0) + 1;
        console.log('🔄 Tentativa', imageRetryCount[`banner-${index}`], 'para banner', index);
        
        // Esconder erro e mostrar loading
        const errorElement = document.getElementById(`error-${index}`);
        const loadingElement = document.getElementById(`loading-${index}`);
        if (errorElement) errorElement.style.display = 'none';
        if (loadingElement) loadingElement.style.display = 'flex';
        
        // Tentar carregar novamente
        loadImageLazy(img, index, 'banner');
    }
}

// Função para tentar novamente carregar um modelo
function retryLoadModel(index) {
    const img = document.querySelector(`.model-img[data-model="${index}"]`);
    if (img) {
        imageRetryCount[`model-${index}`] = (imageRetryCount[`model-${index}`] || 0) + 1;
        console.log('🔄 Tentativa', imageRetryCount[`model-${index}`], 'para modelo', index);
        
        // Esconder erro e mostrar loading
        const errorElement = document.getElementById(`model-error-${index}`);
        const loadingElement = document.getElementById(`model-loading-${index}`);
        if (errorElement) errorElement.style.display = 'none';
        if (loadingElement) loadingElement.style.display = 'flex';
        
        // Tentar carregar novamente
        loadImageLazy(img, index, 'model');
    }
}

// Handler unificado para carregamento de imagem
function handleImageLoad(img, index, type) {
    loadedImages++;
    
    console.log('✅', type, 'carregado:', index, '- Total:', loadedImages, '/', totalImages);
    
    // Esconder loading e mostrar imagem
    const loadingElement = document.getElementById(`${type === 'banner' ? 'loading' : 'model-loading'}-${index}`);
    if (loadingElement) loadingElement.style.display = 'none';
    
    img.style.display = 'block';
    img.style.opacity = '1';
}

// Handler unificado para erro de imagem
function handleImageError(img, index, type) {
    loadedImages++;
    
    console.log('❌ Erro no', type, ':', index);
    
    // Mostrar erro
    const loadingElement = document.getElementById(`${type === 'banner' ? 'loading' : 'model-loading'}-${index}`);
    const errorElement = document.getElementById(`${type === 'banner' ? 'error' : 'model-error'}-${index}`);
    
    if (loadingElement) loadingElement.style.display = 'none';
    if (errorElement) errorElement.style.display = 'flex';
}

// Expor funções globalmente para os botões de retry
window.retryLoadImage = retryLoadImage;
window.retryLoadModel = retryLoadModel;

// Debug: Log quando a página termina de carregar
window.addEventListener('load', function() {
    console.log('🎯 Página totalmente carregada');
});
</script>

<?php
} // Fim do if/else principal

include "includes/footer.php";
?>