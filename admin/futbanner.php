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
                        <img id="banner-img-<?php echo $index; ?>" 
                             src="" 
                             alt="Banner Parte <?php echo $index + 1; ?>" 
                             class="banner-preview-image loading"
                             data-grupo="<?php echo $index; ?>"
                             data-script="<?php echo $geradorScript; ?>"
                             style="display: none;">
                        <div id="loading-<?php echo $index; ?>" class="loading-placeholder">
                            <div class="loading-spinner"></div>
                            <p class="loading-text">Carregando banner...</p>
                        </div>
                        <div id="error-<?php echo $index; ?>" class="error-placeholder" style="display: none;">
                            <i class="fas fa-exclamation-triangle text-4xl text-danger-500 mb-2"></i>
                            <p class="error-text">Erro ao carregar banner</p>
                            <button class="btn btn-secondary btn-sm mt-2" onclick="retryBanner(<?php echo $index; ?>)">
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
        <?php endforeach; ?>
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
        min-height: 250px;
    }

    .banner-preview-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: opacity 0.3s ease;
    }

    .loading-placeholder,
    .error-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--text-muted);
        padding: 2rem;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid var(--border-color);
        border-top: 3px solid var(--primary-500);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 1rem;
    }

    .loading-text,
    .error-text {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin: 0;
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

    .mt-2 {
        margin-top: 0.5rem;
    }

    .mt-4 {
        margin-top: 1rem;
    }

    .w-full {
        width: 100%;
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
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

function loadBanner(index, script) {
    const img = document.getElementById(`banner-img-${index}`);
    const loading = document.getElementById(`loading-${index}`);
    const error = document.getElementById(`error-${index}`);
    
    if (!img || !loading || !error) return;
    
    // Reset estado
    img.style.display = 'none';
    loading.style.display = 'flex';
    error.style.display = 'none';
    
    // Criar URL com cache busting
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(7);
    const url = `${script}?grupo=${index}&_t=${timestamp}&_r=${random}`;
    
    console.log(`Carregando banner ${index}: ${url}`);
    
    // Timeout de 15 segundos
    const timeout = setTimeout(() => {
        console.log(`Timeout para banner ${index}`);
        showError(index, 'Timeout ao carregar banner');
    }, 15000);
    
    img.onload = function() {
        clearTimeout(timeout);
        console.log(`Banner ${index} carregado com sucesso`);
        
        // Verificar se a imagem realmente carregou
        if (this.naturalWidth === 0 || this.naturalHeight === 0) {
            console.log(`Banner ${index} carregou mas tem dimensões inválidas`);
            showError(index, 'Imagem inválida');
            return;
        }
        
        // Mostrar imagem
        this.style.display = 'block';
        loading.style.display = 'none';
        error.style.display = 'none';
        
        // Reset retry count
        retryCount[index] = 0;
    };
    
    img.onerror = function() {
        clearTimeout(timeout);
        console.log(`Erro ao carregar banner ${index}`);
        showError(index, 'Erro ao carregar imagem');
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
        return;
    }
    
    const img = document.getElementById(`banner-img-${index}`);
    const script = img.getAttribute('data-script');
    
    console.log(`Tentativa ${retryCount[index]} para banner ${index}`);
    
    // Delay progressivo
    const delay = retryCount[index] * 1000;
    setTimeout(() => {
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
        
        // Carregar banners com delay escalonado
        banners.forEach((banner, i) => {
            setTimeout(() => {
                loadBanner(banner.index, banner.script);
            }, i * 500); // 500ms entre cada banner
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
        const timeout = setTimeout(function() {
            if (!img.complete) {
                img.style.opacity = '0.5';
                img.alt = 'Erro ao carregar prévia';
            }
        }, 10000);
        
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