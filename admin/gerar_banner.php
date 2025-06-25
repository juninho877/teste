<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

// Incluir classes necessárias
require_once 'classes/UserImage.php';

// Configuração da API
$apiKey = 'ec8237f367023fbadd38ab6a1596b40c';
$language = 'pt-BR';

// Verificar se o nome foi fornecido
if (!isset($_GET['name']) || empty(trim($_GET['name']))) {
    echo "Nome do filme ou série não especificado.";
    exit;
}

try {
    $name = urlencode(trim($_GET['name']));
    $type = isset($_GET['type']) && $_GET['type'] == 'serie' ? 'tv' : 'movie';
    $tipoTexto = $type === 'tv' ? 'SÉRIE' : 'FILME';
    $year = isset($_GET['year']) && !empty($_GET['year']) ? $_GET['year'] : '';

    // URL de busca
    $searchUrl = "https://api.themoviedb.org/3/search/$type?api_key=$apiKey&language=$language&query=$name";
    if ($year) {
        $searchUrl .= ($type === 'tv' ? "&first_air_date_year=" : "&primary_release_year=") . $year;
    }

    // Fazer busca
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (compatible; FutBanner/1.0)'
        ]
    ]);
    
    $searchResponse = @file_get_contents($searchUrl, false, $context);
    if ($searchResponse === false) {
        throw new Exception("Erro ao conectar com a API do TMDB");
    }
    
    $searchData = json_decode($searchResponse, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erro ao decodificar resposta da API");
    }

    if (empty($searchData['results'])) {
        echo "Nenhum filme ou série encontrado com o nome '$name'.";
        exit;
    }

    $id = $searchData['results'][0]['id'];

    // Buscar detalhes do filme/série
    $mediaUrl = "https://api.themoviedb.org/3/$type/$id?api_key=$apiKey&language=$language";
    $elencoUrl = "https://api.themoviedb.org/3/$type/$id/credits?api_key=$apiKey&language=$language";

    $mediaResponse = @file_get_contents($mediaUrl, false, $context);
    $elencoResponse = @file_get_contents($elencoUrl, false, $context);
    
    if ($mediaResponse === false || $elencoResponse === false) {
        throw new Exception("Erro ao buscar detalhes do conteúdo");
    }

    $mediaData = json_decode($mediaResponse, true);
    $elencoData = json_decode($elencoResponse, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erro ao processar dados do conteúdo");
    }

    // Extrair informações
    $nome = isset($mediaData['title']) ? $mediaData['title'] : $mediaData['name'];
    $data = date('d/m/Y', strtotime($mediaData['release_date'] ?? $mediaData['first_air_date']));
    $categoria = implode(" • ", array_slice(array_column($mediaData['genres'], 'name'), 0, 3));
    $sinopse = $mediaData['overview'];
    $poster = "https://image.tmdb.org/t/p/w500" . $mediaData['poster_path'];
    $backdropUrl = "https://image.tmdb.org/t/p/w780" . $mediaData['backdrop_path'];
    $atores = array_slice($elencoData['cast'], 0, 5);

    // Limitar sinopse
    $maxSinopseLength = 300;
    if (strlen($sinopse) > $maxSinopseLength) {
        $sinopse = substr($sinopse, 0, $maxSinopseLength) . '...';
    }

    // Configurações da imagem
    $imageWidth = 980;
    $imageHeight = 1280;
    $image = imagecreatetruecolor($imageWidth, $imageHeight);

    // Carregar imagem de fundo
    $backgroundImage = @imagecreatefromjpeg($backdropUrl);
    if ($backgroundImage === false) {
        throw new Exception("Erro ao carregar imagem de fundo");
    }

    imagecopyresampled($image, $backgroundImage, 0, 0, 0, 0, $imageWidth, $imageHeight, imagesx($backgroundImage), imagesy($backgroundImage));

    // Aplicar overlay escuro
    $intensidadeBase = 70;
    $numPassos = 5;
    $deslocamento = 1;

    for ($i = 0; $i < $numPassos; $i++) {
        $alfa = min(127, $intensidadeBase + ($i * ((127 - $intensidadeBase) / ($numPassos - 1))));
        $corSombraDesfocada = imagecolorallocatealpha($image, 0, 0, 0, $alfa);
        imagefilledrectangle(
            $image,
            -$i * $deslocamento,
            -$i * $deslocamento,
            $imageWidth + $i * $deslocamento,
            $imageHeight + $i * $deslocamento,
            $corSombraDesfocada
        );
    }

    $sombraCentralAlfa = min(127, $intensidadeBase + 10);
    $corSombraCentral = imagecolorallocatealpha($image, 0, 0, 0, $sombraCentralAlfa);
    imagefilledrectangle($image, 0, 0, $imageWidth, $imageHeight, $corSombraCentral);

    // Cores
    $whiteColor = imagecolorallocate($image, 255, 255, 255);
    $yellowColor = imagecolorallocate($image, 255, 215, 0);
    $fontPath = __DIR__ . '/fonts/dejavu-sans-bold.ttf';
    $fontSize = 20;

    // Função para quebrar texto
    function wrapText($text, $font, $fontSize, $maxWidth) {
        $wrappedText = '';
        $words = explode(' ', $text);
        $line = '';
        foreach ($words as $word) {
            $testLine = $line . ' ' . $word;
            $testBox = imagettfbbox($fontSize, 0, $font, $testLine);
            $testWidth = $testBox[2] - $testBox[0];
            if ($testWidth <= $maxWidth) {
                $line = $testLine;
            } else {
                $wrappedText .= trim($line) . "\n";
                $line = $word;
            }
        }
        $wrappedText .= trim($line);
        return $wrappedText;
    }

    // Função para cantos arredondados
    function applyRoundedCorners($image, $radius = 20) {
        $width = imagesx($image);
        $height = imagesy($image);

        $rounded = imagecreatetruecolor($width, $height);
        imagesavealpha($rounded, true);
        $transparent = imagecolorallocatealpha($rounded, 0, 0, 0, 127);
        imagefill($rounded, 0, 0, $transparent);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $inCorner = false;
                if ($x < $radius && $y < $radius) {
                    $inCorner = (pow($x - $radius, 2) + pow($y - $radius, 2)) <= pow($radius, 2);
                } elseif ($x >= $width - $radius && $y < $radius) {
                    $inCorner = (pow($x - ($width - $radius - 1), 2) + pow($y - $radius, 2)) <= pow($radius, 2);
                } elseif ($x < $radius && $y >= $height - $radius) {
                    $inCorner = (pow($x - $radius, 2) + pow($y - ($height - $radius - 1), 2)) <= pow($radius, 2);
                } elseif ($x >= $width - $radius && $y >= $height - $radius) {
                    $inCorner = (pow($x - ($width - $radius - 1), 2) + pow($y - ($height - $radius - 1), 2)) <= pow($radius, 2);
                } else {
                    $inCorner = true;
                }

                if ($inCorner) {
                    $color = imagecolorat($image, $x, $y);
                    imagesetpixel($rounded, $x, $y, $color);
                }
            }
        }

        return $rounded;
    }

    // Carregar logo do usuário para banners de filmes/séries
    $userImage = new UserImage();
    $userId = $_SESSION['user_id'];
    $logoContent = $userImage->getImageContent($userId, 'logo_movie_banner');
    
    if ($logoContent !== false) {
        $icon = @imagecreatefromstring($logoContent);
        if ($icon !== false) {
            $iconWidth = 240;
            $iconHeight = 240;
            $iconX = 1000;
            $iconY = 600;
            imagecopyresampled($image, $icon, $iconX, $iconY, 0, 0, $iconWidth, $iconHeight, imagesx($icon), imagesy($icon));
            imagedestroy($icon);
        }
    }

    // Adicionar imagem de dispositivos
    $urlDispositivos = 'https://i.ibb.co/6csWNSxN/dispImg.png';
    $imgDispositivosResource = @imagecreatefrompng($urlDispositivos);
    if ($imgDispositivosResource !== false) {
        $imgDisX = 700;
        $imgDisY = 1130;
        $imgDisWidth = 285;
        $imgDisHeight = 205;
        imagecopyresampled(
            $image,
            $imgDispositivosResource,
            $imgDisX,
            $imgDisY,
            0,
            0,
            $imgDisWidth,
            $imgDisHeight,
            imagesx($imgDispositivosResource),
            imagesy($imgDispositivosResource)
        );
        imagedestroy($imgDispositivosResource);
    }

    // Adicionar texto "TIPO"
    $tipoFontSize = 38;
    $fundoVermelho = imagecolorallocate($image, 255, 0, 0);
    $margemVerticalTipo = 10;
    $margemHorizontalTipo = 20;
    $tipoBox = imagettfbbox($tipoFontSize, 0, $fontPath, $tipoTexto);
    $tipoLargura = abs($tipoBox[2] - $tipoBox[0]);
    $tipoAltura = abs($tipoBox[7] - $tipoBox[1]);
    $boxXTipo = 45;
    $boxYTipo = 155;
    $boxLarguraTipo = $tipoLargura + ($margemHorizontalTipo * 2);
    $boxAlturaTipo = $tipoAltura + ($margemVerticalTipo * 2);
    imagefilledrectangle($image, $boxXTipo, $boxYTipo, $boxXTipo + $boxLarguraTipo, $boxYTipo + $boxAlturaTipo, $fundoVermelho);
    $textoXTipo = $boxXTipo + $margemHorizontalTipo;
    $textoYTipo = $boxYTipo + $margemVerticalTipo + $tipoAltura;
    imagettftext($image, $tipoFontSize, 0, $textoXTipo, $textoYTipo, $whiteColor, $fontPath, $tipoTexto);

    // Adicionar texto "JÁ DISPONÍVEL"
    $textoFixo = "JÁ DISPONÍVEL";
    $fontSizeFixo = 38;
    $posicaoX = 55;
    $posicaoY = 120;
    $textBoxFixo = imagettfbbox($fontSizeFixo, 0, $fontPath, $textoFixo);
    $textoLargura = $textBoxFixo[2] - $textBoxFixo[0];
    $fundoAmarelo = imagecolorallocate($image, 255, 215, 0);
    $corPreta = imagecolorallocate($image, 0, 0, 0);
    $margemVertical = -6;
    $margemHorizontal = 10;
    imagefilledrectangle(
        $image,
        $posicaoX - $margemHorizontal,
        $posicaoY + $textBoxFixo[1] - $margemVertical,
        $posicaoX + $textoLargura + $margemHorizontal,
        $posicaoY + $textBoxFixo[7] + $margemVertical,
        $fundoAmarelo
    );
    imagettftext($image, $fontSizeFixo, 0, $posicaoX, $posicaoY, $corPreta, $fontPath, $textoFixo);

    // Adicionar título
    $tamanhoMaximoFonte = 33;
    $tamanhoMinimoFonte = 7;
    $larguraMaximaTitulo = $imageWidth - 460;
    $posicaoX_titulo = 445;
    $posicaoY_titulo = 245;
    $tamanhoFonteFinal = $tamanhoMaximoFonte;
    while ($tamanhoFonteFinal > $tamanhoMinimoFonte) {
        $textBox = imagettfbbox($tamanhoFonteFinal, 0, $fontPath, $nome);
        $larguraTexto = abs($textBox[2] - $textBox[0]);
        if ($larguraTexto <= $larguraMaximaTitulo) {
            break;
        }
        $tamanhoFonteFinal--;
    }
    imagettftext(
        $image,
        $tamanhoFonteFinal,
        0,
        $posicaoX_titulo,
        $posicaoY_titulo,
        $whiteColor,
        $fontPath,
        $nome
    );

    // Adicionar poster
    $posterY = 285;
    $posterImage = @imagecreatefromjpeg($poster);
    if ($posterImage !== false) {
        $posterWidth = 400;
        $posterHeight = 650;
        $posterX = 25;
        $posterImageRounded = applyRoundedCorners($posterImage, 20);
        imagecopyresampled($image, $posterImageRounded, $posterX, $posterY, 0, 0, $posterWidth, $posterHeight, imagesx($posterImageRounded), imagesy($posterImageRounded));
        imagedestroy($posterImage);
        imagedestroy($posterImageRounded);
    }

    // Adicionar categoria
    $tamanhoMaximoFonteCat = 25;
    $tamanhoMinimoFonteCat = 10;
    $larguraMaximaCategoria = $imageWidth - 600;
    $posicaoX_cat = 600;
    $posicaoY_cat = 330;
    $tamanhoFonteFinalCat = $tamanhoMaximoFonteCat;
    while ($tamanhoFonteFinalCat > $tamanhoMinimoFonteCat) {
        $textBoxCat = imagettfbbox($tamanhoFonteFinalCat, 0, $fontPath, $categoria);
        $larguraTextoCat = abs($textBoxCat[2] - $textBoxCat[0]);
        if ($larguraTextoCat <= $larguraMaximaCategoria) {
            break;
        }
        $tamanhoFonteFinalCat--;
    }
    imagettftext(
        $image,
        $tamanhoFonteFinalCat,
        0,
        $posicaoX_cat,
        $posicaoY_cat,
        $whiteColor,
        $fontPath,
        $categoria
    );

    // Adicionar avaliação
    $nota = $mediaData['vote_average'];
    $notaFormatada = number_format($nota, 1, ',', '');
    $estrelaCheia = '★';
    $estrelaVazia = '☆';
    $totalEstrelas = 10;
    $numEstrelasCheias = round($nota);
    $numEstrelasVazias = $totalEstrelas - $numEstrelasCheias;
    $textoEstrelas = '';
    for ($i = 0; $i < $numEstrelasCheias; $i++) {
        $textoEstrelas .= $estrelaCheia;
    }
    for ($i = 0; $i < $numEstrelasVazias; $i++) {
        $textoEstrelas .= $estrelaVazia;
    }
    $posicaoX_estrelas = 450;
    $posicaoY_estrelas = 285;
    $fontSizeEstrelas = 28;
    $fontSizeNota = 24;
    imagettftext(
        $image,
        $fontSizeEstrelas,
        0,
        $posicaoX_estrelas,
        $posicaoY_estrelas,
        $yellowColor,
        $fontPath,
        $textoEstrelas
    );
    $boxEstrelas = imagettfbbox($fontSizeEstrelas, 0, $fontPath, $textoEstrelas);
    $larguraEstrelas = $boxEstrelas[2] - $boxEstrelas[0];
    $posicaoX_nota = $posicaoX_estrelas + $larguraEstrelas + 15;
    imagettftext(
        $image,
        $fontSizeNota,
        0,
        $posicaoX_nota,
        $posicaoY_estrelas,
        $whiteColor,
        $fontPath,
        $notaFormatada
    );

    // Adicionar sinopse
    $maxWidth = $imageWidth - 460;
    $wrappedSinopse = wrapText($sinopse, $fontPath, $fontSize, $maxWidth);
    imagettftext($image, $fontSize, 0, 445, 390, $whiteColor, $fontPath, $wrappedSinopse);

    // Salvar imagem
    $relativePath = 'img/' . $id . '_media.png';
    $outputPath = __DIR__ . '/' . $relativePath;
    
    // Criar diretório se não existir
    $imgDir = dirname($outputPath);
    if (!is_dir($imgDir)) {
        mkdir($imgDir, 0755, true);
    }
    
    if (!imagepng($image, $outputPath)) {
        throw new Exception("Erro ao salvar a imagem");
    }

    // Limpar memória
    imagedestroy($image);
    imagedestroy($backgroundImage);

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Banner Gerado</title>
    <style>
        body { 
            background: #111; 
            color: #fff; 
            text-align: center; 
            font-family: Arial, sans-serif; 
            padding: 40px; 
        }
        .container { 
            max-width: 800px; 
            margin: auto; 
        }
        img { 
            max-width: 100%; 
            height: auto; 
            margin-bottom: 20px; 
            border: 4px solid #00bcd4; 
        }
        a.button, button.button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            background-color: #00bcd4;
            color: #fff;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        a.button:hover, button.button:hover {
            background-color: #0097a7;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Banner Gerado com Sucesso!</h1>
        <img src="<?= $relativePath ?>" alt="Banner do Filme/Série">
        <br>
        <?php
            $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $baseURL = $protocolo . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
            $urlCompleta = rtrim($baseURL, '/') . '/' . $relativePath;
        ?>
        <a class="button" href="<?= $relativePath ?>" target="_blank">Ver Imagem</a>
        <a class="button" href="<?= $relativePath ?>" download>Baixar Imagem</a>
        <button class="button" onclick="navigator.clipboard.writeText('<?= $urlCompleta ?>').then(() => alert('URL copiada!'));">Copiar URL</button>
        <a class="button" href="painel.php">Nova Busca</a>
    </div>
</body>
</html>