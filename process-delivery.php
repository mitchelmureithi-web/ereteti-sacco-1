<?php
// process-delivery.php — CREATE: record a delivery from the delivery form
require_once 'includes/db.php';

// Only accept submissions sent by the POST method.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: deliveries.php');
    exit;
}

// Read and tidy the submitted values.
$member = strtoupper(trim($_POST['member_no'] ?? ''));
$date = trim($_POST['delivery_date'] ?? '');
$session = strtoupper(trim($_POST['session'] ?? ''));
$litres = trim($_POST['litres'] ?? '');

$errors = [];

// Validate the values on the server.
if (!preg_match('/^ER-[0-9]{4}$/', $member)) {
    $errors[] = 'The member number must be ER- followed by four digits.';
}

$dateParts = explode('-', $date);
if (
    count($dateParts) !== 3
    || !checkdate((int) $dateParts[1], (int) $dateParts[2], (int) $dateParts[0])
) {
    $errors[] = 'Choose a valid delivery date.';
}

if ($session !== 'MORNING' && $session !== 'EVENING') {
    $errors[] = 'Choose the morning or evening session.';
}

if (!is_numeric($litres) || (float) $litres <= 0 || (float) $litres > 60) {
    $errors[] = 'Litres must be more than 0 and at most 60.';
}

// Find the member's farmer ID.
$farmerId = null;

if (count($errors) === 0) {
    $stmt = mysqli_prepare(
        $conn,
        'SELECT farmer_id FROM farmer WHERE member_no = ?'
    );

    if ($stmt === false) {
        $errors[] = 'The database could not prepare the member lookup.';
    } else {
        mysqli_stmt_bind_param($stmt, 's', $member);
        mysqli_stmt_execute($stmt);
        $farmer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($farmer === null) {
            $errors[] = 'No farmer has the member number ' . $member . '.';
        } else {
            $farmerId = (int) $farmer['farmer_id'];
        }
    }
}

// Save the delivery.
if (count($errors) === 0) {
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO milk_delivery
         (farmer_id, delivery_date, session, litres)
         VALUES (?, ?, ?, ?)'
    );

    if ($stmt === false) {
        $errors[] = 'The database could not prepare the delivery.';
    } else {
        mysqli_stmt_bind_param(
            $stmt,
            'issd',
            $farmerId,
            $date,
            $session,
            $litres
        );

        try {
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header(
                'Location: deliveries.php?date='
                . urlencode($date)
                . '&saved=1'
            );
            exit;
        } catch (mysqli_sql_exception $e) {
            mysqli_stmt_close($stmt);

            if ($e->getCode() === 1062) {
                $errors[] = $member
                    . ' has already delivered in the '
                    . strtolower($session)
                    . ' session on '
                    . $date
                    . '.';
            } else {
                throw $e;
            }
        }
    }
}

// Show validation errors.
$page_title = 'Delivery not recorded';
include 'includes/header.php';
?>
<main>
    <h2>The delivery was not recorded</h2>

    <?php if (count($errors) > 0): ?>
        <ul>
        <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p><a href="deliveries.php">Back to the deliveries</a></p>
</main>
<?php include 'includes/footer.php'; ?>
