namespace App\Controllers;

use Govorun\Messaging\Media;
use Govorun\Messaging\Message;
use Govorun\Routing\Controller;

class {{ $className }} extends Controller
{
    public function handle(): void
    {
{!! $blockCode !!}
    }
}
