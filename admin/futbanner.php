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
                        <img src="<?php echo $geradorScript; ?>?grupo=<?php echo $index; ?>" 
                             alt="Banner Parte <?php echo $index + 1; ?>" 
                             class="banner-preview-image"
                             loading="lazy">
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="card group hover:shadow-xl transition-all duration-300">
                <div class="card-header">
                    <h3 class="card-title">Banner Modelo <?php echo $i; ?></h3>
                    <p class="card-subtitle">Estilo profissional e moderno</p>
                </div>
                <div class="card-body">
                    <div class="banner-preview-container">
                        <img src="gerar_fut<?php echo $i > 1 ? '_' . $i : ''; ?>.php?grupo=0" 
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

<?php
} // Fim do if/else principal
?>

<!-- Modal de Progresso -->
<div id="progressModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-magic text-primary-500"></i>
                <span id="modalTitle">Carregando Prévias</span>
            </h3>
            <p class="modal-subtitle" id="modalSubtitle">Aguarde enquanto preparamos as prévias...</p>
        </div>
        
        <div class="modal-body">
            <div class="progress-info">
                <div class="progress-text">
                    <span id="progressText">Iniciando...</span>
                    <span id="progressPercent">0%</span>
                </div>
                <div class="progress-bar">
                    <div id="progressFill" class="progress-fill"></div>
                </div>
            </div>
            
            <div id="itemsStatus" class="items-status">
                <!-- Items serão adicionados dinamicamente -->
            </div>
        </div>
        
        <?php if (!isset($_GET['banner'])): ?>
        <div class="modal-footer">
            <button id="skipBtn" class="btn btn-secondary w-full">
                <i class="fas fa-forward"></i>
                Pular Prévias e Escolher Modelo
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Modal Overlay - Posicionamento fixo para cobrir toda a tela */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(4px);
        z-index: 999999;
        display: none;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-overlay.show {
        display: flex;
        opacity: 1;
        visibility: visible;
    }

    /* Modal Container - Centralizado */
    .modal-container {
        background: var(--bg-primary);
        border-radius: 16px;
        box-shadow: var(--shadow-xl);
        border: 1px solid var(--border-color);
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        transform: scale(0.9) translateY(20px);
        transition: all 0.3s ease;
        position: relative;
    }

    .modal-overlay.show .modal-container {
        transform: scale(1) translateY(0);
    }

    /* Modal Header */
    .modal-header {
        padding: 2rem 2rem 1rem;
        border-bottom: 1px solid var(--border-color);
        text-align: center;
    }

    .modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
    }

    .modal-subtitle {
        color: var(--text-secondary);
        font-size: 0.875rem;
    }

    /* Modal Body */
    .modal-body {
        padding: 2rem;
    }

    /* Progress Info */
    .progress-info {
        margin-bottom: 2rem;
    }

    .progress-text {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: var(--bg-tertiary);
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-500), var(--primary-600));
        border-radius: 4px;
        width: 0%;
        transition: width 0.3s ease;
    }

    /* Status Items */
    .items-status {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .status-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: var(--bg-secondary);
        border-radius: 12px;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .status-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: var(--bg-tertiary);
    }

    .status-info {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .status-title {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 0.875rem;
    }

    .status-subtitle {
        color: var(--text-muted);
        font-size: 0.75rem;
    }

    .status-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .status-text {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--text-secondary);
    }

    /* Status States */
    .status-loading .status-icon {
        background: var(--primary-50);
    }

    .status-loading .status-icon i {
        color: var(--primary-500);
        animation: spin 1s linear infinite;
    }

    .status-success .status-icon {
        background: var(--success-50);
    }

    .status-success .status-icon i {
        color: var(--success-500);
    }

    .status-error .status-icon {
        background: var(--danger-50);
    }

    .status-error .status-icon i {
        color: var(--danger-500);
    }

    /* Modal Footer */
    .modal-footer {
        padding: 1rem 2rem 2rem;
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    /* Banner Preview Styles */
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
        object-fit: contain;
        transition: opacity 0.3s ease;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .modal-container {
            width: 95%;
            margin: 1rem;
        }

        .modal-header,
        .modal-body,
        .modal-footer {
            padding: 1.5rem;
        }

        .status-item {
            padding: 0.75rem;
        }
    }

    /* Animations */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Dark theme adjustments */
    [data-theme="dark"] .modal-overlay {
        background: rgba(0, 0, 0, 0.9);
    }

    [data-theme="dark"] .text-gray-300 {
        color: var(--text-muted);
    }

    [data-theme="dark"] .text-warning-500 {
        color: #f59e0b;
    }

    /* Utility Classes */
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('progressModal');
    const images = document.querySelectorAll('.banner-preview-image');
    
    let loadedCount = 0;
    let totalImages = images.length;
    let isSkipped = false;
    
    if (totalImages === 0) return;
    
    // Configurar modal baseado na página
    <?php if (isset($_GET['banner'])): ?>
        setupBannerModal();
    <?php else: ?>
        setupModelModal();
    <?php endif; ?>
    
    // Mostrar modal
    showModal();
    
    // Configurar carregamento das imagens
    images.forEach((img, index) => {
        const timeout = setTimeout(() => {
            if (!img.complete && !isSkipped) {
                updateItemStatus(index, 'error');
                loadedCount++;
                updateProgress();
            }
        }, 60000); // 60 segundos timeout
        
        img.addEventListener('load', function() {
            clearTimeout(timeout);
            if (!isSkipped) {
                updateItemStatus(index, 'success');
                loadedCount++;
                updateProgress();
            }
        });
        
        img.addEventListener('error', function() {
            clearTimeout(timeout);
            if (!isSkipped) {
                updateItemStatus(index, 'error');
                loadedCount++;
                updateProgress();
            }
        });
        
        // Iniciar carregamento
        updateItemStatus(index, 'loading');
    });
    
    // Botão pular (apenas na página de modelos)
    const skipBtn = document.getElementById('skipBtn');
    if (skipBtn) {
        skipBtn.addEventListener('click', function() {
            skipLoading();
        });
    }
    
    // Permitir navegação livre
    document.addEventListener('click', function(e) {
        if (e.target.closest('a[href]') && !e.target.closest('#progressModal')) {
            skipLoading();
        }
    });
    
    // Fechar modal clicando no overlay
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            skipLoading();
        }
    });
    
    function showModal() {
        modal.classList.add('show');
    }
    
    function hideModal() {
        modal.classList.remove('show');
    }
    
    function skipLoading() {
        isSkipped = true;
        hideModal();
    }
    
    function setupBannerModal() {
        document.getElementById('modalTitle').textContent = 'Carregando Banners';
        document.getElementById('modalSubtitle').textContent = 'Aguarde enquanto preparamos seus banners...';
        
        const statusContainer = document.getElementById('itemsStatus');
        statusContainer.innerHTML = '';
        
        <?php foreach ($gruposDeJogos as $index => $grupo): ?>
            const item<?php echo $index; ?> = createStatusItem(
                <?php echo $index; ?>,
                'Banner Parte <?php echo $index + 1; ?>',
                '<?php echo count($grupo); ?> jogos'
            );
            statusContainer.appendChild(item<?php echo $index; ?>);
        <?php endforeach; ?>
    }
    
    function setupModelModal() {
        document.getElementById('modalTitle').textContent = 'Carregando Prévias dos Modelos';
        document.getElementById('modalSubtitle').textContent = 'Aguarde enquanto preparamos as prévias...';
        
        const statusContainer = document.getElementById('itemsStatus');
        statusContainer.innerHTML = '';
        
        <?php for ($i = 1; $i <= 3; $i++): ?>
            const model<?php echo $i; ?> = createStatusItem(
                <?php echo $i - 1; ?>,
                'Banner Modelo <?php echo $i; ?>',
                'Estilo profissional'
            );
            statusContainer.appendChild(model<?php echo $i; ?>);
        <?php endfor; ?>
    }
    
    function createStatusItem(index, title, subtitle) {
        const item = document.createElement('div');
        item.className = 'status-item';
        item.id = `status-item-${index}`;
        item.innerHTML = `
            <div class="status-icon">
                <i class="fas fa-clock text-gray-400"></i>
            </div>
            <div class="status-info">
                <span class="status-title">${title}</span>
                <span class="status-subtitle">${subtitle}</span>
            </div>
            <div class="status-indicator">
                <span class="status-text">Aguardando</span>
            </div>
        `;
        return item;
    }
    
    function updateProgress() {
        const percent = Math.round((loadedCount / totalImages) * 100);
        document.getElementById('progressText').textContent = `Carregando ${loadedCount + 1} de ${totalImages}`;
        document.getElementById('progressPercent').textContent = `${percent}%`;
        document.getElementById('progressFill').style.width = `${percent}%`;
        
        if (loadedCount >= totalImages) {
            setTimeout(() => {
                hideModal();
            }, 1000);
        }
    }
    
    function updateItemStatus(index, status) {
        const statusItem = document.getElementById(`status-item-${index}`);
        if (!statusItem) return;
        
        statusItem.className = `status-item status-${status}`;
        const icon = statusItem.querySelector('.status-icon i');
        const text = statusItem.querySelector('.status-text');
        
        switch (status) {
            case 'loading':
                icon.className = 'fas fa-spinner';
                text.textContent = 'Carregando...';
                break;
            case 'success':
                icon.className = 'fas fa-check-circle';
                text.textContent = 'Concluído';
                break;
            case 'error':
                icon.className = 'fas fa-times-circle';
                text.textContent = 'Erro';
                break;
        }
    }
});

// Permitir navegação livre - sem travamentos
window.addEventListener('beforeunload', function() {
    // Não fazer nada que possa travar
});
</script>

<?php include "includes/footer.php"; ?>