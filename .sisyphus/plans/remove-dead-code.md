# Remove Dead Code from Laravel Project

## Context

### Original Request
Find and remove dead code (unused code) that is no longer used in the Laravel project. Focus on high-confidence, safe-to-delete items only.

### Interview Summary

**Key Discussions:**
- User wants high-confidence dead code only (safe to delete)
- Exclude any items that might be in use or require manual verification
- Complete analysis across all code categories: models, controllers, views, services, Livewire components, methods, config files, migrations

**Research Findings:**
- Launched 11 parallel analysis agents to comprehensively scan codebase
- Performed exhaustive grep searches across all file types (PHP, Blade, JavaScript)
- Cross-referenced findings with routes, tests, and usage patterns
- Identified 4 Livewire components and 1 method with 0 references

### Self-Review (Gap Analysis)

**Gaps Identified and Resolved:**

1. **Testing Strategy**: Added comprehensive test suite verification to ensure nothing breaks after deletion
2. **Rollback Protection**: Added git commit checkpoints after each deletion to enable safe rollback
3. **Verification Methods**: Included both automated tests and manual verification checks
4. **Edge Cases**: Checked for dynamic imports, reflection, and magic methods (none found for identified items)

**Assumptions Made:**
- grep searches captured all usage patterns (checked: @livewire, <livewire:, @volt, Livewire::test, method calls)
- NostrAuth::pleb() is not called via reflection or dynamic strings (verified by searching for "pleb" patterns)
- Livewire components are not loaded via Service Providers (verified by searching for use statements)

**Scope Boundaries:**
- INCLUDE: Only the 5 high-confidence dead code items identified
- EXCLUDE: Any potentially used code, experimental code, or items needing manual verification
- EXCLUDE: Tests or documentation files referencing dead code (cleanup separate concern)

---

## Work Objectives

### Core Objective
Safely remove 5 high-confidence dead code items from the Laravel codebase without breaking any functionality.

### Concrete Deliverables
- Deleted files: 4 Livewire components
- Modified file: 1 method removed from NostrAuth.php
- Clean git commits with rollback capability

### Definition of Done
- [ ] All 5 dead code items removed
- [ ] Test suite passes: `vendor/bin/sail artisan test --compact`
- [ ] No errors in browser logs
- [ ] Git commits created for each deletion with rollback capability

### Must Have
- Preserve all git history (commits, not commits that delete history)
- All existing tests must continue to pass
- No new errors or warnings introduced

### Must NOT Have (Guardrails)
- NO removal of any code with existing references
- NO deletion of test files (even if they test dead code)
- NO changes to documentation or README files
- NO modifications to git history
- NO deletion of files not explicitly listed in this plan

---

## Verification Strategy

### Test Decision
- **Infrastructure exists**: YES (Pest test suite with 20+ test files)
- **User wants tests**: YES (verify all existing tests still pass)
- **Framework**: Pest (already configured in project)
- **QA approach**: Run full test suite after each deletion to catch breakage immediately

### Test Execution Strategy

After each deletion:
1. Run the relevant subset of tests to verify no breakage
2. Run the full test suite to ensure comprehensive coverage

**Test Commands:**
```bash
# Run full test suite
vendor/bin/sail artisan test --compact

# Run specific test files if needed
vendor/bin/sail artisan test --compact tests/Feature/Livewire/Association/ProfileTest.php
```

**Acceptance Criteria for Each Task:**
- [ ] Code deleted successfully (file or method)
- [ ] `vendor/bin/sail artisan test --compact` → PASS (all tests)
- [ ] No new errors in browser logs (via laravel-boost_browser-logs)
- [ ] Git commit created with descriptive message
- [ ] Commit verified with `git log -1`

---

## Task Flow

```
Task 1 → Task 2 → Task 3 → Task 4 → Task 5
```

## Parallelization

| Group | Tasks | Reason |
|-------|--------|--------|
| N/A    | All sequential | Each deletion must be verified before proceeding to next |

| Task | Depends On | Reason |
|------|------------|--------|
| 2 | 1 | Verify first deletion works before proceeding |
| 3 | 2 | Verify second deletion works before proceeding |
| 4 | 3 | Verify third deletion works before proceeding |
| 5 | 4 | Verify fourth deletion works before proceeding |

---

## TODOs

