namespace App\Controllers;

use App\Flows\{{ $flowClass }};
use Govorun\Routing\Controller;

class {{ $className }} extends Controller
{
    public function handle(): void
    {
        $this->startFlow({{ $flowClass }}::class);
    }
}
