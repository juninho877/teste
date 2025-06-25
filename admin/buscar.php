<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$apiKey = 'ec8237f367023fbadd38ab6a1596b40c';
$language = 'pt-BR';

$pageTitle = "Resultados da Busca";
include "includes/header.php";

if (isset($_GET['query']) && !empty($_GET['query'])) {
    $query = urlencode($_GET['query']);
    $type = isset($_GET['type']) && $_GET['type'] == 'serie' ? 'serie' : 'filme';
    $ano = isset($_GET['ano_lancamento']) ? intval($_GET['ano_lancamento']) : null;
    
    if ($type == 'serie') {
        $api_type = 'tv';
        $url = "https://api.themoviedb.org/3/search/tv?api_key=$apiKey&language=$language&query=$query";
        if ($ano) { $url .= "&first_air_date_year=$ano"; }
    } else {
        $api_type = 'movie';
        $url = "https://api.themoviedb.org/3/search/movie?api_key=$apiKey&language=$language&query=$query";
        if ($ano) { $url .= "&primary_release_year=$ano"; }
    }

    @$response = file_get_contents($url);
    $data = $response ? json_decode($response, true) : null;
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

<?php if ($data && !empty($data['results'])): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($data['results'] as $item): 
            $id = $item['id'];
            $title = $item['title'] ?? $item['name'];
            $posterPath = $item['poster_path'] ? "https://image.tmdb.org/t/p/w500" . $item['poster_path'] : null;
            $releaseDate = $item['release_date'] ?? $item['first_air_date'] ?? '';
            $year = $releaseDate ? substr($releaseDate, 0, 4) : '';
            $overview = $item['overview'] ?? '';
            $rating = $item['vote_average'] ?? 0;
        ?>
            <div class="card group hover:shadow-xl transition-all duration-300">
                <div class="relative overflow-hidden">
                    <?php if ($posterPath): ?>
                        <img src="<?php echo $posterPath; ?>" alt="Poster de <?php echo htmlspecialchars($title); ?>" 
                             class="w-full h-80 object-cover group-hover:scale-105 transition-transform duration-300">
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

<?php else: ?>
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
<?php endif; ?>

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