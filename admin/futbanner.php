<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

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

$pageTitle = isset($_GET['banner']) ? "Gerador de Banner" : "Selecionar Modelo de Banner";
include "includes/header.php";

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

<!-- Modal de Carregamento -->
<div id="loadingModal" class="loading-modal">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <h3>🏈 Carregando Banners</h3>
        <p>Aguarde enquanto preparamos seus banners...</p>
        <div class="loading-progress">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <span class="progress-text" id="progressText">Iniciando... 0%</span>
        </div>
        <div class="loading-steps" id="loadingSteps">
            <div class="step" id="step1">
                <i class="fas fa-circle-notch fa-spin"></i>
                <span>Banner Modelo <?php echo $tipo_banner; ?></span>
            </div>
            <div class="step" id="step2">
                <i class="fas fa-clock"></i>
                <span>Banner Parte 1 - 5 jogos</span>
                <small>Aguardando</small>
            </div>
            <div class="step" id="step3">
                <i class="fas fa-clock"></i>
                <span>Banner Parte 2 - 5 jogos</span>
                <small>Aguardando</small>
            </div>
            <div class="step" id="step4">
                <i class="fas fa-clock"></i>
                <span>Banner Parte 3 - 5 jogos</span>
                <small>Aguardando</small>
            </div>
            <div class="step" id="step5">
                <i class="fas fa-clock"></i>
                <span>Banner Parte 4 - 5 jogos</span>
                <small>Aguardando</small>
            </div>
            <div class="step" id="step6">
                <i class="fas fa-clock"></i>
                <span>Banner Parte 6 - 4 jogos</span>
                <small>Aguardando</small>
            </div>
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
    <!-- Grid com 2 colunas em telas grandes para os banners -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
    <!-- Grid com 3 colunas para os modelos -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                    <a href="?banner=<?php echo $i; ?>" class="btn btn-primary w-full mt-4 group-hover:bg-primary-600" onclick="showLoadingModal()">
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

<style>
    /* Modal de Carregamento */
    .loading-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(10px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        animation: fadeIn 0.3s ease-out;
    }

    .loading-modal.show {
        display: flex;
    }

    .loading-content {
        background: var(--bg-primary);
        border-radius: 20px;
        padding: 2rem;
        max-width: 500px;
        width: 90%;
        text-align: center;
        box-shadow: var(--shadow-xl);
        border: 1px solid var(--border-color);
    }

    .loading-spinner {
        width: 60px;
        height: 60px;
        border: 4px solid var(--bg-tertiary);
        border-top: 4px solid var(--primary-500);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1.5rem;
    }

    .loading-content h3 {
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        font-size: 1.5rem;
    }

    .loading-content p {
        color: var(--text-secondary);
        margin-bottom: 2rem;
    }

    .loading-progress {
        margin-bottom: 2rem;
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: var(--bg-tertiary);
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-500), var(--primary-600));
        width: 0%;
        transition: width 0.3s ease;
        border-radius: 4px;
    }

    .progress-text {
        font-size: 0.875rem;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .loading-steps {
        text-align: left;
    }

    .step {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        background: var(--bg-secondary);
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .step.active {
        background: var(--primary-50);
        border-left: 3px solid var(--primary-500);
    }

    .step.completed {
        background: var(--success-50);
        border-left: 3px solid var(--success-500);
    }

    .step i {
        color: var(--text-muted);
        width: 16px;
    }

    .step.active i {
        color: var(--primary-500);
    }

    .step.completed i {
        color: var(--success-500);
    }

    .step span {
        font-weight: 500;
        color: var(--text-primary);
        flex: 1;
    }

    .step small {
        color: var(--text-muted);
        font-size: 0.75rem;
    }

    /* Banner Preview Styles - Tamanho controlado para 2 por linha */
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

    .banner-preview-image[loading="lazy"] {
        background: var(--bg-secondary);
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

    /* Dark theme adjustments */
    [data-theme="dark"] .text-gray-300 {
        color: var(--text-muted);
    }

    [data-theme="dark"] .text-warning-500 {
        color: #f59e0b;
    }

    [data-theme="dark"] .loading-content {
        background: var(--bg-secondary);
    }

    [data-theme="dark"] .step.active {
        background: rgba(59, 130, 246, 0.1);
    }

    [data-theme="dark"] .step.completed {
        background: rgba(34, 197, 94, 0.1);
    }

    /* Animations */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }

    /* Loading state for images */
    .banner-preview-image {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill='%23e2e8f0'%3E%3Ccircle cx='50' cy='50' r='4'%3E%3Canimate attributeName='opacity' values='1;0;1' dur='1s' repeatCount='indefinite'/%3E%3C/circle%3E%3Ccircle cx='30' cy='50' r='4'%3E%3Canimate attributeName='opacity' values='1;0;1' dur='1s' begin='0.2s' repeatCount='indefinite'/%3E%3C/circle%3E%3Ccircle cx='70' cy='50' r='4'%3E%3Canimate attributeName='opacity' values='1;0;1' dur='1s' begin='0.4s' repeatCount='indefinite'/%3E%3C/circle%3E%3C/g%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: center;
        background-size: 50px 50px;
    }

    .banner-preview-image[src] {
        background-image: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gerenciamento de imagens dos banners
    const images = document.querySelectorAll('.banner-preview-image');
    
    images.forEach(function(img) {
        const timeout = setTimeout(function() {
            if (!img.complete) {
                img.style.opacity = '0.5';
                img.alt = 'Erro ao carregar banner';
            }
        }, 10000);
        
        img.addEventListener('load', function() {
            clearTimeout(timeout);
            img.style.opacity = '1';
        });
        
        img.addEventListener('error', function() {
            clearTimeout(timeout);
            img.style.opacity = '0.5';
            img.alt = 'Erro ao carregar banner';
        });
    });
});

// Função para mostrar o modal de carregamento
function showLoadingModal() {
    const modal = document.getElementById('loadingModal');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const steps = document.querySelectorAll('.step');
    
    modal.classList.add('show');
    
    let progress = 0;
    let currentStep = 0;
    
    const interval = setInterval(() => {
        progress += Math.random() * 15 + 5;
        
        if (progress >= 100) {
            progress = 100;
            clearInterval(interval);
            
            // Simular conclusão
            setTimeout(() => {
                modal.classList.remove('show');
            }, 1000);
        }
        
        progressFill.style.width = progress + '%';
        progressText.textContent = `${Math.round(progress)}%`;
        
        // Atualizar steps
        const stepIndex = Math.floor((progress / 100) * steps.length);
        if (stepIndex > currentStep && stepIndex < steps.length) {
            if (currentStep > 0) {
                steps[currentStep - 1].classList.remove('active');
                steps[currentStep - 1].classList.add('completed');
                steps[currentStep - 1].querySelector('i').className = 'fas fa-check';
                steps[currentStep - 1].querySelector('small').textContent = 'Concluído';
            }
            
            steps[stepIndex].classList.add('active');
            steps[stepIndex].querySelector('small').textContent = 'Processando...';
            currentStep = stepIndex + 1;
        }
    }, 200);
}
</script>

<?php include "includes/footer.php"; ?>