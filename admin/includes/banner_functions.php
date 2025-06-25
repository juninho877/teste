<?php
// Funções compartilhadas para geração de banners

// Configurações globais
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

// Cache global para imagens
$GLOBALS['imageCache'] = [];

function logDebug($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[BANNER_DEBUG] {$timestamp}: {$message}");
}

function carregarImagemDeUrl(string $url, int $maxSize) {
    global $imageCache;
    
    $cacheKey = md5($url . $maxSize);
    if (isset($imageCache[$cacheKey])) {
        logDebug("Cache HIT para: $url");
        return $imageCache[$cacheKey];
    }

    logDebug("Carregando imagem: $url (max: {$maxSize}px)");

    $urlParaCarregar = $url;
    $extensao = strtolower(pathinfo($url, PATHINFO_EXTENSION));

    if ($extensao === 'svg') {
        $cloudinaryCloudName = CLOUDINARY_CLOUD_NAME;
        if (empty($cloudinaryCloudName)) {
            logDebug("ERRO: Cloudinary não configurado para SVG");
            return $imageCache[$cacheKey] = false;
        }
        $urlParaCarregar = "https://res.cloudinary.com/{$cloudinaryCloudName}/image/fetch/f_png/" . urlencode($url);
        logDebug("SVG convertido via Cloudinary: $urlParaCarregar");
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $urlParaCarregar,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_ENCODING => '',
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_FORBID_REUSE => true,
        CURLOPT_HTTPHEADER => [
            'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Connection: close'
        ],
        CURLOPT_LOW_SPEED_LIMIT => 1024,
        CURLOPT_LOW_SPEED_TIME => 30,
    ]);
    
    $startTime = microtime(true);
    $imageContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $loadTime = round(microtime(true) - $startTime, 2);

    if ($imageContent === false || $httpCode >= 400 || !empty($error)) {
        logDebug("ERRO CARREGAMENTO: URL=$url | HTTP=$httpCode | Error=$error | Time={$loadTime}s");
        return $imageCache[$cacheKey] = false;
    }
    
    if (empty($imageContent)) {
        logDebug("ERRO: Conteúdo vazio para $url");
        return $imageCache[$cacheKey] = false;
    }

    logDebug("Download OK: " . strlen($imageContent) . " bytes em {$loadTime}s");
    
    $img = @imagecreatefromstring($imageContent);
    if (!$img) {
        logDebug("ERRO: Não foi possível criar imagem de " . strlen($imageContent) . " bytes");
        return $imageCache[$cacheKey] = false;
    }

    $w = imagesx($img); $h = imagesy($img);
    if ($w == 0 || $h == 0) {
        imagedestroy($img);
        logDebug("ERRO: Dimensões inválidas W=$w H=$h");
        return $imageCache[$cacheKey] = false;
    }
    
    $scale = min($maxSize / $w, $maxSize / $h, 1.0);
    $newW = (int)($w * $scale); $newH = (int)($h * $scale);
    
    $imgResized = imagecreatetruecolor($newW, $newH);
    imagealphablending($imgResized, false); 
    imagesavealpha($imgResized, true);
    $transparent = imagecolorallocatealpha($imgResized, 0, 0, 0, 127);
    imagefill($imgResized, 0, 0, $transparent);
    
    imagecopyresampled($imgResized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
    imagedestroy($img);
    
    logDebug("SUCESSO: {$newW}x{$newH} em {$loadTime}s");
    return $imageCache[$cacheKey] = $imgResized;
}

