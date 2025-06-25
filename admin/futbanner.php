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
        CURLOPT_SSL_VERIFYPEER => false
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
        <a href="<?php echo $geradorScript; ?>?download_all=1" class="btn btn-success">
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
                    <div class="mb-4">
                        <img src="<?php echo $geradorScript; ?>?grupo=<?php echo $index; ?>" 
                             alt="Banner Parte <?php echo $index + 1; ?>" 
                             class="w-full h-auto rounded-lg border border-gray-200 shadow-sm">
                    </div>
                    <a href="<?php echo $geradorScript; ?>?grupo=<?php echo $index; ?>&download=1" 
                       class="btn btn-primary w-full">
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
    <!-- Loading Modal -->
    <div id="loadingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500 mx-auto mb-4"></div>
                <h3 class="text-lg font-semibold mb-2">Preparando Visualização</h3>
                <p class="text-muted mb-4">Carregando prévias dos banners...</p>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div id="progressBar" class="bg-primary-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="loadingStatus" class="text-sm text-muted mt-2">Iniciando...</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="card group hover:shadow-xl transition-all duration-300">
                <div class="card-header">
                    <h3 class="card-title">Banner Modelo <?php echo $i; ?></h3>
                    <p class="card-subtitle">Estilo profissional e moderno</p>
                </div>
                <div class="card-body">
                    <div class="relative mb-6 bg-gray-100 rounded-lg overflow-hidden" style="aspect-ratio: 16/9;">
                        <img src="gerar_fut<?php echo $i > 1 ? '_' . $i : ''; ?>.php?grupo=0" 
                             alt="Prévia do Banner <?php echo $i; ?>" 
                             class="w-full h-full object-cover preview-image opacity-0 transition-opacity duration-300"
                             onload="this.style.opacity='1'">
                        <div class="absolute inset-0 flex items-center justify-center bg-gray-200">
                            <div class="animate-pulse">
                                <i class="fas fa-image text-4xl text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    <a href="?banner=<?php echo $i; ?>" class="btn btn-primary w-full group-hover:bg-primary-600">
                        <i class="fas fa-check"></i>
                        Usar este Modelo
                    </a>
                </div>
            </div>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<style>
    [data-theme="dark"] .bg-gray-100 {
        background-color: var(--bg-tertiary);
    }
    
    [data-theme="dark"] .bg-gray-200 {
        background-color: var(--bg-secondary);
    }
    
    [data-theme="dark"] .text-gray-300 {
        color: var(--text-muted);
    }
    
    [data-theme="dark"] .text-gray-400 {
        color: var(--text-muted);
    }
    
    [data-theme="dark"] .border-gray-200 {
        border-color: var(--border-color);
    }
    
    .flex-wrap {
        flex-wrap: wrap;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('loadingModal');
        const statusText = document.getElementById('loadingStatus');
        const progressBar = document.getElementById('progressBar');
        const previewImages = document.querySelectorAll('.preview-image');
        const totalImages = previewImages.length;
        let loadedImages = 0;

        if (totalImages === 0) return;

        // Show loading modal
        modal.classList.remove('hidden');

        const handleImageLoad = () => {
            loadedImages++;
            const percentage = (loadedImages / totalImages) * 100;
            progressBar.style.width = percentage + '%';
            statusText.textContent = `Carregando banner ${loadedImages} de ${totalImages}...`;
            
            if (loadedImages === totalImages) {
                statusText.textContent = "Finalizando...";
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 500);
            }
        };
        
        previewImages.forEach(image => {
            image.onload = handleImageLoad;
            image.onerror = handleImageLoad;
        });
    });
</script>

<?php
} // Fim do if/else principal

include "includes/footer.php";
?>