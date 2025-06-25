<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

// Verificação de sessão primeiro
if (!isset($_SESSION["usuario"])) {
    http_response_code(403);
    header('Content-Type: image/png');
    $im = imagecreatetruecolor(600, 100);
    imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
    imagestring($im, 5, 10, 40, "Erro: Acesso Negado.", imagecolorallocate($im, 0, 0, 0));
    imagepng($im);
    imagedestroy($im);
    exit();
}

// Configurações otimizadas
define('CLOUDINARY_CLOUD_NAME', 'dwrikepvg');
define('LOGO_OVERRIDES', [
    'St. Louis City' => 'https://a.espncdn.com/combiner/i?img=/i/teamlogos/soccer/500/21812.png',
    'Guarulhos' => 'https://upload.wikimedia.org/wikipedia/pt/d/d5/GuarulhosGRU.png',
    'Estados Unidos' => 'https://a.espncdn.com/combiner/i?img=/i/teamlogos/countries/500/usa.png',
    'Tupa' => 'https://static.flashscore.com/res/image/data/8SqNKfdM-27lsDqoa.png',
    'Guadeloupe' => 'https://static.flashscore.com/res/image/data/z7uwX5e5-Qw31eZbP.png',
    'Tanabi' => 'https://ssl.gstatic.com/onebox/media/sports/logos/_0PCb1YBKcxp8eXBCCtZpg_96x96.png',
    'Mundial de Clubes FIFA' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ad/2025_FIFA_Club_World_Cup.svg/1200px-2025_FIFA_Club_World_Cup.svg.png',
]);

// Cache simples
$imageCache = [];

function desenhar_retangulo_arredondado($image, $x, $y, $width, $height, $radius, $color) {
    $x1 = $x; $y1 = $y; $x2 = $x + $width; $y2 = $y + $height;
    if ($radius > $width / 2) $radius = $width / 2;
    if ($radius > $height / 2) $radius = $height / 2;

    imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledarc($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color, IMG_ARC_PIE);
    imagefilledarc($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color, IMG_ARC_PIE);
    imagefilledarc($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color, IMG_ARC_PIE);
    imagefilledarc($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $color, IMG_ARC_PIE);
}

function carregarImagemDeUrl(string $url, int $maxSize) {
    global $imageCache;
    
    $cacheKey = md5($url . $maxSize);
    if (isset($imageCache[$cacheKey])) {
        return $imageCache[$cacheKey];
    }

    $urlParaCarregar = $url;
    $extensao = strtolower(pathinfo($url, PATHINFO_EXTENSION));

    if ($extensao === 'svg') {
        $cloudinaryCloudName = CLOUDINARY_CLOUD_NAME;
        if (empty($cloudinaryCloudName)) return $imageCache[$cacheKey] = false;
        $urlParaCarregar = "https://res.cloudinary.com/{$cloudinaryCloudName}/image/fetch/f_png/" . urlencode($url);
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $urlParaCarregar,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; FutBanner/1.0)'
    ]);
    
    $imageContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($imageContent === false || $httpCode >= 400) return $imageCache[$cacheKey] = false;
    
    $img = @imagecreatefromstring($imageContent);
    if (!$img) return $imageCache[$cacheKey] = false;

    $w = imagesx($img); $h = imagesy($img);
    if ($w == 0 || $h == 0) { 
        imagedestroy($img); 
        return $imageCache[$cacheKey] = false; 
    }
    
    $scale = min($maxSize / $w, $maxSize / $h, 1.0);
    $newW = (int)($w * $scale); $newH = (int)($h * $scale);
    $imgResized = imagecreatetruecolor($newW, $newH);
    imagealphablending($imgResized, false); imagesavealpha($imgResized, true);
    imagecopyresampled($imgResized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
    imagedestroy($img);
    
    return $imageCache[$cacheKey] = $imgResized;
}

function carregarLogoCanalComAlturaFixa(string $url, int $alturaFixa = 50) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; FutBanner/1.0)'
    ]);
    
    $imageContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($imageContent === false || $httpCode >= 400) return false;
    
    $img = @imagecreatefromstring($imageContent);
    if (!$img) return false;
    
    $origW = imagesx($img); $origH = imagesy($img);
    if ($origH == 0) { imagedestroy($img); return false; }
    
    $ratio = $origW / $origH;
    $newW = (int)($alturaFixa * $ratio);
    $newH = $alturaFixa;
    $imgResized = imagecreatetruecolor($newW, $newH);
    imagealphablending($imgResized, false); imagesavealpha($imgResized, true);
    $transparent = imagecolorallocatealpha($imgResized, 0, 0, 0, 127);
    imagefill($imgResized, 0, 0, $transparent);
    imagecopyresampled($imgResized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    imagedestroy($img);
    return $imgResized;
}