function carregarLogoCanalComAlturaFixa(string $url, int $alturaFixa = 50) {
    logDebug("Carregando logo canal: $url");
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_ENCODING => '',
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_FORBID_REUSE => true,
        CURLOPT_HTTPHEADER => [
            'Connection: close'
        ],
    ]);
    
    $imageContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($imageContent === false || $httpCode >= 400 || !empty($error)) {
        logDebug("ERRO logo canal: HTTP=$httpCode | Error=$error");
        return false;
    }
    
    $img = @imagecreatefromstring($imageContent);
    if (!$img) {
        logDebug("ERRO: Não foi possível criar imagem do canal");
        return false;
    }
    
    $origW = imagesx($img); $origH = imagesy($img);
    if ($origH == 0) { 
        imagedestroy($img); 
        logDebug("ERRO: Altura zero na imagem do canal");
        return false; 
    }
    
    $ratio = $origW / $origH;
    $newW = (int)($alturaFixa * $ratio);
    $newH = $alturaFixa;
    
    $imgResized = imagecreatetruecolor($newW, $newH);
    imagealphablending($imgResized, false); 
    imagesavealpha($imgResized, true);
    $transparent = imagecolorallocatealpha($imgResized, 0, 0, 0, 127);
    imagefill($imgResized, 0, 0, $transparent);
    
    imagecopyresampled($imgResized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    imagedestroy($img);
    
    logDebug("Logo canal OK: {$newW}x{$newH}");
    return $imgResized;
}

function criarPlaceholderComNome(string $nomeTime, int $size = 68) {
    logDebug("Criando placeholder para: $nomeTime (size: $size)");
    
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false); 
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    $textColor = imagecolorallocate($img, 80, 80, 80);
    $fontePath = __DIR__ . '/../fonts/RobotoCondensed-Bold.ttf';
    
    if (!file_exists($fontePath)) { 
        logDebug("AVISO: Fonte não encontrada, usando fonte padrão");
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
            $linhas[] = $linhaAtual; 
            $linhaAtual = $palavra; 
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
    
    logDebug("Placeholder criado: {$size}x{$size}");
    return $img;
}

function carregarEscudo(string $nomeTime, ?string $url, int $maxSize = 60) {
    if (!empty($url)) {
        $imagem = carregarImagemDeUrl($url, $maxSize);
        if ($imagem) {
            logDebug("Escudo carregado via URL: $nomeTime");
            return $imagem;
        }
        logDebug("Falha ao carregar escudo via URL: $nomeTime");
    }
    
    logDebug("Usando placeholder para: $nomeTime");
    return criarPlaceholderComNome($nomeTime, $maxSize);
}

function getChaveRemota() {
    logDebug("Obtendo chave remota...");
    
    $url = base64_decode('aHR0cHM6Ly9hcGlmdXQucHJvamVjdHguY2xpY2svQXV0b0FwaS9BRVMvY29uZmlna2V5LnBocA==');
    $auth = base64_decode('dmFxdW9UQlpFb0U4QmhHMg==');
    $postData = json_encode(['auth' => $auth]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($postData)]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response && $httpCode == 200) {
        $data = json_decode($response, true);
        $chave = $data['chave'] ?? null;
        logDebug($chave ? "Chave remota obtida com sucesso" : "Chave não encontrada na resposta");
        return $chave;
    }
    
    logDebug("ERRO ao obter chave remota: HTTP=$httpCode");
    return null;
}

function descriptografarURL($urlCodificada, $chave) {
    $parts = explode('::', base64_decode($urlCodificada), 2);
    if (count($parts) < 2) {
        logDebug("ERRO: Formato de URL criptografada inválido");
        return null;
    }
    list($url_criptografada, $iv) = $parts;
    $url = openssl_decrypt($url_criptografada, 'aes-256-cbc', $chave, 0, $iv);
    logDebug($url ? "URL descriptografada com sucesso" : "ERRO ao descriptografar URL");
    return $url;
}

function desenharTexto($im, $texto, $x, $y, $cor, $tamanho=12, $angulo=0, $fonteCustom = null) {
    $fontPath = __DIR__ . '/../fonts/CalSans-Regular.ttf';
    $fonteUsada = $fonteCustom ?? $fontPath;
    
    if (file_exists($fonteUsada)) {
        $bbox = imagettfbbox($tamanho, $angulo, $fonteUsada, $texto);
        $alturaTexto = abs($bbox[7] - $bbox[1]);
        imagettftext($im, $tamanho, $angulo, $x, $y + $alturaTexto, $cor, $fonteUsada, $texto);
    } else {
        logDebug("AVISO: Fonte não encontrada: $fonteUsada");
        imagestring($im, 5, $x, $y, $texto, $cor);
    }
}

