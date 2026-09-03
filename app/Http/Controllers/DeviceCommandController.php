<?php

namespace App\Http\Controllers;

use App\Actions\RequestDeviceCommandAction;
use App\Http\Requests\StoreDeviceCommandRequest;
use App\Models\DeviceCommand;
use Illuminate\Http\JsonResponse;

class DeviceCommandController extends Controller
{
    public function __construct(
        private readonly RequestDeviceCommandAction $requestCommand,
    ) {}

    public function __invoke(StoreDeviceCommandRequest $request): JsonResponse
    {
        $data = $request->validated();
        $device = $request->commandDevice();
        abort_if($device === null, 403);

        $command = $this->requestCommand->execute(
            $device,
            $request->user(),
            $data['action'],
            $request,
        );

        return response()->json([
            'ok' => true,
            'status' => $command->status,
            'message' => $command->action === DeviceCommand::ACTION_IMMOBILIZE
                ? __('trackers.engine_control_immobilize_requested')
                : __('trackers.engine_control_release_requested'),
        ], 202);
    }
}
