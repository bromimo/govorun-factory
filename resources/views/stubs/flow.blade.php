namespace App\Flows;

use Govorun\Framework\Flow;
use Govorun\Framework\Message;

class {{ $className }} extends Flow
{
@if(!empty($interruptCommands))
    protected array $interruptCommands = {!! var_export($interruptCommands, true) !!};
@endif
@if($interruptOnEvent)
    protected bool $interruptOnEvent = true;
@endif

{!! $stepsCode !!}
}
