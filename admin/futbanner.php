<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$pageTitle = isset($_GET['banner']) ? "Gerador de Banner" : "Selecionar Modelo de Banner";
include "includes/header.php";

// Funções de criptografia e busca de dados (simplificadas)
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

// Obter dados dos jogos
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

<!-- Modal de Progresso -->
<div id="progressModal" class="progress-modal">
    <div class="progress-modal-content">
        <div class="progress-header">
            <h3 class="progress-title">
                <i class="fas fa-magic"></i>
                Gerando Banners
            </h3>
            <p class="progress-subtitle">Aguarde enquanto criamos seus banners...</p>
        </div>
        
        <div class="progress-body">
            <div class="progress-bar-container">
                <div class="progress-bar">
                    <div id="progressBarFill" class="progress-bar-fill"></div>
                </div>
                <div class="progress-text">
                    <span id="progressPercent">0%</span>
                    <span id="progressStatus">Iniciando...</span>
                </div>
            </div>
            
            <div class="banners-status">
                <?php foreach ($gruposDeJogos as $index => $grupo): ?>
                    <div id="banner-status-<?php echo $index; ?>" class="banner-status-item">
                        <div class="status-icon">
                            <i class="fas fa-clock text-muted"></i>
                        </div>
                        <span class="status-text">Banner Parte <?php echo $index + 1; ?></span>
                        <div class="status-indicator">
                            <div class="status-spinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-futbol text-primary-500 mr-3"></i>
        Banners de Jogos de Hoje
    </h1>
    <p class="page-subtitle">Modelo <?php echo $tipo_banner; ?> - <?php echo count($jogos); ?> jogos disponíveis</p>
</div>

