namespace App\Flows;

use Govorun\State\Flow;
use Govorun\Messaging\Message;

class {{ $className }} extends Flow
{
@if(!empty($interruptCommands))
    protected array $interruptCommands = {!! var_export($interruptCommands, true) !!};

@endif
@if($interruptOnEvent)
    protected bool $interruptOnEvent = true;

@endif
    public function handle(): void
    {
{!! $stepsCode !!}    }
}
