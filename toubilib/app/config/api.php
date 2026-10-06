<?php

declare(strict_types=1);

use toubilib\adapters\actions\AnnulerRendezVousAction;
use toubilib\adapters\actions\ListerPraticiensAction;
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
    ObtenirRendezVousAction::class => create(ObtenirRendezVousAction::class)
        ->constructor(get(ServiceRendezVousInterface::class)),
    AnnulerRendezVousAction::class => create(AnnulerRendezVousAction::class)
        ->constructor(get(ServiceRendezVousInterface::class)),
];
