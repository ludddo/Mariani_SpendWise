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

// Query per il bilancio totale
$totalQuery = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS total_expenses FROM expenses WHERE user_id = :user_id");
$totalQuery->execute(['user_id' => $userId]);
$totalExpenses = $totalQuery->fetch(PDO::FETCH_ASSOC)['total_expenses'];

// Query per spese per categoria
$categoriesQuery = $pdo->prepare("
    SELECT c.name AS category, COALESCE(SUM(e.amount), 0) AS total 
    FROM categories c 
    LEFT JOIN expenses e ON c.id = e.category_id 
    WHERE c.user_id = :user_id 
    GROUP BY c.name
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
    SELECT e.amount, e.description, e.date, c.name AS category 
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
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .container {
            margin-top: 2rem;
        }
        .card {
            margin-bottom: 1rem;
        }
        .button-group {
            display: flex;
            gap: 10px;
            justify-content: end;
            margin-bottom: 1rem;
        }
    </style>
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
        <?php if (isset($_GET['expense_added']) && $_GET['expense_added'] == 1): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Spesa aggiunta con successo!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="row">
            <!-- Bilancio totale -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Bilancio Totale</h5>
                        <p class="card-text display-4" style="color: #fff;">€<?= number_format($totalExpenses, 2) ?></p>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentExpenses as $expense): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($expense['date']) ?></td>
                                        <td><?= htmlspecialchars($expense['category']) ?></td>
                                        <td><?= htmlspecialchars($expense['description']) ?></td>
                                        <td>€<?= number_format($expense['amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentExpenses)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Nessuna spesa recente.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
