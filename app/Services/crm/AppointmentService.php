<?php

namespace App\Services\crm;

use App\Models\Appointment;
use Illuminate\Pagination\LengthAwarePaginator;

class AppointmentService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Appointment::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('scheduled_time', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function find(int $id): Appointment
    {
        return Appointment::findOrFail($id);
    }

    public function create(array $data): Appointment
    {
        return Appointment::create($data);
    }

    public function update(int $id, array $data): Appointment
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->update($data);
        return $appointment->fresh();
    }

    public function delete(int $id): bool
    {
        return Appointment::findOrFail($id)->delete();
    }
}
