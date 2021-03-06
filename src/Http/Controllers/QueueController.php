<?php

namespace LaravelQueueManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class QueueController extends Controller
{
    /**
     * Process a queue.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function process(Request $request)
    {
        try {
            
            set_time_limit(config('queue_manager.timeout'));
            
            $data = json_decode($request->get('data'));
            $service = unserialize($data->data->command);
            
            if (extension_loaded('newrelic')) {
                newrelic_name_transaction('queue.' . $service->getName());
            }
            
            $service->execute();

        } catch (\Throwable $e) {

            $response = [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'error_code' => $e->getCode(),
                'error_description' => $e->getMessage(),
            ];

            if (config('app.debug', false)) {
                $response['trace'] = $e->getTraceAsString();
                $response['file'] = $e->getFile();
                $response['line'] = $e->getLine();
                $response['class'] = get_class($e);
            }

            return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response = [
            'status_code' => Response::HTTP_OK,
        ];

        return response()->json($response, Response::HTTP_OK);
    }
}
