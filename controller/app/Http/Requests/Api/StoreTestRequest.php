<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'node_id' => 'required|string|exists:nodes,slug',
            'test_type' => 'required|string|in:ping,traceroute,mtr,dns',
            'target' => 'required|string|max:255',
            'ip_family' => 'sometimes|string|in:auto,ipv4,ipv6',
        ];
    }

    public function messages(): array
    {
        return [
            'node_id.required' => 'Please select a node.',
            'node_id.exists' => 'The selected node does not exist.',
            'test_type.required' => 'Please select a test type.',
            'test_type.in' => 'Invalid test type. Allowed: ping, traceroute, mtr, dns.',
            'target.required' => 'Please enter a target hostname or IP address.',
        ];
    }
}
