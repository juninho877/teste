<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$pageTitle = isset($_GET['banner']) ? "Gerador de Banner" : "Selecionar Modelo de Banner";
include "includes/header.php";

// Funções de criptografia e busca de dados (mantidas do código original)
function getChaveRemota() {
    $url_base64 = 'aHR0cHM6Ly9hcGlmdXQucHJvamVjdHguY2xpY2svQXV0b0FwaS9BRVMvY29uZmlna2V5LnBocA==';
    $auth_base64 = 'dmFxdW9UQlpFb0U4QmhHMg==';
    $url = base64_decode($url_base64);
    $auth = base64_decode($auth_base64);
    $postData = json_encode(['auth' => $auth]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Content-Length: ' . strlen($postData)],
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response ? json_decode($response, true)['chave'] ?? null : null;
}

function descriptografarURL($urlCodificada, $chave) {
    list($url_criptografada, $iv) = explode('::', base64_decode($urlCodificada), 2);
    return openssl_decrypt($url_criptografada, 'aes-256-cbc', $chave, 0, $iv);
}

// Obter dados dos jogos com timeout reduzido
$chave_secreta = getChaveRemota();
$parametro_criptografado = 'SVI0Sjh1MTJuRkw1bmFyeFdPb3cwOXA2TFo3RWlSQUxLbkczaGE4MXBiMWhENEpOWkhkSFZoeURaWFVDM1lTZzo6RNBu5BBhzmFRkTPPSikeJg==';
$json_url = $chave_secreta ? descriptografarURL($parametro_criptografado, $chave_secreta) : null;

$jogos = [];
if ($json_url) {
    $ch = curl_init($json_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    $json_content = curl_exec($ch);
    curl_close($ch);

    if ($json_content !== false) {
        $todos_jogos = json_decode($json_content, true);
        if (is_array($todos_jogos)) {
            foreach ($todos_jogos as $jogo) {
                if (isset($jogo['data_jogo']) && $jogo['data_jogo'] === 'hoje') {
                    $jogos[] = $jogo;
                }
            }
        }
    }
}

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
        <button onclick="downloadAllBanners('<?php echo $geradorScript; ?>')" class="btn btn-success" id="downloadAllBtn">
            <i class="fas fa-download"></i>
            Baixar Todos (ZIP)
        </button>
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
                    <div class="banner-preview-container" id="preview-<?php echo $index; ?>">
                        <div class="banner-loading-placeholder">
                            <div class="loading-spinner"></div>
                            <span>Carregando prévia...</span>
                        </div>
                        <img data-src="<?php echo $geradorScript; ?>?grupo=<?php echo $index; ?>" 
                             alt="Banner Parte <?php echo $index + 1; ?>" 
                             class="banner-preview-image lazy-load"
                             style="opacity: 0;">
                    </div>
                    <button onclick="downloadSingleBanner('<?php echo $geradorScript; ?>', <?php echo $index; ?>)" 
                            class="btn btn-primary w-full mt-4">
                        <i class="fas fa-download"></i>
                        Baixar Banner
                    </button>
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="card group hover:shadow-xl transition-all duration-300">
                <div class="card-header">
                    <h3 class="card-title">Banner Modelo <?php echo $i; ?></h3>
                    <p class="card-subtitle">Estilo profissional e moderno</p>
                </div>
                <div class="card-body">
                    <div class="banner-preview-container" id="model-preview-<?php echo $i; ?>">
                        <div class="banner-loading-placeholder">
                            <div class="loading-spinner"></div>
                            <span>Carregando prévia...</span>
                        </div>
                        <img data-src="gerar_fut<?php echo $i > 1 ? '_' . $i : ''; ?>.php?grupo=0" 
                             alt="Prévia do Banner <?php echo $i; ?>" 
                             class="banner-preview-image lazy-load"
                             style="opacity: 0;">
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
    }

    .banner-preview-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: opacity 0.3s ease;
    }

    .banner-loading-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: var(--bg-secondary);
        color: var(--text-muted);
        font-size: 0.875rem;
        gap: 1rem;
        z-index: 2;
    }

    .loading-spinner {
        width: 32px;
        height: 32px;
        border: 3px solid var(--border-color);
        border-top: 3px solid var(--primary-500);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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

    /* Loading overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .loading-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .loading-content {
        background: var(--bg-primary);
        padding: 2rem;
        border-radius: var(--border-radius);
        text-align: center;
        box-shadow: var(--shadow-xl);
        max-width: 300px;
    }

    .loading-content .loading-spinner {
        width: 48px;
        height: 48px;
        margin-bottom: 1rem;
    }
</style>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <h3 class="font-semibold mb-2">Processando...</h3>
        <p class="text-muted text-sm">Aguarde enquanto geramos seus banners</p>
        <button onclick="cancelOperation()" class="btn btn-secondary mt-4">
            <i class="fas fa-times"></i>
            Cancelar
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let currentRequests = [];
    let isNavigating = false;

    // Lazy loading das imagens
    const lazyImages = document.querySelectorAll('.lazy-load');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                const placeholder = img.previousElementSibling;
                
                // Timeout para evitar travamento
                const timeout = setTimeout(() => {
                    if (img.style.opacity === '0') {
                        placeholder.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>Timeout ao carregar</span>';
                    }
                }, 8000);

                img.onload = () => {
                    clearTimeout(timeout);
                    img.style.opacity = '1';
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                };

                img.onerror = () => {
                    clearTimeout(timeout);
                    if (placeholder) {
                        placeholder.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>Erro ao carregar</span>';
                    }
                };

                img.src = img.dataset.src;
                observer.unobserve(img);
            }
        });
    });

    lazyImages.forEach(img => imageObserver.observe(img));

    // Permitir navegação mesmo durante carregamento
    window.addEventListener('beforeunload', (e) => {
        if (currentRequests.length > 0 && !isNavigating) {
            currentRequests.forEach(request => {
                if (request.abort) request.abort();
            });
        }
    });

    // Interceptar cliques em links para cancelar operações
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (link && !link.href.includes('#') && !link.target) {
            isNavigating = true;
            cancelAllRequests();
        }
    });
});

function downloadSingleBanner(script, index) {
    showLoading();
    
    const downloadUrl = `${script}?grupo=${index}&download=1`;
    
    // Criar link temporário para download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = `banner_parte_${index + 1}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Esconder loading após um tempo
    setTimeout(() => {
        hideLoading();
    }, 2000);
}

function downloadAllBanners(script) {
    showLoading();
    
    const downloadUrl = `${script}?download_all=1`;
    
    // Criar link temporário para download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = `banners_completos.zip`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Esconder loading após um tempo
    setTimeout(() => {
        hideLoading();
    }, 3000);
}

function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.add('active');
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
}

function cancelOperation() {
    cancelAllRequests();
    hideLoading();
}

function cancelAllRequests() {
    // Cancelar todas as requisições ativas
    if (window.currentRequests) {
        window.currentRequests.forEach(request => {
            if (request.abort) request.abort();
        });
        window.currentRequests = [];
    }
}

// Otimização: Preload apenas das imagens visíveis
function preloadVisibleImages() {
    const visibleImages = document.querySelectorAll('.banner-preview-image[data-src]');
    const viewportHeight = window.innerHeight;
    
    visibleImages.forEach(img => {
        const rect = img.getBoundingClientRect();
        if (rect.top < viewportHeight + 200) { // 200px de margem
            if (img.dataset.src && !img.src) {
                img.src = img.dataset.src;
            }
        }
    });
}

// Executar preload após um pequeno delay
setTimeout(preloadVisibleImages, 500);

// Preload ao fazer scroll
let scrollTimeout;
window.addEventListener('scroll', () => {
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(preloadVisibleImages, 100);
});
</script>

<?php
} // Fim do if/else principal

include "includes/footer.php";
?>