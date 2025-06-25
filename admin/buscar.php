<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

// Configuração da API
$apiKey = 'ec8237f367023fbadd38ab6a1596b40c';
$language = 'pt-BR';

$pageTitle = "Resultados da Busca";
include "includes/header.php";

// Verificar se há parâmetros de busca
if (!isset($_GET['query']) || empty(trim($_GET['query']))) {
    ?>
    <div class="page-header">
        <h1 class="page-title">Erro na Busca</h1>
        <p class="page-subtitle">Parâmetros de busca inválidos</p>
    </div>

    <div class="card">
        <div class="card-body text-center py-12">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle text-6xl text-warning-500"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Busca inválida</h3>
            <p class="text-muted mb-6">Por favor, realize uma busca válida na página anterior.</p>
            <a href="painel.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i>
                Voltar para Busca
            </a>
        </div>
    </div>
    <?php
    include "includes/footer.php";
    exit();
}

try {
    $query = urlencode(trim($_GET['query']));
    $type = isset($_GET['type']) && $_GET['type'] == 'serie' ? 'serie' : 'filme';
    $ano = isset($_GET['ano_lancamento']) && !empty($_GET['ano_lancamento']) ? intval($_GET['ano_lancamento']) : null;
    
    // Determinar tipo da API
    if ($type == 'serie') {
        $api_type = 'tv';
        $url = "https://api.themoviedb.org/3/search/tv?api_key=$apiKey&language=$language&query=$query";
        if ($ano) { 
            $url .= "&first_air_date_year=$ano"; 
        }
    } else {
        $api_type = 'movie';
        $url = "https://api.themoviedb.org/3/search/movie?api_key=$apiKey&language=$language&query=$query";
        if ($ano) { 
            $url .= "&primary_release_year=$ano"; 
        }
    }

    // Fazer requisição à API
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (compatible; FutBanner/1.0)'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception("Erro ao conectar com a API do TMDB");
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erro ao decodificar resposta da API");
    }

    ?>
    <div class="page-header">
        <h1 class="page-title">Resultados da Busca</h1>
        <p class="page-subtitle">Encontramos os seguintes resultados para: <strong>"<?php echo htmlspecialchars(urldecode($_GET['query'])); ?>"</strong></p>
    </div>

    <div class="mb-6">
        <a href="painel.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Nova Busca
        </a>
    </div>

    <?php if ($data && isset($data['results']) && !empty($data['results'])): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($data['results'] as $item): 
                $id = isset($item['id']) ? $item['id'] : 0;
                $title = isset($item['title']) ? $item['title'] : (isset($item['name']) ? $item['name'] : 'Título não disponível');
                $posterPath = isset($item['poster_path']) && $item['poster_path'] ? "https://image.tmdb.org/t/p/w500" . $item['poster_path'] : null;
                $releaseDate = isset($item['release_date']) ? $item['release_date'] : (isset($item['first_air_date']) ? $item['first_air_date'] : '');
                $year = $releaseDate ? substr($releaseDate, 0, 4) : '';
                $overview = isset($item['overview']) ? $item['overview'] : '';
                $rating = isset($item['vote_average']) ? $item['vote_average'] : 0;
            ?>
                <div class="card group hover:shadow-xl transition-all duration-300">
                    <div class="relative overflow-hidden">
                        <?php if ($posterPath): ?>
                            <img src="<?php echo htmlspecialchars($posterPath); ?>" 
                                 alt="Poster de <?php echo htmlspecialchars($title); ?>" 
                                 class="w-full h-80 object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-80 bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-image text-4xl text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Rating Badge -->
                        <?php if ($rating > 0): ?>
                            <div class="absolute top-3 right-3 bg-black bg-opacity-75 text-white px-2 py-1 rounded-lg text-sm font-semibold">
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                <?php echo number_format($rating, 1); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Type Badge -->
                        <div class="absolute top-3 left-3 bg-primary-500 text-white px-2 py-1 rounded-lg text-xs font-semibold uppercase">
                            <?php echo $type === 'serie' ? 'Série' : 'Filme'; ?>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <h3 class="font-semibold text-lg mb-2 line-clamp-2"><?php echo htmlspecialchars($title); ?></h3>
                        
                        <?php if ($releaseDate): ?>
                            <p class="text-sm text-muted mb-2">
                                <i class="fas fa-calendar mr-1"></i>
                                <?php echo date('d/m/Y', strtotime($releaseDate)); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($overview): ?>
                            <p class="text-sm text-muted mb-4 line-clamp-3"><?php echo htmlspecialchars(substr($overview, 0, 120)) . '...'; ?></p>
                        <?php endif; ?>
                        
                        <form method="GET" action="gerar_banner.php" onsubmit="showLoading(event, this)" class="mt-auto">
                            <input type="hidden" name="name" value="<?php echo htmlspecialchars($title, ENT_QUOTES); ?>">
                            <input type="hidden" name="type" value="<?php echo $type === 'serie' ? 'serie' : 'filme'; ?>">
                            <input type="hidden" name="year" value="<?php echo htmlspecialchars($year, ENT_QUOTES); ?>">
                            <button type="submit" class="btn btn-primary w-full">
                                <i class="fas fa-magic"></i>
                                Gerar Banner
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-12">
                <div class="mb-4">
                    <i class="fas fa-search text-6xl text-gray-300"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Nenhum resultado encontrado</h3>
                <p class="text-muted mb-6">Tente buscar com termos diferentes ou verifique a ortografia.</p>
                <a href="painel.php" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Fazer Nova Busca
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php
} catch (Exception $e) {
    ?>
    <div class="page-header">
        <h1 class="page-title">Erro na Busca</h1>
        <p class="page-subtitle">Ocorreu um problema ao processar sua solicitação</p>
    </div>

    <div class="card">
        <div class="card-body text-center py-12">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle text-6xl text-danger-500"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Erro no Sistema</h3>
            <p class="text-muted mb-6"><?php echo htmlspecialchars($e->getMessage()); ?></p>
            <div class="flex gap-4 justify-center">
                <a href="painel.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Voltar para Busca
                </a>
                <button onclick="location.reload()" class="btn btn-secondary">
                    <i class="fas fa-redo"></i>
                    Tentar Novamente
                </button>
            </div>
        </div>
    </div>
    <?php
}
?>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .relative {
        position: relative;
    }
    
    .absolute {
        position: absolute;
    }
    
    .top-3 {
        top: 0.75rem;
    }
    
    .right-3 {
        right: 0.75rem;
    }
    
    .left-3 {
        left: 0.75rem;
    }
    
    .bg-black {
        background-color: rgb(0 0 0);
    }
    
    .bg-opacity-75 {
        background-color: rgb(0 0 0 / 0.75);
    }
    
    .text-white {
        color: rgb(255 255 255);
    }
    
    .px-2 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    
    .py-1 {
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
    }
    
    .rounded-lg {
        border-radius: var(--border-radius);
    }
    
    .text-sm {
        font-size: 0.875rem;
        line-height: 1.25rem;
    }
    
    .text-xs {
        font-size: 0.75rem;
        line-height: 1rem;
    }
    
    .font-semibold {
        font-weight: 600;
    }
    
    .uppercase {
        text-transform: uppercase;
    }
    
    .text-yellow-400 {
        color: rgb(250 204 21);
    }
    
    .mr-1 {
        margin-right: 0.25rem;
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
    
    .mt-auto {
        margin-top: auto;
    }
    
    .w-full {
        width: 100%;
    }
    
    .h-80 {
        height: 20rem;
    }
    
    .object-cover {
        object-fit: cover;
    }
    
    .overflow-hidden {
        overflow: hidden;
    }
    
    .transition-transform {
        transition-property: transform;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }
    
    .duration-300 {
        transition-duration: 300ms;
    }
    
    .group:hover .group-hover\:scale-105 {
        transform: scale(1.05);
    }
    
    .text-lg {
        font-size: 1.125rem;
        line-height: 1.75rem;
    }
    
    .text-xl {
        font-size: 1.25rem;
        line-height: 1.75rem;
    }
    
    .text-6xl {
        font-size: 3.75rem;
        line-height: 1;
    }
    
    .text-4xl {
        font-size: 2.25rem;
        line-height: 2.5rem;
    }
    
    .py-12 {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }
    
    .bg-gray-200 {
        background-color: var(--bg-tertiary);
    }
    
    .text-gray-300 {
        color: var(--text-muted);
    }
    
    .text-gray-400 {
        color: var(--text-muted);
    }
    
    .flex {
        display: flex;
    }
    
    .items-center {
        align-items: center;
    }
    
    .justify-center {
        justify-content: center;
    }
    
    .text-center {
        text-align: center;
    }
    
    .bg-primary-500 {
        background-color: var(--primary-500);
    }
    
    .text-danger-500 {
        color: var(--danger-500);
    }
    
    .gap-4 {
        gap: 1rem;
    }
    
    /* Dark theme adjustments */
    [data-theme="dark"] .bg-gray-200 {
        background-color: var(--bg-tertiary);
    }
    
    [data-theme="dark"] .text-gray-300 {
        color: var(--text-muted);
    }
    
    [data-theme="dark"] .text-gray-400 {
        color: var(--text-muted);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function showLoading(event, form) {
        event.preventDefault();
        
        Swal.fire({
            title: 'Gerando Banner',
            text: 'Por favor, aguarde enquanto criamos seu banner personalizado...',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        setTimeout(() => {
            form.submit();
        }, 1000);
    }
</script>

<?php include "includes/footer.php"; ?>