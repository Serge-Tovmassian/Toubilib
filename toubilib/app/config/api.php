<?php

declare(strict_types=1);

use toubilib\adapters\actions\AnnulerRendezVousAction;
use toubilib\adapters\actions\ChangerEtatRendezVousAction;
use toubilib\adapters\actions\CreerRendezVousAction;
use toubilib\adapters\actions\DisponibilitesPraticienAction;
use toubilib\adapters\actions\HistoriquePatientAction;
use toubilib\adapters\actions\ListerPraticiensAction;
use toubilib\adapters\actions\ListerRendezVousPraticienAction;
use toubilib\adapters\actions\ObtenirPraticienAction;
use toubilib\adapters\actions\ObtenirRendezVousAction;
use toubilib\application\ports\api\ServicePraticienInterface;
use toubilib\application\ports\api\ServiceRendezVousInterface;

use function DI\create;
use function DI\get;

return [
    ListerPraticiensAction::class => create(ListerPraticiensAction::class)
        ->constructor(get(ServicePraticienInterface::class)),
    ObtenirPraticienAction::class => create(ObtenirPraticienAction::class)
        ->constructor(get(ServicePraticienInterface::class)),
    'agenda.praticien' => create(ListerRendezVousPraticienAction::class)
        ->constructor(get(ServiceRendezVousInterface::class), false),
    'creneaux.praticien' => create(ListerRendezVousPraticienAction::class)
        ->constructor(get(ServiceRendezVousInterface::class), true),
    'honorer.rdv' => create(ChangerEtatRendezVousAction::class)
        ->constructor(get(ServiceRendezVousInterface::class), true),
    'ignorer.rdv' => create(ChangerEtatRendezVousAction::class)
        ->constructor(get(ServiceRendezVousInterface::class), false),
    DisponibilitesPraticienAction::class => create(DisponibilitesPraticienAction::class)
        ->constructor(get(ServicePraticienInterface::class)),
    HistoriquePatientAction::class => create(HistoriquePatientAction::class)
        ->constructor(get(ServiceRendezVousInterface::class)),
    CreerRendezVousAction::class => create(CreerRendezVousAction::class)
        ->constructor(get(ServiceRendezVousInterface::class)),
    ObtenirRendezVousAction::class =>create(ObtenirRendezVousAction::class)
        ->constructor(get(ServiceRendezVousInterface::class)),
    AnnulerRendezVousAction::class => create(AnnulerRendezVousAction::class)
        ->constructor(get(ServiceRendezVousInterface::class)),
];
