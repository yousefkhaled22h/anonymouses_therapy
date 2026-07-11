<?php
// db_utils.php
require_once 'includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['role'] ?? '';
$message = '';
$message_type = 'success';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    try {
        switch ($action) {
            case 'fix_collation':
                // Get all tables and convert to utf8mb4_general_ci
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $converted = [];
                foreach ($tables as $table) {
                    $pdo->exec("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                    $converted[] = $table;
                }
                $message = "Global Collation Fix completed! Converted tables: " . implode(', ', $converted);
                break;

            case 'alter_resource':
                // Add url column to resource table if not exists
                try {
                    $pdo->exec("ALTER TABLE resource ADD COLUMN url VARCHAR(255) DEFAULT NULL;");
                    $message = "Successfully added 'url' column to the resource table.";
                } catch (Exception $e) {
                    $message = "Column might already exist: " . $e->getMessage();
                    $message_type = 'warning';
                }
                break;

            case 'alter_user':
                // Add status column to user table if not exists
                try {
                    $pdo->exec("ALTER TABLE user ADD COLUMN status VARCHAR(20) DEFAULT 'Active'");
                    $message = "Successfully added 'status' column to the user table.";
                } catch (Exception $e) {
                    $message = "Column might already exist: " . $e->getMessage();
                    $message_type = 'warning';
                }
                break;

            case 'clean_resources':
                // Delete empty or invalid resources
                $stmt = $pdo->prepare("DELETE FROM resource WHERE resource_id = '' OR resource_id NOT IN ('RES_1', 'RES_2', 'RES_3')");
                $stmt->execute();
                $rows = $stmt->rowCount();
                $message = "Successfully cleaned bad resources. Rows affected: $rows.";
                break;

            case 'update_resource_urls':
                // Update file protocol URLs to assets path
                $stmt = $pdo->prepare("UPDATE resource SET url = 'assets/uploads/resources/2101.07714v3.pdf' WHERE url LIKE 'file://%'");
                $stmt->execute();
                $rows = $stmt->rowCount();
                $message = "Successfully standardized resource PDFs. Rows affected: $rows.";
                break;

            default:
                $message = "Unknown action requested.";
                $message_type = 'error';
                break;
        }
    } catch (PDOException $e) {
        $message = "Database Error: " . $e->getMessage();
        $message_type = 'error';
    }
}

require_once 'includes/header.php';
?>
<style>
    .utils-wrapper {
        min-height: 80vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        background: linear-gradient(135deg, #fdfbf8 0%, #ede8e1 100%);
    }

    .utils-container {
        width: 100%;
        max-width: 800px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.4);
    }

    .utils-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .utils-header h1 {
        font-size: 2.25rem;
        color: #3E2723;
        font-family: var(--font-heading);
        margin-bottom: 10px;
    }

    .utils-header p {
        color: #7d7265;
        font-size: 1.1rem;
    }

    .grid-utils {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .util-card {
        background: #fdfdfd;
        border: 1px solid #ede8e1;
        border-radius: 12px;
        padding: 24px;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .util-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.03);
        border-color: #8A7055;
    }

    .util-card h3 {
        font-size: 1.25rem;
        color: #3E2723;
        margin-bottom: 10px;
        font-weight: 600;
    }

    .util-card p {
        font-size: 0.95rem;
        color: #887d72;
        margin-bottom: 20px;
        line-height: 1.5;
    }

    .btn-action {
        width: 100%;
        padding: 10px 15px;
        background: #8A7055;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }

    .btn-action:hover {
        background: #3E2723;
    }

    .alert-box {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        font-weight: 500;
        text-align: center;
    }

    .alert-box.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-box.warning {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .alert-box.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<div class="utils-wrapper">
    <div class="utils-container animate-up">
        <div class="utils-header">
            <h1>Database & Maintenance Tools 🛠️</h1>
            <p>Admin and maintenance utilities consolidated into a single control panel.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-box <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid-utils">
            <!-- Collation Fix -->
            <div class="util-card">
                <div>
                    <h3>Global Collation Fix</h3>
                    <p>Standardizes all database tables to <code>utf8mb4_general_ci</code> to avoid encoding and sorting errors across fields.</p>
                </div>
                <form action="?action=fix_collation" method="POST">
                    <button type="submit" class="btn-action">Run Collation Fix</button>
                </form>
            </div>

            <!-- Alter Resource Table -->
            <div class="util-card">
                <div>
                    <h3>Alter Resource Table</h3>
                    <p>Appends the missing <code>url</code> column to the resource table structure so document links render correctly.</p>
                </div>
                <form action="?action=alter_resource" method="POST">
                    <button type="submit" class="btn-action">Alter Resource Table</button>
                </form>
            </div>

            <!-- Alter User Table -->
            <div class="util-card">
                <div>
                    <h3>Alter User Table</h3>
                    <p>Adds the <code>status</code> column to the main User table to support therapist approval and ban features.</p>
                </div>
                <form action="?action=alter_user" method="POST">
                    <button type="submit" class="btn-action">Alter User Table</button>
                </form>
            </div>

            <!-- Clean Resources -->
            <div class="util-card">
                <div>
                    <h3>Clean Bad Resources</h3>
                    <p>Deletes invalid or corrupted rows from the resource table that do not match default resource indexes.</p>
                </div>
                <form action="?action=clean_resources" method="POST">
                    <button type="submit" class="btn-action">Clean Resource Table</button>
                </form>
            </div>

            <!-- Update PDF URLs -->
            <div class="util-card">
                <div>
                    <h3>Standardize PDFs</h3>
                    <p>Updates outdated local file paths inside the resource table with correct platform assets urls.</p>
                </div>
                <form action="?action=update_resource_urls" method="POST">
                    <button type="submit" class="btn-action">Standardize PDFs</button>
                </form>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" style="color: #8A7055; font-weight: bold; text-decoration: none;">&larr; Back to Dashboard</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
