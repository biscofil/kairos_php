<?php


namespace App\P2P\Messages;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class TakeBackN
 * @package App\P2P\Messages
 * @property int $n
 * @property int $m
 */
class TakeBackN extends P2PMessage
{

    const NAME = 'take_back_n_in_m_seconds';

    private $n;
    private $m;

    public function __construct(int $n, int $m, string $from, string $to)
    {
        $this->from = $from;
        $this->to = $to;
        $this->n = $n;
        $this->m = $m;
    }

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
        // TODO call job for task
        Log::debug(config('app.url') . " > TakeBackN message received from " . $this->from);
        Log::debug(config('app.url') . " > " . $this->n);
        return true;
    }


}
