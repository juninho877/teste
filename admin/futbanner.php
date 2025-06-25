<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$pageTitle = isset($_GET['banner']) ? "Gerador de Banner" : "Selecionar Modelo de Banner";
include "includes/header.php";

// =================================================================================
// ETAPA 1: BUSCAR DADOS DOS JOGOS (SEMPRE)
// =================================================================================

// Funções de criptografia
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

// =================================================================================
// ETAPA 2: DECIDIR QUAL TELA EXIBIR
// =================================================================================

if (isset($_GET['banner'])) {
    // --- TELA FINAL: EXIBE OS BANNERS EM LAYOUT RESPONSIVO E MAIS COMPACTO ---

    $tipo_banner = $_GET['banner'];
    $geradorScript = '';

    switch ($tipo_banner) {
        case '1': $geradorScript = 'gerar_fut.php'; break;
        case '2': $geradorScript = 'gerar_fut_2.php'; break;
        case '3': $geradorScript = 'gerar_fut_3.php'; break;
        default:
            echo "<div class='container'><p class='alert alert-danger'>Tipo de banner inválido!</p></div>";
            include "includes/footer.php";
            exit();
    }
?>
    <style>
        .futbanner-container {
            padding: 20px;
            color: #f8f9fa;
            max-width: 1200px; /* <<< MUDANÇA: Largura máxima reduzida */
            margin: 0 auto;     /* Centraliza o container */
        }
        .futbanner-title { color: #ffffff; margin-bottom: 30px; text-align: center; }

        .banner-grid {
            display: grid;
            grid-template-columns: 1fr; /* Padrão para mobile: 1 coluna */
            gap: 15px; /* <<< MUDANÇA: Espaçamento reduzido */
        }

        /* Mantém as 2 colunas para telas maiores */
        @media (min-width: 992px) {
            .banner-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .banner-section {
            background-color: #2c3034; border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex; flex-direction: column;
        }
        .banner-title { color: #4e73df; margin-bottom: 15px; text-align: center; }
        .banner-image {
            max-width: 100%; height: auto; border: 1px solid #4e73df;
            border-radius: 6px; display: block; margin: 0 auto 15px auto;
        }
        .btn-download-container { text-align: center; margin-top: auto; }
        .btn-download {
            background-color: #4e73df; color: white; border: none; padding: 10px 20px;
            border-radius: 5px; text-decoration: none; display: inline-block;
            margin: 5px; transition: all 0.3s;
        }
        .btn-download:hover { background-color: #3a5bbf; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .btn-download-all { background-color: #1cc88a; margin-bottom: 30px; display: inline-block; }
        .btn-download-all:hover { background-color: #17a673; }
        .no-games { color: #f8f9fa; text-align: center; padding: 20px; background-color: #2c3034; border-radius: 8px; }
    </style>

    <div class="futbanner-container">
        <h1 class="futbanner-title">Banners de Jogos de Hoje (Modelo <?php echo $tipo_banner; ?>)</h1>
        <div class="text-center" style="margin-bottom: 20px;">
            <a href="<?php echo basename(__FILE__); ?>" class="btn-download" style="background-color: #f6c23e; color: #5a5c69;"><i class="fas fa-arrow-left"></i> Voltar para Seleção</a>
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
                        <img src="<?php echo $geradorScript; ?>?grupo=<?php echo $index; ?>" alt="Banner Parte <?php echo $index + 1; ?>" class="banner-image">
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

<?php
} else {
    // --- TELA INICIAL: SELEÇÃO COM O MODAL PROFISSIONAL ---
?>
    <div id="loading-modal">
        <div class="modal-content">
            <h3>Preparando Visualização</h3>
            <p>Por favor, aguarde enquanto carregamos as prévias dos banners.</p>
            <div class="progress-bar-container">
                <div id="progress-bar-fill"></div>
            </div>
            <p id="loading-status">Iniciando...</p>
        </div>
    </div>

    <style>
        /* ESTILOS DO MODAL PROFISSIONAL */
        #loading-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(10, 25, 47, 0.7); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999; opacity: 0; animation: modal-fade-in 0.5s forwards;
        }
        @keyframes modal-fade-in { to { opacity: 1; } }

        #loading-modal .modal-content {
            background: linear-gradient(145deg, #2c3e50, #1a2533); color: #ecf0f1;
            padding: 35px 45px; border-radius: 12px; text-align: center; width: 90%; max-width: 450px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1);
            transform: scale(0.95); opacity: 0; animation: modal-content-pop-in 0.5s 0.2s forwards;
        }
        @keyframes modal-content-pop-in { to { transform: scale(1); opacity: 1; } }
        
        #loading-modal h3 { margin-top: 0; margin-bottom: 10px; font-size: 1.5em; color: #fff; font-weight: 600; }
        #loading-modal p { margin-bottom: 25px; font-size: 1em; color: #bdc3c7; }
        .progress-bar-container { width: 100%; height: 10px; background-color: rgba(0, 0, 0, 0.3); border-radius: 5px; margin-bottom: 15px; overflow: hidden; }
        #progress-bar-fill { width: 0%; height: 100%; background: linear-gradient(90deg, #1abc9c, #16a085); border-radius: 5px; transition: width 0.4s ease-out; }
        #loading-status { font-size: 0.9em; color: #95a5a6; margin-bottom: 0; min-height: 1.2em; }
        
        /* Estilos da página de seleção */
        .selection-container { padding: 40px 20px; color: #f8f9fa; }
        .selection-title { text-align: center; margin-bottom: 40px; }
        .preview-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 30px; }
        .preview-card {
            background-color: #2c3034; border-radius: 8px; padding: 20px; text-align: center;
            width: 350px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .preview-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0,0,0,0.3); }
        .preview-card h2 { color: #4e73df; margin-bottom: 15px; }
        .preview-image-container {
             flex-grow: 1; display: flex; align-items: center; justify-content: center;
             margin-bottom: 20px; background-color: #1a1c1e;
             border: 1px solid #4e73df; border-radius: 6px; min-height: 400px;
        }
        .preview-image { max-width: 100%; height: auto; border-radius: 6px; }
        .btn-select {
            display: block; width: 100%; padding: 12px; font-size: 1.1em; color: #fff;
            background-color: #1cc88a; border: none; border-radius: 5px; text-decoration: none;
            transition: background-color 0.3s; margin-top: auto;
        }
        .btn-select:hover { background-color: #17a673; color: #fff; }
        .no-games { text-align: center; padding: 20px; background-color: #2c3034; border-radius: 8px; }
    </style>

    <div class="selection-container">
        <h1 class="selection-title">Escolha o Modelo de Banner</h1>
        <?php if (empty($jogos)): ?>
            <div class="no-games"><p>Nenhum jogo disponível no momento para gerar as prévias.</p></div>
        <?php else: ?>
            <div class="preview-grid">
                <div class="preview-card">
                    <div>
                        <h2>Banner 1</h2>
                        <div class="preview-image-container">
                            <img src="gerar_fut.php?grupo=0" alt="Prévia do Banner 1" class="preview-image">
                        </div>
                    </div>
                    <a href="?banner=1" class="btn-select">Usar este Modelo</a>
                </div>
                <div class="preview-card">
                     <div>
                        <h2>Banner 2</h2>
                        <div class="preview-image-container">
                            <img src="gerar_fut_2.php?grupo=0" alt="Prévia do Banner 2" class="preview-image">
                        </div>
                    </div>
                    <a href="?banner=2" class="btn-select">Usar este Modelo</a>
                </div>
                <div class="preview-card">
                     <div>
                        <h2>Banner 3</h2>
                        <div class="preview-image-container">
                            <img src="gerar_fut_3.php?grupo=0" alt="Prévia do Banner 3" class="preview-image">
                        </div>
                    </div>
                    <a href="?banner=3" class="btn-select">Usar este Modelo</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('loading-modal');
            const statusText = document.getElementById('loading-status');
            const progressBarFill = document.getElementById('progress-bar-fill');
            const previewImages = document.querySelectorAll('.preview-image');
            const totalImages = previewImages.length;
            let loadedImages = 0;

            if (totalImages === 0) {
                modal.style.display = 'none';
                return;
            }

            const handleImageLoad = () => {
                loadedImages++;
                const percentage = (loadedImages / totalImages) * 100;
                progressBarFill.style.width = percentage + '%';
                statusText.textContent = `Carregando banner ${loadedImages} de ${totalImages}...`;
                
                if (loadedImages === totalImages) {
                    statusText.textContent = "Finalizando...";
                    setTimeout(() => {
                        modal.style.opacity = '0';
                        modal.querySelector('.modal-content').style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            modal.style.display = 'none';
                        }, 500);
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