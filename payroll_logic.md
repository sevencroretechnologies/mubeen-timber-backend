# Payroll & Attendance Calculation Logic

This document explains how salary, attendance, and deductions are calculated in the system.

## 1. Salary Basics
*   **Base Salary**: The fixed monthly salary defined in the employee's profile.
*   **Total Days**: The total number of days in the selected month (e.g., 30 or 31).
*   **Per Day Salary**:
    ```math
    Per Day Salary = Base Salary / Total Days
    ```

## 2. Attendance Status
The system tracks attendance using the following statuses in `WorkLogs`:

| Status | Meaning | Salary Impact |
| :--- | :--- | :--- |
| **Present** | Employee was present for the full day. | 100% Pay |
| **Absent** | Employee was absent without leave. | **0% Pay (1 Day Deduction)** |
| **Half Day** | Employee worked partial hours (approx. 4h). | **50% Pay (0.5 Day Deduction)** |

> **Note**: If a "Half Day" is coupled with a "Half Day Leave", the deduction logic follows the Leave rules below.

## 3. Leave Types (Time Off)
Leaves are processed based on the **"Is Paid"** setting in the Leave Category.

| Leave Type Setting | Example | Impact on Salary |
| :--- | :--- | :--- |
| **Paid Leave** | Casual Leave, Sick Leave | **No Deduction** (Counted as Worked) |
| **Unpaid Leave** | LOP (Loss of Pay) | **1 Day Deduction** per day taken |

## 4. Calculation Formulas

### A. Calculate Deductible Days (LOP Days)
First, we calculate how many days the employee should **NOT** be paid for.

```math
LOP Days = (Absent Days) + (0.5 * Half Day Count) + (Unpaid Leave Days)
```
*Note: If a Half Day is covered by a 0.5 Paid Leave, it is not added to LOP.*

### B. Calculate Payable Days
The number of days the employee earns salary for.

```math
Payable Days = Total Days in Month - LOP Days
```

### C. Final Net Salary
```math
Net Salary = Payable Days * Per Day Salary
```

---

## 5. Example Scenario

**Employee**: John Doe
**Base Salary**: ₹30,000
**Month**: September (30 Days)
**Per Day Salary**: ₹1,000

| Activity | Count | LOP Calculation |
| :--- | :--- | :--- |
| **Absent** | 2 Days | 2 Days |
| **Half Day** | 2 Days | 1 Day (2 * 0.5) |
| **Sick Leave (Paid)** | 1 Day | 0 Days |
| **Urgent Leave (Unpaid)**| 1 Day | 1 Day |
| **TOTAL LOP** | | **4 Days** |

**Calculations**:
*   **Payable Days**: 30 - 4 = 26 Days
*   **Net Salary**: 26 * ₹1,000 = **₹26,000**
