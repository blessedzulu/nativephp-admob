# Architecture Decision Records

This folder holds short markdown records of non-obvious decisions made during the project's life. New records are added as decisions land, never edited in place once accepted (use a new ADR to supersede an older one).

Format: each ADR is a file `NNNN-title-in-kebab-case.md`, numbered sequentially starting at `0001`.

Template:

```markdown
# NNNN. Title

- Status: proposed | accepted | superseded by ADR-XXXX
- Date: YYYY-MM-DD

## Context

What is the situation? What forces are at play?

## Decision

What we decided to do.

## Consequences

What becomes easier? Harder? What follows from this?
```

ADRs are intentionally short — half a page at most. The goal is "future contributor reads this in 90 seconds and understands why the code looks the way it does."