function getImageFromJson($jsonPath) {
    static $cache = [];
    if (isset($cache[$jsonPath])) {
        logDebug("Cache JSON HIT: $jsonPath");
        return $cache[$jsonPath];
    }
    
    logDebug("Carregando JSON: $jsonPath");
    
    $jsonContent = @file_get_contents($jsonPath);
    if ($jsonContent === false) {
        logDebug("ERRO: Não foi possível ler JSON: $jsonPath");
        return $cache[$jsonPath] = null;
    }
    
    $data = json_decode($jsonContent, true);
    if (empty($data) || !isset($data[0]['ImageName'])) {
        logDebug("ERRO: JSON inválido ou sem ImageName: $jsonPath");
        return $cache[$jsonPath] = null;
    }
    
    $imagePath = str_replace('../', '', $data[0]['ImageName']);
    if (!file_exists($imagePath)) {
        logDebug("ERRO: Arquivo de imagem não existe: $imagePath");
        return $cache[$jsonPath] = null;
    }
    
    $content = @file_get_contents($imagePath);
    logDebug($content ? "Imagem carregada: " . strlen($content) . " bytes" : "ERRO ao carregar imagem");
    
    return $cache[$jsonPath] = $content;
}

function centralizarTextoX($larguraImagem, $tamanhoFonte, $fonte, $texto) { 
    if (!file_exists($fonte)) {
        logDebug("AVISO: Fonte não encontrada para centralização: $fonte");
        return $larguraImagem / 2;
    }
    $caixa = imagettfbbox($tamanhoFonte, 0, $fonte, $texto); 
    return ($larguraImagem - ($caixa[2] - $caixa[0])) / 2; 
}

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

function obterJogosDeHoje() {
    $startTime = microtime(true);
    logDebug("=== INÍCIO BUSCA JOGOS ===");
    
    $chave_secreta = getChaveRemota();
    if (!$chave_secreta) {
        logDebug("ERRO CRÍTICO: Não foi possível obter chave remota");
        return [];
    }
    
    $parametro_criptografado = 'SVI0Sjh1MTJuRkw1bmFyeFdPb3cwOXA2TFo3RWlSQUxLbkczaGE4MXBiMWhENEpOWkhkSFZoeURaWFVDM1lTZzo6RNBu5BBhzmFRkTPPSikeJg==';
    $json_url = descriptografarURL($parametro_criptografado, $chave_secreta);
    
    if (!$json_url) {
        logDebug("ERRO CRÍTICO: Não foi possível descriptografar URL dos jogos");
        return [];
    }

    logDebug("URL dos jogos obtida, fazendo requisição...");

    $jogos = [];
    $ch = curl_init($json_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Connection: close'
    ]);
    
    $json_content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($json_content === false || $httpCode >= 400 || !empty($error)) {
        logDebug("ERRO BUSCA JOGOS: HTTP=$httpCode | Error=$error | Time={$info['total_time']}s");
        return [];
    }
    
    logDebug("Resposta recebida: " . strlen($json_content) . " bytes");
    
    $todos_jogos = json_decode($json_content, true);
    if (!is_array($todos_jogos)) {
        logDebug("ERRO: JSON inválido ou não é array");
        return [];
    }
    
    logDebug("Total de jogos no JSON: " . count($todos_jogos));
    
    foreach ($todos_jogos as $jogo) {
        if (isset($jogo['data_jogo']) && $jogo['data_jogo'] === 'hoje') {
            $jogos[] = $jogo;
        }
    }
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    logDebug("=== FIM BUSCA JOGOS: " . count($jogos) . " jogos de hoje encontrados em {$duration}s ===");
    
    return $jogos;
}

function verificarMemoriaERecursos() {
    $memoryUsage = memory_get_usage(true);
    $memoryPeak = memory_get_peak_usage(true);
    $memoryLimit = ini_get('memory_limit');
    
    logDebug("MEMÓRIA: Atual=" . formatBytes($memoryUsage) . " | Pico=" . formatBytes($memoryPeak) . " | Limite=$memoryLimit");
    
    if ($memoryUsage > (1024 * 1024 * 100)) { // 100MB
        logDebug("AVISO: Alto uso de memória detectado");
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Verificar recursos no carregamento
verificarMemoriaERecursos();

// Configurar headers para melhor performance
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
}

logDebug("Banner functions carregadas com sucesso");
?>