- [ ] 1. Delete unused Livewire component: VoteForm

  **What to do:**
  - Delete file: `app/Livewire/Forms/VoteForm.php`
  - Run full test suite to verify nothing breaks
  - Create git commit for rollback capability

  **Must NOT do:**
  - Do NOT delete any tests that reference VoteForm (if any exist)
  - Do NOT delete any documentation files
  - Do NOT modify any other files

  **Parallelizable**: NO (sequential for safe rollback)

  **References**:

  **Evidence of 0 Usage** (from exhaustive analysis):
  - Grep for "VoteForm" in PHP files: Only 1 match (file definition itself)
  - Grep for "VoteForm" in Blade files: 0 matches
  - Grep for "VoteForm" in JS files: 0 matches
  - Livewire::test calls: No tests reference VoteForm
  - @livewire directives: No VoteForm usage
  - <livewire: tags: No VoteForm usage

  **Test References** (to verify no breakage):
  - No test files reference VoteForm (safe to delete)

  **Acceptance Criteria**:
  - [ ] File deleted: `app/Livewire/Forms/VoteForm.php`
  - [ ] Verify deletion: `ls app/Livewire/Forms/VoteForm.php` → Error (file not found)
  - [ ] `vendor/bin/sail artisan test --compact` → PASS (all tests, no failures)
  - [ ] Git commit created:
    ```bash
    git add app/Livewire/Forms/VoteForm.php
    git commit -m "remove: unused Livewire component VoteForm (0 references in codebase)"
    git log -1  # Verify commit
    ```
  - [ ] Evidence: Command output captured from test run and git log

  **Commit**: YES
  - Message: `remove: unused Livewire component VoteForm (0 references in codebase)`
  - Files: `app/Livewire/Forms/VoteForm.php`
  - Pre-commit: `vendor/bin/sail artisan test --compact`

---

- [ ] 2. Delete unused Livewire component: NotificationForm

  **What to do:**
  - Delete file: `app/Livewire/Forms/NotificationForm.php`
  - Run full test suite to verify nothing breaks
  - Create git commit for rollback capability

  **Must NOT do:**
  - Do NOT delete any tests that reference NotificationForm (if any exist)
  - Do NOT delete any documentation files
  - Do NOT modify any other files

  **Parallelizable**: NO (depends on 1)

  **References**:

  **Evidence of 0 Usage** (from exhaustive analysis):
  - Grep for "NotificationForm" in PHP files: Only 1 match (file definition itself)
  - Grep for "NotificationForm" in Blade files: 0 matches
  - Grep for "NotificationForm" in JS files: 0 matches
  - Livewire::test calls: No tests reference NotificationForm
  - @livewire directives: No NotificationForm usage
  - <livewire: tags: No NotificationForm usage

  **Test References** (to verify no breakage):
  - No test files reference NotificationForm (safe to delete)

  **Acceptance Criteria**:
  - [ ] File deleted: `app/Livewire/Forms/NotificationForm.php`
  - [ ] Verify deletion: `ls app/Livewire/Forms/NotificationForm.php` → Error (file not found)
  - [ ] `vendor/bin/sail artisan test --compact` → PASS (all tests, no failures)
  - [ ] Git commit created:
    ```bash
    git add app/Livewire/Forms/NotificationForm.php
    git commit -m "remove: unused Livewire component NotificationForm (0 references in codebase)"
    git log -1  # Verify commit
    ```
  - [ ] Evidence: Command output captured from test run and git log

  **Commit**: YES
  - Message: `remove: unused Livewire component NotificationForm (0 references in codebase)`
  - Files: `app/Livewire/Forms/NotificationForm.php`
  - Pre-commit: `vendor/bin/sail artisan test --compact`

---

