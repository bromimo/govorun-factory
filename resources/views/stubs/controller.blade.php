namespace App\Controllers;

use Govorun\Framework\Controller;
use Govorun\Framework\Message;

class {{ $className }} extends Controller
{
    public function handle(): void
    {
{!! $blockCode !!}
    }
}
