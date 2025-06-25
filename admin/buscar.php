<?php
// Sua lógica PHP original, 100% preservada.
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}
$apiKey = 'ec8237f367023fbadd38ab6a1596b40c';
$language = 'pt-BR';

$pageTitle = "Resultados da Busca";
// Inclui o cabeçalho do nosso novo template
include "includes/header.php";
?>

<style>
    .results-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 25px;
    }
    .result-card {
        background: var(--card-bg);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        text-align: center;
        transition: all 0.3s ease;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .result-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        border-color: var(--accent-color);
    }
    .poster-container {
        width: 100%;
        height: 300px;
        background-color: #1a1a2d;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .poster-container img { width: 100%; height: 100%; object-fit: cover; }
    .poster-container .no-poster-icon { font-size: 4rem; color: var(--text-muted); }

    .info-container {
        padding: 15px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .info-container .text-content {
        flex-grow: 1;
    }
    .info-container h3 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 5px;
    }
    .info-container small { font-size: 0.85rem; color: var(--text-muted); }
    .info-container form { margin-top: 15px; }

    .generate-btn {
        width: 100%; padding: 10px; font-size: 0.9rem; font-weight: bold;
        color: #fff; background-color: var(--accent-color);
        border: none; border-radius: 8px; cursor: pointer;
        transition: background-color 0.3s;
    }
    .generate-btn:hover { background-color: #3a5bbf; }
    .no-results { text-align: center; padding: 40px; color: var(--text-muted); }
</style>

<?php
// O `if` para verificar se a busca foi feita.
if (isset($_GET['query']) && !empty($_GET['query'])) {
    $query = urlencode($_GET['query']);
    // Lógica para determinar o tipo, exatamente como no seu original.
    $type = isset($_GET['type']) && $_GET['type'] == 'serie' ? 'serie' : 'filme'; // 'filme' como padrão
    $ano = isset($_GET['ano_lancamento']) ? intval($_GET['ano_lancamento']) : null;
    
    // Montagem da URL, como no seu original.
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
    
    // Exibição do cabeçalho da página
    echo '<div class="page-header">';
    echo '<h1>Resultados para: "' . htmlspecialchars(urldecode($_GET['query'])) . '"</h1>';
    echo '</div>';

    // O container principal para o conteúdo
    echo '<div class="content-card">';
    if ($data && !empty($data['results'])) {
        echo "<div class='results-grid'>";

        // Loop para exibir os resultados, com a estrutura do seu form original
        foreach ($data['results'] as $item) {
            $id = $item['id'];
            $title = $item['title'] ?? $item['name'];
            $posterPath = $item['poster_path'] ? "https://image.tmdb.org/t/p/w500" . $item['poster_path'] : null;
            $releaseDate = $item['release_date'] ?? $item['first_air_date'] ?? '';
            $year = $releaseDate ? substr($releaseDate, 0, 4) : '';

            echo "<div class='result-card'>";
            echo "<div class='poster-container'>";
            if ($posterPath) {
                echo "<img src='{$posterPath}' alt='Poster de {$title}'>";
            } else {
                echo "<i class='fas fa-image no-poster-icon'></i>";
            }
            echo "</div>";

            echo "<div class='info-container'>";
            echo "<div class='text-content'>";
            echo "<h3>" . htmlspecialchars($title) . "</h3>";
            if ($releaseDate) {
                echo "<small>Lançamento: " . htmlspecialchars($releaseDate) . "</small>";
            }
            echo "</div>"; // Fim de .text-content
            
            // FORMULÁRIO EXATAMENTE COMO O SEU ORIGINAL
            echo "<form method='GET' action='gerar_banner.php' onsubmit='showLoading(event, this)'>";
            echo "<input type='hidden' name='name' value='" . htmlspecialchars($title, ENT_QUOTES) . "'>";
            echo "<input type='hidden' name='type' value='" . ($type == 'serie' ? 'serie' : 'filme') . "'>";
            echo "<input type='hidden' name='year' value='" . htmlspecialchars($year, ENT_QUOTES) . "'>";
            echo "<button type='submit' class='generate-btn'>Gerar Banner</button>";
            echo "</form>";
            
            echo "</div>"; // Fim de .info-container
            echo "</div>"; // Fim de .result-card
        }

        echo "</div>"; // Fim de .results-grid
    } else {
        echo "<div class='no-results'><i class='fas fa-search' style='font-size: 3rem; margin-bottom: 15px;'></i><p>Nenhum resultado encontrado.</p></div>";
    }
    echo '</div>'; // Fim de .content-card

} else {
    echo '<div class="content-card no-results"><p>Por favor, realize uma busca na página anterior.</p></div>';
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function showLoading(event, form) {
        event.preventDefault();
        Swal.fire({
            title: 'Aguarde...',
            text: 'Estamos gerando seu banner!',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            // Adicionando as cores do nosso tema ao SweetAlert
            background: '#2c2f4a', 
            color: '#f1f1f1',
            didOpen: () => {
                Swal.showLoading();
            }
        });
        setTimeout(() => {
            form.submit();
        }, 1000); // Pequeno atraso para mostrar o efeito, como no seu original.
    }
</script>

<?php 
// Inclui o rodapé do nosso novo template
include "includes/footer.php"; 
?>