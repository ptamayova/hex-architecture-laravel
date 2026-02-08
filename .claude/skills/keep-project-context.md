---
automatic: true
description: Automatically update CLAUDE.md files when making architectural or design decisions
---

You are maintaining project context across conversations.

When you make decisions that will guide future development, immediately update the relevant CLAUDE.md file.

## Situations That Trigger This Skill

Update CLAUDE.md files when establishing:

- **Architectural patterns** - layer boundaries, dependency flow, port/adapter patterns
- **Naming conventions** - class suffixes, method naming, file naming, variable patterns
- **File organization** - where to place features, bounded contexts, shared code
- **Commands & scripts** - how to run tests, migrations, builds, deployments
- **Library choices** - which package to use for X, why A over B
- **Error handling** - exception hierarchies, where to catch, how to propagate
- **Validation patterns** - where to validate (domain/application/controller)
- **Database patterns** - migration structure, query approaches, seeding strategies
- **Testing strategies** - mock vs real, test organization, assertion patterns
- **Code organization** - method size limits, extraction patterns, class responsibilities
- **Dependency injection** - binding strategies, service provider patterns
- **Configuration** - where config lives, env variable usage, feature flags
- **Frontend patterns** - state management, data flow, component composition

## How to Update

1. **Identify the decision scope**
   - Domain pattern/rule → /src/Domain/CLAUDE.md
   - Application use case pattern → /src/Application/CLAUDE.md
   - Infrastructure implementation → /src/Infrastructure/CLAUDE.md
   - Frontend component/pattern → /resources/CLAUDE.md
   - Test structure/pattern → /tests/CLAUDE.md
   - Cross-cutting concern → /CLAUDE.md

2. **Document the decision**
   - Read the target CLAUDE.md file
   - Add a concise rule (1-2 sentences max)
   - Use active voice and imperative tone
   - Examples:
     - "Always name Domain entities with 'Entity' suffix"
     - "Use DTOs for all Application layer responses, never return domain entities"
     - "Run tests with `php artisan test --parallel`"
     - "Interface names must end with 'Port' (e.g., UserRepositoryPort)"
     - "Use shadcn/ui for UI components, never create custom alternatives"

3. **Keep it minimal**
   - Only document decisions that will guide future development
   - Avoid obvious or temporary patterns
   - Focus on "always do X" or "never do Y" rules

Run this automatically after completing features where you established new patterns or made choices that should persist across future development.
