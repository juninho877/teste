<?php
// Sua lógica PHP para o 'logo.php' foi mantida integralmente.
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$logo_types = [
    'logo_banner_1' => ['name' => 'Logo Banner 1', 'json_path' => './api/fzstore/logo_banner_1.json', 'fixed_filename' => 'logo_banner_1'],
    'logo_banner_2' => ['name' => 'Logo Banner 2', 'json_path' => './api/fzstore/logo_banner_2.json', 'fixed_filename' => 'logo_banner_2'],
    'logo_banner_3' => ['name' => 'Logo Banner 3', 'json_path' => './api/fzstore/logo_banner_3.json', 'fixed_filename' => 'logo_banner_3'],
];

$current_logo_key = $_GET['tipo'] ?? array_key_first($logo_types);
if (!array_key_exists($current_logo_key, $logo_types)) {
    header("Location: logo.php");
    exit();
}

$current_logo_config = $logo_types[$current_logo_key];
$jsonPath = $current_logo_config['json_path'];
$successMessage = '';
$errorMessage = '';
$redirect_logo_key = $current_logo_key;

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $posted_logo_type = $_POST['logo_type'] ?? null;
    if ($posted_logo_type && isset($logo_types[$posted_logo_type])) {
        $redirect_logo_key = $posted_logo_type;
        $jsonPath_to_update = $logo_types[$posted_logo_type]['json_path'];
        $fixed_filename_base = $logo_types[$posted_logo_type]['fixed_filename'];

        function update_logo_json($path, $imageName, $uploadType) {
            $jsonData = json_encode([["ImageName" => $imageName, "Upload_type" => $uploadType]]);
            return file_put_contents($path, $jsonData) ? "Logo atualizado com sucesso!" : "Erro ao salvar as informações do logo.";
        }

        if (isset($_POST['upload']) && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($file['type'], $allowedTypes)) {
                $uploadPath = './fzstore/logo/';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = $fixed_filename_base . '.' . $extension;
                $destination = $uploadPath . $fileName;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $successMessage = update_logo_json($jsonPath_to_update, "../fzstore/logo/" . $fileName, "by_file");
                } else { $errorMessage = 'Falha ao mover o arquivo enviado.'; }
            } else { $errorMessage = 'Tipo de arquivo inválido.'; }
        } elseif (isset($_POST['url-submit'])) {
            $imageUrl = filter_var($_POST['image-url'], FILTER_SANITIZE_URL);
            if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $successMessage = update_logo_json($jsonPath_to_update, $imageUrl, "by_url");
            } else { $errorMessage = 'A URL fornecida não é válida.'; }
        } elseif (isset($_POST['default-logo'])) {
            $successMessage = update_logo_json($jsonPath_to_update, "imgelementos/semlogo.png", "default");
        }
    } else {
        $errorMessage = "Tipo de logo inválido enviado.";
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
        } elseif ($uploadmethord == "default") {
            $imageFilex = "/admin/imgelementos/semlogo.png";
            $methord = "Logo Padrão";
            $showPreview = true;
        }
    }
}

$pageTitle = "Gerenciar Logos";
include "includes/header.php"; 
?>

