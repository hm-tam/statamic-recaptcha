<?php

namespace Statamic\Addons\Recaptcha;

use GuzzleHttp\Client;
use Statamic\Extend\Listener;
use Statamic\Contracts\Forms\Submission;

class RecaptchaListener extends Listener
{
    /**
     * The events to be listened for, and the methods to call.
     *
     * @var array
     */
    public $events = [
        'Form.submission.creating' => 'beforeCreate'
    ];

    public function beforeCreate(Submission $submission)
    {
        if (! $response = request('g-recaptcha-response')) {
            return $submission;
        }

        $client = new Client();

        $params = [
            'secret' => $this->getConfig('secret') ?: env('RECAPTCHA_SECRET', ''),
            'response' => $response
        ];

        $response = $client->post('https://www.google.com/recaptcha/api/siteverify', ['query' => $params]);
        
        if ($response->getStatusCode() == 200) {
            $data = collect(json_decode($response->getBody(), true));
        } else {
            throw new \Exception($response->getReasonPhrase());
        }

        if (! $data->get('success')) {
            return [
                'submission' => $submission,
                'errors' => [$this->getConfig('error_message', 'reCAPTCHA failed.')]
            ];
        }

        return $submission;
    }
}