- [ ] 3. Delete unused Livewire component: EinundzwanzigPlebTable

  **What to do:**
  - Delete file: `app/Livewire/EinundzwanzigPlebTable.php`
  - Run full test suite to verify nothing breaks
  - Create git commit for rollback capability

  **Must NOT do:**
  - Do NOT delete any tests that reference EinundzwanzigPlebTable (if any exist)
  - Do NOT delete any documentation files
  - Do NOT modify any other files

  **Parallelizable**: NO (depends on 2)

  **References**:

  **Evidence of 0 Usage** (from exhaustive analysis):
  - Grep for "EinundzwanzigPlebTable" in PHP files: Only 1 match (file definition itself)
  - Grep for "EinundzwanzigPlebTable" in Blade files: 0 matches
  - Grep for "EinundzwanzigPlebTable" in JS files: 0 matches
  - Livewire::test calls: No tests reference EinundzwanzigPlebTable
  - @livewire directives: No EinundzwanzigPlebTable usage
  - <livewire: tags: No EinundzwanzigPlebTable usage
  - Routes check: No routes reference EinundzwanzigPlebTable

  **File Context** (for reference):
  - Line 38 contains: `->view('components.detail')`
  - This is a PowerGrid component, but never instantiated

  **Test References** (to verify no breakage):
  - No test files reference EinundzwanzigPlebTable (safe to delete)

  **Acceptance Criteria**:
  - [ ] File deleted: `app/Livewire/EinundzwanzigPlebTable.php`
  - [ ] Verify deletion: `ls app/Livewire/EinundzwanzigPlebTable.php` → Error (file not found)
  - [ ] `vendor/bin/sail artisan test --compact` → PASS (all tests, no failures)
  - [ ] Git commit created:
    ```bash
    git add app/Livewire/EinundzwanzigPlebTable.php
    git commit -m "remove: unused Livewire component EinundzwanzigPlebTable (0 references in codebase)"
    git log -1  # Verify commit
    ```
  - [ ] Evidence: Command output captured from test run and git log

  **Commit**: YES
  - Message: `remove: unused Livewire component EinundzwanzigPlebTable (0 references in codebase)`
  - Files: `app/Livewire/EinundzwanzigPlebTable.php`
  - Pre-commit: `vendor/bin/sail artisan test --compact`

---

- [ ] 4. Delete unused Livewire component: MeetupTable

  **What to do:**
  - Delete file: `app/Livewire/MeetupTable.php`
  - Run full test suite to verify nothing breaks
  - Create git commit for rollback capability

  **Must NOT do:**
  - Do NOT delete any tests that reference MeetupTable (if any exist)
  - Do NOT delete any documentation files
  - Do NOT modify any other files

  **Parallelizable**: NO (depends on 3)

  **References**:

  **Evidence of 0 Usage** (from exhaustive analysis):
  - Grep for "MeetupTable" in PHP files: Only 1 match (file definition itself)
  - Grep for "MeetupTable" in Blade files: 0 matches
  - Grep for "MeetupTable" in JS files: 0 matches
  - Livewire::test calls: No tests reference MeetupTable
  - @livewire directives: No MeetupTable usage
  - <livewire: tags: No MeetupTable usage
  - Routes check: No routes reference MeetupTable

  **File Context** (for reference):
  - Final class extending PowerGridComponent
  - Never instantiated anywhere in codebase

  **Test References** (to verify no breakage):
  - No test files reference MeetupTable (safe to delete)

  **Acceptance Criteria**:
  - [ ] File deleted: `app/Livewire/MeetupTable.php`
  - [ ] Verify deletion: `ls app/Livewire/MeetupTable.php` → Error (file not found)
  - [ ] `vendor/bin/sail artisan test --compact` → PASS (all tests, no failures)
  - [ ] Git commit created:
    ```bash
    git add app/Livewire/MeetupTable.php
    git commit -m "remove: unused Livewire component MeetupTable (0 references in codebase)"
    git log -1  # Verify commit
    ```
  - [ ] Evidence: Command output captured from test run and git log

  **Commit**: YES
  - Message: `remove: unused Livewire component MeetupTable (0 references in codebase)`
  - Files: `app/Livewire/MeetupTable.php`
  - Pre-commit: `vendor/bin/sail artisan test --compact`

---

