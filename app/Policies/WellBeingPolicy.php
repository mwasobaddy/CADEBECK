<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class WellBeingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('access_wellbeing_reports');
    }

    public function viewTeamWellbeing(User $user): bool
    {
        return $user->hasPermissionTo('access_wellbeing_reports');
    }

    public function viewDepartmentWellbeing(User $user): bool
    {
        return $user->hasPermissionTo('access_wellbeing_reports');
    }

    public function viewEmployeeWellbeing(User $user, Employee $employee): bool
    {
        // Developer and Executive can see all
        if ($user->hasRole(['Developer', 'Executive'])) {
            return true;
        }

        // Manager N-1 can see employees under their supervision
        if ($user->hasRole('Manager N-1')) {
            return $this->isManagerN1SupervisorOf($user, $employee);
        }

        // Manager N-2 can see employees they supervise
        if ($user->hasRole('Manager N-2')) {
            return $this->isManagerN2SupervisorOf($user, $employee);
        }

        return false;
    }

    private function isManagerN1SupervisorOf(User $managerN1, Employee $employee): bool
    {
        // Same logic as LeaveRequestPolicy
        if ($employee->supervisor && $employee->supervisor->user->hasRole('Manager N-2')) {
            return $employee->supervisor->supervisor &&
                   $employee->supervisor->supervisor->user_id === $managerN1->id;
        }

        if ($employee->user->hasRole('Manager N-2')) {
            return $employee->supervisor &&
                   $employee->supervisor->user_id === $managerN1->id;
        }

        return false;
    }

    private function isManagerN2SupervisorOf(User $managerN2, Employee $employee): bool
    {
        return $employee->supervisor &&
               $employee->supervisor->user_id === $managerN2->id;
    }
}
