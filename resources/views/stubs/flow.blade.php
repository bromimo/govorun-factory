namespace App\Flows;

use Govorun\State\Flow;
use Govorun\State\Step;
@if($useMedia)
use Govorun\Messaging\Media;
@endif
@if($useMessage)
use Govorun\Messaging\Message;
@endif
@if($useButton)
use Govorun\Messaging\Button;
@endif
@if($useKeyboard)
use Govorun\Messaging\Keyboard;
@endif
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
