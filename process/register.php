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
    $error = "Errore di connessione al database: " . $e->getMessage();
}

// Verifica che il form sia stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    // Validazione di base
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Tutti i campi sono obbligatori.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email non valida.";
    } elseif (strlen($password) < 6) {
        $error = "La password deve essere di almeno 6 caratteri.";
    } else {
        // Verifica se l'email è già registrata
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = "Email già registrata. Riprova con un'altra.";
        } else {
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
                $error = "Errore durante la registrazione: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - SpendWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="form-container">
            <h1 class="form-title">Registrati a SpendWise</h1>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label for="registerName" class="form-label">Nome</label>
                    <input type="text" class="form-control" id="registerName" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="registerEmail" class="form-label">Email</label>
                    <input type="email" class="form-control" id="registerEmail" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="registerPassword" class="form-label">Password</label>
                    <input type="password" class="form-control" id="registerPassword" name="password" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Registrati</button>
            </form>
        </div>
    </div>
</body>
</html>