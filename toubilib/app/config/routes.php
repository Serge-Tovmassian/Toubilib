<?php

declare(strict_types=1);

use toubilib\adapters\actions\AnnulerRendezVousAction;
use toubilib\adapters\actions\CreerRendezVousAction;
use toubilib\adapters\actions\ListerPraticiensAction;
use toubilib\adapters\actions\ObtenirPraticienAction;
use toubilib\adapters\actions\ObtenirRendezVousAction;

$app->get('/praticiens',ListerPraticiensAction::class);
$app->get('/praticiens/{id}', ObtenirPraticienAction::class);
$app->get('/praticiens/{id}/agenda', 'agenda.praticien');
$app->get('/praticiens/{id}/creneaux', 'creneaux.praticien');
$app->post('/rdvs', CreerRendezVousAction::class);
$app->get('/rdvs/{id}',ObtenirRendezVousAction::class);
$app->patch('/rdvs/{id}/annuler', AnnulerRendezVousAction::class);
$app->patch('/rdvs/{id}/honorer', 'honorer.rdv');
$app->patch('/rdvs/{id}/ignorer', 'ignorer.rdv');
