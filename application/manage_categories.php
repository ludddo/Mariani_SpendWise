<?php
// Avvio sessione
session_start();

// Verifica se l'utente è autenticato
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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

// ID dell'utente
$userId = $_SESSION['user_id'];

// Aggiunta di una nuova categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $parentId = $_POST['parent_id'] === '' ? NULL : $_POST['parent_id'];
    $name = trim($_POST['name']);

    if (empty($name)) {
        $error = "Il nome della categoria è obbligatorio.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (user_id, parent_id, name) VALUES (:user_id, :parent_id, :name)");
        $stmt->execute([
            'user_id' => $userId,
            'parent_id' => $parentId,
            'name' => $name
        ]);
        $success = "Categoria aggiunta con successo!";
    }
}

// Eliminazione di una categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $categoryId = $_POST['category_id'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        'id' => $categoryId,
        'user_id' => $userId
    ]);
    $success = "Categoria eliminata con successo!";
}

// Recupera tutte le categorie per l'utente
$stmt = $pdo->prepare("
    SELECT id, name, parent_id 
    FROM categories 
    WHERE user_id = :user_id
    ORDER BY name
");
$stmt->execute(['user_id' => $userId]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crea un array di categorie organizzate per parent_id
$categoriesByParent = [];
foreach ($categories as $category) {
    $parentId = $category['parent_id'] ?? 0; // 0 per le categorie principali
    if (!isset($categoriesByParent[$parentId])) {
        $categoriesByParent[$parentId] = [];
    }
    $categoriesByParent[$parentId][] = $category;
}

// Funzione ricorsiva per visualizzare la gerarchia di categorie
function displayCategoriesForSelect($parentId, $categoriesByParent, $level = 0) {
    if (isset($categoriesByParent[$parentId])) {
        foreach ($categoriesByParent[$parentId] as $category) {
            echo "<option value=\"" . htmlspecialchars($category['id']) . "\">";
            echo str_repeat("&nbsp;&nbsp;&nbsp;", $level) . htmlspecialchars($category['name']);
            echo "</option>";

            // Chiamata ricorsiva per le sottocategorie
            displayCategoriesForSelect($category['id'], $categoriesByParent, $level + 1);
        }
    }
}

function displayCategories($parentId, $categoriesByParent, $level = 0) {
    if (isset($categoriesByParent[$parentId])) {
        foreach ($categoriesByParent[$parentId] as $category) {
            echo "<li class='list-group-item'>";
            // Indentazione visiva per le categorie
            echo str_repeat("&nbsp;&nbsp;&nbsp;", $level) . htmlspecialchars($category['name']);
            echo " <form action='manage_categories.php' method='POST' class='d-inline'>
                    <input type='hidden' name='action' value='delete'>
                    <input type='hidden' name='category_id' value='" . htmlspecialchars($category['id']) . "'>
                    <button type='submit' class='btn btn-sm btn-danger float-end'>Elimina</button>
                  </form>";
            // Richiamo ricorsivo per sottocategorie
            echo "<ul>";
            displayCategories($category['id'], $categoriesByParent, $level + 1);
            echo "</ul>";
            echo "</li>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Categorie - SpendWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-center">Gestione Categorie</h1>
            <a href="dashboard.php" class="btn btn-secondary">Torna alla Dashboard</a>
        </div>

        <!-- Messaggi di successo o errore -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Form per aggiungere categorie -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Aggiungi una nuova categoria</h5>
                <form action="manage_categories.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome Categoria</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                    <label for="parent_id" class="form-label">Categoria Principale (opzionale)</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="" selected>Nessuna</option>
                            <?php displayCategoriesForSelect(0, $categoriesByParent); ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Aggiungi</button>
                </form>
            </div>
        </div>

        <!-- Elenco delle categorie -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Categorie Esistenti</h5>
                <ul class="list-group">
                    <?php displayCategories(0, $categoriesByParent); ?>
                </ul>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
