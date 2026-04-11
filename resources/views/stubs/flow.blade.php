namespace App\Flows;

use Govorun\State\Flow;
use Govorun\State\Step;
use Govorun\Messaging\Message;
use Govorun\Messaging\Keyboard;
use Govorun\Messaging\IncomingMessage;

class {{ $className }} extends Flow
{
    protected array $steps = [{!! $stepsList !!}];
@if(!empty($interruptCommands))

    protected array $interruptCommands = {!! var_export($interruptCommands, true) !!};
@endif
@if($interruptOnEvent)

    protected bool $interruptOnEvent = true;
@endif
{!! $stepMethods !!}{!! $onCompleteMethod !!}{!! $onCancelMethod !!}}
