<?php

declare(strict_types=1);

use toubilib\adapters\actions\AnnulerRendezVousAction;
use toubilib\adapters\actions\CreerRendezVousAction;
use toubilib\adapters\actions\DeclarerIndisponibiliteAction;
use toubilib\adapters\actions\DisponibilitesPraticienAction;
use toubilib\adapters\actions\HistoriquePatientAction;
use toubilib\adapters\actions\ListerPraticiensAction;
use toubilib\adapters\actions\LoginAction;
use toubilib\adapters\actions\ObtenirPraticienAction;
use toubilib\adapters\actions\ObtenirRendezVousAction;

$app->post('/auth/login', LoginAction::class);
$app->get('/praticiens',ListerPraticiensAction::class);
$app->get('/praticiens/{id}', ObtenirPraticienAction::class);
$app->get('/praticiens/{id}/disponibilites', DisponibilitesPraticienAction::class);
$app->post('/praticiens/{id}/indisponibilites', DeclarerIndisponibiliteAction::class)->add('acces.praticien');
$app->get('/praticiens/{id}/agenda', 'agenda.praticien')->add('acces.praticien');
$app->get('/praticiens/{id}/creneaux', 'creneaux.praticien')->add('acces.praticien');
$app->get('/patients/{id}/rdvs', HistoriquePatientAction::class)->add('acces.patient');
$app->post('/rdvs',CreerRendezVousAction::class);
$app->get('/rdvs/{id}',ObtenirRendezVousAction::class)->add('acces.rdv.concerne');
$app->patch('/rdvs/{id}/annuler', AnnulerRendezVousAction::class)->add('acces.rdv.concerne');
$app->patch('/rdvs/{id}/honorer', 'honorer.rdv')->add('acces.rdv.praticien');
$app->patch('/rdvs/{id}/ignorer', 'ignorer.rdv')->add('acces.rdv.praticien');
