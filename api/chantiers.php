<?php

declare(strict_types=1);

function validate_chantier_input(array $body): array
{
    $nom        = trim((string)($body['nom'] ?? ''));
    $ville      = trim((string)($body['ville'] ?? ''));
    $entreprise = in_array($body['entreprise'] ?? '', ['Moncomble', 'RVM'], true) ? $body['entreprise'] : 'Moncomble';
    $couleur    = trim((string)($body['couleur'] ?? '#ADBF5E'));
    $creneaux   = is_array($body['creneaux'] ?? null) ? $body['creneaux'] : [];

    $errors = [];
    if ($nom === '') {
        $errors['nom'] = 'Nom requis';
    }
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $couleur)) {
        $couleur = '#ADBF5E';
    }

    if (!$creneaux) {
        $errors['creneaux'] = 'Au moins un créneau de date est requis';
    }

    $cleanCreneaux = [];
    foreach ($creneaux as $i => $c) {
        $date_debut = trim((string)($c['date_debut'] ?? ''));
        $date_fin   = trim((string)($c['date_fin'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_debut)) {
            $errors["creneaux.$i.date_debut"] = 'Date de début invalide';
            continue;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_fin)) {
            $errors["creneaux.$i.date_fin"] = 'Date de fin invalide';
            continue;
        }
        if ($date_fin < $date_debut) {
            $errors["creneaux.$i.date_fin"] = 'La date de fin doit être postérieure à la date de début';
            continue;
        }
        $cleanCreneaux[] = [$date_debut, $date_fin];
    }

    if ($errors) {
        json_error('Données invalides', 400, $errors);
    }

    return [$nom, $ville ?: null, $entreprise, $couleur, $cleanCreneaux];
}

function chantier_out(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT * FROM chantiers WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    $cStmt = $pdo->prepare('SELECT id, date_debut, date_fin FROM chantier_creneaux WHERE chantier_id = ? ORDER BY date_debut ASC');
    $cStmt->execute([$id]);

    return [
        'id'         => (int)$row['id'],
        'nom'        => $row['nom'],
        'ville'      => $row['ville'],
        'entreprise' => $row['entreprise'],
        'couleur'    => $row['couleur'],
        'creneaux' => array_map(fn($c) => [
            'id'         => (int)$c['id'],
            'date_debut' => $c['date_debut'],
            'date_fin'   => $c['date_fin'],
        ], $cStmt->fetchAll()),
    ];
}

function save_creneaux(PDO $pdo, int $chantierId, array $creneaux): void
{
    $pdo->prepare('DELETE FROM chantier_creneaux WHERE chantier_id = ?')->execute([$chantierId]);
    $stmt = $pdo->prepare('INSERT INTO chantier_creneaux (chantier_id, date_debut, date_fin) VALUES (?, ?, ?)');
    foreach ($creneaux as [$date_debut, $date_fin]) {
        $stmt->execute([$chantierId, $date_debut, $date_fin]);
    }
}

// GET /chantiers — liste tous les chantiers avec leurs créneaux
function handle_chantiers_list(string $method, array $params, ?array $user): void
{
    $pdo = get_pdo();
    $ids = $pdo->query(
        'SELECT c.id
         FROM chantiers c
         LEFT JOIN chantier_creneaux cc ON cc.chantier_id = c.id
         GROUP BY c.id
         ORDER BY MIN(cc.date_debut) ASC, c.created_at ASC'
    )->fetchAll(PDO::FETCH_COLUMN);
    json_success(array_map(fn($id) => chantier_out($pdo, (int)$id), $ids));
}

// POST /chantiers — crée un chantier avec ses créneaux
function handle_chantiers_create(string $method, array $params, ?array $user): void
{
    [$nom, $ville, $entreprise, $couleur, $creneaux] = validate_chantier_input(request_body());

    $pdo = get_pdo();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO chantiers (nom, ville, entreprise, couleur) VALUES (?, ?, ?, ?)')
            ->execute([$nom, $ville, $entreprise, $couleur]);
        $id = (int)$pdo->lastInsertId();
        save_creneaux($pdo, $id, $creneaux);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    json_success(chantier_out($pdo, $id), 'Chantier créé', 201);
}

// PUT /chantiers/{id} — met à jour un chantier et remplace ses créneaux
function handle_chantiers_update(string $method, array $params, ?array $user): void
{
    $id = (int)($params['id'] ?? 0);
    if (!$id) {
        json_error('Identifiant invalide', 400);
    }

    $pdo    = get_pdo();
    $exists = $pdo->prepare('SELECT id FROM chantiers WHERE id = ?');
    $exists->execute([$id]);
    if (!$exists->fetch()) {
        json_error('Chantier introuvable', 404);
    }

    [$nom, $ville, $entreprise, $couleur, $creneaux] = validate_chantier_input(request_body());

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE chantiers SET nom = ?, ville = ?, entreprise = ?, couleur = ? WHERE id = ?')
            ->execute([$nom, $ville, $entreprise, $couleur, $id]);
        save_creneaux($pdo, $id, $creneaux);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    json_success(chantier_out($pdo, $id), 'Chantier mis à jour');
}

// DELETE /chantiers/{id} — supprime un chantier (et ses créneaux via cascade)
function handle_chantiers_delete(string $method, array $params, ?array $user): void
{
    $id = (int)($params['id'] ?? 0);
    if (!$id) {
        json_error('Identifiant invalide', 400);
    }

    $pdo  = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM chantiers WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        json_error('Chantier introuvable', 404);
    }

    json_success(null, 'Chantier supprimé');
}
