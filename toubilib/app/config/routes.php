<?php

declare(strict_types=1);

use toubilib\adapters\actions\AnnulerRendezVousAction;

$app->patch('/rendez-vous/{id}/annulation', AnnulerRendezVousAction::class);