function criarPlaceholderComNome(string $nomeTime, int $size = 68) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false); imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    $textColor = imagecolorallocate($img, 80, 80, 80);
    $fontePath = __DIR__ . '/fonts/RobotoCondensed-Bold.ttf';
    
    if (!file_exists($fontePath)) { 
        imagestring($img, 2, 2, $size/2 - 5, "No Logo", $textColor); 
        return $img; 
    }
    
    $nomeLimpo = trim(preg_replace('/\s*\([^)]*\)/', '', $nomeTime));
    $palavras = explode(' ', $nomeLimpo);
    $linhas = []; $linhaAtual = '';
    
    foreach ($palavras as $palavra) {
        $testeLinha = $linhaAtual . ($linhaAtual ? ' ' : '') . $palavra;
        $bbox = imagettfbbox(10.5, 0, $fontePath, $testeLinha);
        if (($bbox[2] - $bbox[0]) > ($size - 8) && $linhaAtual !== '') { 
            $linhas[] = $linhaAtual; $linhaAtual = $palavra; 
        } else { 
            $linhaAtual = $testeLinha; 
        }
    }
    $linhas[] = $linhaAtual;
    
    $bbox = imagettfbbox(10.5, 0, $fontePath, "A");
    $alturaLinha = abs($bbox[7] - $bbox[1]);
    $alturaTotalTexto = (count($linhas) * $alturaLinha) + ((count($linhas) - 1) * 2);
    $y = ($size - $alturaTotalTexto) / 2 + $alturaLinha;
    
    foreach ($linhas as $linha) {
        $bboxLinha = imagettfbbox(10.5, 0, $fontePath, $linha);
        $x = ($size - ($bboxLinha[2] - $bboxLinha[0])) / 2;
        imagettftext($img, 10.5, 0, (int)$x, (int)$y, $textColor, $fontePath, $linha);
        $y += $alturaLinha + 2;
    }
    return $img;
}

function carregarEscudo(string $nomeTime, ?string $url, int $maxSize = 60) {
    if (!empty($url)) {
        $imagem = carregarImagemDeUrl($url, $maxSize);
        if ($imagem) return $imagem;
    }
    return criarPlaceholderComNome($nomeTime, $maxSize);
}

function getChaveRemota() {
    $url = base64_decode('aHR0cHM6Ly9hcGlmdXQucHJvamVjdHguY2xpY2svQXV0b0FwaS9BRVMvY29uZmlna2V5LnBocA==');
    $auth = base64_decode('dmFxdW9UQlpFb0U4QmhHMg==');
    $postData = json_encode(['auth' => $auth]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($postData)]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    
    $response = curl_exec($ch);
    curl_close($ch);
    return $response ? json_decode($response, true)['chave'] ?? null : null;
}

function descriptografarURL($urlCodificada, $chave) {
    $parts = explode('::', base64_decode($urlCodificada), 2);
    if (count($parts) < 2) return null;
    list($url_criptografada, $iv) = $parts;
    return openssl_decrypt($url_criptografada, 'aes-256-cbc', $chave, 0, $iv);
}

function desenharTexto($im, $texto, $x, $y, $cor, $tamanho=12, $angulo=0, $fonteCustom = null) {
    $fontPath = __DIR__ . '/fonts/CalSans-Regular.ttf';
    $fonteUsada = $fonteCustom ?? $fontPath;
    if (file_exists($fonteUsada)) {
        $bbox = imagettfbbox($tamanho, $angulo, $fonteUsada, $texto);
        $alturaTexto = abs($bbox[7] - $bbox[1]);
        imagettftext($im, $tamanho, $angulo, $x, $y + $alturaTexto, $cor, $fonteUsada, $texto);
    } else {
        imagestring($im, 5, $x, $y, $texto, $cor);
    }
}

