<?php

declare(strict_types=1);

use toubilib\adapters\actions\AnnulerRendezVousAction;
use toubilib\adapters\actions\ListerPraticiensAction;
use toubilib\adapters\actions\ObtenirPraticienAction;
use toubilib\adapters\actions\ObtenirRendezVousAction;

$app->get('/praticiens', ListerPraticiensAction::class);
$app->get('/praticiens/{id}', ObtenirPraticienAction::class);
$app->get('/rdvs/{id}', ObtenirRendezVousAction::class);
$app->patch('/rendez-vous/{id}/annulation', AnnulerRendezVousAction::class);
