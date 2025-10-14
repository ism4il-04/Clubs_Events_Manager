<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Georgia', 'Times New Roman', Times, serif;
            background: #ffffff;
            margin: 0;
            padding: 5mm 10mm;
        }
        .certificate {
            width: 100%;
            height: auto;
            position: relative;
        }
        
        /* Borders */
        .border-outer {
            border: 3px solid #1e3a8a;
            padding: 3mm;
        }
        .border-inner {
            border: 1px solid #3b82f6;
            padding: 2mm;
        }
        
        /* Header with logos */
        .header-logos {
            position: relative;
            height: 45px;
            margin-bottom: 5px;
            z-index: 10;
        }
        .logo-left, .logo-right {
            position: absolute;
            top: 0;
            max-height: 45px;
            max-width: 75px;
        }
        .logo-left {
            left: 0;
        }
        .logo-right {
            right: 0;
        }
        
        /* Title section */
        .title-section {
            text-align: center;
            margin-bottom: 8px;
        }
        .certificate-title {
            font-size: 36px;
            color: #1e3a8a;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        .certificate-subtitle {
            font-size: 18px;
            color: #3b82f6;
            font-style: italic;
            letter-spacing: 1px;
        }
        
        /* Content section */
        .content {
            text-align: center;
            margin: 10px auto;
            padding: 0 40px;
        }
        .content p {
            font-size: 14px;
            line-height: 1.4;
            color: #1f2937;
            margin: 6px 0;
        }
        .student-name {
            font-size: 24px;
            color: #1e3a8a;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-decoration: underline;
            text-decoration-thickness: 2px;
            text-underline-offset: 4px;
        }
        .student-info {
            font-size: 15px;
            color: #374151;
            margin: 8px 0;
            font-weight: 500;
        }
        .event-name {
            font-size: 20px;
            color: #3b82f6;
            font-weight: bold;
            margin: 10px 0;
            font-style: italic;
        }
        .event-details {
            font-size: 13px;
            color: #4b5563;
            line-height: 1.6;
            margin: 10px 0;
        }
        .event-details strong {
            color: #1e3a8a;
        }
        
        /* Signatures section */
        .signatures {
            width: 100%;
            margin-top: 20px;
            margin-bottom: 10px;
            display: table;
        }
        .signature-block {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .signature-line {
            border-top: 2px solid #1f2937;
            width: 150px;
            margin: 0 auto 6px;
        }
        .signature-label {
            font-size: 11px;
            color: #4b5563;
            font-weight: bold;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="border-outer">
            <div class="border-inner">
                <!-- Header with logos -->
                <div class="header-logos">
                    <?php if (!empty($logoEcoleBase64)): ?>
                        <img src="<?= $logoEcoleBase64 ?>" class="logo-left" alt="ENSA Logo">
                    <?php endif; ?>
                    <?php if (!empty($logoClubBase64)): ?>
                        <img src="<?= $logoClubBase64 ?>" class="logo-right" alt="Club Logo">
                    <?php endif; ?>
                </div>
                
                <!-- Title -->
                <div class="title-section">
                    <div class="certificate-title">Attestation</div>
                    <div class="certificate-subtitle">de Participation</div>
                </div>
                
                <!-- Content -->
                <div class="content">
                    <p><?= htmlspecialchars($organisateurName) ?> atteste que</p>
                    
                    <div class="student-name"><?= htmlspecialchars($participantData['prenom'] . ' ' . $participantData['nom']) ?></div>
                    
                    <div class="student-info">
                        <?php if (!empty($participantData['annee'])): ?>
                            <?= htmlspecialchars($participantData['annee']) ?> année
                        <?php endif; ?>
                        <?php if (!empty($participantData['filiere'])): ?>
                            <?= htmlspecialchars($participantData['filiere']) ?>
                        <?php endif; ?>
                    </div>
                    
                    <p>a participé avec succès à l'événement</p>
                    
                    <div class="event-name">"<?= htmlspecialchars($event['nomEvent']) ?>"</div>
                    
                    <div class="event-details">
                        <strong>Lieu :</strong> <?= htmlspecialchars($event['lieu']) ?><br>
                        <?php if ($event['dateDepart'] != $event['dateFin']): ?>
                            <strong>Date :</strong> Du <?= date('d/m/Y', strtotime($event['dateDepart'])) ?> au <?= date('d/m/Y', strtotime($event['dateFin'])) ?>
                        <?php else: ?>
                            <strong>Le :</strong> <?= date('d/m/Y', strtotime($event['dateDepart'])) ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Signatures -->
                <div class="signatures">
                    <div class="signature-block">
                        <div class="signature-line"></div>
                        <div class="signature-label">Signature de l'Étudiant</div>
                    </div>
                    <div class="signature-block">
                        <div class="signature-line"></div>
                        <div class="signature-label">Signature de l'Organisateur</div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="footer">
                    Délivré le <?= date('d/m/Y') ?> - <?= htmlspecialchars($organisateurName) ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

