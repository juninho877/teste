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
logDebug("=== INÍCIO FUTBANNER.PHP ===");
$jogos = obterJogosDeHoje();
logDebug("Jogos obtidos: " . count($jogos));

$jogosPorBanner = 5;
$gruposDeJogos = array_chunk(array_keys($jogos), $jogosPorBanner);
logDebug("Grupos de jogos criados: " . count($gruposDeJogos));

if (isset($_GET['banner'])) {
    // Tela de visualização dos banners
    $tipo_banner = $_GET['banner'];
    $geradorScript = '';

    switch ($tipo_banner) {
        case '1': $geradorScript = 'gerar_fut.php'; break;
        case '2': $geradorScript = 'gerar_fut_2.php'; break;
        case '3': $geradorScript = 'gerar_fut_3.php'; break;
        default:
            logDebug("ERRO: Tipo de banner inválido: $tipo_banner");
            echo "<div class='card'><div class='card-body text-center'><p class='text-danger'>Tipo de banner inválido!</p></div></div>";
            include "includes/footer.php";
            exit();
    }

    logDebug("Tipo de banner selecionado: $tipo_banner | Script: $geradorScript");
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
            <div class="mt-4 text-sm text-muted">
                <p>Debug: <?php echo count($jogos); ?> jogos encontrados</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Grid para banners -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($gruposDeJogos as $index => $grupo): ?>
            <div class="card banner-card">
                <div class="card-header">
                    <h3 class="card-title">Banner Parte <?php echo $index + 1; ?></h3>
                    <p class="card-subtitle"><?php echo count($grupo); ?> jogos neste banner</p>
                </div>
                <div class="card-body">
                    <div class="banner-preview-container" id="container-<?php echo $index; ?>">
                        <!-- Loading inicial -->
                        <div class="banner-loading-overlay" id="loading-<?php echo $index; ?>">
                            <div class="loading-spinner"></div>
                            <div class="loading-text">
                                <span id="loading-text-<?php echo $index; ?>">Carregando banner...</span>
                                <div class="loading-details" id="loading-details-<?php echo $index; ?>"></div>
                            </div>
                            <div class="loading-progress" id="progress-<?php echo $index; ?>">
                                <div class="progress-bar" id="progress-bar-<?php echo $index; ?>"></div>
                            </div>
                        </div>
                        
                        <!-- Imagem do banner -->
                        <img id="banner-img-<?php echo $index; ?>" 
                             class="banner-preview-image" 
                             style="display: none;"
                             alt="Banner Parte <?php echo $index + 1; ?>"
                             data-grupo="<?php echo $index; ?>"
                             data-script="<?php echo $geradorScript; ?>">
                        
                        <!-- Overlay de erro -->
                        <div class="banner-error-overlay" id="error-<?php echo $index; ?>" style="display: none;">
                            <i class="fas fa-exclamation-triangle text-danger-500"></i>
                            <span class="error-title">Erro ao carregar banner</span>
                            <div class="error-details" id="error-details-<?php echo $index; ?>"></div>
                            <div class="error-actions">
                                <button class="retry-btn" onclick="retryLoadBanner(<?php echo $index; ?>)">
                                    <i class="fas fa-redo"></i> Tentar Novamente
                                </button>
                                <button class="debug-btn" onclick="toggleDebugInfo(<?php echo $index; ?>)">
                                    <i class="fas fa-bug"></i> Debug
                                </button>
                            </div>
                            <div class="debug-info" id="debug-<?php echo $index; ?>" style="display: none;"></div>
                        </div>
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
    logDebug("Exibindo tela de seleção de modelos");
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
            <div class="mt-4 text-sm text-muted">
                <p>Debug: Sistema funcionando, mas sem jogos para hoje</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Grid para modelos -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="card model-card group hover:shadow-xl transition-all duration-300">
                <div class="card-header">
                    <h3 class="card-title">Banner Modelo <?php echo $i; ?></h3>
                    <p class="card-subtitle">Estilo profissional e moderno</p>
                </div>
                <div class="card-body">
                    <div class="banner-preview-container model-preview" id="model-container-<?php echo $i; ?>">
                        <div class="banner-loading-overlay" id="model-loading-<?php echo $i; ?>">
                            <div class="loading-spinner"></div>
                            <div class="loading-text">
                                <span>Carregando modelo...</span>
                            </div>
                            <div class="loading-progress">
                                <div class="progress-bar"></div>
                            </div>
                        </div>
                        
                        <img id="model-img-<?php echo $i; ?>" 
                             class="banner-preview-image" 
                             style="display: none;"
                             alt="Prévia do Banner <?php echo $i; ?>"
                             data-model="<?php echo $i; ?>">
                        
                        <div class="banner-error-overlay" id="model-error-<?php echo $i; ?>" style="display: none;">
                            <i class="fas fa-exclamation-triangle text-danger-500"></i>
                            <span class="error-title">Erro ao carregar modelo</span>
                            <div class="error-actions">
                                <button class="retry-btn" onclick="retryLoadModel(<?php echo $i; ?>)">
                                    <i class="fas fa-redo"></i> Tentar Novamente
                                </button>
                            </div>
                            <div class="debug-info" id="model-debug-<?php echo $i; ?>"></div>
                        </div>
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
        min-height: 200px;
    }

    .model-preview {
        aspect-ratio: 3/2;
        min-height: 150px;
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
        padding: 1rem;
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
        gap: 0.75rem;
        color: var(--text-muted);
        z-index: 2;
        text-align: center;
        padding: 1rem;
    }

    .loading-text {
        text-align: center;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .loading-details {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
    }

    .error-title {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .error-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .retry-btn, .debug-btn {
        background: var(--primary-500);
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        font-size: 0.875rem;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .retry-btn:hover {
        background: var(--primary-600);
    }

    .debug-btn {
        background: var(--warning-500);
    }

    .debug-btn:hover {
        background: var(--warning-600);
    }

    .loading-spinner {
        width: 32px;
        height: 32px;
        border: 3px solid var(--border-color);
        border-top: 3px solid var(--primary-500);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .loading-progress {
        width: 100%;
        max-width: 200px;
        height: 6px;
        background: var(--border-color);
        border-radius: 3px;
        overflow: hidden;
        margin-top: 0.5rem;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-500), var(--primary-600));
        width: 0%;
        animation: progress 3s ease-in-out infinite;
        border-radius: 3px;
    }

    .error-details, .debug-info {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.5rem;
        max-width: 300px;
        word-wrap: break-word;
        text-align: left;
        background: var(--bg-tertiary);
        padding: 0.75rem;
        border-radius: var(--border-radius-sm);
        font-family: 'Courier New', monospace;
        line-height: 1.4;
        border: 1px solid var(--border-color);
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes progress {
        0% { width: 0%; }
        30% { width: 30%; }
        60% { width: 60%; }
        100% { width: 100%; }
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

    [data-theme="dark"] .text-danger-500 {
        color: var(--danger-500);
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

    /* Responsividade */
    @media (max-width: 768px) {
        .banner-preview-container {
            min-height: 150px;
        }
        
        .card-header,
        .card-body {
            padding: 1rem;
        }
        
        .error-actions {
            flex-direction: column;
        }
        
        .retry-btn, .debug-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script>
// Configurações globais
const BANNER_CONFIG = {
    maxRetries: 8,
    retryDelay: 2000,
    loadTimeout: 120000, // 2 minutos
    retryCount: {},
    debugMode: true,
    progressInterval: null
};

// URLs dos geradores
const GENERATOR_URLS = {
    1: 'gerar_fut.php',
    2: 'gerar_fut_2.php', 
    3: 'gerar_fut_3.php'
};

function debugLog(message) {
    if (BANNER_CONFIG.debugMode) {
        const timestamp = new Date().toLocaleTimeString();
        console.log(`[BANNER_DEBUG] ${timestamp}: ${message}`);
    }
}

function updateLoadingText(index, text) {
    const element = document.getElementById(`loading-text-${index}`);
    if (element) {
        element.textContent = text;
    }
}

function updateLoadingDetails(index, details) {
    const element = document.getElementById(`loading-details-${index}`);
    if (element) {
        element.textContent = details;
    }
}

function updateDebugInfo(elementId, info) {
    const debugElement = document.getElementById(elementId);
    if (debugElement) {
        debugElement.innerHTML = `<strong>Debug Info:</strong><br>${info}`;
    }
}

function animateProgress(index) {
    const progressBar = document.getElementById(`progress-bar-${index}`);
    if (!progressBar) return;
    
    let width = 0;
    const interval = setInterval(() => {
        width += Math.random() * 15;
        if (width > 90) width = 90; // Não chegar a 100% até carregar
        progressBar.style.width = width + '%';
    }, 500);
    
    return interval;
}

document.addEventListener('DOMContentLoaded', function() {
    debugLog('🚀 Sistema de carregamento de banners inicializado');
    
    <?php if (isset($_GET['banner'])): ?>
        // Carregar banners
        const bannerType = <?php echo json_encode($tipo_banner); ?>;
        const totalBanners = <?php echo count($gruposDeJogos); ?>;
        
        debugLog(`📊 Carregando ${totalBanners} banners do tipo ${bannerType}`);
        
        // Carregar banners com delay escalonado
        for (let i = 0; i < totalBanners; i++) {
            setTimeout(() => {
                loadBanner(i, bannerType);
            }, i * 800); // 800ms entre cada banner
        }
    <?php else: ?>
        // Carregar modelos
        debugLog('📊 Carregando 3 modelos de banner');
        
        for (let i = 1; i <= 3; i++) {
            setTimeout(() => {
                loadModel(i);
            }, (i - 1) * 600); // 600ms entre cada modelo
        }
    <?php endif; ?>
});

function loadBanner(index, bannerType) {
    const img = document.getElementById(`banner-img-${index}`);
    const loading = document.getElementById(`loading-${index}`);
    const error = document.getElementById(`error-${index}`);
    
    if (!img || !loading || !error) {
        debugLog(`❌ Elementos não encontrados para banner ${index}`);
        return;
    }
    
    debugLog(`🔄 Iniciando carregamento do banner ${index}`);
    
    // Reset estado
    img.style.display = 'none';
    loading.style.display = 'flex';
    error.style.display = 'none';
    
    // Atualizar textos de loading
    updateLoadingText(index, `Carregando banner ${index + 1}...`);
    updateLoadingDetails(index, 'Preparando requisição...');
    
    // Iniciar animação de progresso
    const progressInterval = animateProgress(index);
    
    // Construir URL com parâmetros únicos
    const generatorScript = GENERATOR_URLS[bannerType] || 'gerar_fut.php';
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(7);
    const url = `${generatorScript}?grupo=${index}&_t=${timestamp}&_r=${random}&_cache_bust=${Math.floor(Math.random() * 1000000)}`;
    
    debugLog(`📡 URL do banner ${index}: ${url}`);
    updateDebugInfo(`debug-${index}`, `URL: ${url}<br>Tentativa: ${(BANNER_CONFIG.retryCount[`banner-${index}`] || 0) + 1}<br>Timestamp: ${new Date().toLocaleString()}`);
    
    // Atualizar detalhes
    updateLoadingDetails(index, `Conectando ao servidor...`);
    
    // Timeout de segurança
    const timeoutId = setTimeout(() => {
        clearInterval(progressInterval);
        debugLog(`⏰ Timeout para banner ${index} após ${BANNER_CONFIG.loadTimeout}ms`);
        showBannerError(index, `Timeout: Banner demorou mais que ${BANNER_CONFIG.loadTimeout/1000}s para carregar`);
    }, BANNER_CONFIG.loadTimeout);
    
    // Configurar handlers da imagem
    img.onload = function() {
        clearTimeout(timeoutId);
        clearInterval(progressInterval);
        
        debugLog(`✅ Banner ${index} carregado com sucesso`);
        
        // Verificar se a imagem realmente carregou
        if (this.naturalWidth === 0 || this.naturalHeight === 0) {
            debugLog(`⚠️ Banner ${index} carregou mas tem dimensões inválidas`);
            showBannerError(index, 'Imagem carregada mas com dimensões inválidas');
            return;
        }
        
        debugLog(`📐 Banner ${index}: ${this.naturalWidth}x${this.naturalHeight}px`);
        
        // Finalizar progresso
        const progressBar = document.getElementById(`progress-bar-${index}`);
        if (progressBar) {
            progressBar.style.width = '100%';
        }
        
        // Mostrar imagem com fade
        setTimeout(() => {
            loading.style.display = 'none';
            img.style.display = 'block';
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                img.style.opacity = '1';
            }, 50);
        }, 300);
    };
    
    img.onerror = function() {
        clearTimeout(timeoutId);
        clearInterval(progressInterval);
        debugLog(`❌ Erro ao carregar banner ${index}`);
        showBannerError(index, 'Erro ao carregar imagem do banner');
    };
    
    // Monitorar progresso do carregamento
    setTimeout(() => {
        updateLoadingDetails(index, 'Processando imagem...');
    }, 1000);
    
    setTimeout(() => {
        updateLoadingDetails(index, 'Finalizando...');
    }, 2000);
    
    // Iniciar carregamento
    img.src = url;
    debugLog(`🎯 Carregamento iniciado para banner ${index}`);
}

function loadModel(modelNumber) {
    const img = document.getElementById(`model-img-${modelNumber}`);
    const loading = document.getElementById(`model-loading-${modelNumber}`);
    const error = document.getElementById(`model-error-${modelNumber}`);
    
    if (!img || !loading || !error) {
        debugLog(`❌ Elementos não encontrados para modelo ${modelNumber}`);
        return;
    }
    
    debugLog(`🔄 Iniciando carregamento do modelo ${modelNumber}`);
    
    // Reset estado
    img.style.display = 'none';
    loading.style.display = 'flex';
    error.style.display = 'none';
    
    // Construir URL
    const generatorScript = GENERATOR_URLS[modelNumber] || 'gerar_fut.php';
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(7);
    const url = `${generatorScript}?grupo=0&_t=${timestamp}&_r=${random}&_cache_bust=${Math.floor(Math.random() * 1000000)}`;
    
    debugLog(`📡 URL do modelo ${modelNumber}: ${url}`);
    updateDebugInfo(`model-debug-${modelNumber}`, `URL: ${url}<br>Tentativa: ${(BANNER_CONFIG.retryCount[`model-${modelNumber}`] || 0) + 1}<br>Timestamp: ${new Date().toLocaleString()}`);
    
    // Timeout de segurança
    const timeoutId = setTimeout(() => {
        debugLog(`⏰ Timeout para modelo ${modelNumber}`);
        showModelError(modelNumber, `Timeout: Modelo demorou mais que ${BANNER_CONFIG.loadTimeout/1000}s para carregar`);
    }, BANNER_CONFIG.loadTimeout);
    
    // Configurar handlers
    img.onload = function() {
        clearTimeout(timeoutId);
        debugLog(`✅ Modelo ${modelNumber} carregado com sucesso`);
        
        if (this.naturalWidth === 0 || this.naturalHeight === 0) {
            debugLog(`⚠️ Modelo ${modelNumber} carregou mas tem dimensões inválidas`);
            showModelError(modelNumber, 'Imagem carregada mas com dimensões inválidas');
            return;
        }
        
        debugLog(`📐 Modelo ${modelNumber}: ${this.naturalWidth}x${this.naturalHeight}px`);
        
        loading.style.display = 'none';
        img.style.display = 'block';
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
            img.style.opacity = '1';
        }, 50);
    };
    
    img.onerror = function() {
        clearTimeout(timeoutId);
        debugLog(`❌ Erro ao carregar modelo ${modelNumber}`);
        showModelError(modelNumber, 'Erro ao carregar imagem do modelo');
    };
    
    // Iniciar carregamento
    img.src = url;
    debugLog(`🎯 Carregamento iniciado para modelo ${modelNumber}`);
}

function showBannerError(index, message) {
    const loading = document.getElementById(`loading-${index}`);
    const error = document.getElementById(`error-${index}`);
    const errorDetails = document.getElementById(`error-details-${index}`);
    
    if (loading) loading.style.display = 'none';
    if (error) error.style.display = 'flex';
    if (errorDetails) {
        errorDetails.innerHTML = `<strong>Erro:</strong> ${message}<br><strong>Hora:</strong> ${new Date().toLocaleString()}`;
    }
    
    debugLog(`💥 Erro no banner ${index}: ${message}`);
}

function showModelError(modelNumber, message) {
    const loading = document.getElementById(`model-loading-${modelNumber}`);
    const error = document.getElementById(`model-error-${modelNumber}`);
    
    if (loading) loading.style.display = 'none';
    if (error) error.style.display = 'flex';
    
    debugLog(`💥 Erro no modelo ${modelNumber}: ${message}`);
}

function retryLoadBanner(index) {
    const retryKey = `banner-${index}`;
    BANNER_CONFIG.retryCount[retryKey] = (BANNER_CONFIG.retryCount[retryKey] || 0) + 1;
    
    debugLog(`🔄 Tentativa ${BANNER_CONFIG.retryCount[retryKey]} para banner ${index}`);
    
    if (BANNER_CONFIG.retryCount[retryKey] > BANNER_CONFIG.maxRetries) {
        showBannerError(index, `Máximo de tentativas excedido (${BANNER_CONFIG.maxRetries})`);
        debugLog(`🚫 Máximo de tentativas excedido para banner ${index}`);
        return;
    }
    
    <?php if (isset($_GET['banner'])): ?>
    const bannerType = <?php echo json_encode($tipo_banner); ?>;
    
    // Delay progressivo: mais tentativas = mais delay
    const delay = BANNER_CONFIG.retryDelay * BANNER_CONFIG.retryCount[retryKey];
    
    setTimeout(() => {
        debugLog(`⏳ Aguardando ${delay}ms antes de tentar novamente banner ${index}`);
        loadBanner(index, bannerType);
    }, delay);
    <?php endif; ?>
}

function retryLoadModel(modelNumber) {
    const retryKey = `model-${modelNumber}`;
    BANNER_CONFIG.retryCount[retryKey] = (BANNER_CONFIG.retryCount[retryKey] || 0) + 1;
    
    debugLog(`🔄 Tentativa ${BANNER_CONFIG.retryCount[retryKey]} para modelo ${modelNumber}`);
    
    if (BANNER_CONFIG.retryCount[retryKey] > BANNER_CONFIG.maxRetries) {
        showModelError(modelNumber, `Máximo de tentativas excedido (${BANNER_CONFIG.maxRetries})`);
        debugLog(`🚫 Máximo de tentativas excedido para modelo ${modelNumber}`);
        return;
    }
    
    const delay = BANNER_CONFIG.retryDelay * BANNER_CONFIG.retryCount[retryKey];
    
    setTimeout(() => {
        debugLog(`⏳ Aguardando ${delay}ms antes de tentar novamente modelo ${modelNumber}`);
        loadModel(modelNumber);
    }, delay);
}

function toggleDebugInfo(index) {
    const debugElement = document.getElementById(`debug-${index}`);
    if (debugElement) {
        debugElement.style.display = debugElement.style.display === 'none' ? 'block' : 'none';
    }
}

// Expor funções globalmente
window.retryLoadBanner = retryLoadBanner;
window.retryLoadModel = retryLoadModel;
window.toggleDebugInfo = toggleDebugInfo;

// Monitor de performance
setInterval(() => {
    if (performance.memory) {
        const memory = performance.memory;
        debugLog(`💾 Memória: ${Math.round(memory.usedJSHeapSize / 1024 / 1024)}MB usados`);
    }
}, 30000);

// Log quando a página termina de carregar
window.addEventListener('load', function() {
    debugLog('🎯 Página totalmente carregada');
});

// Detectar problemas de rede
window.addEventListener('online', () => debugLog('🌐 Conexão restaurada'));
window.addEventListener('offline', () => debugLog('🚫 Conexão perdida'));
</script>

<?php
logDebug("=== FIM FUTBANNER.PHP ===");
} // Fim do if/else principal

include "includes/footer.php";
?>