<div class="banner-actions mb-6">
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
    <div class="banners-grid">
        <?php foreach ($gruposDeJogos as $index => $grupo): ?>
            <div class="banner-card">
                <div class="banner-card-header">
                    <div class="banner-info">
                        <h3 class="banner-title">Banner Parte <?php echo $index + 1; ?></h3>
                        <p class="banner-subtitle"><?php echo count($grupo); ?> jogos</p>
                    </div>
                    <div class="banner-status" id="status-<?php echo $index; ?>">
                        <div class="status-loading">
                            <i class="fas fa-clock text-muted"></i>
                        </div>
                    </div>
                </div>
                
                <div class="banner-preview-container">
                    <img id="banner-img-<?php echo $index; ?>" 
                         src="" 
                         alt="Banner Parte <?php echo $index + 1; ?>" 
                         class="banner-preview-image"
                         data-grupo="<?php echo $index; ?>"
                         data-script="<?php echo $geradorScript; ?>"
                         style="display: none;">
                    
                    <div id="loading-<?php echo $index; ?>" class="loading-placeholder">
                        <div class="loading-spinner"></div>
                        <p class="loading-text">Carregando banner...</p>
                        <div class="loading-progress">
                            <div class="loading-bar">
                                <div class="loading-bar-fill"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="error-<?php echo $index; ?>" class="error-placeholder" style="display: none;">
                        <i class="fas fa-exclamation-triangle text-4xl text-danger-500 mb-3"></i>
                        <p class="error-text">Erro ao carregar banner</p>
                        <button class="btn btn-secondary btn-sm mt-3" onclick="retryBanner(<?php echo $index; ?>)">
                            <i class="fas fa-redo"></i> Tentar Novamente
                        </button>
                    </div>
                </div>
                
                <div class="banner-actions">
                    <a href="<?php echo $geradorScript; ?>?grupo=<?php echo $index; ?>&download=1" 
                       class="btn btn-primary w-full" target="_blank">
                        <i class="fas fa-download"></i>
                        Baixar Banner
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
    /* Layout Principal dos Banners */
    .banners-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
        max-width: 1600px;
        margin: 0 auto;
    }

    /* Responsivo: 2 colunas em telas maiores */
    @media (min-width: 992px) {
        .banners-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 2.5rem;
        }
    }

    /* Card do Banner */
    .banner-card {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .banner-card:hover {
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px);
    }

    /* Header do Card */
    .banner-card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--bg-secondary);
    }

    .banner-info {
        flex: 1;
    }

    .banner-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .banner-title::before {
        content: '';
        width: 8px;
        height: 8px;
        background: var(--primary-500);
        border-radius: 50%;
    }

    .banner-subtitle {
        color: var(--text-secondary);
        font-size: 0.875rem;
        margin: 0;
    }

    .banner-status {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .status-loading i {
        color: var(--text-muted);
        animation: pulse 2s infinite;
    }

    .status-success i {
        color: var(--success-500);
    }

    .status-error i {
        color: var(--danger-500);
    }

    /* Container da Prévia - DIMENSÕES AUMENTADAS */
    .banner-preview-container {
        position: relative;
        width: 100%;
        height: 450px; /* Aumentado de 300px para 450px */
        background: var(--bg-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    /* Responsivo para diferentes tamanhos */
    @media (min-width: 1200px) {
        .banner-preview-container {
            height: 500px; /* Ainda maior em telas grandes */
        }
    }

    @media (max-width: 991px) {
        .banner-preview-container {
            height: 400px; /* Menor em tablets */
        }
    }

    @media (max-width: 768px) {
        .banner-preview-container {
            height: 350px; /* Menor em mobile */
        }
    }

    @media (max-width: 480px) {
        .banner-preview-container {
            height: 300px; /* Mínimo em mobile pequeno */
        }
    }

    .banner-preview-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: opacity 0.3s ease;
        background: var(--bg-secondary);
    }

    /* Estados de Loading e Error */
    .loading-placeholder,
    .error-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--text-muted);
        padding: 2rem;
        background: var(--bg-secondary);
    }

    .loading-spinner {
        width: 48px;
        height: 48px;
        border: 4px solid var(--border-color);
        border-top: 4px solid var(--primary-500);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 1.5rem;
    }

    .loading-text,
    .error-text {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin: 0 0 1rem 0;
        font-weight: 500;
    }

    .loading-progress {
        width: 100%;
        max-width: 200px;
        margin-top: 1rem;
    }

    .loading-bar {
        width: 100%;
        height: 4px;
        background: var(--bg-tertiary);
        border-radius: 2px;
        overflow: hidden;
    }

    .loading-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-500), var(--primary-600));
        border-radius: 2px;
        width: 0%;
        animation: loadingProgress 3s ease-in-out infinite;
    }

    @keyframes loadingProgress {
        0%, 100% { width: 0%; }
        50% { width: 100%; }
    }

    /* Ações do Banner */
    .banner-actions {
        padding: 1.5rem;
        background: var(--bg-primary);
        margin-top: auto;
    }

    .banner-actions .btn {
        font-weight: 600;
        padding: 0.875rem 1.5rem;
        border-radius: var(--border-radius);
        transition: all 0.3s ease;
    }

    .banner-actions .btn:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    /* Ações Principais */
    .banner-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 2rem;
    }

    /* Modal de Progresso */
    .progress-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(8px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .progress-modal.active {
        opacity: 1;
        visibility: visible;
    }

    .progress-modal-content {
        background: var(--bg-primary);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-xl);
        border: 1px solid var(--border-color);
        width: 90%;
        max-width: 500px;
        max-height: 80vh;
        overflow-y: auto;
        animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .progress-header {
        padding: 2rem 2rem 1rem;
        text-align: center;
        border-bottom: 1px solid var(--border-color);
    }

    .progress-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .progress-title i {
        color: var(--primary-500);
    }

    .progress-subtitle {
        color: var(--text-secondary);
        font-size: 0.875rem;
    }

    .progress-body {
        padding: 2rem;
    }

    .progress-bar-container {
        margin-bottom: 2rem;
    }

    .progress-bar {
        width: 100%;
        height: 12px;
        background: var(--bg-tertiary);
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 1rem;
        position: relative;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-500), var(--primary-600));
        border-radius: 6px;
        width: 0%;
        transition: width 0.5s ease;
        position: relative;
        overflow: hidden;
    }

    .progress-bar-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .progress-text {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.875rem;
    }

    #progressPercent {
        font-weight: 600;
        color: var(--primary-500);
        font-size: 1rem;
    }

    #progressStatus {
        color: var(--text-secondary);
    }

    .banners-status {
        space-y: 0.75rem;
    }

    .banner-status-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        background: var(--bg-secondary);
        border-radius: var(--border-radius-sm);
        transition: var(--transition);
    }

    .banner-status-item.loading {
        background: var(--primary-50);
        border-left: 3px solid var(--primary-500);
    }

    .banner-status-item.success {
        background: var(--success-50);
        border-left: 3px solid var(--success-500);
    }

    .banner-status-item.error {
        background: var(--danger-50);
        border-left: 3px solid var(--danger-500);
    }

    [data-theme="dark"] .banner-status-item.loading {
        background: rgba(59, 130, 246, 0.1);
    }

    [data-theme="dark"] .banner-status-item.success {
        background: rgba(34, 197, 94, 0.1);
    }

    [data-theme="dark"] .banner-status-item.error {
        background: rgba(239, 68, 68, 0.1);
    }

    .status-icon {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .status-text {
        flex: 1;
        font-weight: 500;
        color: var(--text-primary);
    }

    .status-indicator {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .status-spinner {
        color: var(--primary-500);
    }

    /* Animações */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    /* Utilitários */
    .mr-3 {
        margin-right: 0.75rem;
    }

    .mb-3 {
        margin-bottom: 0.75rem;
    }

    .mb-6 {
        margin-bottom: 1.5rem;
    }

    .mt-3 {
        margin-top: 0.75rem;
    }

    .w-full {
        width: 100%;
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
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

    .text-4xl {
        font-size: 2.25rem;
        line-height: 2.5rem;
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

    /* Dark theme adjustments */
    [data-theme="dark"] .text-gray-300 {
        color: var(--text-muted);
    }

    [data-theme="dark"] .text-danger-500 {
        color: #ef4444;
    }
</style>

<script>
let retryCount = {};
const maxRetries = 3;
let totalBanners = 0;
let loadedBanners = 0;
let failedBanners = 0;

function showProgressModal() {
    const modal = document.getElementById('progressModal');
    modal.classList.add('active');
}

function hideProgressModal() {
    const modal = document.getElementById('progressModal');
    modal.classList.remove('active');
}

function updateProgress() {
    const percent = Math.round((loadedBanners / totalBanners) * 100);
    const progressBar = document.getElementById('progressBarFill');
    const progressPercent = document.getElementById('progressPercent');
    const progressStatus = document.getElementById('progressStatus');
    
    progressBar.style.width = percent + '%';
    progressPercent.textContent = percent + '%';
    
    if (loadedBanners === totalBanners) {
        progressStatus.textContent = 'Concluído!';
        setTimeout(() => {
            hideProgressModal();
        }, 1500);
    } else {
        progressStatus.textContent = `${loadedBanners}/${totalBanners} banners carregados`;
    }
}

function updateBannerStatus(index, status) {
    const statusItem = document.getElementById(`banner-status-${index}`);
    const statusIcon = statusItem.querySelector('.status-icon i');
    const statusSpinner = statusItem.querySelector('.status-spinner');
    const cardStatus = document.getElementById(`status-${index}`);
    
    // Remove todas as classes de status
    statusItem.classList.remove('loading', 'success', 'error');
    cardStatus.className = 'banner-status';
    
    switch (status) {
        case 'loading':
            statusItem.classList.add('loading');
            cardStatus.classList.add('status-loading');
            statusIcon.className = 'fas fa-clock text-primary-500';
            if (statusSpinner) statusSpinner.style.display = 'block';
            cardStatus.innerHTML = '<div class="status-loading"><i class="fas fa-spinner fa-spin text-primary-500"></i></div>';
            break;
        case 'success':
            statusItem.classList.add('success');
            cardStatus.classList.add('status-success');
            statusIcon.className = 'fas fa-check-circle text-success-500';
            if (statusSpinner) statusSpinner.style.display = 'none';
            cardStatus.innerHTML = '<div class="status-success"><i class="fas fa-check-circle text-success-500"></i></div>';
            break;
        case 'error':
            statusItem.classList.add('error');
            cardStatus.classList.add('status-error');
            statusIcon.className = 'fas fa-times-circle text-danger-500';
            if (statusSpinner) statusSpinner.style.display = 'none';
            cardStatus.innerHTML = '<div class="status-error"><i class="fas fa-times-circle text-danger-500"></i></div>';
            break;
    }
}

function loadBanner(index, script) {
    const img = document.getElementById(`banner-img-${index}`);
    const loading = document.getElementById(`loading-${index}`);
    const error = document.getElementById(`error-${index}`);
    
    if (!img || !loading || !error) return;
    
    // Atualizar status no modal e card
    updateBannerStatus(index, 'loading');
    
    // Reset estado
    img.style.display = 'none';
    loading.style.display = 'flex';
    error.style.display = 'none';
    
    // Criar URL com cache busting
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(7);
    const url = `${script}?grupo=${index}&_t=${timestamp}&_r=${random}`;
    
    console.log(`Carregando banner ${index}: ${url}`);
    
    // Timeout aumentado para 60 segundos
    const timeout = setTimeout(() => {
        console.log(`Timeout para banner ${index} após 60 segundos`);
        showError(index, 'Timeout ao carregar banner');
        updateBannerStatus(index, 'error');
        failedBanners++;
        loadedBanners++;
        updateProgress();
    }, 60000); // 60 segundos
    
    img.onload = function() {
        clearTimeout(timeout);
        console.log(`Banner ${index} carregado com sucesso`);
        
        // Verificar se a imagem realmente carregou
        if (this.naturalWidth === 0 || this.naturalHeight === 0) {
            console.log(`Banner ${index} carregou mas tem dimensões inválidas`);
            showError(index, 'Imagem inválida');
            updateBannerStatus(index, 'error');
            failedBanners++;
        } else {
            // Mostrar imagem
            this.style.display = 'block';
            loading.style.display = 'none';
            error.style.display = 'none';
            updateBannerStatus(index, 'success');
            
            // Reset retry count
            retryCount[index] = 0;
        }
        
        loadedBanners++;
        updateProgress();
    };
    
    img.onerror = function() {
        clearTimeout(timeout);
        console.log(`Erro ao carregar banner ${index}`);
        showError(index, 'Erro ao carregar imagem');
        updateBannerStatus(index, 'error');
        failedBanners++;
        loadedBanners++;
        updateProgress();
    };
    
    // Iniciar carregamento
    img.src = url;
}

function showError(index, message) {
    const img = document.getElementById(`banner-img-${index}`);
    const loading = document.getElementById(`loading-${index}`);
    const error = document.getElementById(`error-${index}`);
    
    if (img) img.style.display = 'none';
    if (loading) loading.style.display = 'none';
    if (error) {
        error.style.display = 'flex';
        const errorText = error.querySelector('.error-text');
        if (errorText) {
            errorText.textContent = `${message} (Tentativa ${retryCount[index] || 0}/${maxRetries})`;
        }
    }
}

function retryBanner(index) {
    retryCount[index] = (retryCount[index] || 0) + 1;
    
    if (retryCount[index] > maxRetries) {
        showError(index, 'Máximo de tentativas excedido');
        updateBannerStatus(index, 'error');
        return;
    }
    
    const img = document.getElementById(`banner-img-${index}`);
    const script = img.getAttribute('data-script');
    
    console.log(`Tentativa ${retryCount[index]} para banner ${index}`);
    
    // Delay progressivo (mais tempo entre tentativas)
    const delay = retryCount[index] * 2000; // 2, 4, 6 segundos
    setTimeout(() => {
        // Decrementar loadedBanners para reprocessar
        loadedBanners--;
        loadBanner(index, script);
    }, delay);
}

// Carregar banners quando a página estiver pronta
document.addEventListener('DOMContentLoaded', function() {
    console.log('Iniciando carregamento dos banners...');
    
    <?php if (!empty($gruposDeJogos)): ?>
        const banners = [
            <?php foreach ($gruposDeJogos as $index => $grupo): ?>
                {index: <?php echo $index; ?>, script: '<?php echo $geradorScript; ?>'},
            <?php endforeach; ?>
        ];
        
        totalBanners = banners.length;
        loadedBanners = 0;
        failedBanners = 0;
        
        // Mostrar modal de progresso
        showProgressModal();
        
        // Carregar banners com delay escalonado (mais tempo entre cada um)
        banners.forEach((banner, i) => {
            setTimeout(() => {
                loadBanner(banner.index, banner.script);
            }, i * 2000); // 2 segundos entre cada banner
        });
    <?php endif; ?>
});

// Expor função globalmente
window.retryBanner = retryBanner;
</script>

<?php
} else {
    // Tela de seleção de modelo
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-futbol text-primary-500 mr-3"></i>
        Escolha o Modelo de Banner
    </h1>
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
                    <div class="banner-preview-container">
                        <img src="gerar_fut<?php echo $i > 1 ? '_' . $i : ''; ?>.php?grupo=0&_preview=1&_t=<?php echo time(); ?>" 
                             alt="Prévia do Banner <?php echo $i; ?>" 
                             class="banner-preview-image"
                             loading="lazy">
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

    [data-theme="dark"] .text-warning-500 {
        color: #f59e0b;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('.banner-preview-image');
    
    images.forEach(function(img) {
        // Timeout aumentado para prévias também
        const timeout = setTimeout(function() {
            if (!img.complete) {
                img.style.opacity = '0.5';
                img.alt = 'Erro ao carregar prévia';
            }
        }, 15000); // 15 segundos para prévias
        
        img.addEventListener('load', function() {
            clearTimeout(timeout);
            img.style.opacity = '1';
        });
        
        img.addEventListener('error', function() {
            clearTimeout(timeout);
            img.style.opacity = '0.5';
            img.alt = 'Erro ao carregar prévia';
        });
    });
});
</script>

<?php
}

include "includes/footer.php";
?>