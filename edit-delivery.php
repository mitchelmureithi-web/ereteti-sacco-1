<?php
// edit-delivery.php — EDIT: loads one delivery, then updates it
require_once 'includes/db.php';

$page_title = 'Edit delivery';
$errors = [];
$delivery = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'delivery_id', FILTER_VALIDATE_INT);
    $member = strtoupper(trim($_POST['member_no'] ?? ''));
    $date = $_POST['delivery_date'] ?? '';
    $session = $_POST['session'] ?? '';
    $litres = $_POST['litres'] ?? '';

    if (!$id) {
        $errors[] = 'Choose a valid delivery to edit.';
    }

    if (!preg_match('/^ER-[0-9]{4}$/', $member)) {
        $errors[] = 'The member number must be ER- and four digits, such as ER-0001.';
    }

    if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date)) {
        $errors[] = 'Choose the date of the delivery.';
    }

    if ($session !== 'MORNING' && $session !== 'EVENING') {
        $errors[] = 'Choose the morning or the evening session.';
    }

    if (!is_numeric($litres) || $litres <= 0 || $litres > 60) {
        $errors[] = 'Litres must be more than 0 and at most 60.';
    }

    if (count($errors) === 0) {
        $stmt = mysqli_prepare($conn, 'SELECT farmer_id FROM farmer WHERE member_no = ?');
        mysqli_stmt_bind_param($stmt, 's', $member);
        mysqli_stmt_execute($stmt);
        $farmer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($farmer === null) {
            $errors[] = 'No farmer has the member number ' . $member . '.';
        }
    }

    if (count($errors) === 0) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE milk_delivery
             SET farmer_id = ?, delivery_date = ?, session = ?, litres = ?
             WHERE delivery_id = ?'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'issdi',
            $farmer['farmer_id'],
            $date,
            $session,
            $litres,
            $id
        );

        try {
            mysqli_stmt_execute($stmt);
            header('Location: deliveries.php?date=' . urlencode($date) . '&updated=1');
            exit;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                $errors[] = $member . ' has already delivered in the '
                    . strtolower($session) . ' session on ' . $date . '.';
            } else {
                throw $e;
            }
        }
    }

    $delivery = [
        'delivery_id' => $id,
        'member_no' => $member,
        'delivery_date' => $date,
        'session' => $session,
        'litres' => $litres
    ];
} else {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        $errors[] = 'Choose a valid delivery to edit.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT d.delivery_id, d.delivery_date, d.session, d.litres,
                    f.member_no
             FROM milk_delivery d
             JOIN farmer f ON f.farmer_id = d.farmer_id
             WHERE d.delivery_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $delivery = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($delivery === null) {
            $errors[] = 'That delivery could not be found.';
        }
    }
}

include 'includes/header.php';
?>
<main>
    <h2>Edit a delivery</h2>

    <?php if (count($errors) > 0): ?>
        <ul>
        <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($delivery !== null): ?>
        <form action="edit-delivery.php" method="post">
            <input type="hidden" name="delivery_id"
                value="<?php echo htmlspecialchars($delivery['delivery_id']); ?>">

            <label for="member">Member number</label>
            <input type="text" id="member" name="member_no"
                value="<?php echo htmlspecialchars($delivery['member_no']); ?>" required>

            <label for="delivery-date">Date</label>
            <input type="date" id="delivery-date" name="delivery_date"
                value="<?php echo htmlspecialchars($delivery['delivery_date']); ?>" required>

            <label for="session">Session</label>
            <select id="session" name="session" required>
                <option value="MORNING"<?php echo $delivery['session'] === 'MORNING' ? ' selected' : ''; ?>>
                    Morning
                </option>
                <option value="EVENING"<?php echo $delivery['session'] === 'EVENING' ? ' selected' : ''; ?>>
                    Evening
                </option>
            </select>

            <label for="litres">Litres</label>
            <input type="number" id="litres" name="litres" step="0.01"
                value="<?php echo htmlspecialchars($delivery['litres']); ?>" required>

            <button type="submit">Update delivery</button>
        </form>
    <?php endif; ?>

    <p><a href="deliveries.php">Back to the deliveries</a></p>
</main>
<?php include 'includes/footer.php'; ?>
