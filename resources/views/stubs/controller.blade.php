namespace App\Controllers;

@if($useMedia)
use Govorun\Messaging\Media;
@endif
@if($useMessage)
use Govorun\Messaging\Message;
@endif
use Govorun\Routing\Controller;

class {{ $className }} extends Controller
{
    public function handle(): void
    {
{!! $blockCode !!}
    }
}
