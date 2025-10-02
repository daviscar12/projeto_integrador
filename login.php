<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'nahoradocheckin');
define('DB_USER', 'root'); 
define('DB_PASS', ''); 


session_start();


function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Erro na conexão: " . $e->getMessage());
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'login':
                processarLogin();
                break;
            case 'register':
                processarRegistro();
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Ação inválida']);
                break;
        }
    }
}


function processarLogin() {
    if (!isset($_POST['email']) || !isset($_POST['senha'])) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        return;
    }
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, nome_completo, email, senha FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome_completo'];
            $_SESSION['usuario_email'] = $usuario['email'];
            
            echo json_encode([
                'success' => true, 
                'message' => 'Login realizado com sucesso',
                'redirect' => 'index.html' 
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
    }
}


function processarRegistro() {
    if (!isset($_POST['email']) || !isset($_POST['senha']) || !isset($_POST['nome_completo']) || !isset($_POST['confirmar_senha'])) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        return;
    }
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $nome = filter_var($_POST['nome_completo'], FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'];
    $confirmarSenha = $_POST['confirmar_senha'];
    
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        return;
    }
    
    if ($senha !== $confirmarSenha) {
        echo json_encode(['success' => false, 'message' => 'As senhas não coincidem']);
        return;
    }
    
    if (strlen($senha) < 6) {
        echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres']);
        return;
    }
    
    try {
        $pdo = getDBConnection();
        
        
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Este email já está em uso']);
            return;
        }
        

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome_completo, email, senha) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $email, $senhaHash]);
        
        if ($stmt->rowCount() > 0) {
            
            $usuarioId = $pdo->lastInsertId();
            $_SESSION['usuario_id'] = $usuarioId;
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_email'] = $email;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Conta criada com sucesso',
                'redirect' => 'index.html' 
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao criar conta']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
    }
}


function verificarLogin() {
    return isset($_SESSION['usuario_id']);
}

function logout() {
    session_unset();
    session_destroy();
    return true;
}


if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>NaHoraDoCheckIn - Entrar</title>
  <link rel="stylesheet" href="assets/css/styles.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=home" />
</head>
<body>
  <header class="navbar21">
    <div class="logo-area21">
      <span class="material-symbols-outlined">home</span>
      <span class="logo21">NaHoraDoCheckIn</span>
    </div>
    
    <div class="menu24">
      <button class="bntmenu21">Entrar</button>
      <button class="bntmenu22"><a href="index.html">Voltar</a></button>
    </div>
  </header>

  <main class="main22">
    <h2>Bem-vindo ao <span class="highlight22">NaHoraDoCheckIn</span></h2>
    <p>Entre ou cadastre-se para encontrar os melhores hotéis</p>

    <div class="tabs21">
      <button class="tab21 active" data-tab="login">Entrar</button>
      <button class="tab21" data-tab="register">Cadastrar</button>
    </div>

    <div class="form-wrapper21">
      <form id="loginForm" class="form21" method="POST" action="login.php">
        <input type="hidden" name="action" value="login">
        <label>Email</label>
        <input type="email" name="email" placeholder="seu@email.com" required />

        <label>Senha <a href="#" class="forgot21">Esqueceu a senha?</a></label>
        <input type="password" name="senha" placeholder="********" required />

        <button type="submit" class="btn-primary21">Entrar</button>

        <div class="divider21">Ou continue com</div>
        <div class="social-login21">
          <button type="button" class="google21"> Google</button>
          <button type="button" class="facebook21"> Facebook</button>
        </div>
      </form>

      <form id="registerForm" class="form21 hidden21" method="POST" action="login.php">
        <input type="hidden" name="action" value="register">
        <label>Email</label>
        <input type="email" name="email" placeholder="seu@email.com" required />

        <label>Nome Completo</label>
        <input type="text" name="nome_completo" placeholder="Seu Nome" required />

        <label>Senha</label>
        <input type="password" name="senha" placeholder="********" required />

        <label>Confirmar Senha</label>
        <input type="password" name="confirmar_senha" placeholder="********" required />

        <button type="submit" class="btn-primary21">Cadastrar</button>
      </form>
    </div>
  </main>

  <footer class="footer21">
    <div class="cta21">
      <h2>Pronto para sua próxima aventura?</h2>
      <p>Registre-se agora e receba acesso a ofertas exclusivas e descontos especiais para suas próximas reservas.</p>
      <div class="cta-buttons21">
        <button class="btn-primary21">Entrar / Registrar</button>
        <button class="btn-secondary21">Explorar Hotéis</button>
      </div>
    </div>

    <div class="footer-links21">
      <div class="col21">
        <h3 class="logo21">NaHoraDoCheckIn</h3>
        <p>Encontre os melhores hotéis para suas viagens em um só lugar.</p>
      </div>
      <div class="col21">
        <h4>Links Rápidos</h4>
        <ul>
          <li><a href="#">Home</a></li>
          <li><a href="#">Pesquisar Hotéis</a></li>
          <li><a href="#">Sobre Nós</a></li>
          <li><a href="#">Entrar / Registrar</a></li>
        </ul>
      </div>
      <div class="col21">
        <h4>Suporte</h4>
        <ul>
          <li><a href="#">Centro de Ajuda</a></li>
          <li><a href="#">Políticas de Cancelamento</a></li>
          <li><a href="#">Contato</a></li>
        </ul>
      </div>
      <div class="col21">
        <h4>Contato</h4>
        <p>Senac, 730, Visconde de Taunay<br />Joinville, SC</p>
        <p>Email: contato@nahoradocheckin.com</p>
        <p>Tel: (47) 99706-2510</p>
      </div>
    </div>
  </footer>

  <script>
    // JavaScript para processar os formulários via AJAX
    document.addEventListener('DOMContentLoaded', function() {
      // Alternar entre login e registro
      const tabs = document.querySelectorAll('.tab21');
      const loginForm = document.getElementById('loginForm');
      const registerForm = document.getElementById('registerForm');
      
      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          const tabName = this.getAttribute('data-tab');
          
          tabs.forEach(t => t.classList.remove('active'));
          this.classList.add('active');
          
          if (tabName === 'login') {
            loginForm.classList.remove('hidden21');
            registerForm.classList.add('hidden21');
          } else {
            loginForm.classList.add('hidden21');
            registerForm.classList.remove('hidden21');
          }
        });
      });
      
      // Processar formulários com AJAX
      const forms = document.querySelectorAll('form');
      forms.forEach(form => {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          
          const formData = new FormData(this);
          
          fetch('login.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alert(data.message);
              if (data.redirect) {
                window.location.href = data.redirect;
              }
            } else {
              alert('Erro: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar a requisição');
          });
        });
      });
    });
  </script>
</body>
</html>