<style>
    .two-column-grid {
        display: grid;
        grid-template-columns: 1fr; /* Padrão mobile */
        gap: 30px;
    }
    @media (min-width: 768px) {
        .two-column-grid {
            grid-template-columns: 1fr 1fr; /* 2 colunas em telas maiores */
        }
    }
    .column-box {
        background: rgba(0,0,0,0.15);
        padding: 25px;
        border-radius: 10px;
    }
    .column-box h3 {
        color: var(--accent-color);
        margin-bottom: 20px;
        font-weight: 600;
        border-bottom: 1px solid var(--accent-color);
        padding-bottom: 10px;
    }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500; }
    .form-select, .form-control {
        width: 100%; padding: 12px 15px; background-color: var(--page-bg);
        border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: var(--text-color);
    }
    .form-select:focus, .form-control:focus { outline: none; border-color: var(--accent-color); }
    
    .preview-area {
        width: 100%;
        height: 150px; /* Um pouco mais alto para logos */
        margin-top: 15px;
        background-color: var(--page-bg);
        background-image: linear-gradient(45deg, #2c2f4a 25%, transparent 25%), linear-gradient(-45deg, #2c2f4a 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #2c2f4a 75%), linear-gradient(-45deg, transparent 75%, #2c2f4a 75%);
        background-size: 20px 20px;
        border: 2px dashed rgba(255,255,255,0.2);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        padding: 10px;
    }
    .preview-area img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .current-method-info { text-align: center; color: var(--text-muted); font-size: 0.9rem; margin-top: 15px; }
    .current-method-info strong { color: var(--accent-color); }
    
    .method-switcher { display: flex; border-radius: 8px; overflow: hidden; }
    .method-switcher input[type="radio"] { display: none; }
    .method-switcher label {
        flex: 1; text-align: center; padding: 12px; cursor: pointer;
        background-color: rgba(0,0,0,0.2); color: var(--text-muted);
        transition: all 0.3s; font-weight: 500;
    }
    .method-switcher input[type="radio"]:checked + label { background-color: var(--accent-color); color: #fff; }
    
    .submit-btn, .secondary-btn {
        width: 100%; padding: 12px; font-size: 1rem; font-weight: 600; color: #fff;
        border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s;
        display: flex; align-items: center; justify-content: center;
    }
    .submit-btn i, .secondary-btn i { margin-right: 8px; }

    .submit-btn { background-color: var(--success-color); }
    .submit-btn:hover { background-color: #218838; transform: translateY(-2px); }

    .secondary-btn { background-color: #6c757d; } /* Cinza para ação secundária */
    .secondary-btn:hover { background-color: #5a6268; }

    .divider-or { text-align: center; color: var(--text-muted); margin: 20px 0; font-weight: bold; }
</style>

<div class="page-header">
    <h1><i class="fas fa-image" style="color: var(--accent-color);"></i> Gerenciar Logos</h1>
</div>

<div class="content-card">
    <div class="two-column-grid">
        <div class="column-box">
            <h3>1. Selecione e Visualize</h3>
            <div class="form-group">
                <label for="logo-selector">Logo para Editar:</label>
                <select id="logo-selector" class="form-select">
                    <?php foreach ($logo_types as $key => $details): ?>
                        <option value="<?= $key ?>" <?= ($key == $current_logo_key) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($details['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <label>Prévia Atual:</label>
            <div class="preview-area">
                <?php if ($showPreview): ?>
                    <img src="<?= $imageFilex ?>?v=<?= time() ?>" alt="Preview do Logo">
                <?php else: ?>
                    <span style="color: var(--text-muted);">Nenhum logo definido.</span>
                <?php endif; ?>
            </div>
            <p class="current-method-info">Método Atual: <strong><?= $methord ?></strong></p>
        </div>

        <div class="column-box">
            <h3>2. Altere a Imagem</h3>
            <div class="method-switcher">
                <input type="radio" id="upload-radio" name="upload-type" value="file" checked>
                <label for="upload-radio"><i class="fas fa-upload"></i> Enviar Arquivo</label>
                
                <input type="radio" id="url-radio" name="upload-type" value="url">
                <label for="url-radio"><i class="fas fa-link"></i> Usar URL</label>
            </div>
            <div class="forms-container" style="margin-top: 20px;">
                <form method="post" enctype="multipart/form-data" id="upload-form" class="method-form" action="logo.php?tipo=<?= $current_logo_key ?>">
                    <input type="hidden" name="logo_type" value="<?= $current_logo_key ?>">
                    <div class="form-group">
                        <label for="image">Selecione uma imagem (PNG, JPG, GIF, WebP):</label>
                        <input class="form-control" type="file" name="image" id="image" accept="image/*">
                    </div>
                    <button class="submit-btn" type="submit" name="upload"><i class="fas fa-paper-plane"></i> Enviar</button>
                </form>

                <form method="post" id="url-form" class="method-form" style="display: none;" action="logo.php?tipo=<?= $current_logo_key ?>">
                    <input type="hidden" name="logo_type" value="<?= $current_logo_key ?>">
                    <div class="form-group">
                        <label for="image-url">Insira a URL da imagem:</label>
                        <input class="form-control" type="text" name="image-url" id="image-url" placeholder="https://...">
                    </div>
                    <button class="submit-btn" type="submit" name="url-submit"><i class="fas fa-save"></i> Salvar URL</button>
                </form>
            </div>

            <div class="divider-or">OU</div>

            <form method="post" id="default-form" action="logo.php?tipo=<?= $current_logo_key ?>">
                <input type="hidden" name="logo_type" value="<?= $current_logo_key ?>">
                <button class="secondary-btn" type="submit" name="default-logo"><i class="fas fa-undo"></i> Restaurar Padrão</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sua lógica de JS original foi mantida
    const logoSelector = document.getElementById('logo-selector');
    logoSelector.addEventListener('change', function() {
        window.location.href = 'logo.php?tipo=' + this.value;
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
        title: 'Sucesso!', text: '<?= addslashes($successMessage) ?>', icon: 'success',
        background: '#2c2f4a', color: '#f1f1f1', confirmButtonColor: 'var(--success-color)'
    }).then(() => {
        window.location.href = window.location.pathname + '?tipo=<?= $redirect_logo_key ?>';
    });
    <?php elseif (!empty($errorMessage)): ?>
    Swal.fire({
        title: 'Erro!', text: '<?= addslashes($errorMessage) ?>', icon: 'error',
        background: '#2c2f4a', color: '#f1f1f1', confirmButtonColor: 'var(--danger-color)'
    });
    <?php endif; ?>
});
</script>

<?php 
include "includes/footer.php"; 
?>