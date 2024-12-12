<?php
// Avvio sessione
session_start();

// Verifica se l'utente è autenticato
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Configurazione database
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

// ID utente dalla sessione
$userId = $_SESSION['user_id'];

// Gestione eliminazione e modifica spesa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_expense') {
        $expenseId = $_POST['expense_id'];
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            'id' => $expenseId,
            'user_id' => $userId
        ]);
        $success = "Spesa eliminata con successo!";
    } elseif ($_POST['action'] === 'edit_expense') {
        $expenseId = $_POST['expense_id'];
        $categoryId = $_POST['category_id'];
        $amount = $_POST['amount'];
        $description = trim($_POST['description']);
        $date = $_POST['date'];

        // Validazione dei dati
        if (empty($categoryId) || empty($amount) || empty($date)) {
            $error = "Tutti i campi sono obbligatori.";
        } elseif ($amount <= 0) {
            $error = "L'importo deve essere maggiore di zero.";
        } else {
            $stmt = $pdo->prepare("
                UPDATE expenses 
                SET category_id = :category_id, amount = :amount, description = :description, date = :date 
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([
                'category_id' => $categoryId,
                'amount' => $amount,
                'description' => $description,
                'date' => $date,
                'id' => $expenseId,
                'user_id' => $userId
            ]);
            $success = "Spesa modificata con successo!";
        }
    }
}

// Query per il bilancio totale
$totalQuery = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS total_expenses FROM expenses WHERE user_id = :user_id");
$totalQuery->execute(['user_id' => $userId]);
$totalExpenses = $totalQuery->fetch(PDO::FETCH_ASSOC)['total_expenses'];

// Query per spese per categoria
$categoriesQuery = $pdo->prepare("
    SELECT c.id, c.name AS category, COALESCE(SUM(e.amount), 0) AS total 
    FROM categories c 
    LEFT JOIN expenses e ON c.id = e.category_id 
    WHERE c.user_id = :user_id 
    GROUP BY c.id, c.name
");
$categoriesQuery->execute(['user_id' => $userId]);
$categoriesExpenses = $categoriesQuery->fetchAll(PDO::FETCH_ASSOC);

// Prepara i dati per il grafico (solo categorie con spese > 0)
$categoryLabels = [];
$categoryTotals = [];
foreach ($categoriesExpenses as $category) {
    if ($category['total'] > 0) { // Considera solo le categorie con spese > 0
        $categoryLabels[] = htmlspecialchars($category['category']);
        $categoryTotals[] = $category['total'];
    }
}

// Query per le spese recenti
$recentExpensesQuery = $pdo->prepare("
    SELECT e.id, e.amount, e.description, e.date, c.name AS category, e.category_id
    FROM expenses e
    JOIN categories c ON e.category_id = c.id
    WHERE e.user_id = :user_id
    ORDER BY e.date DESC
    LIMIT 5
");
$recentExpensesQuery->execute(['user_id' => $userId]);
$recentExpenses = $recentExpensesQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-center">Benvenuto, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
            <div class="button-group">
                <a href="add_expense.php" class="btn btn-primary">Aggiungi Spesa</a>
                <a href="manage_categories.php" class="btn btn-secondary">Gestisci Categorie</a>
            </div>
        </div>
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
        <div class="row">
            <!-- Bilancio totale -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Bilancio Totale</h5>
                        <p class="card-text display-4" style="color: white;">€<?= number_format($totalExpenses, 2) ?></p>
                    </div>
                </div>
            </div>
            <!-- Spese per categoria -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Spese per Categoria</h5>
                        <canvas id="categoryExpensesChart" width="400" height="400"></canvas>
                        <?php if (empty($categoryTotals)): ?>
                            <p class="text-muted text-center mt-3">Nessuna spesa registrata nelle categorie.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <!-- Spese recenti -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Spese Recenti</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Categoria</th>
                                    <th>Descrizione</th>
                                    <th>Importo</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentExpenses as $expense): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($expense['date']) ?></td>
                                        <td><?= htmlspecialchars($expense['category']) ?></td>
                                        <td><?= htmlspecialchars($expense['description']) ?></td>
                                        <td>€<?= number_format($expense['amount'], 2) ?></td>
                                        <td>
                                            <form action="dashboard.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete_expense">
                                                <input type="hidden" name="expense_id" value="<?= $expense['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Elimina</button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editExpenseModal" data-id="<?= $expense['id'] ?>" data-category="<?= $expense['category_id'] ?>" data-amount="<?= $expense['amount'] ?>" data-description="<?= $expense['description'] ?>" data-date="<?= $expense['date'] ?>">Modifica</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentExpenses)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nessuna spesa recente.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal per modificare la spesa -->
    <div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editExpenseModalLabel">Modifica Spesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editExpenseForm" action="dashboard.php" method="POST">
                        <input type="hidden" name="action" value="edit_expense">
                        <input type="hidden" name="expense_id" id="editExpenseId">
                        <div class="mb-3">
                            <label for="editCategory" class="form-label">Categoria</label>
                            <select class="form-select" id="editCategory" name="category_id" required>
                                <?php foreach ($categoriesExpenses as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['category']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editAmount" class="form-label">Importo</label>
                            <input type="number" step="0.01" class="form-control" id="editAmount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Descrizione</label>
                            <input type="text" class="form-control" id="editDescription" name="description" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label for="editDate" class="form-label">Data</label>
                            <input type="date" class="form-control" id="editDate" name="date" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Salva modifiche</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Dati per il grafico
    const categoryLabels = <?= json_encode($categoryLabels) ?>;
    const categoryTotals = <?= json_encode($categoryTotals) ?>;

    if (categoryTotals.length > 0) {
        const ctx = document.getElementById('categoryExpensesChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Spese per Categoria',
                    data: categoryTotals,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.raw;
                                const percentage = ((value / total) * 100).toFixed(2);
                                return `${context.label}: €${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Popola il modal di modifica con i dati della spesa selezionata
    document.getElementById('editExpenseModal').addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var expenseId = button.getAttribute('data-id');
        var categoryId = button.getAttribute('data-category');
        var amount = button.getAttribute('data-amount');
        var description = button.getAttribute('data-description');
        var date = button.getAttribute('data-date');

        var modal = this;
        modal.querySelector('#editExpenseId').value = expenseId;
        modal.querySelector('#editCategory').value = categoryId;
        modal.querySelector('#editAmount').value = amount;
        modal.querySelector('#editDescription').value = description;
        modal.querySelector('#editDate').value = date;
    });
    </script>
</body>
</html>