<?php
function generateCertificateHTML($data) {
    // Prepare logos
    $ensaLogoPath = realpath('../Circle Logo.jpg');
    $clubLogoPath = !empty($data['logo']) && file_exists('../' . $data['logo']) ? realpath('../' . $data['logo']) : null;

    $ensaLogoBase64 = '';
    if ($ensaLogoPath && file_exists($ensaLogoPath)) {
        $imageData = base64_encode(file_get_contents($ensaLogoPath));
        $ensaLogoBase64 = 'data:image/jpeg;base64,' . $imageData;
    }

    $clubLogoBase64 = '';
    if ($clubLogoPath && file_exists($clubLogoPath)) {
        $ext = pathinfo($clubLogoPath, PATHINFO_EXTENSION);
        $imageData = base64_encode(file_get_contents($clubLogoPath));
        $clubLogoBase64 = 'data:image/' . $ext . ';base64,' . $imageData;
    }

    // Format year display
    $anneeText = $data['annee'] ? $data['annee'] . 'ème année' : '';

    // Certificate HTML template
    $html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: "Georgia", "Times New Roman", Times, serif;
            background: #fff;
        }
        .certificate {
            width: 297mm;
            height: 210mm;
            background: white;
            position: relative;
            padding: 15mm;
            box-sizing: border-box;
        }
        .border-outer {
            position: absolute;
            top: 10mm;
            left: 10mm;
            right: 10mm;
            bottom: 10mm;
            border: 3px solid #1e3a8a;
        }
        .border-inner {
            position: absolute;
            top: 12mm;
            left: 12mm;
            right: 12mm;
            bottom: 12mm;
            border: 1px solid #3b82f6;
        }
        .logo-container {
            position: relative;
            height: 80px;
            margin-bottom: 10px;
        }
        .logo-left {
            position: absolute;
            left: 0;
            top: 0;
            height: 70px;
            width: auto;
        }
        .logo-right {
            position: absolute;
            right: 0;
            top: 0;
            height: 70px;
            width: auto;
        }
        .header {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .certificate-title {
            font-size: 42px;
            color: #1e3a8a;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
            letter-spacing: 4px;
        }
        .subtitle {
            font-size: 20px;
            color: #3b82f6;
            margin: 5px 0;
            font-style: italic;
        }
        .content {
            text-align: center;
            margin: 20px auto;
            padding: 0 50px;
            max-width: 900px;
        }
        .content p {
            font-size: 16px;
            line-height: 1.6;
            color: #1f2937;
            margin: 8px 0;
        }
        .student-name {
            font-size: 30px;
            color: #1e3a8a;
            font-weight: bold;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .student-info {
            font-size: 17px;
            color: #374151;
            margin: 10px 0;
        }
        .event-name {
            font-size: 24px;
            color: #3b82f6;
            font-weight: bold;
            margin: 15px 0;
            font-style: italic;
        }
        .event-details {
            font-size: 15px;
            color: #4b5563;
            margin: 15px 0;
            line-height: 1.8;
        }
        .signatures {
            position: absolute;
            bottom: 25mm;
            left: 50px;
            right: 50px;
            display: table;
            width: calc(100% - 100px);
        }
        .signature-block {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 2px solid #1f2937;
            width: 200px;
            margin: 0 auto 8px;
        }
        .signature-label {
            font-size: 13px;
            color: #4b5563;
            font-weight: bold;
        }
        .footer {
            position: absolute;
            bottom: 12mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 11px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="border-outer"></div>
        <div class="border-inner"></div>
        
        <div class="logo-container">';

    if ($ensaLogoBase64) {
        $html .= '<img src="' . $ensaLogoBase64 . '" class="logo-left" alt="ENSA Logo">';
    }

    if ($clubLogoBase64) {
        $html .= '<img src="' . $clubLogoBase64 . '" class="logo-right" alt="Club Logo">';
    }

    $html .= '</div>
        
        <div class="header">
            <div class="certificate-title">Attestation</div>
            <div class="subtitle">de Participation</div>
        </div>
        
        <div class="content">
            <p>La présente atteste que</p>
            
            <div class="student-name">' . strtoupper(htmlspecialchars($data['prenom'] . ' ' . $data['nom'])) . '</div>
            
            <div class="student-info">' . $anneeText . ' ' . htmlspecialchars($data['filiere'] ?? '') . '</div>
            
            <p>a participé avec succès à l\'événement</p>
            
            <div class="event-name">"' . htmlspecialchars($data['nomEvent']) . '"</div>
            
            <div class="event-details">
                Organisé par <strong>' . htmlspecialchars($data['clubNom']) . '</strong><br>
                Lieu: ' . htmlspecialchars($data['lieu']) . '<br>
                Du ' . date('d/m/Y', strtotime($data['dateDepart'])) . ' au ' . date('d/m/Y', strtotime($data['dateFin'])) . '
            </div>
        </div>
        
        <div class="signatures">
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-label">Signature de l\'Étudiant</div>
            </div>
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-label">Signature de l\'Organisateur</div>
            </div>
        </div>
        
        <div class="footer">
            Délivré le ' . date('d/m/Y') . ' - ' . htmlspecialchars($data['clubNom']) . '
        </div>
    </div>
</body>
</html>
';

    return $html;
}
?>

