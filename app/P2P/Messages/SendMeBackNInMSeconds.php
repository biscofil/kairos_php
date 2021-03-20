<?php


namespace App\P2P\Messages;


use App\Jobs\RunP2PTask;
use App\P2P\Tasks\WaitAndRespond;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class SendMeBackNInMSeconds
 * @package App\P2P\Messages
 * @property int $n
 * @property int $m
 */
class SendMeBackNInMSeconds extends P2PMessage
{

    protected $name = 'send_me_back_n_in_m_seconds';

    private $n;
    private $m;

    public function __construct(int $n, int $m, string $from, string $to)
    {
        parent::__construct($from, $to);
        $this->n = $n;
        $this->m = $m;
    }

    /**
     * @param array $messageData
     * @return static
     * @throws ValidationException
     * @throws \Exception
     */
    public static function fromRequest(array $messageData): P2PMessage
    {
        $data = Validator::make($messageData, [
            'n' => ['required', 'integer'],
            'm' => ['required', 'integer'],
        ])->validated();

        return new static($data['n'], $data['m'], $messageData['sender'], config('app.url'));
    }

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return [
            'n' => $this->n,
            'm' => $this->m
        ];
    }

    /**
     * @return P2PMessage|null
     */
    public function onMessageReceived(): ?P2PMessage
    {

        Log::debug(config('app.url') . " > SendMeBackNInMSeconds request received from " . $this->from);

        RunP2PTask::dispatch(new WaitAndRespond(
            $this->n,
            $this->m,
            $this->to, // ME
            $this->from // Sender is now destination
        ));

        return $this->getDefaultResponse();

    }


}
