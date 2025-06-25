<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$background_types = [
    'fundo_banner_1' => ['name' => 'Fundo Banner 1', 'json_path' => './api/fzstore/background_banner_1.json', 'fixed_filename' => 'background_banner_1'],
    'fundo_banner_2' => ['name' => 'Fundo Banner 2', 'json_path' => './api/fzstore/background_banner_2.json', 'fixed_filename' => 'background_banner_2'],
    'fundo_banner_3' => ['name' => 'Fundo Banner 3', 'json_path' => './api/fzstore/background_banner_3.json', 'fixed_filename' => 'background_banner_3'],
];

$current_bg_key = $_GET['tipo'] ?? array_key_first($background_types);
if (!array_key_exists($current_bg_key, $background_types)) {
    header("Location: background.php");
    exit();
}

$current_bg_config = $background_types[$current_bg_key];
$jsonPath = $current_bg_config['json_path'];
$successMessage = '';
$errorMessage = '';
$redirect_bg_key = $current_bg_key;

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $posted_bg_type = $_POST['bg_type'] ?? null;
    if ($posted_bg_type && isset($background_types[$posted_bg_type])) {
        $redirect_bg_key = $posted_bg_type;
        $jsonPath_to_update = $background_types[$posted_bg_type]['json_path'];
        $fixed_filename_base = $background_types[$posted_bg_type]['fixed_filename'];

        function update_background_json($path, $imageName, $uploadType) {
            $jsonData = json_encode([["ImageName" => $imageName, "Upload_type" => $uploadType]]);
            return file_put_contents($path, $jsonData) ? "Plano de fundo atualizado com sucesso!" : "Erro ao salvar as informações da imagem.";
        }

        if (isset($_POST['upload']) && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($file['type'], $allowedTypes)) {
                $uploadPath = './fzstore/Img/';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = $fixed_filename_base . '.' . $extension;
                $destination = $uploadPath . $fileName;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $successMessage = update_background_json($jsonPath_to_update, "../fzstore/Img/" . $fileName, "by_file");
                } else { $errorMessage = 'Falha ao mover o arquivo enviado.'; }
            } else { $errorMessage = 'Tipo de arquivo inválido.'; }
        } elseif (isset($_POST['url-submit'])) {
            $imageUrl = filter_var($_POST['image-url'], FILTER_SANITIZE_URL);
            if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $successMessage = update_background_json($jsonPath_to_update, $imageUrl, "by_url");
            } else { $errorMessage = 'A URL fornecida não é válida.'; }
        }
    } else {
        $errorMessage = "Tipo de plano de fundo inválido enviado.";
    }
}

$methord = "Não Definido";
$imageFilex = '';
$showPreview = false;
if (file_exists($jsonPath)) {
    $jsonDatax = json_decode(file_get_contents($jsonPath), true);
    if (isset($jsonDatax) && is_array($jsonDatax) && !empty($jsonDatax) && isset($jsonDatax[0])) {
        $filenamex = $jsonDatax[0]['ImageName'] ?? '';
        $uploadmethord = $jsonDatax[0]['Upload_type'] ?? 'default';
        if ($uploadmethord == "by_file" && !empty($filenamex)) {
            $imageFilex = str_replace('../', '/admin/', $filenamex);
            $methord = "Arquivo Enviado";
            $showPreview = true;
        } elseif ($uploadmethord == "by_url" && filter_var($filenamex, FILTER_VALIDATE_URL)) {
            $imageFilex = $filenamex;
            $methord = "URL Externa";
            $showPreview = true;
        }
    }
}

