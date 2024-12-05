<?php
// Avvio sessione
session_start();

// Configurazione database
$host = 'localhost';
$dbname = 'spendwise';
$username = 'root';
$password = '';

// Connessione al database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}

// Verifica che il form sia stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Controllo che i campi non siano vuoti
    if (empty($email) || empty($password)) {
        die("Email e password sono obbligatori.");
    }

    // Query per verificare l'utente
    $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Salva informazioni utente nella sessione
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        // Reindirizza alla dashboard
        header("Location: ../application/dashboard.php");
        exit;
    } else {
        echo "Credenziali non valide. Riprova.";
    }
}
?>
