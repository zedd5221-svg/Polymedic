<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X-Ray Result - <?= esc($examination['patient_name']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', serif;
            padding: 40px;
            background: #fff;
            color: #1a1a2e;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 40px;
            border-radius: 8px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0148ca;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            color: #0148ca;
            letter-spacing: 2px;
            margin: 0;
        }
        .header p {
            color: #666;
            font-size: 14px;
            margin: 5px 0 0;
        }
        .report-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            color: #0a2b4e;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .patient-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
            background: #f8faff;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .patient-info .item {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #e8edf5;
            padding: 5px 0;
        }
        .patient-info .item .label {
            font-weight: 600;
            color: #555;
        }
        .patient-info .item .value {
            color: #0a2b4e;
        }
        .findings-section {
            margin: 25px 0;
        }
        .findings-section h3 {
            font-size: 16px;
            color: #0148ca;
            border-bottom: 1px solid #0148ca;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .findings-section p {
            line-height: 1.8;
            font-size: 14px;
            text-align: justify;
            padding: 0 5px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #666;
        }
        .footer .signature {
            text-align: center;
        }
        .footer .signature .line {
            width: 200px;
            border-bottom: 1px solid #333;
            margin: 10px auto 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-badge.released {
            background: #ccfbf1;
            color: #0d9488;
        }
        @media print {
            body { padding: 20px; }
            .container { border: none; padding: 20px; }
            .no-print { display: none; }
        }
        @media (max-width: 600px) {
            .patient-info {
                grid-template-columns: 1fr;
            }
            .footer {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>PolyMedic</h1>
            <p>Diagnostic &amp; Laboratory Center</p>
            <p style="font-size: 12px; margin-top: 5px;">Gov. Gutierez Ave, Cotabato City 9600 | Tel: (064) 123-4567</p>
        </div>

        <div class="report-title">
            X-Ray Examination Report
            <span class="status-badge released" style="display: block; margin: 5px auto; width: fit-content;">
                Released
            </span>
        </div>

        <!-- Patient Information -->
        <div class="patient-info">
            <div class="item">
                <span class="label">Patient Name:</span>
                <span class="value"><?= esc($examination['patient_name']) ?></span>
            </div>
            <div class="item">
                <span class="label">Patient ID:</span>
                <span class="value"><?= $appointment['reference_number'] ?? 'N/A' ?></span>
            </div>
            <div class="item">
                <span class="label">Age / Sex:</span>
                <span class="value"><?= esc($examination['age']) ?> / <?= esc($examination['gender']) ?></span>
            </div>
            <div class="item">
                <span class="label">Exam Type:</span>
                <span class="value"><?= esc($examination['exam_type']) ?></span>
            </div>
            <div class="item">
                <span class="label">Exam Date:</span>
                <span class="value"><?= date('F d, Y', strtotime($examination['exam_date'])) ?></span>
            </div>
            <div class="item">
                <span class="label">Radiologist:</span>
                <span class="value"><?= $examination['radiologist_name'] ?? 'Dr. Maria Tan, MD' ?></span>
            </div>
            <div class="item">
                <span class="label">Priority:</span>
                <span class="value"><?= esc($examination['priority']) ?></span>
            </div>
            <div class="item">
                <span class="label">Released On:</span>
                <span class="value"><?= date('F d, Y h:i A', strtotime($examination['released_at'])) ?></span>
            </div>
        </div>

        <!-- Findings -->
        <div class="findings-section">
            <h3>Findings</h3>
            <p><?= nl2br(esc($examination['findings'])) ?></p>
        </div>

        <!-- Interpretation -->
        <div class="findings-section">
            <h3>Interpretation / Impression</h3>
            <p><?= nl2br(esc($examination['interpretation'])) ?></p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>
                <p><strong>Prepared by:</strong></p>
                <p style="font-size: 12px; color: #888;">Radiologist</p>
            </div>
            <div class="signature">
                <div class="line"></div>
                <p style="font-size: 12px;">Signature / Date</p>
            </div>
            <div>
                <p><strong>Generated:</strong></p>
                <p style="font-size: 12px; color: #888;"><?= date('F d, Y h:i A') ?></p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; font-size: 11px; color: #999;">
            <p>This is a computer-generated report. No signature required.</p>
            <p>PolyMedic Diagnostic &amp; Laboratory Center</p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px;" class="no-print">
        <button onclick="window.print()" style="padding: 10px 30px; background: #0148ca; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px;">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <button onclick="window.close()" style="padding: 10px 30px; background: #e2e8f0; color: #333; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; margin-left: 10px;">
            Close
        </button>
    </div>
</body>
</html>