---
automatic: true
description: Automatically update CLAUDE.md files when making architectural or design decisions
---

You are maintaining project context across conversations.

**CRITICAL**: After making architectural or design decisions, you MUST update the relevant CLAUDE.md file IN THE PROJECT DIRECTORY before completing your response to the user.

## ⚠️ What NOT to Do

- ❌ Do NOT create files in `~/.claude/projects/.../memory/` directory
- ❌ Do NOT create files with names like `MEMORY.md` or anything other than `CLAUDE.md`
- ❌ Do NOT skip documentation when you establish patterns
- ❌ Do NOT wait for the user to ask - update proactively

## ✅ What to Do

- ✅ Update CLAUDE.md files IN THE PROJECT (e.g., `/tests/CLAUDE.md`, `/src/Domain/CLAUDE.md`)
- ✅ Use the Write or Edit tool to update the appropriate CLAUDE.md file
- ✅ Do this BEFORE completing your response to the user
- ✅ Tell the user which file you updated and why

## When This Skill Triggers

**MUST update CLAUDE.md files after**:

1. **Implementing architecture tests** → Update `/tests/CLAUDE.md`
2. **Establishing naming conventions** → Update relevant layer CLAUDE.md
3. **Creating new patterns** (repositories, use cases, DTOs) → Update layer CLAUDE.md
4. **Fixing architectural violations** → Update `/tests/CLAUDE.md` or layer CLAUDE.md
5. **Adding new test strategies** → Update `/tests/CLAUDE.md`
6. **Setting up new libraries/frameworks** → Update root `/CLAUDE.md`
7. **Establishing command patterns** → Update relevant CLAUDE.md
8. **Creating validation patterns** → Update layer CLAUDE.md
9. **Setting up database patterns** → Update `/app/CLAUDE.md` or `/src/Infrastructure/CLAUDE.md`
10. **Defining error handling** → Update relevant layer CLAUDE.md

## Decision-to-File Mapping

**Map your decision to the correct CLAUDE.md file**:

- Architecture tests, test structure → `/tests/CLAUDE.md`
- Domain entity patterns, value objects → `/src/Domain/CLAUDE.md`
- Use case patterns, DTO rules → `/src/Application/CLAUDE.md`
- Repository implementations, adapters → `/src/Infrastructure/CLAUDE.md`
- React components, Inertia patterns → `/resources/CLAUDE.md`
- Laravel controllers, middleware → `/app/CLAUDE.md`
- Project-wide rules, library choices → `/CLAUDE.md` (root)

## Workflow Checklist

When you establish a pattern, follow these steps:

1. ✅ **Identify the decision scope** - Which layer/area does this affect?
2. ✅ **Determine the target file** - Which CLAUDE.md should be updated?
3. ✅ **Read the current file** - Use Read tool on the CLAUDE.md file
4. ✅ **Add concise documentation** (1-3 sentences max per rule)
5. ✅ **Use Write/Edit tool** - Update the project CLAUDE.md file
6. ✅ **Inform the user** - Tell them what you documented and where

## Documentation Format

Use this format when documenting:

```markdown
## [Category Name]

### [Specific Pattern]

[Concise rule in imperative tone, 1-3 sentences max]

**Example**:
- ✅ Correct: [code or pattern]
- ❌ Wrong: [anti-pattern]
```

**Examples of good documentation**:

1. For architecture tests:
   ```markdown
   ## Architecture Test Commands

   Run architecture tests only with `php artisan test --filter ArchTest`.
   Run full test suite with `php artisan test`.
   ```

2. For naming conventions:
   ```markdown
   ## Repository Naming

   Repository implementations must have vendor prefix (Eloquent*, Laravel*, Database*).
   Repository interfaces must end with Interface suffix (UserRepositoryInterface).

   **Example**:
   - ✅ Correct: EloquentUserRepository implements UserRepositoryInterface
   - ❌ Wrong: UserRepository implements UserRepo
   ```

3. For architectural patterns:
   ```markdown
   ## Final Classes & Mocking

   Only apply final to infrastructure implementations and use cases.
   Never apply final to interfaces, abstract classes, or classes that need mocking.
   This enables proper unit testing with interface-based mocks.
   ```

## Real Example from Recent Session

**What happened**: Implemented comprehensive architecture tests with 33 rules

**What should have been updated**: `/tests/CLAUDE.md`

**What was documented**:
- Overview of 33 architecture tests across 12 categories
- Pest Arch API patterns and limitations
- Testing strategies and commands
- Critical discovery about final classes preventing mocking

**Correct approach**:
```markdown
1. Identified scope: Test structure/patterns
2. Target file: /tests/CLAUDE.md
3. Read current file
4. Added comprehensive documentation
5. Used Write tool to update /tests/CLAUDE.md
6. Informed user: "Updated /tests/CLAUDE.md with architecture test documentation"
```

## Timing

**Update CLAUDE.md files IMMEDIATELY after**:

- Writing new architecture tests
- Creating new patterns (entities, repositories, use cases)
- Fixing architectural violations
- Establishing naming conventions
- Making library/framework choices
- Setting up new commands or scripts

**Do this BEFORE**:
- Completing your final response to the user
- Marking tasks as complete
- Moving to the next task

## Verification

After updating, verify:

1. ✅ File is in project directory (not hidden directory)
2. ✅ File is named CLAUDE.md (not MEMORY.md or other name)
3. ✅ Documentation is concise (1-3 sentences per rule)
4. ✅ Examples use ✅ and ❌ markers for clarity
5. ✅ User was informed which file was updated
