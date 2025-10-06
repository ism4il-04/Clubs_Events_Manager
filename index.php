<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            color: white;
            font-size: 2.5rem;
            margin-bottom: 40px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .event-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        .event-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            color: white;
        }

        .event-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .event-category {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .event-body {
            padding: 20px;
        }

        .event-info {
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #555;
        }

        .info-icon {
            width: 20px;
            margin-right: 10px;
            color: #667eea;
        }

        .event-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            max-height: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .event-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .status-valide {
            background: #d4edda;
            color: #155724;
        }

        .status-attente {
            background: #fff3cd;
            color: #856404;
        }

        .status-annule {
            background: #f8d7da;
            color: #721c24;
        }

        .event-footer {
            border-top: 1px solid #eee;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .places-info {
            color: #667eea;
            font-weight: bold;
        }

        .participate-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .participate-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .participate-btn:active {
            transform: scale(0.98);
        }

        .participate-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }

        .no-events {
            text-align: center;
            color: white;
            font-size: 1.5rem;
            margin-top: 50px;
        }

        @media (max-width: 768px) {
            .events-grid {
                grid-template-columns: 1fr;
            }

            h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>📅 Événements Disponibles</h1>

    <div class="events-grid">
        <?php
        require_once './connexion.php';
        $events = $conn->query("SELECT * FROM evenements");
        $eventsList = $events->fetchAll();

        if (count($eventsList) > 0) {
            foreach ($eventsList as $event) {
                // Determine status class
                $statusClass = 'status-attente';
                if (strtolower($event['status']) == 'validé' || strtolower($event['status']) == 'valide') {
                    $statusClass = 'status-valide';
                } elseif (strtolower($event['status']) == 'annulé' || strtolower($event['status']) == 'annule') {
                    $statusClass = 'status-annule';
                }

                // Check if places available
                $placesAvailable = $event['places'] > 0;
                ?>

                <div class="event-card">
                    <div class="event-header">
                        <div class="event-title"><?php echo htmlspecialchars($event['nom']); ?></div>
                        <?php if (!empty($event['categorie'])) { ?>
                            <span class="event-category"><?php echo htmlspecialchars($event['categorie']); ?></span>
                        <?php } ?>
                    </div>

                    <div class="event-body">
                        <div class="event-info">
                            <div class="info-row">
                                <span class="info-icon">📍</span>
                                <span><?php echo htmlspecialchars($event['lieu']); ?></span>
                            </div>

                            <div class="info-row">
                                <span class="info-icon">📅</span>
                                <span>
                                <?php
                                echo date('d/m/Y', strtotime($event['dateDepart']));
                                if ($event['dateFin'] && $event['dateFin'] != $event['dateDepart']) {
                                    echo ' - ' . date('d/m/Y', strtotime($event['dateFin']));
                                }
                                ?>
                            </span>
                            </div>

                            <div class="info-row">
                                <span class="info-icon">⏰</span>
                                <span>
                                <?php
                                echo date('H:i', strtotime($event['heureDepart']));
                                if ($event['heureFin']) {
                                    echo ' - ' . date('H:i', strtotime($event['heureFin']));
                                }
                                ?>
                            </span>
                            </div>
                        </div>

                        <?php if (!empty($event['description'])) { ?>
                            <div class="event-description">
                                <?php echo htmlspecialchars($event['description']); ?>
                            </div>
                        <?php } ?>

                        <span class="event-status <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($event['status']); ?>
                    </span>
                    </div>

                    <div class="event-footer">
                        <div class="places-info">
                            <?php echo $event['places']; ?> places disponibles
                        </div>
                        <form method="POST" action="participer.php" style="margin: 0;">
                            <input type="hidden" name="evenement_id" value="<?php echo $event['id']; ?>">
                            <button
                                type="submit"
                                class="participate-btn"
                                <?php echo !$placesAvailable ? 'disabled' : ''; ?>
                            >
                                <?php echo $placesAvailable ? 'Participer' : 'Complet'; ?>
                            </button>
                        </form>
                    </div>
                </div>

                <?php
            }
        } else {
            ?>
            <div class="no-events">
                Aucun événement disponible pour le moment.
            </div>
            <?php
        }
        ?>
    </div>
</div>
</body>
</html>