function getImageFromJson($jsonPath) {
    static $cache = [];
    if (isset($cache[$jsonPath])) return $cache[$jsonPath];
    
    $jsonContent = @file_get_contents($jsonPath);
    if ($jsonContent === false) return $cache[$jsonPath] = null;
    
    $data = json_decode($jsonContent, true);
    if (empty($data) || !isset($data[0]['ImageName'])) return $cache[$jsonPath] = null;
    
    $imagePath = str_replace('../', '', $data[0]['ImageName']);
    if (!file_exists($imagePath)) return $cache[$jsonPath] = null;
    
    return $cache[$jsonPath] = @file_get_contents($imagePath);
}

function centralizarTextoX($larguraImagem, $tamanhoFonte, $fonte, $texto) { 
    if (!file_exists($fonte)) return $larguraImagem / 2;
    $caixa = imagettfbbox($tamanhoFonte, 0, $fonte, $texto); 
    return ($larguraImagem - ($caixa[2] - $caixa[0])) / 2; 
}

function gerarBanner($im, $jogos, $grupoJogos, $padding, $heightPorJogo, $width, $preto, $branco, $fontLiga) {
    static $fundoJogo = null;
    static $logoLiga = null;
    
    if ($fundoJogo === null) {
        $fundoJogoPath = __DIR__ . '/fzstore/card/card_banner_3.png';
        $fundoJogo = file_exists($fundoJogoPath) ? imagecreatefrompng($fundoJogoPath) : false;
    }
    
    $yAtual = $padding + 480;

    foreach ($grupoJogos as $idx) {
        if (!isset($jogos[$idx])) continue;

        $jogo = $jogos[$idx];
        if ($fundoJogo) {
            imagecopyresampled($im, $fundoJogo, 15, $yAtual, 0, 0, $width - ($padding * 2), $heightPorJogo - 8, imagesx($fundoJogo), imagesy($fundoJogo));
        }

        $time1 = $jogo['time1'] ?? 'Time 1';
        $time2 = $jogo['time2'] ?? 'Time 2';
        $liga = $jogo['competicao'] ?? 'Liga';
        $hora = $jogo['horario'] ?? '';
        
        $escudo1_url = LOGO_OVERRIDES[$time1] ?? ($jogo['img_time1_url'] ?? '');
        $escudo2_url = LOGO_OVERRIDES[$time2] ?? ($jogo['img_time2_url'] ?? '');
        
        $imgEscudo1 = carregarEscudo($time1, $escudo1_url, 180);
        $imgEscudo2 = carregarEscudo($time2, $escudo2_url, 180);
        
        $yTop = $yAtual + 20;
        if($imgEscudo1) imagecopy($im, $imgEscudo1, 165, $yTop + 15, 0, 0, imagesx($imgEscudo1), imagesy($imgEscudo1));
        if($imgEscudo2) imagecopy($im, $imgEscudo2, 1130, $yTop + 15, 0, 0, imagesx($imgEscudo2), imagesy($imgEscudo2));
        
        // Limpar memória apenas se não estiver no cache
        if($imgEscudo1 && !isset($GLOBALS['imageCache'][md5($escudo1_url . 180)])) imagedestroy($imgEscudo1);
        if($imgEscudo2 && !isset($GLOBALS['imageCache'][md5($escudo2_url . 180)])) imagedestroy($imgEscudo2);
        
        $fonteNomes = __DIR__ . '/fonts/CalSans-Regular.ttf';
        $tamanhoNomes = 25; $corNomes = $preto;
        $tamanhoHora = 50;
        $textoLinha1 = mb_strtoupper($time1);
        $textoLinha2 = mb_strtoupper($time2);

        $eixoCentralColuna = 500;
        $eixoCentralColuna2 = 940;
        $eixoCentralColuna3 = 720;
        
        $bbox1 = imagettfbbox($tamanhoNomes, 0, $fonteNomes, $textoLinha1);
        $xPos1 = $eixoCentralColuna - (($bbox1[2] - $bbox1[0]) / 2);
        $bbox2 = imagettfbbox($tamanhoNomes, 0, $fonteNomes, $textoLinha2);
        $xPos2 = $eixoCentralColuna2 - (($bbox2[2] - $bbox2[0]) / 2);
        $bbox3 = imagettfbbox($tamanhoHora, 0, $fonteNomes, $hora);
        $xPos3 = $eixoCentralColuna3 - (($bbox3[2] - $bbox3[0]) / 2);
        
        desenharTexto($im, $textoLinha1, $xPos1, $yTop + 85, $corNomes, $tamanhoNomes);
        desenharTexto($im, $textoLinha2, $xPos2, $yTop + 85, $corNomes, $tamanhoNomes);
        desenharTexto($im, $hora, $xPos3, $yTop - 10, $branco, $tamanhoHora);
        
        $canaisDoJogo = $jogo['canais'];
        if (!empty($canaisDoJogo)) {
            $logosParaDesenhar = [];
            $larguraTotalBloco = 0; $espacoEntreLogos = 5;
            
            foreach ($canaisDoJogo as $canal) {
                if (!empty($canal['img_url'])) {
                    $logoCanal = carregarLogoCanalComAlturaFixa($canal['img_url'], 55);
                    if ($logoCanal) {
                        $logosParaDesenhar[] = $logoCanal;
                        $larguraTotalBloco += imagesx($logoCanal);
                    }
                }
            }
            
            if (!empty($logosParaDesenhar)) {
                $larguraTotalBloco += (count($logosParaDesenhar) - 1) * $espacoEntreLogos;
                $xAtual = (($width - $larguraTotalBloco) / 2);
                foreach ($logosParaDesenhar as $logo) {
                    imagecopy($im, $logo, (int)$xAtual, (int)($yTop + 145), 0, 0, imagesx($logo), imagesy($logo));
                    $xAtual += imagesx($logo) + $espacoEntreLogos;
                    imagedestroy($logo);
                }
            }
        }
        $yAtual += $heightPorJogo;
    }

    $fonteTitulo = __DIR__ . '/fonts/BebasNeue-Regular.ttf';
    $fonteData = __DIR__ . '/fonts/RobotoCondensed-VariableFont_wght.ttf';
    $corBranco = imagecolorallocate($im, 255, 255, 255);
    $titulo1 = "DESTAQUES DE HOJE";
    
    setlocale(LC_TIME, 'pt_BR.utf8', 'pt_BR.UTF-8', 'pt_BR', 'portuguese');
    $dataTexto = mb_strtoupper(strftime('%A - %d de %B'));
    imagettftext($im, 82, 0, centralizarTextoX($width, 82, $fonteTitulo, $titulo1), 120, $corBranco, $fonteTitulo, $titulo1);
    
    $corBranco2 = imagecolorallocate($im, 255, 255, 255);
    $corTexto = imagecolorallocate($im, 0, 0, 0);
    $retanguloLargura = 1135;
    $retanguloAltura = 130;
    $cantoRaio = 15; 
    $retanguloX = ($width - $retanguloLargura) / 2;
    $retanguloY = 348; 
    
    desenhar_retangulo_arredondado($im, $retanguloX, $retanguloY, $retanguloLargura, $retanguloAltura, $cantoRaio, $corBranco2);
    
    $fontPath2 = __DIR__ . '/fonts/CalSans-Regular.ttf';
    $tamanhoFonte = 78;
    $textoX = centralizarTextoX($width, $tamanhoFonte, $fontPath2, $dataTexto);
    $textoY_preciso = $retanguloY + 45;
    
    desenharTexto($im, $dataTexto, $textoX, $textoY_preciso, $corTexto, $tamanhoFonte);

    // Logo das ligas (cache)
    if ($logoLiga === null) {
        $ligas_url = 'https://i.ibb.co/W4nVKgd3/tlx.png';
        $logoLiga = @imagecreatefrompng($ligas_url);
    }
    
    if ($logoLiga) {
        imagecopy($im, $logoLiga, 0, 1740, 0, 0, imagesx($logoLiga), imagesy($logoLiga));
    }

    $logoContent = getImageFromJson('api/fzstore/logo_banner_3.json');
    if ($logoContent && ($logoOriginal = @imagecreatefromstring($logoContent))) {
        $w = imagesx($logoOriginal); $h = imagesy($logoOriginal);
        if ($w > 0 && $h > 0) {
            $scale = min(350 / $w, 350 / $h, 1.0);
            $newW = (int)($w * $scale); $newH = (int)($h * $scale);
            $logoRedimensionada = imagecreatetruecolor($newW, $newH);
            imagealphablending($logoRedimensionada, false); imagesavealpha($logoRedimensionada, true);
            imagecopyresampled($logoRedimensionada, $logoOriginal, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagecopy($im, $logoRedimensionada, 10, 5, 0, 0, $newW, $newH);
            imagedestroy($logoRedimensionada);
        }
        imagedestroy($logoOriginal);
    }
}

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
    
    if ($json_content) {
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

if (empty($jogos)) {
    header('Content-Type: image/png');
    $im = imagecreatetruecolor(600, 100);
    imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
    imagestring($im, 5, 10, 40, "Nenhum jogo disponivel.", imagecolorallocate($im, 0, 0, 0));
    imagepng($im);
    imagedestroy($im);
    exit;
}

$width = 1440;
$heightPorJogo = 240;
$padding = 15;
$espacoExtra = 649;
$fontLiga = __DIR__ . '/fonts/MANDATOR.ttf';
$jogosPorBanner = 5;
$gruposDeJogos = array_chunk(array_keys($jogos), $jogosPorBanner);

if (isset($_GET['download_all']) && $_GET['download_all'] == 1) {
    $zip = new ZipArchive();
    $zipNome = "banners_topplay_" . date('Y-m-d') . ".zip";
    $caminhoTempZip = sys_get_temp_dir() . '/' . uniqid('banners_') . '.zip';
    $tempFiles = [];

    if ($zip->open($caminhoTempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($gruposDeJogos as $index => $grupoJogos) {
            $numJogosNesteBanner = count($grupoJogos);
            $height = max($numJogosNesteBanner * $heightPorJogo + $padding * 2 + $espacoExtra, 2030);

            $im = imagecreatetruecolor($width, $height);
            $preto = imagecolorallocate($im, 0, 0, 0);
            $branco = imagecolorallocate($im, 255, 255, 255);
            
            $fundoContent = getImageFromJson('api/fzstore/background_banner_3.json');
            if ($fundoContent && ($fundo = @imagecreatefromstring($fundoContent))) {
                imagecopyresampled($im, $fundo, 0, 0, 0, 0, $width, $height, imagesx($fundo), imagesy($fundo));
                imagedestroy($fundo);
            } else {
                imagefill($im, 0, 0, $branco);
            }

            gerarBanner($im, $jogos, $grupoJogos, $padding, $heightPorJogo, $width, $preto, $branco, $fontLiga);
            
            $nomeArquivoTemp = sys_get_temp_dir() . '/banner_topplay_' . uniqid() . '.png';
            imagepng($im, $nomeArquivoTemp);
            
            $zip->addFile($nomeArquivoTemp, 'banner_topplay_' . ($index + 1) . '.png');
            $tempFiles[] = $nomeArquivoTemp;
            imagedestroy($im);
        }
        $zip->close();

        if (ob_get_level()) ob_end_clean();
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipNome . '"');
        header('Content-Length: ' . filesize($caminhoTempZip));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        if(readfile($caminhoTempZip)) {
            foreach ($tempFiles as $file) {
                if (file_exists($file)) unlink($file);
            }
            unlink($caminhoTempZip);
        }
        exit;
    } else {
        die("Erro: Não foi possível criar o arquivo ZIP.");
    }
} else {
    $grupoIndex = isset($_GET['grupo']) ? (int)$_GET['grupo'] : 0;
    if (!isset($gruposDeJogos[$grupoIndex])) {
        header('Content-Type: image/png');
        $im = imagecreatetruecolor(600, 100);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        imagestring($im, 5, 10, 40, "Banner invalido.", imagecolorallocate($im, 0, 0, 0));
        imagepng($im);
        imagedestroy($im);
        exit;
    }

    $grupoJogos = $gruposDeJogos[$grupoIndex];
    $numJogosNesteBanner = count($grupoJogos);
    $height = max($numJogosNesteBanner * $heightPorJogo + $padding * 2 + $espacoExtra, 2030);

    $im = imagecreatetruecolor($width, $height);
    $preto = imagecolorallocate($im, 0, 0, 0);
    $branco = imagecolorallocate($im, 255, 255, 255);
    
    $fundoContent = getImageFromJson('api/fzstore/background_banner_3.json');
    if ($fundoContent && ($fundo = @imagecreatefromstring($fundoContent))) {
        imagecopyresampled($im, $fundo, 0, 0, 0, 0, $width, $height, imagesx($fundo), imagesy($fundo));
        imagedestroy($fundo);
    } else {
        imagefill($im, 0, 0, $branco);
    }

    gerarBanner($im, $jogos, $grupoJogos, $padding, $heightPorJogo, $width, $preto, $branco, $fontLiga);

    if (isset($_GET['download']) && $_GET['download'] == 1) {
        $nomeArquivo = "banner_topplay_" . date('Y-m-d') . "_parte" . ($grupoIndex + 1) . ".png";
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
    }

    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=300');
    imagepng($im);
    imagedestroy($im);
    exit;
}
?>