<?php
// Configurazione del database
$host = 'localhost';
$dbname = 'spendwise';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}

// Verifica che il form sia stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validazione di base
    if (empty($name) || empty($email) || empty($password)) {
        die("Tutti i campi sono obbligatori.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Email non valida.");
    }

    if (strlen($password) < 6) {
        die("La password deve essere di almeno 6 caratteri.");
    }

    // Verifica se l'email è già registrata
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        die("Email già registrata. Riprova con un'altra.");
    }

    // Hash della password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Inserimento dell'utente nel database
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
    try {
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        // Recupera l'ID dell'utente appena creato
        $newUserId = $pdo->lastInsertId();

        // Duplica le categorie globali e le loro sottocategorie per il nuovo utente
        $categoriesStmt = $pdo->query("
            SELECT id, name 
            FROM global_categories 
            WHERE parent_id IS NULL
        ");
        $globalCategories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($globalCategories as $category) {
            // Inserisci la categoria principale per l'utente
            $stmt = $pdo->prepare("INSERT INTO categories (user_id, parent_id, name) VALUES (:user_id, NULL, :name)");
            $stmt->execute([
                'user_id' => $newUserId,
                'name' => $category['name']
            ]);

            // Recupera l'ID della categoria appena creata
            $newCategoryId = $pdo->lastInsertId();

            // Inserisci le sottocategorie della categoria globale
            $subcategoriesStmt = $pdo->prepare("
                SELECT name 
                FROM global_categories 
                WHERE parent_id = :parent_id
            ");
            $subcategoriesStmt->execute(['parent_id' => $category['id']]);
            $globalSubcategories = $subcategoriesStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($globalSubcategories as $subcategory) {
                $stmt = $pdo->prepare("INSERT INTO categories (user_id, parent_id, name) VALUES (:user_id, :parent_id, :name)");
                $stmt->execute([
                    'user_id' => $newUserId,
                    'parent_id' => $newCategoryId,
                    'name' => $subcategory['name']
                ]);
            }
        }

        // Reindirizzamento al login
        header("Location: ../index.html?success=1");
        exit;
    } catch (PDOException $e) {
        die("Errore durante la registrazione: " . $e->getMessage());
    }
}
?>
