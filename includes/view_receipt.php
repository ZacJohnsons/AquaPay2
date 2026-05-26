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
$sql = "SELECT t.token_id, t.token_value, t.units, t.issue_date, t.expiry_date, p.amount, p.payment_date, c.name, c.meter_no, c.address
        FROM tokens t
        JOIN payments p ON p.client_id = t.client_id
        JOIN client_information c ON c.client_id = t.client_id
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AquaPay Receipt</title>
    <!--Favicon-->
    <link rel="icon" type="image/png" href="../images/icon.png">
    <!-- FontAwesome for icons -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">

    <style>
        body {
            background: #eaf6fb;
            position: relative;
        }
        .receipt-container {
            max-width: 550px;
            min-height: 700px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,123,255,0.12);
            padding: 32px 24px 24px 24px;
            font-family: 'Segoe UI', Arial, sans-serif;
            position: relative;
            overflow: hidden;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-20deg);
            font-size: 60px;
            color: #e3f2fd;
            font-weight: bold;
            opacity: 0.25;
            pointer-events: none;
            z-index: 0;
            user-select: none;
        }
        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }
        .logo-section {
            text-align: center;
            flex: 0 0 110px;
        }
        .receipt-logo {
            width: 70px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 1.5em;
            font-weight: 700;
            color: #007bff;
            margin-bottom: 0;
        }
        .company-details {
            text-align: right;
            font-size: 0.98em;
            color: #444;
            flex: 1;
            margin-left: 16px;
        }
        .company-details p {
            margin: 2px 0;
        }
        .receipt-info {
            background: #f1f8ff;
            border-radius: 8px;
            padding: 16px 12px;
            margin-bottom: 18px;
            box-shadow: 0 1px 4px rgba(0,123,255,0.07);
            z-index: 1;
            position: relative;
        }
        .receipt-info p {
            margin: 6px 0;
            font-size: 1.05em;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,123,255,0.07);
            z-index: 1;
            position: relative;
        }
        .receipt-table th, .receipt-table td {
            border: 1px solid #e3f2fd;
            padding: 10px 8px;
            text-align: center;
        }
        .receipt-table th {
            background: #007bff;
            color: #fff;
            font-weight: 600;
        }
        .receipt-table td {
            background: #f9fbfd;
        }
        .receipt-actions {
            text-align: right;
            margin-top: 18px;
            z-index: 1;
            position: relative;
        }
        .btn {
            padding: 8px 18px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            background: #007bff;
            color: #fff;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn.download {
            background: #28a745;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn.download:hover {
            background: #218838;
        }
        @media print {
            body {
                background: #fff !important;
            }
            .receipt-container {
                box-shadow: none !important;
                border: none !important;
            }
            .receipt-actions { display: none; }
            .watermark { opacity: 0.12; }
        }
    </style>
</head>
<body>
    <div class="receipt-container" id="receipt-content">
        <div class="watermark">AquaPay</div>
        <div class="receipt-header">
            <div class="logo-section">
                <img src="../images/icon.png" alt="AquaPay Logo" class="receipt-logo">
                <div class="company-name">AquaPay</div>
            </div>
            <div class="company-details">
                <p>Plot 10, Gulu City, Uganda</p>
                <p>support@aquapay.com</p>
                <p>+256 783 953 940</p>
            </div>
        </div>
        <div class="receipt-info">
            <p><strong>Receipt No:</strong> RCPT-<?php echo str_pad($row['token_id'], 6, "0", STR_PAD_LEFT); ?></p>
            <p><strong>Date of Payment:</strong> <?php echo htmlspecialchars($row['payment_date']); ?></p>
            <p><strong>Client Name:</strong> <?php echo htmlspecialchars($row['name']); ?></p>
            <p><strong>Meter No:</strong> <?php echo htmlspecialchars($row['meter_no']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
        </div>
        <table class="receipt-table">
            <tr>
                <th>Amount Paid</th>
                <th>Token</th>
                <th>Units</th>
                <th>Issue Date</th>
            </tr>
            <tr>
                <td>UGX <?php echo number_format($row['amount']); ?></td>
                <td><?php echo htmlspecialchars($row['token_value']); ?></td>
                <td><?php echo htmlspecialchars($row['units']); ?></td>
                <td><?php echo htmlspecialchars($row['issue_date']); ?></td>
            </tr>
        </table>
        <div class="receipt-actions">
            <button class="btn" onclick="window.print()">Print</button>
            <!-- <button class="btn download" onclick="downloadPDF()">Download</button> -->
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- <script>
        function downloadPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: "portrait",
                unit: "pt",
                format: [550, 700]
            });
            doc.html(document.getElementById('receipt-content'), {
                callback: function (pdf) {
                    pdf.save('AquaPay_Receipt_<?php echo str_pad($row['token_id'], 6, "0", STR_PAD_LEFT); ?>.pdf');
                },
                x: 10,
                y: 10,
                html2canvas: { scale: 1 }
            });
        }
    </script> -->
</body>
</html>