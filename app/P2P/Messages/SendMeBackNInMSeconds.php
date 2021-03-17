<?php


namespace App\P2P\Messages;


use App\Jobs\RunP2PTask;
use App\P2P\Tasks\WaitAndRespond;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class SendMeBackNInMSeconds
 * @package App\P2P\Messages
 * @property int $n
 * @property int $m
 */
class SendMeBackNInMSeconds extends P2PMessage
{

    const NAME = 'send_me_back_n_in_m_seconds';

    private $n;
    private $m;

    public function __construct(int $n, int $m, string $from, string $to)
    {
        $this->n = $n;
        $this->m = $m;
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @param Request $request
     * @return mixed|static
     */
    public static function fromRequest(Request $request)
    {
        $data = $request->validate([
            'sender' => ['required', 'url'],
            'n' => ['required', 'integer'],
            'm' => ['required', 'integer'],
        ]);
        return new static($data['n'], $data['m'], $data['sender'], config('app.url'));
    }

    /**
     *
     */
    public function getRequestData(): array
    {
        return [
            'message' => self::NAME,
            'n' => $this->n,
            'm' => $this->m
        ];
    }

    public function onMessageReceived()
    {

        Log::debug(config('app.url') . " > SendMeBackNInMSeconds request received from " . $this->from);

        RunP2PTask::dispatch(new WaitAndRespond(
            $this->n,
            $this->m,
            $this->to, // ME
            $this->from // Sender is now destination
        ));

        return true;

    }


}
