<?php

declare(strict_types=1);

namespace toubilib\domain\entities;

enum EtatRendezVous: int
{
    case ANNULE = -1;
    case PLANIFIE = 0;
    case HONORE = 1;
    case NON_HONORE = 2;
}
