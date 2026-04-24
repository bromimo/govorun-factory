namespace App\Controllers;

@if($useMedia)
use Govorun\Messaging\Media;
@endif
@if($useButton)
use Govorun\Messaging\Button;
@endif
@if($useMessage)
use Govorun\Messaging\Message;
@endif
@if($useKeyboard)
use Govorun\Messaging\Keyboard;
@endif
use Govorun\Routing\Controller;

class {{ $className }} extends Controller
{
    public function handle(): void
    {
{!! $blockCode !!}
    }
}
