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

<style>
    .futbanner-container {
        padding: 20px;
        color: #f8f9fa;
        max-width: 1200px;
        margin: 0 auto;
    }
    .futbanner-title { 
        color: #ffffff; 
        margin-bottom: 30px; 
        text-align: center; 
    }

    .banner-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
    }

    @media (min-width: 992px) {
        .banner-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .banner-section {
        background-color: #2c3034; 
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: flex; 
        flex-direction: column;
    }
    .banner-title { 
        color: #4e73df; 
        margin-bottom: 15px; 
        text-align: center; 
    }
    .banner-image {
        max-width: 100%; 
        height: auto; 
        border: 1px solid #4e73df;
        border-radius: 6px; 
        display: block; 
        margin: 0 auto 15px auto;
        min-height: 200px;
        background: #1a1d20;
        position: relative;
    }
    .banner-image.loading {
        background: #1a1d20 url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiBzdHJva2U9IiM0ZTczZGYiPjxnIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+PGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMSAxKSIgc3Ryb2tlLXdpZHRoPSIyIj48Y2lyY2xlIHN0cm9rZS1vcGFjaXR5PSIuNSIgY3g9IjE4IiBjeT0iMTgiIHI9IjE4Ii8+PHBhdGggZD0ibTM5IDM5YzAtOS45NC04LjA2LTE4LTE4LTE4Ij48YW5pbWF0ZVRyYW5zZm9ybSBhdHRyaWJ1dGVOYW1lPSJ0cmFuc2Zvcm0iIHR5cGU9InJvdGF0ZSIgZnJvbT0iMCAxOCAxOCIgdG89IjM2MCAxOCAxOCIgZHVyPSIxcyIgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiLz48L3BhdGg+PC9nPjwvZz48L3N2Zz4=') no-repeat center center;
    }
    .btn-download-container { 
        text-align: center; 
        margin-top: auto; 
    }
    .btn-download {
        background-color: #4e73df; 
        color: white; 
        border: none; 
        padding: 10px 20px;
        border-radius: 5px; 
        text-decoration: none; 
        display: inline-block;
        margin: 5px; 
        transition: all 0.3s;
    }
    .btn-download:hover { 
        background-color: #3a5bbf; 
        transform: translateY(-2px); 
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
    }
    .btn-download-all { 
        background-color: #1cc88a; 
        margin-bottom: 30px; 
        display: inline-block; 
    }
    .btn-download-all:hover { 
        background-color: #17a673; 
    }
    .no-games { 
        color: #f8f9fa; 
        text-align: center; 
        padding: 20px; 
        background-color: #2c3034; 
        border-radius: 8px; 
    }
    .error-banner {
        background: #dc3545;
        color: white;
        padding: 10px;
        border-radius: 4px;
        text-align: center;
        margin: 10px 0;
        font-size: 14px;
    }
    .retry-btn {
        background: #ffc107;
        color: #212529;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
        margin-top: 5px;
        font-size: 12px;
    }
    .retry-btn:hover {
        background: #e0a800;
    }
</style>

<div class="futbanner-container">
    <h1 class="futbanner-title">Banners de Jogos de Hoje (Modelo <?php echo $tipo_banner; ?>)</h1>
    <div class="text-center" style="margin-bottom: 20px;">
        <a href="<?php echo basename(__FILE__); ?>" class="btn-download" style="background-color: #f6c23e; color: #5a5c69;">
            <i class="fas fa-arrow-left"></i> Voltar para Seleção
        </a>
    </div>

    <?php if (empty($jogos)) : ?>
        <div class="no-games"><p>Nenhum jogo disponível no momento.</p></div>
    <?php else : ?>
        <div class="text-center">
            <a href="<?php echo $geradorScript; ?>?download_all=1" class="btn-download btn-download-all">
                <i class="fas fa-file-archive"></i> Baixar Todos (ZIP)
            </a>
        </div>

        <div class="banner-grid">
            <?php foreach ($gruposDeJogos as $index => $grupo): ?>
                <div class="banner-section">
                    <h2 class="banner-title">Banner Parte <?php echo $index + 1; ?></h2>
                    <div id="banner-container-<?php echo $index; ?>">
                        <img id="banner-img-<?php echo $index; ?>" 
                             src="" 
                             alt="Banner Parte <?php echo $index + 1; ?>" 
                             class="banner-image loading"
                             data-grupo="<?php echo $index; ?>"
                             data-script="<?php echo $geradorScript; ?>"
                             style="display: none;">
                        <div id="error-<?php echo $index; ?>" class="error-banner" style="display: none;">
                            <div>Erro ao carregar banner</div>
                            <button class="retry-btn" onclick="retryBanner(<?php echo $index; ?>)">
                                <i class="fas fa-redo"></i> Tentar Novamente
                            </button>
                        </div>
                    </div>
                    <div class="btn-download-container">
                        <a href="<?php echo $geradorScript; ?>?grupo=<?php echo $index; ?>&download=1" class="btn-download">
                            <i class="fas fa-download"></i> Baixar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
let retryCount = {};
const maxRetries = 5;

function loadBanner(index, script) {
    const img = document.getElementById(`banner-img-${index}`);
    const error = document.getElementById(`error-${index}`);
    const container = document.getElementById(`banner-container-${index}`);
    
    if (!img || !error) return;
    
    // Reset estado
    img.style.display = 'none';
    error.style.display = 'none';
    img.className = 'banner-image loading';
    
    // Criar URL com cache busting mais agressivo
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(7);
    const cacheBust = Math.floor(Math.random() * 1000000);
    const url = `${script}?grupo=${index}&_t=${timestamp}&_r=${random}&_cb=${cacheBust}&_v=${Math.random()}`;
    
    console.log(`Carregando banner ${index}: ${url}`);
    
    // Timeout de 30 segundos
    const timeout = setTimeout(() => {
        console.log(`Timeout para banner ${index}`);
        showError(index, 'Timeout: Banner demorou muito para carregar');
    }, 30000);
    
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
        this.className = 'banner-image';
        this.style.display = 'block';
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
    const error = document.getElementById(`error-${index}`);
    
    if (img) {
        img.style.display = 'none';
        img.className = 'banner-image';
    }
    
    if (error) {
        error.style.display = 'block';
        error.innerHTML = `
            <div>${message}</div>
            <button class="retry-btn" onclick="retryBanner(${index})">
                <i class="fas fa-redo"></i> Tentar Novamente (${retryCount[index] || 0}/${maxRetries})
            </button>
        `;
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
            }, i * 1000); // 1 segundo entre cada banner
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
        transition: opacity 0.3s ease;
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

    .mt-4 {
        margin-top: 1rem;
    }

    .w-full {
        width: 100%;
    }

    [data-theme="dark"] .text-warning-500 {
        color: #f59e0b;
    }

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