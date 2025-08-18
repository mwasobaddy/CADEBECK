# CADEBECK HR Management System - AI Coding Assistant Instructions

This file provides explicit instructions for any AI coding assistant (GitHub Copilot, ChatGPT, etc.) working on the CADEBECK HR Management System. It defines coding style, architectural preferences, business logic, and project constraints to ensure consistent, secure, and maintainable code.

---

## Purpose

- Guide AI assistants on coding style, conventions, and architectural choices.
- Enforce CADEBECK-specific business logic and terminology.
- Minimize irrelevant or non-compliant code suggestions.
- Serve as a reusable reference for all contributors and AI prompts.

---

## Role of This File

✅ Aligns AI output with CADEBECK coding standards and business rules  
✅ Reduces irrelevant or off-spec suggestions  
✅ Ensures domain-specific terminology and workflows  
✅ Provides templates and examples for consistent code generation  
✅ Enforces architectural and security constraints

---

## Coding Style Guidelines

- **Indentation:** Use 4 spaces (no tabs).
- **Naming:** Use `snake_case` for variables, `StudlyCase` for classes, `camelCase` for methods.
- **Comments:** Use PHPDoc for classes/methods. Add business logic context where relevant.
- **Formatting:** Follow PSR-12 standards for PHP.  
- **Blade:** Use Tailwind CSS classes only. No inline styles or jQuery.

---

## Architectural Preferences

- **Framework:** Laravel 12+ (core), Livewire (reactivity), Tailwind CSS (styling) url https://laravel.com/docs/12.x.
- **RBAC:** Use Spatie Laravel Permission for all roles/permissions.
- **Database:** MySQL (prod), SQLite (dev). Use Eloquent ORM.
- **Frontend:** Livewire for all interactive components.
- **Testing:** Always write feature and unit tests for new logic.
- **Localization:** All user-facing text must support English and Swahili via Laravel translations.

---

## Constraints

- **Security:**  
    - Always use CSRF protection on forms.
    - Validate and sanitize all input (Form Requests).
    - Prevent SQL injection (use Eloquent).
    - Prevent XSS (escape output).
- **Styling:**  
    - Use Tailwind CSS utility classes only.
    - No inline styles or external CSS frameworks.
- **Business Logic:**  
    - Payroll must comply with Kenya Revenue Authority rules.
    - Leave management must validate balances and detect conflicts.
    - Employee onboarding must follow: creation → document upload → profile completion → orientation → activation.
- **Notifications:**  
    - Use Laravel notifications for all workflow events.
    - Support email and real-time broadcast.

---

## Templates & Examples

- **Livewire Component:**  
    - Use Spatie permission checks.
    - Validate with Form Requests.
    - Integrate audit logging.
    - Use Tailwind CSS for UI.
    - Support bilingual interface.

- **Migration:**  
    - Foreign key constraints.
    - Indexes for performance.
    - Audit trail fields (`created_at`, `updated_at`).
    - Soft deletes where appropriate.

- **Model:**  
    - Use Eloquent relationships (`belongsTo`, `hasMany`).
    - Integrate Spatie roles/permissions.
    - Add PHPDoc.

- **Test:**  
    - Feature tests for workflows.
    - Unit tests for business logic.
    - Authorization and validation tests.

---

## Reminders

- Always use domain-specific terminology (employee, payroll, leave, onboarding).
- Never use jQuery or inline JS/CSS.
- All code must be mobile-responsive and accessible.
- All user-facing text must be translatable.
- Always log audit events for critical actions.

---

## Example Prompt for AI

> "Generate a Livewire component for CADEBECK HR that manages employee onboarding, uses Spatie permissions, validates with Form Requests, logs audit events, uses Tailwind CSS, supports English/Swahili, and includes feature tests."

---

## Final Checklist

- [ ] Follows coding style and formatting rules
- [ ] Uses required architectural patterns
- [ ] Enforces all business logic constraints
- [ ] Implements security best practices
- [ ] Supports bilingual interface
- [ ] Includes tests for new features
- [ ] Uses domain terminology

---

**Reference this file for all AI-generated code and prompts for the CADEBECK HR Management System.**
