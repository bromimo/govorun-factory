<?php

namespace App\Controllers\Commands;

use Govorun\Messaging\Message;
use Govorun\Routing\Controller;

class CommandStartController extends Controller
{
    public function handle(): void
    {
        $response = $this->apiCall('GET', '');
        $this->send(
                    Message::make(
                        'Сегодня в Днепре температура '
                        . $this->state->get('temperature')
                        . $this->state->get('degrize')
                        . ', ветер '
                        . $this->state->get('wind_speed')
                        . $this->state->get('km_per_hour')
                        . '.'
                    )
                        ->parseMode('HTML')
                );

    }
}
