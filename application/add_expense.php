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

// Verifica che il form sia stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $categoryId = $_POST['category_id'];
    $amount = $_POST['amount'];
    $description = trim($_POST['description']);
    $date = $_POST['date'];

    // Validazione dei dati
    if (empty($categoryId) || empty($amount) || empty($date)) {
        die("Tutti i campi obbligatori devono essere compilati.");
    }

    if ($amount <= 0) {
        die("L'importo deve essere maggiore di zero.");
    }

    // Inserimento della spesa nel database
    $stmt = $pdo->prepare("
        INSERT INTO expenses (user_id, category_id, amount, description, date) 
        VALUES (:user_id, :category_id, :amount, :description, :date)
    ");
    try {
        $stmt->execute([
            'user_id' => $userId,
            'category_id' => $categoryId,
            'amount' => $amount,
            'description' => $description,
            'date' => $date
        ]);
        // Reindirizzamento con messaggio di successo
        header("Location: dashboard.php?expense_added=1");
        exit;
    } catch (PDOException $e) {
        die("Errore durante l'aggiunta della spesa: " . $e->getMessage());
    }
} else {
    // Recupera categorie principali e sottocategorie
    $stmt = $pdo->prepare("
        SELECT id, name, parent_id 
        FROM categories 
        WHERE user_id = :user_id
        ORDER BY parent_id IS NULL DESC, parent_id, name
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organizza le categorie per parent_id
    $categoriesByParent = [];
    foreach ($categories as $category) {
        $parentId = $category['parent_id'] ?? 0; // 0 per le categorie principali
        if (!isset($categoriesByParent[$parentId])) {
            $categoriesByParent[$parentId] = [];
        }
        $categoriesByParent[$parentId][] = $category;
    }

    // Funzione per generare il menu a discesa
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
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Aggiungi Spesa - SpendWise</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Aggiungi una nuova spesa</h5>
                    <form action="add_expense.php" method="POST">
                        <div class="mb-3">
                            <label for="category" class="form-label">Categoria</label>
                            <select class="form-select" id="category" name="category_id" required>
                                <option value="" selected>Scegli una categoria</option>
                                <?php displayCategoriesForSelect(0, $categoriesByParent); ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Importo (€)</label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrizione</label>
                            <input type="text" class="form-control" id="description" name="description" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label">Data</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Aggiungi Spesa</button>
                        <a href="dashboard.php" class="btn btn-secondary">Annulla</a>
                    </form>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
?>
