<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

// Incluir classes necessárias
require_once 'classes/UserImage.php';

$apiKey = 'ec8237f367023fbadd38ab6a1596b40c';
$language = 'pt-BR';

if (!isset($_GET['name'])) {
    echo "Nome do filme ou série não especificado.";
    exit;
}

try {
    $name = urlencode($_GET['name']);
    $type = isset($_GET['type']) && $_GET['type'] == 'serie' ? 'tv' : 'movie';
    $tipoTexto = $type === 'tv' ? 'SÉRIE' : 'FILME';
    $year = isset($_GET['year']) ? $_GET['year'] : '';
    
    $searchUrl = "https://api.themoviedb.org/3/search/$type?api_key=$apiKey&language=$language&query=$name" . ($year ? "&year=$year" : '');
    
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

    $nome = isset($mediaData['title']) ? $mediaData['title'] : $mediaData['name'];
    $data = date('d/m/Y', strtotime($mediaData['release_date'] ?? $mediaData['first_air_date']));
    $categoria = implode(" • ", array_slice(array_column($mediaData['genres'], 'name'), 0, 3));
    $sinopse = $mediaData['overview'];
    $poster = "https://image.tmdb.org/t/p/w500" . $mediaData['poster_path'];
    $backdropUrl = "https://image.tmdb.org/t/p/w1280" . $mediaData['backdrop_path'];
    $atores = array_slice($elencoData['cast'], 0, 5);
    
    $maxSinopseLength = 300;
    if (strlen($sinopse) > $maxSinopseLength) {
        $sinopse = substr($sinopse, 0, $maxSinopseLength) . '...';
    }
    
    $imageWidth = 1280;
    $imageHeight = 853;
    $image = imagecreatetruecolor($imageWidth, $imageHeight);
    
    $backgroundImage = @imagecreatefromjpeg($backdropUrl);
    if ($backgroundImage === false) {
        throw new Exception("Erro ao carregar imagem de fundo");
    }
    
    imagecopyresampled($image, $backgroundImage, 0, 0, 0, 0, $imageWidth, $imageHeight, imagesx($backgroundImage), imagesy($backgroundImage));
    
    $intensidadeBase = 70;
    $numPassos = 5;  
    $deslocamento = 1; 
    for ($i = 0; $i < $numPassos; $i++) {
        $alfa = min(127, $intensidadeBase + ($i * ( (127 - $intensidadeBase) / ($numPassos - 1) )) );
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
    
    $whiteColor = imagecolorallocate($image, 255, 255, 255);
    $yellowColor = imagecolorallocate($image, 255, 215, 0);
    $fontPath = __DIR__ . '/fonts/dejavu-sans-bold.ttf';
    $fontSize = 20;
    
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

    $urlDispositivos = 'https://i.ibb.co/qLZQSbBp/Design-sem-nome-9.png';
    $imgDispositivosResource = @imagecreatefrompng($urlDispositivos);
    if ($imgDispositivosResource !== false) {
        $imgDisX = 445;
        $imgDisY = 685;
        $imgDisWidth = 416;
        $imgDisHeight = 96;
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
    
    $tipoFontSize = 24;
    $fundoVermelho = imagecolorallocate($image, 255, 0, 0); 
    $margemVerticalTipo = 10;
    $margemHorizontalTipo = 20;
    $tipoBox = imagettfbbox($tipoFontSize, 0, $fontPath, $tipoTexto);
    $tipoLargura = abs($tipoBox [2] - $tipoBox [0]);
    $tipoAltura = abs($tipoBox [7] - $tipoBox [1]);
    $boxXTipo = 445;
    $boxYTipo = 295;
    $boxLarguraTipo = $tipoLargura + ($margemHorizontalTipo * 2);
    $boxAlturaTipo = $tipoAltura + ($margemVerticalTipo * 2);
    imagefilledrectangle($image, $boxXTipo, $boxYTipo, $boxXTipo + $boxLarguraTipo, $boxYTipo + $boxAlturaTipo, $fundoVermelho);
    $textoXTipo = $boxXTipo + $margemHorizontalTipo;
    $textoYTipo = $boxYTipo + $margemVerticalTipo + $tipoAltura;
    imagettftext($image, $tipoFontSize, 0, $textoXTipo, $textoYTipo, $whiteColor, $fontPath, $tipoTexto);
    
    $textoFixo = ("JÁ DISPONÍVEL");
    $fontSizeFixo = 38;
    $posicaoX = 455;
    $posicaoY = 165;
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
    
    $tamanhoMaximoFonte = 33;
    $tamanhoMinimoFonte = 7;
    $larguraMaximaTitulo = $imageWidth - 460;
    $posicaoX_titulo = 445;
    $posterY = 80;
    $posicaoY_titulo = $posterY + 165;
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
    
    $posterImage = @imagecreatefromjpeg($poster);
    if ($posterImage !== false) {
        $posterWidth = 400;
        $posterHeight = 650;
        $posterX = 10;
        imagecopyresampled($image, $posterImage, $posterX, $posterY, 0, 0, $posterWidth, $posterHeight, imagesx($posterImage), imagesy($posterImage));
        imagedestroy($posterImage);
    }
    
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
    
    $maxWidth = $imageWidth - 460;
    $wrappedSinopse = wrapText($sinopse, $fontPath, $fontSize, $maxWidth);
    imagettftext($image, $fontSize, 0, 445, 390, $whiteColor, $fontPath, $wrappedSinopse); 
    
    imagettftext($image, 20, 0, 215, $imageHeight - 30, $whiteColor, $fontPath, "O MELHOR DO STREAMING VOCÊ SÓ ENCONTRA AQUI");
    imagettftext($image, 16, 0, 445, $imageHeight - 180, $whiteColor, $fontPath, "DISPONÍVEL EM DIVERSOS APARELHOS");
    
    // Gerar nome de arquivo temporário único
    $tempFileName = 'banner_' . uniqid() . '_' . time() . '.png';
    $tempFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tempFileName;
    
    // Salvar imagem no arquivo temporário
    if (!imagepng($image, $tempFilePath)) {
        throw new Exception("Erro ao salvar a imagem temporária");
    }
    
    // Limpar memória
    imagedestroy($image);
    imagedestroy($backgroundImage);
    
    // Definir cabeçalhos para servir a imagem
    $fileSize = filesize($tempFilePath);
    header('Content-Type: image/png');
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: inline; filename="banner_' . $nome . '.png"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Enviar o arquivo e deletar imediatamente
    if (readfile($tempFilePath)) {
        unlink($tempFilePath); // Deletar arquivo temporário
    }
    
    exit;

} catch (Exception $e) {
    // Em caso de erro, retornar uma imagem de erro
    header('Content-Type: image/png');
    $errorImage = imagecreatetruecolor(800, 200);
    $bgColor = imagecolorallocate($errorImage, 255, 255, 255);
    $textColor = imagecolorallocate($errorImage, 255, 0, 0);
    imagefill($errorImage, 0, 0, $bgColor);
    imagestring($errorImage, 5, 50, 90, "Erro: " . $e->getMessage(), $textColor);
    imagepng($errorImage);
    imagedestroy($errorImage);
    exit;
}
?>