- [ ] 5. Remove unused method: NostrAuth::pleb()

  **What to do:**
  - Edit file: `app/Support/NostrAuth.php`
  - Remove method `pleb()` at lines 57-60
  - Run full test suite to verify nothing breaks
  - Create git commit for rollback capability

  **Must NOT do:**
  - Do NOT delete the entire NostrAuth.php file (other methods are used)
  - Do NOT delete any tests that use NostrAuth::pleb() (none exist)
  - Do NOT modify any other methods in the class

  **Parallelizable**: NO (depends on 4)

  **References**:

  **File Context** (`app/Support/NostrAuth.php`):
  ```php
  class NostrAuth
  {
      public static function login(string $pubkey): void { ... }
      public static function logout(): void { ... }
      public static function user(): ?NostrUser { ... }  // USED
      public static function check(): bool { ... }  // USED (16 usages)
      public static function pubkey(): ?string { ... }  // USED (9 usages)

      public static function pleb(): ?object  // DELETE THIS
      {
          return self::user()?->getPleb();
      }
  }
  ```

  **Evidence of 0 Usage** (from exhaustive analysis):
  - Grep for "NostrAuth::pleb(" in PHP files: 0 matches
  - Grep for "NostrAuth::pleb(" in Blade files: 0 matches
  - Grep for "pleb" in relation to NostrAuth class: 0 matches
  - Other NostrAuth methods are actively used:
    - `login()`: 60+ usages in tests and auth-button
    - `logout()`: 1 usage in routes/web.php
    - `user()`: 1 usage in WithNostrAuth trait
    - `check()`: 16 usages in Blade views
    - `pubkey()`: 9 usages in Blade views
  - `pleb()`: 0 usages anywhere in codebase

  **Test References** (to verify no breakage):
  - No test files use NostrAuth::pleb() (safe to remove)

  **Acceptance Criteria**:
  - [ ] Method removed: Lines 57-60 deleted from `app/Support/NostrAuth.php`
  - [ ] Verify removal: `grep -n "function pleb" app/Support/NostrAuth.php` → No matches
  - [ ] `vendor/bin/sail artisan test --compact` → PASS (all tests, no failures)
  - [ ] Git commit created:
    ```bash
    git add app/Support/NostrAuth.php
    git commit -m "remove: unused method NostrAuth::pleb() (0 usages in codebase)"
    git log -1  # Verify commit
    ```
  - [ ] Evidence: Command output captured from test run and git log

  **Commit**: YES
  - Message: `remove: unused method NostrAuth::pleb() (0 usages in codebase)`
  - Files: `app/Support/NostrAuth.php`
  - Pre-commit: `vendor/bin/sail artisan test --compact`

---

## Commit Strategy

| After Task | Message | Files | Verification |
|------------|---------|---------|--------------|
| 1 | `remove: unused Livewire component VoteForm (0 references in codebase)` | `app/Livewire/Forms/VoteForm.php` | `vendor/bin/sail artisan test --compact` |
| 2 | `remove: unused Livewire component NotificationForm (0 references in codebase)` | `app/Livewire/Forms/NotificationForm.php` | `vendor/bin/sail artisan test --compact` |
| 3 | `remove: unused Livewire component EinundzwanzigPlebTable (0 references in codebase)` | `app/Livewire/EinundzwanzigPlebTable.php` | `vendor/bin/sail artisan test --compact` |
| 4 | `remove: unused Livewire component MeetupTable (0 references in codebase)` | `app/Livewire/MeetupTable.php` | `vendor/bin/sail artisan test --compact` |
| 5 | `remove: unused method NostrAuth::pleb() (0 usages in codebase)` | `app/Support/NostrAuth.php` | `vendor/bin/sail artisan test --compact` |

---

## Success Criteria

### Verification Commands
```bash
# Verify all deletions completed
ls app/Livewire/Forms/VoteForm.php  # Should fail (file not found)
ls app/Livewire/Forms/NotificationForm.php  # Should fail (file not found)
ls app/Livewire/EinundzwanzigPlebTable.php  # Should fail (file not found)
ls app/Livewire/MeetupTable.php  # Should fail (file not found)
grep "function pleb" app/Support/NostrAuth.php  # Should find no matches

# Verify all tests pass
vendor/bin/sail artisan test --compact  # Should show PASS for all tests

# Verify git history
git log --oneline -5  # Should show 5 new commits
```

### Final Checklist
- [ ] All 4 Livewire component files deleted
- [ ] NostrAuth::pleb() method removed
- [ ] All tests pass (0 failures)
- [ ] No new errors in browser logs
- [ ] 5 git commits created for rollback capability
- [ ] No other files modified or deleted
- [ ] Git history intact (no history rewrites)

### Rollback Instructions (If Something Breaks)

If any tests fail or functionality breaks:
```bash
# Rollback all deletions
git reset --hard HEAD~5  # Undo last 5 commits

# Verify rollback successful
git log --oneline -3
vendor/bin/sail artisan test --compact  # Tests should pass again
```