$pageTitle = "Gerenciar Fundos";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-photo-video text-primary-500 mr-3"></i>
        Gerenciar Planos de Fundo
    </h1>
    <p class="page-subtitle">Configure os fundos utilizados nos banners</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Preview Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Visualização</h3>
            <p class="card-subtitle">Selecione e visualize o fundo atual</p>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="bg-selector" class="form-label">Plano de Fundo para Editar:</label>
                <select id="bg-selector" class="form-input form-select">
                    <?php foreach ($background_types as $key => $details): ?>
                        <option value="<?= $key ?>" <?= ($key == $current_bg_key) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($details['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="preview-container">
                <label class="form-label">Prévia Atual:</label>
                <div class="preview-area">
                    <?php if ($showPreview): ?>
                        <img src="<?= $imageFilex ?>?v=<?= time() ?>" alt="Preview do Fundo" class="preview-image">
                    <?php else: ?>
                        <div class="preview-placeholder">
                            <i class="fas fa-image text-4xl text-gray-400 mb-2"></i>
                            <span class="text-gray-500">Nenhum fundo definido</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="current-method-info">
                    <span class="method-badge">Método Atual: <strong><?= $methord ?></strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Alterar Fundo</h3>
            <p class="card-subtitle">Envie um novo arquivo ou use uma URL</p>
        </div>
        <div class="card-body">
            <div class="method-switcher">
                <input type="radio" id="upload-radio" name="upload-type" value="file" checked>
                <label for="upload-radio">
                    <i class="fas fa-upload"></i>
                    Enviar Arquivo
                </label>
                
                <input type="radio" id="url-radio" name="upload-type" value="url">
                <label for="url-radio">
                    <i class="fas fa-link"></i>
                    Usar URL
                </label>
            </div>

            <div class="forms-container">
                <!-- Upload Form -->
                <form method="post" enctype="multipart/form-data" id="upload-form" class="method-form" action="background.php?tipo=<?= $current_bg_key ?>">
                    <input type="hidden" name="bg_type" value="<?= $current_bg_key ?>">
                    <div class="form-group">
                        <label for="image" class="form-label">Selecione uma imagem:</label>
                        <input class="form-input" type="file" name="image" id="image" accept="image/*">
                        <p class="form-help">Formatos aceitos: PNG, JPG, GIF, WebP</p>
                    </div>
                    <button class="btn btn-primary w-full" type="submit" name="upload">
                        <i class="fas fa-upload"></i>
                        Enviar Arquivo
                    </button>
                </form>

                <!-- URL Form -->
                <form method="post" id="url-form" class="method-form" style="display: none;" action="background.php?tipo=<?= $current_bg_key ?>">
                    <input type="hidden" name="bg_type" value="<?= $current_bg_key ?>">
                    <div class="form-group">
                        <label for="image-url" class="form-label">URL da imagem:</label>
                        <input class="form-input" type="text" name="image-url" id="image-url" placeholder="https://exemplo.com/fundo.jpg">
                        <p class="form-help">Insira a URL completa da imagem</p>
                    </div>
                    <button class="btn btn-primary w-full" type="submit" name="url-submit">
                        <i class="fas fa-save"></i>
                        Salvar URL
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .preview-container {
        margin-top: 1.5rem;
    }

    .preview-area {
        width: 100%;
        height: 250px;
        background: var(--bg-secondary);
        border: 2px dashed var(--border-color);
        border-radius: var(--border-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 0.5rem;
        position: relative;
        overflow: hidden;
    }

    .preview-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .preview-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--text-muted);
    }

    .current-method-info {
        text-align: center;
        margin-top: 1rem;
    }

    .method-badge {
        display: inline-block;
        background: var(--bg-tertiary);
        color: var(--text-secondary);
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius-sm);
        font-size: 0.875rem;
    }

    .method-badge strong {
        color: var(--primary-500);
    }

    .method-switcher {
        display: flex;
        background: var(--bg-tertiary);
        border-radius: var(--border-radius);
        padding: 0.25rem;
        margin-bottom: 1.5rem;
    }

    .method-switcher input[type="radio"] {
        display: none;
    }

    .method-switcher label {
        flex: 1;
        text-align: center;
        padding: 0.75rem 1rem;
        cursor: pointer;
        border-radius: var(--border-radius-sm);
        transition: var(--transition);
        font-weight: 500;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .method-switcher input[type="radio"]:checked + label {
        background: var(--primary-500);
        color: white;
        box-shadow: var(--shadow-sm);
    }

    .forms-container {
        margin-bottom: 1.5rem;
    }

    .method-form {
        animation: fadeIn 0.3s ease-out;
    }

    .form-help {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Dark theme adjustments */
    [data-theme="dark"] .preview-placeholder {
        color: var(--text-muted);
    }

    [data-theme="dark"] .text-gray-400 {
        color: var(--text-muted);
    }

    [data-theme="dark"] .text-gray-500 {
        color: var(--text-muted);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bgSelector = document.getElementById('bg-selector');
    bgSelector.addEventListener('change', function() {
        window.location.href = 'background.php?tipo=' + this.value;
    });

    const uploadRadio = document.getElementById('upload-radio');
    const urlRadio = document.getElementById('url-radio');
    const uploadForm = document.getElementById('upload-form');
    const urlForm = document.getElementById('url-form');
    
    function switchForms() {
        if (uploadRadio.checked) {
            uploadForm.style.display = 'block';
            urlForm.style.display = 'none';
        } else {
            uploadForm.style.display = 'none';
            urlForm.style.display = 'block';
        }
    }
    
    uploadRadio.addEventListener('change', switchForms);
    urlRadio.addEventListener('change', switchForms);
    switchForms();

    <?php if (!empty($successMessage)): ?>
    Swal.fire({
        title: 'Sucesso!',
        text: '<?= addslashes($successMessage) ?>',
        icon: 'success',
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
        confirmButtonColor: '#3b82f6'
    }).then(() => {
        window.location.href = window.location.pathname + '?tipo=<?= $redirect_bg_key ?>';
    });
    <?php elseif (!empty($errorMessage)): ?>
    Swal.fire({
        title: 'Erro!',
        text: '<?= addslashes($errorMessage) ?>',
        icon: 'error',
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
        confirmButtonColor: '#ef4444'
    });
    <?php endif; ?>
});
</script>

<?php include "includes/footer.php"; ?>