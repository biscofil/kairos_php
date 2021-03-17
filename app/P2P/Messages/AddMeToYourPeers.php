<?php


namespace App\P2P\Messages;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class AddMeToYourPeers
 * @package App\P2P\Messages
 */
class AddMeToYourPeers extends P2PMessage
{

    const NAME = 'add_me_to_your_peers';

    public function __construct(string $from, string $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public static function fromRequest(Request $request)
    {
        $data = $request->validate([
            'sender' => ['required', 'url'],
        ]);
        return new static($data['sender'], config('app.url'));
    }

    /**
     *
     */
    public function getRequestData(): array
    {
        return [
            'message' => self::NAME
        ];
    }

    public function onMessageReceived()
    {
        Log::debug(config('app.url') . " > Hello message received from " . $this->from);
        return true;
    }


}
