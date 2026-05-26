<?php
session_start();
include 'db.php';

if(!isset($_SESSION['loggedInUser']) || !isset($_SESSION['client_id'])) {
    header("Location: AdminLogin.php");
    exit();
}

if (!isset($_GET['token_id'])) {
    echo "Invalid receipt.";
    exit();
}

$token_id = intval($_GET['token_id']);
$sql = "SELECT t.token_id, t.token_value, t.units, t.issue_date, t.expiry_date, t.status AS token_status,
               p.amount, p.payment_date, p.payment_status,
               c.name, c.meter_no, c.address
        FROM tokens t
        JOIN client_information c ON c.client_id = t.client_id
        LEFT JOIN payments p ON p.payment_id = (
            SELECT payment_id FROM payments
            WHERE client_id = t.client_id
            ORDER BY ABS(TIMESTAMPDIFF(SECOND, payment_date, t.issue_date))
            LIMIT 1
        )
        WHERE t.token_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $token_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Receipt not found.";
    exit();
}

$row = $result->fetch_assoc();
$stmt->close();

$receiptNo = 'RCPT-' . str_pad($row['token_id'], 6, '0', STR_PAD_LEFT);
$paymentStatus = htmlspecialchars($row['payment_status'] ?? 'Completed');
$tokenStatus = htmlspecialchars($row['token_status'] ?? 'active');
$statusClass = strtolower($tokenStatus) === 'active' ? 'status-active' : 'status-inactive';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AquaPay Receipt — <?php echo htmlspecialchars($receiptNo); ?></title>
    <link rel="icon" type="image/png" href="../images/icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --aqua-deep: #0077b6;
            --aqua-primary: #00b4d8;
            --aqua-pale: #caf0f8;
            --text-dark: #0d1b2a;
            --text-mid: #495057;
            --border: #d6eaf5;
            --card-shadow: 0 12px 40px rgba(0, 119, 182, 0.14);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'DM Sans', system-ui, sans-serif;
            background:
                radial-gradient(ellipse at 20% 0%, rgba(0, 180, 216, 0.2), transparent 50%),
                radial-gradient(ellipse at 100% 100%, rgba(0, 119, 182, 0.15), transparent 45%),
                #eef7fc;
            color: var(--text-dark);
            padding: 32px 16px 48px;
        }

        .receipt-shell {
            max-width: 520px;
            margin: 0 auto;
        }

        .receipt-container {
            background: #fff;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            position: relative;
            border: 1px solid var(--border);
        }

        .receipt-accent {
            height: 6px;
            background: linear-gradient(90deg, var(--aqua-deep), var(--aqua-primary), #90e0ef);
        }

        .receipt-body {
            padding: 28px 28px 24px;
            position: relative;
            z-index: 1;
        }

        .watermark {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 72px;
            font-weight: 700;
            color: var(--aqua-pale);
            opacity: 0.35;
            transform: rotate(-18deg);
            pointer-events: none;
            user-select: none;
            z-index: 0;
        }

        .receipt-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 22px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .receipt-logo {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 119, 182, 0.2);
        }

        .brand h1 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--aqua-deep);
            letter-spacing: -0.02em;
        }

        .brand p {
            margin: 2px 0 0;
            font-size: 0.8rem;
            color: var(--text-mid);
        }

        .receipt-badge {
            text-align: right;
        }

        .receipt-badge .label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-mid);
            margin-bottom: 4px;
        }

        .receipt-badge .number {
            font-size: 1rem;
            font-weight: 700;
            color: var(--aqua-deep);
        }

        .status-pill {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .status-active {
            background: rgba(40, 167, 69, 0.12);
            color: #1e7e34;
        }

        .status-inactive {
            background: rgba(220, 53, 69, 0.1);
            color: #c82333;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 20px;
            margin-bottom: 22px;
            padding: 16px;
            background: linear-gradient(135deg, #f8fcff 0%, #f0f9ff 100%);
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .meta-item .meta-label {
            display: block;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-mid);
            margin-bottom: 4px;
        }

        .meta-item .meta-value {
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--text-dark);
            word-break: break-word;
        }

        .meta-item.full { grid-column: 1 / -1; }

        .token-box {
            background: var(--text-dark);
            color: #fff;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .token-box .token-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            opacity: 0.75;
            margin-bottom: 10px;
        }

        .token-box .token-value {
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 1.15rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            word-break: break-all;
            line-height: 1.5;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .summary-table tr {
            border-bottom: 1px solid var(--border);
        }

        .summary-table tr:last-child {
            border-bottom: none;
            font-weight: 700;
            background: rgba(0, 180, 216, 0.06);
        }

        .summary-table td {
            padding: 12px 8px;
        }

        .summary-table td:first-child {
            color: var(--text-mid);
        }

        .summary-table td:last-child {
            text-align: right;
            font-weight: 600;
            color: var(--text-dark);
        }

        .receipt-footer {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px dashed var(--border);
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-mid);
            line-height: 1.5;
        }

        .receipt-footer strong {
            color: var(--aqua-deep);
        }

        .receipt-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .btn-receipt {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .btn-receipt:hover {
            transform: translateY(-1px);
        }

        .btn-print {
            background: linear-gradient(135deg, var(--aqua-deep), var(--aqua-primary));
            color: #fff;
            box-shadow: 0 4px 14px rgba(0, 119, 182, 0.35);
        }

        .btn-back {
            background: #fff;
            color: var(--aqua-deep);
            border: 1.5px solid var(--border);
        }

        .btn-back:hover {
            background: #f8fcff;
        }

        .zigzag {
            height: 12px;
            background:
                linear-gradient(135deg, #fff 25%, transparent 25%) -8px 0,
                linear-gradient(225deg, #fff 25%, transparent 25%) -8px 0,
                linear-gradient(315deg, #fff 25%, transparent 25%),
                linear-gradient(45deg, #fff 25%, transparent 25%);
            background-size: 16px 12px;
            background-color: #eef7fc;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .receipt-shell { max-width: 100%; }
            .receipt-container {
                box-shadow: none;
                border: none;
            }
            .receipt-actions, .btn-back { display: none !important; }
            .watermark { opacity: 0.15; }
        }

        @media (max-width: 480px) {
            .receipt-body { padding: 20px 18px; }
            .meta-grid { grid-template-columns: 1fr; }
            .receipt-top { flex-direction: column; }
            .receipt-badge { text-align: left; }
        }
    </style>
</head>
<body>
    <div class="receipt-shell">
        <div class="receipt-container" id="receipt-content">
            <div class="receipt-accent"></div>
            <div class="receipt-body">
                <div class="watermark" aria-hidden="true">AquaPay</div>

                <div class="receipt-top">
                    <div class="brand">
                        <img src="../images/icon.png" alt="AquaPay" class="receipt-logo">
                        <div>
                            <h1>AquaPay</h1>
                            <p>Water billing · Gulu City</p>
                        </div>
                    </div>
                    <div class="receipt-badge">
                        <div class="label">Receipt</div>
                        <div class="number"><?php echo htmlspecialchars($receiptNo); ?></div>
                        <span class="status-pill <?php echo $statusClass; ?>"><?php echo $tokenStatus; ?></span>
                    </div>
                </div>

                <div class="meta-grid">
                    <div class="meta-item">
                        <span class="meta-label">Client</span>
                        <span class="meta-value"><?php echo htmlspecialchars($row['name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Meter no.</span>
                        <span class="meta-value"><?php echo htmlspecialchars($row['meter_no']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Payment date</span>
                        <span class="meta-value"><?php echo htmlspecialchars($row['payment_date'] ?? $row['issue_date']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Payment status</span>
                        <span class="meta-value"><?php echo $paymentStatus; ?></span>
                    </div>
                    <div class="meta-item full">
                        <span class="meta-label">Address</span>
                        <span class="meta-value"><?php echo htmlspecialchars($row['address']); ?></span>
                    </div>
                </div>

                <div class="token-box">
                    <div class="token-label">Your water token</div>
                    <div class="token-value"><?php echo htmlspecialchars($row['token_value']); ?></div>
                </div>

                <table class="summary-table">
                    <tr>
                        <td>Amount paid</td>
                        <td>UGX <?php echo number_format((float)($row['amount'] ?? 0)); ?></td>
                    </tr>
                    <tr>
                        <td>Units credited</td>
                        <td><?php echo number_format((float)($row['units'] ?? 0), 2, '.', ''); ?></td>
                    </tr>
                    <tr>
                        <td>Token issued</td>
                        <td><?php echo htmlspecialchars($row['issue_date']); ?></td>
                    </tr>
                    <tr>
                        <td>Valid until</td>
                        <td><?php echo htmlspecialchars($row['expiry_date']); ?></td>
                    </tr>
                </table>

                <div class="receipt-footer">
                    <p>Thank you for using <strong>AquaPay</strong>.</p>
                    <p>support@aquapay.com · +256 783 953 940<br>Plot 10, Gulu City, Uganda</p>
                </div>
            </div>
            <div class="zigzag" aria-hidden="true"></div>
        </div>

        <div class="receipt-actions">
            <button type="button" class="btn-receipt btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print receipt
            </button>
            <button type="button" class="btn-receipt btn-back" onclick="window.close(); history.back();">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>
    </div>
</body>
</html>