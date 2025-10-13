<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: 1920px 1080px;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Georgia', 'Times New Roman', Times, serif;
            background: #ffffff;
            width: 1920px;
            height: 1080px;
            position: relative;
            overflow: hidden;
        }
        .certificate {
            width: 100%;
            height: 100%;
            padding: 60px 80px;
            position: relative;
        }
        
        /* Borders */
        .border-outer {
            position: absolute;
            top: 40px;
            left: 40px;
            right: 40px;
            bottom: 40px;
            border: 6px solid #1e3a8a;
        }
        .border-inner {
            position: absolute;
            top: 50px;
            left: 50px;
            right: 50px;
            bottom: 50px;
            border: 2px solid #3b82f6;
        }
        
        /* Header with logos */
        .header-logos {
            position: relative;
            height: 120px;
            margin-bottom: 30px;
            z-index: 10;
        }
        .logo-left, .logo-right {
            position: absolute;
            top: 0;
            max-height: 110px;
            max-width: 180px;
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
            margin-bottom: 40px;
        }
        .certificate-title {
            font-size: 72px;
            color: #1e3a8a;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 8px;
            margin-bottom: 15px;
        }
        .certificate-subtitle {
            font-size: 36px;
            color: #3b82f6;
            font-style: italic;
            letter-spacing: 3px;
        }
        
        /* Content section */
        .content {
            text-align: center;
            margin: 50px auto;
            padding: 0 120px;
        }
        .content p {
            font-size: 28px;
            line-height: 1.8;
            color: #1f2937;
            margin: 20px 0;
        }
        .student-name {
            font-size: 52px;
            color: #1e3a8a;
            font-weight: bold;
            margin: 30px 0;
            text-transform: uppercase;
            letter-spacing: 4px;
            text-decoration: underline;
            text-decoration-thickness: 3px;
            text-underline-offset: 8px;
        }
        .student-info {
            font-size: 30px;
            color: #374151;
            margin: 20px 0;
            font-weight: 500;
        }
        .event-name {
            font-size: 42px;
            color: #3b82f6;
            font-weight: bold;
            margin: 30px 0;
            font-style: italic;
        }
        .event-details {
            font-size: 26px;
            color: #4b5563;
            line-height: 2;
            margin: 30px 0;
        }
        .event-details strong {
            color: #1e3a8a;
        }
        
        /* Signatures section */
        .signatures {
            position: absolute;
            bottom: 120px;
            left: 120px;
            right: 120px;
            display: table;
            width: calc(100% - 240px);
        }
        .signature-block {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 3px solid #1f2937;
            width: 400px;
            margin: 0 auto 15px;
        }
        .signature-label {
            font-size: 24px;
            color: #4b5563;
            font-weight: bold;
        }
        
        /* Footer */
        .footer {
            position: absolute;
            bottom: 50px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 20px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="border-outer"></div>
        <div class="border-inner"></div>
        
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
            <p>La présente atteste que</p>
            
            <div class="student-name"><?= htmlspecialchars($participantData['prenom'] . ' ' . $participantData['nom']) ?></div>
            
            <div class="student-info">
                <?php if (!empty($participantData['annee'])): ?>
                    <?= htmlspecialchars($participantData['annee']) ?>ème année
                <?php endif; ?>
                <?php if (!empty($participantData['filiere'])): ?>
                    <?= htmlspecialchars($participantData['filiere']) ?>
                <?php endif; ?>
            </div>
            
            <p>a participé avec succès à l'événement</p>
            
            <div class="event-name">"<?= htmlspecialchars($event['nomEvent']) ?>"</div>
            
            <div class="event-details">
                <strong>Catégorie :</strong> <?= htmlspecialchars($event['categorie'] ?? 'N/A') ?><br>
                <strong>Lieu :</strong> <?= htmlspecialchars($event['lieu']) ?><br>
                <strong>Date :</strong> Du <?= date('d/m/Y', strtotime($event['dateDepart'])) ?> au <?= date('d/m/Y', strtotime($event['dateFin'])) ?>
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
</body>
</html>

