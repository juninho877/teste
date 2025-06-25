<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Painel' : 'Painel Administrativo'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Reset e Estilos Globais */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --sidebar-width: 260px;
            --page-bg: #1a1a2d;
            --sidebar-bg: rgba(26, 26, 45, 0.7); /* Aumentei um pouco a transparência */
            --card-bg: rgba(44, 47, 74, 0.5);
            --text-color: #f0f0f0;
            --text-muted: #a0a0c0;
            --accent-color: #4e73df;
            --transition-speed: 0.35s; /* Um pouco mais suave */
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--page-bg);
            color: var(--text-color);
            background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1579952363873-27f3bade9f55');
            background-position: center;
            background-size: cover;
            background-attachment: fixed;
        }

        .page-wrapper { display: flex; min-height: 100vh; }

        /* --- CORREÇÃO DA SIDEBAR --- */
        #sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform var(--transition-speed) ease-in-out; /* MUDANÇA: transição no transform */
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            z-index: 1000;
        }

        #main-content {
            flex-grow: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: margin-left var(--transition-speed);
        }

        /* --- LÓGICA RESPONSIVA CORRIGIDA --- */
        #menu-toggle-button { display: none; }
        #overlay {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);
            z-index: 999;
        }

        @media (max-width: 992px) {
            #sidebar {
                transform: translateX(-100%); /* MUDANÇA: Esconde o menu usando transform */
            }
            body.sidebar-open #sidebar {
                transform: translateX(0); /* MUDANÇA: Mostra o menu usando transform */
            }
            body.sidebar-open #overlay {
                display: block; /* Mostra a camada escura quando o menu está aberto */
            }
            #main-content { margin-left: 0; }
            #menu-toggle-button {
                display: flex; position: fixed; top: 15px; left: 15px; z-index: 1001;
                background: var(--accent-color); color: white; border: none; border-radius: 50%;
                width: 45px; height: 45px; align-items: center; justify-content: center;
                cursor: pointer; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            }
        }
        
        /* O resto do CSS permanece o mesmo */
        #sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        #sidebar-header h2 { font-size: 1.5rem; font-weight: 600; color: #fff; }
        #sidebar-content { flex-grow: 1; padding-top: 20px; overflow-y: auto; }
        .sidebar-item { display: flex; align-items: center; padding: 15px 25px; color: var(--text-muted); text-decoration: none; transition: all var(--transition-speed); font-weight: 500; border-left: 4px solid transparent; }
        .sidebar-item:hover { background: rgba(255, 255, 255, 0.05); color: #fff; border-left-color: var(--accent-color); }
        .sidebar-item.active { background: rgba(78, 115, 223, 0.2); color: #fff; border-left-color: var(--accent-color); font-weight: bold; }
        .sidebar-item i { width: 30px; margin-right: 15px; font-size: 1.2rem; }
        #sidebar-footer { padding: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-item.logout:hover { border-left-color: var(--danger-color); }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 2.2rem; font-weight: 700; }
        .content-card { background: var(--card-bg); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; padding: 30px; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <button id="menu-toggle-button"><i class="fas fa-bars"></i></button>

    <div id="sidebar">
        <div id="sidebar-header">
            <h2>FutBanner</h2>
        </div>
        <div id="sidebar-content">
            <a href="index.php" class="sidebar-item">
                <i class="fa-solid fa-house"></i><span>Dashboard</span>
            </a>
            <a href="futbanner.php" class="sidebar-item">
                <i class="fas fa-futbol"></i><span>Banner Fut</span>
            </a>
            <a href="logo.php" class="sidebar-item">
                <i class="fas fa-image"></i><span>Logo</span>
            </a>
            <a href="background.php" class="sidebar-item">
                <i class="fas fa-photo-video"></i><span>Fundo</span>
            </a>
            <a href="card.php" class="sidebar-item">
                <i class="fas fa-th-large"></i><span>Card Jogos</span>
            </a>
            <a href="setting.php" class="sidebar-item">
                <i class="fas fa-cog"></i><span>Credenciais</span>
            </a>
        </div>
        <div id="sidebar-footer">
            <a href="logout.php" class="sidebar-item logout">
                <i class="fas fa-sign-out-alt"></i><span>Sair</span>
            </a>
        </div>
    </div>
    
    <div id="main-content">