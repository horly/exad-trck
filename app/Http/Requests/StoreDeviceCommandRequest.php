<?php

namespace App\Http\Requests;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Vehicle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceCommandRequest extends FormRequest
{
    private bool $commandDeviceResolved = false;

    private ?Device $resolvedCommandDevice = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $device = $this->commandDevice();

        return $device instanceof Device
            && $this->user()?->can('control-engine', $device) === true;
    }

    public function commandDevice(): ?Device
    {
        if ($this->commandDeviceResolved) {
            return $this->resolvedCommandDevice;
        }

        $this->commandDeviceResolved = true;
        $device = $this->route('device');
        $user = $this->user();

        if ($device instanceof Device) {
            return $this->resolvedCommandDevice = $device;
        }

        if (is_numeric($device) && $user !== null) {
            return $this->resolvedCommandDevice = Device::query()
                ->visibleTo($user)
                ->find((int) $device);
        }

        $vehicle = $this->route('vehicle');
        $vehicleId = $vehicle instanceof Vehicle
            ? $vehicle->id
            : (is_numeric($vehicle) ? (int) $vehicle : null);

        if ($vehicleId === null || $user === null) {
            return null;
        }

        return $this->resolvedCommandDevice = Device::query()
            ->visibleTo($user)
            ->where('vehicle_id', $vehicleId)
            ->first();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in([DeviceCommand::ACTION_IMMOBILIZE, DeviceCommand::ACTION_RELEASE])],
            'output' => ['required', 'integer', Rule::in([1, 2])],
            'confirmation' => ['required', 'accepted'],
        ];
    }
}
